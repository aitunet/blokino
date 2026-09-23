<?php
/**
 * Render del block blokino/content-slider (dinámico).
 *
 * Itera $attributes['items'] (repeater del sidebar) y arma slides estructurados
 * (imagen + grupo de contenido + CTAs) dentro del runtime Swiper COMPARTIDO
 * (.blokino-carousel). Todo por tokens; escape estricto.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blokino_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $blokino_items ) ) {
	return;
}

/**
 * Devuelve la clase text-align para un valor de campo (inherit = sin clase).
 */
$blokino_align_class = function ( $value ) {
	$value = is_string( $value ) ? $value : '';
	if ( in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
		return ' blokino-cslide__text--' . $value;
	}
	return '';
};

/**
 * Renderiza los CTAs de un slide.
 */
$blokino_render_ctas = function ( $ctas ) {
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
		$html  .= '<a class="blokino-cslide__cta blokino-cslide__cta--' . esc_attr( $style ) . '"' . $href . $target . '>' . esc_html( $text ) . '</a>';
	}
	return '' !== $html ? '<div class="blokino-cslide__ctas">' . $html . '</div>' : '';
};

$blokino_slides = '';
foreach ( $blokino_items as $blokino_item ) {
	// Imagen de FONDO del slide (background + overlay) con controles de posición
	// (foco), tamaño (cover/contain/auto) y repetición. El contenido va encima.
	$blokino_img_id  = isset( $blokino_item['imageId'] ) ? absint( $blokino_item['imageId'] ) : 0;
	$blokino_img_url = isset( $blokino_item['imageUrl'] ) ? esc_url_raw( $blokino_item['imageUrl'] ) : '';
	$blokino_bg_url  = '';
	if ( $blokino_img_id ) {
		// A CSS background gets no srcset; request a bounded size (not 'full') so
		// mobiles don't download an oversized hero image. Falls back to full.
		$blokino_bg_url = wp_get_attachment_image_url( $blokino_img_id, '2048x2048' );
		if ( ! $blokino_bg_url ) {
			$blokino_bg_url = wp_get_attachment_image_url( $blokino_img_id, 'full' );
		}
	}
	if ( ! $blokino_bg_url && '' !== $blokino_img_url ) {
		$blokino_bg_url = $blokino_img_url;
	}

	$blokino_bg = '';
	if ( $blokino_bg_url ) {
		// Posición por palabra clave (lista blanca de background-position válidas).
		$blokino_positions = array( 'left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom' );
		$blokino_bg_pos  = isset( $blokino_item['bgPosition'] ) && in_array( $blokino_item['bgPosition'], $blokino_positions, true ) ? $blokino_item['bgPosition'] : 'center center';
		$blokino_bg_size = isset( $blokino_item['bgSize'] ) && in_array( $blokino_item['bgSize'], array( 'cover', 'contain', 'auto' ), true ) ? $blokino_item['bgSize'] : 'cover';
		$blokino_bg_rep  = isset( $blokino_item['bgRepeat'] ) && in_array( $blokino_item['bgRepeat'], array( 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ), true ) ? $blokino_item['bgRepeat'] : 'no-repeat';
		$blokino_bg_style = 'background-image:url(' . esc_url( $blokino_bg_url ) . ');'
			. 'background-position:' . $blokino_bg_pos . ';'
			. 'background-size:' . $blokino_bg_size . ';'
			. 'background-repeat:' . $blokino_bg_rep . ';';
		$blokino_bg = '<div class="blokino-cslide__bg" style="' . esc_attr( $blokino_bg_style ) . '"></div>';

		// Overlay para legibilidad del texto sobre la imagen: color configurable (hex
		// del control; por defecto la tinta del theme) + opacidad (0–90).
		$blokino_ov = isset( $blokino_item['bgOverlay'] ) ? max( 0, min( 90, (int) $blokino_item['bgOverlay'] ) ) : 40;
		if ( $blokino_ov > 0 ) {
			$blokino_ov_type  = isset( $blokino_item['bgOverlayType'] ) ? sanitize_key( $blokino_item['bgOverlayType'] ) : 'color';
				/*
				 * Overlay en gradiente (scrim direccional). Este style se emite A MANO,
				 * solo con esc_attr(), así que NO pasa por safecss_filter_attr(): aquí
				 * no hay red de WordPress debajo y la validación es la única defensa.
				 * Antes bastaba con quitar los ';' y encontrar un 'gradient(' en
				 * cualquier parte de la cadena; ahora se exige que el valor ENTERO sea
				 * una función de gradiente, con el MISMO helper que usa blokino/section:
				 * un solo criterio para los dos sitios, en vez de uno flojo y otro
				 * estricto según por dónde saliera el valor.
				 */
				$blokino_ov_grad = '';
				if ( 'gradient' === $blokino_ov_type && ! empty( $blokino_item['bgOverlayGradient'] ) ) {
					$blokino_ov_grad = blokino_safe_css_gradient( $blokino_item['bgOverlayGradient'] );
				}
					/*
					 * `sanitize_hex_color()` devolvía '' con cualquier rgba(), y el color
					 * elegido se cambiaba en silencio por la tinta del fallback — pero el
					 * control del sidebar lleva enableAlpha, así que en cuanto el comprador
					 * tocaba la transparencia perdía su color. Se normaliza a hex de 8
					 * dígitos, que conserva el alpha (mismo helper que blokino/section).
					 */
					$blokino_ov_color = isset( $blokino_item['bgOverlayColor'] ) && is_string( $blokino_item['bgOverlayColor'] ) ? blokino_safe_css_color( $blokino_item['bgOverlayColor'] ) : '';
			$blokino_ov_bg    = $blokino_ov_grad ? $blokino_ov_grad : ( $blokino_ov_color ? $blokino_ov_color : 'var(--tnt-color-ink,#0b0b0f)' );
			$blokino_bg .= '<div class="blokino-cslide__overlay" style="opacity:' . number_format( $blokino_ov / 100, 2, '.', '' ) . ';background:' . esc_attr( $blokino_ov_bg ) . ';"></div>';
		}
	}

	// Textos.
	$blokino_title = ( isset( $blokino_item['title'] ) && is_string( $blokino_item['title'] ) ) ? trim( wp_strip_all_tags( $blokino_item['title'] ) ) : '';
	$blokino_sub   = ( isset( $blokino_item['subtitle'] ) && is_string( $blokino_item['subtitle'] ) ) ? trim( wp_strip_all_tags( $blokino_item['subtitle'] ) ) : '';
	$blokino_desc  = ( isset( $blokino_item['description'] ) && is_string( $blokino_item['description'] ) ) ? wp_kses_post( $blokino_item['description'] ) : '';

	$blokino_title_html = '' !== $blokino_title ? '<h3 class="blokino-cslide__title' . $blokino_align_class( isset( $blokino_item['titleAlign'] ) ? $blokino_item['titleAlign'] : '' ) . '">' . esc_html( $blokino_title ) . '</h3>' : '';
	$blokino_sub_html   = '' !== $blokino_sub ? '<p class="blokino-cslide__subtitle' . $blokino_align_class( isset( $blokino_item['subtitleAlign'] ) ? $blokino_item['subtitleAlign'] : '' ) . '">' . esc_html( $blokino_sub ) . '</p>' : '';
	$blokino_desc_html  = '' !== $blokino_desc ? '<div class="blokino-cslide__desc' . $blokino_align_class( isset( $blokino_item['descAlign'] ) ? $blokino_item['descAlign'] : '' ) . '">' . $blokino_desc . '</div>' : '';

	// Orden subtítulo/título.
	$blokino_head = ! empty( $blokino_item['subtitleFirst'] ) ? ( $blokino_sub_html . $blokino_title_html ) : ( $blokino_title_html . $blokino_sub_html );

	// Alineación de contenido (grupo).
	$blokino_content_align = isset( $blokino_item['contentAlign'] ) && in_array( $blokino_item['contentAlign'], array( 'left', 'center', 'right' ), true ) ? $blokino_item['contentAlign'] : 'left';

	$blokino_content = '<div class="blokino-cslide__content blokino-cslide__content--' . esc_attr( $blokino_content_align ) . '">'
		. $blokino_head . $blokino_desc_html
		. $blokino_render_ctas( isset( $blokino_item['ctas'] ) ? $blokino_item['ctas'] : array() )
		. '</div>';

	// Modificador según haya imagen de fondo: con media = slide con capa de imagen +
	// overlay y contenido superpuesto; solo texto = slide plano por tokens del theme.
	$blokino_cslide_class = 'blokino-cslide' . ( '' !== $blokino_bg ? ' blokino-cslide--media' : ' blokino-cslide--text' );
	$blokino_slides .= '<div class="swiper-slide"><div class="' . $blokino_cslide_class . '">' . $blokino_bg . $blokino_content . '</div></div>';
}

