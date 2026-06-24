<?php
/**
 * Tunet Core · Demo importer + recommended-plugins wizard.
 *
 * Engine-side and theme-agnostic: reads the theme's `tunet_core_demo_manifest`
 * and builds content, recording every created ID for replace/rollback. Optional
 * WooCommerce/Contact Form 7 are resolved conditionally.
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

	/** Recommended plugins: slug (wp.org) => [file, label, optional]. */
	public static function recommended_plugins() {
		return array(
			'contact-form-7' => array(
				'file'     => 'contact-form-7/wp-contact-form-7.php',
				'label'    => 'Contact Form 7',
				'optional' => false,
			),
			'woocommerce'    => array(
				'file'     => 'woocommerce/woocommerce.php',
				'label'    => 'WooCommerce',
				'optional' => true,
			),
		);
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
	 * Store the cf7 form id or the images map.
	 *
	 * @param string $key   'cf7' | 'images_map'.
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
	 * Sideload all manifest images; store key→id map; track attachments.
	 */
	public function step_media() {
		$images = self::manifest()['images'] ?? array();
		$map    = array();
		foreach ( $images as $key => $relpath ) {
			$id = $this->sideload_image( $relpath );
			if ( $id ) {
				$map[ $key ] = $id;
				$this->track( 'attachments', $id );
			}
		}
		$this->set_record( 'images_map', $map );
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
	 * Freshly execute a theme pattern's PHP and return its markup.
	 *
	 * Re-includes the file (rather than the cached registered content) so the
	 * contact patterns' CF7 conditional resolves against the just-created form.
	 *
	 * @param string $slug e.g. 'aurora/studio'.
	 * @return string
	 */
	public function expand_pattern( $slug ) {
		$name = preg_replace( '#^[^/]+/#', '', $slug ); // strip 'aurora/'
		$file = get_theme_file_path( 'patterns/' . $name . '.php' );
		if ( ! file_exists( $file ) ) {
			return '';
		}
		ob_start();
		include $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		return (string) ob_get_clean();
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
			call_user_func( $steps[ $step ]['cb'] );
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

		// Activate.
		$activate = activate_plugin( $info['file'] );
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

		$images = isset( $manifest['images'] ) && is_array( $manifest['images'] ) ? count( $manifest['images'] ) : 0;
		if ( $images ) {
			$rows[] = array( 'n' => $images, 'label' => _n( 'Image', 'Images', $images, 'tunet' ), 'icon' => 'format-image' );
		}

		return $rows;
	}

	/**
	 * Render the demo wizard: a manifest-driven preview + the import controls.
	 *
	 * Degrades with dignity (§12, regla 3): a theme without a manifest shows a
	 * friendly empty state and a disabled importer instead of failing.
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
				<div id="tunet-demo-intro" class="tunet-demo-intro">
					<div class="tunet-demo-intro__body">
						<p class="tunet-demo-intro__eyebrow">
							<?php
							printf(
								/* translators: %s: active theme name. */
								esc_html__( 'Demo for %s', 'tunet' ),
								'<strong>' . esc_html( $theme->get( 'Name' ) ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
							?>
						</p>
						<h2 class="tunet-demo-intro__title"><?php esc_html_e( 'Recreate the full demo as editable content.', 'tunet' ); ?></h2>
						<p class="tunet-demo-intro__lede"><?php esc_html_e( 'Everything is created as native blocks you can edit freely — pages, portfolio, journal and shop. Missing plugins are offered first, and a single click undoes it all.', 'tunet' ); ?></p>
					</div>
					<?php if ( $summary ) : ?>
						<ul class="tunet-demo-stats">
							<?php foreach ( $summary as $row ) : ?>
								<li class="tunet-demo-stats__item">
									<span class="dashicons dashicons-<?php echo esc_attr( $row['icon'] ); ?>" aria-hidden="true"></span>
									<span class="tunet-demo-stats__n"><?php echo esc_html( number_format_i18n( $row['n'] ) ); ?></span>
									<span class="tunet-demo-stats__l"><?php echo esc_html( $row['label'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div id="tunet-wizard" data-has-demo="<?php echo $has_demo ? '1' : '0'; ?>">
				<?php if ( $has_demo ) : ?>
					<p class="tunet-demo-note">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<?php esc_html_e( 'A demo is already imported. Re-importing replaces it; Undo removes it.', 'tunet' ); ?>
					</p>
				<?php endif; ?>
				<div class="tunet-progress" hidden><div class="tunet-progress__bar"></div></div>
				<p class="tunet-progress__status" aria-live="polite"></p>
				<p class="tunet-demo-actions">
					<button type="button" class="button button-primary" id="tunet-demo-import" <?php disabled( ! $has_items ); ?>><?php esc_html_e( 'Import demo', 'tunet' ); ?></button>
					<button type="button" class="button" id="tunet-demo-rollback" <?php disabled( ! $has_demo ); ?>><?php esc_html_e( 'Undo import', 'tunet' ); ?></button>
				</p>
			</div>
		</div>
		<?php
	}
}
