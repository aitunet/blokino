<?php
/**
 * Extensiones tf* sobre blocks NATIVOS — FASE A (tfAnimation = "fade-up").
 *
 * Vía principal del motor (CLAUDE.md §4.1): se EXTIENDEN los blocks del core,
 * no se recrean. El lado editor (atributos, panel "Blokino Effects", inyección en
 * el save) vive en extensions/effects-editor.js. Esta clase solo lo encola.
 *
 * La inyección PHP equivalente para blocks DINÁMICOS vive en
 * Blokino_Runtime::render_block_effects() (paso 4 del §4.1).
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encola las extensiones de efectos del editor.
 */
class Blokino_Extensions {

	const EDITOR_HANDLE = 'blokino-effects-editor';

	/**
	 * Cablea los hooks de las extensiones.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Encola el script que registra los atributos tf* y el panel del editor.
	 */
	public function enqueue_editor_assets() {
		$rel  = 'extensions/effects-editor.js';
		$path = BLOKINO_PATH . $rel;
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : BLOKINO_VERSION;

		wp_enqueue_script(
			self::EDITOR_HANDLE,
			BLOKINO_URL . $rel,
			array(
				'wp-blocks',
				'wp-hooks',
				'wp-compose',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-i18n',
			),
			$ver,
			true
		);

		// i18n del lado JS (cadenas con dominio 'blokino').
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::EDITOR_HANDLE, 'blokino', BLOKINO_PATH . 'languages' );
		}
	}
}
