<?php
/**
 * Engine admin: settings (stacked sections), global branding overrides, brand
 * logos, and a separate Tools page (import/export).
 *
 * (CLAUDE.md §4.4.) OVERRIDE MODEL: the theme is the source of truth for the
 * --tnt-* tokens; this panel only stores overrides, which the runtime injects
 * as :root{--tnt-*}. Fonts load from Google Fonts (CDN). Logos are engine-level
 * brand assets (they persist across theme switches); the theme decides where to
 * place them (tunet/brand block or the native Site Logo block).
 *
 * Reads/builders are STATIC so the runtime can use them on the front-end.
 *
 * Default UI language is English; strings are translatable (text domain 'tunet-core').
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Engine administration.
 */
class Tunet_Core_Admin {

	const OPTION     = 'tunet_core_settings';
	const MENU_SLUG  = 'tunet-core';
	const TOOLS_SLUG  = 'tunet-tools';
	const CAPABILITY  = 'manage_options';

	/* ---------------------------------------------------------------------
	 * Catalogs (curated Google Fonts)
	 * ------------------------------------------------------------------ */

	/** Sans/serif fonts (display/body) → weights. */
	public static function fonts_text() {
		return array(
			'Inter'             => '400;500;600;700',
			'Sora'              => '400;600;700;800',
			'Space Grotesk'     => '400;500;700',
			'Manrope'           => '400;600;700;800',
			'Poppins'           => '400;500;600;700',
			'Plus Jakarta Sans' => '400;500;600;700',
			'Outfit'            => '400;600;700;800',
			'DM Sans'           => '400;500;700',
			'Archivo'           => '400;600;700;800',
			'Syne'              => '600;700;800',
			'Fraunces'          => '400;600;700',
			'Playfair Display'  => '400;600;700',
			'DM Serif Display'  => '400',
		);
	}

	/** Monospace fonts → weights. */
	public static function fonts_mono() {
		return array(
			'JetBrains Mono' => '400;500;700',
			'Space Mono'     => '400;700',
			'IBM Plex Mono'  => '400;500;600',
			'Fira Code'      => '400;500;600',
			'Roboto Mono'    => '400;500;700',
		);
	}

