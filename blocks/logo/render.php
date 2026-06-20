<?php
/**
 * Render del block tunet/logo (dinámico).
 *
 * Lee el logo de marca (principal/alternativo) gestionado en el motor y lo
 * imprime con srcset (retina automático). Sin logo configurado → no renderiza.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Tunet_Core_Admin' ) ) {
	return;
}

$tnt_variant = ( isset( $attributes['variant'] ) && 'alt' === $attributes['variant'] ) ? 'alt' : 'main';
$tnt_max     = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 160;
$tnt_id      = Tunet_Core_Admin::logo_id( $tnt_variant );

if ( ! $tnt_id ) {
	return; // No hay logo configurado.
}

$tnt_img = wp_get_attachment_image(
	$tnt_id,
	'full',
	false,
	array(
		'class' => 'tunet-logo__img',
		'alt'   => get_bloginfo( 'name' ),
	)
);

if ( ! $tnt_img ) {
	return;
}

$tnt_style   = $tnt_max ? 'max-width:' . $tnt_max . 'px' : '';
$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'tunet-logo',
		'style' => $tnt_style,
	)
);
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php echo $tnt_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup de imagen generado por WP. ?>
</div>
