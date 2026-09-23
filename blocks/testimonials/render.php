<?php
/**
 * Render del block bloquix/testimonials (dinámico, modelo REPEATER).
 *
 * Itera $attributes['items'] (no InnerBlocks). layout=carousel → runtime Swiper
 * compartido (.bloquix-carousel); layout=grid → rejilla auto-suficiente (§9). Cada
 * item se pinta con bloquix_testimonial_card(). Todo por tokens; escape estricto.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// El helper de iconos solo se carga globalmente en el editor; en el front hay
// que requerirlo aquí para las estrellas (idempotente, function_exists guards).
require_once __DIR__ . '/../icon/icons.php';

if ( ! function_exists( 'bloquix_testimonial_card' ) ) {
	/**
	 * Renderiza una tarjeta de testimonio desde un item del repeater.
	 *
	 * @param array $item Item { avatarId, avatarUrl, rating, name, role, quote }.
	 * @return string
	 */
	function bloquix_testimonial_card( $item ) {
		$avatar_id  = isset( $item['avatarId'] ) ? absint( $item['avatarId'] ) : 0;
		$avatar_url = isset( $item['avatarUrl'] ) ? esc_url( $item['avatarUrl'] ) : '';
		$rating     = isset( $item['rating'] ) ? (float) $item['rating'] : 0;
		$rating     = max( 0, min( 5, $rating ) );
		$quote      = ( isset( $item['quote'] ) && is_string( $item['quote'] ) ) ? wp_kses_post( $item['quote'] ) : '';
		$name       = ( isset( $item['name'] ) && is_string( $item['name'] ) ) ? wp_kses_post( $item['name'] ) : '';
		$role       = ( isset( $item['role'] ) && is_string( $item['role'] ) ) ? wp_kses_post( $item['role'] ) : '';

		$avatar = '';
		if ( $avatar_id ) {
			$avatar = wp_get_attachment_image( $avatar_id, 'thumbnail', false, array( 'class' => 'bloquix-testimonial__avatar', 'alt' => $name ? wp_strip_all_tags( $name ) : '' ) );
		} elseif ( '' !== $avatar_url ) {
			$avatar = '<img class="bloquix-testimonial__avatar" src="' . $avatar_url . '" alt="' . esc_attr( $name ? wp_strip_all_tags( $name ) : '' ) . '" />';
		}

		$rating_html = '';
		if ( $rating > 0 && function_exists( 'bloquix_icon_svg' ) ) {
			$star  = bloquix_icon_svg( 'star', array( 'size' => 0, 'class' => 'bloquix-rating__star' ) );
			$five  = str_repeat( $star, 5 );
			$label = sprintf( /* translators: %s: rating value out of 5. */ __( 'Rated %s out of 5', 'bloquix' ), $rating );
			$rating_html  = '<span class="bloquix-rating" role="img" aria-label="' . esc_attr( $label ) . '" style="--tnt-rating:' . esc_attr( number_format( (float) $rating, 1, '.', '' ) ) . ';">';
			$rating_html .= '<span class="bloquix-rating__layer bloquix-rating__layer--empty" aria-hidden="true">' . $five . '</span>';
			$rating_html .= '<span class="bloquix-rating__layer bloquix-rating__layer--full" aria-hidden="true">' . $five . '</span>';
			$rating_html .= '</span>';
		}

		$html  = '<div class="bloquix-testimonial">';
		$html .= $rating_html;
		if ( '' !== $quote ) {
			$html .= '<blockquote class="bloquix-testimonial__quote">' . $quote . '</blockquote>';
		}
		$html .= '<div class="bloquix-testimonial__byline">' . $avatar . '<span class="bloquix-testimonial__meta">';
		if ( '' !== $name ) {
			$html .= '<span class="bloquix-testimonial__name">' . $name . '</span>';
		}
		if ( '' !== $role ) {
			$html .= '<span class="bloquix-testimonial__role">' . $role . '</span>';
		}
		$html .= '</span></div></div>';
		return $html;
	}
}

$bloquix_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $bloquix_items ) ) {
	return;
}

$bloquix_layout = ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) ? 'grid' : 'carousel';

if ( 'grid' === $bloquix_layout ) {
	$bloquix_cols  = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
	$bloquix_cards = '';
	foreach ( $bloquix_items as $bloquix_item ) {
		$bloquix_cards .= bloquix_testimonial_card( $bloquix_item );
	}
	$bloquix_wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'bloquix-testimonials bloquix-testimonials--grid',
			'style' => '--tnt-tst-cols:' . $bloquix_cols . ';',
		)
	);
	echo '<div ' . $bloquix_wrapper . '>' . $bloquix_cards . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card.
	return;
}

if ( class_exists( 'Bloquix_Runtime' ) ) {
	Bloquix_Runtime::enqueue_carousel();
}

$bloquix_spv   = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 3;
$bloquix_space = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$bloquix_speed = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$bloquix_delay = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$bloquix_loop  = ! empty( $attributes['loop'] );
$bloquix_auto  = ! empty( $attributes['autoplay'] );
$bloquix_pag   = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$bloquix_nav   = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$bloquix_pspv  = max( 1, $bloquix_spv );

$bloquix_slides = '';
foreach ( $bloquix_items as $bloquix_item ) {
	$bloquix_slides .= '<div class="swiper-slide">' . bloquix_testimonial_card( $bloquix_item ) . '</div>';
}

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'bloquix-testimonials bloquix-carousel swiper',
		'style'               => '--tnt-carousel-spv:' . $bloquix_pspv . ';',
		'data-swiper-base'    => esc_url( BLOQUIX_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => 'slide',
		'data-spv'            => (string) $bloquix_spv,
		'data-space'          => (string) $bloquix_space,
		'data-speed'          => (string) $bloquix_speed,
		'data-loop'           => $bloquix_loop ? '1' : '0',
		'data-autoplay'       => $bloquix_auto ? '1' : '0',
		'data-autoplay-delay' => (string) $bloquix_delay,
		'data-pagination'     => $bloquix_pag ? '1' : '0',
		'data-navigation'     => $bloquix_nav ? '1' : '0',
	)
);
?>
<div <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="swiper-wrapper">
		<?php echo $bloquix_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado en el card. ?>
	</div>
	<?php if ( $bloquix_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $bloquix_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
