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

$tunet_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $tunet_items ) ) {
	return;
}

/**
 * Devuelve la clase text-align para un valor de campo (inherit = sin clase).
 */
$tunet_align_class = function ( $value ) {
	$value = is_string( $value ) ? $value : '';
	if ( in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
		return ' tunet-cslide__text--' . $value;
	}
	return '';
};

/**
 * Renderiza los CTAs de un slide.
 */
$tunet_render_ctas = function ( $ctas ) {
	if ( ! is_array( $ctas ) ) {
		return '';
	}
	$html = '';
	foreach ( $ctas as $cta ) {
		$text = ( isset( $cta['text'] ) && is_string( $cta['text'] ) ) ? trim( wp_strip_all_tags( $cta['text'] ) ) : '';
		$url  = isset( $cta['url'] ) ? esc_url( $cta['url'] ) : '';
		if ( '' === $text || '' === $url ) {
			continue;
		}
		$style = isset( $cta['style'] ) && in_array( $cta['style'], array( 'filled', 'outline', 'text' ), true ) ? $cta['style'] : 'filled';
		$target = ! empty( $cta['newTab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$href   = '' !== $url ? ' href="' . $url . '"' : '';
		$html  .= '<a class="tunet-cslide__cta tunet-cslide__cta--' . esc_attr( $style ) . '"' . $href . $target . '>' . esc_html( $text ) . '</a>';
	}
	return '' !== $html ? '<div class="tunet-cslide__ctas">' . $html . '</div>' : '';
};

$tunet_slides = '';
foreach ( $tunet_items as $tunet_item ) {
	// Imagen de FONDO del slide (background + overlay) con controles de posición
	// (foco), tamaño (cover/contain/auto) y repetición. El contenido va encima.
	$tunet_img_id  = isset( $tunet_item['imageId'] ) ? absint( $tunet_item['imageId'] ) : 0;
	$tunet_img_url = isset( $tunet_item['imageUrl'] ) ? esc_url_raw( $tunet_item['imageUrl'] ) : '';
	$tunet_bg_url  = '';
	if ( $tunet_img_id ) {
		// A CSS background gets no srcset; request a bounded size (not 'full') so
		// mobiles don't download an oversized hero image. Falls back to full.
		$tunet_bg_url = wp_get_attachment_image_url( $tunet_img_id, '2048x2048' );
		if ( ! $tunet_bg_url ) {
			$tunet_bg_url = wp_get_attachment_image_url( $tunet_img_id, 'full' );
		}
	}
	if ( ! $tunet_bg_url && '' !== $tunet_img_url ) {
		$tunet_bg_url = $tunet_img_url;
	}

	$tunet_bg = '';
	if ( $tunet_bg_url ) {
		// Posición por palabra clave (lista blanca de background-position válidas).
		$tunet_positions = array( 'left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom' );
		$tunet_bg_pos  = isset( $tunet_item['bgPosition'] ) && in_array( $tunet_item['bgPosition'], $tunet_positions, true ) ? $tunet_item['bgPosition'] : 'center center';
		$tunet_bg_size = isset( $tunet_item['bgSize'] ) && in_array( $tunet_item['bgSize'], array( 'cover', 'contain', 'auto' ), true ) ? $tunet_item['bgSize'] : 'cover';
		$tunet_bg_rep  = isset( $tunet_item['bgRepeat'] ) && in_array( $tunet_item['bgRepeat'], array( 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ), true ) ? $tunet_item['bgRepeat'] : 'no-repeat';
		$tunet_bg_style = 'background-image:url(' . esc_url( $tunet_bg_url ) . ');'
			. 'background-position:' . $tunet_bg_pos . ';'
			. 'background-size:' . $tunet_bg_size . ';'
			. 'background-repeat:' . $tunet_bg_rep . ';';
		$tunet_bg = '<div class="tunet-cslide__bg" style="' . esc_attr( $tunet_bg_style ) . '"></div>';

		// Overlay para legibilidad del texto sobre la imagen: color configurable (hex
		// del control; por defecto la tinta del theme) + opacidad (0–90).
		$tunet_ov = isset( $tunet_item['bgOverlay'] ) ? max( 0, min( 90, (int) $tunet_item['bgOverlay'] ) ) : 40;
		if ( $tunet_ov > 0 ) {
			$tunet_ov_type  = isset( $tunet_item['bgOverlayType'] ) ? sanitize_key( $tunet_item['bgOverlayType'] ) : 'color';
				/*
				 * Overlay en gradiente (scrim direccional). Este style se emite A MANO,
				 * solo con esc_attr(), así que NO pasa por safecss_filter_attr(): aquí
				 * no hay red de WordPress debajo y la validación es la única defensa.
				 * Antes bastaba con quitar los ';' y encontrar un 'gradient(' en
				 * cualquier parte de la cadena; ahora se exige que el valor ENTERO sea
				 * una función de gradiente, con el MISMO helper que usa tunet/section:
				 * un solo criterio para los dos sitios, en vez de uno flojo y otro
				 * estricto según por dónde saliera el valor.
				 */
				$tunet_ov_grad = '';
				if ( 'gradient' === $tunet_ov_type && ! empty( $tunet_item['bgOverlayGradient'] ) ) {
					$tunet_ov_grad = tunet_core_safe_css_gradient( $tunet_item['bgOverlayGradient'] );
				}
					/*
					 * `sanitize_hex_color()` devolvía '' con cualquier rgba(), y el color
					 * elegido se cambiaba en silencio por la tinta del fallback — pero el
					 * control del sidebar lleva enableAlpha, así que en cuanto el comprador
					 * tocaba la transparencia perdía su color. Se normaliza a hex de 8
					 * dígitos, que conserva el alpha (mismo helper que tunet/section).
					 */
					$tunet_ov_color = isset( $tunet_item['bgOverlayColor'] ) && is_string( $tunet_item['bgOverlayColor'] ) ? tunet_core_safe_css_color( $tunet_item['bgOverlayColor'] ) : '';
			$tunet_ov_bg    = $tunet_ov_grad ? $tunet_ov_grad : ( $tunet_ov_color ? $tunet_ov_color : 'var(--tnt-color-ink,#0b0b0f)' );
			$tunet_bg .= '<div class="tunet-cslide__overlay" style="opacity:' . number_format( $tunet_ov / 100, 2, '.', '' ) . ';background:' . esc_attr( $tunet_ov_bg ) . ';"></div>';
		}
	}

	// Textos.
	$tunet_title = ( isset( $tunet_item['title'] ) && is_string( $tunet_item['title'] ) ) ? trim( wp_strip_all_tags( $tunet_item['title'] ) ) : '';
	$tunet_sub   = ( isset( $tunet_item['subtitle'] ) && is_string( $tunet_item['subtitle'] ) ) ? trim( wp_strip_all_tags( $tunet_item['subtitle'] ) ) : '';
	$tunet_desc  = ( isset( $tunet_item['description'] ) && is_string( $tunet_item['description'] ) ) ? wp_kses_post( $tunet_item['description'] ) : '';

	$tunet_title_html = '' !== $tunet_title ? '<h3 class="tunet-cslide__title' . $tunet_align_class( isset( $tunet_item['titleAlign'] ) ? $tunet_item['titleAlign'] : '' ) . '">' . esc_html( $tunet_title ) . '</h3>' : '';
	$tunet_sub_html   = '' !== $tunet_sub ? '<p class="tunet-cslide__subtitle' . $tunet_align_class( isset( $tunet_item['subtitleAlign'] ) ? $tunet_item['subtitleAlign'] : '' ) . '">' . esc_html( $tunet_sub ) . '</p>' : '';
	$tunet_desc_html  = '' !== $tunet_desc ? '<div class="tunet-cslide__desc' . $tunet_align_class( isset( $tunet_item['descAlign'] ) ? $tunet_item['descAlign'] : '' ) . '">' . $tunet_desc . '</div>' : '';

	// Orden subtítulo/título.
	$tunet_head = ! empty( $tunet_item['subtitleFirst'] ) ? ( $tunet_sub_html . $tunet_title_html ) : ( $tunet_title_html . $tunet_sub_html );

	// Alineación de contenido (grupo).
	$tunet_content_align = isset( $tunet_item['contentAlign'] ) && in_array( $tunet_item['contentAlign'], array( 'left', 'center', 'right' ), true ) ? $tunet_item['contentAlign'] : 'left';

	$tunet_content = '<div class="tunet-cslide__content tunet-cslide__content--' . esc_attr( $tunet_content_align ) . '">'
		. $tunet_head . $tunet_desc_html
		. $tunet_render_ctas( isset( $tunet_item['ctas'] ) ? $tunet_item['ctas'] : array() )
		. '</div>';

	// Modificador según haya imagen de fondo: con media = slide con capa de imagen +
	// overlay y contenido superpuesto; solo texto = slide plano por tokens del theme.
	$tunet_cslide_class = 'tunet-cslide' . ( '' !== $tunet_bg ? ' tunet-cslide--media' : ' tunet-cslide--text' );
	$tunet_slides .= '<div class="swiper-slide"><div class="' . $tunet_cslide_class . '">' . $tunet_bg . $tunet_content . '</div></div>';
}

if ( '' === $tunet_slides ) {
	return;
}

if ( class_exists( 'Tunet_Core_Runtime' ) ) {
	Tunet_Core_Runtime::enqueue_carousel();
}

$tunet_effect = isset( $attributes['effect'] ) ? sanitize_key( $attributes['effect'] ) : 'slide';
$tunet_spv    = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 1;
$tunet_space  = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$tunet_speed  = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$tunet_delay  = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$tunet_loop   = ! empty( $attributes['loop'] );
$tunet_auto   = ! empty( $attributes['autoplay'] );
$tunet_pag    = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$tunet_nav    = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$tunet_arrows_out = $tunet_nav && ! empty( $attributes['arrowsOutside'] );
$tunet_pspv   = in_array( $tunet_effect, array( 'fade', 'cards' ), true ) ? 1 : max( 1, $tunet_spv );

$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'tunet-content-slider tunet-carousel swiper' . ( $tunet_arrows_out ? ' tunet-carousel--nav-outside' : '' ),
		'style'               => '--tnt-carousel-spv:' . $tunet_pspv . ';',
		'data-swiper-base'    => esc_url( TUNET_CORE_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => $tunet_effect,
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
		<?php echo $tunet_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado arriba. ?>
	</div>
	<?php if ( $tunet_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $tunet_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
