<?php
/**
 * Render del block bloquix/brand (dinámico).
 *
 * Marca del sitio: logo si está configurado (main/alt vía BloqUIX → Logos, o
 * el Site Logo nativo por custom_logo); si no, el título del sitio como texto.
 * Todo enlazado a Home. Reemplaza el par site-logo + site-title de los headers.
 *
 * Variante 'auto' (default): se pintan los DOS logos y el CSS decide cuál se ve,
 * leyendo --tnt-brand-main-display / --tnt-brand-alt-display del theme. Es lo que
 * permite que una style variation oscura saque el logo claro sin tocar el markup.
 * 'main'/'alt' siguen disponibles para forzar la variante en un sitio concreto.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bloquix_variant = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'auto';
if ( ! in_array( $bloquix_variant, array( 'auto', 'main', 'alt' ), true ) ) {
	$bloquix_variant = 'auto';
}
$bloquix_max  = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 140;
$bloquix_link = ! isset( $attributes['linkToHome'] ) || (bool) $attributes['linkToHome'];

// 1) Fuente de los logos: admin del motor si existe; si no, custom_logo nativo
// (degradación wp.org, §12.1: el motor tiene que servir sin un theme Tunet).
// OJO: Bloquix_Admin::logo_id('alt') cae al principal cuando no hay alternativo;
// para 'auto' hace falta saber si existe uno DE VERDAD, así que se lee el ajuste crudo.
$bloquix_has_admin = class_exists( 'Bloquix_Admin' );
$bloquix_main_id   = $bloquix_has_admin ? (int) Bloquix_Admin::logo_id( 'main' ) : (int) get_theme_mod( 'custom_logo' );
$bloquix_alt_id    = $bloquix_has_admin ? (int) Bloquix_Admin::get( 'logo_alt_id' ) : 0;

// 2) Qué se pinta.
// 'auto' (default) = los DOS logos, y quien decide cuál se ve es el CSS a través de
// --tnt-brand-main-display / --tnt-brand-alt-display (contrato §6). Así una style
// variation oscura saca el logo claro sola, sin tocar el markup ni el block: era
// justo lo que el campo "Alternative logo (dark backgrounds)" ya prometía y no hacía.
// Sin logo alternativo configurado, 'auto' se comporta exactamente como 'main'.
$bloquix_render_main = ( 'alt' !== $bloquix_variant );
$bloquix_render_alt  = ( 'main' !== $bloquix_variant ) && $bloquix_alt_id > 0;
if ( 'alt' === $bloquix_variant ) {
	// Variante forzada a mano: se respeta, con el fallback histórico al principal.
	$bloquix_alt_id     = $bloquix_has_admin ? (int) Bloquix_Admin::logo_id( 'alt' ) : 0;
	$bloquix_render_alt = $bloquix_alt_id > 0;
} elseif ( 'auto' === $bloquix_variant && ! $bloquix_main_id && $bloquix_alt_id ) {
	// Caso raro pero posible: solo hay logo alternativo. Pasa a ser el visible; si
	// no, el CSS lo dejaría en display:none y la marca desaparecería del header.
	$bloquix_main_id    = $bloquix_alt_id;
	$bloquix_render_alt = false;
}

// ¿Hay REALMENTE dos logos que conmutar? Solo entonces se emiten las clases de
// variante. Si hay uno solo, va sin modificador y NINGUNA regla de display lo
// puede ocultar: si no, una paleta oscura con --tnt-brand-main-display:none
// borraría la marca de un sitio que únicamente subió el logo principal.
$bloquix_two = $bloquix_render_main && $bloquix_render_alt && $bloquix_main_id && $bloquix_alt_id;

/**
 * <img> de un logo, con su clase de variante solo si hay conmutación.
 *
 * @param int    $id      Attachment ID.
 * @param string $variant main|alt.
 * @return string
 */
$bloquix_img = static function ( $id, $variant ) use ( $bloquix_two ) {
	if ( ! $id ) {
		return '';
	}
	$bloquix_class = 'bloquix-brand__img';
	if ( $bloquix_two ) {
		$bloquix_class .= ' bloquix-brand__img--' . $variant;
	}
	return wp_get_attachment_image(
		$id,
		'full',
		false,
		array(
			// Las dos van con el mismo alt: solo una está visible en cada momento y
			// display:none la saca del árbol de accesibilidad, así que no se duplica.
			'class' => $bloquix_class,
			'alt'   => get_bloginfo( 'name' ),
		)
	);
};

$bloquix_inner = '';
if ( $bloquix_render_main ) {
	$bloquix_inner .= $bloquix_img( $bloquix_main_id, 'main' );
}
if ( $bloquix_render_alt ) {
	$bloquix_inner .= $bloquix_img( $bloquix_alt_id, 'alt' );
}
$bloquix_has_logo = ( '' !== $bloquix_inner ); // false si el id quedó huérfano (imagen borrada) → fallback a título.
if ( ! $bloquix_has_logo ) {
	$bloquix_inner = '<span class="bloquix-brand__title">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}

// 3) Wrapper: <a> a Home (rel="home"), o <span> si linkToHome = false.
// El cap de ancho solo aplica al LOGO; el título de texto (fallback) va a su ancho
// natural — un sitio sin logo con nombre largo no se estrecha ni wrapea (degradación §12).
$bloquix_style = ( $bloquix_has_logo && $bloquix_max ) ? 'max-width:' . $bloquix_max . 'px' : '';
// has-alt = hay dos logos y por tanto conmutación real. Los themes lo usan para
// forzar una variante en bandas que son oscuras SIEMPRE (footers sobre --tnt-color-ink)
// sin arriesgarse a ocultar la marca de un sitio que solo subió un logo.
$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'bloquix-brand' . ( $bloquix_two ? ' has-alt' : '' ),
		'style' => $bloquix_style,
	)
);
$bloquix_tag  = $bloquix_link ? 'a' : 'span';
$bloquix_href = $bloquix_link ? ' href="' . esc_url( home_url( '/' ) ) . '" rel="home"' : '';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $bloquix_wrapper (WP), $bloquix_href (esc_url), $bloquix_inner (wp_get_attachment_image/esc_html), $bloquix_tag literal.
echo '<' . $bloquix_tag . ' ' . $bloquix_wrapper . $bloquix_href . '>' . $bloquix_inner . '</' . $bloquix_tag . '>';
