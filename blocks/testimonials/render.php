<?php
/**
 * Render del block tunet/testimonials (dinámico).
 *
 * layout=carousel → estructura Swiper reusando el runtime COMPARTIDO del motor
 * (.tunet-carousel + Tunet_Core_Runtime::enqueue_carousel()). layout=grid →
 * rejilla CSS auto-suficiente (§9), sin JS. Cada hijo es un tunet/testimonial.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tnt_has_items = ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->inner_blocks ) );
if ( ! $tnt_has_items ) {
	return; // Sin testimonios: no renderizar nada.
}

$tnt_layout = ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) ? 'grid' : 'carousel';

if ( 'grid' === $tnt_layout ) {
	$tnt_cols = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
	$tnt_items = '';
	foreach ( $block->inner_blocks as $tnt_inner ) {
		$tnt_items .= $tnt_inner->render();
	}
	$tnt_wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'tunet-testimonials tunet-testimonials--grid',
			'style' => '--tnt-tst-cols:' . $tnt_cols . ';',
		)
	);
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $tnt_wrapper (WP), $tnt_items (bloques ya renderizados).
	echo '<div ' . $tnt_wrapper . '>' . $tnt_items . '</div>';
	return;
}

// Carousel: reusa el runtime compartido.
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
foreach ( $block->inner_blocks as $tnt_inner ) {
	$tnt_slides .= '<div class="swiper-slide">' . $tnt_inner->render() . '</div>';
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
		<?php echo $tnt_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bloques ya renderizados. ?>
	</div>
	<?php if ( $tnt_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $tnt_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
