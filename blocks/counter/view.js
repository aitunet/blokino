/* ==========================================================================
 * Tunet Core · Block tunet/counter — runtime de frontend (vanilla)
 * --------------------------------------------------------------------------
 * Anima el número de `start` a `end` al entrar en viewport (IntersectionObserver
 * + requestAnimationFrame, easing easeOutExpo). Sin dependencias.
 * Se carga SOLO si el block está en la página (viewScript de block.json).
 *
 * prefers-reduced-motion → muestra el valor final al instante (sin contar).
 * Si el JS no corre, el servidor ya pintó el valor final (fallback accesible).
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof document === 'undefined' ) {
		return;
	}

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function format( value, decimals, separator ) {
		var fixed = value.toFixed( decimals );
		if ( ! separator ) {
			return fixed;
		}
		var parts = fixed.split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		return parts.join( '.' );
	}

	// easeOutExpo: arranque rápido, frenada suave (sensación premium).
	function ease( t ) {
		return t === 1 ? 1 : 1 - Math.pow( 2, -10 * t );
	}

	function animate( el ) {
		var num = el.querySelector( '.tunet-counter__num' );
		if ( ! num ) {
			return;
		}
		var start = parseFloat( el.getAttribute( 'data-start' ) ) || 0;
		var end = parseFloat( el.getAttribute( 'data-end' ) ) || 0;
		var duration = parseInt( el.getAttribute( 'data-duration' ), 10 ) || 0;
		var decimals = parseInt( el.getAttribute( 'data-decimals' ), 10 ) || 0;
		var separator = el.getAttribute( 'data-separator' ) === '1';

		if ( reduceMotion || duration <= 0 ) {
			num.textContent = format( end, decimals, separator );
			return;
		}

		var t0 = null;
		function step( now ) {
			if ( t0 === null ) {
				t0 = now;
			}
			var p = Math.min( ( now - t0 ) / duration, 1 );
			var value = start + ( end - start ) * ease( p );
			num.textContent = format( value, decimals, separator );
			if ( p < 1 ) {
				window.requestAnimationFrame( step );
			} else {
				num.textContent = format( end, decimals, separator );
			}
		}
		window.requestAnimationFrame( step );
	}

	function init() {
		var nodes = document.querySelectorAll( '[data-tnt-counter]' );
		if ( ! nodes.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			// Sin soporte: deja el valor final (ya renderizado por el servidor).
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						animate( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -10% 0px', threshold: 0 }
		);

		for ( var i = 0; i < nodes.length; i++ ) {
			observer.observe( nodes[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
