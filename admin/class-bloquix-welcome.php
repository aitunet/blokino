<?php
/**
 * Get started — the screen behind the "BloqUIX" menu entry.
 *
 * Where a site owner lands the first time: what the engine does, the three
 * steps that get a site going (theme → demo → effects), the latest changelog
 * entry, and the doors to docs and support. Plain WordPress admin UI, no
 * redirect on activation: a dismissible notice points here once and goes away
 * as soon as the screen is opened (or dismissed), per the wp.org guidelines.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get started screen.
 */
class Bloquix_Welcome {

	const CAPABILITY = 'manage_options';
	const OPTION     = 'bloquix_welcome_seen';
	const DOCS_URL   = 'https://tunetdesign.com/docs/';
	const SUPPORT    = 'info@tunetdesign.com';
	const GITHUB     = 'https://github.com/aitunet/bloquix';

	/**
	 * Wire hooks. The menu entry itself is registered by Bloquix_Admin (the
	 * top-level item IS this screen).
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
		add_action( 'admin_post_bloquix_welcome_dismiss', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Admin URL of this screen.
	 *
	 * @return string
	 */
	public static function url() {
		return admin_url( 'admin.php?page=' . Bloquix_Admin::MENU_SLUG );
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
		 * Base URL of the BloqUIX documentation.
		 *
		 * @param string $base Docs root, with trailing slash.
		 */
		$base = apply_filters( 'bloquix_docs_url', self::DOCS_URL );
		return add_query_arg(
			array(
				'utm_source'   => 'bloquix',
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
		if ( ! class_exists( 'Bloquix_Demo' ) ) {
			return 'none';
		}
		if ( Bloquix_Demo::get_record() ) {
			return 'imported';
		}
		return Bloquix_Demo::manifest() ? 'available' : 'none';
	}

	/**
	 * The latest changelog entry from readme.txt: version + bullet lines.
	 *
	 * @return array{version:string,items:string[]}|null
	 */
	public static function latest_changes() {
		$file = BLOQUIX_PATH . 'readme.txt';
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
		if ( $screen && 'toplevel_page_' . Bloquix_Admin::MENU_SLUG === $screen->id ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=bloquix_welcome_dismiss' ), 'bloquix_welcome_dismiss' );
		?>
		<div class="notice notice-info bloquix-welcome-notice">
			<p>
				<strong><?php esc_html_e( 'BloqUIX is ready.', 'bloquix' ); ?></strong>
				<?php esc_html_e( 'Pick a theme, import its demo and add motion to any block — the Get started screen walks you through it.', 'bloquix' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Get started', 'bloquix' ); ?></a>
				<a class="bloquix-welcome-notice__dismiss" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'bloquix' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Dismiss the notice (admin-post, nonce).
	 */
	public function handle_dismiss() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'bloquix' ) );
		}
		check_admin_referer( 'bloquix_welcome_dismiss' );
		self::mark_seen();
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Remember that the owner has seen the screen (or dismissed the notice).
	 */
	public static function mark_seen() {
		update_option( self::OPTION, defined( 'BLOQUIX_VERSION' ) ? BLOQUIX_VERSION : '1', false );
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
		$bloquix_theme = self::is_tunet_theme();
		$demo        = self::demo_state();
		$version     = defined( 'BLOQUIX_VERSION' ) ? BLOQUIX_VERSION : '';
		$changes     = self::latest_changes();
		$demo_url    = class_exists( 'Bloquix_Demo' ) ? admin_url( 'admin.php?page=' . Bloquix_Demo::MENU_SLUG ) : '';
		$themes_url  = class_exists( 'Bloquix_Themes' ) ? Bloquix_Themes::url() : '';
		$editor_url  = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ? admin_url( 'site-editor.php' ) : admin_url( 'post-new.php?post_type=page' );
		?>
		<div class="wrap bloquix-admin bloquix-welcome">
			<h1 class="bloquix-welcome__title"><?php esc_html_e( 'Get started', 'bloquix' ); ?></h1>

			<header class="bloquix-welcome__hero">
				<div class="bloquix-welcome__hero-copy">
					<p class="bloquix-welcome__kicker"><?php esc_html_e( 'BloqUIX', 'bloquix' ); ?><?php if ( $version ) : ?> <span class="bloquix-welcome__version">v<?php echo esc_html( $version ); ?></span><?php endif; ?></p>
					<p class="bloquix-welcome__headline"><?php esc_html_e( 'Motion for your blocks, on your terms.', 'bloquix' ); ?></p>
					<p class="bloquix-welcome__lede"><?php esc_html_e( 'BloqUIX adds entrance, hover and scroll effects to the blocks you already use, plus a few blocks the editor lacks — sliders, sections with rich backgrounds, marquees, counters. Nothing is applied until you switch it on, and every color and curve comes from your theme.', 'bloquix' ); ?></p>
				</div>
				<ul class="bloquix-welcome__status" aria-label="<?php esc_attr_e( 'Site status', 'bloquix' ); ?>">
					<li class="<?php echo $bloquix_theme ? 'is-good' : 'is-neutral'; ?>">
						<span class="bloquix-welcome__status-label"><?php esc_html_e( 'Theme', 'bloquix' ); ?></span>
						<strong><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong>
						<span class="bloquix-welcome__status-note"><?php echo $bloquix_theme ? esc_html__( 'Made for this engine', 'bloquix' ) : esc_html__( 'Any theme works — effects use the engine defaults', 'bloquix' ); ?></span>
					</li>
					<li class="<?php echo 'imported' === $demo ? 'is-good' : ( 'available' === $demo ? 'is-ready' : 'is-neutral' ); ?>">
						<span class="bloquix-welcome__status-label"><?php esc_html_e( 'Demo', 'bloquix' ); ?></span>
						<strong>
							<?php
							if ( 'imported' === $demo ) {
								esc_html_e( 'Imported', 'bloquix' );
							} elseif ( 'available' === $demo ) {
								esc_html_e( 'Ready to import', 'bloquix' );
							} else {
								esc_html_e( 'No demo for this theme', 'bloquix' );
							}
							?>
						</strong>
					</li>
					<li class="is-good">
						<span class="bloquix-welcome__status-label"><?php esc_html_e( 'Effects', 'bloquix' ); ?></span>
						<strong><?php esc_html_e( 'Runtime active', 'bloquix' ); ?></strong>
						<span class="bloquix-welcome__status-note"><?php esc_html_e( 'Loads only on pages that use an effect', 'bloquix' ); ?></span>
					</li>
				</ul>
			</header>

			<h2 class="bloquix-welcome__h2"><?php esc_html_e( 'Three steps to a finished site', 'bloquix' ); ?></h2>
			<ol class="bloquix-welcome__steps">
				<li class="bloquix-welcome__step<?php echo $bloquix_theme ? ' is-done' : ''; ?>">
					<span class="bloquix-welcome__num" aria-hidden="true">1</span>
					<h3><?php esc_html_e( 'Pick a theme', 'bloquix' ); ?></h3>
					<p><?php esc_html_e( 'Any theme works. A theme made for this engine also brings its own design tokens, block patterns and a one-click demo.', 'bloquix' ); ?></p>
					<?php if ( $bloquix_theme ) : ?>
						<p class="bloquix-welcome__done">
							<?php
							/* translators: %s: active theme name. */
							echo esc_html( sprintf( __( 'You’re on %s.', 'bloquix' ), $theme->get( 'Name' ) ) );
							?>
						</p>
					<?php elseif ( $themes_url ) : ?>
						<p><?php esc_html_e( 'Start free with Tunet Starter, our theme on WordPress.org — or pick a premium one.', 'bloquix' ); ?></p>
						<p>
							<?php if ( class_exists( 'Bloquix_Themes' ) && 'installed' === Bloquix_Themes::local_state( Bloquix_Themes::FREE_THEME ) ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'themes.php?action=activate&stylesheet=' . Bloquix_Themes::FREE_THEME ), 'switch-theme_' . Bloquix_Themes::FREE_THEME ) ); ?>"><?php esc_html_e( 'Activate Tunet Starter', 'bloquix' ); ?></a>
							<?php elseif ( class_exists( 'Bloquix_Themes' ) ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( Bloquix_Themes::free_theme_install_url() ); ?>"><?php esc_html_e( 'Get Tunet Starter — free', 'bloquix' ); ?></a>
							<?php endif; ?>
							<a class="button" href="<?php echo esc_url( $themes_url ); ?>"><?php esc_html_e( 'Browse themes', 'bloquix' ); ?></a>
						</p>
					<?php endif; ?>
				</li>
				<li class="bloquix-welcome__step<?php echo 'imported' === $demo ? ' is-done' : ''; ?>">
					<span class="bloquix-welcome__num" aria-hidden="true">2</span>
					<h3><?php esc_html_e( 'Import the demo', 'bloquix' ); ?></h3>
					<p><?php esc_html_e( 'Pages, patterns, images and settings, wired to your Media Library so you can edit or replace anything — with a one-click rollback.', 'bloquix' ); ?></p>
					<?php if ( 'imported' === $demo ) : ?>
						<p class="bloquix-welcome__done"><?php esc_html_e( 'Demo imported.', 'bloquix' ); ?> <a href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Manage', 'bloquix' ); ?></a></p>
					<?php elseif ( 'available' === $demo ) : ?>
						<p><a class="button button-primary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Open the Demo importer', 'bloquix' ); ?></a></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'This theme ships no demo. Build with the block patterns of your theme, or pick one made for this engine.', 'bloquix' ); ?></p>
					<?php endif; ?>
				</li>
				<li class="bloquix-welcome__step">
					<span class="bloquix-welcome__num" aria-hidden="true">3</span>
					<h3><?php esc_html_e( 'Add motion to any block', 'bloquix' ); ?></h3>
					<p><?php esc_html_e( 'Select a Group, Image, Heading, Paragraph, Cover or Buttons block and open the BloqUIX Effects panel in the sidebar: entrance animation, hover, scroll. Leave it empty and the block stays exactly as it is.', 'bloquix' ); ?></p>
					<div class="bloquix-welcome__panel" aria-hidden="true">
						<div class="bloquix-welcome__panel-title"><?php esc_html_e( 'BloqUIX Effects', 'bloquix' ); ?></div>
						<div class="bloquix-welcome__panel-row"><span><?php esc_html_e( 'Animation', 'bloquix' ); ?></span><span class="bloquix-welcome__panel-val">fade-up</span></div>
						<div class="bloquix-welcome__panel-row"><span><?php esc_html_e( 'Easing', 'bloquix' ); ?></span><span class="bloquix-welcome__panel-val">expo</span></div>
						<div class="bloquix-welcome__panel-row"><span><?php esc_html_e( 'Hover', 'bloquix' ); ?></span><span class="bloquix-welcome__panel-val">lift</span></div>
					</div>
					<p><a class="button" href="<?php echo esc_url( $editor_url ); ?>"><?php esc_html_e( 'Open the editor', 'bloquix' ); ?></a></p>
				</li>
			</ol>

			<div class="bloquix-welcome__cols">
				<section class="bloquix-welcome__card">
					<h2><?php esc_html_e( 'What BloqUIX adds', 'bloquix' ); ?></h2>
					<ul class="bloquix-welcome__list">
						<li><a href="<?php echo esc_url( self::docs_url( 'bloquix/effects/', 'effects' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Effects on native blocks', 'bloquix' ); ?></a> — <?php esc_html_e( 'entrance, hover and scroll, per block, opt-in.', 'bloquix' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'bloquix/blocks/', 'blocks' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Blocks the editor lacks', 'bloquix' ); ?></a> — <?php esc_html_e( 'Section, Slider, Marquee, Counter, Before/After, Testimonials, Brand, Icon, Badge.', 'bloquix' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'bloquix/tokens/', 'tokens' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Design tokens', 'bloquix' ); ?></a> — <?php esc_html_e( 'every effect reads its colors and curves from the active theme; style variations recolor everything.', 'bloquix' ); ?></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'bloquix/demo-importer/', 'importer' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Demo importer', 'bloquix' ); ?></a> — <?php esc_html_e( 'per theme, with progress and rollback.', 'bloquix' ); ?></li>
					</ul>
				</section>

				<section class="bloquix-welcome__card">
					<h2><?php esc_html_e( 'Help', 'bloquix' ); ?></h2>
					<ul class="bloquix-welcome__list">
						<li><a href="<?php echo esc_url( self::docs_url( '', 'docs' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'bloquix' ); ?></a></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'getting-started/', 'getting-started' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Getting started guide', 'bloquix' ); ?></a></li>
						<li><a href="<?php echo esc_url( self::docs_url( 'faq/', 'faq' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'FAQ', 'bloquix' ); ?></a></li>
						<li><a href="mailto:<?php echo esc_attr( self::SUPPORT ); ?>"><?php esc_html_e( 'Support', 'bloquix' ); ?></a> · <?php echo esc_html( self::SUPPORT ); ?></li>
						<li><a href="<?php echo esc_url( self::GITHUB ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Source on GitHub', 'bloquix' ); ?></a></li>
					</ul>
					<?php if ( $changes ) : ?>
						<h2 class="bloquix-welcome__whatsnew">
							<?php
							/* translators: %s: version number. */
							echo esc_html( sprintf( __( 'What’s new in %s', 'bloquix' ), $changes['version'] ) );
							?>
						</h2>
						<ul class="bloquix-welcome__list bloquix-welcome__list--changes">
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
