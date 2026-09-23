<?php
/**
 * Runtime de efectos — registro, carga CONDICIONAL e inyección PHP — FASE A.
 *
 * Filosofía (CLAUDE.md §3, §4.3, §10):
 *  - Nada se carga "por si acaso". El runtime se ENCOLA solo si la página
 *    contiene al menos un block con un efecto tf* activo.
 *  - El CSS de efectos es agnóstico: lee los tokens --tnt-* del theme activo.
 *  - Sin librerías de animación de terceros: todo el runtime es vanilla
 *    (IntersectionObserver + rAF + CSS). GSAP se retiró (licencia no GPL,
 *    incompatible con wordpress.org); cinematic-zoom es un scrub propio.
 *
 * Patrón de carga (no bloquea el render):
 *  - <head>: SOLO un snippet inline mínimo (sin red) que añade la clase
 *    .blokino-tf-ready a <html>. Es el gate anti-FOUC: el estado oculto del CSS
 *    depende de esa clase, así que el contenido arranca oculto desde el primer
 *    paint sin necesidad de descargar el runtime.
 *  - footer: el runtime real (IntersectionObserver) con strategy "defer", que
 *    no bloquea el parseo. Revela los elementos al entrar en viewport.
 *  - Si el runtime nunca carga, el contenido queda visible (resiliencia) y no
 *    hay CLS: solo se animan opacity/transform, nunca display.
 *
 * Detección: se pre-escanea el contenido del objeto consultado en
 * wp_enqueue_scripts. Como red de seguridad, render_block también encola al
 * renderizar un block con efecto (cubre blocks dinámicos / contextos no
 * singulares); en ese caso el snippet inline puede no llegar al <head> y la
 * clase la añade defensivamente el propio runtime.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestiona los assets del runtime de efectos y la inyección server-side.
 */
class Blokino_Runtime {

	const STYLE_HANDLE  = 'blokino-effects';
	const SCRIPT_HANDLE = 'blokino-runtime';
	const TOKENS_HANDLE = 'blokino-tokens';

	const CAROUSEL_STYLE  = 'blokino-carousel';
	const CAROUSEL_SCRIPT = 'blokino-carousel';

	/** Curvas de easing válidas (mapean a tokens --tnt-ease-*). */
	const EASINGS = array( 'expo', 'power3', 'spring', 'circ' );

	/** Animaciones de entrada válidas (tfAnimation). Espeja el SelectControl del editor. */
	const ANIMATIONS = array( 'fade-up', 'clip-reveal', 'mask-up', 'blur-in', 'scale-in', 'slide-left', 'slide-right', 'text-stagger', 'text-fill' );

	/** Efectos hover válidos (LOTE 2). */
	const HOVERS = array( 'lift', 'glow', 'tilt', 'magnetic', 'underline-grow', 'image-zoom' );

	/** Efectos en scroll válidos (LOTE 3). 'cinematic-zoom' = scrub suavizado en rAF (vanilla). */
	const SCROLLS = array( 'parallax', 'sticky-pin', 'reveal-on-scroll', 'progress', 'zoom', 'cinematic-zoom' );

	/** Modos de mezcla válidos (LOTE 3). */
	const BLENDS = array( 'multiply', 'screen', 'overlay', 'difference', 'exclusion', 'luminosity' );

	/** Efectos de borde válidos (LOTE 3). */
	const BORDER_FX = array( 'gradient', 'conic-rotate' );

