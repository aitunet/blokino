<?php
/**
 * Engine admin: settings (stacked sections), global branding overrides, brand
 * logos, and a separate Tools page (import/export).
 *
 * (CLAUDE.md §4.4.) OVERRIDE MODEL: the theme is the source of truth for the
 * --tnt-* tokens; this panel only stores overrides, which the runtime injects
 * as :root{--tnt-*}. Fonts load from Google Fonts (CDN). Logos are engine-level
 * brand assets (they persist across theme switches); the theme decides where to
 * place them (blokino/brand block or the native Site Logo block).
 *
 * Reads/builders are STATIC so the runtime can use them on the front-end.
 *
 * Default UI language is English; strings are translatable (text domain 'blokino').
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Engine administration.
 */
class Blokino_Admin {

	const OPTION     = 'blokino_settings';
	const MENU_SLUG  = 'blokino';          // top-level entry = the Get started screen
	const SETTINGS_SLUG = 'blokino-settings';
	const TOOLS_SLUG  = 'blokino-tools';
	const CAPABILITY  = 'manage_options';

	/* ---------------------------------------------------------------------
	 * Catalogs (curated Google Fonts)
	 * ------------------------------------------------------------------ */

	/** Sans/serif fonts (display/body) → weights. Themes/plugins extend it with the blokino_fonts_text filter. */
	public static function fonts_text() {
		return apply_filters( 'blokino_fonts_text', array(
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
		return apply_filters( 'blokino_fonts_mono', array(
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
		add_action( 'admin_enqueue_scripts', array( $this, 'menu_icon_style' ) );
		add_action( 'admin_post_blokino_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_blokino_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_blokino_import', array( $this, 'handle_import' ) );
	}

	/**
	 * La marca: "Encaje volteado · 2 bloques" (elegida 2026-09-23).
	 *
	 * Un bloque cortado en tres piezas con huecos de 9: la b (asta + vientre cuadrado) y
	 * los dos bloques que la encajan hasta formar el cuadrado (barra de arriba y columna
	 * de la derecha). Esquinas salientes r6; la esquina interior de la b, recta.
	 *
	 * Dos paths en un espacio de 256 (el bloque ocupa 60–196, viewBox "56 56 144 144"
	 * con 4 de margen): la b y los dos bloques juntos. Un solo sitio con la geometría
	 * para que el data-URI del menú y las máscaras de menu_icon_style() no puedan
	 * divergir; la fuente documentada está en admin/img/icon-blokino.svg y el generador
	 * de todos los assets en wporg-assets/build-icon.mjs. Va en línea porque
	 * add_menu_page() necesita el valor al registrar el menú y leer el archivo en cada
	 * carga del admin sería una lectura de disco por página para 400 bytes.
	 */
	const ICON_VIEWBOX     = '56 56 144 144';
	const ICON_PATH_B      = 'M66 60 H93 A6 6 0 0 1 99 66 V113 H137 A6 6 0 0 1 143 119 V190 A6 6 0 0 1 137 196 H66 A6 6 0 0 1 60 190 V66 A6 6 0 0 1 66 60 Z';
	const ICON_PATH_BLOCKS = 'M114 60 H190 A6 6 0 0 1 196 66 V98 A6 6 0 0 1 190 104 H114 A6 6 0 0 1 108 98 V66 A6 6 0 0 1 114 60 Z M158 113 H190 A6 6 0 0 1 196 119 V190 A6 6 0 0 1 190 196 H158 A6 6 0 0 1 152 190 V119 A6 6 0 0 1 158 113 Z';
	const ICON_SIGNAL      = '#00BBDB';

	/**
	 * La marca como data-URI para add_menu_page(): las piezas en blanco.
	 *
	 * Es el estado de reposo Y el fallback: un icono pasado por data-URI se pinta como
	 * background-image y WP no lo recolorea. En un navegador con máscaras CSS,
	 * menu_icon_style() lo reemplaza por las dos máscaras (b blanca, bloques blancos que
	 * pasan a Signal con el ratón encima o con la pantalla activa); sin máscaras, se
	 * queda este.
	 *
	 * @return string
	 */
	private static function menu_icon_data_uri() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . self::ICON_VIEWBOX . '">'
			. '<path fill="#fff" d="' . self::ICON_PATH_B . '"/>'
			. '<path fill="#fff" d="' . self::ICON_PATH_BLOCKS . '"/>'
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
	 * La b siempre blanca; los bloques blancos en reposo y Signal cuando el ítem tiene el
	 * ratón encima, el foco, o es la pantalla activa (decisión del usuario, 2026-09-14,
	 * mantenida con la marca de Blokino). Un icono por data-URI no puede hacer eso (WP no
	 * lo recolorea), así que cada parte es un pseudo-elemento con su máscara y su
	 * background-color. Va detrás de un @supports y solo entonces se oculta el
	 * background, de modo que un navegador sin máscaras conserva el icono blanco de
	 * menu_icon_data_uri() en lugar de quedarse sin ninguno.
	 *
	 * Se encola con wp_add_inline_style() sobre un handle sin archivo (pedido del review
	 * de wp.org: nada de <style> impreso a mano). El menú sale en todas las pantallas del
	 * admin, así que se encola en todas; son ~2 KB de CSS.
	 *
	 * Sin margin vertical: WP ya centra los iconos del menú con padding 7px 0 sobre
	 * 34px (7+20+7); un margen propio se sumaría y bajaría la marca respecto a los
	 * dashicons vecinos (medido en la versión anterior).
	 */
	public function menu_icon_style() {
		$mask_b      = self::menu_icon_mask( self::ICON_PATH_B );
		$mask_blocks = self::menu_icon_mask( self::ICON_PATH_BLOCKS );
		$item        = '#adminmenu #toplevel_page_' . self::MENU_SLUG;
		$img         = $item . ' .wp-menu-image';
		$lit         = $item . ':hover .wp-menu-image::after, ' . $item . '.current .wp-menu-image::after, ' . $item . '.wp-has-current-submenu .wp-menu-image::after, ' . $item . ' a:focus .wp-menu-image::after';

		$css = '@supports ((-webkit-mask-image: ' . $mask_b . ') or (mask-image: ' . $mask_b . ')) {'
			. $img . ' { position: relative; background-image: none !important; }'
			. $img . '::before, ' . $img . '::after {'
			. 'content: ""; position: absolute; inset: 0; background-color: #fff;'
			. '-webkit-mask-repeat: no-repeat; mask-repeat: no-repeat;'
			. '-webkit-mask-position: center; mask-position: center;'
			. '-webkit-mask-size: 20px 20px; mask-size: 20px 20px;'
			. 'transition: background-color .15s ease; }'
			. $img . '::before { -webkit-mask-image: ' . $mask_b . '; mask-image: ' . $mask_b . '; }'
			. $img . '::after { -webkit-mask-image: ' . $mask_blocks . '; mask-image: ' . $mask_blocks . '; }'
			. $lit . ' { background-color: ' . self::ICON_SIGNAL . '; }'
			. '}';

		wp_register_style( 'blokino-menu-icon', false, array(), BLOKINO_VERSION );
		wp_enqueue_style( 'blokino-menu-icon' );
		wp_add_inline_style( 'blokino-menu-icon', $css );
	}

	/**
	 * Register menu + submenus + Appearance link.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Blokino', 'blokino' ),
			__( 'Blokino', 'blokino' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( 'Blokino_Welcome', 'render_page' ),
			self::menu_icon_data_uri(),
			59
		);

		// The parent's own entry is the first submenu: Get started.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Get started', 'blokino' ),
			__( 'Get started', 'blokino' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( 'Blokino_Welcome', 'render_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'blokino' ),
			__( 'Settings', 'blokino' ),
			self::CAPABILITY,
			self::SETTINGS_SLUG,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tools', 'blokino' ),
			__( 'Tools', 'blokino' ),
			self::CAPABILITY,
			self::TOOLS_SLUG,
			array( $this, 'render_tools_page' )
		);

		// Direct link under Appearance → settings page.
		add_submenu_page(
			'themes.php',
			__( 'Blokino', 'blokino' ),
			__( 'Blokino', 'blokino' ),
			self::CAPABILITY,
			'admin.php?page=' . self::SETTINGS_SLUG
		);
	}

	/**
	 * Fires after Blokino registered its own submenus. Premium themes hook
	 * here to add screens (e.g. License) under the Blokino menu.
	 *
	 * Hooked at `admin_menu` priority 12 — after Settings/Tools (priority 10,
	 * `register_menu`) and Demo (priority 11) but before Themes (priority 13) —
	 * so the menu order stays Settings · Tools · Demo · (theme screens) · Themes.
	 */
	public function fire_menu_hook() {
		/**
		 * Fires after Blokino registered its own submenus. Premium themes hook
		 * here to add screens (e.g. License) under the Blokino menu.
		 *
		 * @param string $parent_slug The Blokino menu slug.
		 */
		do_action( 'blokino_admin_menu', self::MENU_SLUG );
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
		$is_demo     = ( false !== strpos( $hook, Blokino_Demo::MENU_SLUG ) );
		$is_themes   = ( false !== strpos( $hook, Blokino_Themes::MENU_SLUG ) );

		if ( ! $is_welcome && ! $is_settings && ! $is_tools && ! $is_demo && ! $is_themes ) {
			return;
		}

		if ( $is_settings ) {
			wp_enqueue_media();
		}

		wp_enqueue_style( 'blokino-admin', BLOKINO_URL . 'admin/admin.css', array(), (string) filemtime( BLOKINO_PATH . 'admin/admin.css' ) );
		wp_enqueue_script( 'blokino-admin', BLOKINO_URL . 'admin/admin.js', array(), (string) filemtime( BLOKINO_PATH . 'admin/admin.js' ), true );

		wp_localize_script(
			'blokino-admin',
			'blokinoAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'homeUrl' => home_url( '/' ),
				'nonce'   => wp_create_nonce( 'blokino_demo' ),
				'i18n'    => array(
					'importing'    => __( 'Importing…', 'blokino' ),
					'done'         => __( 'Demo imported.', 'blokino' ),
					'rollback'     => __( 'Import undone.', 'blokino' ),
					'error'        => __( 'An error occurred.', 'blokino' ),
					'chooseLogo'   => __( 'Select logo', 'blokino' ),
					'useLogo'      => __( 'Use this logo', 'blokino' ),
					'pluginsTitle' => __( 'Recommended plugins', 'blokino' ),
					'installAct'   => __( 'Install & activate', 'blokino' ),
					'installing'   => __( 'Installing…', 'blokino' ),
					'activate'     => __( 'Activate', 'blokino' ),
					'active'       => __( 'Active', 'blokino' ),
					'required'     => __( 'Required', 'blokino' ),
					'continue'     => __( 'Continue', 'blokino' ),
					'optional'     => __( 'Optional', 'blokino' ),
					'pluginsReady' => __( 'All set — continue to the import.', 'blokino' ),
					'noPlugins'    => __( 'No extra plugins needed for this demo — continue to the import.', 'blokino' ),
					'viewSite'     => __( 'View site', 'blokino' ),
					'installManually' => __( 'Install manually', 'blokino' ),
					/* translators: %s: what the server answered, e.g. "HTTP 504". */
					'serverError'  => __( 'The server did not answer with JSON (%s). The request was probably cut short by a time limit.', 'blokino' ),
					'retrying'     => __( 'Connection hiccup — retrying…', 'blokino' ),
					'retryHint'    => __( 'Click Retry to resume from this step.', 'blokino' ),
					'retry'        => __( 'Retry', 'blokino' ),
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
		$notice   = isset( $_GET['blokino_notice'] ) ? sanitize_key( wp_unslash( $_GET['blokino_notice'] ) ) : '';
		$post_url = admin_url( 'admin-post.php' );
		$palette  = $this->theme_palette();
		?>
		<div class="wrap blokino-admin">
			<h1><?php esc_html_e( 'Blokino', 'blokino' ); ?></h1>
			<p class="description"><?php esc_html_e( 'The theme provides the defaults. Anything left empty uses the theme; anything you set overrides it globally (--tnt-* tokens).', 'blokino' ); ?></p>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $this->notice_text( $notice ) ); ?></p></div>
			<?php endif; ?>

			<?php if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) : ?>
				<div class="card blokino-section-card">
					<h2><?php esc_html_e( 'Appearance & styles', 'blokino' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Switch the active theme’s look — its color style variations (e.g. light / dark) — in the Site Editor. The variations ship with the theme; the editor is where you preview and apply them.', 'blokino' ); ?></p>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'site-editor.php?path=%2Fwp_global_styles' ) ); ?>">
							<?php esc_html_e( 'Open Styles in the Site Editor', 'blokino' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $post_url ); ?>">
				<input type="hidden" name="action" value="blokino_save_settings" />
				<?php wp_nonce_field( 'blokino_save_settings' ); ?>

				<div class="card blokino-section-card">
					<h2><?php esc_html_e( 'Brand logos', 'blokino' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Brand assets that persist across themes. The main logo syncs with the native Site Logo. Upload high resolution (retina is automatic).', 'blokino' ); ?></p>
					<?php /* El campo ya se llamaba "dark backgrounds" pero nada lo aplicaba solo: el logo alternativo se quedaba sin usar y el header oscuro mostraba el oscuro. Ahora sí conmuta, y conviene decirlo aquí. */ ?>
					<p class="description"><?php esc_html_e( 'With the Brand block set to Automatic, the alternative logo is used on dark palettes and dark style variations, and the main one everywhere else. Leave it empty to always use the main logo.', 'blokino' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->row_logo( __( 'Main logo', 'blokino' ), 'logo_main_id', (int) $s['logo_main_id'] );
						$this->row_logo( __( 'Alternative logo (dark backgrounds)', 'blokino' ), 'logo_alt_id', (int) $s['logo_alt_id'], true );
						?>
					</table>
				</div>

				<div class="card blokino-section-card">
					<h2><?php esc_html_e( 'Branding', 'blokino' ); ?></h2>

					<h3><?php esc_html_e( 'Typography (Google Fonts)', 'blokino' ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						$this->row_font( __( 'Display', 'blokino' ), 'font_display', $s['font_display'], self::fonts_text() );
						$this->row_font( __( 'Body', 'blokino' ), 'font_body', $s['font_body'], self::fonts_text() );
						$this->row_font( __( 'Monospace', 'blokino' ), 'font_mono', $s['font_mono'], self::fonts_mono() );
						$this->row_select( __( 'Base size', 'blokino' ), 'text_base', $s['text_base'], array( '' => __( 'Theme default', 'blokino' ), 'sm' => __( 'Compact', 'blokino' ), 'lg' => __( 'Large', 'blokino' ) ) );
						?>
					</table>

					<h3><?php esc_html_e( 'Brand colors', 'blokino' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Empty = the theme decides. A value here overrides the theme token site-wide, in every style variation.', 'blokino' ); ?>
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
						'brand_primary'  => __( 'Primary', 'blokino' ),
						'brand_accent'   => __( 'Accent', 'blokino' ),
						'brand_accent_2' => __( 'Accent 2', 'blokino' ),
						'brand_bg'       => __( 'Background', 'blokino' ),
						'brand_text'     => __( 'Text', 'blokino' ),
						'brand_ink'      => __( 'Dark bands', 'blokino' ),
						'brand_on_ink'   => __( 'Text on dark bands', 'blokino' ),
					);
					$brand_active = array();
					foreach ( $brand_keys as $bk => $blabel ) {
						if ( ! empty( $s[ $bk ] ) ) {
							$brand_active[] = $blabel . ' (' . $s[ $bk ] . ')';
						}
					}
					if ( $brand_active ) :
						?>
						<div class="notice notice-warning inline blokino-brand-warning">
							<p>
								<strong><?php esc_html_e( 'Brand colors are overriding this theme.', 'blokino' ); ?></strong>
								<?php
								printf(
									/* translators: %s: comma-separated list of overridden color names with their hex value. */
									esc_html__( 'Active: %s. While these are set, switching the style variation will not change them.', 'blokino' ),
									esc_html( implode( ', ', $brand_active ) )
								);
								?>
							</p>
							<?php if ( ! empty( $s['brand_bg'] ) && empty( $s['brand_text'] ) ) : ?>
								<p>
									<?php esc_html_e( 'You set a background but not a text color: on a theme whose text color changes with the variation, that pair can end up unreadable.', 'blokino' ); ?>
								</p>
							<?php endif; ?>
							<?php if ( ! empty( $s['brand_ink'] ) && empty( $s['brand_on_ink'] ) ) : ?>
								<p>
									<?php esc_html_e( 'You set the dark bands but not their text color: make sure the theme\'s text on dark bands still reads on your new background.', 'blokino' ); ?>
								</p>
							<?php endif; ?>
							<p>
								<button type="button" class="button" id="blokino-brand-clear-all">
									<?php esc_html_e( 'Clear all brand colors', 'blokino' ); ?>
								</button>
								<span class="description"><?php esc_html_e( 'Then press Save changes.', 'blokino' ); ?></span>
							</p>
						</div>
						<?php
					endif;
					?>
					<table class="form-table blokino-brand-colors" role="presentation">
						<?php
						$this->row_color( __( 'Primary', 'blokino' ), 'brand_primary', $s['brand_primary'], $palette['primary'] ?? '' );
						$this->row_color( __( 'Accent', 'blokino' ), 'brand_accent', $s['brand_accent'], $palette['accent'] ?? '' );
						$this->row_color( __( 'Accent 2', 'blokino' ), 'brand_accent_2', $s['brand_accent_2'], $palette['accent-2'] ?? '' );
						$this->row_color( __( 'Background', 'blokino' ), 'brand_bg', $s['brand_bg'], $palette['bg'] ?? '' );
						$this->row_color( __( 'Text', 'blokino' ), 'brand_text', $s['brand_text'], $palette['text'] ?? '' );
						?>
					</table>
					<p class="description">
						<?php esc_html_e( 'Dark bands are the sections the theme draws over a photo or on its ink color (a hero, a closing call to action, the footer). They keep their own colors so they stay readable in every style variation — Background and Text above do not reach them. Set these two to recolor them as well.', 'blokino' ); ?>
					</p>
					<table class="form-table blokino-brand-colors" role="presentation">
						<?php
						$tokens = $this->theme_tokens( array( 'ink', 'on-ink' ) );
						$this->row_color( __( 'Dark bands', 'blokino' ), 'brand_ink', $s['brand_ink'], $tokens['ink'] ?? '' );
						$this->row_color( __( 'Text on dark bands', 'blokino' ), 'brand_on_ink', $s['brand_on_ink'], $tokens['on-ink'] ?? '' );
						?>
					</table>

					<h3><?php esc_html_e( 'Shape', 'blokino' ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						$this->row_number(
							__( 'Corner radius (px)', 'blokino' ),
							'radius',
							$s['radius'],
							__( 'Empty = theme. Rounding for Blokino blocks/components that use the radius tokens. 0 = sharp.', 'blokino' ),
							64
						);
						?>
					</table>

					<h3><?php esc_html_e( 'Motion', 'blokino' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Speed of all Blokino effects (entrance, hover, scroll). Subtle = faster and tighter; Bold = slower and more dramatic.', 'blokino' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->row_select( __( 'Motion intensity', 'blokino' ), 'motion', $s['motion'], array( '' => __( 'Theme default', 'blokino' ), 'subtle' => __( 'Subtle (fast)', 'blokino' ), 'bold' => __( 'Bold (slow)', 'blokino' ) ) );
						?>
					</table>
				</div>

				<div class="card blokino-section-card">
					<h2><?php esc_html_e( 'Content layout', 'blokino' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Show a sidebar — or run full width — on blog posts and archives. Pages are not affected here: they use per-page templates (e.g. Narrow, With sidebar) chosen in the page editor.', 'blokino' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$layout_opts = array(
							''        => __( 'Full width (no sidebar)', 'blokino' ),
							'sidebar' => __( 'With sidebar', 'blokino' ),
						);
						$this->row_select( __( 'Single posts', 'blokino' ), 'layout_post_single', $s['layout_post_single'], $layout_opts );
						$this->row_select( __( 'Blog & archives', 'blokino' ), 'layout_archive', $s['layout_archive'], $layout_opts );
						?>
					</table>
					<p class="description"><?php esc_html_e( 'Themes that ship a sidebar area react automatically. A theme without one simply stays full width.', 'blokino' ); ?></p>
				</div>

				<div class="card blokino-section-card">
					<h2><?php esc_html_e( 'General', 'blokino' ); ?></h2>
					<p><label>
						<input type="checkbox" name="effects_enabled" value="1" <?php checked( ! empty( $s['effects_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable the effects engine (tf*) on the front-end', 'blokino' ); ?>
					</label></p>
					<p class="description"><?php esc_html_e( 'When disabled, the runtime is not loaded and blocks render clean (useful for performance debugging).', 'blokino' ); ?></p>
				</div>

				<?php submit_button( __( 'Save settings', 'blokino' ) ); ?>
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
		$notice   = isset( $_GET['blokino_notice'] ) ? sanitize_key( wp_unslash( $_GET['blokino_notice'] ) ) : '';
		$post_url = admin_url( 'admin-post.php' );
		?>
		<div class="wrap blokino-admin">
			<h1><?php esc_html_e( 'Blokino · Tools', 'blokino' ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $this->notice_text( $notice ) ); ?></p></div>
			<?php endif; ?>

			<div class="blokino-admin__grid">
				<div class="card">
					<h2><?php esc_html_e( 'Export', 'blokino' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Download the current settings as a JSON file.', 'blokino' ); ?></p>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'blokino_export', $post_url ), 'blokino_export' ) ); ?>">
							<span class="dashicons dashicons-download" aria-hidden="true" style="vertical-align:text-bottom"></span>
							<?php esc_html_e( 'Export settings (.json)', 'blokino' ); ?>
						</a>
					</p>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Import', 'blokino' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Restore settings from a previously exported JSON file.', 'blokino' ); ?></p>
					<form method="post" action="<?php echo esc_url( $post_url ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="blokino_import" />
						<?php wp_nonce_field( 'blokino_import' ); ?>
						<p><input type="file" name="blokino_import_file" accept="application/json,.json" required /></p>
						<?php submit_button( __( 'Import settings', 'blokino' ), 'secondary' ); ?>
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
		$options = array( '' => __( 'Theme default', 'blokino' ) );
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
			<th scope="row"><label for="blokino-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="blokino-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
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
			<th scope="row"><label for="blokino-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" id="blokino-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" min="0" max="<?php echo esc_attr( $max ); ?>" step="1" class="small-text" />
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
			<th scope="row"><label for="blokino-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td class="blokino-color-row">
				<input type="color" value="<?php echo esc_attr( $current ? $current : $placeholder ); ?>" data-target="blokino-<?php echo esc_attr( $name ); ?>" class="blokino-color-swatch" aria-hidden="true" tabindex="-1" />
				<input type="text" id="blokino-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" placeholder="<?php echo esc_attr( $placeholder . ' (theme)' ); ?>" class="blokino-color-text regular-text" pattern="#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" />
				<button type="button" class="button blokino-icon-btn blokino-color-clear" data-target="blokino-<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Reset to theme color', 'blokino' ); ?>" aria-label="<?php esc_attr_e( 'Reset to theme color', 'blokino' ); ?>">
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
		$id  = 'blokino-' . $name;
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<div class="blokino-logo-field">
					<div class="blokino-logo-preview<?php echo $dark ? ' blokino-logo-preview--dark' : ''; ?>" data-for="<?php echo esc_attr( $id ); ?>">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="" />
						<?php endif; ?>
					</div>
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" />
					<p class="blokino-logo-actions">
						<button type="button" class="button blokino-logo-pick" data-target="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Choose / change', 'blokino' ); ?></button>
						<button type="button" class="button blokino-icon-btn blokino-logo-remove" data-target="<?php echo esc_attr( $id ); ?>" title="<?php esc_attr_e( 'Remove logo', 'blokino' ); ?>" aria-label="<?php esc_attr_e( 'Remove logo', 'blokino' ); ?>">
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
				return __( 'Settings saved.', 'blokino' );
			case 'imported':
				return __( 'Settings imported successfully.', 'blokino' );
			case 'import_error':
				return __( 'The file is not a valid settings JSON.', 'blokino' );
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
			wp_die( esc_html__( 'Permission denied.', 'blokino' ) );
		}
		check_admin_referer( 'blokino_save_settings' );

		$clean = $this->sanitize_settings( wp_unslash( $_POST ) );
		update_option( self::OPTION, $clean );
		$this->sync_custom_logo( $clean );

		wp_safe_redirect( add_query_arg( 'blokino_notice', 'saved', admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ) );
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
			wp_die( esc_html__( 'Permission denied.', 'blokino' ) );
		}
		check_admin_referer( 'blokino_export' );

		$payload = array(
			'_type'    => 'blokino-settings',
			'_version' => BLOKINO_VERSION,
			'settings' => self::get_settings(),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=blokino-settings.json' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Import settings from a JSON file.
	 */
	public function handle_import() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'blokino' ) );
		}
		check_admin_referer( 'blokino_import' );

		$notice = 'import_error';

		// El saneado real de una subida es is_uploaded_file(): confirma que la ruta
		// es la que PHP acaba de crear y no una que venga del cliente. Aun así se
		// pasa por sanitize_text_field antes de tocarla, porque un tmp_name no
		// contiene nada que ese filtro pueda estropear y así la comprobación queda
		// explícita en el código en vez de argumentada en un phpcs:ignore.
		$tmp = isset( $_FILES['blokino_import_file']['tmp_name'] )
			? sanitize_text_field( wp_unslash( $_FILES['blokino_import_file']['tmp_name'] ) )
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

		wp_safe_redirect( add_query_arg( 'blokino_notice', $notice, admin_url( 'admin.php?page=' . self::TOOLS_SLUG ) ) );
		exit;
	}

}
