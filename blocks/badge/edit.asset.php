<?php
/**
 * Dependencias y versión del editorScript del block blokino/badge.
 *
 * Este archivo FALTABA, y no era cosmético: register_block_script_handle() solo
 * llama a wp_set_script_translations() si el script declara 'wp-i18n' entre sus
 * dependencias (medido en wp-includes/blocks.php), así que los 5 strings del panel
 * de este block no podían traducirse por mucho .json que se empaquetara. Y sin
 * dependencias declaradas el script confiaba en que wp.element / wp.blockEditor ya
 * estuvieran cargados por casualidad.
 *
 * @package Blokino
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
	),
	'version'      => '0.1.0',
);
