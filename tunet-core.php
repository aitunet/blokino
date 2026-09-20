<?php
/**
 * Plugin Name:       Tunet Core
 * Plugin URI:        https://tunetdesign.com/docs/tunet-core/
 * Description:       Engine of the Tunet ecosystem. Provides the shared infrastructure (native block extensions with tf* effects, custom blocks, the effects runtime and an options panel). Presentation lives in each theme; this plugin never hardcodes styles. Not sold separately.
 * Version:           0.1.51
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            TUNET Design
 * Author URI:        https://tunetdesign.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tunet-core
 * Domain Path:       /languages
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No acceso directo.
}

/* -------------------------------------------------------------------------
 * Constantes del plugin
 * ---------------------------------------------------------------------- */
define( 'TUNET_CORE_VERSION', '0.1.51' );
define( 'TUNET_CORE_FILE', __FILE__ );
define( 'TUNET_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'TUNET_CORE_URL', plugin_dir_url( __FILE__ ) );

/* Versiones mínimas soportadas (se validan en activación). */
define( 'TUNET_CORE_MIN_WP', '6.6' );
define( 'TUNET_CORE_MIN_PHP', '7.4' );

/* -------------------------------------------------------------------------
 * Carga de los módulos del motor (cada uno vive en su carpeta, §9).
 * De momento son andamiaje: registran sus hooks pero aún no aplican efectos.
 * ---------------------------------------------------------------------- */
require_once TUNET_CORE_PATH . 'extensions/class-tunet-extensions.php';
require_once TUNET_CORE_PATH . 'blocks/class-tunet-blocks.php';
require_once TUNET_CORE_PATH . 'runtime/class-tunet-runtime.php';
require_once TUNET_CORE_PATH . 'content/class-tunet-content.php';
require_once TUNET_CORE_PATH . 'content/content-types.php';
require_once TUNET_CORE_PATH . 'admin/class-tunet-admin.php';
require_once TUNET_CORE_PATH . 'admin/class-tunet-demo.php';
require_once TUNET_CORE_PATH . 'admin/class-tunet-themes.php';
require_once TUNET_CORE_PATH . 'admin/class-tunet-welcome.php';

/**
 * Orquestador principal del motor.
 *
 * Singleton que cablea los módulos y los ajustes globales del plugin.
 * Mantener delgado: la lógica de cada área vive en su módulo.
 */
final class Tunet_Core {

	/**
	 * Instancia única.
	 *
	 * @var Tunet_Core|null
	 */
	private static $instance = null;

	/**
	 * Módulo de runtime de efectos (carga condicional de assets).
	 *
	 * @var Tunet_Core_Runtime
	 */
	public $runtime;

	/**
	 * Devuelve (creándola si hace falta) la instancia única.
	 *
	 * @return Tunet_Core
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor privado: cablea hooks globales y arranca módulos.
	 */
	private function __construct() {
		/*
		 * i18n: NO hace falta load_plugin_textdomain(). Está desaconsejado desde WP
		 * 4.6 y el propio Plugin Check lo marca. WordPress resuelve el dominio solo,
		 * just-in-time: los paquetes de idioma de translate.wordpress.org por el slug
		 * (que ES el text domain, 'tunet-core'), y los .mo empaquetados por la
		 * cabecera Domain Path del plugin.
		 *
		 * MEDIDO, no supuesto (WP 7.0.2): quitando la llamada y forzando es_ES, los
		 * 428 strings siguen traducidos y is_textdomain_loaded('tunet-core') sigue
		 * devolviendo true. La prueba llevaba un testigo dentro del propio archivo
		 * para descartar que OPcache estuviera sirviendo el código anterior.
		 */

		// Build / performance: assets de bloques del core separados por block,
		// para poder cargar solo lo que aparece en la página (§3, §10).
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );

		// Arranque de módulos del motor.
		add_action( 'plugins_loaded', array( $this, 'boot_modules' ) );
	}

	/**
	 * Instancia los módulos del motor.
	 *
	 * El orden importa: el runtime registra los assets que luego consumirán
	 * tanto las extensiones tf* como los blocks propios.
	 */
	public function boot_modules() {
		$this->runtime = new Tunet_Core_Runtime();

		// Andamiaje: registran sus hooks pero todavía no inyectan efectos.
		new Tunet_Core_Extensions();
		new Tunet_Core_Blocks();

		// Tipos de contenido compartidos (portfolio). Front + admin.
		new Tunet_Core_Content();

		if ( is_admin() ) {
			new Tunet_Core_Admin();
			new Tunet_Core_Demo();
			new Tunet_Core_Themes();
			new Tunet_Core_Welcome();
		}
	}
}

/**
 * Acceso global conveniente al motor.
 *
 * @return Tunet_Core
 */
function tunet_core() {
	return Tunet_Core::instance();
}