if ( '' === $blokino_slides ) {
	return;
}

if ( class_exists( 'Blokino_Runtime' ) ) {
	Blokino_Runtime::enqueue_carousel();
}

$blokino_effect = isset( $attributes['effect'] ) ? sanitize_key( $attributes['effect'] ) : 'slide';
$blokino_spv    = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 1;
$blokino_space  = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$blokino_speed  = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$blokino_delay  = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$blokino_loop   = ! empty( $attributes['loop'] );
$blokino_auto   = ! empty( $attributes['autoplay'] );
$blokino_pag    = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$blokino_nav    = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$blokino_arrows_out = $blokino_nav && ! empty( $attributes['arrowsOutside'] );
$blokino_pspv   = in_array( $blokino_effect, array( 'fade', 'cards' ), true ) ? 1 : max( 1, $blokino_spv );

$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'blokino-content-slider blokino-carousel swiper' . ( $blokino_arrows_out ? ' blokino-carousel--nav-outside' : '' ),
		'style'               => '--tnt-carousel-spv:' . $blokino_pspv . ';',
		'data-swiper-base'    => esc_url( BLOKINO_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => $blokino_effect,
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
		<?php echo $blokino_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado arriba. ?>
	</div>
	<?php if ( $blokino_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $blokino_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
