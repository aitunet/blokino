<?php
/**
 * Render del block tunet/slider (dinámico).
 *
 * Envuelve cada bloque interno en una .swiper-slide e imprime la estructura que
 * espera Swiper. Las opciones van en data-attributes; el runtime (view.js) carga
 * Swiper de forma LAZY (solo cerca del viewport) e inicializa. data-swiper-base
 * apunta a la copia vendorizada (sin CDN en runtime).
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Construye las slides desde los bloques internos (WP_Block).
$tnt_slides = '';
if ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $tnt_inner ) {
		$tnt_slides .= '<div class="swiper-slide">' . $tnt_inner->render() . '</div>';
	}
}

if ( '' === $tnt_slides ) {
	return; // Slider vacío: no renderizar nada.
}

$tnt_effect    = isset( $attributes['effect'] ) ? sanitize_key( $attributes['effect'] ) : 'slide';
$tnt_spv       = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 1;
$tnt_space     = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$tnt_speed     = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$tnt_delay     = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 4000;
$tnt_loop      = ! empty( $attributes['loop'] );
$tnt_autoplay  = ! empty( $attributes['autoplay'] );
$tnt_pag       = ! empty( $attributes['pagination'] );
$tnt_nav       = ! empty( $attributes['navigation'] );

// Per-view count for the pre-init fallback layout (fade/cards stack at 1).
$tnt_preview_spv = in_array( $tnt_effect, array( 'fade', 'cards' ), true ) ? 1 : max( 1, $tnt_spv );

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class'                 => 'tunet-slider swiper',
		'style'                 => '--tnt-slider-spv:' . $tnt_preview_spv . ';',
		'data-swiper-base'      => esc_url( TUNET_CORE_URL . 'blocks/slider/vendor/' ),
		'data-effect'           => $tnt_effect,
		'data-spv'              => (string) $tnt_spv,
		'data-space'            => (string) $tnt_space,
		'data-speed'            => (string) $tnt_speed,
		'data-loop'             => $tnt_loop ? '1' : '0',
		'data-autoplay'         => $tnt_autoplay ? '1' : '0',
		'data-autoplay-delay'   => (string) $tnt_delay,
		'data-pagination'       => $tnt_pag ? '1' : '0',
		'data-navigation'       => $tnt_nav ? '1' : '0',
	)
);
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="swiper-wrapper">
		<?php echo $tnt_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado. ?>
	</div>
	<?php if ( $tnt_pag ) : ?>
		<div class="swiper-pagination"></div>
	<?php endif; ?>
	<?php if ( $tnt_nav ) : ?>
		<div class="swiper-button-prev"></div>
		<div class="swiper-button-next"></div>
	<?php endif; ?>
</div>
