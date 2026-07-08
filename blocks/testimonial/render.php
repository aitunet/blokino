<?php
/**
 * Render del block tunet/testimonial (dinámico).
 *
 * Tarjeta de testimonio: rating de medias estrellas (dos capas: vacía outline +
 * llena recortada por ancho = rating/5), quote, avatar, nombre, rol. Estrellas
 * con el ícono 'star' del motor; color por --tnt-color-star. Todo por tokens.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tnt_avatar_id  = isset( $attributes['avatarId'] ) ? absint( $attributes['avatarId'] ) : 0;
$tnt_avatar_url = isset( $attributes['avatarUrl'] ) ? esc_url( $attributes['avatarUrl'] ) : '';
$tnt_rating     = isset( $attributes['rating'] ) ? (float) $attributes['rating'] : 0;
$tnt_rating     = max( 0, min( 5, $tnt_rating ) );
$tnt_quote      = isset( $attributes['quote'] ) ? wp_kses_post( $attributes['quote'] ) : '';
$tnt_name       = isset( $attributes['name'] ) ? wp_kses_post( $attributes['name'] ) : '';
$tnt_role       = isset( $attributes['role'] ) ? wp_kses_post( $attributes['role'] ) : '';

$tnt_wrapper = get_block_wrapper_attributes( array( 'class' => 'tunet-testimonial' ) );

// Avatar.
$tnt_avatar = '';
if ( $tnt_avatar_id ) {
	$tnt_avatar = wp_get_attachment_image(
		$tnt_avatar_id,
		'thumbnail',
		false,
		array(
			'class' => 'tunet-testimonial__avatar',
			'alt'   => $tnt_name ? wp_strip_all_tags( $tnt_name ) : '',
		)
	);
} elseif ( $tnt_avatar_url ) {
	$tnt_avatar = '<img class="tunet-testimonial__avatar" src="' . $tnt_avatar_url . '" alt="' . esc_attr( $tnt_name ? wp_strip_all_tags( $tnt_name ) : '' ) . '" />';
}

// Rating (dos capas de 5 estrellas; la llena se recorta a rating/5).
$tnt_rating_html = '';
if ( $tnt_rating > 0 && function_exists( 'tunet_core_icon_svg' ) ) {
	$tnt_star  = tunet_core_icon_svg( 'star', array( 'size' => 0, 'class' => 'tunet-rating__star' ) );
	$tnt_five  = str_repeat( $tnt_star, 5 );
	$tnt_label = sprintf( /* translators: %s: rating value out of 5. */ __( 'Rated %s out of 5', 'tunet' ), $tnt_rating );
	$tnt_rating_html  = '<span class="tunet-rating" role="img" aria-label="' . esc_attr( $tnt_label ) . '" style="--tnt-rating:' . esc_attr( $tnt_rating ) . ';">';
	$tnt_rating_html .= '<span class="tunet-rating__layer tunet-rating__layer--empty" aria-hidden="true">' . $tnt_five . '</span>';
	$tnt_rating_html .= '<span class="tunet-rating__layer tunet-rating__layer--full" aria-hidden="true">' . $tnt_five . '</span>';
	$tnt_rating_html .= '</span>';
}
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- estrellas: SVG del helper + label escapado.
	echo $tnt_rating_html;
	?>
	<?php if ( '' !== $tnt_quote ) : ?>
		<blockquote class="tunet-testimonial__quote"><?php echo $tnt_quote; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post. ?></blockquote>
	<?php endif; ?>
	<div class="tunet-testimonial__byline">
		<?php echo $tnt_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image / esc_url+esc_attr. ?>
		<span class="tunet-testimonial__meta">
			<?php if ( '' !== $tnt_name ) : ?><span class="tunet-testimonial__name"><?php echo $tnt_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post. ?></span><?php endif; ?>
			<?php if ( '' !== $tnt_role ) : ?><span class="tunet-testimonial__role"><?php echo $tnt_role; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post. ?></span><?php endif; ?>
		</span>
	</div>
</div>
