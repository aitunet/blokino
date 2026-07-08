/* ==========================================================================
 * Tunet Core · Helper compartido del editor: window.tunetIconSvg
 * --------------------------------------------------------------------------
 * Fuente ÚNICA del <svg> en el editor (espejo de tunet_core_icon_svg en PHP).
 * La usan icon/edit.js y marquee/edit.js. Ramifica por `mode` (stroke|fill).
 * Lee window.tunetIcons (localize). Sin JSX ni build.
 * ========================================================================== */
( function () {
	'use strict';
	window.tunetIconSvg = function ( name, opts ) {
		var ICONS = window.tunetIcons || {};
		var ic = ICONS[ name ];
		if ( ! ic ) {
			return '';
		}
		opts = opts || {};
		// A diferencia del PHP (size:0 ⇒ SVG sin width/height, dimensionado por CSS),
		// el editor SIEMPRE pide un tamaño explícito → no replicamos el caso size:0.
		var size = opts.size && opts.size > 0 ? opts.size : 24;
		var paint = 'fill' === ic.mode
			? 'fill="currentColor"'
			: 'fill="none" stroke="currentColor" stroke-width="' + ( opts.stroke || 2 ) +
			  '" stroke-linecap="round" stroke-linejoin="round"';
		// Clase opcional en el <svg> (para que el CSS del bloque pueda pisar el
		// paint por defecto, p.ej. estrellas de rating). Solo se emite si se pide
		// → callers sin opts.class quedan byte-idénticos (retrocompat).
		var cls = opts.class ? ' class="' + opts.class + '"' : '';
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' + size +
			'" height="' + size + '"' + cls + ' ' + paint + ' aria-hidden="true">' + ic.svg + '</svg>';
	};
} )();
