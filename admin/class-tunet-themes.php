<?php
/**
 * Tunet Core · Themes screen — the premium themes built for this engine.
 *
 * A dedicated submenu (Tunet Core → Themes) that lists the themes sold on
 * tunetdesign.com from the store's public catalog endpoint, so the list grows
 * with the store and never needs a plugin release (CLAUDE.md §12). Built to the
 * wordpress.org guidelines: no notices, no dashboard widgets, no nags — the only
 * way to see it is to open it; the catalog is fetched only then, cached for 12
 * hours, with a neutral user agent (nothing about this site is sent); and the
 * plugin keeps every feature whether or not a Tunet theme is active.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Themes screen.
 */
class Tunet_Core_Themes {

	const MENU_SLUG  = 'tunet-themes';
	const CAPABILITY = 'manage_options';
	const TRANSIENT  = 'tunet_core_themes_catalog';
	const ENDPOINT   = 'https://tunetdesign.com/wp-json/tunet/v1/themes';
	const STORE_URL  = 'https://tunetdesign.com/downloads/';
	const TTL        = 12 * HOUR_IN_SECONDS;

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 12 );
		add_action( 'admin_post_tunet_themes_refresh', array( $this, 'handle_refresh' ) );
	}

	/**
	 * Submenu under Tunet Core, after Demo.
	 */
	public function register_menu() {
		add_submenu_page(
			Tunet_Core_Admin::MENU_SLUG,
			__( 'Themes', 'tunet-core' ),
			__( 'Themes', 'tunet-core' ),
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
	 * (`add_filter( 'tunet_core_themes_endpoint', fn() => 'http://tunet.local/wp-json/tunet/v1/themes' )`).
	 *
	 * @return string
	 */
	public static function endpoint() {
		return (string) apply_filters( 'tunet_core_themes_endpoint', self::ENDPOINT );
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
				return new WP_Error( 'tunet_themes_offline', __( 'The theme catalog is not available right now.', 'tunet-core' ) );
			}
		}
		$response = wp_remote_get(
			self::endpoint(),
			array(
				'timeout'    => 8,
				'user-agent' => 'Tunet Core/' . ( defined( 'TUNET_CORE_VERSION' ) ? TUNET_CORE_VERSION : '0' ), // Neutral: WP's default UA carries the site URL.
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);
		$items = is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $items ) ) {
			set_transient( self::TRANSIENT . '_fail', 1, 10 * MINUTE_IN_SECONDS );
			return is_wp_error( $response ) ? $response : new WP_Error( 'tunet_themes_http', __( 'The theme catalog is not available right now.', 'tunet-core' ) );
		}
		delete_transient( self::TRANSIENT . '_fail' );
		$clean = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['name'] ) || empty( $item['url'] ) ) {
				continue;
			}
			$clean[] = array(
				'slug'     => sanitize_key( $item['slug'] ?? '' ),
				'theme'    => sanitize_key( $item['theme'] ?? '' ),
				'name'     => sanitize_text_field( $item['name'] ),
				'tagline'  => sanitize_text_field( $item['tagline'] ?? '' ),
				'price'    => sanitize_text_field( $item['price'] ?? '' ),
				'url'      => esc_url_raw( $item['url'] ),
				'demo_url' => esc_url_raw( $item['demo_url'] ?? '' ),
				'image'    => esc_url_raw( $item['image'] ?? '' ),
				'version'  => sanitize_text_field( $item['version'] ?? '' ),
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
			wp_die( esc_html__( 'Permission denied.', 'tunet-core' ) );
		}
		check_admin_referer( 'tunet_themes_refresh' );
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
				'utm_source'   => 'tunet-core',
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
	private static function local_state( $slug ) {
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
	 * Render.
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$catalog  = self::catalog();
		$demo_url = admin_url( 'admin.php?page=' . Tunet_Core_Demo::MENU_SLUG );
		$refresh  = wp_nonce_url( admin_url( 'admin-post.php?action=tunet_themes_refresh' ), 'tunet_themes_refresh' );
		?>
		<div class="wrap tunet-admin tunet-themes">
			<h1><?php esc_html_e( 'Tunet Core · Themes', 'tunet-core' ); ?></h1>
			<p class="description tunet-themes__lede">
				<?php esc_html_e( 'Premium themes designed for this engine: bespoke design tokens, block patterns and a one-click demo you import from this plugin. Tunet Core stays free and works with any theme.', 'tunet-core' ); ?>
			</p>

			<?php if ( is_wp_error( $catalog ) ) : ?>
				<div class="tunet-demo-empty">
					<span class="dashicons dashicons-cloud" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'The catalog could not be loaded.', 'tunet-core' ); ?></strong></p>
						<p class="description">
							<a href="<?php echo esc_url( self::out( self::STORE_URL, 'offline' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Browse the themes on tunetdesign.com ↗', 'tunet-core' ); ?></a>
							· <a href="<?php echo esc_url( $refresh ); ?>"><?php esc_html_e( 'Try again', 'tunet-core' ); ?></a>
						</p>
					</div>
				</div>
			<?php elseif ( empty( $catalog ) ) : ?>
				<div class="tunet-demo-empty">
					<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
					<div>
						<p><strong><?php esc_html_e( 'No themes listed yet.', 'tunet-core' ); ?></strong></p>
						<p class="description"><a href="<?php echo esc_url( self::out( self::STORE_URL, 'empty' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit tunetdesign.com ↗', 'tunet-core' ); ?></a></p>
					</div>
				</div>
			<?php else : ?>
				<div class="tunet-themes__grid">
					<?php foreach ( $catalog as $t ) : ?>
						<?php
						$state = self::local_state( $t['theme'] );
						$name  = preg_replace( '/\s+(?:—|–|-)\s+WordPress theme$/iu', '', $t['name'] ); // "Aurora — WordPress theme" → "Aurora".
						?>
						<article class="tunet-theme-card<?php echo $state ? ' is-' . esc_attr( $state ) : ''; ?>">
							<a class="tunet-theme-card__shot" href="<?php echo esc_url( self::out( $t['url'], 'image' ) ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
								<?php if ( $t['image'] ) : ?>
									<img src="<?php echo esc_url( $t['image'] ); ?>" alt="" loading="lazy" />
								<?php else : ?>
									<span class="tunet-theme-card__noshot"><?php echo esc_html( $name ); ?></span>
								<?php endif; ?>
								<?php if ( 'active' === $state ) : ?>
									<span class="tunet-theme-card__badge tunet-theme-card__badge--active"><?php esc_html_e( 'Active', 'tunet-core' ); ?></span>
								<?php elseif ( 'installed' === $state ) : ?>
									<span class="tunet-theme-card__badge"><?php esc_html_e( 'Installed', 'tunet-core' ); ?></span>
								<?php endif; ?>
							</a>
							<div class="tunet-theme-card__body">
								<div class="tunet-theme-card__head">
									<h2><?php echo esc_html( $name ); ?></h2>
									<?php if ( $t['price'] ) : ?><span class="tunet-theme-card__price"><?php echo esc_html( $t['price'] ); ?></span><?php endif; ?>
								</div>
								<?php if ( $t['tagline'] ) : ?><p class="tunet-theme-card__tagline"><?php echo esc_html( $t['tagline'] ); ?></p><?php endif; ?>
								<div class="tunet-theme-card__actions">
									<?php if ( 'active' === $state ) : ?>
										<a class="button button-primary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Import the demo', 'tunet-core' ); ?></a>
									<?php else : ?>
										<a class="button button-primary" href="<?php echo esc_url( self::out( $t['url'], 'get' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo 'installed' === $state ? esc_html__( 'View on tunetdesign.com ↗', 'tunet-core' ) : esc_html__( 'Get the theme ↗', 'tunet-core' ); ?></a>
									<?php endif; ?>
									<?php if ( $t['demo_url'] ) : ?>
										<a class="button" href="<?php echo esc_url( self::out( $t['demo_url'], 'preview' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Live preview ↗', 'tunet-core' ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="description tunet-themes__foot">
					<?php
					printf(
						/* translators: 1: store link, 2: refresh link. */
						esc_html__( 'Themes are sold on %1$s and come as parent + child theme with a license key for updates and support. %2$s', 'tunet-core' ),
						'<a href="' . esc_url( self::out( self::STORE_URL, 'footer' ) ) . '" target="_blank" rel="noopener noreferrer">tunetdesign.com</a>',
						'<a href="' . esc_url( $refresh ) . '">' . esc_html__( 'Refresh the list', 'tunet-core' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
