<?php
/**
 * Tunet Core · Set de iconos del block tunet/icon.
 *
 * Fuente ÚNICA del markup de iconos: la usa render.php (front) + el separador-icono
 * de tunet/marquee, y se expone al editor (picker) por localize (window.tunetIcons).
 * Solo el markup INTERNO del <svg> (paths/shapes); el <svg> lo arma el helper.
 *
 * Cada icono declara `mode` ('stroke'|'fill') y `category`
 * ('general'|'nav'|'contact'|'brand').
 *
 * Licencias del arte de iconos (todas GPL-compatibles):
 *   - Iconos de trazo (mode:stroke): Lucide (ISC) / Feather (MIT).
 *   - Iconos de marca (mode:fill):   Simple Icons (CC0).
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tunet_core_icon_set' ) ) {
	/**
	 * @return array<string,array{label:string,svg:string,mode:string,category:string}>
	 */
	function tunet_core_icon_set() {
		static $set = null;
		if ( null !== $set ) {
			return $set;
		}
		$set = array(
			// --- Generales (trazo) --------------------------------------------
			'layout'         => array( 'label' => 'Layout',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>' ),
			'activity'       => array( 'label' => 'Activity',       'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>' ),
			'code'           => array( 'label' => 'Code',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/>' ),
			'search'         => array( 'label' => 'Search',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>' ),
			'star'           => array( 'label' => 'Star',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 21l-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/>' ),
			'clock'          => array( 'label' => 'Clock',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>' ),
			'zap'            => array( 'label' => 'Zap',            'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>' ),
			'layers'         => array( 'label' => 'Layers',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="m12 2 10 5-10 5L2 7l10-5z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>' ),
			'pen'            => array( 'label' => 'Pen',            'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>' ),
			'globe'          => array( 'label' => 'Globe',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>' ),
			'target'         => array( 'label' => 'Target',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>' ),
			'compass'        => array( 'label' => 'Compass',        'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12z"/>' ),
			'play'           => array( 'label' => 'Play',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M5 3 19 12 5 21z"/>' ),
			'check'          => array( 'label' => 'Check',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M20 6 9 17l-5-5"/>' ),
			'users'          => array( 'label' => 'Users',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>' ),
			'heart'          => array( 'label' => 'Heart',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>' ),
			'shield'         => array( 'label' => 'Shield',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' ),
			'grid'           => array( 'label' => 'Grid',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>' ),
			'award'          => array( 'label' => 'Award',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="8" r="6"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/>' ),
			// --- Acción / objeto (trazo) ---------------------------------------
			'download'       => array( 'label' => 'Download',       'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>' ),
			'upload'         => array( 'label' => 'Upload',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>' ),
			'share'          => array( 'label' => 'Share',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>' ),
			'link'           => array( 'label' => 'Link',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>' ),
			'trash'          => array( 'label' => 'Trash',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>' ),
			'bookmark'       => array( 'label' => 'Bookmark',       'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M17 3a2 2 0 0 1 2 2v15a1 1 0 0 1-1.496.868l-4.512-2.578a2 2 0 0 0-1.984 0l-4.512 2.578A1 1 0 0 1 5 20V5a2 2 0 0 1 2-2z"/>' ),
			'tag'            => array( 'label' => 'Tag',            'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>' ),
			'camera'         => array( 'label' => 'Camera',         'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/>' ),
			'image'          => array( 'label' => 'Image',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>' ),
			'quote'          => array( 'label' => 'Quote',          'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/><path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/>' ),
			'sun'            => array( 'label' => 'Sun',            'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>' ),
			'moon'           => array( 'label' => 'Moon',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"/>' ),
			'info'           => array( 'label' => 'Info',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>' ),
			'alert-circle'   => array( 'label' => 'Alert circle',   'mode' => 'stroke', 'category' => 'general', 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>' ),
			'user'           => array( 'label' => 'User',           'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>' ),
			'eye'            => array( 'label' => 'Eye',            'mode' => 'stroke', 'category' => 'general', 'svg' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>' ),
			// --- Nav / UI (trazo) ---------------------------------------------
			'arrow-right'    => array( 'label' => 'Arrow right',    'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M5 12h14M12 5l7 7-7 7"/>' ),
			'arrow-up-right' => array( 'label' => 'Arrow up-right', 'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M7 17 17 7M7 7h10v10"/>' ),
			'chevron-up'     => array( 'label' => 'Chevron up',     'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="m18 15-6-6-6 6"/>' ),
			'chevron-down'   => array( 'label' => 'Chevron down',   'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="m6 9 6 6 6-6"/>' ),
			'chevron-left'   => array( 'label' => 'Chevron left',   'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="m15 18-6-6 6-6"/>' ),
			'chevron-right'  => array( 'label' => 'Chevron right',  'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="m9 18 6-6-6-6"/>' ),
			'menu'           => array( 'label' => 'Menu',           'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/>' ),
			'close'          => array( 'label' => 'Close',          'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' ),
			'home'           => array( 'label' => 'Home',           'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>' ),
			'external-link'  => array( 'label' => 'External link',  'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>' ),
			'arrow-left'     => array( 'label' => 'Arrow left',     'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>' ),
			'arrow-down'     => array( 'label' => 'Arrow down',     'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>' ),
			'plus'           => array( 'label' => 'Plus',           'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M5 12h14"/><path d="M12 5v14"/>' ),
			'minus'          => array( 'label' => 'Minus',          'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M5 12h14"/>' ),
			'settings'       => array( 'label' => 'Settings',       'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/><circle cx="12" cy="12" r="3"/>' ),
			'filter'         => array( 'label' => 'Filter',         'mode' => 'stroke', 'category' => 'nav',     'svg' => '<path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/>' ),
			// --- Contacto (trazo) ---------------------------------------------
			'mail'           => array( 'label' => 'Mail',           'mode' => 'stroke', 'category' => 'contact', 'svg' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>' ),
			'message'        => array( 'label' => 'Message',        'mode' => 'stroke', 'category' => 'contact', 'svg' => '<path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5z"/>' ),
			'phone'          => array( 'label' => 'Phone',          'mode' => 'stroke', 'category' => 'contact', 'svg' => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>' ),
			'map-pin'        => array( 'label' => 'Map pin',        'mode' => 'stroke', 'category' => 'contact', 'svg' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>' ),
			'calendar'       => array( 'label' => 'Calendar',       'mode' => 'stroke', 'category' => 'contact', 'svg' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>' ),
			'send'           => array( 'label' => 'Send',           'mode' => 'stroke', 'category' => 'contact', 'svg' => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>' ),
		);
		return $set;
	}
}

if ( ! function_exists( 'tunet_core_icon_svg' ) ) {
	/**
	 * Arma un <svg> inline desde el set curado, ramificando por `mode`.
	 *
	 * @param string $slug Nombre del icono en tunet_core_icon_set().
	 * @param array  $args size(int,24;0=sin dims) · stroke(float,2; ignorado en fill) · class · label('' = decorativo).
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
		$mode   = isset( $set[ $slug ]['mode'] ) && 'fill' === $set[ $slug ]['mode'] ? 'fill' : 'stroke';

		$dims = $size > 0 ? sprintf( ' width="%1$d" height="%1$d"', $size ) : '';
		$cls  = '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '';
		$a11y = '' !== $label
			? ' role="img" aria-label="' . esc_attr( $label ) . '"'
			: ' aria-hidden="true" focusable="false"';

		if ( 'fill' === $mode ) {
			$paint = ' fill="currentColor"';
		} else {
			$paint = ' fill="none" stroke="currentColor" stroke-width="' . esc_attr( (string) $stroke ) .
				'" stroke-linecap="round" stroke-linejoin="round"';
		}

		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' . $cls . $dims . $paint . $a11y . '>' .
			$set[ $slug ]['svg'] . '</svg>';
	}
}