// Arranca el motor.
tunet_core();

/**
 * Helper de plantilla: devuelve el markup del logo de marca.
 *
 * Para usar en plantillas de theme. El logo vive en el motor (persiste al
 * cambiar de theme); el theme decide dónde colocarlo. Retina vía srcset.
 *
 * @param string $variant 'main' | 'alt'.
 * @return string HTML del logo (vacío si no hay logo configurado).
 */
function tunet_core_logo( $variant = 'main' ) {
	if ( ! class_exists( 'Tunet_Core_Admin' ) ) {
		return '';
	}
	$id = Tunet_Core_Admin::logo_id( 'alt' === $variant ? 'alt' : 'main' );
	if ( ! $id ) {
		return '';
	}
	return wp_get_attachment_image(
		$id,
		'full',
		false,
		array(
			'class' => 'tunet-logo__img',
			'alt'   => get_bloginfo( 'name' ),
		)
	);
}

/* -------------------------------------------------------------------------
 * Layout de contenido (sidebar / full-width) — gobernado por el motor.
 *
 * Una sola opción del motor decide si las ENTRADAS y los ARCHIVOS llevan
 * sidebar o van a ancho completo; las PÁGINAS quedan fuera (usan sus propias
 * plantillas de página). Vive en el motor (no en cada theme) para que TODOS los
 * themes Tunet reaccionen al mismo ajuste sin duplicar lógica (CLAUDE.md §13).
 * El motor solo emite clases en el <body>; el theme decide cómo dibujar el
 * sidebar (parte `sidebar` + CSS). Un theme sin sidebar degrada con dignidad:
 * queda a ancho completo (§12).
 * ---------------------------------------------------------------------- */

/**
 * Layout de contenido para la petición actual.
 *
 * @return string 'sidebar' | 'full'.
 */
function tunet_core_content_layout() {
	if ( is_admin() || ! class_exists( 'Tunet_Core_Admin' ) ) {
		return 'full';
	}

	// Entradas de blog (no páginas, no portada estática, no CPTs con layout propio).
	if ( is_singular( 'post' ) ) {
		return 'sidebar' === Tunet_Core_Admin::get( 'layout_post_single' ) ? 'sidebar' : 'full';
	}

	// Índice de blog + archivos de taxonomía/fecha/autor de entradas + búsqueda.
	if ( is_home() || is_category() || is_tag() || is_date() || is_author() || is_search() ) {
		return 'sidebar' === Tunet_Core_Admin::get( 'layout_archive' ) ? 'sidebar' : 'full';
	}

	return 'full';
}

/**
 * Añade las clases de layout al <body> para que el CSS del theme conmute
 * entre rejilla-con-sidebar y ancho completo.
 *
 * @param string[] $classes Clases del body.
 * @return string[]
 */
function tunet_core_body_layout_class( $classes ) {
	$classes[] = ( 'sidebar' === tunet_core_content_layout() ) ? 'tunet-has-sidebar' : 'tunet-no-sidebar';
	return $classes;
}
add_filter( 'body_class', 'tunet_core_body_layout_class' );

/* -------------------------------------------------------------------------
 * Helpers de front-end site-wide (theme-agnósticos; §0.1: el mecanismo vive en
 * el motor, no duplicado en cada theme).
 * ---------------------------------------------------------------------- */

/**
 * Procesa shortcodes dentro de los bloques core/shortcode renderizados desde
 * plantillas FSE. Las plantillas de bloques NO ejecutan the_content, así que
 * [contact-form-7] (y cualquier shortcode) se imprimiría en crudo. Corre por
 * request → nonce de CF7 fresco. Mecanismo puro e idéntico en todo theme → vive
 * en el motor (antes duplicado en los cinco functions.php).
 *
 * @param string $content HTML renderizado del bloque.
 * @param array  $block   Bloque parseado.
 * @return string
 */
function tunet_core_render_shortcode_blocks( $content, $block ) {
	if ( isset( $block['blockName'] ) && 'core/shortcode' === $block['blockName'] && false !== strpos( $content, '[' ) ) {
		return do_shortcode( $content );
	}
	return $content;
}
add_filter( 'render_block', 'tunet_core_render_shortcode_blocks', 10, 2 );

/**
 * Meta description de fallback en <head> cuando ningún plugin SEO la gestiona:
 * el excerpt en vistas singulares, si no el tagline del sitio. Theme-agnóstico →
 * vive en el motor (antes duplicado en cada theme). Degrada con dignidad (§12):
 * si hay un plugin SEO activo, no hace nada.
 */
