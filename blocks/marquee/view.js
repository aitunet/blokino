/* ==========================================================================
 * Tunet Core · Block tunet/marquee — runtime de frontend (fill-to-cover)
 * --------------------------------------------------------------------------
 * El loop sin costuras (translateX(-50%) sobre dos grupos idénticos) solo es
 * perfecto si CADA grupo es al menos tan ancho como el contenedor. Con poco
 * contenido (p. ej. 4–5 palabras en una banda full-width) un grupo queda más
 * angosto que el viewport → al desplazarse aparece un hueco vacío al final.
 *
 * Esta utilidad repite el contenido DENTRO de cada grupo hasta que el grupo
 * cubre el contenedor. Así la banda es seamless con cualquier cantidad de
 * contenido, sin que el theme tenga que duplicar ítems a mano. Es comportamiento
 * de la BASE: el marquee "simplemente funciona".
 *
 * prefers-reduced-motion → no hay animación (la banda es scrollable estática),
 * así que no hay hueco que rellenar: se omite.
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof document === 'undefined' ) {
		return;
	}

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function fill( marquee ) {
		var container = marquee.clientWidth;
		if ( ! container ) {
			return;
		}
		var groups = marquee.querySelectorAll( '.tunet-marquee__group' );
		for ( var i = 0; i < groups.length; i++ ) {
			var group = groups[ i ];
			// Guardar el contenido ORIGINAL una sola vez; en cada pasada se parte
			// de él (evita crecimiento exponencial al re-evaluar en resize).
			if ( typeof group.dataset.tntMarqueeBase === 'undefined' ) {
				group.dataset.tntMarqueeBase = group.innerHTML;
			}
			var base = group.dataset.tntMarqueeBase;
			if ( '' === base.trim() ) {
				continue;
			}
			group.innerHTML = base;
			// Repetir el contenido propio del grupo hasta cubrir el contenedor.
			// Guard alto pero finito por si el contenido fuese diminuto.
			// Las copias de relleno son DECORATIVAS: van en un envoltorio inert +
			// aria-hidden con display:contents (no altera el layout de la banda) para
			// no duplicar tab-stops / IDs ni exponerlas al lector de pantalla.
			var guard = 0;
			while ( group.scrollWidth < container && guard < 40 ) {
				var filler = document.createElement( 'span' );
				filler.setAttribute( 'inert', '' );
				filler.setAttribute( 'aria-hidden', 'true' );
				filler.style.display = 'contents';
				filler.innerHTML = base;
				group.appendChild( filler );
				guard++;
			}
		}
	}

	function init() {
		if ( reduceMotion ) {
			return;
		}
		var marquees = document.querySelectorAll( '.tunet-marquee' );
		for ( var i = 0; i < marquees.length; i++ ) {
			fill( marquees[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Re-evaluar en resize (el contenedor puede crecer y revelar un hueco).
	var resizeTimer = null;
	window.addEventListener( 'resize', function () {
		if ( resizeTimer ) {
			window.clearTimeout( resizeTimer );
		}
		resizeTimer = window.setTimeout( init, 200 );
	} );
} )();