	/** Families using a serif stack. */
	private static function serif_fonts() {
		return array( 'Fraunces', 'Playfair Display', 'DM Serif Display' );
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	/**
	 * Defaults (empty overrides = use the theme).
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'effects_enabled' => true,
			'font_display'    => '',
			'font_body'       => '',
			'font_mono'       => '',
			'text_base'       => '',
			'brand_primary'   => '',
			'brand_accent'    => '',
			'brand_accent_2'  => '',
			'brand_bg'        => '',
			'brand_text'      => '',
			'radius'          => '',
			'motion'          => '',
			'logo_main_id'    => 0,
			'logo_alt_id'     => 0,
			'layout_post_single' => '',
			'layout_archive'     => '',
		);
	}

	/**
	 * Current settings (with defaults).
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Read a single setting.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$s = self::get_settings();
		return isset( $s[ $key ] ) ? $s[ $key ] : null;
	}

	/**
	 * Is the effects engine enabled? (runtime).
	 *
	 * @return bool
	 */
	public static function effects_enabled() {
		return (bool) self::get( 'effects_enabled' );
	}

	/* ---------------------------------------------------------------------
	 * Builders consumed by the runtime (front + editor)
	 * ------------------------------------------------------------------ */

	/**
	 * CSS font stack for a family.
	 *
	 * @param string $family Family.
	 * @return string
	 */
	private static function font_stack( $family ) {
		if ( in_array( $family, self::serif_fonts(), true ) ) {
			return "'" . $family . "', Georgia, 'Times New Roman', serif";
		}
		if ( array_key_exists( $family, self::fonts_mono() ) ) {
			return "'" . $family . "', ui-monospace, SFMono-Regular, Menlo, monospace";
		}
		return "'" . $family . "', system-ui, -apple-system, 'Segoe UI', sans-serif";
	}

	/**
	 * Override CSS (:root{--tnt-*}). Empty if none.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	public static function branding_css( $s ) {
		$d    = array();
		$text = self::fonts_text();
		$mono = self::fonts_mono();

		if ( ! empty( $s['font_display'] ) && isset( $text[ $s['font_display'] ] ) ) {
			$d[] = '--tnt-font-display:' . self::font_stack( $s['font_display'] );
		}
		if ( ! empty( $s['font_body'] ) && isset( $text[ $s['font_body'] ] ) ) {
			$d[] = '--tnt-font-body:' . self::font_stack( $s['font_body'] );
		}
		if ( ! empty( $s['font_mono'] ) && isset( $mono[ $s['font_mono'] ] ) ) {
			$d[] = '--tnt-font-mono:' . self::font_stack( $s['font_mono'] );
		}

		$base = array(
			'sm' => 'clamp(0.9375rem, 0.9rem + 0.2vw, 1rem)',
			'lg' => 'clamp(1.0625rem, 1rem + 0.3vw, 1.25rem)',
		);
		if ( ! empty( $s['text_base'] ) && isset( $base[ $s['text_base'] ] ) ) {
			$d[] = '--tnt-text-base:' . $base[ $s['text_base'] ];
		}

		$colors = array(
			'brand_primary'  => '--tnt-color-primary',
			'brand_accent'   => '--tnt-color-accent',
			'brand_accent_2' => '--tnt-color-accent-2',
			'brand_bg'       => '--tnt-color-bg',
			'brand_text'     => '--tnt-color-text',
		);
		foreach ( $colors as $key => $var ) {
			$hex = isset( $s[ $key ] ) ? sanitize_hex_color( $s[ $key ] ) : '';
			if ( $hex ) {
				$d[] = $var . ':' . $hex;
			}
		}

		if ( isset( $s['radius'] ) && '' !== $s['radius'] && is_numeric( $s['radius'] ) ) {
			$px  = max( 0, (int) $s['radius'] );
			$d[] = '--tnt-radius-sm:' . (int) round( $px * 0.6 ) . 'px';
			$d[] = '--tnt-radius-md:' . $px . 'px';
			$d[] = '--tnt-radius-lg:' . (int) round( $px * 1.6 ) . 'px';
		}

		if ( 'subtle' === ( $s['motion'] ?? '' ) ) {
			$d[] = '--tnt-dur-fast:150ms';
			$d[] = '--tnt-dur-base:300ms';
			$d[] = '--tnt-dur-slow:500ms';
		} elseif ( 'bold' === ( $s['motion'] ?? '' ) ) {
			$d[] = '--tnt-dur-fast:250ms';
			$d[] = '--tnt-dur-base:550ms';
			$d[] = '--tnt-dur-slow:900ms';
		}

		return $d ? ':root{' . implode( ';', $d ) . '}' : '';
	}

	/**
	 * Google Fonts URL. Empty if none.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	public static function fonts_url( $s ) {
		$text     = self::fonts_text();
		$mono     = self::fonts_mono();
		$families = array();

		foreach ( array( 'font_display', 'font_body' ) as $key ) {
			if ( ! empty( $s[ $key ] ) && isset( $text[ $s[ $key ] ] ) ) {
				$families[ $s[ $key ] ] = $text[ $s[ $key ] ];
			}
		}
		if ( ! empty( $s['font_mono'] ) && isset( $mono[ $s['font_mono'] ] ) ) {
			$families[ $s['font_mono'] ] = $mono[ $s['font_mono'] ];
		}
		if ( ! $families ) {
			return '';
		}

		$parts = array();
		foreach ( $families as $family => $weights ) {
			$parts[] = 'family=' . str_replace( ' ', '+', $family ) . ':wght@' . $weights;
		}
		return 'https://fonts.googleapis.com/css2?' . implode( '&', $parts ) . '&display=swap';
	}

	/**
	 * Attachment ID for a logo variant.
	 *
	 * @param string $variant main|alt.
	 * @return int
	 */
	public static function logo_id( $variant = 'main' ) {
		$key = ( 'alt' === $variant ) ? 'logo_alt_id' : 'logo_main_id';
		$id  = (int) self::get( $key );
		if ( ! $id && 'alt' === $variant ) {
			$id = (int) self::get( 'logo_main_id' );
		}
		return $id;
	}

	/* ---------------------------------------------------------------------
	 * Hooks
	 * ------------------------------------------------------------------ */

	/**
	 * Wire hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'menu_icon_style' ) );
		add_action( 'admin_post_tunet_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_tunet_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_tunet_import', array( $this, 'handle_import' ) );
	}

	/**
	 * La forma de la marca: el núcleo con la T en contraforma.
	 *
	 * Un solo sitio con el `d` del path, para que el data-URI del menú y la máscara
	 * de menu_icon_style() no puedan divergir. La fuente legible, con el porqué del
	 * diseño y las mediciones de color, está en admin/img/icon-tunet-core.svg; aquí
	 * va en línea porque add_menu_page() necesita el valor al registrar el menú y
	 * leer el archivo en cada carga del admin sería una lectura de disco por página
	 * para 300 bytes.
	 */
	const ICON_PATH = 'M7.5 2.5h9a5 5 0 0 1 5 5v9a5 5 0 0 1-5 5h-9a5 5 0 0 1-5-5v-9a5 5 0 0 1 5-5zM6.8 6.6h10.4v3.3h-3.6v8.7h-3.2V9.9H6.8z';

	/**
	 * La marca como data-URI para add_menu_page().
	 *
	 * El color va clavado al gris del menú de WP (#a7aaad) porque un icono pasado por
	 * data-URI se pinta como background-image y WP NO lo recolorea: ese hex es el
	 * estado de reposo. El activo/hover lo resuelve menu_icon_style().
	 *
	 * @return string
	 */
	private static function menu_icon_data_uri() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
			. '<path fill-rule="evenodd" fill="#a7aaad" d="' . self::ICON_PATH . '"/>'
			. '</svg>';
		// base64 y no percent-encoding: es la convención de WP para iconos de menú y
		// evita tener que acertar con el escapado de #, <, > y las comillas.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- data-URI, no ofuscación.
	}

