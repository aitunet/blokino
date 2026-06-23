<?php
/**
 * Render del block tunet/icon (dinámico).
 *
 * Pinta un <svg> inline desde el set curado (icons.php). El color viene de
 * `currentColor` (hereda del bloque o de su color de texto), así que es
 * theme-token friendly. Tamaño y grosor por atributos; a11y según `label`.
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

$tnt_inner  = $tnt_set[ $tnt_name ]['svg'];
$tnt_size   = isset( $attributes['size'] ) ? (int) $attributes['size'] : 24;
$tnt_stroke = isset( $attributes['strokeWidth'] ) ? (float) $attributes['strokeWidth'] : 2;
$tnt_label  = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

$tnt_dims = $tnt_size > 0 ? sprintf( ' width="%1$d" height="%1$d"', $tnt_size ) : '';
$tnt_a11y = '' !== $tnt_label
	? ' role="img" aria-label="' . esc_attr( $tnt_label ) . '"'
	: ' aria-hidden="true" focusable="false"';

$tnt_wrapper = get_block_wrapper_attributes( array( 'class' => 'tunet-icon' ) );
?>
<span <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"<?php echo $tnt_dims; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- enteros. ?> fill="none" stroke="currentColor" stroke-width="<?php echo esc_attr( (string) $tnt_stroke ); ?>" stroke-linecap="round" stroke-linejoin="round"<?php echo $tnt_a11y; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba. ?>><?php echo $tnt_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG curado del motor (icons.php), no entrada de usuario. ?></svg></span>
