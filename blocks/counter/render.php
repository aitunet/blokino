<?php
/**
 * Render del block bloquix/counter (dinámico).
 *
 * El servidor pinta el valor FINAL (fallback sin JS / accesible). El runtime
 * (view.js) lo anima desde `start` al entrar en viewport. Los parámetros van en
 * data-attributes; nada hardcodeado en el motor (estética desde tokens).
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bloquix_start     = isset( $attributes['start'] ) ? (float) $attributes['start'] : 0;
$bloquix_end       = isset( $attributes['end'] ) ? (float) $attributes['end'] : 100;
$bloquix_duration  = isset( $attributes['duration'] ) ? max( 0, (int) $attributes['duration'] ) : 2000;
$bloquix_decimals  = isset( $attributes['decimals'] ) ? max( 0, min( 4, (int) $attributes['decimals'] ) ) : 0;
$bloquix_separator = ! empty( $attributes['separator'] );
$bloquix_prefix    = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$bloquix_suffix    = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '';

// Valor final formateado (con o sin separador de miles).
$bloquix_formatted = $bloquix_separator
	? number_format( $bloquix_end, $bloquix_decimals )
	: number_format( $bloquix_end, $bloquix_decimals, '.', '' );

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class'              => 'bloquix-counter',
		'data-tnt-counter'   => '',
		'data-start'         => (string) $bloquix_start,
		'data-end'           => (string) $bloquix_end,
		'data-duration'      => (string) $bloquix_duration,
		'data-decimals'      => (string) $bloquix_decimals,
		'data-separator'     => $bloquix_separator ? '1' : '0',
	)
);
?>
<p <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php if ( '' !== $bloquix_prefix ) : ?>
		<span class="bloquix-counter__affix bloquix-counter__prefix"><?php echo esc_html( $bloquix_prefix ); ?></span>
	<?php endif; ?>
	<span class="bloquix-counter__num"><?php echo esc_html( $bloquix_formatted ); ?></span>
	<?php if ( '' !== $bloquix_suffix ) : ?>
		<span class="bloquix-counter__affix bloquix-counter__suffix"><?php echo esc_html( $bloquix_suffix ); ?></span>
	<?php endif; ?>
</p>
