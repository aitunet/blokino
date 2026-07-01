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

$tnt_set  = tunet_core_icon_set();
$tnt_name = isset( $attributes['icon'] ) ? sanitize_key( $attributes['icon'] ) : '';
if ( ! isset( $tnt_set[ $tnt_name ] ) ) {
	$tnt_keys = array_keys( $tnt_set );
	$tnt_name = isset( $tnt_keys[0] ) ? $tnt_keys[0] : '';
}
if ( '' === $tnt_name ) {
	return;
}

$tnt_size   = isset( $attributes['size'] ) ? (int) $attributes['size'] : 24;
$tnt_stroke = isset( $attributes['strokeWidth'] ) ? (float) $attributes['strokeWidth'] : 2;
$tnt_label  = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

$tnt_svg = tunet_core_icon_svg(
	$tnt_name,
	array(
		'size'   => $tnt_size,
		'stroke' => $tnt_stroke,
		'label'  => $tnt_label,
	)
);

$tnt_wrapper = get_block_wrapper_attributes( array( 'class' => 'tunet-icon' ) );
?>
<span <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>><?php echo $tnt_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG curado del motor (icons.php), no entrada de usuario. ?></span>