	/**
	 * Evita encolar dos veces en la misma request.
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Cablea registro, detección e inyección.
	 */
	public function __construct() {
		// Registrar (no encolar) en front-end.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 10 );

		// Detección temprana (antes de wp_head) → decide si hay que cargar.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_runtime' ), 20 );

		// Snippet inline mínimo en <head>: gate anti-FOUC, sin red.
		add_action( 'wp_head', array( $this, 'print_bootstrap' ), 1 );

		// render_block es la ÚNICA vía de inyección de data-tf-* (estáticos y
		// dinámicos): el save() produce markup limpio y el markup guardado nunca
		// se toca; este filtro también es la red de seguridad de carga (§4.1, paso 4).
		add_filter( 'render_block', array( $this, 'render_block_effects' ), 10, 2 );

		// Baseline de tokens --tnt-*: fallback neutral en cascade layer para que
		// el motor degrade con dignidad SIN un theme Tunet (§12, regla 1).
		// Prioridad 5: antes que los overrides de branding (que deben ganar).
		// enqueue_block_assets cubre front + iframe del editor.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_token_defaults' ), 5 );

		// Branding global (overrides --tnt-* + Google Fonts) en front y editor.
		// Independiente del toggle de efectos. enqueue_block_assets cubre ambos.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_branding' ) );
	}

	/**
	 * Encola el baseline de tokens --tnt-* del motor (front + editor).
	 *
	 * Fallback neutral y theme-agnóstico en una @layer: cualquier theme (Tunet o
	 * no) que defina --tnt-* en :root lo sobre-escribe sin importar el orden de
	 * carga. Garantiza que los efectos y bloques del core se vean bien aun sin un
	 * theme Tunet activo (CLAUDE.md §12, regla 1). Es un archivo pequeño y se
	 * carga siempre: si un theme lo redefine, queda inerte pero sin coste real.
	 */
	public function enqueue_token_defaults() {
		wp_enqueue_style(
			self::TOKENS_HANDLE,
			BLOKINO_URL . 'runtime/tnt-defaults.css',
			array(),
			self::asset_version( 'runtime/tnt-defaults.css' )
		);
	}

