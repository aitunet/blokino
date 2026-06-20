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

$tnt_start     = isset( $attributes['start'] ) ? (float) $attributes['start'] : 0;
$tnt_end       = isset( $attributes['end'] ) ? (float) $attributes['end'] : 100;
$tnt_duration  = isset( $attributes['duration'] ) ? max( 0, (int) $attributes['duration'] ) : 2000;
$tnt_decimals  = isset( $attributes['decimals'] ) ? max( 0, min( 4, (int) $attributes['decimals'] ) ) : 0;
$tnt_separator = ! empty( $attributes['separator'] );
$tnt_prefix    = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$tnt_suffix    = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '';

// Valor final formateado (con o sin separador de miles).
$tnt_formatted = $tnt_separator
	? number_format( $tnt_end, $tnt_decimals )
	: number_format( $tnt_end, $tnt_decimals, '.', '' );

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class'              => 'tunet-counter',
		'data-tnt-counter'   => '',
		'data-start'         => (string) $tnt_start,
		'data-end'           => (string) $tnt_end,
		'data-duration'      => (string) $tnt_duration,
		'data-decimals'      => (string) $tnt_decimals,
		'data-separator'     => $tnt_separator ? '1' : '0',
	)
);
?>
<p <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php if ( '' !== $tnt_prefix ) : ?>
		<span class="tunet-counter__affix tunet-counter__prefix"><?php echo esc_html( $tnt_prefix ); ?></span>
	<?php endif; ?>
	<span class="tunet-counter__num"><?php echo esc_html( $tnt_formatted ); ?></span>
	<?php if ( '' !== $tnt_suffix ) : ?>
		<span class="tunet-counter__affix tunet-counter__suffix"><?php echo esc_html( $tnt_suffix ); ?></span>
	<?php endif; ?>
</p>
