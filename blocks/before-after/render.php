<?php
/**
 * Render del block blokino/before-after (dinámico).
 *
 * Estructura: imagen "antes" en flujo (define la altura) + capa "después"
 * absoluta recortada por clip-path a la posición del divisor. El divisor es un
 * slider accesible (role=slider, teclado). El runtime (view.js) gestiona el
 * arrastre. Sin las dos imágenes no renderiza nada.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blokino_before = isset( $attributes['beforeUrl'] ) ? esc_url( $attributes['beforeUrl'] ) : '';
$blokino_after  = isset( $attributes['afterUrl'] ) ? esc_url( $attributes['afterUrl'] ) : '';

if ( '' === $blokino_before || '' === $blokino_after ) {
	return; // No hay nada que comparar.
}

$blokino_before_alt = isset( $attributes['beforeAlt'] ) ? (string) $attributes['beforeAlt'] : '';
$blokino_after_alt  = isset( $attributes['afterAlt'] ) ? (string) $attributes['afterAlt'] : '';
$blokino_before_lbl = isset( $attributes['beforeLabel'] ) ? (string) $attributes['beforeLabel'] : '';
$blokino_after_lbl  = isset( $attributes['afterLabel'] ) ? (string) $attributes['afterLabel'] : '';
$blokino_pos        = isset( $attributes['startPosition'] ) ? max( 0, min( 100, (int) $attributes['startPosition'] ) ) : 50;
// Dimensiones intrínsecas (reservan espacio → evitan CLS). Solo se emiten si ambas existen.
$blokino_w          = isset( $attributes['width'] ) ? (int) $attributes['width'] : 0;
$blokino_h          = isset( $attributes['height'] ) ? (int) $attributes['height'] : 0;
// Fallback: derive intrinsic dims from the before attachment when not stored on the block.
if ( ( $blokino_w <= 0 || $blokino_h <= 0 ) && ! empty( $attributes['beforeId'] ) ) {
	$blokino_meta = wp_get_attachment_metadata( (int) $attributes['beforeId'] );
	if ( is_array( $blokino_meta ) && ! empty( $blokino_meta['width'] ) && ! empty( $blokino_meta['height'] ) ) {
		$blokino_w = (int) $blokino_meta['width'];
		$blokino_h = (int) $blokino_meta['height'];
	}
}
$blokino_dim        = ( $blokino_w > 0 && $blokino_h > 0 ) ? ' width="' . esc_attr( $blokino_w ) . '" height="' . esc_attr( $blokino_h ) . '"' : '';

$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'blokino-ba',
		'style' => '--tnt-ba-pos:' . $blokino_pos . '%',
	)
);
?>
<div <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<img class="blokino-ba__img blokino-ba__before" src="<?php echo $blokino_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $blokino_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $blokino_before_alt ); ?>" draggable="false" />

	<div class="blokino-ba__after">
		<img class="blokino-ba__img" src="<?php echo $blokino_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $blokino_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $blokino_after_alt ); ?>" draggable="false" />
		<?php if ( '' !== $blokino_after_lbl ) : ?>
			<span class="blokino-ba__label blokino-ba__label--after"><?php echo esc_html( $blokino_after_lbl ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $blokino_before_lbl ) : ?>
		<span class="blokino-ba__label blokino-ba__label--before"><?php echo esc_html( $blokino_before_lbl ); ?></span>
	<?php endif; ?>

	<div class="blokino-ba__handle" role="slider" tabindex="0"
		aria-label="<?php esc_attr_e( 'Compare before and after', 'blokino' ); ?>"
		aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $blokino_pos ); ?>">
		<span class="blokino-ba__grip" aria-hidden="true"></span>
	</div>
</div>
