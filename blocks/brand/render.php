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

$tnt_variant = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'auto';
if ( ! in_array( $tnt_variant, array( 'auto', 'main', 'alt' ), true ) ) {
	$tnt_variant = 'auto';
}
$tnt_max  = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 140;
$tnt_link = ! isset( $attributes['linkToHome'] ) || (bool) $attributes['linkToHome'];

// 1) Fuente de los logos: admin del motor si existe; si no, custom_logo nativo
// (degradación wp.org, §12.1: el motor tiene que servir sin un theme Tunet).
// OJO: Tunet_Core_Admin::logo_id('alt') cae al principal cuando no hay alternativo;
// para 'auto' hace falta saber si existe uno DE VERDAD, así que se lee el ajuste crudo.
$tnt_has_admin = class_exists( 'Tunet_Core_Admin' );
$tnt_main_id   = $tnt_has_admin ? (int) Tunet_Core_Admin::logo_id( 'main' ) : (int) get_theme_mod( 'custom_logo' );
$tnt_alt_id    = $tnt_has_admin ? (int) Tunet_Core_Admin::get( 'logo_alt_id' ) : 0;

// 2) Qué se pinta.
// 'auto' (default) = los DOS logos, y quien decide cuál se ve es el CSS a través de
// --tnt-brand-main-display / --tnt-brand-alt-display (contrato §6). Así una style
// variation oscura saca el logo claro sola, sin tocar el markup ni el block: era
// justo lo que el campo "Alternative logo (dark backgrounds)" ya prometía y no hacía.
// Sin logo alternativo configurado, 'auto' se comporta exactamente como 'main'.
$tnt_render_main = ( 'alt' !== $tnt_variant );
$tnt_render_alt  = ( 'main' !== $tnt_variant ) && $tnt_alt_id > 0;
if ( 'alt' === $tnt_variant ) {
	// Variante forzada a mano: se respeta, con el fallback histórico al principal.
	$tnt_alt_id     = $tnt_has_admin ? (int) Tunet_Core_Admin::logo_id( 'alt' ) : 0;
	$tnt_render_alt = $tnt_alt_id > 0;
} elseif ( 'auto' === $tnt_variant && ! $tnt_main_id && $tnt_alt_id ) {
	// Caso raro pero posible: solo hay logo alternativo. Pasa a ser el visible; si
	// no, el CSS lo dejaría en display:none y la marca desaparecería del header.
	$tnt_main_id    = $tnt_alt_id;
	$tnt_render_alt = false;
}

/**
 * <img> de un logo con su clase de variante.
 *
 * @param int    $id      Attachment ID.
 * @param string $variant main|alt.
 * @return string
 */
$tnt_img = static function ( $id, $variant ) {
	if ( ! $id ) {
		return '';
	}
	return wp_get_attachment_image(
		$id,
		'full',
		false,
		array(
			// Las dos van con el mismo alt: solo una está visible en cada momento y
			// display:none la saca del árbol de accesibilidad, así que no se duplica.
			'class' => 'tunet-brand__img tunet-brand__img--' . $variant,
			'alt'   => get_bloginfo( 'name' ),
		)
	);
};

$tnt_inner = '';
if ( $tnt_render_main ) {
	$tnt_inner .= $tnt_img( $tnt_main_id, 'main' );
}
if ( $tnt_render_alt ) {
	$tnt_inner .= $tnt_img( $tnt_alt_id, 'alt' );
}
$tnt_has_logo = ( '' !== $tnt_inner ); // false si el id quedó huérfano (imagen borrada) → fallback a título.
if ( ! $tnt_has_logo ) {
	$tnt_inner = '<span class="tunet-brand__title">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}

// 3) Wrapper: <a> a Home (rel="home"), o <span> si linkToHome = false.
// El cap de ancho solo aplica al LOGO; el título de texto (fallback) va a su ancho
// natural — un sitio sin logo con nombre largo no se estrecha ni wrapea (degradación §12).
$tnt_style   = ( $tnt_has_logo && $tnt_max ) ? 'max-width:' . $tnt_max . 'px' : '';
$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'tunet-brand',
		'style' => $tnt_style,
	)
);
$tnt_tag  = $tnt_link ? 'a' : 'span';
$tnt_href = $tnt_link ? ' href="' . esc_url( home_url( '/' ) ) . '" rel="home"' : '';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $tnt_wrapper (WP), $tnt_href (esc_url), $tnt_inner (wp_get_attachment_image/esc_html), $tnt_tag literal.
echo '<' . $tnt_tag . ' ' . $tnt_wrapper . $tnt_href . '>' . $tnt_inner . '</' . $tnt_tag . '>';
