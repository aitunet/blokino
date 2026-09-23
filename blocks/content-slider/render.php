<?php
/**
 * Render del block bloquix/content-slider (dinámico).
 *
 * Itera $attributes['items'] (repeater del sidebar) y arma slides estructurados
 * (imagen + grupo de contenido + CTAs) dentro del runtime Swiper COMPARTIDO
 * (.bloquix-carousel). Todo por tokens; escape estricto.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bloquix_items = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
if ( empty( $bloquix_items ) ) {
	return;
}

/**
 * Devuelve la clase text-align para un valor de campo (inherit = sin clase).
 */
$bloquix_align_class = function ( $value ) {
	$value = is_string( $value ) ? $value : '';
	if ( in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
		return ' bloquix-cslide__text--' . $value;
	}
	return '';
};

/**
 * Renderiza los CTAs de un slide.
 */
$bloquix_render_ctas = function ( $ctas ) {
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
		$html  .= '<a class="bloquix-cslide__cta bloquix-cslide__cta--' . esc_attr( $style ) . '"' . $href . $target . '>' . esc_html( $text ) . '</a>';
	}
	return '' !== $html ? '<div class="bloquix-cslide__ctas">' . $html . '</div>' : '';
};

$bloquix_slides = '';
foreach ( $bloquix_items as $bloquix_item ) {
	// Imagen de FONDO del slide (background + overlay) con controles de posición
	// (foco), tamaño (cover/contain/auto) y repetición. El contenido va encima.
	$bloquix_img_id  = isset( $bloquix_item['imageId'] ) ? absint( $bloquix_item['imageId'] ) : 0;
	$bloquix_img_url = isset( $bloquix_item['imageUrl'] ) ? esc_url_raw( $bloquix_item['imageUrl'] ) : '';
	$bloquix_bg_url  = '';
	if ( $bloquix_img_id ) {
		// A CSS background gets no srcset; request a bounded size (not 'full') so
		// mobiles don't download an oversized hero image. Falls back to full.
		$bloquix_bg_url = wp_get_attachment_image_url( $bloquix_img_id, '2048x2048' );
		if ( ! $bloquix_bg_url ) {
			$bloquix_bg_url = wp_get_attachment_image_url( $bloquix_img_id, 'full' );
		}
	}
	if ( ! $bloquix_bg_url && '' !== $bloquix_img_url ) {
		$bloquix_bg_url = $bloquix_img_url;
	}

	$bloquix_bg = '';
	if ( $bloquix_bg_url ) {
		// Posición por palabra clave (lista blanca de background-position válidas).
		$bloquix_positions = array( 'left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom' );
		$bloquix_bg_pos  = isset( $bloquix_item['bgPosition'] ) && in_array( $bloquix_item['bgPosition'], $bloquix_positions, true ) ? $bloquix_item['bgPosition'] : 'center center';
		$bloquix_bg_size = isset( $bloquix_item['bgSize'] ) && in_array( $bloquix_item['bgSize'], array( 'cover', 'contain', 'auto' ), true ) ? $bloquix_item['bgSize'] : 'cover';
		$bloquix_bg_rep  = isset( $bloquix_item['bgRepeat'] ) && in_array( $bloquix_item['bgRepeat'], array( 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ), true ) ? $bloquix_item['bgRepeat'] : 'no-repeat';
		$bloquix_bg_style = 'background-image:url(' . esc_url( $bloquix_bg_url ) . ');'
			. 'background-position:' . $bloquix_bg_pos . ';'
			. 'background-size:' . $bloquix_bg_size . ';'
			. 'background-repeat:' . $bloquix_bg_rep . ';';
		$bloquix_bg = '<div class="bloquix-cslide__bg" style="' . esc_attr( $bloquix_bg_style ) . '"></div>';

		// Overlay para legibilidad del texto sobre la imagen: color configurable (hex
		// del control; por defecto la tinta del theme) + opacidad (0–90).
		$bloquix_ov = isset( $bloquix_item['bgOverlay'] ) ? max( 0, min( 90, (int) $bloquix_item['bgOverlay'] ) ) : 40;
		if ( $bloquix_ov > 0 ) {
			$bloquix_ov_type  = isset( $bloquix_item['bgOverlayType'] ) ? sanitize_key( $bloquix_item['bgOverlayType'] ) : 'color';
				/*
				 * Overlay en gradiente (scrim direccional). Este style se emite A MANO,
				 * solo con esc_attr(), así que NO pasa por safecss_filter_attr(): aquí
				 * no hay red de WordPress debajo y la validación es la única defensa.
				 * Antes bastaba con quitar los ';' y encontrar un 'gradient(' en
				 * cualquier parte de la cadena; ahora se exige que el valor ENTERO sea
				 * una función de gradiente, con el MISMO helper que usa bloquix/section:
				 * un solo criterio para los dos sitios, en vez de uno flojo y otro
				 * estricto según por dónde saliera el valor.
				 */
				$bloquix_ov_grad = '';
				if ( 'gradient' === $bloquix_ov_type && ! empty( $bloquix_item['bgOverlayGradient'] ) ) {
					$bloquix_ov_grad = bloquix_safe_css_gradient( $bloquix_item['bgOverlayGradient'] );
				}
					/*
					 * `sanitize_hex_color()` devolvía '' con cualquier rgba(), y el color
					 * elegido se cambiaba en silencio por la tinta del fallback — pero el
					 * control del sidebar lleva enableAlpha, así que en cuanto el comprador
					 * tocaba la transparencia perdía su color. Se normaliza a hex de 8
					 * dígitos, que conserva el alpha (mismo helper que bloquix/section).
					 */
					$bloquix_ov_color = isset( $bloquix_item['bgOverlayColor'] ) && is_string( $bloquix_item['bgOverlayColor'] ) ? bloquix_safe_css_color( $bloquix_item['bgOverlayColor'] ) : '';
			$bloquix_ov_bg    = $bloquix_ov_grad ? $bloquix_ov_grad : ( $bloquix_ov_color ? $bloquix_ov_color : 'var(--tnt-color-ink,#0b0b0f)' );
			$bloquix_bg .= '<div class="bloquix-cslide__overlay" style="opacity:' . number_format( $bloquix_ov / 100, 2, '.', '' ) . ';background:' . esc_attr( $bloquix_ov_bg ) . ';"></div>';
		}
	}

	// Textos.
	$bloquix_title = ( isset( $bloquix_item['title'] ) && is_string( $bloquix_item['title'] ) ) ? trim( wp_strip_all_tags( $bloquix_item['title'] ) ) : '';
	$bloquix_sub   = ( isset( $bloquix_item['subtitle'] ) && is_string( $bloquix_item['subtitle'] ) ) ? trim( wp_strip_all_tags( $bloquix_item['subtitle'] ) ) : '';
	$bloquix_desc  = ( isset( $bloquix_item['description'] ) && is_string( $bloquix_item['description'] ) ) ? wp_kses_post( $bloquix_item['description'] ) : '';

	$bloquix_title_html = '' !== $bloquix_title ? '<h3 class="bloquix-cslide__title' . $bloquix_align_class( isset( $bloquix_item['titleAlign'] ) ? $bloquix_item['titleAlign'] : '' ) . '">' . esc_html( $bloquix_title ) . '</h3>' : '';
	$bloquix_sub_html   = '' !== $bloquix_sub ? '<p class="bloquix-cslide__subtitle' . $bloquix_align_class( isset( $bloquix_item['subtitleAlign'] ) ? $bloquix_item['subtitleAlign'] : '' ) . '">' . esc_html( $bloquix_sub ) . '</p>' : '';
	$bloquix_desc_html  = '' !== $bloquix_desc ? '<div class="bloquix-cslide__desc' . $bloquix_align_class( isset( $bloquix_item['descAlign'] ) ? $bloquix_item['descAlign'] : '' ) . '">' . $bloquix_desc . '</div>' : '';

	// Orden subtítulo/título.
	$bloquix_head = ! empty( $bloquix_item['subtitleFirst'] ) ? ( $bloquix_sub_html . $bloquix_title_html ) : ( $bloquix_title_html . $bloquix_sub_html );

	// Alineación de contenido (grupo).
	$bloquix_content_align = isset( $bloquix_item['contentAlign'] ) && in_array( $bloquix_item['contentAlign'], array( 'left', 'center', 'right' ), true ) ? $bloquix_item['contentAlign'] : 'left';

	$bloquix_content = '<div class="bloquix-cslide__content bloquix-cslide__content--' . esc_attr( $bloquix_content_align ) . '">'
		. $bloquix_head . $bloquix_desc_html
		. $bloquix_render_ctas( isset( $bloquix_item['ctas'] ) ? $bloquix_item['ctas'] : array() )
		. '</div>';

	// Modificador según haya imagen de fondo: con media = slide con capa de imagen +
	// overlay y contenido superpuesto; solo texto = slide plano por tokens del theme.
	$bloquix_cslide_class = 'bloquix-cslide' . ( '' !== $bloquix_bg ? ' bloquix-cslide--media' : ' bloquix-cslide--text' );
	$bloquix_slides .= '<div class="swiper-slide"><div class="' . $bloquix_cslide_class . '">' . $bloquix_bg . $bloquix_content . '</div></div>';
}

