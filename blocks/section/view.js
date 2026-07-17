/* ==========================================================================
 * Tunet Core · Block tunet/section — runtime de frontend (mínimo)
 * --------------------------------------------------------------------------
 * Responsabilidades sobre el VÍDEO de fondo (no pausable por CSS):
 *  - prefers-reduced-motion → detener el autoplay.
 *  - Con movimiento normal → inyectar un control de pausa/play accesible
 *    (WCAG 2.2.2 Pause, Stop, Hide) para el vídeo que autoreproduce en loop.
 * Los fondos CSS (gradiente/mesh) ya paran su animación vía media query.
 * Sin dependencias.
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof document === 'undefined' ) {
		return;
	}

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function eachVideo( fn ) {
		var videos = document.querySelectorAll( '.tunet-section.is-bg-video video.tunet-section__bg' );
		for ( var i = 0; i < videos.length; i++ ) {
			fn( videos[ i ] );
		}
	}

	function stopForReducedMotion( v ) {
		v.removeAttribute( 'autoplay' );
		v.autoplay = false;
		try { v.pause(); } catch ( e ) {}
	}

	// Control de pausa/play accesible (WCAG 2.2.2). Progressive enhancement.
	function addPauseControl( v ) {
		var section = v.closest ? v.closest( '.tunet-section' ) : null;
		if ( ! section || section.querySelector( '.tunet-section__video-toggle' ) ) {
			return;
		}
		var pauseLabel = section.getAttribute( 'data-pause-label' ) || 'Pause background video';
		var playLabel = section.getAttribute( 'data-play-label' ) || 'Play background video';
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'tunet-section__video-toggle';
		btn.setAttribute( 'aria-pressed', 'false' );
		btn.setAttribute( 'aria-label', pauseLabel );
		btn.innerHTML = '<span class="tunet-section__video-toggle-icon" aria-hidden="true"></span>';
		btn.addEventListener( 'click', function () {
			var paused;
			if ( v.paused ) {
				try { v.play(); } catch ( e ) {}
				paused = false;
			} else {
				try { v.pause(); } catch ( e ) {}
				paused = true;
			}
			section.classList.toggle( 'is-video-paused', paused );
			btn.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			btn.setAttribute( 'aria-label', paused ? playLabel : pauseLabel );
		} );
		section.appendChild( btn );
	}

	function init() {
		eachVideo( reduceMotion ? stopForReducedMotion : addPauseControl );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
