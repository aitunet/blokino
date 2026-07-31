<?php
/**
 * Render del block tunet/brand (dinámico).
 *
 * Marca del sitio: logo si está configurado (main/alt vía Tunet Core → Logos, o
 * el Site Logo nativo por custom_logo); si no, el título del sitio como texto.
 * Todo enlazado a Home. Reemplaza el par site-logo + site-title de los headers.
 *
 * Variante 'auto' (default): se pintan los DOS logos y el CSS decide cuál se ve,
 * leyendo --tnt-brand-main-display / --tnt-brand-alt-display del theme. Es lo que
 * permite que una style variation oscura saque el logo claro sin tocar el markup.
 * 'main'/'alt' siguen disponibles para forzar la variante en un sitio concreto.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tunet_variant = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'auto';
if ( ! in_array( $tunet_variant, array( 'auto', 'main', 'alt' ), true ) ) {
	$tunet_variant = 'auto';
}
$tunet_max  = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 140;
$tunet_link = ! isset( $attributes['linkToHome'] ) || (bool) $attributes['linkToHome'];

// 1) Fuente de los logos: admin del motor si existe; si no, custom_logo nativo
// (degradación wp.org, §12.1: el motor tiene que servir sin un theme Tunet).
// OJO: Tunet_Core_Admin::logo_id('alt') cae al principal cuando no hay alternativo;
// para 'auto' hace falta saber si existe uno DE VERDAD, así que se lee el ajuste crudo.
$tunet_has_admin = class_exists( 'Tunet_Core_Admin' );
$tunet_main_id   = $tunet_has_admin ? (int) Tunet_Core_Admin::logo_id( 'main' ) : (int) get_theme_mod( 'custom_logo' );
$tunet_alt_id    = $tunet_has_admin ? (int) Tunet_Core_Admin::get( 'logo_alt_id' ) : 0;

// 2) Qué se pinta.
// 'auto' (default) = los DOS logos, y quien decide cuál se ve es el CSS a través de
// --tnt-brand-main-display / --tnt-brand-alt-display (contrato §6). Así una style
// variation oscura saca el logo claro sola, sin tocar el markup ni el block: era
// justo lo que el campo "Alternative logo (dark backgrounds)" ya prometía y no hacía.
// Sin logo alternativo configurado, 'auto' se comporta exactamente como 'main'.
$tunet_render_main = ( 'alt' !== $tunet_variant );
$tunet_render_alt  = ( 'main' !== $tunet_variant ) && $tunet_alt_id > 0;
if ( 'alt' === $tunet_variant ) {
	// Variante forzada a mano: se respeta, con el fallback histórico al principal.
	$tunet_alt_id     = $tunet_has_admin ? (int) Tunet_Core_Admin::logo_id( 'alt' ) : 0;
	$tunet_render_alt = $tunet_alt_id > 0;
} elseif ( 'auto' === $tunet_variant && ! $tunet_main_id && $tunet_alt_id ) {
	// Caso raro pero posible: solo hay logo alternativo. Pasa a ser el visible; si
	// no, el CSS lo dejaría en display:none y la marca desaparecería del header.
	$tunet_main_id    = $tunet_alt_id;
	$tunet_render_alt = false;
}

// ¿Hay REALMENTE dos logos que conmutar? Solo entonces se emiten las clases de
// variante. Si hay uno solo, va sin modificador y NINGUNA regla de display lo
// puede ocultar: si no, una paleta oscura con --tnt-brand-main-display:none
// borraría la marca de un sitio que únicamente subió el logo principal.
$tunet_two = $tunet_render_main && $tunet_render_alt && $tunet_main_id && $tunet_alt_id;

/**
 * <img> de un logo, con su clase de variante solo si hay conmutación.
 *
 * @param int    $id      Attachment ID.
 * @param string $variant main|alt.
 * @return string
 */
$tunet_img = static function ( $id, $variant ) use ( $tunet_two ) {
	if ( ! $id ) {
		return '';
	}
	$tunet_class = 'tunet-brand__img';
	if ( $tunet_two ) {
		$tunet_class .= ' tunet-brand__img--' . $variant;
	}
	return wp_get_attachment_image(
		$id,
		'full',
		false,
		array(
			// Las dos van con el mismo alt: solo una está visible en cada momento y
			// display:none la saca del árbol de accesibilidad, así que no se duplica.
			'class' => $tunet_class,
			'alt'   => get_bloginfo( 'name' ),
		)
	);
};

$tunet_inner = '';
if ( $tunet_render_main ) {
	$tunet_inner .= $tunet_img( $tunet_main_id, 'main' );
}
if ( $tunet_render_alt ) {
	$tunet_inner .= $tunet_img( $tunet_alt_id, 'alt' );
}
$tunet_has_logo = ( '' !== $tunet_inner ); // false si el id quedó huérfano (imagen borrada) → fallback a título.
if ( ! $tunet_has_logo ) {
	$tunet_inner = '<span class="tunet-brand__title">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}

// 3) Wrapper: <a> a Home (rel="home"), o <span> si linkToHome = false.
// El cap de ancho solo aplica al LOGO; el título de texto (fallback) va a su ancho
// natural — un sitio sin logo con nombre largo no se estrecha ni wrapea (degradación §12).
$tunet_style = ( $tunet_has_logo && $tunet_max ) ? 'max-width:' . $tunet_max . 'px' : '';
// has-alt = hay dos logos y por tanto conmutación real. Los themes lo usan para
// forzar una variante en bandas que son oscuras SIEMPRE (footers sobre --tnt-color-ink)
// sin arriesgarse a ocultar la marca de un sitio que solo subió un logo.
$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'tunet-brand' . ( $tunet_two ? ' has-alt' : '' ),
		'style' => $tunet_style,
	)
);
$tunet_tag  = $tunet_link ? 'a' : 'span';
$tunet_href = $tunet_link ? ' href="' . esc_url( home_url( '/' ) ) . '" rel="home"' : '';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $tunet_wrapper (WP), $tunet_href (esc_url), $tunet_inner (wp_get_attachment_image/esc_html), $tunet_tag literal.
echo '<' . $tunet_tag . ' ' . $tunet_wrapper . $tunet_href . '>' . $tunet_inner . '</' . $tunet_tag . '>';
