/* ==========================================================================
 * BloqUIX · Block bloquix/section — runtime de frontend (mínimo)
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
		var videos = document.querySelectorAll( '.bloquix-section.is-bg-video video.bloquix-section__bg' );
		for ( var i = 0; i < videos.length; i++ ) {
			fn( videos[ i ] );
		}
	}

	// Dejar el vídeo quieto: con reduced-motion o con el autoplay apagado por el
	// comprador. Si el render eligió la rama perezosa (hay póster) aquí no hay nada
	// descargado todavía, y con preload="none" sigue sin haberlo.
	function holdPaused( v ) {
		v.removeAttribute( 'autoplay' );
		v.autoplay = false;
		try { v.pause(); } catch ( e ) {}
	}

	// Arranque desde JS (rama perezosa: el HTML salió sin `autoplay` para no
	// descargar nada). muted por propiedad además de por atributo: sin ello algunos
	// navegadores rechazan el play() programático por su política de autoplay.
	function start( v ) {
		v.muted = true;
		var p = v.play();
		if ( p && typeof p.catch === 'function' ) {
			p.catch( function () {} );
		}
	}

	// Control de pausa/play accesible (WCAG 2.2.2). Progressive enhancement.
	// startPaused = true cuando el vídeo NO arrancó (reduced-motion): el botón
	// nace en estado "play" para que se pueda iniciar a mano.
	function addPauseControl( v, startPaused ) {
		var section = v.closest ? v.closest( '.bloquix-section' ) : null;
		if ( ! section || section.querySelector( '.bloquix-section__video-toggle' ) ) {
			return;
		}
		var pauseLabel = section.getAttribute( 'data-pause-label' ) || 'Pause background video';
		var playLabel = section.getAttribute( 'data-play-label' ) || 'Play background video';
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'bloquix-section__video-toggle';
		if ( startPaused ) {
			section.classList.add( 'is-video-paused' );
		}
		btn.setAttribute( 'aria-pressed', startPaused ? 'true' : 'false' );
		btn.setAttribute( 'aria-label', startPaused ? playLabel : pauseLabel );
		btn.innerHTML = '<span class="bloquix-section__video-toggle-icon" aria-hidden="true"></span>';
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
			// El comprador puede apagar el autoplay; reduced-motion siempre manda.
			var quiere = v.getAttribute( 'data-tnt-autoplay' ) !== '0';
			var suena  = quiere && ! reduceMotion;

			if ( suena ) {
				// Sin `autoplay` en el HTML = rama perezosa: lo arranca el JS.
				if ( ! v.autoplay ) {
					start( v );
				}
			} else {
				holdPaused( v );
			}
			addPauseControl( v, ! suena );

			// Sin loop, al terminar el control tiene que volver a "play" o se queda
			// en "pause" ofreciendo pausar algo que ya está parado.
			v.addEventListener( 'ended', function () {
				var section = v.closest ? v.closest( '.bloquix-section' ) : null;
				var btn = section && section.querySelector( '.bloquix-section__video-toggle' );
				if ( ! section || ! btn ) {
					return;
				}
				section.classList.add( 'is-video-paused' );
				btn.setAttribute( 'aria-pressed', 'true' );
				btn.setAttribute( 'aria-label', section.getAttribute( 'data-play-label' ) || 'Play background video' );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
