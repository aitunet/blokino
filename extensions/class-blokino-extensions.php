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
	 * Bloques que muestran el panel Blokino Effects.
	 *
	 * @return string[]
	 */
	public static function effect_blocks() {
		$blocks = array(
			'core/group',
			'core/columns',
			'core/column',
			'core/cover',
			'core/image',
			'core/gallery',
			'core/media-text',
			'core/video',
			'core/heading',
			'core/paragraph',
			'core/list',
			'core/quote',
			'core/separator',
			'core/buttons',
			'core/button',
			'core/site-title',
			'core/post-title',
			'core/post-featured-image',
			'core/query',
			'blokino/section',
			'blokino/marquee',
			'blokino/counter',
			'blokino/before-after',
		);
		/**
		 * Blocks that get the Blokino Effects panel.
		 *
		 * @param string[] $blocks Block names.
		 */
		$blocks = apply_filters( 'blokino_effect_blocks', $blocks );
		return array_values( array_unique( array_filter( array_map( 'strval', (array) $blocks ) ) ) );
	}

	/**
	 * Encola el script que registra los atributos tf* y el panel del editor.
	 */
	public function enqueue_editor_assets() {
		$rel  = 'extensions/effects-editor.js';
		$ver  = blokino_asset_version( $rel );

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
				'wp-data',
			),
			$ver,
			true
		);

		// U-09: la lista de bloques con panel de efectos vive en PHP y es filtrable
		// (blokino_effect_blocks); el JS la recibe antes de cargarse. El render PHP
		// ya es genérico: cualquier bloque con atributos tf* recibe sus data-tf-*.
		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.blokinoEffectBlocks = ' . wp_json_encode( self::effect_blocks() ) . ';',
			'before'
		);

		// i18n del lado JS (cadenas con dominio 'blokino').
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::EDITOR_HANDLE, 'blokino', BLOKINO_PATH . 'languages' );
		}
	}
}
