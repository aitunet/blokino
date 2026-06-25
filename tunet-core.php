<?php
/**
 * Plugin Name:       Tunet Core
 * Plugin URI:        https://tunetdesign.com/tunet-core
 * Description:       Engine of the Tunet ecosystem. Provides the shared infrastructure (native block extensions with tf* effects, custom blocks, the effects runtime and an options panel). Presentation lives in each theme; this plugin never hardcodes styles. Not sold separately.
 * Version:           0.1.1
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            TUNET Digital Agency
 * Author URI:        https://tunetdesign.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tunet
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
define( 'TUNET_CORE_VERSION', '0.1.1' );
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
require_once TUNET_CORE_PATH . 'admin/class-tunet-admin.php';
require_once TUNET_CORE_PATH . 'admin/class-tunet-demo.php';

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
		// i18n: cargar el text domain en el momento correcto.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Build / performance: assets de bloques del core separados por block,
		// para poder cargar solo lo que aparece en la página (§3, §10).
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );

		// Arranque de módulos del motor.
		add_action( 'plugins_loaded', array( $this, 'boot_modules' ) );
	}

	/**
	 * Carga el text domain del plugin.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'tunet', false, dirname( plugin_basename( TUNET_CORE_FILE ) ) . '/languages' );
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
 * Updates de themes Tunet (premium → se actualizan por EDD, no por wp.org).
 * ---------------------------------------------------------------------- */

/**
 * Evita el falso aviso de "actualización disponible" para los themes Tunet.
 *
 * WordPress compara cada theme instalado con el directorio de wp.org POR SLUG;
 * como existen themes públicos llamados "aurora", "ember", etc., wp.org ofrece
 * "su" versión como update. Los themes Tunet son premium (fuera de wp.org) y se
 * actualizan por EDD Software Licensing (CLAUDE.md §12); quitamos del transient
 * de updates cualquier theme marcado como nuestro.
 *
 * Vive en el motor (no en cada theme) a propósito: el motor SIEMPRE está activo,
 * así cubre también los themes Tunet INACTIVOS —cuyo functions.php no se carga—
 * y cualquier theme Tunet futuro sin código nuevo. "Lo nuestro" se identifica por
 * el header Update URI o Author URI = tunetdesign.com.
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
	foreach ( array_keys( $value->response ) as $slug ) {
		$theme = wp_get_theme( $slug );
		if ( ! $theme->exists() ) {
			continue;
		}
		$signals = (string) $theme->get( 'UpdateURI' ) . ' ' . (string) $theme->get( 'AuthorURI' );
		if ( false !== stripos( $signals, 'tunetdesign.com' ) ) {
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
					__( 'Tunet Core requiere WordPress %1$s o superior y PHP %2$s o superior.', 'tunet' ),
					TUNET_CORE_MIN_WP,
					TUNET_CORE_MIN_PHP
				)
			),
			esc_html__( 'Activación de Tunet Core', 'tunet' ),
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
