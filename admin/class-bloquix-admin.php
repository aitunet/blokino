<?php
/**
 * Engine admin: settings (stacked sections), global branding overrides, brand
 * logos, and a separate Tools page (import/export).
 *
 * (CLAUDE.md §4.4.) OVERRIDE MODEL: the theme is the source of truth for the
 * --tnt-* tokens; this panel only stores overrides, which the runtime injects
 * as :root{--tnt-*}. Fonts load from Google Fonts (CDN). Logos are engine-level
 * brand assets (they persist across theme switches); the theme decides where to
 * place them (bloquix/brand block or the native Site Logo block).
 *
 * Reads/builders are STATIC so the runtime can use them on the front-end.
 *
 * Default UI language is English; strings are translatable (text domain 'bloquix').
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Engine administration.
 */
class Bloquix_Admin {

	const OPTION     = 'bloquix_settings';
	const MENU_SLUG  = 'bloquix';          // top-level entry = the Get started screen
	const SETTINGS_SLUG = 'bloquix-settings';
	const TOOLS_SLUG  = 'bloquix-tools';
	const CAPABILITY  = 'manage_options';

	/* ---------------------------------------------------------------------
	 * Catalogs (curated Google Fonts)
	 * ------------------------------------------------------------------ */

	/** Sans/serif fonts (display/body) → weights. Themes/plugins extend it with the bloquix_fonts_text filter. */
	public static function fonts_text() {
		return apply_filters( 'bloquix_fonts_text', array(
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
		) );
	}

