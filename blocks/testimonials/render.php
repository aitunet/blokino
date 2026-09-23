<?php
/**
 * Render del block blokino/testimonials (dinámico, modelo REPEATER).
 *
 * Itera $attributes['items'] (no InnerBlocks). layout=carousel → runtime Swiper
 * compartido (.blokino-carousel); layout=grid → rejilla auto-suficiente (§9). Cada
 * item se pinta con blokino_testimonial_card(). Todo por tokens; escape estricto.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// El helper de iconos solo se carga globalmente en el editor; en el front hay
// que requerirlo aquí para las estrellas (idempotente, function_exists guards).
require_once __DIR__ . '/../icon/icons.php';

if ( ! function_exists( 'blokino_testimonial_card' ) ) {
	/**
	 * Renderiza una tarjeta de testimonio desde un item del repeater.
	 *
	 * @param array $item Item { avatarId, avatarUrl, rating, name, role, quote }.
	 * @return string
	 */
	function blokino_testimonial_card( $item ) {
		$avatar_id  = isset( $item['avatarId'] ) ? absint( $item['avatarId'] ) : 0;
		$avatar_url = isset( $item['avatarUrl'] ) ? esc_url( $item['avatarUrl'] ) : '';
		$rating     = isset( $item['rating'] ) ? (float) $item['rating'] : 0;
		$rating     = max( 0, min( 5, $rating ) );
		$quote      = ( isset( $item['quote'] ) && is_string( $item['quote'] ) ) ? wp_kses_post( $item['quote'] ) : '';
		$name       = ( isset( $item['name'] ) && is_string( $item['name'] ) ) ? wp_kses_post( $item['name'] ) : '';
		$role       = ( isset( $item['role'] ) && is_string( $item['role'] ) ) ? wp_kses_post( $item['role'] ) : '';

		$avatar = '';
		if ( $avatar_id ) {
			$avatar = wp_get_attachment_image( $avatar_id, 'thumbnail', false, array( 'class' => 'blokino-testimonial__avatar', 'alt' => $name ? wp_strip_all_tags( $name ) : '' ) );
		} elseif ( '' !== $avatar_url ) {
			$avatar = '<img class="blokino-testimonial__avatar" src="' . $avatar_url . '" alt="' . esc_attr( $name ? wp_strip_all_tags( $name ) : '' ) . '" />';
		}

		$rating_html = '';
		if ( $rating > 0 && function_exists( 'blokino_icon_svg' ) ) {
			$star  = blokino_icon_svg( 'star', array( 'size' => 0, 'class' => 'blokino-rating__star' ) );
			$five  = str_repeat( $star, 5 );
			$label = sprintf( /* translators: %s: rating value out of 5. */ __( 'Rated %s out of 5', 'blokino' ), $rating );
			$rating_html  = '<span class="blokino-rating" role="img" aria-label="' . esc_attr( $label ) . '" style="--tnt-rating:' . esc_attr( number_format( (float) $rating, 1, '.', '' ) ) . ';">';
			$rating_html .= '<span class="blokino-rating__layer blokino-rating__layer--empty" aria-hidden="true">' . $five . '</span>';
			$rating_html .= '<span class="blokino-rating__layer blokino-rating__layer--full" aria-hidden="true">' . $five . '</span>';
			$rating_html .= '</span>';
		}

		$html  = '<div class="blokino-testimonial">';
		$html .= $rating_html;
		if ( '' !== $quote ) {
			$html .= '<blockquote class="blokino-testimonial__quote">' . $quote . '</blockquote>';
		}
		$html .= '<div class="blokino-testimonial__byline">' . $avatar . '<span class="blokino-testimonial__meta">';
		if ( '' !== $name ) {
			$html .= '<span class="blokino-testimonial__name">' . $name . '</span>';
		}
		if ( '' !== $role ) {
			$html .= '<span class="blokino-testimonial__role">' . $role . '</span>';
		}
		$html .= '</span></div></div>';
		return $html;
	}
}

$blokino_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $blokino_items ) ) {
	return;
}

$blokino_layout = ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) ? 'grid' : 'carousel';

if ( 'grid' === $blokino_layout ) {
	$blokino_cols  = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
	$blokino_cards = '';
	foreach ( $blokino_items as $blokino_item ) {
		$blokino_cards .= blokino_testimonial_card( $blokino_item );
	}
	$blokino_wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'blokino-testimonials blokino-testimonials--grid',
			'style' => '--tnt-tst-cols:' . $blokino_cols . ';',
		)
	);
	echo '<div ' . $blokino_wrapper . '>' . $blokino_cards . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card.
	return;
}

if ( class_exists( 'Blokino_Runtime' ) ) {
	Blokino_Runtime::enqueue_carousel();
}

$blokino_spv   = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 3;
$blokino_space = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$blokino_speed = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$blokino_delay = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$blokino_loop  = ! empty( $attributes['loop'] );
$blokino_auto  = ! empty( $attributes['autoplay'] );
$blokino_pag   = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$blokino_nav   = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$blokino_pspv  = max( 1, $blokino_spv );

$blokino_slides = '';
foreach ( $blokino_items as $blokino_item ) {
	$blokino_slides .= '<div class="swiper-slide">' . blokino_testimonial_card( $blokino_item ) . '</div>';
}

$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'blokino-testimonials blokino-carousel swiper',
		'style'               => '--tnt-carousel-spv:' . $blokino_pspv . ';',
		'data-swiper-base'    => esc_url( BLOKINO_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => 'slide',
		'data-spv'            => (string) $blokino_spv,
		'data-space'          => (string) $blokino_space,
		'data-speed'          => (string) $blokino_speed,
		'data-loop'           => $blokino_loop ? '1' : '0',
		'data-autoplay'       => $blokino_auto ? '1' : '0',
		'data-autoplay-delay' => (string) $blokino_delay,
		'data-pagination'     => $blokino_pag ? '1' : '0',
		'data-navigation'     => $blokino_nav ? '1' : '0',
	)
);
?>
<div <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="swiper-wrapper">
		<?php echo $blokino_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card. ?>
	</div>
	<?php if ( $blokino_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $blokino_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
