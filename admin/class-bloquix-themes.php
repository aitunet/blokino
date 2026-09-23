<?php
/**
 * BloqUIX · Themes screen — the premium themes built for this engine.
 *
 * A dedicated submenu (BloqUIX → Themes) that lists the themes sold on
 * tunetdesign.com from the store's public catalog endpoint, so the list grows
 * with the store and never needs a plugin release (CLAUDE.md §12). Built to the
 * wordpress.org guidelines: no notices, no dashboard widgets, no nags — the only
 * way to see it is to open it; the catalog is fetched only then, cached for 12
 * hours, with a neutral user agent (nothing about this site is sent); and the
 * plugin keeps every feature whether or not a Tunet theme is active.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Themes screen.
 */
class Bloquix_Themes {

	const MENU_SLUG  = 'bloquix-themes';
	const CAPABILITY = 'manage_options';
	const TRANSIENT  = 'bloquix_themes_catalog';
	const ENDPOINT   = 'https://tunetdesign.com/wp-json/tunet/v1/themes';
	const STORE_URL  = 'https://tunetdesign.com/downloads/';
	const TTL        = 12 * HOUR_IN_SECONDS;
	/** The free theme, published on WordPress.org — always listed first, no network needed. */
	const FREE_THEME     = 'tunet-starter';
	const FREE_THEME_URL = 'https://wordpress.org/themes/tunet-starter/';

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 13 );
		add_action( 'admin_post_bloquix_themes_refresh', array( $this, 'handle_refresh' ) );
	}

	/**
	 * Submenu under BloqUIX, after Demo and the `bloquix_admin_menu` hook
	 * (priority 13: Settings/Tools → Demo → theme screens → Themes).
	 */
	public function register_menu() {
		add_submenu_page(
			Bloquix_Admin::MENU_SLUG,
			__( 'Themes', 'bloquix' ),
			__( 'Themes', 'bloquix' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Admin URL of this screen.
	 *
	 * @return string
	 */
	public static function url() {
		return admin_url( 'admin.php?page=' . self::MENU_SLUG );
	}

	/**
	 * Catalog endpoint. Filterable so a staging or local store can be pointed at
	 * (`add_filter( 'bloquix_themes_endpoint', fn() => 'http://tunet.local/wp-json/tunet/v1/themes' )`).
	 *
	 * @return string
	 */
	public static function endpoint() {
		return (string) apply_filters( 'bloquix_themes_endpoint', self::ENDPOINT );
	}

	/**
	 * The catalog: cached list of themes, or a WP_Error when the store could not
	 * be reached and nothing is cached. Fetched only from this screen.
	 *
	 * @param bool $force Ignore the cache.
	 * @return array|WP_Error
	 */
	public static function catalog( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
			// A failed fetch is remembered for a few minutes so a site without outbound
			// access does not wait for the timeout on every visit ("Try again" bypasses it).
			if ( get_transient( self::TRANSIENT . '_fail' ) ) {
				return new WP_Error( 'bloquix_themes_offline', __( 'The theme catalog is not available right now.', 'bloquix' ) );
			}
		}
		$response = wp_remote_get(
			self::endpoint(),
			array(
				'timeout'    => 8,
				'user-agent' => 'BloqUIX/' . ( defined( 'BLOQUIX_VERSION' ) ? BLOQUIX_VERSION : '0' ), // Neutral: WP's default UA carries the site URL.
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);
		$items = is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $items ) ) {
			set_transient( self::TRANSIENT . '_fail', 1, 10 * MINUTE_IN_SECONDS );
			return is_wp_error( $response ) ? $response : new WP_Error( 'bloquix_themes_http', __( 'The theme catalog is not available right now.', 'bloquix' ) );
		}
		delete_transient( self::TRANSIENT . '_fail' );
		$clean = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			// Remote JSON: every field is read as a scalar (a nested value would
			// hit the sanitizers as an array) before being sanitized.
			$field = static function ( $key ) use ( $item ) {
				return isset( $item[ $key ] ) && is_scalar( $item[ $key ] ) ? (string) $item[ $key ] : '';
			};
			if ( '' === $field( 'name' ) || '' === $field( 'url' ) ) {
				continue;
			}
			$clean[] = array(
				'slug'     => sanitize_key( $field( 'slug' ) ),
				'theme'    => sanitize_key( $field( 'theme' ) ),
				'name'     => sanitize_text_field( $field( 'name' ) ),
				'tagline'  => sanitize_text_field( $field( 'tagline' ) ),
				'price'    => sanitize_text_field( $field( 'price' ) ),
				'url'      => esc_url_raw( $field( 'url' ) ),
				'demo_url' => esc_url_raw( $field( 'demo_url' ) ),
				'image'    => esc_url_raw( $field( 'image' ) ),
				'version'  => sanitize_text_field( $field( 'version' ) ),
			);
		}
		set_transient( self::TRANSIENT, $clean, self::TTL );
		return $clean;
	}

	/**
	 * "Refresh" button: drop the cache and come back.
	 */
	public function handle_refresh() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'bloquix' ) );
		}
		check_admin_referer( 'bloquix_themes_refresh' );
		delete_transient( self::TRANSIENT );
		delete_transient( self::TRANSIENT . '_fail' );
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Outbound store link with attribution.
	 *
	 * @param string $url     Store URL.
	 * @param string $content Where the click comes from.
	 * @return string
	 */
	private static function out( $url, $content ) {
		return add_query_arg(
			array(
				'utm_source'   => 'bloquix',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'themes-screen',
				'utm_content'  => $content,
			),
			$url
		);
	}

	/**
	 * Local state of a catalog theme: 'active', 'installed' or ''.
	 *
	 * @param string $slug Theme directory slug.
	 * @return string
	 */
	public static function local_state( $slug ) {
		if ( '' === $slug ) {
			return '';
		}
		$active = wp_get_theme();
		if ( $slug === $active->get_stylesheet() || $slug === $active->get_template() ) {
			return 'active';
		}
		return wp_get_theme( $slug )->exists() ? 'installed' : '';
	}

	/**
	 * Where a click on "Install" goes: WordPress's own theme installer, opened
	 * on the free theme (the directory serves the ZIP; nothing is downloaded by
	 * this plugin).
	 *
	 * @return string
	 */
	public static function free_theme_install_url() {
		return admin_url( 'theme-install.php?theme=' . self::FREE_THEME );
	}

	/**
	 * The free theme's card — Tunet Starter from WordPress.org. Rendered from
	 * plugin data (no request), so it shows even when the store catalog is down,
	 * and a visitor who only installed the plugin learns there is a free theme
	 * made for it.
	 */
	private static function render_free_card() {
		$state = self::local_state( self::FREE_THEME );
		$shot  = BLOQUIX_URL . 'admin/img/tunet-starter.webp';
		?>
		<article class="bloquix-theme-card bloquix-theme-card--free<?php echo $state ? ' is-' . esc_attr( $state ) : ''; ?>">
			<a class="bloquix-theme-card__shot" href="<?php echo esc_url( self::FREE_THEME_URL ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
				<img src="<?php echo esc_url( $shot ); ?>" alt="" loading="lazy" />
				<?php if ( 'active' === $state ) : ?>
					<span class="bloquix-theme-card__badge bloquix-theme-card__badge--active"><?php esc_html_e( 'Active', 'bloquix' ); ?></span>
				<?php elseif ( 'installed' === $state ) : ?>
					<span class="bloquix-theme-card__badge"><?php esc_html_e( 'Installed', 'bloquix' ); ?></span>
				<?php else : ?>
					<span class="bloquix-theme-card__badge"><?php esc_html_e( 'Free', 'bloquix' ); ?></span>
				<?php endif; ?>
			</a>
			<div class="bloquix-theme-card__body">
				<div class="bloquix-theme-card__head">
					<h2>Tunet Starter</h2>
					<span class="bloquix-theme-card__price bloquix-theme-card__price--free"><?php esc_html_e( 'Free', 'bloquix' ); ?></span>
				</div>
				<p class="bloquix-theme-card__tagline"><?php esc_html_e( 'Our free block theme on WordPress.org: a designed home the moment you activate it, three looks, blog and page templates — and every effect of this engine.', 'bloquix' ); ?></p>
				<div class="bloquix-theme-card__actions">
					<?php if ( 'active' === $state ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Open the Site Editor', 'bloquix' ); ?></a>
					<?php elseif ( 'installed' === $state ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'themes.php?action=activate&stylesheet=' . self::FREE_THEME ), 'switch-theme_' . self::FREE_THEME ) ); ?>"><?php esc_html_e( 'Activate', 'bloquix' ); ?></a>
					<?php else : ?>
						<a class="button button-primary" href="<?php echo esc_url( self::free_theme_install_url() ); ?>"><?php esc_html_e( 'Install from WordPress.org', 'bloquix' ); ?></a>
					<?php endif; ?>
					<a class="button" href="<?php echo esc_url( self::FREE_THEME_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Details ↗', 'bloquix' ); ?></a>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Render.
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$catalog  = self::catalog();
		$demo_url = admin_url( 'admin.php?page=' . Bloquix_Demo::MENU_SLUG );
		$refresh  = wp_nonce_url( admin_url( 'admin-post.php?action=bloquix_themes_refresh' ), 'bloquix_themes_refresh' );
		?>
		<div class="wrap bloquix-admin bloquix-themes">
			<h1><?php esc_html_e( 'BloqUIX · Themes', 'bloquix' ); ?></h1>
			<p class="description bloquix-themes__lede">
				<?php esc_html_e( 'Themes designed for this engine — Tunet Starter is free on WordPress.org, the premium ones bring bespoke design tokens, block patterns and a one-click demo you import from this plugin. BloqUIX stays free and works with any theme.', 'bloquix' ); ?>
			</p>

			<div class="bloquix-themes__grid bloquix-themes__grid--free">
				<?php self::render_free_card(); ?>
			</div>

			<h2 class="bloquix-themes__h2"><?php esc_html_e( 'Premium themes', 'bloquix' ); ?></h2>
			<?php if ( is_wp_error( $catalog ) ) : ?>
				<div class="bloquix-demo-empty">
					<span class="dashicons dashicons-cloud" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'The catalog could not be loaded.', 'bloquix' ); ?></strong></p>
						<p class="description">
							<a href="<?php echo esc_url( self::out( self::STORE_URL, 'offline' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Browse the themes on tunetdesign.com ↗', 'bloquix' ); ?></a>
							· <a href="<?php echo esc_url( $refresh ); ?>"><?php esc_html_e( 'Try again', 'bloquix' ); ?></a>
						</p>
					</div>
				</div>
			<?php elseif ( empty( $catalog ) ) : ?>
				<div class="bloquix-demo-empty">
					<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'No themes listed yet.', 'bloquix' ); ?></strong></p>
						<p class="description"><a href="<?php echo esc_url( self::out( self::STORE_URL, 'empty' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit tunetdesign.com ↗', 'bloquix' ); ?></a></p>
					</div>
				</div>
			<?php else : ?>
				<div class="bloquix-themes__grid">
					<?php foreach ( $catalog as $t ) : ?>
						<?php
						$state = self::local_state( $t['theme'] );
						$name  = preg_replace( '/\s+(?:—|–|-)\s+WordPress theme$/iu', '', $t['name'] ); // "Aurora — WordPress theme" → "Aurora".
						?>
						<article class="bloquix-theme-card<?php echo $state ? ' is-' . esc_attr( $state ) : ''; ?>">
							<a class="bloquix-theme-card__shot" href="<?php echo esc_url( self::out( $t['url'], 'image' ) ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
								<?php if ( $t['image'] ) : ?>
									<img src="<?php echo esc_url( $t['image'] ); ?>" alt="" loading="lazy" />
								<?php else : ?>
									<span class="bloquix-theme-card__noshot"><?php echo esc_html( $name ); ?></span>
								<?php endif; ?>
								<?php if ( 'active' === $state ) : ?>
									<span class="bloquix-theme-card__badge bloquix-theme-card__badge--active"><?php esc_html_e( 'Active', 'bloquix' ); ?></span>
								<?php elseif ( 'installed' === $state ) : ?>
									<span class="bloquix-theme-card__badge"><?php esc_html_e( 'Installed', 'bloquix' ); ?></span>
								<?php endif; ?>
							</a>
							<div class="bloquix-theme-card__body">
								<div class="bloquix-theme-card__head">
									<h2><?php echo esc_html( $name ); ?></h2>
									<?php if ( $t['price'] ) : ?><span class="bloquix-theme-card__price"><?php echo esc_html( $t['price'] ); ?></span><?php endif; ?>
								</div>
								<?php if ( $t['tagline'] ) : ?><p class="bloquix-theme-card__tagline"><?php echo esc_html( $t['tagline'] ); ?></p><?php endif; ?>
								<div class="bloquix-theme-card__actions">
									<?php if ( 'active' === $state ) : ?>
										<a class="button button-primary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Import the demo', 'bloquix' ); ?></a>
									<?php else : ?>
										<a class="button button-primary" href="<?php echo esc_url( self::out( $t['url'], 'get' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo 'installed' === $state ? esc_html__( 'View on tunetdesign.com ↗', 'bloquix' ) : esc_html__( 'Get the theme ↗', 'bloquix' ); ?></a>
									<?php endif; ?>
									<?php if ( $t['demo_url'] ) : ?>
										<a class="button" href="<?php echo esc_url( self::out( $t['demo_url'], 'preview' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Live preview ↗', 'bloquix' ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="description bloquix-themes__foot">
					<?php
					printf(
						/* translators: 1: store link, 2: refresh link. */
						esc_html__( 'Themes are sold on %1$s and come as parent + child theme with a license key for updates and support. %2$s', 'bloquix' ),
						'<a href="' . esc_url( self::out( self::STORE_URL, 'footer' ) ) . '" target="_blank" rel="noopener noreferrer">tunetdesign.com</a>',
						'<a href="' . esc_url( $refresh ) . '">' . esc_html__( 'Refresh the list', 'bloquix' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
