<?php
/**
 * Render del block tunet/counter (dinámico).
 *
 * El servidor pinta el valor FINAL (fallback sin JS / accesible). El runtime
 * (view.js) lo anima desde `start` al entrar en viewport. Los parámetros van en
 * data-attributes; nada hardcodeado en el motor (estética desde tokens).
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tunet_start     = isset( $attributes['start'] ) ? (float) $attributes['start'] : 0;
$tunet_end       = isset( $attributes['end'] ) ? (float) $attributes['end'] : 100;
$tunet_duration  = isset( $attributes['duration'] ) ? max( 0, (int) $attributes['duration'] ) : 2000;
$tunet_decimals  = isset( $attributes['decimals'] ) ? max( 0, min( 4, (int) $attributes['decimals'] ) ) : 0;
$tunet_separator = ! empty( $attributes['separator'] );
$tunet_prefix    = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$tunet_suffix    = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '';

// Valor final formateado (con o sin separador de miles).
$tunet_formatted = $tunet_separator
	? number_format( $tunet_end, $tunet_decimals )
	: number_format( $tunet_end, $tunet_decimals, '.', '' );

$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class'              => 'tunet-counter',
		'data-tnt-counter'   => '',
		'data-start'         => (string) $tunet_start,
		'data-end'           => (string) $tunet_end,
		'data-duration'      => (string) $tunet_duration,
		'data-decimals'      => (string) $tunet_decimals,
		'data-separator'     => $tunet_separator ? '1' : '0',
	)
);
?>
<p <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php if ( '' !== $tunet_prefix ) : ?>
		<span class="tunet-counter__affix tunet-counter__prefix"><?php echo esc_html( $tunet_prefix ); ?></span>
	<?php endif; ?>
	<span class="tunet-counter__num"><?php echo esc_html( $tunet_formatted ); ?></span>
	<?php if ( '' !== $tunet_suffix ) : ?>
		<span class="tunet-counter__affix tunet-counter__suffix"><?php echo esc_html( $tunet_suffix ); ?></span>
	<?php endif; ?>
</p>
