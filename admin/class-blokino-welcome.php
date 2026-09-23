<?php
/**
 * Get started — the screen behind the "Blokino" menu entry.
 *
 * Where a site owner lands the first time: what the engine does, the three
 * steps that get a site going (theme → demo → effects), the latest changelog
 * entry, and the doors to docs and support. Plain WordPress admin UI, no
 * redirect on activation: a dismissible notice points here once and goes away
 * as soon as the screen is opened (or dismissed), per the wp.org guidelines.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get started screen.
 */
class Blokino_Welcome {

	const CAPABILITY = 'manage_options';
	const OPTION     = 'blokino_welcome_seen';
	const DOCS_URL   = 'https://tunetdesign.com/docs/';
	const SUPPORT    = 'info@tunetdesign.com';
	const GITHUB     = 'https://github.com/aitunet/blokino';

	/**
	 * Wire hooks. The menu entry itself is registered by Blokino_Admin (the
	 * top-level item IS this screen).
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
		add_action( 'admin_post_blokino_welcome_dismiss', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Admin URL of this screen.
	 *
	 * @return string
	 */
	public static function url() {
		return admin_url( 'admin.php?page=' . Blokino_Admin::MENU_SLUG );
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
		 * Base URL of the Blokino documentation.
		 *
		 * @param string $base Docs root, with trailing slash.
		 */
		$base = apply_filters( 'blokino_docs_url', self::DOCS_URL );
		return add_query_arg(
			array(
				'utm_source'   => 'blokino',
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
		if ( ! class_exists( 'Blokino_Demo' ) ) {
			return 'none';
		}
		if ( Blokino_Demo::get_record() ) {
			return 'imported';
		}
		return Blokino_Demo::manifest() ? 'available' : 'none';
	}

	/**
	 * The latest changelog entry from readme.txt: version + bullet lines.
	 *
	 * @return array{version:string,items:string[]}|null
	 */
	public static function latest_changes() {
		$file = BLOKINO_PATH . 'readme.txt';
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
		if ( $screen && 'toplevel_page_' . Blokino_Admin::MENU_SLUG === $screen->id ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=blokino_welcome_dismiss' ), 'blokino_welcome_dismiss' );
		?>
		<div class="notice notice-info blokino-welcome-notice">
			<p>
				<strong><?php esc_html_e( 'Blokino is ready.', 'blokino' ); ?></strong>
				<?php esc_html_e( 'Pick a theme, import its demo and add motion to any block — the Get started screen walks you through it.', 'blokino' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Get started', 'blokino' ); ?></a>
				<a class="blokino-welcome-notice__dismiss" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'blokino' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Dismiss the notice (admin-post, nonce).
	 */
	public function handle_dismiss() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'blokino' ) );
		}
		check_admin_referer( 'blokino_welcome_dismiss' );
		self::mark_seen();
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Remember that the owner has seen the screen (or dismissed the notice).
	 */
	public static function mark_seen() {
		update_option( self::OPTION, defined( 'BLOKINO_VERSION' ) ? BLOKINO_VERSION : '1', false );
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
		$blokino_theme = self::is_tunet_theme();
		$demo        = self::demo_state();
		$version     = defined( 'BLOKINO_VERSION' ) ? BLOKINO_VERSION : '';
		$changes     = self::latest_changes();
		$demo_url    = class_exists( 'Blokino_Demo' ) ? admin_url( 'admin.php?page=' . Blokino_Demo::MENU_SLUG ) : '';
		$themes_url  = class_exists( 'Blokino_Themes' ) ? Blokino_Themes::url() : '';
		$editor_url  = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ? admin_url( 'site-editor.php' ) : admin_url( 'post-new.php?post_type=page' );
		?>
		<div class="wrap blokino-admin blokino-welcome">
			<h1 class="blokino-welcome__title"><?php esc_html_e( 'Get started', 'blokino' ); ?></h1>

			<header class="blokino-welcome__hero">
				<div class="blokino-welcome__hero-copy">
					<p class="blokino-welcome__kicker"><?php esc_html_e( 'Blokino', 'blokino' ); ?><?php if ( $version ) : ?> <span class="blokino-welcome__version">v<?php echo esc_html( $version ); ?></span><?php endif; ?></p>
					<p class="blokino-welcome__headline"><?php esc_html_e( 'Motion for your blocks, on your terms.', 'blokino' ); ?></p>
					<p class="blokino-welcome__lede"><?php esc_html_e( 'Blokino adds entrance, hover and scroll effects to the blocks you already use, plus a few blocks the editor lacks — sliders, sections with rich backgrounds, marquees, counters. Nothing is applied until you switch it on, and every color and curve comes from your theme.', 'blokino' ); ?></p>
				</div>
				<ul class="blokino-welcome__status" aria-label="<?php esc_attr_e( 'Site status', 'blokino' ); ?>">
					<li class="<?php echo $blokino_theme ? 'is-good' : 'is-neutral'; ?>">
						<span class="blokino-welcome__status-label"><?php esc_html_e( 'Theme', 'blokino' ); ?></span>
						<strong><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong>
						<span class="blokino-welcome__status-note"><?php echo $blokino_theme ? esc_html__( 'Made for this engine', 'blokino' ) : esc_html__( 'Any theme works — effects use the engine defaults', 'blokino' ); ?></span>
					</li>
					<li class="<?php echo 'imported' === $demo ? 'is-good' : ( 'available' === $demo ? 'is-ready' : 'is-neutral' ); ?>">
						<span class="blokino-welcome__status-label"><?php esc_html_e( 'Demo', 'blokino' ); ?></span>
						<strong>
							<?php
							if ( 'imported' === $demo ) {
								esc_html_e( 'Imported', 'blokino' );
							} elseif ( 'available' === $demo ) {
								esc_html_e( 'Ready to import', 'blokino' );
							} else {
								esc_html_e( 'No demo for this theme', 'blokino' );
							}
							?>
						</strong>
					</li>
					<li class="is-good">
						<span class="blokino-welcome__status-label"><?php esc_html_e( 'Effects', 'blokino' ); ?></span>
						<strong><?php esc_html_e( 'Runtime active', 'blokino' ); ?></strong>
						<span class="blokino-welcome__status-note"><?php esc_html_e( 'Loads only on pages that use an effect', 'blokino' ); ?></span>
					</li>
				</ul>
			</header>

			<h2 class="blokino-welcome__h2"><?php esc_html_e( 'Three steps to a finished site', 'blokino' ); ?></h2>
			<ol class="blokino-welcome__steps">
				<li class="blokino-welcome__step<?php echo $blokino_theme ? ' is-done' : ''; ?>">
					<span class="blokino-welcome__num" aria-hidden="true">1</span>
					<h3><?php esc_html_e( 'Pick a theme', 'blokino' ); ?></h3>
					<p><?php esc_html_e( 'Any theme works. A theme made for this engine also brings its own design tokens, block patterns and a one-click demo.', 'blokino' ); ?></p>
					<?php if ( $blokino_theme ) : ?>
						<p class="blokino-welcome__done">
							<?php
							/* translators: %s: active theme name. */
							echo esc_html( sprintf( __( 'You’re on %s.', 'blokino' ), $theme->get( 'Name' ) ) );
							?>
						</p>
					<?php elseif ( $themes_url ) : ?>
						<p><?php esc_html_e( 'Start free with Tunet Starter, our theme on WordPress.org — or pick a premium one.', 'blokino' ); ?></p>
						<p>
							<?php if ( class_exists( 'Blokino_Themes' ) && 'installed' === Blokino_Themes::local_state( Blokino_Themes::FREE_THEME ) ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'themes.php?action=activate&stylesheet=' . Blokino_Themes::FREE_THEME ), 'switch-theme_' . Blokino_Themes::FREE_THEME ) ); ?>"><?php esc_html_e( 'Activate Tunet Starter', 'blokino' ); ?></a>
							<?php elseif ( class_exists( 'Blokino_Themes' ) ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( Blokino_Themes::free_theme_install_url() ); ?>"><?php esc_html_e( 'Get Tunet Starter — free', 'blokino' ); ?></a>
							<?php endif; ?>
							<a class="button" href="<?php echo esc_url( $themes_url ); ?>"><?php esc_html_e( 'Browse themes', 'blokino' ); ?></a>
						</p>
					<?php endif; ?>
				</li>
				<li class="blokino-welcome__step<?php echo 'imported' === $demo ? ' is-done' : ''; ?>">
					<span class="blokino-welcome__num" aria-hidden="true">2</span>
					<h3><?php esc_html_e( 'Import the demo', 'blokino' ); ?></h3>
					<p><?php esc_html_e( 'Pages, patterns, images and settings, wired to your Media Library so you can edit or replace anything — with a one-click rollback.', 'blokino' ); ?></p>
					<?php if ( 'imported' === $demo ) : ?>
						<p class="blokino-welcome__done"><?php esc_html_e( 'Demo imported.', 'blokino' ); ?> <a href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Manage', 'blokino' ); ?></a></p>
					<?php elseif ( 'available' === $demo ) : ?>
						<p><a class="button button-primary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Open the Demo importer', 'blokino' ); ?></a></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'This theme ships no demo. Build with the block patterns of your theme, or pick one made for this engine.', 'blokino' ); ?></p>
					<?php endif; ?>
				</li>
				<li class="blokino-welcome__step">
					<span class="blokino-welcome__num" aria-hidden="true">3</span>
					<h3><?php esc_html_e( 'Add motion to any block', 'blokino' ); ?></h3>
					<p><?php esc_html_e( 'Select a Group, Image, Heading, Paragraph, Cover or Buttons block and open the Blokino Effects panel in the sidebar: entrance animation, hover, scroll. Leave it empty and the block stays exactly as it is.', 'blokino' ); ?></p>
					<div class="blokino-welcome__panel" aria-hidden="true">
						<div class="blokino-welcome__panel-title"><?php esc_html_e( 'Blokino Effects', 'blokino' ); ?></div>
						<div class="blokino-welcome__panel-row"><span><?php esc_html_e( 'Animation', 'blokino' ); ?></span><span class="blokino-welcome__panel-val">fade-up</span></div>
						<div class="blokino-welcome__panel-row"><span><?php esc_html_e( 'Easing', 'blokino' ); ?></span><span class="blokino-welcome__panel-val">expo</span></div>
						<div class="blokino-welcome__panel-row"><span><?php esc_html_e( 'Hover', 'blokino' ); ?></span><span class="blokino-welcome__panel-val">lift</span></div>
					</div>
					<p><a class="button" href="<?php echo esc_url( $editor_url ); ?>"><?php esc_html_e( 'Open the editor', 'blokino' ); ?></a></p>
				</li>
			</ol>

			<div class="blokino-welcome__cols">
				<section class="blokino-welcome__card">
					<h2><?php esc_html_e( 'What Blokino adds', 'blokino' ); ?></h2>
					<ul class="blokino-welcome__list">
						<li><a href="<?php echo esc_url( self::docs_url( 'blokino/effects/', 'effects' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Effects on native blocks', 'blokino' ); ?></a> — <?php esc_html_e( 'entrance, hover and scroll, per block, opt-in.', 'blokino' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'blokino/blocks/', 'blocks' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Blocks the editor lacks', 'blokino' ); ?></a> — <?php esc_html_e( 'Section, Slider, Marquee, Counter, Before/After, Testimonials, Brand, Icon, Badge.', 'blokino' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'blokino/tokens/', 'tokens' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Design tokens', 'blokino' ); ?></a> — <?php esc_html_e( 'every effect reads its colors and curves from the active theme; style variations recolor everything.', 'blokino' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'blokino/demo-importer/', 'importer' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Demo importer', 'blokino' ); ?></a> — <?php esc_html_e( 'per theme, with progress and rollback.', 'blokino' ); ?></li>
					</ul>
				</section>

				<section class="blokino-welcome__card">
					<h2><?php esc_html_e( 'Help', 'blokino' ); ?></h2>
					<ul class="blokino-welcome__list">
						<li><a href="<?php echo esc_url( self::docs_url( '', 'docs' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'blokino' ); ?></a></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'getting-started/', 'getting-started' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Getting started guide', 'blokino' ); ?></a></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'faq/', 'faq' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'FAQ', 'blokino' ); ?></a></li>
						<li><a href="mailto:<?php echo esc_attr( self::SUPPORT ); ?>"><?php esc_html_e( 'Support', 'blokino' ); ?></a> · <?php echo esc_html( self::SUPPORT ); ?></li>
						<li><a href="<?php echo esc_url( self::GITHUB ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Source on GitHub', 'blokino' ); ?></a></li>
					</ul>
					<?php if ( $changes ) : ?>
						<h2 class="blokino-welcome__whatsnew">
							<?php
							/* translators: %s: version number. */
							echo esc_html( sprintf( __( 'What’s new in %s', 'blokino' ), $changes['version'] ) );
							?>
						</h2>
						<ul class="blokino-welcome__list blokino-welcome__list--changes">
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
