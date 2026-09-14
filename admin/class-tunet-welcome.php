<?php
/**
 * Get started — the screen behind the "Tunet Core" menu entry.
 *
 * Where a site owner lands the first time: what the engine does, the three
 * steps that get a site going (theme → demo → effects), the latest changelog
 * entry, and the doors to docs and support. Plain WordPress admin UI, no
 * redirect on activation: a dismissible notice points here once and goes away
 * as soon as the screen is opened (or dismissed), per the wp.org guidelines.
 *
 * @package Tunet_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get started screen.
 */
class Tunet_Core_Welcome {

	const CAPABILITY = 'manage_options';
	const OPTION     = 'tunet_core_welcome_seen';
	const DOCS_URL   = 'https://tunetdesign.com/docs/';
	const SUPPORT    = 'ai@tunetdesign.com';
	const GITHUB     = 'https://github.com/aitunet/tunet-core';

	/**
	 * Wire hooks. The menu entry itself is registered by Tunet_Core_Admin (the
	 * top-level item IS this screen).
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
		add_action( 'admin_post_tunet_welcome_dismiss', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Admin URL of this screen.
	 *
	 * @return string
	 */
	public static function url() {
		return admin_url( 'admin.php?page=' . Tunet_Core_Admin::MENU_SLUG );
	}

	/**
	 * A docs URL. The base is filterable so a fork or a staging site can point
	 * the plugin at its own documentation.
	 *
	 * @param string $path    Path under the docs root ('' = the hub).
	 * @param string $content utm_content label.
	 * @return string
	 */
	public static function docs_url( $path = '', $content = 'link' ) {
		/**
		 * Base URL of the Tunet Core documentation.
		 *
		 * @param string $base Docs root, with trailing slash.
		 */
		$base = apply_filters( 'tunet_core_docs_url', self::DOCS_URL );
		return add_query_arg(
			array(
				'utm_source'   => 'tunet-core',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'get-started',
				'utm_content'  => $content,
			),
			trailingslashit( $base ) . ltrim( $path, '/' )
		);
	}

	/* ---------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------ */