	/**
	 * Inyecta los overrides de branding y carga las Google Fonts elegidas.
	 *
	 * El theme define los tokens por defecto; aquí solo se añaden los overrides
	 * que el usuario haya configurado en el panel (vacío = nada). Aplica tanto
	 * en el front como en el iframe del editor.
	 */
	public function enqueue_branding() {
		if ( ! class_exists( 'Blokino_Admin' ) ) {
			return;
		}

		$settings = Blokino_Admin::get_settings();

		$fonts_url = Blokino_Admin::fonts_url( $settings );
		if ( $fonts_url ) {
			wp_enqueue_style( 'blokino-fonts', $fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- URL versionada por Google.
		}

		$css = Blokino_Admin::branding_css( $settings );
		if ( $css ) {
			if ( ! wp_style_is( 'blokino-branding', 'registered' ) ) {
				// Handle sin fuente: solo transporta el wp_add_inline_style de abajo.
				// La versión no cambia nada aquí, pero se declara porque un registro
				// sin versión es un aviso de los estándares y no merece una excepción.
				wp_register_style( 'blokino-branding', false, array(), BLOKINO_VERSION );
			}
			wp_enqueue_style( 'blokino-branding' );
			wp_add_inline_style( 'blokino-branding', $css );
		}
	}

	/* ---------------------------------------------------------------------
	 * Registro y encolado
	 * ------------------------------------------------------------------ */

	/**
	 * Registra los assets del runtime SIN encolarlos.
	 *
	 * El CSS va en el <head> (define el estado oculto). El script va en el
	 * footer con strategy "defer": no bloquea el parseo y se ejecuta tras él.
	 * El gate anti-FOUC no depende de este script, sino del snippet inline
	 * de print_bootstrap().
	 */
	public function register_assets() {
		wp_register_style(
			self::STYLE_HANDLE,
			BLOKINO_URL . 'runtime/effects.css',
			array(),
			self::asset_version( 'runtime/effects.css' )
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			BLOKINO_URL . 'runtime/effects.js',
			array(),
			self::asset_version( 'runtime/effects.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_register_style(
			self::CAROUSEL_STYLE,
			BLOKINO_URL . 'runtime/carousel.css',
			array(),
			self::asset_version( 'runtime/carousel.css' )
		);
		wp_register_script(
			self::CAROUSEL_SCRIPT,
			BLOKINO_URL . 'runtime/carousel.js',
			array(),
			self::asset_version( 'runtime/carousel.js' ),
			array( 'in_footer' => true )
		);
	}

	/**
	 * Imprime el bootstrap inline en el <head>: añade .blokino-tf-ready a <html>.
	 *
	 * Mínimo y síncrono, sin red. Solo se emite si el runtime está activo en
	 * esta página (carga condicional). Es lo único del runtime que toca el
	 * <head>; el estado oculto del CSS depende de esta clase, garantizando que
	 * el contenido arranca oculto sin descargar el runtime y que, si nada
	 * carga, permanece visible.
	 */
	public function print_bootstrap() {
		if ( ! self::$enqueued ) {
			return;
		}

		// Por la API de WP y no con un <script> literal (review de wp.org): mismo id, sigue síncrono en el <head>.
		wp_print_inline_script_tag(
			"document.documentElement.classList.add('blokino-tf-ready');",
			array( 'id' => 'blokino-tf-bootstrap' )
		);
	}

	/**
	 * Pre-escaneo (antes de wp_head) para encolar el runtime si hay un efecto
	 * activo: primero en el contenido consultado, luego en la PLANTILLA de bloques.
	 *
	 * Los efectos también pueden vivir en la plantilla FSE (héroes de single/
	 * archive) o sus parts, que `post_content` no cubre. Si no se pre-encola, el
	 * gate anti-FOUC `.blokino-tf-ready` y el `effects.css` no llegan al <head> a
	 * tiempo → parpadeo. La plantilla resuelta ya está disponible aquí: WP la fija
	 * en `$_wp_current_template_content` (locate_block_template) durante el
	 * template-loader, ANTES de incluir el canvas donde corre wp_head. (§4.3/§10.)
	 */
	public function maybe_enqueue_runtime() {
		if ( is_admin() || ! self::effects_enabled() ) {
			return;
		}

		$object = get_queried_object();
		if ( $object instanceof WP_Post && self::content_has_effects( $object->post_content ) ) {
			self::enqueue();
			return;
		}

		if ( self::template_has_effects() ) {
			self::enqueue();
		}
	}

	/**
	 * ¿La plantilla de bloques activa (y sus template parts) contienen un efecto
	 * tf* inline? Escanea la plantilla resuelta + las parts que referencia (un
	 * nivel), para que los efectos a nivel de plantilla (no en post_content)
	 * también emitan el gate anti-FOUC en el <head>.
	 *
	 * @return bool
	 */
	private static function template_has_effects() {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return false;
		}

		$content = isset( $GLOBALS['_wp_current_template_content'] ) ? (string) $GLOBALS['_wp_current_template_content'] : '';
		if ( '' === $content ) {
			return false;
		}

		if ( self::content_has_effects( $content ) ) {
			return true;
		}

		// Resolver las parts referenciadas (un nivel) y escanearlas también:
		// un hero con efectos podría vivir en un template part.
		if ( false === strpos( $content, 'wp:template-part' ) || ! function_exists( 'get_block_template' ) ) {
			return false;
		}
		$stylesheet = get_stylesheet();
		foreach ( parse_blocks( $content ) as $block ) {
			if ( 'core/template-part' !== ( $block['blockName'] ?? '' ) || empty( $block['attrs']['slug'] ) ) {
				continue;
			}
			$part = get_block_template( $stylesheet . '//' . $block['attrs']['slug'], 'wp_template_part' );
			if ( $part && ! empty( $part->content ) && self::content_has_effects( $part->content ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Encola el runtime bajo demanda. Idempotente.
	 */
	public static function enqueue() {
		if ( self::$enqueued ) {
			return;
		}

		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			wp_register_style( self::STYLE_HANDLE, BLOKINO_URL . 'runtime/effects.css', array(), BLOKINO_VERSION );
		}
		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			wp_register_script(
				self::SCRIPT_HANDLE,
				BLOKINO_URL . 'runtime/effects.js',
				array(),
				BLOKINO_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		self::$enqueued = true;
	}

	/**
	 * Encola el runtime de carrusel COMPARTIDO (Swiper). Lo llaman en su render
	 * los blocks de carrusel (blokino/testimonials, blokino/content-slider) → carga
	 * condicional: solo si un carrusel aparece en la página. Registro perezoso
	 * por si un render corre antes de register_assets().
	 */
	public static function enqueue_carousel() {
		if ( ! wp_style_is( self::CAROUSEL_STYLE, 'registered' ) ) {
			wp_register_style(
				self::CAROUSEL_STYLE,
				BLOKINO_URL . 'runtime/carousel.css',
				array(),
				self::asset_version( 'runtime/carousel.css' )
			);
		}
		if ( ! wp_script_is( self::CAROUSEL_SCRIPT, 'registered' ) ) {
			wp_register_script(
				self::CAROUSEL_SCRIPT,
				BLOKINO_URL . 'runtime/carousel.js',
				array(),
				self::asset_version( 'runtime/carousel.js' ),
				array( 'in_footer' => true )
			);
		}
		wp_enqueue_style( self::CAROUSEL_STYLE );
		wp_enqueue_script( self::CAROUSEL_SCRIPT );
	}

	/* ---------------------------------------------------------------------
	 * Detección de efectos en contenido
	 * ------------------------------------------------------------------ */

	/**
	 * ¿El contenido contiene algún block con un efecto tf* activo?
	 *
	 * @param string $content Contenido de bloques.
	 * @return bool
	 */
	private static function content_has_effects( $content ) {
		if ( empty( $content ) || ! has_blocks( $content ) ) {
			return false;
		}

		// Fast-path barato: los atributos se serializan como JSON en el
		// comentario del block; si no aparece ninguna clave, no hay efecto.
		if ( false === strpos( $content, '"tfAnimation"' )
			&& false === strpos( $content, '"tfHover"' )
			&& false === strpos( $content, '"tfScroll"' )
			&& false === strpos( $content, '"tfBlend"' )
			&& false === strpos( $content, '"tfBorderFx"' )
			&& false === strpos( $content, '"tfDisplay"' ) ) {
			return false;
		}

		return self::blocks_have_effects( parse_blocks( $content ) );
	}

	/**
	 * Recorre recursivamente los blocks buscando un efecto tf* activo.
	 *
	 * @param array $blocks Blocks parseados.
	 * @return bool
	 */
	private static function blocks_have_effects( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['attrs'] ) && self::attrs_have_effect( $block['attrs'] ) ) {
				return true;
			}
			if ( ! empty( $block['innerBlocks'] ) && self::blocks_have_effects( $block['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * ¿Está activado el motor de efectos? (ajuste global del admin).
	 *
	 * @return bool
	 */
	private static function effects_enabled() {
		if ( class_exists( 'Blokino_Admin' ) ) {
			return Blokino_Admin::effects_enabled();
		}
		return true;
	}

	/**
	 * ¿Los atributos de un block activan algún efecto tf*?
	 *
	 * Punto único de extensión a medida que crece el catálogo (scroll, blend…).
	 *
	 * @param array $attrs Atributos del block.
	 * @return bool
	 */
	private static function attrs_have_effect( $attrs ) {
		$keys = array( 'tfAnimation', 'tfHover', 'tfScroll', 'tfBlend', 'tfBorderFx', 'tfDisplay' );
		foreach ( $keys as $key ) {
			if ( ! empty( $attrs[ $key ] ) && 'none' !== $attrs[ $key ] ) {
				return true;
			}
		}
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Inyección server-side (paso 4 del §4.1)
	 * ------------------------------------------------------------------ */

	/**
	 * Inyecta data-tf-* en el markup renderizado de un block con efecto.
	 *
	 * Única vía de inyección (estáticos y dinámicos): el markup GUARDADO nunca
	 * se toca, así que no hay errores de validación de bloques en el editor.
	 * Los atributos viven en el comentario del block y se leen aquí. El valor
	 * del easing se construye con tokens --tnt-* (nada hardcodeado).
	 *
	 * Se inyecta siempre en la PRIMERA etiqueta (el wrapper propio del block);
	 * los data-tf-* de bloques anidados quedan más adentro y no interfieren.
	 *
	 * @param string $block_content HTML renderizado.
	 * @param array  $block         Block parseado.
	 * @return string
	 */
	public function render_block_effects( $block_content, $block ) {
		if ( ! self::effects_enabled() ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

		if ( ! self::attrs_have_effect( $attrs ) ) {
			return $block_content;
		}

		// Red de seguridad de carga (contextos que el pre-escaneo no cubre).
		self::enqueue();

		// Atributos data-* a inyectar en el wrapper del block.
		$data_atts = array();

		$animation = isset( $attrs['tfAnimation'] ) ? (string) $attrs['tfAnimation'] : '';
		if ( in_array( $animation, self::ANIMATIONS, true ) ) {
			$data_atts['data-tf-animation'] = $animation;
			if ( ! empty( $attrs['tfStagger'] ) && 'text-stagger' !== $animation ) {
				$data_atts['data-tf-stagger'] = (string) absint( $attrs['tfStagger'] );
			}
		}

		if ( 'text-fill' === $animation && ! empty( $attrs['tfFillAccent'] ) ) {
			$data_atts['data-tf-fill-accent'] = preg_replace( '/[^0-9,\s]/', '', (string) $attrs['tfFillAccent'] );
		}

		$hover = isset( $attrs['tfHover'] ) ? (string) $attrs['tfHover'] : '';
		if ( in_array( $hover, self::HOVERS, true ) ) {
			$data_atts['data-tf-hover'] = $hover;
		}

		$scroll = isset( $attrs['tfScroll'] ) ? (string) $attrs['tfScroll'] : '';
		if ( in_array( $scroll, self::SCROLLS, true ) ) {
			$data_atts['data-tf-scroll'] = $scroll;
		}

		$blend = isset( $attrs['tfBlend'] ) ? (string) $attrs['tfBlend'] : '';
		if ( in_array( $blend, self::BLENDS, true ) ) {
			$data_atts['data-tf-blend'] = $blend;
		}

		$border = isset( $attrs['tfBorderFx'] ) ? (string) $attrs['tfBorderFx'] : '';
		if ( in_array( $border, self::BORDER_FX, true ) ) {
			$data_atts['data-tf-border-fx'] = $border;
		}

		$display = isset( $attrs['tfDisplay'] ) ? (string) $attrs['tfDisplay'] : '';
		if ( 'outline' === $display ) {
			$data_atts['data-tf-display'] = $display;
		}

		if ( empty( $data_atts ) ) {
			return $block_content;
		}

		$style = self::build_style_vars( $attrs );

		return self::inject_into_first_tag( $block_content, $data_atts, $style );
	}

	/**
	 * Construye la cadena de CSS vars inline a partir de los atributos tf*.
	 *
	 * @param array $attrs Atributos del block.
	 * @return string Ej: "--tf-delay:200ms;--tf-ease:var(--tnt-ease-power3)".
	 */
	private static function build_style_vars( $attrs ) {
		$vars = array();

		if ( ! empty( $attrs['tfAnimDelay'] ) ) {
			$vars[] = '--tf-delay:' . absint( $attrs['tfAnimDelay'] ) . 'ms';
		}
		if ( ! empty( $attrs['tfAnimDuration'] ) ) {
			$vars[] = '--tf-duration:' . absint( $attrs['tfAnimDuration'] ) . 'ms';
		}
		if ( ! empty( $attrs['tfAnimEasing'] ) && in_array( $attrs['tfAnimEasing'], self::EASINGS, true ) ) {
			$vars[] = '--tf-ease:var(--tnt-ease-' . $attrs['tfAnimEasing'] . ')';
		}
		if ( ! empty( $attrs['tfStagger'] ) ) {
			$vars[] = '--tf-stagger:' . absint( $attrs['tfStagger'] ) . 'ms';
		}
		// Parallax: número sin unidad (lo consumen rAF y el calc() del CSS).
		if ( ! empty( $attrs['tfParallaxSpeed'] )
			&& isset( $attrs['tfScroll'] ) && 'parallax' === $attrs['tfScroll'] ) {
			$vars[] = '--tf-parallax-speed:' . absint( $attrs['tfParallaxSpeed'] );
		}

		// Parámetros del borde animado (solo si hay border-fx).
		// OJO: los nombres NO deben contener "border-color"/"border-width"/etc.,
		// porque WordPress aplica reglas globales :where([style*="border-color"])
		// que pondrían un borde sólido espurio al elemento. Usamos --tf-bw/bdur/bc*.
		if ( ! empty( $attrs['tfBorderFx'] ) && in_array( $attrs['tfBorderFx'], self::BORDER_FX, true ) ) {
			if ( ! empty( $attrs['tfBorderWidth'] ) ) {
				$vars[] = '--tf-bw:' . absint( $attrs['tfBorderWidth'] ) . 'px';
			}
			if ( ! empty( $attrs['tfBorderSpeed'] ) ) {
				$vars[] = '--tf-bdur:' . (float) $attrs['tfBorderSpeed'] . 's';
			}
			$c1 = self::sanitize_css_color( isset( $attrs['tfBorderColor1'] ) ? $attrs['tfBorderColor1'] : '' );
			if ( '' !== $c1 ) {
				$vars[] = '--tf-bc1:' . $c1;
			}
			$c2 = self::sanitize_css_color( isset( $attrs['tfBorderColor2'] ) ? $attrs['tfBorderColor2'] : '' );
			if ( '' !== $c2 ) {
				$vars[] = '--tf-bc2:' . $c2;
			}
		}

		return implode( ';', $vars );
	}

	/**
	 * Inserta atributos data-tf-* (y CSS vars) en la primera etiqueta del HTML.
	 *
	 * @param string $html       HTML del block.
	 * @param array  $data_atts  Pares nombre => valor de atributos a inyectar.
	 * @param string $style      Cadena de CSS vars inline (puede ir vacía).
	 * @return string
	 */
	private static function inject_into_first_tag( $html, $data_atts, $style ) {
		// Captura la primera etiqueta de apertura y su lista de atributos.
		if ( ! preg_match( '/<([a-zA-Z][\w:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/', $html, $m, PREG_OFFSET_CAPTURE ) ) {
			return $html;
		}

		$whole_tag  = $m[0][0];
		$tag_offset = $m[0][1];
		$tag_name   = $m[1][0];
		$attr_str   = $m[2][0];

		$inject = '';
		foreach ( $data_atts as $name => $value ) {
			$inject .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}

		if ( '' !== $style ) {
			if ( preg_match( '/\sstyle\s*=\s*"([^"]*)"/', $attr_str, $sm ) ) {
				$merged   = rtrim( $sm[1], ';' ) . ';' . $style;
				$attr_str = preg_replace( '/\sstyle\s*=\s*"[^"]*"/', ' style="' . esc_attr( $merged ) . '"', $attr_str, 1 );
			} else {
				$attr_str .= ' style="' . esc_attr( $style ) . '"';
			}
		}

		$new_tag = '<' . $tag_name . $inject . $attr_str . '>';

		return substr( $html, 0, $tag_offset ) . $new_tag . substr( $html, $tag_offset + strlen( $whole_tag ) );
	}

	/* ---------------------------------------------------------------------
	 * Utilidades
	 * ------------------------------------------------------------------ */

	/**
	 * Sanea un color CSS proveniente del color picker (hex o funcional).
	 *
	 * Acepta #hex (3/4/6/8) y rgb()/rgba()/hsl()/hsla(); cualquier otra cosa se
	 * descarta. Evita inyección en el atributo style.
	 *
	 * @param mixed $value Valor del atributo.
	 * @return string Color válido o cadena vacía.
	 */
	private static function sanitize_css_color( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}
		$value = trim( $value );
		if ( preg_match( '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Versión de un asset basada en filemtime (cache-busting en desarrollo).
	 *
	 * @param string $relative_path Ruta relativa al directorio del plugin.
	 * @return string
	 */
	private static function asset_version( $relative_path ) {
		$abs = BLOKINO_PATH . $relative_path;
		return file_exists( $abs ) ? (string) filemtime( $abs ) : BLOKINO_VERSION;
	}
}