	/**
	 * Hace que el icono del menú siga el esquema de color del admin.
	 *
	 * Un icono de menú pasado como data-URI se pinta con background-image y WordPress
	 * no lo recolorea: se queda gris cuando el ítem está activo o con el ratón encima,
	 * mientras la etiqueta se pone blanca. Se ve descuidado, y es la razón por la que
	 * tantos plugins tienen el icono apagado en su propia pantalla.
	 *
	 * Solución: repintarlo con una máscara sobre `currentColor`, que sí hereda el
	 * color del ítem (gris → blanco). Va detrás de un @supports y solo entonces se
	 * oculta el background, de modo que un navegador sin máscaras conserva el icono
	 * original en lugar de quedarse sin ninguno.
	 *
	 * Se emite en admin_head y no en admin.css porque ese archivo solo se encola en
	 * las pantallas del plugin, y el icono del menú sale en TODAS.
	 */
	public function menu_icon_style() {
		// La máscara usa el alfa, así que el color del path da igual: negro = opaco.
		$mask = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill-rule='evenodd' fill='%23000' d='"
			. self::ICON_PATH . "'/%3E%3C/svg%3E\")";
		$sel  = '#adminmenu #toplevel_page_' . self::MENU_SLUG . ' .wp-menu-image';
		?>
		<style id="tunet-core-menu-icon">
			@supports ((-webkit-mask-image: <?php echo $mask; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>) or (mask-image: <?php echo $mask; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>)) {
				<?php echo esc_html( $sel ); ?> { background-image: none !important; }
				<?php echo esc_html( $sel ); ?>::before {
					content: "";
					display: block;
					width: 20px;
					height: 20px;
					/*
					 * SIN margin vertical: WP ya centra los iconos del menú con
					 * `padding: 7px 0` sobre un contenedor de 34px (7+20+7=34, centro
					 * exacto), y esa regla también aplica aquí. Un margen propio SE SUMA
					 * a ese padding y baja la marca respecto a todos los dashicons
					 * vecinos — medido: con `margin-top:6px` el centro salía a 23px
					 * cuando el de los demás está a 17px. Solo `auto` horizontal.
					 */
					margin: 0 auto;
					background-color: currentColor;
					-webkit-mask-image: <?php echo $mask; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>;
					mask-image: <?php echo $mask; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>;
					-webkit-mask-repeat: no-repeat;
					mask-repeat: no-repeat;
					-webkit-mask-position: center;
					mask-position: center;
					-webkit-mask-size: 20px 20px;
					mask-size: 20px 20px;
				}
			}
		</style>
		<?php
	}

