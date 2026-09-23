<?php
/**
 * Render del block blokino/icon (dinámico).
 *
 * Pinta un <svg> inline desde el set curado (icons.php) vía el helper compartido
 * blokino_icon_svg(). El color viene de `currentColor` (theme-token friendly).
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/icons.php';

$blokino_set  = blokino_icon_set();
$blokino_name = isset( $attributes['icon'] ) ? sanitize_key( $attributes['icon'] ) : '';
if ( ! isset( $blokino_set[ $blokino_name ] ) ) {
	$blokino_keys = array_keys( $blokino_set );
	$blokino_name = isset( $blokino_keys[0] ) ? $blokino_keys[0] : '';
}
if ( '' === $blokino_name ) {
	return;
}

$blokino_size   = isset( $attributes['size'] ) ? (int) $attributes['size'] : 24;
$blokino_stroke = isset( $attributes['strokeWidth'] ) ? (float) $attributes['strokeWidth'] : 2;
$blokino_label  = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

$blokino_svg = blokino_icon_svg(
	$blokino_name,
	array(
		'size'   => $blokino_size,
		'stroke' => $blokino_stroke,
		'label'  => $blokino_label,
	)
);

$blokino_wrapper = get_block_wrapper_attributes( array( 'class' => 'blokino-icon' ) );
?>
<span <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>><?php echo $blokino_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG curado del motor (icons.php), no entrada de usuario. ?></span>
