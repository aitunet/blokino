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

	public function step_cf7() {}
	public function step_projects() {}
	public function step_posts() {}
	public function step_pages() {}
	public function step_products() {}

	/**
	 * Final step (no-op for now).
	 */
	public function step_finalize() {}

	/**
	 * Delete everything tracked in the record (implemented in Task 7).
	 */
	public function replace() {}

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
	 * Report recommended-plugin state (implemented in Task 9).
	 */
	public function ajax_plugins() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error();
		}
		wp_send_json_success( array( 'plugins' => array() ) );
	}

	/**
	 * Install + activate one plugin (implemented in Task 9).
	 */
	public function ajax_install() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error();
		}
		wp_send_json_error( array( 'message' => 'not implemented' ) );
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
	 * Render the wizard shell (steps filled in by admin.js — Task 9).
	 */
	public function render_demo_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$has_demo = (bool) self::get_record();
		?>
		<div class="wrap tunet-admin">
			<h1><?php esc_html_e( 'Tunet Core · Demo', 'tunet' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Recreate the theme demo as editable blocks. You can undo it.', 'tunet' ); ?></p>
			<div id="tunet-wizard" data-has-demo="<?php echo $has_demo ? '1' : '0'; ?>">
				<div class="tunet-progress" hidden><div class="tunet-progress__bar"></div></div>
				<p class="tunet-progress__status" aria-live="polite"></p>
				<p>
					<button type="button" class="button button-primary" id="tunet-demo-import"><?php esc_html_e( 'Start', 'tunet' ); ?></button>
					<button type="button" class="button" id="tunet-demo-rollback" <?php disabled( ! $has_demo ); ?>><?php esc_html_e( 'Undo import', 'tunet' ); ?></button>
				</p>
			</div>
		</div>
		<?php
	}
}
