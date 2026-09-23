<?php
/**
 * Dependencias y versión del editorScript del block blokino/breadcrumbs.
 *
 * 'wp-i18n' es obligatorio aunque parezca de más: register_block_script_handle()
 * solo llama a wp_set_script_translations() si el script lo declara como
 * dependencia, y sin eso los strings del panel no se traducen por mucho .json que
 * se empaquete. Misma lección que en blokino/badge.
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
