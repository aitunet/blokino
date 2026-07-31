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
			$label = sprintf( /* translators: %s: rating value out of 5. */ __( 'Rated %s out of 5', 'tunet-core' ), $rating );
			$rating_html  = '<span class="tunet-rating" role="img" aria-label="' . esc_attr( $label ) . '" style="--tnt-rating:' . esc_attr( number_format( (float) $rating, 1, '.', '' ) ) . ';">';
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

$tunet_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $tunet_items ) ) {
	return;
}

$tunet_layout = ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) ? 'grid' : 'carousel';

if ( 'grid' === $tunet_layout ) {
	$tunet_cols  = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
	$tunet_cards = '';
	foreach ( $tunet_items as $tunet_item ) {
		$tunet_cards .= tunet_core_testimonial_card( $tunet_item );
	}
	$tunet_wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'tunet-testimonials tunet-testimonials--grid',
			'style' => '--tnt-tst-cols:' . $tunet_cols . ';',
		)
	);
	echo '<div ' . $tunet_wrapper . '>' . $tunet_cards . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card.
	return;
}

if ( class_exists( 'Tunet_Core_Runtime' ) ) {
	Tunet_Core_Runtime::enqueue_carousel();
}

$tunet_spv   = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 3;
$tunet_space = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$tunet_speed = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$tunet_delay = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$tunet_loop  = ! empty( $attributes['loop'] );
$tunet_auto  = ! empty( $attributes['autoplay'] );
$tunet_pag   = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$tunet_nav   = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$tunet_pspv  = max( 1, $tunet_spv );

$tunet_slides = '';
foreach ( $tunet_items as $tunet_item ) {
	$tunet_slides .= '<div class="swiper-slide">' . tunet_core_testimonial_card( $tunet_item ) . '</div>';
}

$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'tunet-testimonials tunet-carousel swiper',
		'style'               => '--tnt-carousel-spv:' . $tunet_pspv . ';',
		'data-swiper-base'    => esc_url( TUNET_CORE_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => 'slide',
		'data-spv'            => (string) $tunet_spv,
		'data-space'          => (string) $tunet_space,
		'data-speed'          => (string) $tunet_speed,
		'data-loop'           => $tunet_loop ? '1' : '0',
		'data-autoplay'       => $tunet_auto ? '1' : '0',
		'data-autoplay-delay' => (string) $tunet_delay,
		'data-pagination'     => $tunet_pag ? '1' : '0',
		'data-navigation'     => $tunet_nav ? '1' : '0',
	)
);
?>
<div <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="swiper-wrapper">
		<?php echo $tunet_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card. ?>
	</div>
	<?php if ( $tunet_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $tunet_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
