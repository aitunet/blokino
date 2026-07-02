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
		var size = opts.size && opts.size > 0 ? opts.size : 24;
		var paint = 'fill' === ic.mode
			? 'fill="currentColor"'
			: 'fill="none" stroke="currentColor" stroke-width="' + ( opts.stroke || 2 ) +
			  '" stroke-linecap="round" stroke-linejoin="round"';
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' + size +
			'" height="' + size + '" ' + paint + ' aria-hidden="true">' + ic.svg + '</svg>';
	};
} )();