function tunet_core_meta_description() {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return; // Un plugin SEO dedicado es dueño de la descripción.
	}

	if ( is_singular() && ! is_front_page() && has_excerpt() ) {
		$desc = get_the_excerpt();
	} else {
		$desc = get_bloginfo( 'description', 'display' );
	}

	$desc = trim( wp_strip_all_tags( (string) $desc ) );
	if ( '' === $desc ) {
		return;
	}

	printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( wp_trim_words( $desc, 30, '' ) ) );
}
add_action( 'wp_head', 'tunet_core_meta_description', 1 );

/* -------------------------------------------------------------------------
 * Updates de themes Tunet (premium → se actualizan por EDD, no por wp.org).
 * ---------------------------------------------------------------------- */

/**
 * Evita el falso aviso de "actualización disponible" para los themes Tunet.
 *
 * WordPress compara cada theme instalado con el directorio de wp.org POR SLUG;
 * como existen themes públicos llamados "aurora", "ember", etc., wp.org ofrece
 * "su" versión como update. Los themes Tunet PREMIUM son ajenos a wp.org y se
 * actualizan por EDD Software Licensing vía su propio updater (`update_themes_
 * <host>`, lib/license-client/class-tunet-license-updater.php; CLAUDE.md §12);
 * de la transient de updates solo quitamos la entrada FALSA que pone wp.org
 * (se detecta porque su `package`/`url` resuelve a un host wordpress.org), nunca
 * la que produjo el updater propio del theme (apunta a la tienda/EDD).
 *
 * Vive en el motor (no en cada theme) a propósito: el motor SIEMPRE está activo,
 * así cubre también los themes Tunet INACTIVOS —cuyo functions.php no se carga—
 * y cualquier theme Tunet futuro sin código nuevo. "Lo nuestro premium" se
 * identifica solo por el header Update URI = tunetdesign.com; un theme Tunet
 * GRATIS distribuido por wp.org (p. ej. Tunet Starter) no declara ese header y
 * debe seguir recibiendo sus updates del directorio con normalidad.
 *
 * Degrada con dignidad (§12): sin themes Tunet instalados no toca nada; no
 * desactiva funciones ni muestra avisos → cumple las guidelines de wp.org.
 *
 * @param mixed $value Transient `site_transient_update_themes`.
 * @return mixed
 */
function tunet_core_suppress_theme_updates( $value ) {
	if ( ! isset( $value->response ) || ! is_array( $value->response ) ) {
		return $value;
	}
	foreach ( $value->response as $slug => $entry ) {
		$theme = wp_get_theme( $slug );
		if ( ! $theme->exists() ) {
			continue;
		}
		// Only themes that update from tunetdesign.com (premium, EDD). A theme
		// hosted on wordpress.org (Tunet Starter) has no Update URI → keep it.
		$update_uri = (string) $theme->get( 'UpdateURI' );
		if ( '' === $update_uri || false === stripos( $update_uri, 'tunetdesign.com' ) ) {
			continue;
		}
		// Drop only what came from the directory (a public theme sharing the slug);
		// an entry the theme's own updater produced points elsewhere and stays.
		$entry = (array) $entry;
		$from  = '';
		foreach ( array( 'package', 'url' ) as $field ) {
			if ( ! empty( $entry[ $field ] ) ) {
				$from = (string) wp_parse_url( (string) $entry[ $field ], PHP_URL_HOST );
				break;
			}
		}
		if ( '' === $from || preg_match( '/(^|\.)wordpress\.org$/i', $from ) ) {
			unset( $value->response[ $slug ] );
		}
	}
	return $value;
}
add_filter( 'site_transient_update_themes', 'tunet_core_suppress_theme_updates' );

/* -------------------------------------------------------------------------
 * Activación / desactivación seguras.
 * ---------------------------------------------------------------------- */

/**
 * En activación: validar entorno mínimo. Si no se cumple, abortar limpio.
 */
function tunet_core_activate() {
	if ( version_compare( PHP_VERSION, TUNET_CORE_MIN_PHP, '<' )
		|| version_compare( get_bloginfo( 'version' ), TUNET_CORE_MIN_WP, '<' ) ) {

		deactivate_plugins( plugin_basename( TUNET_CORE_FILE ) );
		wp_die(
			esc_html(
				sprintf(
					/* translators: 1: required WordPress version, 2: required PHP version. */
					__( 'Tunet Core requires WordPress %1$s or newer and PHP %2$s or newer.', 'tunet-core' ),
					TUNET_CORE_MIN_WP,
					TUNET_CORE_MIN_PHP
				)
			),
			esc_html__( 'Tunet Core activation', 'tunet-core' ),
			array( 'back_link' => true )
		);
	}
}
register_activation_hook( __FILE__, 'tunet_core_activate' );

/**
 * En desactivación: limpieza ligera. No borra datos del usuario.
 */
function tunet_core_deactivate() {
	// Placeholder: flush de caches transitorios del motor si los hubiera.
}
register_deactivation_hook( __FILE__, 'tunet_core_deactivate' );
