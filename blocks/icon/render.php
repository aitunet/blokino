<?php
/**
 * Render del block tunet/icon (dinámico).
 *
 * Pinta un <svg> inline desde el set curado (icons.php) vía el helper compartido
 * tunet_core_icon_svg(). El color viene de `currentColor` (theme-token friendly).
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/icons.php';

$tunet_set  = tunet_core_icon_set();
$tunet_name = isset( $attributes['icon'] ) ? sanitize_key( $attributes['icon'] ) : '';
if ( ! isset( $tunet_set[ $tunet_name ] ) ) {
	$tunet_keys = array_keys( $tunet_set );
	$tunet_name = isset( $tunet_keys[0] ) ? $tunet_keys[0] : '';
}
if ( '' === $tunet_name ) {
	return;
}

$tunet_size   = isset( $attributes['size'] ) ? (int) $attributes['size'] : 24;
$tunet_stroke = isset( $attributes['strokeWidth'] ) ? (float) $attributes['strokeWidth'] : 2;
$tunet_label  = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

$tunet_svg = tunet_core_icon_svg(
	$tunet_name,
	array(
		'size'   => $tunet_size,
		'stroke' => $tunet_stroke,
		'label'  => $tunet_label,
	)
);

$tunet_wrapper = get_block_wrapper_attributes( array( 'class' => 'tunet-icon' ) );
?>
<span <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>><?php echo $tunet_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG curado del motor (icons.php), no entrada de usuario. ?></span>
