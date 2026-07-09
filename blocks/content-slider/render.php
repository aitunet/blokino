<?php
/**
 * Render del block tunet/content-slider (dinámico).
 *
 * Itera $attributes['items'] (repeater del sidebar) y arma slides estructurados
 * (imagen + grupo de contenido + CTAs) dentro del runtime Swiper COMPARTIDO
 * (.tunet-carousel). Todo por tokens; escape estricto.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tnt_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $tnt_items ) ) {
	return;
}

/**
 * Devuelve la clase text-align para un valor de campo (inherit = sin clase).
 */
$tnt_align_class = function ( $value ) {
	$value = is_string( $value ) ? $value : '';
	if ( in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
		return ' tunet-cslide__text--' . $value;
	}
	return '';
};

/**
 * Renderiza los CTAs de un slide.
 */
$tnt_render_ctas = function ( $ctas ) {
	if ( ! is_array( $ctas ) ) {
		return '';
	}
	$html = '';
	foreach ( $ctas as $cta ) {
		$text = isset( $cta['text'] ) ? trim( wp_strip_all_tags( $cta['text'] ) ) : '';
		$url  = isset( $cta['url'] ) ? esc_url( $cta['url'] ) : '';
		if ( '' === $text ) {
			continue;
		}
		$style = isset( $cta['style'] ) && in_array( $cta['style'], array( 'filled', 'outline', 'text' ), true ) ? $cta['style'] : 'filled';
		$target = ! empty( $cta['newTab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$href   = '' !== $url ? ' href="' . $url . '"' : '';
		$html  .= '<a class="tunet-cslide__cta tunet-cslide__cta--' . esc_attr( $style ) . '"' . $href . $target . '>' . esc_html( $text ) . '</a>';
	}
	return '' !== $html ? '<div class="tunet-cslide__ctas">' . $html . '</div>' : '';
};

$tnt_slides = '';
foreach ( $tnt_items as $tnt_item ) {
	// Imagen.
	$tnt_media = '';
	$tnt_img_id  = isset( $tnt_item['imageId'] ) ? absint( $tnt_item['imageId'] ) : 0;
	$tnt_img_url = isset( $tnt_item['imageUrl'] ) ? esc_url( $tnt_item['imageUrl'] ) : '';
	if ( $tnt_img_id ) {
		$tnt_media = wp_get_attachment_image( $tnt_img_id, 'large', false, array( 'class' => 'tunet-cslide__img' ) );
	} elseif ( '' !== $tnt_img_url ) {
		$tnt_media = '<img class="tunet-cslide__img" src="' . $tnt_img_url . '" alt="" />';
	}
	$tnt_media = '' !== $tnt_media ? '<div class="tunet-cslide__media">' . $tnt_media . '</div>' : '';

	// Textos.
	$tnt_title = isset( $tnt_item['title'] ) ? trim( wp_strip_all_tags( $tnt_item['title'] ) ) : '';
	$tnt_sub   = isset( $tnt_item['subtitle'] ) ? trim( wp_strip_all_tags( $tnt_item['subtitle'] ) ) : '';
	$tnt_desc  = isset( $tnt_item['description'] ) ? wp_kses_post( $tnt_item['description'] ) : '';

	$tnt_title_html = '' !== $tnt_title ? '<h3 class="tunet-cslide__title' . $tnt_align_class( isset( $tnt_item['titleAlign'] ) ? $tnt_item['titleAlign'] : '' ) . '">' . esc_html( $tnt_title ) . '</h3>' : '';
	$tnt_sub_html   = '' !== $tnt_sub ? '<p class="tunet-cslide__subtitle' . $tnt_align_class( isset( $tnt_item['subtitleAlign'] ) ? $tnt_item['subtitleAlign'] : '' ) . '">' . esc_html( $tnt_sub ) . '</p>' : '';
	$tnt_desc_html  = '' !== $tnt_desc ? '<div class="tunet-cslide__desc' . $tnt_align_class( isset( $tnt_item['descAlign'] ) ? $tnt_item['descAlign'] : '' ) . '">' . $tnt_desc . '</div>' : '';

	// Orden subtítulo/título.
	$tnt_head = ! empty( $tnt_item['subtitleFirst'] ) ? ( $tnt_sub_html . $tnt_title_html ) : ( $tnt_title_html . $tnt_sub_html );

	// Alineación de contenido (grupo).
	$tnt_content_align = isset( $tnt_item['contentAlign'] ) && in_array( $tnt_item['contentAlign'], array( 'left', 'center', 'right' ), true ) ? $tnt_item['contentAlign'] : 'left';

	$tnt_content = '<div class="tunet-cslide__content tunet-cslide__content--' . esc_attr( $tnt_content_align ) . '">'
		. $tnt_head . $tnt_desc_html
		. $tnt_render_ctas( isset( $tnt_item['ctas'] ) ? $tnt_item['ctas'] : array() )
		. '</div>';

	$tnt_slides .= '<div class="swiper-slide"><div class="tunet-cslide">' . $tnt_media . $tnt_content . '</div></div>';
}

if ( '' === $tnt_slides ) {
	return;
}

if ( class_exists( 'Tunet_Core_Runtime' ) ) {
	Tunet_Core_Runtime::enqueue_carousel();
}

$tnt_effect = isset( $attributes['effect'] ) ? sanitize_key( $attributes['effect'] ) : 'slide';
$tnt_spv    = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 1;
$tnt_space  = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$tnt_speed  = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$tnt_delay  = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$tnt_loop   = ! empty( $attributes['loop'] );
$tnt_auto   = ! empty( $attributes['autoplay'] );
$tnt_pag    = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$tnt_nav    = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$tnt_pspv   = in_array( $tnt_effect, array( 'fade', 'cards' ), true ) ? 1 : max( 1, $tnt_spv );

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'tunet-content-slider tunet-carousel swiper',
		'style'               => '--tnt-carousel-spv:' . $tnt_pspv . ';',
		'data-swiper-base'    => esc_url( TUNET_CORE_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => $tnt_effect,
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
		<?php echo $tnt_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado arriba. ?>
	</div>
	<?php if ( $tnt_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $tnt_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
