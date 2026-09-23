<?php
/**
 * Extensiones tf* sobre blocks NATIVOS — FASE A (tfAnimation = "fade-up").
 *
 * Vía principal del motor (CLAUDE.md §4.1): se EXTIENDEN los blocks del core,
 * no se recrean. El lado editor (atributos, panel "BloqUIX Effects", inyección en
 * el save) vive en extensions/effects-editor.js. Esta clase solo lo encola.
 *
 * La inyección PHP equivalente para blocks DINÁMICOS vive en
 * Bloquix_Runtime::render_block_effects() (paso 4 del §4.1).
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encola las extensiones de efectos del editor.
 */
class Bloquix_Extensions {

	const EDITOR_HANDLE = 'bloquix-effects-editor';

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
		$path = BLOQUIX_PATH . $rel;
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : BLOQUIX_VERSION;

		wp_enqueue_script(
			self::EDITOR_HANDLE,
			BLOQUIX_URL . $rel,
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

		// i18n del lado JS (cadenas con dominio 'bloquix').
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::EDITOR_HANDLE, 'bloquix', BLOQUIX_PATH . 'languages' );
		}
	}
}
