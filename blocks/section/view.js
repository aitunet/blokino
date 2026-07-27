/* ==========================================================================
 * Tunet Core · Block tunet/section — runtime de frontend (mínimo)
 * --------------------------------------------------------------------------
 * Responsabilidades sobre el VÍDEO de fondo (no pausable por CSS):
 *  - prefers-reduced-motion → detener el autoplay, PERO dejando el control para
 *    que quien quiera pueda reproducirlo. Respetar la preferencia es no arrancar
 *    solo; no es quitarle al visitante la posibilidad de ver el vídeo. Sin el
 *    control, la sección se queda en el fotograma 0 y parece una imagen rota.
 *  - Con movimiento normal → autoplay + el mismo control para pausar
 *    (WCAG 2.2.2 Pause, Stop, Hide).
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
	// startPaused = true cuando el vídeo NO arrancó (reduced-motion): el botón
	// nace en estado "play" para que se pueda iniciar a mano.
	function addPauseControl( v, startPaused ) {
		var section = v.closest ? v.closest( '.tunet-section' ) : null;
		if ( ! section || section.querySelector( '.tunet-section__video-toggle' ) ) {
			return;
		}
		var pauseLabel = section.getAttribute( 'data-pause-label' ) || 'Pause background video';
		var playLabel = section.getAttribute( 'data-play-label' ) || 'Play background video';
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'tunet-section__video-toggle';
		if ( startPaused ) {
			section.classList.add( 'is-video-paused' );
		}
		btn.setAttribute( 'aria-pressed', startPaused ? 'true' : 'false' );
		btn.setAttribute( 'aria-label', startPaused ? playLabel : pauseLabel );
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
		eachVideo( function ( v ) {
			if ( reduceMotion ) {
				stopForReducedMotion( v );
			}
			addPauseControl( v, reduceMotion );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
