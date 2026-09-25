<?php
/**
 * Blokino · Themes screen — the premium themes built for this engine.
 *
 * A dedicated submenu (Blokino → Themes) that lists the themes sold on
 * tunetdesign.com from the store's public catalog endpoint, so the list grows
 * with the store and never needs a plugin release (CLAUDE.md §12). Built to the
 * wordpress.org guidelines: no notices, no dashboard widgets, no nags — the only
 * way to see it is to open it; the catalog is fetched only then, cached for 12
 * hours, with a neutral user agent (nothing about this site is sent); and the
 * plugin keeps every feature whether or not a Tunet theme is active.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Themes screen.
 */
class Blokino_Themes {

	const MENU_SLUG  = 'blokino-themes';
	const CAPABILITY = 'manage_options';
	const TRANSIENT  = 'blokino_themes_catalog';
	const ENDPOINT   = 'https://tunetdesign.com/wp-json/tunet/v1/themes';
	const STORE_URL  = 'https://tunetdesign.com/downloads/';
	const TTL        = 12 * HOUR_IN_SECONDS;
	/**
	 * The free theme — always listed first, no network needed. Until it is approved on WordPress.org
	 * (Blokino 1.1) the card links to its product page on the author's site instead of installing it;
	 * "Activate" stays when it is already installed.
	 */
	const FREE_THEME     = 'blokmark';
	const FREE_THEME_URL = 'https://www.tunetdesign.com/downloads/blokmark/';

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 13 );
		add_action( 'admin_post_blokino_themes_refresh', array( $this, 'handle_refresh' ) );
	}

	/**
	 * Submenu under Blokino, after Demo and the `blokino_admin_menu` hook
	 * (priority 13: Settings/Tools → Demo → theme screens → Themes).
	 */
	public function register_menu() {
		add_submenu_page(
			Blokino_Admin::MENU_SLUG,
			__( 'Themes', 'blokino' ),
			__( 'Themes', 'blokino' ),
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
	 * (`add_filter( 'blokino_themes_endpoint', fn() => 'http://tunet.local/wp-json/tunet/v1/themes' )`).
	 *
	 * @return string
	 */
	public static function endpoint() {
		return (string) apply_filters( 'blokino_themes_endpoint', self::ENDPOINT );
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
				return new WP_Error( 'blokino_themes_offline', __( 'The theme catalog is not available right now.', 'blokino' ) );
			}
		}
		$response = wp_remote_get(
			self::endpoint(),
			array(
				'timeout'    => 8,
				'user-agent' => 'Blokino/' . ( defined( 'BLOKINO_VERSION' ) ? BLOKINO_VERSION : '0' ), // Neutral: WP's default UA carries the site URL.
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);
		$items = is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $items ) ) {
			set_transient( self::TRANSIENT . '_fail', 1, 10 * MINUTE_IN_SECONDS );
			return is_wp_error( $response ) ? $response : new WP_Error( 'blokino_themes_http', __( 'The theme catalog is not available right now.', 'blokino' ) );
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
			wp_die( esc_html__( 'Permission denied.', 'blokino' ) );
		}
		check_admin_referer( 'blokino_themes_refresh' );
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
				'utm_source'   => 'blokino',
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
	 * Where to get the free theme: its product page (a download, not an installer)
	 * until it is on WordPress.org.
	 *
	 * @return string
	 */
	public static function free_theme_url() {
		return self::FREE_THEME_URL;
	}

	/**
	 * The free theme's card — Blokmark. Rendered from
	 * plugin data (no request), so it shows even when the store catalog is down,
	 * and a visitor who only installed the plugin learns there is a free theme
	 * made for it.
	 */
	private static function render_free_card() {
		$state = self::local_state( self::FREE_THEME );
		$shot  = BLOKINO_URL . 'admin/img/blokmark.webp';
		?>
		<article class="blokino-theme-card blokino-theme-card--free<?php echo $state ? ' is-' . esc_attr( $state ) : ''; ?>">
			<a class="blokino-theme-card__shot" href="<?php echo esc_url( self::FREE_THEME_URL ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
				<img src="<?php echo esc_url( $shot ); ?>" alt="" loading="lazy" />
				<?php if ( 'active' === $state ) : ?>
					<span class="blokino-theme-card__badge blokino-theme-card__badge--active"><?php esc_html_e( 'Active', 'blokino' ); ?></span>
				<?php elseif ( 'installed' === $state ) : ?>
					<span class="blokino-theme-card__badge"><?php esc_html_e( 'Installed', 'blokino' ); ?></span>
				<?php else : ?>
					<span class="blokino-theme-card__badge"><?php esc_html_e( 'Free', 'blokino' ); ?></span>
				<?php endif; ?>
			</a>
			<div class="blokino-theme-card__body">
				<div class="blokino-theme-card__head">
					<h2>Blokmark</h2>
					<span class="blokino-theme-card__price blokino-theme-card__price--free"><?php esc_html_e( 'Free', 'blokino' ); ?></span>
				</div>
				<p class="blokino-theme-card__tagline"><?php esc_html_e( 'Our free block theme: a designed home the moment you activate it, three looks, blog and page templates, and every effect of this engine.', 'blokino' ); ?></p>
				<div class="blokino-theme-card__actions">
					<?php if ( 'active' === $state ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Open the Site Editor', 'blokino' ); ?></a>
					<?php elseif ( 'installed' === $state ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'themes.php?action=activate&stylesheet=' . self::FREE_THEME ), 'switch-theme_' . self::FREE_THEME ) ); ?>"><?php esc_html_e( 'Activate', 'blokino' ); ?></a>
					<?php else : ?>
						<a class="button button-primary" href="<?php echo esc_url( self::free_theme_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Download free ↗', 'blokino' ); ?></a>
					<?php endif; ?>
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
		if ( is_array( $catalog ) ) {
			// The store lists the free theme too; it already has its own card above the premium grid.
			$catalog = array_values( array_filter( $catalog, static function ( $t ) { return self::FREE_THEME !== $t['theme']; } ) );
		}
		$demo_url = admin_url( 'admin.php?page=' . Blokino_Demo::MENU_SLUG );
		$refresh  = wp_nonce_url( admin_url( 'admin-post.php?action=blokino_themes_refresh' ), 'blokino_themes_refresh' );
		?>
		<div class="wrap blokino-admin blokino-themes">
			<h1><?php esc_html_e( 'Blokino · Themes', 'blokino' ); ?></h1>
			<p class="description blokino-themes__lede">
				<?php esc_html_e( 'Themes designed for this engine. Blokmark is free; the premium ones bring bespoke design tokens, block patterns and a one-click demo you import from this plugin. Blokino stays free and works with any theme.', 'blokino' ); ?>
			</p>

			<div class="blokino-themes__grid blokino-themes__grid--free">
				<?php self::render_free_card(); ?>
			</div>

			<h2 class="blokino-themes__h2"><?php esc_html_e( 'Premium themes', 'blokino' ); ?></h2>
			<?php if ( is_wp_error( $catalog ) ) : ?>
				<div class="blokino-demo-empty">
					<span class="dashicons dashicons-cloud" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'The catalog could not be loaded.', 'blokino' ); ?></strong></p>
						<p class="description">
							<a href="<?php echo esc_url( self::out( self::STORE_URL, 'offline' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Browse the themes on tunetdesign.com ↗', 'blokino' ); ?></a>
							· <a href="<?php echo esc_url( $refresh ); ?>"><?php esc_html_e( 'Try again', 'blokino' ); ?></a>
						</p>
					</div>
				</div>
			<?php elseif ( empty( $catalog ) ) : ?>
				<div class="blokino-demo-empty">
					<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'No themes listed yet.', 'blokino' ); ?></strong></p>
						<p class="description"><a href="<?php echo esc_url( self::out( self::STORE_URL, 'empty' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit tunetdesign.com ↗', 'blokino' ); ?></a></p>
					</div>
				</div>
			<?php else : ?>
				<div class="blokino-themes__grid">
					<?php foreach ( $catalog as $t ) : ?>
						<?php
						$state = self::local_state( $t['theme'] );
						$name  = preg_replace( '/\s+(?:—|–|-)\s+WordPress theme$/iu', '', $t['name'] ); // "Aurora — WordPress theme" → "Aurora".
						?>
						<article class="blokino-theme-card<?php echo $state ? ' is-' . esc_attr( $state ) : ''; ?>">
							<a class="blokino-theme-card__shot" href="<?php echo esc_url( self::out( $t['url'], 'image' ) ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
								<?php if ( $t['image'] ) : ?>
									<img src="<?php echo esc_url( $t['image'] ); ?>" alt="" loading="lazy" />
								<?php else : ?>
									<span class="blokino-theme-card__noshot"><?php echo esc_html( $name ); ?></span>
								<?php endif; ?>
								<?php if ( 'active' === $state ) : ?>
									<span class="blokino-theme-card__badge blokino-theme-card__badge--active"><?php esc_html_e( 'Active', 'blokino' ); ?></span>
								<?php elseif ( 'installed' === $state ) : ?>
									<span class="blokino-theme-card__badge"><?php esc_html_e( 'Installed', 'blokino' ); ?></span>
								<?php endif; ?>
							</a>
							<div class="blokino-theme-card__body">
								<div class="blokino-theme-card__head">
									<h2><?php echo esc_html( $name ); ?></h2>
									<?php if ( $t['price'] ) : ?><span class="blokino-theme-card__price"><?php echo esc_html( $t['price'] ); ?></span><?php endif; ?>
								</div>
								<?php if ( $t['tagline'] ) : ?><p class="blokino-theme-card__tagline"><?php echo esc_html( $t['tagline'] ); ?></p><?php endif; ?>
								<div class="blokino-theme-card__actions">
									<?php if ( 'active' === $state ) : ?>
										<a class="button button-primary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Import the demo', 'blokino' ); ?></a>
									<?php else : ?>
										<a class="button button-primary" href="<?php echo esc_url( self::out( $t['url'], 'get' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo 'installed' === $state ? esc_html__( 'View on tunetdesign.com ↗', 'blokino' ) : esc_html__( 'Get the theme ↗', 'blokino' ); ?></a>
									<?php endif; ?>
									<?php if ( $t['demo_url'] ) : ?>
										<a class="button" href="<?php echo esc_url( self::out( $t['demo_url'], 'preview' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Live preview ↗', 'blokino' ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="description blokino-themes__foot">
					<?php
					printf(
						/* translators: 1: store link, 2: refresh link. */
						esc_html__( 'Themes are sold on %1$s and come as parent + child theme with a license key for updates and support. %2$s', 'blokino' ),
						'<a href="' . esc_url( self::out( self::STORE_URL, 'footer' ) ) . '" target="_blank" rel="noopener noreferrer">tunetdesign.com</a>',
						'<a href="' . esc_url( $refresh ) . '">' . esc_html__( 'Refresh the list', 'blokino' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
