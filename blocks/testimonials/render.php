<?php
/**
 * Render del block tunet/testimonials (dinámico, modelo REPEATER).
 *
 * Itera $attributes['items'] (no InnerBlocks). layout=carousel → runtime Swiper
 * compartido (.tunet-carousel); layout=grid → rejilla auto-suficiente (§9). Cada
 * item se pinta con tunet_core_testimonial_card(). Todo por tokens; escape estricto.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// El helper de iconos solo se carga globalmente en el editor; en el front hay
// que requerirlo aquí para las estrellas (idempotente, function_exists guards).
require_once __DIR__ . '/../icon/icons.php';

if ( ! function_exists( 'tunet_core_testimonial_card' ) ) {
	/**
	 * Renderiza una tarjeta de testimonio desde un item del repeater.
	 *
	 * @param array $item Item { avatarId, avatarUrl, rating, name, role, quote }.
	 * @return string
	 */
	function tunet_core_testimonial_card( $item ) {
		$avatar_id  = isset( $item['avatarId'] ) ? absint( $item['avatarId'] ) : 0;
		$avatar_url = isset( $item['avatarUrl'] ) ? esc_url( $item['avatarUrl'] ) : '';
		$rating     = isset( $item['rating'] ) ? (float) $item['rating'] : 0;
		$rating     = max( 0, min( 5, $rating ) );
		$quote      = ( isset( $item['quote'] ) && is_string( $item['quote'] ) ) ? wp_kses_post( $item['quote'] ) : '';
		$name       = ( isset( $item['name'] ) && is_string( $item['name'] ) ) ? wp_kses_post( $item['name'] ) : '';
		$role       = ( isset( $item['role'] ) && is_string( $item['role'] ) ) ? wp_kses_post( $item['role'] ) : '';

		$avatar = '';
		if ( $avatar_id ) {
			$avatar = wp_get_attachment_image( $avatar_id, 'thumbnail', false, array( 'class' => 'tunet-testimonial__avatar', 'alt' => $name ? wp_strip_all_tags( $name ) : '' ) );
		} elseif ( '' !== $avatar_url ) {
			$avatar = '<img class="tunet-testimonial__avatar" src="' . $avatar_url . '" alt="' . esc_attr( $name ? wp_strip_all_tags( $name ) : '' ) . '" />';
		}

		$rating_html = '';
		if ( $rating > 0 && function_exists( 'tunet_core_icon_svg' ) ) {
			$star  = tunet_core_icon_svg( 'star', array( 'size' => 0, 'class' => 'tunet-rating__star' ) );
			$five  = str_repeat( $star, 5 );
			$label = sprintf( /* translators: %s: rating value out of 5. */ __( 'Rated %s out of 5', 'tunet' ), $rating );
			$rating_html  = '<span class="tunet-rating" role="img" aria-label="' . esc_attr( $label ) . '" style="--tnt-rating:' . esc_attr( $rating ) . ';">';
			$rating_html .= '<span class="tunet-rating__layer tunet-rating__layer--empty" aria-hidden="true">' . $five . '</span>';
			$rating_html .= '<span class="tunet-rating__layer tunet-rating__layer--full" aria-hidden="true">' . $five . '</span>';
			$rating_html .= '</span>';
		}

		$html  = '<div class="tunet-testimonial">';
		$html .= $rating_html;
		if ( '' !== $quote ) {
			$html .= '<blockquote class="tunet-testimonial__quote">' . $quote . '</blockquote>';
		}
		$html .= '<div class="tunet-testimonial__byline">' . $avatar . '<span class="tunet-testimonial__meta">';
		if ( '' !== $name ) {
			$html .= '<span class="tunet-testimonial__name">' . $name . '</span>';
		}
		if ( '' !== $role ) {
			$html .= '<span class="tunet-testimonial__role">' . $role . '</span>';
		}
		$html .= '</span></div></div>';
		return $html;
	}
}

$tnt_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $tnt_items ) ) {
	return;
}

$tnt_layout = ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) ? 'grid' : 'carousel';

if ( 'grid' === $tnt_layout ) {
	$tnt_cols  = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
	$tnt_cards = '';
	foreach ( $tnt_items as $tnt_item ) {
		$tnt_cards .= tunet_core_testimonial_card( $tnt_item );
	}
	$tnt_wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'tunet-testimonials tunet-testimonials--grid',
			'style' => '--tnt-tst-cols:' . $tnt_cols . ';',
		)
	);
	echo '<div ' . $tnt_wrapper . '>' . $tnt_cards . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card.
	return;
}

if ( class_exists( 'Tunet_Core_Runtime' ) ) {
	Tunet_Core_Runtime::enqueue_carousel();
}

$tnt_spv   = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 3;
$tnt_space = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$tnt_speed = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$tnt_delay = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$tnt_loop  = ! empty( $attributes['loop'] );
$tnt_auto  = ! empty( $attributes['autoplay'] );
$tnt_pag   = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$tnt_nav   = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$tnt_pspv  = max( 1, $tnt_spv );

$tnt_slides = '';
foreach ( $tnt_items as $tnt_item ) {
	$tnt_slides .= '<div class="swiper-slide">' . tunet_core_testimonial_card( $tnt_item ) . '</div>';
}

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'tunet-testimonials tunet-carousel swiper',
		'style'               => '--tnt-carousel-spv:' . $tnt_pspv . ';',
		'data-swiper-base'    => esc_url( TUNET_CORE_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => 'slide',
		'data-spv'            => (string) $tnt_spv,
		'data-space'          => (string) $tnt_space,
		'data-speed'          => (string) $tnt_speed,
		'data-loop'           => $tnt_loop ? '1' : '0',
		'data-autoplay'       => $tnt_auto ? '1' : '0',
		'data-autoplay-delay' => (string) $tnt_delay,
		'data-pagination'     => $tnt_pag ? '1' : '0',
		'data-navigation'     => $tnt_nav ? '1' : '0',
	)
);
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="swiper-wrapper">
		<?php echo $tnt_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card. ?>
	</div>
	<?php if ( $tnt_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $tnt_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