	/** Monospace fonts → weights. */
	public static function fonts_mono() {
		return apply_filters( 'bloquix_fonts_mono', array(
			'JetBrains Mono' => '400;500;700',
			'Space Mono'     => '400;700',
			'IBM Plex Mono'  => '400;500;600',
			'Fira Code'      => '400;500;600',
			'Roboto Mono'    => '400;500;700',
		) );
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
			'brand_ink'       => '',
			'brand_on_ink'    => '',
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

		/*
		 * Each brand color overrides its --tnt-color-* token at :root (after the
		 * theme's tokens.css, so it wins over style variations). It is ALSO emitted
		 * as --tnt-brand-<name>, set only while overridden: a theme that locks a
		 * band's palette to fixed tokens (§13.1 — dark bands over photos keep their
		 * own colors so a light variation never makes them unreadable) can let an
		 * explicit brand choice through where it makes sense, e.g.
		 * --tnt-color-ink-primary: var( --tnt-brand-primary, #CDB891 ).
		 * "Dark bands" (ink / on-ink) are the two tokens every band lock resolves
		 * to, so setting them recolors those bands directly.
		 */
		$colors = array(
			'brand_primary'  => array( '--tnt-color-primary', '--tnt-brand-primary' ),
			'brand_accent'   => array( '--tnt-color-accent', '--tnt-brand-accent' ),
			'brand_accent_2' => array( '--tnt-color-accent-2', '--tnt-brand-accent-2' ),
			'brand_bg'       => array( '--tnt-color-bg', '--tnt-brand-bg' ),
			'brand_text'     => array( '--tnt-color-text', '--tnt-brand-text' ),
			'brand_ink'      => array( '--tnt-color-ink', '--tnt-brand-ink' ),
			'brand_on_ink'   => array( '--tnt-color-on-ink', '--tnt-brand-on-ink' ),
		);
		foreach ( $colors as $key => $vars ) {
			$hex = isset( $s[ $key ] ) ? sanitize_hex_color( $s[ $key ] ) : '';
			if ( $hex ) {
				$d[] = $vars[0] . ':' . $hex;
				$d[] = $vars[1] . ':' . $hex;
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
		add_action( 'admin_menu', array( $this, 'fire_menu_hook' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'menu_icon_style' ) );
		add_action( 'admin_post_bloquix_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_bloquix_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_bloquix_import', array( $this, 'handle_import' ) );
	}

	/**
	 * La marca: "tC · corte" (elegida 2026-09-14).
	 *
	 * La t del logo cortada por una diagonal paralela al bisel del asta: la parte de
	 * arriba a la izquierda (asta, brazo izquierdo, medio cruce) es la t; la de abajo a
	 * la derecha (brazo derecho, medio cruce, palo, gancho) es la C que la letra ya
	 * contenía. El hueco entre las dos es VACÍO: la diagonal desplazada ±4,5 y cada
	 * forma recortada por su desplazamiento (sin línea encima, sin trazos).
	 *
	 * Dos paths en el espacio de la letra (bbox 66–194 × 42–216, viewBox cuadrado
	 * "43 42 174 174"). Un solo sitio con la geometría para que el data-URI del menú y
	 * las máscaras de menu_icon_style() no puedan divergir; la fuente documentada está
	 * en admin/img/icon-bloquix.svg y el generador de todos los assets en
	 * wporg-assets/build-icon.mjs. Va en línea porque add_menu_page() necesita el valor
	 * al registrar el menú y leer el archivo en cada carga del admin sería una lectura
	 * de disco por página para 300 bytes.
	 */
	const ICON_VIEWBOX = '43 42 174 174';
	const ICON_PATH_T  = 'M104 60 L154 42 V86.1 L97 134 H66 V92 H104 Z';
	const ICON_PATH_C  = 'M161 92 H190 V134 H154 V160 C154 176 164 182 180 182 H194 V216 H168 C126 216 104 194 104 158 V139.9 Z';
	const ICON_SIGNAL  = '#00BBDB';

	/**
	 * La marca como data-URI para add_menu_page(): las dos partes en blanco.
	 *
	 * Es el estado de reposo Y el fallback: un icono pasado por data-URI se pinta como
	 * background-image y WP no lo recolorea. En un navegador con máscaras CSS,
	 * menu_icon_style() lo reemplaza por las dos máscaras (t blanca, C blanca que pasa a
	 * Signal con el ratón encima o con la pantalla activa); sin máscaras, se queda este.
	 *
	 * @return string
	 */
	private static function menu_icon_data_uri() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . self::ICON_VIEWBOX . '">'
			. '<path fill="#fff" d="' . self::ICON_PATH_C . '"/>'
			. '<path fill="#fff" d="' . self::ICON_PATH_T . '"/>'
			. '</svg>';
		// base64 y no percent-encoding: es la convención de WP para iconos de menú y
		// evita tener que acertar con el escapado de #, <, > y las comillas.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- data-URI, no ofuscación.
	}

	/**
	 * Una parte de la marca como máscara CSS (url data-URI, percent-encoded).
	 *
	 * @param string $d Path de la parte.
	 * @return string
	 */
	private static function menu_icon_mask( $d ) {
		return "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='" . self::ICON_VIEWBOX . "'%3E%3Cpath fill='%23000' d='" . $d . "'/%3E%3C/svg%3E\")";
	}

	/**
	 * Pinta el icono del menú en dos tonos.
	 *
	 * La t siempre blanca; la C blanca en reposo y Signal cuando el ítem tiene el ratón
	 * encima, el foco, o es la pantalla activa (decisión del usuario, 2026-09-14). Un
	 * icono por data-URI no puede hacer eso (WP no lo recolorea), así que cada parte
	 * es un pseudo-elemento con su máscara y su background-color. Va detrás de un
	 * @supports y solo entonces se oculta el background, de modo que un navegador sin
	 * máscaras conserva el icono blanco de menu_icon_data_uri() en lugar de quedarse sin
	 * ninguno.
	 *
	 * Sin margin vertical: WP ya centra los iconos del menú con padding 7px 0 sobre
	 * 34px (7+20+7); un margen propio se sumaría y bajaría la marca respecto a los
	 * dashicons vecinos (medido en la versión anterior).
	 */
	public function menu_icon_style() {
		$mask_t = self::menu_icon_mask( self::ICON_PATH_T );
		$mask_c = self::menu_icon_mask( self::ICON_PATH_C );
		$item   = '#adminmenu #toplevel_page_' . self::MENU_SLUG;
		$img    = $item . ' .wp-menu-image';
		$lit    = $item . ':hover .wp-menu-image::after, ' . $item . '.current .wp-menu-image::after, ' . $item . '.wp-has-current-submenu .wp-menu-image::after, ' . $item . ' a:focus .wp-menu-image::after';
		?>
		<style id="bloquix-menu-icon">
			@supports ((-webkit-mask-image: <?php echo $mask_t; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>) or (mask-image: <?php echo $mask_t; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>)) {
				<?php echo esc_html( $img ); ?> { position: relative; background-image: none !important; }
				<?php echo esc_html( $img ); ?>::before,
				<?php echo esc_html( $img ); ?>::after {
					content: "";
					position: absolute;
					inset: 0;
					background-color: #fff;
					-webkit-mask-repeat: no-repeat;
					mask-repeat: no-repeat;
					-webkit-mask-position: center;
					mask-position: center;
					-webkit-mask-size: 20px 20px;
					mask-size: 20px 20px;
					transition: background-color .15s ease;
				}
				<?php echo esc_html( $img ); ?>::before {
					-webkit-mask-image: <?php echo $mask_t; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>;
					mask-image: <?php echo $mask_t; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>;
				}
				<?php echo esc_html( $img ); ?>::after {
					-webkit-mask-image: <?php echo $mask_c; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>;
					mask-image: <?php echo $mask_c; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal del motor. ?>;
				}
				<?php echo esc_html( $lit ); ?> { background-color: <?php echo esc_html( self::ICON_SIGNAL ); ?>; }
			}
		</style>
		<?php
	}

	/**
	 * Register menu + submenus + Appearance link.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'BloqUIX', 'bloquix' ),
			__( 'BloqUIX', 'bloquix' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( 'Bloquix_Welcome', 'render_page' ),
			self::menu_icon_data_uri(),
			59
		);

		// The parent's own entry is the first submenu: Get started.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Get started', 'bloquix' ),
			__( 'Get started', 'bloquix' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( 'Bloquix_Welcome', 'render_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'bloquix' ),
			__( 'Settings', 'bloquix' ),
			self::CAPABILITY,
			self::SETTINGS_SLUG,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tools', 'bloquix' ),
			__( 'Tools', 'bloquix' ),
			self::CAPABILITY,
			self::TOOLS_SLUG,
			array( $this, 'render_tools_page' )
		);

		// Direct link under Appearance → settings page.
		add_submenu_page(
			'themes.php',
			__( 'BloqUIX', 'bloquix' ),
			__( 'BloqUIX', 'bloquix' ),
			self::CAPABILITY,
			'admin.php?page=' . self::SETTINGS_SLUG
		);
	}

	/**
	 * Fires after BloqUIX registered its own submenus. Premium themes hook
	 * here to add screens (e.g. License) under the BloqUIX menu.
	 *
	 * Hooked at `admin_menu` priority 12 — after Settings/Tools (priority 10,
	 * `register_menu`) and Demo (priority 11) but before Themes (priority 13) —
	 * so the menu order stays Settings · Tools · Demo · (theme screens) · Themes.
	 */
	public function fire_menu_hook() {
		/**
		 * Fires after BloqUIX registered its own submenus. Premium themes hook
		 * here to add screens (e.g. License) under the BloqUIX menu.
		 *
		 * @param string $parent_slug The BloqUIX menu slug.
		 */
		do_action( 'bloquix_admin_menu', self::MENU_SLUG );
	}

	/**
	 * Enqueue assets on the plugin pages.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$is_welcome  = ( 'toplevel_page_' . self::MENU_SLUG === $hook );
		$is_settings = ( false !== strpos( $hook, self::SETTINGS_SLUG ) );
		$is_tools    = ( false !== strpos( $hook, self::TOOLS_SLUG ) );
		$is_demo     = ( false !== strpos( $hook, Bloquix_Demo::MENU_SLUG ) );
		$is_themes   = ( false !== strpos( $hook, Bloquix_Themes::MENU_SLUG ) );

		if ( ! $is_welcome && ! $is_settings && ! $is_tools && ! $is_demo && ! $is_themes ) {
			return;
		}

		if ( $is_settings ) {
			wp_enqueue_media();
		}

		wp_enqueue_style( 'bloquix-admin', BLOQUIX_URL . 'admin/admin.css', array(), (string) filemtime( BLOQUIX_PATH . 'admin/admin.css' ) );
		wp_enqueue_script( 'bloquix-admin', BLOQUIX_URL . 'admin/admin.js', array(), (string) filemtime( BLOQUIX_PATH . 'admin/admin.js' ), true );

		wp_localize_script(
			'bloquix-admin',
			'bloquixAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'homeUrl' => home_url( '/' ),
				'nonce'   => wp_create_nonce( 'bloquix_demo' ),
				'i18n'    => array(
					'importing'    => __( 'Importing…', 'bloquix' ),
					'done'         => __( 'Demo imported.', 'bloquix' ),
					'rollback'     => __( 'Import undone.', 'bloquix' ),
					'error'        => __( 'An error occurred.', 'bloquix' ),
					'chooseLogo'   => __( 'Select logo', 'bloquix' ),
					'useLogo'      => __( 'Use this logo', 'bloquix' ),
					'pluginsTitle' => __( 'Recommended plugins', 'bloquix' ),
					'installAct'   => __( 'Install & activate', 'bloquix' ),
					'installing'   => __( 'Installing…', 'bloquix' ),
					'activate'     => __( 'Activate', 'bloquix' ),
					'active'       => __( 'Active', 'bloquix' ),
					'required'     => __( 'Required', 'bloquix' ),
					'continue'     => __( 'Continue', 'bloquix' ),
					'optional'     => __( 'Optional', 'bloquix' ),
					'pluginsReady' => __( 'All set — continue to the import.', 'bloquix' ),
					'noPlugins'    => __( 'No extra plugins needed for this demo — continue to the import.', 'bloquix' ),
					'viewSite'     => __( 'View site', 'bloquix' ),
					'installManually' => __( 'Install manually', 'bloquix' ),
					/* translators: %s: what the server answered, e.g. "HTTP 504". */
					'serverError'  => __( 'The server did not answer with JSON (%s). The request was probably cut short by a time limit.', 'bloquix' ),
					'retrying'     => __( 'Connection hiccup — retrying…', 'bloquix' ),
					'retryHint'    => __( 'Click Retry to resume from this step.', 'bloquix' ),
					'retry'        => __( 'Retry', 'bloquix' ),
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
	 * Fixed --tnt-color-<name> values from the active theme's tokens.css (the
	 * tokens theme.json's palette does not carry, such as ink / on-ink), used as
	 * placeholders. Child first, then parent; empty when the theme ships no
	 * tokens.css or the token is not a literal hex.
	 *
	 * @param string[] $names Token names without the --tnt-color- prefix.
	 * @return array<string,string> name => #hex.
	 */
	private function theme_tokens( $names ) {
		$out = array();
		$css = '';
		foreach ( array( get_stylesheet_directory(), get_template_directory() ) as $dir ) {
			$file = $dir . '/assets/css/tokens.css';
			if ( file_exists( $file ) ) {
				$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- theme file on disk.
				break;
			}
		}
		if ( '' === $css ) {
			return $out;
		}
		foreach ( $names as $name ) {
			if ( preg_match( '/--tnt-color-' . preg_quote( $name, '/' ) . '\s*:\s*(#[0-9a-fA-F]{3,8})\s*;/', $css, $m ) ) {
				$out[ $name ] = sanitize_hex_color( $m[1] ) ? $m[1] : '';
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
		$notice   = isset( $_GET['bloquix_notice'] ) ? sanitize_key( wp_unslash( $_GET['bloquix_notice'] ) ) : '';
		$post_url = admin_url( 'admin-post.php' );
		$palette  = $this->theme_palette();
		?>
		<div class="wrap bloquix-admin">
			<h1><?php esc_html_e( 'BloqUIX', 'bloquix' ); ?></h1>
			<p class="description"><?php esc_html_e( 'The theme provides the defaults. Anything left empty uses the theme; anything you set overrides it globally (--tnt-* tokens).', 'bloquix' ); ?></p>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $this->notice_text( $notice ) ); ?></p></div>
			<?php endif; ?>

			<?php if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) : ?>
				<div class="card bloquix-section-card">
					<h2><?php esc_html_e( 'Appearance & styles', 'bloquix' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Switch the active theme’s look — its color style variations (e.g. light / dark) — in the Site Editor. The variations ship with the theme; the editor is where you preview and apply them.', 'bloquix' ); ?></p>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'site-editor.php?path=%2Fwp_global_styles' ) ); ?>">
							<?php esc_html_e( 'Open Styles in the Site Editor', 'bloquix' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $post_url ); ?>">
				<input type="hidden" name="action" value="bloquix_save_settings" />
				<?php wp_nonce_field( 'bloquix_save_settings' ); ?>

				<div class="card bloquix-section-card">
					<h2><?php esc_html_e( 'Brand logos', 'bloquix' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Brand assets that persist across themes. The main logo syncs with the native Site Logo. Upload high resolution (retina is automatic).', 'bloquix' ); ?></p>
					<?php /* El campo ya se llamaba "dark backgrounds" pero nada lo aplicaba solo: el logo alternativo se quedaba sin usar y el header oscuro mostraba el oscuro. Ahora sí conmuta, y conviene decirlo aquí. */ ?>
					<p class="description"><?php esc_html_e( 'With the Brand block set to Automatic, the alternative logo is used on dark palettes and dark style variations, and the main one everywhere else. Leave it empty to always use the main logo.', 'bloquix' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->row_logo( __( 'Main logo', 'bloquix' ), 'logo_main_id', (int) $s['logo_main_id'] );
						$this->row_logo( __( 'Alternative logo (dark backgrounds)', 'bloquix' ), 'logo_alt_id', (int) $s['logo_alt_id'], true );
						?>
					</table>
				</div>

				<div class="card bloquix-section-card">
					<h2><?php esc_html_e( 'Branding', 'bloquix' ); ?></h2>

					<h3><?php esc_html_e( 'Typography (Google Fonts)', 'bloquix' ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						$this->row_font( __( 'Display', 'bloquix' ), 'font_display', $s['font_display'], self::fonts_text() );
						$this->row_font( __( 'Body', 'bloquix' ), 'font_body', $s['font_body'], self::fonts_text() );
						$this->row_font( __( 'Monospace', 'bloquix' ), 'font_mono', $s['font_mono'], self::fonts_mono() );
						$this->row_select( __( 'Base size', 'bloquix' ), 'text_base', $s['text_base'], array( '' => __( 'Theme default', 'bloquix' ), 'sm' => __( 'Compact', 'bloquix' ), 'lg' => __( 'Large', 'bloquix' ) ) );
						?>
					</table>

					<h3><?php esc_html_e( 'Brand colors', 'bloquix' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Empty = the theme decides. A value here overrides the theme token site-wide, in every style variation.', 'bloquix' ); ?>
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
						'brand_primary'  => __( 'Primary', 'bloquix' ),
						'brand_accent'   => __( 'Accent', 'bloquix' ),
						'brand_accent_2' => __( 'Accent 2', 'bloquix' ),
						'brand_bg'       => __( 'Background', 'bloquix' ),
						'brand_text'     => __( 'Text', 'bloquix' ),
						'brand_ink'      => __( 'Dark bands', 'bloquix' ),
						'brand_on_ink'   => __( 'Text on dark bands', 'bloquix' ),
					);
					$brand_active = array();
					foreach ( $brand_keys as $bk => $blabel ) {
						if ( ! empty( $s[ $bk ] ) ) {
							$brand_active[] = $blabel . ' (' . $s[ $bk ] . ')';
						}
					}
					if ( $brand_active ) :
						?>
						<div class="notice notice-warning inline bloquix-brand-warning">
							<p>
								<strong><?php esc_html_e( 'Brand colors are overriding this theme.', 'bloquix' ); ?></strong>
								<?php
								printf(
									/* translators: %s: comma-separated list of overridden color names with their hex value. */
									esc_html__( 'Active: %s. While these are set, switching the style variation will not change them.', 'bloquix' ),
									esc_html( implode( ', ', $brand_active ) )
								);
								?>
							</p>
							<?php if ( ! empty( $s['brand_bg'] ) && empty( $s['brand_text'] ) ) : ?>
								<p>
									<?php esc_html_e( 'You set a background but not a text color: on a theme whose text color changes with the variation, that pair can end up unreadable.', 'bloquix' ); ?>
								</p>
							<?php endif; ?>
							<?php if ( ! empty( $s['brand_ink'] ) && empty( $s['brand_on_ink'] ) ) : ?>
								<p>
									<?php esc_html_e( 'You set the dark bands but not their text color: make sure the theme\'s text on dark bands still reads on your new background.', 'bloquix' ); ?>
								</p>
							<?php endif; ?>
							<p>
								<button type="button" class="button" id="bloquix-brand-clear-all">
									<?php esc_html_e( 'Clear all brand colors', 'bloquix' ); ?>
								</button>
								<span class="description"><?php esc_html_e( 'Then press Save changes.', 'bloquix' ); ?></span>
							</p>
						</div>
						<?php
					endif;
					?>
					<table class="form-table bloquix-brand-colors" role="presentation">
						<?php
						$this->row_color( __( 'Primary', 'bloquix' ), 'brand_primary', $s['brand_primary'], $palette['primary'] ?? '' );
						$this->row_color( __( 'Accent', 'bloquix' ), 'brand_accent', $s['brand_accent'], $palette['accent'] ?? '' );
						$this->row_color( __( 'Accent 2', 'bloquix' ), 'brand_accent_2', $s['brand_accent_2'], $palette['accent-2'] ?? '' );
						$this->row_color( __( 'Background', 'bloquix' ), 'brand_bg', $s['brand_bg'], $palette['bg'] ?? '' );
						$this->row_color( __( 'Text', 'bloquix' ), 'brand_text', $s['brand_text'], $palette['text'] ?? '' );
						?>
					</table>
					<p class="description">
						<?php esc_html_e( 'Dark bands are the sections the theme draws over a photo or on its ink color (a hero, a closing call to action, the footer). They keep their own colors so they stay readable in every style variation — Background and Text above do not reach them. Set these two to recolor them as well.', 'bloquix' ); ?>
					</p>
					<table class="form-table bloquix-brand-colors" role="presentation">
						<?php
						$tokens = $this->theme_tokens( array( 'ink', 'on-ink' ) );
						$this->row_color( __( 'Dark bands', 'bloquix' ), 'brand_ink', $s['brand_ink'], $tokens['ink'] ?? '' );
						$this->row_color( __( 'Text on dark bands', 'bloquix' ), 'brand_on_ink', $s['brand_on_ink'], $tokens['on-ink'] ?? '' );
						?>
					</table>

					<h3><?php esc_html_e( 'Shape', 'bloquix' ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						$this->row_number(
							__( 'Corner radius (px)', 'bloquix' ),
							'radius',
							$s['radius'],
							__( 'Empty = theme. Rounding for BloqUIX blocks/components that use the radius tokens. 0 = sharp.', 'bloquix' ),
							64
						);
						?>
					</table>

					<h3><?php esc_html_e( 'Motion', 'bloquix' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Speed of all BloqUIX effects (entrance, hover, scroll). Subtle = faster and tighter; Bold = slower and more dramatic.', 'bloquix' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->row_select( __( 'Motion intensity', 'bloquix' ), 'motion', $s['motion'], array( '' => __( 'Theme default', 'bloquix' ), 'subtle' => __( 'Subtle (fast)', 'bloquix' ), 'bold' => __( 'Bold (slow)', 'bloquix' ) ) );
						?>
					</table>
				</div>

				<div class="card bloquix-section-card">
					<h2><?php esc_html_e( 'Content layout', 'bloquix' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Show a sidebar — or run full width — on blog posts and archives. Pages are not affected here: they use per-page templates (e.g. Narrow, With sidebar) chosen in the page editor.', 'bloquix' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$layout_opts = array(
							''        => __( 'Full width (no sidebar)', 'bloquix' ),
							'sidebar' => __( 'With sidebar', 'bloquix' ),
						);
						$this->row_select( __( 'Single posts', 'bloquix' ), 'layout_post_single', $s['layout_post_single'], $layout_opts );
						$this->row_select( __( 'Blog & archives', 'bloquix' ), 'layout_archive', $s['layout_archive'], $layout_opts );
						?>
					</table>
					<p class="description"><?php esc_html_e( 'Themes that ship a sidebar area react automatically. A theme without one simply stays full width.', 'bloquix' ); ?></p>
				</div>

				<div class="card bloquix-section-card">
					<h2><?php esc_html_e( 'General', 'bloquix' ); ?></h2>
					<p><label>
						<input type="checkbox" name="effects_enabled" value="1" <?php checked( ! empty( $s['effects_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable the effects engine (tf*) on the front-end', 'bloquix' ); ?>
					</label></p>
					<p class="description"><?php esc_html_e( 'When disabled, the runtime is not loaded and blocks render clean (useful for performance debugging).', 'bloquix' ); ?></p>
				</div>

				<?php submit_button( __( 'Save settings', 'bloquix' ) ); ?>
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
		$notice   = isset( $_GET['bloquix_notice'] ) ? sanitize_key( wp_unslash( $_GET['bloquix_notice'] ) ) : '';
		$post_url = admin_url( 'admin-post.php' );
		?>
		<div class="wrap bloquix-admin">
			<h1><?php esc_html_e( 'BloqUIX · Tools', 'bloquix' ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $this->notice_text( $notice ) ); ?></p></div>
			<?php endif; ?>

			<div class="bloquix-admin__grid">
				<div class="card">
					<h2><?php esc_html_e( 'Export', 'bloquix' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Download the current settings as a JSON file.', 'bloquix' ); ?></p>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'bloquix_export', $post_url ), 'bloquix_export' ) ); ?>">
							<span class="dashicons dashicons-download" aria-hidden="true" style="vertical-align:text-bottom"></span>
							<?php esc_html_e( 'Export settings (.json)', 'bloquix' ); ?>
						</a>
					</p>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Import', 'bloquix' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Restore settings from a previously exported JSON file.', 'bloquix' ); ?></p>
					<form method="post" action="<?php echo esc_url( $post_url ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="bloquix_import" />
						<?php wp_nonce_field( 'bloquix_import' ); ?>
						<p><input type="file" name="bloquix_import_file" accept="application/json,.json" required /></p>
						<?php submit_button( __( 'Import settings', 'bloquix' ), 'secondary' ); ?>
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
		$options = array( '' => __( 'Theme default', 'bloquix' ) );
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
			<th scope="row"><label for="bloquix-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="bloquix-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
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
			<th scope="row"><label for="bloquix-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" id="bloquix-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" min="0" max="<?php echo esc_attr( $max ); ?>" step="1" class="small-text" />
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
			<th scope="row"><label for="bloquix-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td class="bloquix-color-row">
				<input type="color" value="<?php echo esc_attr( $current ? $current : $placeholder ); ?>" data-target="bloquix-<?php echo esc_attr( $name ); ?>" class="bloquix-color-swatch" aria-hidden="true" tabindex="-1" />
				<input type="text" id="bloquix-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" placeholder="<?php echo esc_attr( $placeholder . ' (theme)' ); ?>" class="bloquix-color-text regular-text" pattern="#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" />
				<button type="button" class="button bloquix-icon-btn bloquix-color-clear" data-target="bloquix-<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Reset to theme color', 'bloquix' ); ?>" aria-label="<?php esc_attr_e( 'Reset to theme color', 'bloquix' ); ?>">
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
		$id  = 'bloquix-' . $name;
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<div class="bloquix-logo-field">
					<div class="bloquix-logo-preview<?php echo $dark ? ' bloquix-logo-preview--dark' : ''; ?>" data-for="<?php echo esc_attr( $id ); ?>">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="" />
						<?php endif; ?>
					</div>
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" />
					<p class="bloquix-logo-actions">
						<button type="button" class="button bloquix-logo-pick" data-target="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Choose / change', 'bloquix' ); ?></button>
						<button type="button" class="button bloquix-icon-btn bloquix-logo-remove" data-target="<?php echo esc_attr( $id ); ?>" title="<?php esc_attr_e( 'Remove logo', 'bloquix' ); ?>" aria-label="<?php esc_attr_e( 'Remove logo', 'bloquix' ); ?>">
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
				return __( 'Settings saved.', 'bloquix' );
			case 'imported':
				return __( 'Settings imported successfully.', 'bloquix' );
			case 'import_error':
				return __( 'The file is not a valid settings JSON.', 'bloquix' );
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
			'brand_ink'       => $color( $src['brand_ink'] ?? '' ),
			'brand_on_ink'    => $color( $src['brand_on_ink'] ?? '' ),
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
			wp_die( esc_html__( 'Permission denied.', 'bloquix' ) );
		}
		check_admin_referer( 'bloquix_save_settings' );

		$clean = $this->sanitize_settings( wp_unslash( $_POST ) );
		update_option( self::OPTION, $clean );
		$this->sync_custom_logo( $clean );

		wp_safe_redirect( add_query_arg( 'bloquix_notice', 'saved', admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ) );
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
			wp_die( esc_html__( 'Permission denied.', 'bloquix' ) );
		}
		check_admin_referer( 'bloquix_export' );

		$payload = array(
			'_type'    => 'bloquix-settings',
			'_version' => BLOQUIX_VERSION,
			'settings' => self::get_settings(),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bloquix-settings.json' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Import settings from a JSON file.
	 */
	public function handle_import() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'bloquix' ) );
		}
		check_admin_referer( 'bloquix_import' );

		$notice = 'import_error';

		// El saneado real de una subida es is_uploaded_file(): confirma que la ruta
		// es la que PHP acaba de crear y no una que venga del cliente. Aun así se
		// pasa por sanitize_text_field antes de tocarla, porque un tmp_name no
		// contiene nada que ese filtro pueda estropear y así la comprobación queda
		// explícita en el código en vez de argumentada en un phpcs:ignore.
		$tmp = isset( $_FILES['bloquix_import_file']['tmp_name'] )
			? sanitize_text_field( wp_unslash( $_FILES['bloquix_import_file']['tmp_name'] ) )
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

		wp_safe_redirect( add_query_arg( 'bloquix_notice', $notice, admin_url( 'admin.php?page=' . self::TOOLS_SLUG ) ) );
		exit;
	}

}
