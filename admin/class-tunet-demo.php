<?php
/**
 * Tunet Core · Demo importer + recommended-plugins wizard.
 *
 * Engine-side and theme-agnostic: reads the theme's `tunet_core_demo_manifest`
 * and builds content, recording every created ID for replace/rollback. The
 * recommended plugins and the conditional Woo/CF7 content are declared by the
 * theme's manifest, not hardcoded here.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Demo importer.
 */
class Tunet_Core_Demo {

	const RECORD     = 'tunet_core_demo_record';
	const MENU_SLUG  = 'tunet-demo';
	const CAPABILITY = 'manage_options';
	const NONCE      = 'tunet_demo';

	/**
	 * Plugins the wizard offers — resolved from the ACTIVE THEME's manifest
	 * (`plugins` key), never hardcoded, so the engine stays theme-agnostic (§12).
	 * The theme declares which plugins its demo uses and whether each is optional;
	 * the engine only supplies detection metadata (the plugin file) for the ones
	 * it knows how to install from wp.org. A theme may pass its own `file`/`label`
	 * for a plugin the engine doesn't know.
	 *
	 * A theme that uses no third-party plugin simply omits the key → the wizard
	 * shows nothing (e.g. a theme without WooCommerce never sees Woo). All entries
	 * default to optional: the wizard recommends, it never forces an install.
	 *
	 * Manifest shape:  'plugins' => array( 'contact-form-7' => array( 'optional' => true ), … )
	 *
	 * @return array slug => [ file, label, optional ].
	 */
	public static function recommended_plugins() {
		// Detection metadata for the plugins the engine knows (slug => file + default label).
		$known = array(
			'contact-form-7' => array( 'file' => 'contact-form-7/wp-contact-form-7.php', 'label' => 'Contact Form 7' ),
			'woocommerce'    => array( 'file' => 'woocommerce/woocommerce.php', 'label' => 'WooCommerce' ),
		);

		$declared = self::manifest()['plugins'] ?? array();
		$out      = array();
		foreach ( (array) $declared as $slug => $info ) {
			$slug = sanitize_key( (string) $slug );
			$info = is_array( $info ) ? $info : array();
			$base = isset( $known[ $slug ] ) ? $known[ $slug ] : array();
			$file = ! empty( $info['file'] ) ? $info['file'] : ( $base['file'] ?? '' );
			if ( '' === $slug || ! $file ) {
				continue; // can't detect/install without a known slug + plugin file path
			}
			$out[ $slug ] = array(
				'file'     => $file,
				'label'    => ! empty( $info['label'] ) ? $info['label'] : ( $base['label'] ?? $slug ),
				'optional' => isset( $info['optional'] ) ? (bool) $info['optional'] : true,
			);
		}
		return $out;
	}

	/**
	 * Wire hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 11 );
		add_action( 'wp_ajax_tunet_demo_step', array( $this, 'ajax_step' ) );
		add_action( 'wp_ajax_tunet_demo_rollback', array( $this, 'ajax_rollback' ) );
		add_action( 'wp_ajax_tunet_demo_plugins', array( $this, 'ajax_plugins' ) );
		add_action( 'wp_ajax_tunet_demo_install', array( $this, 'ajax_install' ) );
	}

	/* ---- Manifest --------------------------------------------------- */

	/**
	 * @return array
	 */
	public static function manifest() {
		$m = apply_filters( 'tunet_core_demo_manifest', array() );
		return is_array( $m ) ? $m : array();
	}

	/* ---- Record ----------------------------------------------------- */

	/**
	 * @return array
	 */
	public static function get_record() {
		$r = get_option( self::RECORD, array() );
		return is_array( $r ) ? $r : array();
	}

	/**
	 * Begin a fresh record (does not delete prior content — see replace()).
	 */
	public function start_record() {
		update_option(
			self::RECORD,
			array(
				'version'      => defined( 'TUNET_CORE_VERSION' ) ? TUNET_CORE_VERSION : '0',
				'created_at'   => time(),
				'posts'        => array(),
				'projects'     => array(),
				'attachments'  => array(),
				'products'     => array(),
				'product_cats' => array(),
				'project_types'=> array(),
				'cf7'          => 0,
				'images_map'   => array(),
				'url_map'      => array(),
			)
		);
	}

	/**
	 * Track a created ID in a bucket.
	 *
	 * @param string $bucket Bucket key.
	 * @param int    $id     ID.
	 */
	public function track( $bucket, $id ) {
		$r = self::get_record();
		if ( ! isset( $r[ $bucket ] ) || ! is_array( $r[ $bucket ] ) ) {
			$r[ $bucket ] = array();
		}
		$r[ $bucket ][] = (int) $id;
		update_option( self::RECORD, $r );
	}

