<?php
/**
 * Render del block tunet/before-after (dinámico).
 *
 * Estructura: imagen "antes" en flujo (define la altura) + capa "después"
 * absoluta recortada por clip-path a la posición del divisor. El divisor es un
 * slider accesible (role=slider, teclado). El runtime (view.js) gestiona el
 * arrastre. Sin las dos imágenes no renderiza nada.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tnt_before = isset( $attributes['beforeUrl'] ) ? esc_url( $attributes['beforeUrl'] ) : '';
$tnt_after  = isset( $attributes['afterUrl'] ) ? esc_url( $attributes['afterUrl'] ) : '';

if ( '' === $tnt_before || '' === $tnt_after ) {
	return; // No hay nada que comparar.
}

$tnt_before_alt = isset( $attributes['beforeAlt'] ) ? (string) $attributes['beforeAlt'] : '';
$tnt_after_alt  = isset( $attributes['afterAlt'] ) ? (string) $attributes['afterAlt'] : '';
$tnt_before_lbl = isset( $attributes['beforeLabel'] ) ? (string) $attributes['beforeLabel'] : '';
$tnt_after_lbl  = isset( $attributes['afterLabel'] ) ? (string) $attributes['afterLabel'] : '';
$tnt_pos        = isset( $attributes['startPosition'] ) ? max( 0, min( 100, (int) $attributes['startPosition'] ) ) : 50;
// Dimensiones intrínsecas (reservan espacio → evitan CLS). Solo se emiten si ambas existen.
$tnt_w          = isset( $attributes['width'] ) ? (int) $attributes['width'] : 0;
$tnt_h          = isset( $attributes['height'] ) ? (int) $attributes['height'] : 0;
// Fallback: derive intrinsic dims from the before attachment when not stored on the block.
if ( ( $tnt_w <= 0 || $tnt_h <= 0 ) && ! empty( $attributes['beforeId'] ) ) {
	$tnt_meta = wp_get_attachment_metadata( (int) $attributes['beforeId'] );
	if ( is_array( $tnt_meta ) && ! empty( $tnt_meta['width'] ) && ! empty( $tnt_meta['height'] ) ) {
		$tnt_w = (int) $tnt_meta['width'];
		$tnt_h = (int) $tnt_meta['height'];
	}
}
$tnt_dim        = ( $tnt_w > 0 && $tnt_h > 0 ) ? ' width="' . esc_attr( $tnt_w ) . '" height="' . esc_attr( $tnt_h ) . '"' : '';

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'tunet-ba',
		'style' => '--tnt-ba-pos:' . $tnt_pos . '%',
	)
);
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<img class="tunet-ba__img tunet-ba__before" src="<?php echo $tnt_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $tnt_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $tnt_before_alt ); ?>" draggable="false" />

	<div class="tunet-ba__after">
		<img class="tunet-ba__img" src="<?php echo $tnt_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $tnt_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $tnt_after_alt ); ?>" draggable="false" />
		<?php if ( '' !== $tnt_after_lbl ) : ?>
			<span class="tunet-ba__label tunet-ba__label--after"><?php echo esc_html( $tnt_after_lbl ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $tnt_before_lbl ) : ?>
		<span class="tunet-ba__label tunet-ba__label--before"><?php echo esc_html( $tnt_before_lbl ); ?></span>
	<?php endif; ?>

	<div class="tunet-ba__handle" role="slider" tabindex="0"
		aria-label="<?php esc_attr_e( 'Compare before and after', 'tunet' ); ?>"
		aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $tnt_pos ); ?>">
		<span class="tunet-ba__grip" aria-hidden="true"></span>
	</div>
</div>