	/**
	 * Is the active theme one made for this engine? Author URI or Update URI on
	 * tunetdesign.com — covers the premium themes and Tunet Starter alike.
	 *
	 * @return bool
	 */
	public static function is_tunet_theme() {
		$theme = wp_get_theme();
		foreach ( array( 'AuthorURI', 'UpdateURI' ) as $header ) {
			if ( false !== stripos( (string) $theme->get( $header ), 'tunetdesign.com' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Demo state for the active theme: 'imported' | 'available' | 'none'.
	 *
	 * @return string
	 */
	public static function demo_state() {
		if ( ! class_exists( 'Tunet_Core_Demo' ) ) {
			return 'none';
		}
		if ( Tunet_Core_Demo::get_record() ) {
			return 'imported';
		}
		return Tunet_Core_Demo::manifest() ? 'available' : 'none';
	}

	/**
	 * The latest changelog entry from readme.txt: version + bullet lines.
	 *
	 * @return array{version:string,items:string[]}|null
	 */
	public static function latest_changes() {
		$file = TUNET_CORE_PATH . 'readme.txt';
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$readme = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$pos    = strpos( $readme, '== Changelog ==' );
		if ( false === $pos || ! preg_match( '/^= ([0-9.]+) =\s*\n((?:\*.*\n?)+)/m', substr( $readme, $pos ), $m ) ) {
			return null;
		}
		$items = array();
		foreach ( preg_split( '/\r?\n/', trim( $m[2] ) ) as $line ) {
			$line = trim( ltrim( trim( $line ), '*' ) );
			if ( '' !== $line ) {
				$items[] = str_replace( '`', '', $line );
			}
		}
		return array( 'version' => $m[1], 'items' => $items );
	}

	/* ---------------------------------------------------------------------
	 * Notice
	 * ------------------------------------------------------------------ */

	/**
	 * One dismissible pointer to this screen, for admins, until it is opened
	 * or dismissed. Never on the screen itself.
	 */
	public function maybe_notice() {
		if ( ! current_user_can( self::CAPABILITY ) || get_option( self::OPTION ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_' . Tunet_Core_Admin::MENU_SLUG === $screen->id ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=tunet_welcome_dismiss' ), 'tunet_welcome_dismiss' );
		?>
		<div class="notice notice-info tunet-welcome-notice">
			<p>
				<strong><?php esc_html_e( 'Tunet Core is ready.', 'tunet-core' ); ?></strong>
				<?php esc_html_e( 'Pick a theme, import its demo and add motion to any block — the Get started screen walks you through it.', 'tunet-core' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Get started', 'tunet-core' ); ?></a>
				<a class="tunet-welcome-notice__dismiss" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'tunet-core' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Dismiss the notice (admin-post, nonce).
	 */
	public function handle_dismiss() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tunet-core' ) );
		}
		check_admin_referer( 'tunet_welcome_dismiss' );
		self::mark_seen();
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Remember that the owner has seen the screen (or dismissed the notice).
	 */
	public static function mark_seen() {
		update_option( self::OPTION, defined( 'TUNET_CORE_VERSION' ) ? TUNET_CORE_VERSION : '1', false );
	}

	/* ---------------------------------------------------------------------
	 * Screen
	 * ------------------------------------------------------------------ */

	/**
	 * Render the Get started screen.
	 */
	public static function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		self::mark_seen();

		$theme       = wp_get_theme();
		$tunet_theme = self::is_tunet_theme();
		$demo        = self::demo_state();
		$version     = defined( 'TUNET_CORE_VERSION' ) ? TUNET_CORE_VERSION : '';
		$changes     = self::latest_changes();
		$demo_url    = class_exists( 'Tunet_Core_Demo' ) ? admin_url( 'admin.php?page=' . Tunet_Core_Demo::MENU_SLUG ) : '';
		$themes_url  = class_exists( 'Tunet_Core_Themes' ) ? Tunet_Core_Themes::url() : '';
		$editor_url  = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ? admin_url( 'site-editor.php' ) : admin_url( 'post-new.php?post_type=page' );
		?>
		<div class="wrap tunet-admin tunet-welcome">
			<h1 class="tunet-welcome__title"><?php esc_html_e( 'Get started', 'tunet-core' ); ?></h1>

			<header class="tunet-welcome__hero">
				<div class="tunet-welcome__hero-copy">
					<p class="tunet-welcome__kicker"><?php esc_html_e( 'Tunet Core', 'tunet-core' ); ?><?php if ( $version ) : ?> <span class="tunet-welcome__version">v<?php echo esc_html( $version ); ?></span><?php endif; ?></p>
					<p class="tunet-welcome__headline"><?php esc_html_e( 'Motion for your blocks, on your terms.', 'tunet-core' ); ?></p>
					<p class="tunet-welcome__lede"><?php esc_html_e( 'Tunet Core adds entrance, hover and scroll effects to the blocks you already use, plus a few blocks the editor lacks — sliders, sections with rich backgrounds, marquees, counters. Nothing is applied until you switch it on, and every color and curve comes from your theme.', 'tunet-core' ); ?></p>
				</div>
				<ul class="tunet-welcome__status" aria-label="<?php esc_attr_e( 'Site status', 'tunet-core' ); ?>">
					<li class="<?php echo $tunet_theme ? 'is-good' : 'is-neutral'; ?>">
						<span class="tunet-welcome__status-label"><?php esc_html_e( 'Theme', 'tunet-core' ); ?></span>
						<strong><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong>
						<span class="tunet-welcome__status-note"><?php echo $tunet_theme ? esc_html__( 'Made for this engine', 'tunet-core' ) : esc_html__( 'Any theme works — effects use the engine defaults', 'tunet-core' ); ?></span>
					</li>
					<li class="<?php echo 'imported' === $demo ? 'is-good' : ( 'available' === $demo ? 'is-ready' : 'is-neutral' ); ?>">
						<span class="tunet-welcome__status-label"><?php esc_html_e( 'Demo', 'tunet-core' ); ?></span>
						<strong>
							<?php
							if ( 'imported' === $demo ) {
								esc_html_e( 'Imported', 'tunet-core' );
							} elseif ( 'available' === $demo ) {
								esc_html_e( 'Ready to import', 'tunet-core' );
							} else {
								esc_html_e( 'No demo for this theme', 'tunet-core' );
							}
							?>
						</strong>
					</li>
					<li class="is-good">
						<span class="tunet-welcome__status-label"><?php esc_html_e( 'Effects', 'tunet-core' ); ?></span>
						<strong><?php esc_html_e( 'Runtime active', 'tunet-core' ); ?></strong>
						<span class="tunet-welcome__status-note"><?php esc_html_e( 'Loads only on pages that use an effect', 'tunet-core' ); ?></span>
					</li>
				</ul>
			</header>

			<h2 class="tunet-welcome__h2"><?php esc_html_e( 'Three steps to a finished site', 'tunet-core' ); ?></h2>
			<ol class="tunet-welcome__steps">
				<li class="tunet-welcome__step<?php echo $tunet_theme ? ' is-done' : ''; ?>">
					<span class="tunet-welcome__num" aria-hidden="true">1</span>
					<h3><?php esc_html_e( 'Pick a theme', 'tunet-core' ); ?></h3>
					<p><?php esc_html_e( 'Any theme works. A theme made for this engine also brings its own design tokens, block patterns and a one-click demo.', 'tunet-core' ); ?></p>
					<?php if ( $tunet_theme ) : ?>
						<p class="tunet-welcome__done">
							<?php
							/* translators: %s: active theme name. */
							echo esc_html( sprintf( __( 'You’re on %s.', 'tunet-core' ), $theme->get( 'Name' ) ) );
							?>
						</p>
					<?php elseif ( $themes_url ) : ?>
						<p><a class="button button-primary" href="<?php echo esc_url( $themes_url ); ?>"><?php esc_html_e( 'Browse themes', 'tunet-core' ); ?></a></p>
					<?php endif; ?>
				</li>
				<li class="tunet-welcome__step<?php echo 'imported' === $demo ? ' is-done' : ''; ?>">
					<span class="tunet-welcome__num" aria-hidden="true">2</span>
					<h3><?php esc_html_e( 'Import the demo', 'tunet-core' ); ?></h3>
					<p><?php esc_html_e( 'Pages, patterns, images and settings, wired to your Media Library so you can edit or replace anything — with a one-click rollback.', 'tunet-core' ); ?></p>
					<?php if ( 'imported' === $demo ) : ?>
						<p class="tunet-welcome__done"><?php esc_html_e( 'Demo imported.', 'tunet-core' ); ?> <a href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Manage', 'tunet-core' ); ?></a></p>
					<?php elseif ( 'available' === $demo ) : ?>
						<p><a class="button button-primary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Open the Demo importer', 'tunet-core' ); ?></a></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'This theme ships no demo. Build with the block patterns of your theme, or pick one made for this engine.', 'tunet-core' ); ?></p>
					<?php endif; ?>
				</li>
				<li class="tunet-welcome__step">
					<span class="tunet-welcome__num" aria-hidden="true">3</span>
					<h3><?php esc_html_e( 'Add motion to any block', 'tunet-core' ); ?></h3>
					<p><?php esc_html_e( 'Select a Group, Image, Heading, Paragraph, Cover or Buttons block and open the Tunet Effects panel in the sidebar: entrance animation, hover, scroll. Leave it empty and the block stays exactly as it is.', 'tunet-core' ); ?></p>
					<div class="tunet-welcome__panel" aria-hidden="true">
						<div class="tunet-welcome__panel-title"><?php esc_html_e( 'Tunet Effects', 'tunet-core' ); ?></div>
						<div class="tunet-welcome__panel-row"><span><?php esc_html_e( 'Animation', 'tunet-core' ); ?></span><span class="tunet-welcome__panel-val">fade-up</span></div>
						<div class="tunet-welcome__panel-row"><span><?php esc_html_e( 'Easing', 'tunet-core' ); ?></span><span class="tunet-welcome__panel-val">expo</span></div>
						<div class="tunet-welcome__panel-row"><span><?php esc_html_e( 'Hover', 'tunet-core' ); ?></span><span class="tunet-welcome__panel-val">lift</span></div>
					</div>
					<p><a class="button" href="<?php echo esc_url( $editor_url ); ?>"><?php esc_html_e( 'Open the editor', 'tunet-core' ); ?></a></p>
				</li>
			</ol>

			<div class="tunet-welcome__cols">
				<section class="tunet-welcome__card">
					<h2><?php esc_html_e( 'What Tunet Core adds', 'tunet-core' ); ?></h2>
					<ul class="tunet-welcome__list">
						<li><a href="<?php echo esc_url( self::docs_url( 'tunet-core/effects/', 'effects' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Effects on native blocks', 'tunet-core' ); ?></a> — <?php esc_html_e( 'entrance, hover and scroll, per block, opt-in.', 'tunet-core' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'tunet-core/blocks/', 'blocks' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Blocks the editor lacks', 'tunet-core' ); ?></a> — <?php esc_html_e( 'Section, Slider, Marquee, Counter, Before/After, Testimonials, Brand, Icon, Badge.', 'tunet-core' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'tunet-core/tokens/', 'tokens' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Design tokens', 'tunet-core' ); ?></a> — <?php esc_html_e( 'every effect reads its colors and curves from the active theme; style variations recolor everything.', 'tunet-core' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'tunet-core/demo-importer/', 'importer' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Demo importer', 'tunet-core' ); ?></a> — <?php esc_html_e( 'per theme, with progress and rollback.', 'tunet-core' ); ?></li>
					</ul>
				</section>

				<section class="tunet-welcome__card">
					<h2><?php esc_html_e( 'Help', 'tunet-core' ); ?></h2>
					<ul class="tunet-welcome__list">
						<li><a href="<?php echo esc_url( self::docs_url( '', 'docs' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'tunet-core' ); ?></a></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'getting-started/', 'getting-started' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Getting started guide', 'tunet-core' ); ?></a></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'faq/', 'faq' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'FAQ', 'tunet-core' ); ?></a></li>
						<li><a href="mailto:<?php echo esc_attr( self::SUPPORT ); ?>"><?php esc_html_e( 'Support', 'tunet-core' ); ?></a> · <?php echo esc_html( self::SUPPORT ); ?></li>
						<li><a href="<?php echo esc_url( self::GITHUB ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Source on GitHub', 'tunet-core' ); ?></a></li>
					</ul>
					<?php if ( $changes ) : ?>
						<h2 class="tunet-welcome__whatsnew">
							<?php
							/* translators: %s: version number. */
							echo esc_html( sprintf( __( 'What’s new in %s', 'tunet-core' ), $changes['version'] ) );
							?>
						</h2>
						<ul class="tunet-welcome__list tunet-welcome__list--changes">
							<?php foreach ( $changes['items'] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			</div>

		</div>
		<?php
	}
}
