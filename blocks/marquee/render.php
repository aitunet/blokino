<?php
/**
 * Render del block tunet/marquee (dinámico).
 *
 * Envuelve el contenido de los bloques internos en la estructura de banda y lo
 * DUPLICA (segundo grupo aria-hidden) para conseguir un loop sin costuras con
 * translateX(-50%). Velocidad, dirección, separación y pausa salen de los
 * atributos; nada hardcodeado en el motor.
 *
 * Variables disponibles: $attributes, $content, $block.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tnt_speed     = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 30;
$tnt_gap       = isset( $attributes['gap'] ) ? max( 0, (float) $attributes['gap'] ) : 3;
$tnt_direction = ( isset( $attributes['direction'] ) && 'right' === $attributes['direction'] ) ? 'right' : 'left';
$tnt_pause     = ! empty( $attributes['pauseOnHover'] );

$tnt_classes = 'tunet-marquee is-' . $tnt_direction;
if ( $tnt_pause ) {
	$tnt_classes .= ' is-pausable';
}

$tnt_style = sprintf(
	'--tnt-marquee-duration:%ss;--tnt-marquee-gap:%srem;',
	(float) $tnt_speed,
	(float) $tnt_gap
);

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $tnt_classes,
		'style' => $tnt_style,
	)
);
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="tunet-marquee__track">
		<div class="tunet-marquee__group"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML ya renderizado de bloques internos. ?></div>
		<div class="tunet-marquee__group" aria-hidden="true"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- duplicado decorativo. ?></div>
	</div>
</div>
