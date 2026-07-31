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

$tunet_before = isset( $attributes['beforeUrl'] ) ? esc_url( $attributes['beforeUrl'] ) : '';
$tunet_after  = isset( $attributes['afterUrl'] ) ? esc_url( $attributes['afterUrl'] ) : '';

if ( '' === $tunet_before || '' === $tunet_after ) {
	return; // No hay nada que comparar.
}

$tunet_before_alt = isset( $attributes['beforeAlt'] ) ? (string) $attributes['beforeAlt'] : '';
$tunet_after_alt  = isset( $attributes['afterAlt'] ) ? (string) $attributes['afterAlt'] : '';
$tunet_before_lbl = isset( $attributes['beforeLabel'] ) ? (string) $attributes['beforeLabel'] : '';
$tunet_after_lbl  = isset( $attributes['afterLabel'] ) ? (string) $attributes['afterLabel'] : '';
$tunet_pos        = isset( $attributes['startPosition'] ) ? max( 0, min( 100, (int) $attributes['startPosition'] ) ) : 50;
// Dimensiones intrínsecas (reservan espacio → evitan CLS). Solo se emiten si ambas existen.
$tunet_w          = isset( $attributes['width'] ) ? (int) $attributes['width'] : 0;
$tunet_h          = isset( $attributes['height'] ) ? (int) $attributes['height'] : 0;
// Fallback: derive intrinsic dims from the before attachment when not stored on the block.
if ( ( $tunet_w <= 0 || $tunet_h <= 0 ) && ! empty( $attributes['beforeId'] ) ) {
	$tunet_meta = wp_get_attachment_metadata( (int) $attributes['beforeId'] );
	if ( is_array( $tunet_meta ) && ! empty( $tunet_meta['width'] ) && ! empty( $tunet_meta['height'] ) ) {
		$tunet_w = (int) $tunet_meta['width'];
		$tunet_h = (int) $tunet_meta['height'];
	}
}
$tunet_dim        = ( $tunet_w > 0 && $tunet_h > 0 ) ? ' width="' . esc_attr( $tunet_w ) . '" height="' . esc_attr( $tunet_h ) . '"' : '';

$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'tunet-ba',
		'style' => '--tnt-ba-pos:' . $tunet_pos . '%',
	)
);
?>
<div <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<img class="tunet-ba__img tunet-ba__before" src="<?php echo $tunet_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $tunet_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $tunet_before_alt ); ?>" draggable="false" />

	<div class="tunet-ba__after">
		<img class="tunet-ba__img" src="<?php echo $tunet_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $tunet_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $tunet_after_alt ); ?>" draggable="false" />
		<?php if ( '' !== $tunet_after_lbl ) : ?>
			<span class="tunet-ba__label tunet-ba__label--after"><?php echo esc_html( $tunet_after_lbl ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $tunet_before_lbl ) : ?>
		<span class="tunet-ba__label tunet-ba__label--before"><?php echo esc_html( $tunet_before_lbl ); ?></span>
	<?php endif; ?>

	<div class="tunet-ba__handle" role="slider" tabindex="0"
		aria-label="<?php esc_attr_e( 'Compare before and after', 'tunet-core' ); ?>"
		aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $tunet_pos ); ?>">
		<span class="tunet-ba__grip" aria-hidden="true"></span>
	</div>
</div>
