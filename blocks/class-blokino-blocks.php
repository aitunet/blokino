<?php
/**
 * Blocks PROPIOS del motor y helpers que comparten sus render.php.
 *
 * Solo se crean blocks donde el core no llega (CLAUDE.md §4.2). No se duplican
 * párrafos, botones ni columnas del core. Cada uno vive en blocks/<slug>/ con
 * su block.json; WordPress carga su CSS/JS solo si el block aparece en la
 * página, y los carruseles encolan Swiper desde su render
 * (Blokino_Runtime::enqueue_carousel()).
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

if ( ! function_exists( 'blokino_bg_tone' ) ) {
	/**
	 * Tono de un color de fondo, para que el texto de una sección contraste en
	 * cualquier theme. Una banda pensada para un theme concreto (Aurora pinta sus
	 * cifras con `var(--tnt-color-text)` de fondo y pone el texto claro desde su
	 * propio CSS) se leía negro sobre negro al cambiar de theme (pruebas
	 * 2026-11-19, Aurora → TT5).
	 *
	 * @param string $color Valor de bgColor ya validado.
	 * @return string 'inverse' (fondo = color del texto), 'dark' o '' (claro o desconocido).
	 */
	function blokino_bg_tone( $color ) {
		$color = strtolower( trim( (string) $color ) );
		if ( preg_match( '/^var\(\s*--tnt-color-text\s*[,)]/', $color ) ) {
			return 'inverse';
		}
		if ( preg_match( '/^var\(\s*--tnt-color-ink\s*[,)]/', $color ) ) {
			return 'dark';
		}
		if ( ! preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{1,2})?$/', $color, $m ) ) {
			return '';
		}
		$hex = 3 === strlen( $m[1] ) ? preg_replace( '/(.)/', '$1$1', $m[1] ) : $m[1];
		$lum = 0;
		foreach ( array( 0.2126, 0.7152, 0.0722 ) as $i => $weight ) {
			$c    = hexdec( substr( $hex, $i * 2, 2 ) ) / 255;
			$lum += $weight * ( $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 ) );
		}
		// Por debajo de 0.18 el texto blanco contrasta más que el negro.
		return $lum < 0.18 ? 'dark' : '';
	}
}

if ( ! function_exists( 'blokino_carousel_a11y_attrs' ) ) {
	/**
	 * Etiquetas accesibles de los carruseles (E-08), como data-* del wrapper.
	 * carousel.js las pasa al módulo a11y de Swiper y al botón de pausa, así
	 * se anuncian en el idioma del sitio en vez del inglés por defecto de Swiper.
	 *
	 * @return array<string,string>
	 */
	function blokino_carousel_a11y_attrs() {
		return array(
			'data-prev-label'   => __( 'Previous slide', 'blokino' ),
			'data-next-label'   => __( 'Next slide', 'blokino' ),
			/* translators: {{index}} is replaced by the slide number; keep it as is. */
			'data-bullet-label' => __( 'Go to slide {{index}}', 'blokino' ),
			'data-pause-label'  => __( 'Pause autoplay', 'blokino' ),
			'data-play-label'   => __( 'Play autoplay', 'blokino' ),
		);
	}
}

if ( ! function_exists( 'blokino_carousel_wrapper_args' ) ) {
	/**
	 * Atributos del contenedor Swiper, comunes a Content slider y Testimonials
	 * (O-S-08: estaban copiados en los dos render.php). Incluye los mensajes de
	 * accesibilidad. El resultado va a get_block_wrapper_attributes().
	 *
	 * @param string $class Clases propias del bloque (se añade "blokino-carousel swiper").
	 * @param array  $o     effect, spv, pspv, space, speed, delay (números/cadenas)
	 *                      y loop, autoplay, pagination, navigation (bool).
	 * @return array<string,string>
	 */
	function blokino_carousel_wrapper_args( $class, array $o ) {
		return array(
			'class'               => $class,
			'style'               => '--tnt-carousel-spv:' . $o['pspv'] . ';',
			'data-swiper-base'    => esc_url( BLOKINO_URL . 'runtime/vendor/swiper/' ),
			'data-effect'         => $o['effect'],
			'data-spv'            => (string) $o['spv'],
			'data-space'          => (string) $o['space'],
			'data-speed'          => (string) $o['speed'],
			'data-loop'           => $o['loop'] ? '1' : '0',
			'data-autoplay'       => $o['autoplay'] ? '1' : '0',
			'data-autoplay-delay' => (string) $o['delay'],
			'data-pagination'     => $o['pagination'] ? '1' : '0',
			'data-navigation'     => $o['navigation'] ? '1' : '0',
		) + blokino_carousel_a11y_attrs();
	}
}

