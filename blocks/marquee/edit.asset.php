<?php
/**
 * Dependencias y versión del editorScript del block (autoría a mano, sin build).
 *
 * WordPress lee este archivo junto a edit.js (mismo nombre base + .asset.php) y
 * registra el script con estas dependencias. Sin él, edit.js cargaría sin
 * garantizar que wp.blockEditor / wp.components estén disponibles.
 *
 * @package Tunet\Core
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
