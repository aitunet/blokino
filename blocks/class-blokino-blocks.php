<?php
/**
 * Blocks PROPIOS del motor (STUB — PIEZA 1).
 *
 * Solo se crean blocks donde el core no llega (CLAUDE.md §4.2). No se duplican
 * párrafos, botones ni columnas del core.
 *
 * Blocks previstos (cada uno con su block.json y carga de assets condicional
 * vía should_load_separate_core_block_assets, ya activado en el orquestador):
 *   - blokino/section       (backgrounds avanzados, overlay, shape divider)
 *   - blokino/marquee       (banda infinita)
 *   - blokino/counter       (número animado on-scroll)
 *   - blokino/before-after  (comparador de imágenes)
 *
 * Cada block dinámico encolará el runtime en su render llamando a
 * Blokino_Runtime::enqueue() — así nada se carga si el block no aparece.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'blokino_safe_css_color' ) ) {
	/**
	 * Normaliza un color para que SOBREVIVA a safecss_filter_attr() en un
	 * inline-style (y para no perderse en los saneadores de cada block).
	 *
	 * MEDIDO en WP 7.0.2: dentro de un `style` inline, WP DESCARTA `rgb()`,
	 * `rgba()`, `hsl()` y `color-mix()`; solo pasan el hex, `var()` y los
	 * gradientes de su allowlist (un gradiente sí admite `rgba()` en sus stops,
	 * por eso los overlays en gradiente nunca fallaron).
	 *
	 * Importa porque el descarte NO deja "sin color", que sería inofensivo: deja
	 * la variable sin definir y el CSS cae a su FALLBACK a la opacidad pedida. En
	 * `blokino/section` eso era un panel opaco del color de fondo que TAPABA la foto
	 * entera — así se envió `vector/contact-cta`. Y los controles del sidebar
	 * llevan `enableAlpha`, así que el ColorPalette produce `rgba()` en cuanto el
	 * comprador toca la transparencia: es un caso corriente, no una rareza.
	 *
	 * @param string $value Color tal cual viene del atributo.
	 * @return string Hex (8 dígitos si hay alpha), el valor original si ya era
	 *                seguro, o '' si es irrepresentable (decide el llamador).
	 */
	function blokino_safe_css_color( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		/*
		 * Ya seguro: hex, palabra clave (transparent, currentColor…) o var().
		 * El var() se valida ENTERO a propósito: no todos los llamadores pasan por
		 * safecss_filter_attr (el overlay del content-slider se emite a mano, solo
		 * con esc_attr), y esc_attr no escapa el `;` — un valor tipo
		 * `var(--x);background:url(…)` colaría una declaración extra en el style.
		 */
		if ( preg_match( '/^#[0-9a-f]{3,8}$/i', $value )
			|| preg_match( '/^[a-z]+$/i', $value )
			|| preg_match( '/^var\(\s*--[A-Za-z0-9_-]+\s*(?:,\s*[#A-Za-z0-9_\-.%\s]*)?\)$/', $value ) ) {
			return $value;
		}

		$to_hex = static function ( $r, $g, $b, $a ) {
			$hex = sprintf( '#%02x%02x%02x', max( 0, min( 255, (int) round( $r ) ) ), max( 0, min( 255, (int) round( $g ) ) ), max( 0, min( 255, (int) round( $b ) ) ) );
			if ( $a < 1 ) {
				$hex .= sprintf( '%02x', max( 0, min( 255, (int) round( $a * 255 ) ) ) );
			}
			return $hex;
		};

		// rgb() / rgba(), con comas o con la sintaxis moderna de espacios.
		if ( preg_match( '/^rgba?\(\s*([\d.]+%?)[\s,]+([\d.]+%?)[\s,]+([\d.]+%?)(?:[\s,\/]+([\d.]+%?))?\s*\)$/i', $value, $m ) ) {
			$chan  = static function ( $v ) {
				return false !== strpos( $v, '%' ) ? ( (float) $v ) * 2.55 : (float) $v;
			};
			$alpha = isset( $m[4] ) && '' !== $m[4]
				? ( false !== strpos( $m[4], '%' ) ? ( (float) $m[4] ) / 100 : (float) $m[4] )
				: 1;
			return $to_hex( $chan( $m[1] ), $chan( $m[2] ), $chan( $m[3] ), $alpha );
		}

		// hsl() / hsla().
		if ( preg_match( '/^hsla?\(\s*([\d.]+)(?:deg)?[\s,]+([\d.]+)%[\s,]+([\d.]+)%(?:[\s,\/]+([\d.]+%?))?\s*\)$/i', $value, $m ) ) {
			$h     = fmod( (float) $m[1], 360 ) / 360;
			$s     = (float) $m[2] / 100;
			$l     = (float) $m[3] / 100;
			$alpha = isset( $m[4] ) && '' !== $m[4]
				? ( false !== strpos( $m[4], '%' ) ? ( (float) $m[4] ) / 100 : (float) $m[4] )
				: 1;
			$hue   = static function ( $p, $q, $t ) {
				if ( $t < 0 ) {
					$t += 1;
				}
				if ( $t > 1 ) {
					$t -= 1;
				}
				if ( $t < 1 / 6 ) {
					return $p + ( $q - $p ) * 6 * $t;
				}
				if ( $t < 1 / 2 ) {
					return $q;
				}
				if ( $t < 2 / 3 ) {
					return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
				}
				return $p;
			};
			if ( 0.0 === $s ) {
				$r = $l;
				$g = $l;
				$b = $l;
			} else {
				$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
				$p = 2 * $l - $q;
				$r = $hue( $p, $q, $h + 1 / 3 );
				$g = $hue( $p, $q, $h );
				$b = $hue( $p, $q, $h - 1 / 3 );
			}
			return $to_hex( $r * 255, $g * 255, $b * 255, $alpha );
		}

		// Irrepresentable (color-mix(), oklch()…): que decida el llamador.
		return '';
	}
}