if ( ! function_exists( 'blokino_css_url' ) ) {
	/**
	 * Único punto para meter una URL dentro de `url("…")` en un inline-style.
	 *
	 * esc_url() conserva `)` y `;`, y su `&#039;` el navegador lo decodifica antes
	 * de parsear el CSS: una URL de atributo podía cerrar el url() y añadir
	 * declaraciones (position:fixed sobre toda la página). Aquí se codifica todo
	 * lo que puede terminar la cadena o la función. El llamador la envuelve en
	 * comillas dobles y pasa el style entero por esc_attr().
	 *
	 * @param string $url URL tal cual viene del atributo o de la biblioteca.
	 * @return string URL segura dentro de url("…"), o '' si no es http(s).
	 */
	function blokino_css_url( $url ) {
		$url = esc_url_raw( (string) $url, array( 'http', 'https' ) );
		if ( '' === $url ) {
			return '';
		}
		return str_replace(
			array( '\\', '"', "'", '(', ')', ';', ' ', "\n", "\r", "\t" ),
			array( '%5C', '%22', '%27', '%28', '%29', '%3B', '%20', '', '', '' ),
			$url
		);
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
 * Registra los blocks propios del motor y los helpers de su editor.
 */
class Blokino_Blocks {

	/**
	 * Cablea el registro de blocks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		// O-U-05: los helpers del editor se REGISTRAN; los cargan como dependencia
		// los edit.asset.php de los bloques que los usan (icon, marquee, carruseles).
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_icons' ), 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_repeater_control' ), 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_carousel_editor' ), 1 );
		add_action( 'wp_ajax_' . self::ICONS_ACTION, array( $this, 'serve_icons' ) );
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

	/** Acción AJAX que sirve el set de iconos como script cacheable. */
	const ICONS_ACTION = 'blokino_icons';

	/**
	 * El set de iconos en JSON (filtrable con blokino_icon_set).
	 *
	 * @return string
	 */
	private static function icons_json() {
		require_once BLOKINO_PATH . 'blocks/icon/icons.php';
		return (string) wp_json_encode( blokino_icon_set() );
	}

	/**
	 * Expone el set curado de iconos al editor (para el picker de blokino/icon).
	 *
	 * O-U-05: antes iban ~36 KB de JSON inline en cada carga del editor. Ahora es
	 * un script aparte, versionado por el hash del set (cambia si un theme añade
	 * iconos con el filtro) y cacheado por el navegador. No puede ser un archivo
	 * estático justamente por ese filtro.
	 */
	public function localize_icons() {
		$json = self::icons_json();
		$src  = add_query_arg(
			array(
				'action' => self::ICONS_ACTION,
				'v'      => substr( md5( $json ), 0, 12 ),
			),
			admin_url( 'admin-ajax.php' )
		);
		// Versión null: la URL ya lleva el hash; un ?ver= extra no aporta nada.
		wp_register_script( 'blokino-icons-data', $src, array(), null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- versionado por el hash del set en la URL.

		// Helper JS compartido (espejo de blokino_icon_svg) para los pickers.
		wp_register_script(
			'blokino-icon-svg',
			BLOKINO_URL . 'blocks/icon/icon-svg.js',
			array( 'blokino-icons-data' ),
			BLOKINO_VERSION,
			false
		);
	}

	/**
	 * Sirve el set de iconos como JavaScript (admin-ajax, solo con sesión).
	 * Datos públicos de solo lectura: sin nonce, para que la URL sea cacheable.
	 */
	public function serve_icons() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			status_header( 403 );
			exit;
		}
		header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		header( 'Cache-Control: private, max-age=31536000, immutable' );
		echo 'window.blokinoIcons = ' . self::icons_json() . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON de wp_json_encode servido como script.
		exit;
	}

	/**
	 * Encola el componente RepeaterControl compartido (editor). Los edit.js de los
	 * bloques que lo usan lo declaran como dependencia en su edit.asset.php.
	 */
	public function enqueue_repeater_control() {
		$ver = blokino_asset_version( 'blocks/shared/repeater-control.js' );
		wp_register_script(
			'blokino-repeater-control',
			BLOKINO_URL . 'blocks/shared/repeater-control.js',
			array( 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ),
			$ver,
			false
		);

		// Este script NO se registra desde un block.json, así que nadie le cablea las
		// traducciones: hay que declararlas aquí o sus labels salen siempre en inglés.
		wp_set_script_translations( 'blokino-repeater-control', 'blokino', BLOKINO_PATH . 'languages' );

		$css_ver = blokino_asset_version( 'blocks/shared/repeater.css' );
		// El CSS (2 KB, barra lateral) sigue encolado: un estilo no puede colgar
		// como dependencia de un script.
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
		$editor_ver = blokino_asset_version( 'blocks/shared/carousel-editor.js' );
		wp_register_script(
			'blokino-carousel-editor',
			BLOKINO_URL . 'blocks/shared/carousel-editor.js',
			array( 'wp-element', 'wp-server-side-render' ),
			$editor_ver,
			false
		);

		$carousel_css = add_query_arg( 'ver', blokino_asset_version( 'runtime/carousel.css' ), BLOKINO_URL . 'runtime/carousel.css' );
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