	/**
	 * Register menu + submenus + Appearance link.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Tunet Core', 'tunet-core' ),
			__( 'Tunet Core', 'tunet-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_settings_page' ),
			self::menu_icon_data_uri(),
			59
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'tunet-core' ),
			__( 'Settings', 'tunet-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tools', 'tunet-core' ),
			__( 'Tools', 'tunet-core' ),
			self::CAPABILITY,
			self::TOOLS_SLUG,
			array( $this, 'render_tools_page' )
		);

		// Direct link under Appearance → settings page.
		add_submenu_page(
			'themes.php',
			__( 'Tunet Core', 'tunet-core' ),
			__( 'Tunet Core', 'tunet-core' ),
			self::CAPABILITY,
			'admin.php?page=' . self::MENU_SLUG
		);
	}

	/**
	 * Enqueue assets on the plugin pages.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$is_settings = ( 'toplevel_page_' . self::MENU_SLUG === $hook );
		$is_tools    = ( false !== strpos( $hook, self::TOOLS_SLUG ) );
		$is_demo     = ( false !== strpos( $hook, Tunet_Core_Demo::MENU_SLUG ) );

		if ( ! $is_settings && ! $is_tools && ! $is_demo ) {
			return;
		}

		if ( $is_settings ) {
			wp_enqueue_media();
		}

		wp_enqueue_style( 'tunet-core-admin', TUNET_CORE_URL . 'admin/admin.css', array(), (string) filemtime( TUNET_CORE_PATH . 'admin/admin.css' ) );
		wp_enqueue_script( 'tunet-core-admin', TUNET_CORE_URL . 'admin/admin.js', array(), (string) filemtime( TUNET_CORE_PATH . 'admin/admin.js' ), true );

		wp_localize_script(
			'tunet-core-admin',
			'tunetCoreAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'homeUrl' => home_url( '/' ),
				'nonce'   => wp_create_nonce( 'tunet_demo' ),
				'i18n'    => array(
					'importing'    => __( 'Importing…', 'tunet-core' ),
					'done'         => __( 'Demo imported.', 'tunet-core' ),
					'rollback'     => __( 'Import undone.', 'tunet-core' ),
					'error'        => __( 'An error occurred.', 'tunet-core' ),
					'chooseLogo'   => __( 'Select logo', 'tunet-core' ),
					'useLogo'      => __( 'Use this logo', 'tunet-core' ),
					'pluginsTitle' => __( 'Recommended plugins', 'tunet-core' ),
					'installAct'   => __( 'Install & activate', 'tunet-core' ),
					'installing'   => __( 'Installing…', 'tunet-core' ),
					'activate'     => __( 'Activate', 'tunet-core' ),
					'active'       => __( 'Active', 'tunet-core' ),
					'required'     => __( 'Required', 'tunet-core' ),
					'continue'     => __( 'Continue', 'tunet-core' ),
					'optional'     => __( 'Optional', 'tunet-core' ),
					'pluginsReady' => __( 'All set — continue to the import.', 'tunet-core' ),
					'noPlugins'    => __( 'No extra plugins needed for this demo — continue to the import.', 'tunet-core' ),
					'viewSite'     => __( 'View site', 'tunet-core' ),
					'installManually' => __( 'Install manually', 'tunet-core' ),
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Render: Settings page (stacked sections)
	 * ------------------------------------------------------------------ */

	/**
	 * Theme palette (for color placeholders).
	 *
	 * @return array slug => hex
	 */
	private function theme_palette() {
		$out  = array();
		$pal  = wp_get_global_settings( array( 'color', 'palette' ) );
		$list = isset( $pal['theme'] ) ? $pal['theme'] : ( is_array( $pal ) ? $pal : array() );
		if ( is_array( $list ) ) {
			foreach ( $list as $c ) {
				if ( isset( $c['slug'], $c['color'] ) ) {
					$out[ $c['slug'] ] = $c['color'];
				}
			}
		}
		return $out;
	}