	/**
	 * Store a scalar/array value on the record (e.g. 'cf7', 'images_map', 'url_map').
	 *
	 * @param string $key   Record key.
	 * @param mixed  $value Value.
	 */
	public function set_record( $key, $value ) {
		$r         = self::get_record();
		$r[ $key ] = $value;
		update_option( self::RECORD, $r );
	}

	/**
	 * Delete the record option (content deletion handled by replace()).
	 */
	public function clear_record() {
		delete_option( self::RECORD );
	}

	/* ---- Step pipeline ---------------------------------------------- */

	/**
	 * Ordered import steps. Conditional steps appear only when their plugin is
	 * active. Builder callbacks are added in later tasks.
	 *
	 * @return array<int,array{label:string,cb:callable}>
	 */
	public function import_steps() {
		$steps   = array();
		$steps[] = array( 'label' => __( 'Preparing…', 'tunet' ), 'cb' => array( $this, 'step_begin' ) );
		$steps[] = array( 'label' => __( 'Importing media…', 'tunet' ), 'cb' => array( $this, 'step_media' ) );
		if ( class_exists( 'WPCF7_ContactForm' ) ) {
			$steps[] = array( 'label' => __( 'Creating contact form…', 'tunet' ), 'cb' => array( $this, 'step_cf7' ) );
		}
		$steps[] = array( 'label' => __( 'Creating projects…', 'tunet' ), 'cb' => array( $this, 'step_projects' ) );
		$steps[] = array( 'label' => __( 'Creating journal posts…', 'tunet' ), 'cb' => array( $this, 'step_posts' ) );
		$steps[] = array( 'label' => __( 'Creating pages…', 'tunet' ), 'cb' => array( $this, 'step_pages' ) );
		if ( class_exists( 'WooCommerce' ) ) {
			$steps[] = array( 'label' => __( 'Creating products…', 'tunet' ), 'cb' => array( $this, 'step_products' ) );
		}
		$steps[] = array( 'label' => __( 'Finishing…', 'tunet' ), 'cb' => array( $this, 'step_finalize' ) );
		return $steps;
	}

	/**
	 * Step 0: replace any prior import, then start a fresh record.
	 */
	public function step_begin() {
		if ( self::get_record() ) {
			$this->replace();
		}
		$this->start_record();
	}

	/** Placeholder builder steps — implemented in later tasks. */

	/**
	 * Copy a theme-relative file into the media library.
	 *
	 * @param string $relpath e.g. 'assets/img/work/fintech.webp'.
	 * @return int Attachment ID (0 on failure).
	 */
	public function sideload_image( $relpath ) {
		$src = get_theme_file_path( $relpath );
		if ( ! file_exists( $src ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return 0;
		}
		$filename = wp_unique_filename( $uploads['path'], basename( $src ) );
		$dest     = trailingslashit( $uploads['path'] ) . $filename;

		if ( ! @copy( $src, $dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return 0;
		}

		$filetype = wp_check_filetype( $dest, null );
		$attach   = array(
			'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/webp',
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_status'    => 'inherit',
		);
		$att_id = wp_insert_attachment( $attach, $dest );
		if ( is_wp_error( $att_id ) || ! $att_id ) {
			return 0;
		}
		wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $dest ) );
		return (int) $att_id;
	}

