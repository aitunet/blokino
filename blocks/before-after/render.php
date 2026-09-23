<?php
/**
 * Render del block bloquix/before-after (dinámico).
 *
 * Estructura: imagen "antes" en flujo (define la altura) + capa "después"
 * absoluta recortada por clip-path a la posición del divisor. El divisor es un
 * slider accesible (role=slider, teclado). El runtime (view.js) gestiona el
 * arrastre. Sin las dos imágenes no renderiza nada.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bloquix_before = isset( $attributes['beforeUrl'] ) ? esc_url( $attributes['beforeUrl'] ) : '';
$bloquix_after  = isset( $attributes['afterUrl'] ) ? esc_url( $attributes['afterUrl'] ) : '';

if ( '' === $bloquix_before || '' === $bloquix_after ) {
	return; // No hay nada que comparar.
}

$bloquix_before_alt = isset( $attributes['beforeAlt'] ) ? (string) $attributes['beforeAlt'] : '';
$bloquix_after_alt  = isset( $attributes['afterAlt'] ) ? (string) $attributes['afterAlt'] : '';
$bloquix_before_lbl = isset( $attributes['beforeLabel'] ) ? (string) $attributes['beforeLabel'] : '';
$bloquix_after_lbl  = isset( $attributes['afterLabel'] ) ? (string) $attributes['afterLabel'] : '';
$bloquix_pos        = isset( $attributes['startPosition'] ) ? max( 0, min( 100, (int) $attributes['startPosition'] ) ) : 50;
// Dimensiones intrínsecas (reservan espacio → evitan CLS). Solo se emiten si ambas existen.
$bloquix_w          = isset( $attributes['width'] ) ? (int) $attributes['width'] : 0;
$bloquix_h          = isset( $attributes['height'] ) ? (int) $attributes['height'] : 0;
// Fallback: derive intrinsic dims from the before attachment when not stored on the block.
if ( ( $bloquix_w <= 0 || $bloquix_h <= 0 ) && ! empty( $attributes['beforeId'] ) ) {
	$bloquix_meta = wp_get_attachment_metadata( (int) $attributes['beforeId'] );
	if ( is_array( $bloquix_meta ) && ! empty( $bloquix_meta['width'] ) && ! empty( $bloquix_meta['height'] ) ) {
		$bloquix_w = (int) $bloquix_meta['width'];
		$bloquix_h = (int) $bloquix_meta['height'];
	}
}
$bloquix_dim        = ( $bloquix_w > 0 && $bloquix_h > 0 ) ? ' width="' . esc_attr( $bloquix_w ) . '" height="' . esc_attr( $bloquix_h ) . '"' : '';

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'bloquix-ba',
		'style' => '--tnt-ba-pos:' . $bloquix_pos . '%',
	)
);
?>
<div <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<img class="bloquix-ba__img bloquix-ba__before" src="<?php echo $bloquix_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $bloquix_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $bloquix_before_alt ); ?>" draggable="false" />

	<div class="bloquix-ba__after">
		<img class="bloquix-ba__img" src="<?php echo $bloquix_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado con esc_url. ?>"<?php echo $bloquix_dim; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?> alt="<?php echo esc_attr( $bloquix_after_alt ); ?>" draggable="false" />
		<?php if ( '' !== $bloquix_after_lbl ) : ?>
			<span class="bloquix-ba__label bloquix-ba__label--after"><?php echo esc_html( $bloquix_after_lbl ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $bloquix_before_lbl ) : ?>
		<span class="bloquix-ba__label bloquix-ba__label--before"><?php echo esc_html( $bloquix_before_lbl ); ?></span>
	<?php endif; ?>

	<div class="bloquix-ba__handle" role="slider" tabindex="0"
		aria-label="<?php esc_attr_e( 'Compare before and after', 'bloquix' ); ?>"
		aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $bloquix_pos ); ?>">
		<span class="bloquix-ba__grip" aria-hidden="true"></span>
	</div>
</div>