if ( '' === $bloquix_slides ) {
	return;
}

if ( class_exists( 'Bloquix_Runtime' ) ) {
	Bloquix_Runtime::enqueue_carousel();
}

$bloquix_effect = isset( $attributes['effect'] ) ? sanitize_key( $attributes['effect'] ) : 'slide';
$bloquix_spv    = isset( $attributes['slidesPerView'] ) ? (float) $attributes['slidesPerView'] : 1;
$bloquix_space  = isset( $attributes['spaceBetween'] ) ? max( 0, (int) $attributes['spaceBetween'] ) : 24;
$bloquix_speed  = isset( $attributes['speed'] ) ? max( 0, (int) $attributes['speed'] ) : 600;
$bloquix_delay  = isset( $attributes['autoplayDelay'] ) ? max( 0, (int) $attributes['autoplayDelay'] ) : 5000;
$bloquix_loop   = ! empty( $attributes['loop'] );
$bloquix_auto   = ! empty( $attributes['autoplay'] );
$bloquix_pag    = ! isset( $attributes['pagination'] ) || ! empty( $attributes['pagination'] );
$bloquix_nav    = ! isset( $attributes['navigation'] ) || ! empty( $attributes['navigation'] );
$bloquix_arrows_out = $bloquix_nav && ! empty( $attributes['arrowsOutside'] );
$bloquix_pspv   = in_array( $bloquix_effect, array( 'fade', 'cards' ), true ) ? 1 : max( 1, $bloquix_spv );

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'bloquix-content-slider bloquix-carousel swiper' . ( $bloquix_arrows_out ? ' bloquix-carousel--nav-outside' : '' ),
		'style'               => '--tnt-carousel-spv:' . $bloquix_pspv . ';',
		'data-swiper-base'    => esc_url( BLOQUIX_URL . 'runtime/vendor/swiper/' ),
		'data-effect'         => $bloquix_effect,
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
		<?php echo $bloquix_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado arriba. ?>
	</div>
	<?php if ( $bloquix_pag ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	<?php if ( $bloquix_nav ) : ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php endif; ?>
</div>