	/**
	 * Render the Settings page (sections: Logos → Branding → General).
	 */
	public function render_settings_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$s = self::get_settings();
		// No es procesado de formulario: es la bandera que admin-post.php nos devuelve
		// por redirección para saber QUÉ aviso pintar. No cambia nada, la pantalla ya
		// está detrás de current_user_can(), y el valor pasa por sanitize_key() y
		// luego por un lookup contra una lista cerrada en notice_text().
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice   = isset( $_GET['tunet_notice'] ) ? sanitize_key( wp_unslash( $_GET['tunet_notice'] ) ) : '';
		$post_url = admin_url( 'admin-post.php' );
		$palette  = $this->theme_palette();
		?>
		<div class="wrap tunet-admin">
			<h1><?php esc_html_e( 'Tunet Core', 'tunet-core' ); ?></h1>
			<p class="description"><?php esc_html_e( 'The theme provides the defaults. Anything left empty uses the theme; anything you set overrides it globally (--tnt-* tokens).', 'tunet-core' ); ?></p>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $this->notice_text( $notice ) ); ?></p></div>
			<?php endif; ?>

			<?php if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) : ?>
				<div class="card tunet-section-card">
					<h2><?php esc_html_e( 'Appearance & styles', 'tunet-core' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Switch the active theme’s look — its color style variations (e.g. light / dark) — in the Site Editor. The variations ship with the theme; the editor is where you preview and apply them.', 'tunet-core' ); ?></p>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'site-editor.php?path=%2Fwp_global_styles' ) ); ?>">
							<?php esc_html_e( 'Open Styles in the Site Editor', 'tunet-core' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $post_url ); ?>">
				<input type="hidden" name="action" value="tunet_save_settings" />
				<?php wp_nonce_field( 'tunet_save_settings' ); ?>

				<div class="card tunet-section-card">
					<h2><?php esc_html_e( 'Brand logos', 'tunet-core' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Brand assets that persist across themes. The main logo syncs with the native Site Logo. Upload high resolution (retina is automatic).', 'tunet-core' ); ?></p>
					<?php /* El campo ya se llamaba "dark backgrounds" pero nada lo aplicaba solo: el logo alternativo se quedaba sin usar y el header oscuro mostraba el oscuro. Ahora sí conmuta, y conviene decirlo aquí. */ ?>
					<p class="description"><?php esc_html_e( 'With the Brand block set to Automatic, the alternative logo is used on dark palettes and dark style variations, and the main one everywhere else. Leave it empty to always use the main logo.', 'tunet-core' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->row_logo( __( 'Main logo', 'tunet-core' ), 'logo_main_id', (int) $s['logo_main_id'] );
						$this->row_logo( __( 'Alternative logo (dark backgrounds)', 'tunet-core' ), 'logo_alt_id', (int) $s['logo_alt_id'], true );
						?>
					</table>
				</div>

				<div class="card tunet-section-card">
					<h2><?php esc_html_e( 'Branding', 'tunet-core' ); ?></h2>

					<h3><?php esc_html_e( 'Typography (Google Fonts)', 'tunet-core' ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						$this->row_font( __( 'Display', 'tunet-core' ), 'font_display', $s['font_display'], self::fonts_text() );
						$this->row_font( __( 'Body', 'tunet-core' ), 'font_body', $s['font_body'], self::fonts_text() );
						$this->row_font( __( 'Monospace', 'tunet-core' ), 'font_mono', $s['font_mono'], self::fonts_mono() );
						$this->row_select( __( 'Base size', 'tunet-core' ), 'text_base', $s['text_base'], array( '' => __( 'Theme default', 'tunet-core' ), 'sm' => __( 'Compact', 'tunet-core' ), 'lg' => __( 'Large', 'tunet-core' ) ) );
						?>
					</table>

					<h3><?php esc_html_e( 'Brand colors', 'tunet-core' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Empty = the theme decides. A value here overrides the theme token site-wide, in every style variation.', 'tunet-core' ); ?>
					</p>
					<?php
					/*
					 * Aviso cuando hay overrides activos. Estos colores se emiten como
					 * --tnt-color-* en :root DESPUES del tokens.css del theme, asi que
					 * GANAN a las style variations (que solo redefinen los presets de
					 * WP). Un valor olvidado aqui deja la variacion "sin efecto" —y si
					 * se pisa el fondo sin pisar el texto, el par puede quedar ilegible.
					 * Se avisa en vez de prohibirlo: el override explicito es la funcion.
					 */
					$brand_keys   = array(
						'brand_primary'  => __( 'Primary', 'tunet-core' ),
						'brand_accent'   => __( 'Accent', 'tunet-core' ),
						'brand_accent_2' => __( 'Accent 2', 'tunet-core' ),
						'brand_bg'       => __( 'Background', 'tunet-core' ),
						'brand_text'     => __( 'Text', 'tunet-core' ),
					);
					$brand_active = array();
					foreach ( $brand_keys as $bk => $blabel ) {
						if ( ! empty( $s[ $bk ] ) ) {
							$brand_active[] = $blabel . ' (' . $s[ $bk ] . ')';
						}
					}
					if ( $brand_active ) :
						?>
						<div class="notice notice-warning inline tunet-brand-warning">
							<p>
								<strong><?php esc_html_e( 'Brand colors are overriding this theme.', 'tunet-core' ); ?></strong>
								<?php
								printf(
									/* translators: %s: comma-separated list of overridden color names with their hex value. */
									esc_html__( 'Active: %s. While these are set, switching the style variation will not change them.', 'tunet-core' ),
									esc_html( implode( ', ', $brand_active ) )
								);
								?>
							</p>
							<?php if ( ! empty( $s['brand_bg'] ) && empty( $s['brand_text'] ) ) : ?>
								<p>
									<?php esc_html_e( 'You set a background but not a text color: on a theme whose text color changes with the variation, that pair can end up unreadable.', 'tunet-core' ); ?>
								</p>
							<?php endif; ?>
							<p>
								<button type="button" class="button" id="tunet-brand-clear-all">
									<?php esc_html_e( 'Clear all brand colors', 'tunet-core' ); ?>
								</button>
								<span class="description"><?php esc_html_e( 'Then press Save changes.', 'tunet-core' ); ?></span>
							</p>
						</div>
						<?php
					endif;
					?>
					<table class="form-table tunet-brand-colors" role="presentation">
						<?php
						$this->row_color( __( 'Primary', 'tunet-core' ), 'brand_primary', $s['brand_primary'], $palette['primary'] ?? '' );
						$this->row_color( __( 'Accent', 'tunet-core' ), 'brand_accent', $s['brand_accent'], $palette['accent'] ?? '' );
						$this->row_color( __( 'Accent 2', 'tunet-core' ), 'brand_accent_2', $s['brand_accent_2'], $palette['accent-2'] ?? '' );
						$this->row_color( __( 'Background', 'tunet-core' ), 'brand_bg', $s['brand_bg'], $palette['bg'] ?? '' );
						$this->row_color( __( 'Text', 'tunet-core' ), 'brand_text', $s['brand_text'], $palette['text'] ?? '' );
						?>
					</table>

					<h3><?php esc_html_e( 'Shape', 'tunet-core' ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						$this->row_number(
							__( 'Corner radius (px)', 'tunet-core' ),
							'radius',
							$s['radius'],
							__( 'Empty = theme. Rounding for Tunet blocks/components that use the radius tokens. 0 = sharp.', 'tunet-core' ),
							64
						);
						?>
					</table>

					<h3><?php esc_html_e( 'Motion', 'tunet-core' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Speed of all Tunet effects (entrance, hover, scroll). Subtle = faster and tighter; Bold = slower and more dramatic.', 'tunet-core' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->row_select( __( 'Motion intensity', 'tunet-core' ), 'motion', $s['motion'], array( '' => __( 'Theme default', 'tunet-core' ), 'subtle' => __( 'Subtle (fast)', 'tunet-core' ), 'bold' => __( 'Bold (slow)', 'tunet-core' ) ) );
						?>
					</table>
				</div>

				<div class="card tunet-section-card">
					<h2><?php esc_html_e( 'Content layout', 'tunet-core' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Show a sidebar — or run full width — on blog posts and archives. Pages are not affected here: they use per-page templates (e.g. Narrow, With sidebar) chosen in the page editor.', 'tunet-core' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$layout_opts = array(
							''        => __( 'Full width (no sidebar)', 'tunet-core' ),
							'sidebar' => __( 'With sidebar', 'tunet-core' ),
						);
						$this->row_select( __( 'Single posts', 'tunet-core' ), 'layout_post_single', $s['layout_post_single'], $layout_opts );
						$this->row_select( __( 'Blog & archives', 'tunet-core' ), 'layout_archive', $s['layout_archive'], $layout_opts );
						?>
					</table>
					<p class="description"><?php esc_html_e( 'Themes that ship a sidebar area react automatically. A theme without one simply stays full width.', 'tunet-core' ); ?></p>
				</div>

				<div class="card tunet-section-card">
					<h2><?php esc_html_e( 'General', 'tunet-core' ); ?></h2>
					<p><label>
						<input type="checkbox" name="effects_enabled" value="1" <?php checked( ! empty( $s['effects_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable the effects engine (tf*) on the front-end', 'tunet-core' ); ?>
					</label></p>
					<p class="description"><?php esc_html_e( 'When disabled, the runtime is not loaded and blocks render clean (useful for performance debugging).', 'tunet-core' ); ?></p>
				</div>

				<?php submit_button( __( 'Save settings', 'tunet-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the Tools page (import/export + demo).
	 */
	public function render_tools_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		// Misma bandera de aviso por redirección que en la pantalla de ajustes: no
		// procesa nada, la página ya exigió la capacidad, y el valor se sanea y se
		// resuelve contra una lista cerrada.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice   = isset( $_GET['tunet_notice'] ) ? sanitize_key( wp_unslash( $_GET['tunet_notice'] ) ) : '';
		$post_url = admin_url( 'admin-post.php' );
		?>
		<div class="wrap tunet-admin">
			<h1><?php esc_html_e( 'Tunet Core · Tools', 'tunet-core' ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $this->notice_text( $notice ) ); ?></p></div>
			<?php endif; ?>

			<div class="tunet-admin__grid">
				<div class="card">
					<h2><?php esc_html_e( 'Export', 'tunet-core' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Download the current settings as a JSON file.', 'tunet-core' ); ?></p>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'tunet_export', $post_url ), 'tunet_export' ) ); ?>">
							<span class="dashicons dashicons-download" aria-hidden="true" style="vertical-align:text-bottom"></span>
							<?php esc_html_e( 'Export settings (.json)', 'tunet-core' ); ?>
						</a>
					</p>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Import', 'tunet-core' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Restore settings from a previously exported JSON file.', 'tunet-core' ); ?></p>
					<form method="post" action="<?php echo esc_url( $post_url ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="tunet_import" />
						<?php wp_nonce_field( 'tunet_import' ); ?>
						<p><input type="file" name="tunet_import_file" accept="application/json,.json" required /></p>
						<?php submit_button( __( 'Import settings', 'tunet-core' ), 'secondary' ); ?>
					</form>
				</div>

			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Row render helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Font selector row.
	 *
	 * @param string $label   Label.
	 * @param string $name    Field name.
	 * @param string $current Value.
	 * @param array  $fonts   family => weights map.
	 */
	private function row_font( $label, $name, $current, $fonts ) {
		$options = array( '' => __( 'Theme default', 'tunet-core' ) );
		foreach ( $fonts as $family => $weights ) {
			$options[ $family ] = $family;
		}
		$this->row_select( $label, $name, $current, $options );
	}

	/**
	 * <select> row.
	 *
	 * @param string $label   Label.
	 * @param string $name    Name.
	 * @param string $current Value.
	 * @param array  $options value => label.
	 */
	private function row_select( $label, $name, $current, $options ) {
		?>
		<tr>
			<th scope="row"><label for="tunet-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="tunet-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $options as $value => $opt_label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $opt_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Number input row.
	 *
	 * @param string $label   Label.
	 * @param string $name    Name.
	 * @param string $current Value ('' allowed).
	 * @param string $help    Help text.
	 * @param int    $max     Max value.
	 */
	private function row_number( $label, $name, $current, $help = '', $max = 100 ) {
		?>
		<tr>
			<th scope="row"><label for="tunet-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" id="tunet-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" min="0" max="<?php echo esc_attr( $max ); ?>" step="1" class="small-text" />
				<?php if ( $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Color row (swatch + text + reset; empty = theme).
	 *
	 * @param string $label       Label.
	 * @param string $name        Name.
	 * @param string $current     Value.
	 * @param string $placeholder Theme hex.
	 */
	private function row_color( $label, $name, $current, $placeholder ) {
		$current     = $current ? $current : '';
		$placeholder = $placeholder ? $placeholder : '#000000';
		?>
		<tr>
			<th scope="row"><label for="tunet-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td class="tunet-color-row">
				<input type="color" value="<?php echo esc_attr( $current ? $current : $placeholder ); ?>" data-target="tunet-<?php echo esc_attr( $name ); ?>" class="tunet-color-swatch" aria-hidden="true" tabindex="-1" />
				<input type="text" id="tunet-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" placeholder="<?php echo esc_attr( $placeholder . ' (theme)' ); ?>" class="tunet-color-text regular-text" pattern="#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" />
				<button type="button" class="button tunet-icon-btn tunet-color-clear" data-target="tunet-<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Reset to theme color', 'tunet-core' ); ?>" aria-label="<?php esc_attr_e( 'Reset to theme color', 'tunet-core' ); ?>">
					<span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Logo row (preview + media picker + remove).
	 *
	 * @param string $label   Label.
	 * @param string $name    Field name (hidden, attachment ID).
	 * @param int    $current Current attachment ID.
	 * @param bool   $dark    Logo meant for dark backgrounds: preview it on a dark
	 *                        checkerboard. A light logo on the light board reads as
	 *                        an empty box — parece que la subida ha fallado.
	 */
	private function row_logo( $label, $name, $current, $dark = false ) {
		$img = $current ? wp_get_attachment_image_url( $current, 'medium' ) : '';
		$id  = 'tunet-' . $name;
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<div class="tunet-logo-field">
					<div class="tunet-logo-preview<?php echo $dark ? ' tunet-logo-preview--dark' : ''; ?>" data-for="<?php echo esc_attr( $id ); ?>">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="" />
						<?php endif; ?>
					</div>
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" />
					<p class="tunet-logo-actions">
						<button type="button" class="button tunet-logo-pick" data-target="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Choose / change', 'tunet-core' ); ?></button>
						<button type="button" class="button tunet-icon-btn tunet-logo-remove" data-target="<?php echo esc_attr( $id ); ?>" title="<?php esc_attr_e( 'Remove logo', 'tunet-core' ); ?>" aria-label="<?php esc_attr_e( 'Remove logo', 'tunet-core' ); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Notice text.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private function notice_text( $key ) {
		switch ( $key ) {
			case 'saved':
				return __( 'Settings saved.', 'tunet-core' );
			case 'imported':
				return __( 'Settings imported successfully.', 'tunet-core' );
			case 'import_error':
				return __( 'The file is not a valid settings JSON.', 'tunet-core' );
			default:
				return '';
		}
	}

	/* ---------------------------------------------------------------------
	 * Handlers
	 * ------------------------------------------------------------------ */

	/**
	 * Sanitize settings from $_POST or import.
	 *
	 * @param array $src Data.
	 * @return array
	 */
	private function sanitize_settings( $src ) {
		$text = self::fonts_text();
		$mono = self::fonts_mono();

		$font_text = function ( $v ) use ( $text ) {
			return ( is_string( $v ) && isset( $text[ $v ] ) ) ? $v : '';
		};
		$font_mono = function ( $v ) use ( $mono ) {
			return ( is_string( $v ) && isset( $mono[ $v ] ) ) ? $v : '';
		};
		$enum = function ( $v, $allowed ) {
			return ( is_string( $v ) && in_array( $v, $allowed, true ) ) ? $v : '';
		};
		$color = function ( $v ) {
			if ( ! is_string( $v ) ) {
				return '';
			}
			$v = trim( $v );
			if ( '' !== $v && '#' !== $v[0] ) {
				$v = '#' . $v;
			}
			return (string) sanitize_hex_color( $v );
		};

		return array(
			'effects_enabled' => ! empty( $src['effects_enabled'] ),
			'font_display'    => $font_text( $src['font_display'] ?? '' ),
			'font_body'       => $font_text( $src['font_body'] ?? '' ),
			'font_mono'       => $font_mono( $src['font_mono'] ?? '' ),
			'text_base'       => $enum( $src['text_base'] ?? '', array( 'sm', 'lg' ) ),
			'brand_primary'   => $color( $src['brand_primary'] ?? '' ),
			'brand_accent'    => $color( $src['brand_accent'] ?? '' ),
			'brand_accent_2'  => $color( $src['brand_accent_2'] ?? '' ),
			'brand_bg'        => $color( $src['brand_bg'] ?? '' ),
			'brand_text'      => $color( $src['brand_text'] ?? '' ),
			'radius'          => ( isset( $src['radius'] ) && '' !== $src['radius'] && is_numeric( $src['radius'] ) ) ? (string) max( 0, min( 64, (int) $src['radius'] ) ) : '',
			'motion'          => $enum( $src['motion'] ?? '', array( 'subtle', 'bold' ) ),
			'logo_main_id'    => isset( $src['logo_main_id'] ) ? absint( $src['logo_main_id'] ) : 0,
			'logo_alt_id'     => isset( $src['logo_alt_id'] ) ? absint( $src['logo_alt_id'] ) : 0,
			'layout_post_single' => $enum( $src['layout_post_single'] ?? '', array( 'sidebar' ) ),
			'layout_archive'     => $enum( $src['layout_archive'] ?? '', array( 'sidebar' ) ),
		);
	}

	/**
	 * Save settings (and sync the main logo with custom_logo).
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tunet-core' ) );
		}
		check_admin_referer( 'tunet_save_settings' );

		$clean = $this->sanitize_settings( wp_unslash( $_POST ) );
		update_option( self::OPTION, $clean );
		$this->sync_custom_logo( $clean );

		wp_safe_redirect( add_query_arg( 'tunet_notice', 'saved', admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	/**
	 * Keep the native custom_logo (Site Logo block) in sync with the engine's main
	 * logo setting. Called from both Save and Import so an imported logo ID also
	 * propagates. Guards for a missing/invalid attachment (an imported ID may not
	 * exist on the target site).
	 *
	 * @param array $settings Sanitized settings array.
	 */
	private function sync_custom_logo( $settings ) {
		$id = isset( $settings['logo_main_id'] ) ? (int) $settings['logo_main_id'] : 0;
		if ( $id && wp_attachment_is_image( $id ) ) {
			set_theme_mod( 'custom_logo', $id );
		} else {
			remove_theme_mod( 'custom_logo' );
		}
	}

	/**
	 * Export settings (JSON download).
	 */
	public function handle_export() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tunet-core' ) );
		}
		check_admin_referer( 'tunet_export' );

		$payload = array(
			'_type'    => 'tunet-core-settings',
			'_version' => TUNET_CORE_VERSION,
			'settings' => self::get_settings(),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=tunet-core-settings.json' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Import settings from a JSON file.
	 */
	public function handle_import() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tunet-core' ) );
		}
		check_admin_referer( 'tunet_import' );

		$notice = 'import_error';

		// El saneado real de una subida es is_uploaded_file(): confirma que la ruta
		// es la que PHP acaba de crear y no una que venga del cliente. Aun así se
		// pasa por sanitize_text_field antes de tocarla, porque un tmp_name no
		// contiene nada que ese filtro pueda estropear y así la comprobación queda
		// explícita en el código en vez de argumentada en un phpcs:ignore.
		$tmp = isset( $_FILES['tunet_import_file']['tmp_name'] )
			? sanitize_text_field( wp_unslash( $_FILES['tunet_import_file']['tmp_name'] ) )
			: '';

		if ( '' !== $tmp && is_uploaded_file( $tmp ) ) {
			$raw  = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- lectura de un fichero local recién subido, no remota.
			$data = json_decode( $raw, true );
			if ( is_array( $data ) && isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
				$clean = $this->sanitize_settings( $data['settings'] );
				update_option( self::OPTION, $clean );
				$this->sync_custom_logo( $clean );
				$notice = 'imported';
			}
		}

		wp_safe_redirect( add_query_arg( 'tunet_notice', $notice, admin_url( 'admin.php?page=' . self::TOOLS_SLUG ) ) );
		exit;
	}

}
