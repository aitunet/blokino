/* ==========================================================================
 * Tunet Core · Block tunet/section — runtime de frontend (mínimo)
 * --------------------------------------------------------------------------
 * Única responsabilidad: respetar prefers-reduced-motion en el vídeo de fondo
 * (que no se puede pausar por CSS). Los fondos CSS (gradiente/mesh) ya paran su
 * animación vía media query. Sin dependencias.
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof document === 'undefined' ) {
		return;
	}

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( ! reduceMotion ) {
		return;
	}

	function init() {
		var videos = document.querySelectorAll( '.tunet-section.is-bg-video video.tunet-section__bg' );
		for ( var i = 0; i < videos.length; i++ ) {
			var v = videos[ i ];
			v.removeAttribute( 'autoplay' );
			v.autoplay = false;
			try { v.pause(); } catch ( e ) {}
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
