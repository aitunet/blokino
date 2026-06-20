<?php
/**
 * Blocks PROPIOS del motor (STUB — PIEZA 1).
 *
 * Solo se crean blocks donde el core no llega (CLAUDE.md §4.2). No se duplican
 * párrafos, botones ni columnas del core.
 *
 * Blocks previstos (cada uno con su block.json y carga de assets condicional
 * vía should_load_separate_core_block_assets, ya activado en el orquestador):
 *   - tunet/slider        (wrapper de Swiper.js)
 *   - tunet/section       (backgrounds avanzados, overlay, shape divider)
 *   - tunet/marquee       (banda infinita)
 *   - tunet/counter       (número animado on-scroll)
 *   - tunet/before-after  (comparador de imágenes)
 *
 * Cada block dinámico encolará el runtime en su render llamando a
 * Tunet_Core_Runtime::enqueue() — así nada se carga si el block no aparece.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra (en el futuro) los blocks propios del motor.
 */
class Tunet_Core_Blocks {

	/**
	 * Cablea el registro de blocks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_logos' ) );
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
		'slider',
		'section',
		'logo',
	);

	/**
	 * Expone las URLs de los logos al editor (para el preview de tunet/logo).
	 */
	public function localize_logos() {
		if ( ! class_exists( 'Tunet_Core_Admin' ) ) {
			return;
		}
		$main = Tunet_Core_Admin::logo_id( 'main' );
		$alt  = Tunet_Core_Admin::logo_id( 'alt' );
		$data = array(
			'main' => $main ? wp_get_attachment_image_url( $main, 'full' ) : '',
			'alt'  => $alt ? wp_get_attachment_image_url( $alt, 'full' ) : '',
		);
		wp_register_script( 'tunet-logos-data', false, array(), TUNET_CORE_VERSION, false );
		wp_enqueue_script( 'tunet-logos-data' );
		wp_add_inline_script( 'tunet-logos-data', 'window.tunetLogos = ' . wp_json_encode( $data ) . ';', 'before' );
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
			$dir = TUNET_CORE_PATH . 'blocks/' . $slug;
			if ( is_dir( $dir ) && file_exists( $dir . '/block.json' ) ) {
				register_block_type( $dir );
			}
		}
	}
}
