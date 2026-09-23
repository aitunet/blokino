<?php
/**
 * Render del block blokino/counter (dinámico).
 *
 * El servidor pinta el valor FINAL (fallback sin JS / accesible). El runtime
 * (view.js) lo anima desde `start` al entrar en viewport. Los parámetros van en
 * data-attributes; nada hardcodeado en el motor (estética desde tokens).
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blokino_start     = isset( $attributes['start'] ) ? (float) $attributes['start'] : 0;
$blokino_end       = isset( $attributes['end'] ) ? (float) $attributes['end'] : 100;
$blokino_duration  = isset( $attributes['duration'] ) ? max( 0, (int) $attributes['duration'] ) : 2000;
$blokino_decimals  = isset( $attributes['decimals'] ) ? max( 0, min( 4, (int) $attributes['decimals'] ) ) : 0;
$blokino_separator = ! empty( $attributes['separator'] );
$blokino_prefix    = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$blokino_suffix    = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '';

// Valor final formateado (con o sin separador de miles).
$blokino_formatted = $blokino_separator
	? number_format( $blokino_end, $blokino_decimals )
	: number_format( $blokino_end, $blokino_decimals, '.', '' );

$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class'              => 'blokino-counter',
		'data-tnt-counter'   => '',
		'data-start'         => (string) $blokino_start,
		'data-end'           => (string) $blokino_end,
		'data-duration'      => (string) $blokino_duration,
		'data-decimals'      => (string) $blokino_decimals,
		'data-separator'     => $blokino_separator ? '1' : '0',
	)
);
?>
<p <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php if ( '' !== $blokino_prefix ) : ?>
		<span class="blokino-counter__affix blokino-counter__prefix"><?php echo esc_html( $blokino_prefix ); ?></span>
	<?php endif; ?>
	<span class="blokino-counter__num"><?php echo esc_html( $blokino_formatted ); ?></span>
	<?php if ( '' !== $blokino_suffix ) : ?>
		<span class="blokino-counter__affix blokino-counter__suffix"><?php echo esc_html( $blokino_suffix ); ?></span>
	<?php endif; ?>
</p>
