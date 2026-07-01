<?php
/**
 * Tunet Core · Set de iconos del block tunet/icon.
 *
 * Iconos de trazo (estilo Feather/Lucide, viewBox 24, `currentColor`). Fuente
 * ÚNICA: la usa render.php (front) y se expone al editor (picker) por localize.
 * Solo el markup INTERNO del <svg> (paths/shapes); el <svg> lo arma el render.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,array{label:string,svg:string}> nombre => label + inner SVG.
 */
function tunet_core_icon_set() {
	return array(
		'layout'         => array( 'label' => 'Layout',         'svg' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>' ),
		'activity'       => array( 'label' => 'Activity',       'svg' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>' ),
		'code'           => array( 'label' => 'Code',           'svg' => '<path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/>' ),
		'search'         => array( 'label' => 'Search',         'svg' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>' ),
		'star'           => array( 'label' => 'Star',           'svg' => '<path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 21l-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/>' ),
		'clock'          => array( 'label' => 'Clock',          'svg' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>' ),
		'zap'            => array( 'label' => 'Zap',            'svg' => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>' ),
		'layers'         => array( 'label' => 'Layers',         'svg' => '<path d="m12 2 10 5-10 5L2 7l10-5z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>' ),
		'pen'            => array( 'label' => 'Pen',            'svg' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>' ),
		'globe'          => array( 'label' => 'Globe',          'svg' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>' ),
		'target'         => array( 'label' => 'Target',         'svg' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>' ),
		'compass'        => array( 'label' => 'Compass',        'svg' => '<circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12z"/>' ),
		'play'           => array( 'label' => 'Play',           'svg' => '<path d="M5 3 19 12 5 21z"/>' ),
		'check'          => array( 'label' => 'Check',          'svg' => '<path d="M20 6 9 17l-5-5"/>' ),
		'arrow-right'    => array( 'label' => 'Arrow right',    'svg' => '<path d="M5 12h14M12 5l7 7-7 7"/>' ),
		'arrow-up-right' => array( 'label' => 'Arrow up-right', 'svg' => '<path d="M7 17 17 7M7 7h10v10"/>' ),
		'mail'           => array( 'label' => 'Mail',           'svg' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>' ),
		'message'        => array( 'label' => 'Message',        'svg' => '<path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5z"/>' ),
		'users'          => array( 'label' => 'Users',          'svg' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>' ),
		'heart'          => array( 'label' => 'Heart',          'svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>' ),
		'shield'         => array( 'label' => 'Shield',         'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' ),
		'grid'           => array( 'label' => 'Grid',           'svg' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>' ),
		'award'          => array( 'label' => 'Award',          'svg' => '<circle cx="12" cy="8" r="6"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/>' ),
	);
}

/**
 * Arma un <svg> inline desde el set curado. Fuente ÚNICA del markup del icono:
 * la usan icon/render.php (front del bloque) y el separador-icono de tunet/marquee.
 *
 * @param string $slug Nombre del icono en tunet_core_icon_set().
 * @param array  $args size(int,24; 0=sin dims) · stroke(float,2) · class(string) · label(string,''=decorativo).
 * @return string <svg>…</svg> o '' si el slug no existe.
 */
function tunet_core_icon_svg( $slug, $args = array() ) {
	$set  = tunet_core_icon_set();
	$slug = sanitize_key( (string) $slug );
	if ( ! isset( $set[ $slug ] ) ) {
		return '';
	}
	$args = array_merge(
		array(
			'size'   => 24,
			'stroke' => 2,
			'class'  => '',
			'label'  => '',
		),
		is_array( $args ) ? $args : array()
	);

	$size   = (int) $args['size'];
	$stroke = (float) $args['stroke'];
	$class  = trim( (string) $args['class'] );
	$label  = trim( (string) $args['label'] );

	$dims = $size > 0 ? sprintf( ' width="%1$d" height="%1$d"', $size ) : '';
	$cls  = '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '';
	$a11y = '' !== $label
		? ' role="img" aria-label="' . esc_attr( $label ) . '"'
		: ' aria-hidden="true" focusable="false"';

	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' . $cls . $dims .
		' fill="none" stroke="currentColor" stroke-width="' . esc_attr( (string) $stroke ) .
		'" stroke-linecap="round" stroke-linejoin="round"' . $a11y . '>' .
		$set[ $slug ]['svg'] . '</svg>';
}
