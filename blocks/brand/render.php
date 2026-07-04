<?php
/**
 * Render del block tunet/brand (dinámico).
 *
 * Marca del sitio: logo si está configurado (main/alt vía Tunet Core → Logos, o
 * el Site Logo nativo por custom_logo); si no, el título del sitio como texto.
 * Todo enlazado a Home. Reemplaza el par site-logo + site-title de los headers.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tnt_variant = ( isset( $attributes['variant'] ) && 'alt' === $attributes['variant'] ) ? 'alt' : 'main';
$tnt_max     = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 140;
$tnt_link    = ! isset( $attributes['linkToHome'] ) || (bool) $attributes['linkToHome'];

// 1) Fuente del logo: admin del motor si existe; si no, custom_logo nativo (degradación wp.org).
$tnt_logo_id = class_exists( 'Tunet_Core_Admin' )
	? (int) Tunet_Core_Admin::logo_id( $tnt_variant )
	: (int) get_theme_mod( 'custom_logo' );

// 2) Contenido interno: <img> del logo (retina automático) o el título como texto (fallback).
$tnt_inner = '';
if ( $tnt_logo_id ) {
	$tnt_inner = wp_get_attachment_image(
		$tnt_logo_id,
		'full',
		false,
		array(
			'class' => 'tunet-brand__img',
			'alt'   => get_bloginfo( 'name' ),
		)
	);
}
if ( '' === $tnt_inner ) {
	$tnt_inner = '<span class="tunet-brand__title">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}

// 3) Wrapper: <a> a Home (rel="home"), o <span> si linkToHome = false.
$tnt_style   = $tnt_max ? 'max-width:' . $tnt_max . 'px' : '';
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