if ( ! function_exists( 'blokino_safe_css_gradient' ) ) {
	/**
	 * Valida un valor de gradiente CSS antes de emitirlo en un inline-style.
	 *
	 * Existe porque el motor tenía DOS criterios distintos para lo mismo, y el más
	 * flojo estaba en el sitio con más uso:
	 *   - `blokino/section` concatenaba `overlayGradient` TAL CUAL, sin comprobar ni
	 *     que fuera un string. Con un atributo array, PHP emite el aviso "Array to
	 *     string conversion" y el CSS acaba con `--tf-sec-overlay:Array`; en un sitio
	 *     con WP_DEBUG_DISPLAY el aviso se imprime dentro de la página.
	 *   - `blokino/content-slider` sí quitaba los `;` y exigía ver `gradient(`.
	 *   - Y el runtime (sanitize_css_color) usa regex ANCLADAS, que es lo correcto.
	 *
	 * Hoy la sección está cubierta por WordPress: get_block_wrapper_attributes()
	 * pasa el style por safecss_filter_attr() (comprobado en
	 * class-wp-block-supports.php), así que una declaración colada con `;` se cae
	 * ahí. Pero el slider emite su style A MANO, solo con esc_attr() —lo dice su
	 * propio comentario—, así que la red de WP no siempre está debajo. Un helper
	 * único evita que la protección dependa de por dónde salga el valor.
	 *
	 * Criterio: la función completa y anclada, con la lista de gradientes válidos, y
	 * sin `;` ni caracteres que permitan cerrar la declaración o el atributo.
	 *
	 * @param mixed $value Valor tal cual viene del atributo del block.
	 * @return string El gradiente si es válido, '' si no (decide el llamador).
	 */
	function blokino_safe_css_gradient( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		// Ni terminadores de declaración ni salidas del atributo o del bloque CSS.
		if ( preg_match( '/[;{}<>"\']/', $value ) ) {
			return '';
		}
		// Solo funciones de gradiente reales, y el valor ENTERO tiene que ser una.
		if ( ! preg_match( '/^(?:repeating-)?(?:linear|radial|conic)-gradient\(\s*[^()]*(?:\([^()]*\)[^()]*)*\)$/i', $value ) ) {
			return '';
		}
		return $value;
	}
}

/**
 * Registra (en el futuro) los blocks propios del motor.
 */
class Blokino_Blocks {

	/**
	 * Cablea el registro de blocks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_icons' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_repeater_control' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_carousel_editor' ) );
	}

	/**
	 * Blocks propios disponibles (carpeta dentro de blocks/ con su block.json).
	 *
	 * @var string[]
	 */
	private $blocks = array(
		'marquee',
		'counter',
		'before-after',
		'section',
		'brand',
		'icon',
		'badge',
		'testimonials',
		'content-slider',
		'breadcrumbs',
	);

	/**
	 * Expone el set curado de iconos al editor (para el picker de blokino/icon).
	 */
	public function localize_icons() {
		require_once BLOKINO_PATH . 'blocks/icon/icons.php';
		wp_register_script( 'blokino-icons-data', false, array(), BLOKINO_VERSION, false );
		wp_enqueue_script( 'blokino-icons-data' );
		wp_add_inline_script( 'blokino-icons-data', 'window.blokinoIcons = ' . wp_json_encode( blokino_icon_set() ) . ';', 'before' );

		// Helper JS compartido (espejo de blokino_icon_svg) para los pickers.
		wp_enqueue_script(
			'blokino-icon-svg',
			BLOKINO_URL . 'blocks/icon/icon-svg.js',
			array( 'blokino-icons-data' ),
			BLOKINO_VERSION,
			false
		);
	}

