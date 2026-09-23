/* ==========================================================================
 * Blokino · Block blokino/before-after — runtime de frontend (vanilla)
 * --------------------------------------------------------------------------
 * Arrastre del divisor (pointer events) + teclado (flechas) sobre el slider.
 * Sin dependencias. Se carga solo si el block está en la página.
 * Si el JS no corre, queda una comparación estática en la posición inicial.
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof document === 'undefined' ) {
		return;
	}

	function clamp( n ) {
		return Math.max( 0, Math.min( 100, n ) );
	}

	function setup( el ) {
		var handle = el.querySelector( '.blokino-ba__handle' );
		var dragging = false;

		function setPos( clientX ) {
			var rect = el.getBoundingClientRect();
			if ( ! rect.width ) {
				return;
			}
			var pct = clamp( ( ( clientX - rect.left ) / rect.width ) * 100 );
			el.style.setProperty( '--tnt-ba-pos', pct + '%' );
			if ( handle ) {
				handle.setAttribute( 'aria-valuenow', Math.round( pct ) );
			}
		}

		el.addEventListener( 'pointerdown', function ( e ) {
			dragging = true;
			el.classList.add( 'is-dragging' );
			setPos( e.clientX );
			if ( el.setPointerCapture ) {
				try { el.setPointerCapture( e.pointerId ); } catch ( err ) {}
			}
		} );
		el.addEventListener( 'pointermove', function ( e ) {
			if ( dragging ) {
				setPos( e.clientX );
			}
		} );
		function stop() {
			dragging = false;
			el.classList.remove( 'is-dragging' );
		}
		el.addEventListener( 'pointerup', stop );
		el.addEventListener( 'pointercancel', stop );

		// Teclado sobre el tirador (role=slider).
		if ( handle ) {
			handle.addEventListener( 'keydown', function ( e ) {
				var cur = parseFloat( el.style.getPropertyValue( '--tnt-ba-pos' ) );
				if ( isNaN( cur ) ) {
					cur = parseFloat( handle.getAttribute( 'aria-valuenow' ) ) || 50;
				}
				var step = e.shiftKey ? 10 : 2;
				if ( e.key === 'ArrowLeft' || e.key === 'ArrowDown' ) {
					cur -= step;
				} else if ( e.key === 'ArrowRight' || e.key === 'ArrowUp' ) {
					cur += step;
				} else if ( e.key === 'Home' ) {
					cur = 0;
				} else if ( e.key === 'End' ) {
					cur = 100;
				} else {
					return;
				}
				e.preventDefault();
				cur = clamp( cur );
				el.style.setProperty( '--tnt-ba-pos', cur + '%' );
				handle.setAttribute( 'aria-valuenow', Math.round( cur ) );
			} );
		}
	}

	function init() {
		var nodes = document.querySelectorAll( '.blokino-ba' );
		for ( var i = 0; i < nodes.length; i++ ) {
			setup( nodes[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
