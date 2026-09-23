<?php
/**
 * Render del block bloquix/icon (dinámico).
 *
 * Pinta un <svg> inline desde el set curado (icons.php) vía el helper compartido
 * bloquix_icon_svg(). El color viene de `currentColor` (theme-token friendly).
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/icons.php';

$bloquix_set  = bloquix_icon_set();
$bloquix_name = isset( $attributes['icon'] ) ? sanitize_key( $attributes['icon'] ) : '';
if ( ! isset( $bloquix_set[ $bloquix_name ] ) ) {
	$bloquix_keys = array_keys( $bloquix_set );
	$bloquix_name = isset( $bloquix_keys[0] ) ? $bloquix_keys[0] : '';
}
if ( '' === $bloquix_name ) {
	return;
}

$bloquix_size   = isset( $attributes['size'] ) ? (int) $attributes['size'] : 24;
$bloquix_stroke = isset( $attributes['strokeWidth'] ) ? (float) $attributes['strokeWidth'] : 2;
$bloquix_label  = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

$bloquix_svg = bloquix_icon_svg(
	$bloquix_name,
	array(
		'size'   => $bloquix_size,
		'stroke' => $bloquix_stroke,
		'label'  => $bloquix_label,
	)
);

$bloquix_wrapper = get_block_wrapper_attributes( array( 'class' => 'bloquix-icon' ) );
?>
<span <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>><?php echo $bloquix_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG curado del motor (icons.php), no entrada de usuario. ?></span>