	/**
	 * Encola el componente RepeaterControl compartido (editor). Los edit.js de los
	 * bloques que lo usan lo declaran como dependencia en su edit.asset.php.
	 */
	public function enqueue_repeater_control() {
		$abs = BLOKINO_PATH . 'blocks/shared/repeater-control.js';
		$ver = file_exists( $abs ) ? (string) filemtime( $abs ) : BLOKINO_VERSION;
		wp_enqueue_script(
			'blokino-repeater-control',
			BLOKINO_URL . 'blocks/shared/repeater-control.js',
			array( 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ),
			$ver,
			false
		);

		// Este script NO se registra desde un block.json, así que nadie le cablea las
		// traducciones: hay que declararlas aquí o sus labels salen siempre en inglés.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'blokino-repeater-control', 'blokino', BLOKINO_PATH . 'languages' );
		}

		$css_abs = BLOKINO_PATH . 'blocks/shared/repeater.css';
		$css_ver = file_exists( $css_abs ) ? (string) filemtime( $css_abs ) : BLOKINO_VERSION;
		wp_enqueue_style(
			'blokino-repeater-control',
			BLOKINO_URL . 'blocks/shared/repeater.css',
			array(),
			$css_ver
		);
	}

	/**
	 * Encola el preview de carrusel del EDITOR (Swiper real sobre el SSR). Lo usan
	 * los edit.js de los bloques de carrusel (content-slider, testimonials), que lo
	 * declaran como dependencia en su edit.asset.php. Expone la URL de carousel.css
	 * para que el helper la inyecte en el iframe del canvas (donde vive el SSR). No
	 * toca el front: es solo un asset de editor.
	 */
	public function enqueue_carousel_editor() {
		$editor_abs = BLOKINO_PATH . 'blocks/shared/carousel-editor.js';
		$editor_ver = file_exists( $editor_abs ) ? (string) filemtime( $editor_abs ) : BLOKINO_VERSION;
		wp_enqueue_script(
			'blokino-carousel-editor',
			BLOKINO_URL . 'blocks/shared/carousel-editor.js',
			array( 'wp-element', 'wp-server-side-render' ),
			$editor_ver,
			false
		);

		$carousel_css = BLOKINO_URL . 'runtime/carousel.css';
		$carousel_abs = BLOKINO_PATH . 'runtime/carousel.css';
		if ( file_exists( $carousel_abs ) ) {
			$carousel_css = add_query_arg( 'ver', filemtime( $carousel_abs ), $carousel_css );
		}
		wp_add_inline_script(
			'blokino-carousel-editor',
			'window.blokino = window.blokino || {}; window.blokino.carouselCssUrl = ' . wp_json_encode( $carousel_css ) . ';',
			'before'
		);
	}

	/**
	 * Registra los blocks propios desde su block.json.
	 *
	 * Cada block declara sus assets en block.json (style/editorScript/render).
	 * WordPress los carga de forma CONDICIONAL: el CSS del block solo se encola
	 * cuando el block aparece en la página (§3, §10).
	 */
	public function register_blocks() {
		foreach ( $this->blocks as $slug ) {
			$dir = BLOKINO_PATH . 'blocks/' . $slug;
			if ( is_dir( $dir ) && file_exists( $dir . '/block.json' ) ) {
				register_block_type( $dir );
			}
		}

		$this->set_block_script_translations();
	}

	/**
	 * Apunta las traducciones del editor a languages/ del PROPIO plugin.
	 *
	 * register_block_type() ya llama a wp_set_script_translations() por nosotros
	 * —block.json declara "textdomain"— pero lo hace SIN ruta (medido en
	 * wp-includes/blocks.php: register_block_script_handle). Sin ruta, WP resuelve
	 * el directorio por el registro de text domains, que depende de que el .mo del
	 * locale se haya encontrado antes; es un camino indirecto y frágil justo para
	 * los idiomas donde el .mo aún no existe. Re-declararlo con la ruta explícita
	 * cuesta tres líneas y deja el comportamiento sin depender de ese orden.
	 *
	 * Los handles se LEEN del registro en vez de recomponerlos a mano
	 * ('blokino-section-editor-script'): ese nombre lo fabrica
	 * generate_block_asset_handle() y es contrato de WP, no nuestro.
	 */
	private function set_block_script_translations() {
		if ( ! function_exists( 'wp_set_script_translations' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
			return;
		}

		$registry = WP_Block_Type_Registry::get_instance();
		$langs    = BLOKINO_PATH . 'languages';

		foreach ( $this->blocks as $slug ) {
			$type = $registry->get_registered( 'blokino/' . $slug );
			if ( ! $type ) {
				continue;
			}
			foreach ( (array) $type->editor_script_handles as $handle ) {
				wp_set_script_translations( $handle, 'blokino', $langs );
			}
		}
	}
}