	/**
	 * Every demo image bundled in the theme (assets/img, recursive).
	 *
	 * Lets the importer pull ALL of them into the Media Library so the buyer can
	 * manage/replace them, without having to list each one in the manifest. Skips
	 * non-photographic assets (svg icons, etc.).
	 *
	 * @return string[] Theme-relative paths, e.g. 'assets/img/dish-steak.webp'.
	 */
	private static function demo_image_files() {
		$base = get_theme_file_path( 'assets/img' );
		if ( ! is_dir( $base ) ) {
			return array();
		}
		$out = array();
		$it  = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( ! $file->isFile() || ! preg_match( '/\.(webp|jpe?g|png|gif)$/i', $file->getFilename() ) ) {
				continue;
			}
			$rel   = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $base ) + 1 ) );
			$out[] = 'assets/img/' . $rel;
		}
		sort( $out );
		return $out;
	}

	/**
	 * Import every demo image into the Media Library and build the maps the rest
	 * of the importer needs.
	 *
	 * - `images_map` (key→id): manifest images referenced by key (featured images,
	 *   Woo products) — keeps `image_id()` working.
	 * - `url_map` (theme-file URL → [id,url]): used by `wire_media()` to point the
	 *   patterns' inline images at the imported attachments (editable by the buyer).
	 *
	 * Every file is imported once (dedup) and tracked so rollback removes it.
	 */
	public function step_media() {
		$manifest_images = self::manifest()['images'] ?? array(); // key => relpath.

		// All demo images: the manifest ones + everything under assets/img.
		$relpaths = array_values( $manifest_images );
		foreach ( self::demo_image_files() as $rel ) {
			if ( ! in_array( $rel, $relpaths, true ) ) {
				$relpaths[] = $rel;
			}
		}

		$by_rel  = array(); // relpath => id (import each file once).
		$url_map = array(); // theme-file URL => [ id, url ].
		foreach ( $relpaths as $rel ) {
			if ( isset( $by_rel[ $rel ] ) ) {
				continue;
			}
			$id = $this->sideload_image( $rel );
			if ( ! $id ) {
				continue;
			}
			$by_rel[ $rel ] = $id;
			$this->track( 'attachments', $id );
			$url_map[ get_theme_file_uri( $rel ) ] = array(
				'id'  => $id,
				'url' => wp_get_attachment_url( $id ),
			);
		}

		// key => id, for images referenced by key (featured images / products).
		$images_map = array();
		foreach ( $manifest_images as $key => $rel ) {
			if ( isset( $by_rel[ $rel ] ) ) {
				$images_map[ $key ] = $by_rel[ $rel ];
			}
		}

		$this->set_record( 'images_map', $images_map );
		$this->set_record( 'url_map', $url_map );
	}

	/**
	 * Resolve a manifest image key to its attachment ID (post-media step).
	 *
	 * @param string $key Image key.
	 * @return int
	 */
	public function image_id( $key ) {
		$map = self::get_record()['images_map'] ?? array();
		return isset( $map[ $key ] ) ? (int) $map[ $key ] : 0;
	}

	/**
	 * Create a Contact Form 7 form from the manifest (CF7 active only).
	 */
	public function step_cf7() {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return;
		}
		$cfg   = self::manifest()['cf7'] ?? array();
		$title = $cfg['title'] ?? 'Contact';

		$form_markup = "<label>" . __( 'Your name', 'tunet' ) . "\n    [text* your-name]</label>\n\n"
			. "<label>" . __( 'Your email', 'tunet' ) . "\n    [email* your-email]</label>\n\n"
			. "<label>" . __( 'Subject', 'tunet' ) . "\n    [text your-subject]</label>\n\n"
			. "<label>" . __( 'Your message (optional)', 'tunet' ) . "\n    [textarea your-message]</label>\n\n"
			. "[submit \"" . __( 'Submit', 'tunet' ) . "\"]";

		$form = WPCF7_ContactForm::get_template( array( 'title' => $title ) );
		$form->set_properties(
			array(
				'form' => $form_markup,
				'mail' => array(
					'active'             => true,
					'subject'            => '[your-subject]',
					'sender'             => '[your-name] <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
					'recipient'          => get_option( 'admin_email' ),
					'body'               => "From: [your-name] <[your-email]>\n\n[your-message]",
					'additional_headers' => 'Reply-To: [your-email]',
				),
			)
		);
		$id = $form->save();
		if ( $id ) {
			$this->set_record( 'cf7', (int) $id );
		}
	}

	/**
	 * Expand a theme pattern into self-contained, Media-Library-wired markup.
	 *
	 * Inlines any nested `wp:pattern` references into real blocks and points the
	 * images at the attachments imported in step_media, so the saved page is fully
	 * editable by the buyer (not a thin reference rendered from theme files).
	 *
	 * @param string $slug e.g. 'ember/menu'.
	 * @return string
	 */
	public function expand_pattern( $slug ) {
		return $this->wire_media( $this->expand_pattern_raw( $slug, array() ) );
	}

	/**
	 * Run a pattern's PHP and recursively inline its nested `wp:pattern`
	 * references into real block markup.
	 *
	 * Re-includes the file each call (rather than the cached registered content)
	 * so the contact patterns' CF7 conditional resolves against the just-created
	 * form. `$seen` guards against reference loops.
	 *
	 * @param string   $slug e.g. 'ember/menu'.
	 * @param string[] $seen Slugs already expanded in this branch.
	 * @return string
	 */
	public function expand_pattern_raw( $slug, $seen = array() ) {
		$name = preg_replace( '#^[^/]+/#', '', $slug ); // strip 'ember/'
		$file = get_theme_file_path( 'patterns/' . $name . '.php' );
		if ( ! file_exists( $file ) ) {
			return '';
		}
		ob_start();
		include $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		$content = (string) ob_get_clean();

		$seen[] = $slug;
		return (string) preg_replace_callback(
			'#<!--\s*wp:pattern\s+(\{.*?\})\s*/-->#s',
			function ( $m ) use ( $seen ) {
				$attrs = json_decode( $m[1], true );
				$ref   = ( is_array( $attrs ) && isset( $attrs['slug'] ) ) ? (string) $attrs['slug'] : '';
				if ( '' === $ref || in_array( $ref, $seen, true ) ) {
					return ''; // unknown ref or loop guard → drop.
				}
				return $this->expand_pattern_raw( $ref, $seen );
			},
			$content
		);
	}

	/**
	 * Point a pattern's images at the Media Library copies imported in step_media,
	 * so the buyer can edit/replace them from the editor. Rewrites core/image
	 * blocks (adds the attachment id + wp-image-{id} class + library URL) and
	 * tunet/section image backgrounds (sets bgImageId/bgImageUrl). No-op outside an
	 * import (empty url_map) → patterns keep their theme-file URLs.
	 *
	 * @param string $content Expanded (inlined) pattern markup.
	 * @return string
	 */
	private function wire_media( $content ) {
		$map = self::get_record()['url_map'] ?? array();
		if ( empty( $map ) || '' === $content ) {
			return $content;
		}
		return serialize_blocks( $this->wire_media_blocks( parse_blocks( $content ), $map ) );
	}

	/**
	 * Recursive worker for wire_media().
	 *
	 * @param array $blocks parse_blocks() output.
	 * @param array $map    theme-file URL => [ id, url ].
	 * @return array
	 */
	private function wire_media_blocks( $blocks, $map ) {
		foreach ( $blocks as &$block ) {
			$name = isset( $block['blockName'] ) ? $block['blockName'] : '';

			if ( 'core/image' === $name && empty( $block['attrs']['id'] ) ) {
				$html = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
				foreach ( $map as $theme_url => $att ) {
					if ( '' === $theme_url || false === strpos( $html, $theme_url ) ) {
						continue;
					}
					$id                         = (int) $att['id'];
					$block['attrs']['id']       = $id;
					$block['attrs']['sizeSlug'] = empty( $block['attrs']['sizeSlug'] ) ? 'large' : $block['attrs']['sizeSlug'];
					$block['innerHTML']         = $this->add_img_class( str_replace( $theme_url, $att['url'], $block['innerHTML'] ), 'wp-image-' . $id );
					foreach ( (array) $block['innerContent'] as $i => $chunk ) {
						if ( is_string( $chunk ) ) {
							$block['innerContent'][ $i ] = $this->add_img_class( str_replace( $theme_url, $att['url'], $chunk ), 'wp-image-' . $id );
						}
					}
					break;
				}
			} elseif ( 'tunet/section' === $name
				&& isset( $block['attrs']['bgType'], $block['attrs']['bgImageUrl'] )
				&& 'image' === $block['attrs']['bgType']
				&& empty( $block['attrs']['bgImageId'] )
				&& isset( $map[ $block['attrs']['bgImageUrl'] ] ) ) {
				$att                          = $map[ $block['attrs']['bgImageUrl'] ];
				$block['attrs']['bgImageId']  = (int) $att['id'];
				$block['attrs']['bgImageUrl'] = $att['url'];
			} elseif ( 'tunet/before-after' === $name ) {
				// Comparison block: wire both the before and after images.
				foreach ( array( 'before', 'after' ) as $side ) {
					$url_key = $side . 'Url';
					$id_key  = $side . 'Id';
					if ( ! empty( $block['attrs'][ $url_key ] )
						&& empty( $block['attrs'][ $id_key ] )
						&& isset( $map[ $block['attrs'][ $url_key ] ] ) ) {
						$att                       = $map[ $block['attrs'][ $url_key ] ];
						$block['attrs'][ $id_key ]  = (int) $att['id'];
						$block['attrs'][ $url_key ] = $att['url'];
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->wire_media_blocks( $block['innerBlocks'], $map );
			}
		}
		unset( $block );
		return $blocks;
	}

	/**
	 * Add a class to the first <img> in a markup chunk (append to an existing
	 * class attribute, or add one). Used to attach wp-image-{id}.
	 *
	 * @param string $html  Markup.
	 * @param string $class Class to add.
	 * @return string
	 */
	private function add_img_class( $html, $class ) {
		return (string) preg_replace_callback(
			'/<img\b([^>]*?)(\s*\/?)>/i',
			function ( $m ) use ( $class ) {
				$attrs = $m[1];
				if ( preg_match( '/\sclass\s*=\s*("|\').*?\1/i', $attrs ) ) {
					$attrs = preg_replace( '/(\sclass\s*=\s*("|\'))(.*?)(\2)/i', '${1}${3} ' . $class . '${4}', $attrs, 1 );
				} else {
					$attrs .= ' class="' . $class . '"';
				}
				return '<img' . $attrs . $m[2] . '>';
			},
			$html,
			1
		);
	}
	/**
	 * Create the `project` CPT entries from the manifest.
	 */
	public function step_projects() {
		$projects = self::manifest()['projects'] ?? array();
		foreach ( $projects as $p ) {
			if ( get_page_by_path( $p['slug'], OBJECT, 'project' ) ) {
				continue; // idempotent guard within a single build
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'project',
					'post_status'  => 'publish',
					'post_title'   => $p['title'],
					'post_name'    => $p['slug'],
					'post_excerpt' => $p['excerpt'] ?? '',
					'post_content' => $p['content'] ?? '',
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
			$this->track( 'projects', $id );

			if ( ! empty( $p['types'] ) ) {
				$term_ids = $this->ensure_terms( $p['types'], 'project_type' );
				wp_set_object_terms( $id, $term_ids, 'project_type' );
			}
			foreach ( (array) ( $p['meta'] ?? array() ) as $mk => $mv ) {
				update_post_meta( $id, $mk, $mv );
			}
			$att = $this->image_id( $p['image'] ?? '' );
			if ( $att ) {
				set_post_thumbnail( $id, $att );
			}
		}
	}
	/**
	 * Create journal posts from the manifest.
	 */
	public function step_posts() {
		$posts = self::manifest()['posts'] ?? array();
		foreach ( $posts as $p ) {
			if ( get_page_by_path( $p['slug'], OBJECT, 'post' ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'post',
					'post_status'  => 'publish',
					'post_title'   => $p['title'],
					'post_name'    => $p['slug'],
					'post_content' => $p['content'] ?? '',
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
			$this->track( 'posts', $id );

			if ( ! empty( $p['category'] ) ) {
				$cat_ids = $this->ensure_terms( array( $p['category'] ), 'category' );
				wp_set_post_terms( $id, $cat_ids, 'category' );
			}
			$att = $this->image_id( $p['image'] ?? '' );
			if ( $att ) {
				set_post_thumbnail( $id, $att );
			}
		}
	}
	/**
	 * Ensure terms exist; return their IDs. Tracks newly-created project_type terms.
	 *
	 * @param string[] $names    Term names.
	 * @param string   $taxonomy Taxonomy.
	 * @return int[]
	 */
	private function ensure_terms( $names, $taxonomy ) {
		$ids = array();
		foreach ( $names as $name ) {
			$term = term_exists( $name, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $name, $taxonomy );
				if ( ! is_wp_error( $term ) && 'project_type' === $taxonomy ) {
					$this->track( 'project_types', (int) $term['term_id'] );
				}
			}
			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				$ids[] = (int) $term['term_id'];
			}
		}
		return $ids;
	}

	/**
	 * Create demo pages from patterns.
	 */
	public function step_pages() {
		$pages = self::manifest()['pages'] ?? array();
		foreach ( $pages as $page ) {
			if ( get_page_by_path( $page['slug'] ) ) {
				continue;
			}
			$content = $this->expand_pattern( $page['pattern'] );
			if ( '' === $content ) {
				continue; // pattern missing — skip, don't abort
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_content' => $content,
				),
				true
			);
			if ( ! is_wp_error( $id ) && $id ) {
				$this->track( 'posts', $id );
				if ( ! empty( $page['front'] ) ) {
					$this->set_front_page( (int) $id );
				}
			}
		}
	}

	/**
	 * Mark a created page as the static front page, remembering the previous
	 * reading settings so rollback can restore them.
	 *
	 * @param int $id Page ID.
	 */
	private function set_front_page( $id ) {
		$r = self::get_record();
		if ( ! isset( $r['prev_front'] ) ) {
			$this->set_record(
				'prev_front',
				array(
					'show_on_front' => get_option( 'show_on_front' ),
					'page_on_front' => (int) get_option( 'page_on_front' ),
				)
			);
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $id );
	}

	/**
	 * Create WooCommerce products from the manifest (Woo active only).
	 */
	public function step_products() {
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return;
		}
		$woo = self::manifest()['woo'] ?? array();

		// Categories.
		$cat_map = array();
		foreach ( (array) ( $woo['categories'] ?? array() ) as $cat_name ) {
			$term = term_exists( $cat_name, 'product_cat' );
			if ( ! $term ) {
				$term = wp_insert_term( $cat_name, 'product_cat' );
				if ( ! is_wp_error( $term ) ) {
					$this->track( 'product_cats', (int) $term['term_id'] );
				}
			}
			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				$cat_map[ $cat_name ] = (int) $term['term_id'];
			}
		}

		foreach ( (array) ( $woo['products'] ?? array() ) as $pr ) {
			if ( get_page_by_path( $pr['slug'], OBJECT, 'product' ) ) {
				continue;
			}
			$product = new WC_Product_Simple();
			$product->set_name( $pr['title'] );
			$product->set_slug( $pr['slug'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_regular_price( (string) $pr['price'] );
			$product->set_short_description( $pr['short'] ?? '' );
			$product->set_description( $pr['desc'] ?? '' );

			$cat_ids = array();
			foreach ( (array) ( $pr['cats'] ?? array() ) as $c ) {
				if ( isset( $cat_map[ $c ] ) ) {
					$cat_ids[] = $cat_map[ $c ];
				}
			}
			if ( $cat_ids ) {
				$product->set_category_ids( $cat_ids );
			}
			$att = $this->image_id( $pr['image'] ?? '' );
			if ( $att ) {
				$product->set_image_id( $att );
			}
			$pid = $product->save();
			if ( $pid ) {
				$this->track( 'products', $pid );
			}
		}
	}

	/**
	 * Final step: make the demo's pretty URLs work on a brand-new site. A fresh
	 * WordPress defaults permalinks to "Plain", which 404s /work/, /studio/, etc.
	 * Switch to postname if still unset, then flush so the project CPT and the
	 * created pages resolve. NOT reverted on rollback — reverting to Plain would
	 * break the whole site's URLs, not just the demo's.
	 */
	public function step_finalize() {
		if ( '' === get_option( 'permalink_structure' ) ) {
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure( '/%postname%/' );
		}
		flush_rewrite_rules();
	}

	/**
	 * Delete everything tracked in the current record, then clear it.
	 */
	public function replace() {
		$r = self::get_record();
		if ( ! $r ) {
			return;
		}

		// Posts of all kinds (pages, journal posts, projects, products).
		foreach ( array( 'posts', 'projects', 'products', 'attachments' ) as $bucket ) {
			foreach ( (array) ( $r[ $bucket ] ?? array() ) as $id ) {
				wp_delete_post( (int) $id, true );
			}
		}

		// CF7 form (a post too).
		if ( ! empty( $r['cf7'] ) ) {
			wp_delete_post( (int) $r['cf7'], true );
		}

		// Terms.
		foreach ( (array) ( $r['project_types'] ?? array() ) as $tid ) {
			wp_delete_term( (int) $tid, 'project_type' );
		}
		foreach ( (array) ( $r['product_cats'] ?? array() ) as $tid ) {
			wp_delete_term( (int) $tid, 'product_cat' );
		}

		// Restore the previous front-page settings if the import changed them.
		if ( ! empty( $r['prev_front'] ) && is_array( $r['prev_front'] ) ) {
			update_option( 'show_on_front', $r['prev_front']['show_on_front'] );
			update_option( 'page_on_front', (int) $r['prev_front']['page_on_front'] );
		}

		$this->clear_record();
	}

	/* ---- AJAX -------------------------------------------------------- */

	/**
	 * Run one import step.
	 */
	public function ajax_step() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tunet' ) ) );
		}

		$steps = $this->import_steps();
		$total = count( $steps );
		$step  = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 0;

		if ( $step < $total && is_callable( $steps[ $step ]['cb'] ) ) {
			// Buffer (and discard) any stray output a step might trigger (plugin
			// notices, sideload warnings…) so it can't corrupt the JSON response.
			ob_start();
			call_user_func( $steps[ $step ]['cb'] );
			ob_end_clean();
		}

		$next = $step + 1;
		wp_send_json_success(
			array(
				'done'     => ( $next >= $total ),
				'next'     => $next,
				'label'    => $next < $total ? $steps[ $next ]['label'] : '',
				'progress' => (int) round( ( $next / $total ) * 100 ),
			)
		);
	}

	/**
	 * Undo import (implemented in Task 7).
	 */
	public function ajax_rollback() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tunet' ) ) );
		}
		$this->replace();
		$this->clear_record();
		wp_send_json_success( array( 'message' => __( 'Import undone.', 'tunet' ) ) );
	}

	/**
	 * Current state of a recommended plugin.
	 *
	 * @param array $info file/label/optional.
	 * @return string active|inactive|missing
	 */
	private function plugin_state( $info ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed = array_key_exists( $info['file'], get_plugins() );
		if ( ! $installed ) {
			return 'missing';
		}
		return is_plugin_active( $info['file'] ) ? 'active' : 'inactive';
	}

	/**
	 * Report recommended-plugin state.
	 */
	public function ajax_plugins() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error();
		}
		$out      = array();
		$pending  = false;
		foreach ( self::recommended_plugins() as $slug => $info ) {
			$state = $this->plugin_state( $info );
			if ( ! $info['optional'] && 'active' !== $state ) {
				$pending = true;
			}
			$out[] = array(
				'slug'     => $slug,
				'label'    => $info['label'],
				'optional' => (bool) $info['optional'],
				'state'    => $state,
			);
		}
		wp_send_json_success( array( 'plugins' => $out, 'all_satisfied' => ! $pending ) );
	}

	/**
	 * Install (if missing) + activate one recommended plugin.
	 */
	public function ajax_install() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) || ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tunet' ) ) );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$all  = self::recommended_plugins();
		if ( ! isset( $all[ $slug ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown plugin.', 'tunet' ) ) );
		}
		$info = $all[ $slug ];

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$install_url = self_admin_url( 'plugin-install.php?s=' . rawurlencode( $info['label'] ) . '&tab=search&type=term' );

		// Install only if missing.
		if ( ! array_key_exists( $info['file'], get_plugins() ) ) {
			$api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
			if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
				wp_send_json_error( array( 'message' => __( 'Could not reach the plugin directory.', 'tunet' ), 'install_url' => $install_url ) );
			}
			$skin     = new Automatic_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );
			if ( is_wp_error( $result ) || ! $result ) {
				wp_send_json_error( array( 'message' => __( 'Install failed — install it manually.', 'tunet' ), 'install_url' => $install_url ) );
			}
		}

		// Activate. Buffer (and discard) any output emitted during activation: some
		// plugins boot during WP's sandbox scrape and print PHP notices / DB warnings
		// before their own tables exist — e.g. WooCommerce on a fresh install, plus
		// WP 6.7's "_load_textdomain_just_in_time" notice. If that output reaches the
		// response it corrupts the JSON, so the import "fails" until a retry.
		ob_start();
		$activate = activate_plugin( $info['file'] );
		ob_end_clean();
		if ( is_wp_error( $activate ) ) {
			wp_send_json_error( array( 'message' => $activate->get_error_message(), 'install_url' => $install_url ) );
		}

		wp_send_json_success( array( 'slug' => $slug, 'state' => 'active' ) );
	}

	/* ---- Admin page -------------------------------------------------- */

	/**
	 * Register the Demo submenu under the Tunet Core menu.
	 */
	public function register_menu() {
		add_submenu_page(
			Tunet_Core_Admin::MENU_SLUG,
			__( 'Demo', 'tunet' ),
			__( 'Demo', 'tunet' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_demo_page' )
		);
	}

	/**
	 * Build a human "what's included" summary from a manifest.
	 *
	 * Each row is { n, label, icon (dashicon slug) }. Only non-empty buckets are
	 * returned, so a theme with a partial manifest still previews cleanly.
	 *
	 * @param array $manifest Manifest array.
	 * @return array<int,array{n:int,label:string,icon:string}>
	 */
	private static function manifest_summary( $manifest ) {
		$rows = array();

		$pages = isset( $manifest['pages'] ) && is_array( $manifest['pages'] ) ? count( $manifest['pages'] ) : 0;
		if ( $pages ) {
			$rows[] = array( 'n' => $pages, 'label' => _n( 'Page', 'Pages', $pages, 'tunet' ), 'icon' => 'admin-page' );
		}

		$projects = isset( $manifest['projects'] ) && is_array( $manifest['projects'] ) ? count( $manifest['projects'] ) : 0;
		if ( $projects ) {
			$rows[] = array( 'n' => $projects, 'label' => _n( 'Project', 'Projects', $projects, 'tunet' ), 'icon' => 'portfolio' );
		}

		$posts = isset( $manifest['posts'] ) && is_array( $manifest['posts'] ) ? count( $manifest['posts'] ) : 0;
		if ( $posts ) {
			$rows[] = array( 'n' => $posts, 'label' => _n( 'Journal post', 'Journal posts', $posts, 'tunet' ), 'icon' => 'admin-post' );
		}

		$products = isset( $manifest['woo']['products'] ) && is_array( $manifest['woo']['products'] ) ? count( $manifest['woo']['products'] ) : 0;
		if ( $products ) {
			$rows[] = array( 'n' => $products, 'label' => _n( 'Product', 'Products', $products, 'tunet' ), 'icon' => 'cart' );
		}

		// Real image count = manifest-keyed images + every image under assets/img
		// (what step_media actually imports), deduped — so the preview matches.
		$image_rel = isset( $manifest['images'] ) && is_array( $manifest['images'] ) ? array_values( $manifest['images'] ) : array();
		foreach ( self::demo_image_files() as $rel ) {
			if ( ! in_array( $rel, $image_rel, true ) ) {
				$image_rel[] = $rel;
			}
		}
		$images = count( $image_rel );
		if ( $images ) {
			$rows[] = array( 'n' => $images, 'label' => _n( 'Image', 'Images', $images, 'tunet' ), 'icon' => 'format-image' );
		}

		return $rows;
	}

	/**
	 * Render the demo wizard — a stylized, step-by-step screen.
	 *
	 * Step 1: recommended plugins (styled switch list). Step 2: import. A live
	 * preview of the active theme's screenshot sits in the aside. Degrades with
	 * dignity (§12, regla 3): a theme without a manifest shows a friendly empty
	 * state instead of failing.
	 */
	public function render_demo_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$manifest  = self::manifest();
		$has_demo  = (bool) self::get_record();
		$has_items = ! empty( $manifest );
		$theme     = wp_get_theme();
		$summary   = self::manifest_summary( $manifest );
		$shot      = $theme->get_screenshot();
		$home      = home_url( '/' );
		$cancel    = self_admin_url( 'admin.php?page=' . Tunet_Core_Admin::MENU_SLUG );
		?>
		<div class="wrap tunet-admin tunet-demo">
			<h1><?php esc_html_e( 'Tunet Core · Demo', 'tunet' ); ?></h1>

			<?php if ( ! $has_items ) : ?>
				<div class="tunet-demo-empty">
					<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'No demo to import for the active theme.', 'tunet' ); ?></strong></p>
						<p class="description">
							<?php
							printf(
								/* translators: %s: active theme name. */
								esc_html__( '%s does not ship a demo manifest. Activate a Tunet theme to unlock its designed demo.', 'tunet' ),
								'<strong>' . esc_html( $theme->get( 'Name' ) ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
							?>
						</p>
					</div>
				</div>
			<?php else : ?>
			<div id="tunet-wizard" class="tunet-wizard" data-has-demo="<?php echo $has_demo ? '1' : '0'; ?>">
				<ol class="tunet-stepper">
					<li class="tunet-stepper__item is-current" data-step="plugins"><span class="tunet-stepper__n">1</span><?php esc_html_e( 'Plugins', 'tunet' ); ?></li>
					<li class="tunet-stepper__item" data-step="import"><span class="tunet-stepper__n">2</span><?php esc_html_e( 'Import', 'tunet' ); ?></li>
				</ol>

				<div class="tunet-wizard__grid">
					<aside class="tunet-wizard__aside">
						<?php if ( $shot ) : ?>
							<div class="tunet-preview">
								<div class="tunet-preview__bar"><span></span><span></span><span></span></div>
								<div class="tunet-preview__shot"><img src="<?php echo esc_url( $shot ); ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: theme name. */ __( '%s preview', 'tunet' ), $theme->get( 'Name' ) ) ); ?>" /></div>
							</div>
						<?php endif; ?>
						<p class="tunet-preview__caption">
							<?php
							printf(
								/* translators: %s: active theme name. */
								esc_html__( 'Demo for %s', 'tunet' ),
								'<strong>' . esc_html( $theme->get( 'Name' ) ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
							?>
						</p>
					</aside>

					<div class="tunet-wizard__main">
						<section class="tunet-step" data-panel="plugins">
							<h2 class="tunet-step__title"><?php esc_html_e( 'Recommended plugins', 'tunet' ); ?></h2>
							<p class="tunet-step__lead"><?php esc_html_e( 'These add optional parts of the demo (forms, shop…). Pick any you want and install & activate them, or just continue without them.', 'tunet' ); ?></p>
							<ul class="tunet-plugins" id="tunet-plugins-list"></ul>
							<p class="tunet-plugins__msg" aria-live="polite"></p>
							<div class="tunet-step__actions">
								<a class="tunet-cancel" href="<?php echo esc_url( $cancel ); ?>"><?php esc_html_e( 'Cancel', 'tunet' ); ?></a>
								<span class="tunet-step__spacer"></span>
								<button type="button" class="button" id="tunet-plugins-install"><?php esc_html_e( 'Install & activate', 'tunet' ); ?></button>
								<button type="button" class="button button-primary" id="tunet-plugins-continue" disabled><?php esc_html_e( 'Continue', 'tunet' ); ?></button>
							</div>
						</section>

						<section class="tunet-step" data-panel="import" hidden>
							<h2 class="tunet-step__title"><?php esc_html_e( 'Import the demo', 'tunet' ); ?></h2>
							<p class="tunet-step__lead"><?php esc_html_e( 'Creates the demo as native, editable blocks — pages, content and settings. You can undo it with one click.', 'tunet' ); ?></p>
							<?php if ( $has_demo ) : ?>
								<p class="tunet-demo-note"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e( 'A demo is already imported. Re-importing replaces it; Undo removes it.', 'tunet' ); ?></p>
							<?php endif; ?>
							<div class="tunet-progress" hidden><div class="tunet-progress__bar"></div></div>
							<p class="tunet-progress__status" aria-live="polite"></p>
							<p class="tunet-done" hidden><a class="button button-primary button-hero" href="<?php echo esc_url( $home ); ?>"><?php esc_html_e( 'View site', 'tunet' ); ?></a></p>
							<div class="tunet-step__actions">
								<button type="button" class="button tunet-back" data-to="plugins">&larr; <?php esc_html_e( 'Back', 'tunet' ); ?></button>
								<span class="tunet-step__spacer"></span>
								<button type="button" class="button button-primary" id="tunet-demo-import"><?php esc_html_e( 'Import demo', 'tunet' ); ?></button>
								<button type="button" class="button" id="tunet-demo-rollback" <?php disabled( ! $has_demo ); ?>><?php esc_html_e( 'Undo import', 'tunet' ); ?></button>
							</div>
						</section>
					</div>
				</div>
				<?php if ( $summary ) : ?>
					<ul class="tunet-summary">
						<?php foreach ( $summary as $row ) : ?>
							<li class="tunet-summary__item">
								<span class="dashicons dashicons-<?php echo esc_attr( $row['icon'] ); ?>" aria-hidden="true"></span>
								<span class="tunet-summary__n"><?php echo esc_html( number_format_i18n( $row['n'] ) ); ?></span>
								<span class="tunet-summary__l"><?php echo esc_html( $row['label'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
