/* ==========================================================================
 * Tunet Core · Runtime de efectos — FASE B · LOTE 1 (entrada)
 * --------------------------------------------------------------------------
 * Se carga en el footer con defer (no bloquea el render). El gate anti-FOUC
 * (.tunet-tf-ready) lo añade un snippet inline en el <head>; aquí solo se
 * reafirma de forma defensiva por si esa vía no se emitió.
 *
 * Responsabilidades (todo vanilla, sin GSAP):
 *   1. Reafirma .tunet-tf-ready en <html> (defensivo).
 *   2. text-stagger → parte el texto en palabras (.tf-word) con índice --tf-i.
 *   3. tfStagger    → marca los hijos directos como .tf-stagger-item con --tf-i.
 *   4. Observa con IntersectionObserver y añade .is-tf-in al entrar en
 *      viewport. La transición la hace el CSS, leyendo los tokens --tnt-*.
 *   5. HOVER (LOTE 2): tilt y magnetic alimentan CSS vars desde el puntero
 *      (lift/glow/underline-grow/image-zoom son CSS puro, no pasan por aquí).
 *   6. SCROLL (LOTE 3): parallax (--tf-parallax-y) y progress (--tf-progress)
 *      con rAF, salvo que el navegador soporte scroll-driven CSS nativo
 *      (entonces no se ejecuta rAF). sticky-pin/blend/border-fx son CSS puro.
 *   7. prefers-reduced-motion / sin IO / sin hover real → degrada con gracia.
 *
 * Regla: nada se activa salvo que un block lo pida (opt-in vía data-tf-*).
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof window === 'undefined' || typeof document === 'undefined' ) {
		return;
	}

	var root = document.documentElement;

	// Reafirmación defensiva del gate (la vía principal es el snippet inline).
	root.classList.add( 'tunet-tf-ready' );

	window.tunetCore = window.tunetCore || {};
	window.tunetCore.runtimeLoaded = true;

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var SELECTOR = '[data-tf-animation], [data-tf-stagger], [data-tf-scroll="reveal-on-scroll"]';

	// Soporte de scroll-driven CSS: si existe, NO ejecutamos rAF (lo hace el
	// navegador de forma nativa, sin duplicar trabajo).
	var supportsViewTimeline = !! ( window.CSS && CSS.supports && CSS.supports( 'animation-timeline: view()' ) );
	var supportsScrollTimeline = !! ( window.CSS && CSS.supports && CSS.supports( 'animation-timeline: scroll()' ) );

	function reveal( node ) {
		node.classList.add( 'is-tf-in' );
	}

	/* --------------------------------------------------------------------
	 * text-stagger: envuelve cada palabra en <span class="tf-word"> con su
	 * índice. Procesa nodos de texto recursivamente para no romper markup
	 * interno (enlaces, énfasis…). Idempotente.
	 * ------------------------------------------------------------------ */
	function splitWords( el ) {
		if ( el.getAttribute( 'data-tf-split' ) === '1' ) {
			return;
		}
		var counter = { n: 0 };
		glueInline( el );
		walkText( el, counter );
		el.setAttribute( 'data-tf-split', '1' );
	}

	/* Una palabra con markup inline pegado —"do<mark>e</mark>rs", "<em>a</em>go",
	 * "re<strong>mark</strong>able"— NO es tres palabras. Antes de partir por
	 * espacios, los fragmentos de texto pegados (sin espacio) a un elemento
	 * inline se envuelven con él en un único <span class="tf-word"> (ya
	 * indexado luego por walkText en orden de documento). Recursivo, y solo
	 * para inline pequeños: enlaces, énfasis, marcas, spans. */
	var GLUE_TAGS = { A: 1, ABBR: 1, B: 1, EM: 1, I: 1, MARK: 1, SMALL: 1, SPAN: 1, STRONG: 1, SUB: 1, SUP: 1, U: 1 };

	function glueInline( node ) {
		var children = Array.prototype.slice.call( node.childNodes );
		children.forEach( function ( child ) {
			if ( child.nodeType !== 1 || ! GLUE_TAGS[ child.tagName ] || child.classList.contains( 'tf-word' ) ) {
				if ( child.nodeType === 1 && child.childNodes.length && ! child.classList.contains( 'tf-word' ) ) {
					glueInline( child );
				}
				return;
			}
			var prev = child.previousSibling;
			var next = child.nextSibling;
			var before = ( prev && prev.nodeType === 3 ) ? prev.textContent.match( /\S+$/ ) : null;
			var after = ( next && next.nodeType === 3 ) ? next.textContent.match( /^\S+/ ) : null;
			if ( ! before && ! after ) {
				return;
			}
			var span = document.createElement( 'span' );
			span.className = 'tf-word';
			node.insertBefore( span, child );
			if ( before ) {
				prev.textContent = prev.textContent.slice( 0, -before[ 0 ].length );
				span.appendChild( document.createTextNode( before[ 0 ] ) );
			}
			span.appendChild( child );
			if ( after ) {
				next.textContent = next.textContent.slice( after[ 0 ].length );
				span.appendChild( document.createTextNode( after[ 0 ] ) );
			}
		} );
	}

	function walkText( node, counter ) {
		var children = Array.prototype.slice.call( node.childNodes );
		children.forEach( function ( child ) {
			if ( child.nodeType === 1 && child.classList.contains( 'tf-word' ) && ! child.style.getPropertyValue( '--tf-i' ) ) {
				child.style.setProperty( '--tf-i', counter.n++ );
				return;
			}
			if ( child.nodeType === 3 ) {
				if ( ! child.textContent.trim() ) {
					return;
				}
				var frag = document.createDocumentFragment();
				var parts = child.textContent.split( /(\s+)/ );
				parts.forEach( function ( part ) {
					if ( part === '' ) {
						return;
					}
					if ( ! part.trim() ) {
						frag.appendChild( document.createTextNode( part ) );
						return;
					}
					var span = document.createElement( 'span' );
					span.className = 'tf-word';
					span.style.setProperty( '--tf-i', counter.n++ );
					span.textContent = part;
					frag.appendChild( span );
				} );
				node.replaceChild( frag, child );
			} else if ( child.nodeType === 1 && child.childNodes.length ) {
				walkText( child, counter );
			}
		} );
	}

	/* --------------------------------------------------------------------
	 * tfStagger: marca los hijos directos con índice para el escalonado.
	 * Heredan las --tf-from-* del contenedor → reutilizan su animación.
	 * Idempotente.
	 * ------------------------------------------------------------------ */
	function indexChildren( el ) {
		if ( el.getAttribute( 'data-tf-stagger-init' ) === '1' ) {
			return;
		}
		var kids = el.children;
		for ( var i = 0; i < kids.length; i++ ) {
			kids[ i ].classList.add( 'tf-stagger-item' );
			kids[ i ].style.setProperty( '--tf-i', i );
		}
		el.setAttribute( 'data-tf-stagger-init', '1' );
	}

	function prepare( nodes ) {
		for ( var i = 0; i < nodes.length; i++ ) {
			var el = nodes[ i ];
			var anim = el.getAttribute( 'data-tf-animation' );
			if ( anim === 'text-stagger' || anim === 'text-fill' ) {
				splitWords( el );
				if ( anim === 'text-fill' ) {
					markAccentWords( el );
				}
			}
			if ( el.hasAttribute( 'data-tf-stagger' ) ) {
				indexChildren( el );
			}
		}
	}

	// text-fill: marca las palabras (1-based) listadas en data-tf-fill-accent.
	function markAccentWords( el ) {
		var raw = el.getAttribute( 'data-tf-fill-accent' );
		if ( ! raw ) { return; }
		var want = {};
		raw.split( /[\s,]+/ ).forEach( function ( n ) {
			var i = parseInt( n, 10 );
			if ( i > 0 ) { want[ i - 1 ] = true; }
		} );
		var words = el.querySelectorAll( '.tf-word' );
		for ( var k = 0; k < words.length; k++ ) {
			if ( want[ k ] ) { words[ k ].classList.add( 'tf-word--accent' ); }
		}
	}

	function init() {
		var nodes = document.querySelectorAll( SELECTOR );
		if ( ! nodes.length ) {
			return;
		}

		prepare( nodes );

		// Sin movimiento (preferencia del usuario) o sin soporte de IO:
		// mostrar todo de inmediato, estático y visible (§10).
		if ( reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
			for ( var i = 0; i < nodes.length; i++ ) {
				reveal( nodes[ i ] );
			}
			return;
		}

		// threshold 0: revela en cuanto cualquier parte entra (el rootMargin
		// negativo inferior lo adelanta un poco). Con 0.1 un elemento más alto
		// que ~10x el viewport (p. ej. una imagen grande) nunca alcanzaría el
		// ratio y se quedaría oculto. El observer re-evalúa solo cuando una
		// imagen carga tarde y cambia el layout.
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						reveal( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -10% 0px', threshold: 0 }
		);

		// Observar TODO (incluido lo que ya está en viewport). El observer
		// dispara su primer callback de forma ASÍNCRONA, así que el estado
		// oculto se pinta primero y los elementos en pantalla animan al cargar
		// (no se "snappean"). threshold 0 cubre elementos más altos que el
		// viewport (p. ej. imágenes grandes).
		for ( var j = 0; j < nodes.length; j++ ) {
			observer.observe( nodes[ j ] );
		}
	}

	/* --------------------------------------------------------------------
	 * HOVER interactivo (LOTE 2) — vanilla, sin GSAP.
	 * lift / glow / underline-grow / image-zoom son CSS puro (no necesitan JS).
	 * tilt y magnetic sí: alimentan CSS vars desde el puntero.
	 * Se omiten sin capacidad de hover real (táctil) o con reduce-motion.
	 * ------------------------------------------------------------------ */
	var canHover = window.matchMedia && window.matchMedia( '(hover: hover)' ).matches;

	// tilt: escribe SOLO --tf-rot-x/--tf-rot-y; el transform compuesto las lee
	// y la transición corta (dur-fast) suaviza entre eventos de puntero.
	function setupTilt( el ) {
		var maxDeg = 8;
		el.addEventListener( 'pointermove', function ( e ) {
			var r = el.getBoundingClientRect();
			var px = ( e.clientX - r.left ) / r.width - 0.5;
			var py = ( e.clientY - r.top ) / r.height - 0.5;
			el.style.setProperty( '--tf-rot-y', ( px * maxDeg * 2 ).toFixed( 2 ) + 'deg' );
			el.style.setProperty( '--tf-rot-x', ( -py * maxDeg * 2 ).toFixed( 2 ) + 'deg' );
		} );
		el.addEventListener( 'pointerleave', function () {
			el.style.setProperty( '--tf-rot-x', '0deg' );
			el.style.setProperty( '--tf-rot-y', '0deg' );
		} );
	}

	// magnetic: escribe SOLO --tf-hover-x/--tf-hover-y; el suavizado lo hace la
	// transición spring del CSS (sin lerp en rAF), y compone con el resto.
	function setupMagnetic( el ) {
		var strength = 0.3;
		el.addEventListener( 'pointermove', function ( e ) {
			var r = el.getBoundingClientRect();
			var x = ( e.clientX - ( r.left + r.width / 2 ) ) * strength;
			var y = ( e.clientY - ( r.top + r.height / 2 ) ) * strength;
			el.style.setProperty( '--tf-hover-x', x.toFixed( 2 ) + 'px' );
			el.style.setProperty( '--tf-hover-y', y.toFixed( 2 ) + 'px' );
		} );
		el.addEventListener( 'pointerleave', function () {
			el.style.setProperty( '--tf-hover-x', '0px' );
			el.style.setProperty( '--tf-hover-y', '0px' );
		} );
	}

	function initHover() {
		if ( reduceMotion || ! canHover ) {
			return;
		}
		var tilts = document.querySelectorAll( '[data-tf-hover="tilt"]' );
		for ( var i = 0; i < tilts.length; i++ ) {
			setupTilt( tilts[ i ] );
		}
		var mags = document.querySelectorAll( '[data-tf-hover="magnetic"]' );
		for ( var j = 0; j < mags.length; j++ ) {
			setupMagnetic( mags[ j ] );
		}
	}

	/* --------------------------------------------------------------------
	 * SCROLL interactivo (LOTE 3) — vanilla, sin GSAP.
	 * Baseline rAF para parallax (--tf-parallax-y) y progress (--tf-progress).
	 * Se omite cada uno si el navegador soporta su scroll-driven CSS nativo.
	 * reveal-on-scroll lo gestiona el IntersectionObserver de init().
	 * sticky-pin, blend y border-fx son CSS puro (no pasan por aquí).
	 * ------------------------------------------------------------------ */
	function initScroll() {
		var parallax = ( reduceMotion || supportsViewTimeline )
			? []
			: Array.prototype.slice.call( document.querySelectorAll( '[data-tf-scroll="parallax"]' ) );
		// `progress` NO se gatea con reduceMotion a propósito (parallax y zoom sí):
		// es un INDICADOR DE ESTADO —cuánto llevas leído—, no movimiento decorativo.
		// Desactivarlo bajo prefers-reduced-motion dejaría la barra clavada en 0%,
		// que es un indicador roto, no una mejora de accesibilidad.
		var progress = supportsScrollTimeline
			? []
			: Array.prototype.slice.call( document.querySelectorAll( '[data-tf-scroll="progress"]' ) );
		var zoom = ( reduceMotion || supportsViewTimeline )
			? []
			: Array.prototype.slice.call( document.querySelectorAll( '[data-tf-scroll="zoom"]' ) );

		if ( ! parallax.length && ! progress.length && ! zoom.length ) {
			return;
		}

		var ticking = false;

		function update() {
			var vh = window.innerHeight;

			parallax.forEach( function ( el ) {
				var rect = el.getBoundingClientRect();
				var center = rect.top + rect.height / 2;
				var delta = center - vh / 2;
				var speed = parseFloat( el.style.getPropertyValue( '--tf-parallax-speed' ) ) || 20;
				var y = -delta * ( speed / 100 );
				el.style.setProperty( '--tf-parallax-y', y.toFixed( 1 ) + 'px' );
			} );

			if ( progress.length ) {
				var max = document.documentElement.scrollHeight - vh;
				var p = max > 0 ? window.scrollY / max : 0;
				if ( p < 0 ) { p = 0; } else if ( p > 1 ) { p = 1; }
				progress.forEach( function ( el ) {
					el.style.setProperty( '--tf-progress', p.toFixed( 4 ) );
				} );
			}

			// Zoom: la escala crece con el avance del elemento por el viewport.
			zoom.forEach( function ( el ) {
				var rect = el.getBoundingClientRect();
				var prog = 1 - ( rect.top + rect.height / 2 ) / ( vh + rect.height );
				if ( prog < 0 ) { prog = 0; } else if ( prog > 1 ) { prog = 1; }
				el.style.setProperty( '--tf-zoom-scale', ( 1 + prog * 0.14 ).toFixed( 4 ) );
			} );

			ticking = false;
		}

		function onScroll() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
		update();
	}

	/* --------------------------------------------------------------------
	 * CINEMATIC ZOOM (firma del theme) — scrub suavizado con GSAP cargado
	 * BAJO DEMANDA. Solo se ejecuta si hay un elemento que lo pida y no hay
	 * reduce-motion. La imagen escala con el scroll dentro de su marco
	 * (recortado por overflow), dando la sensación cinemática tipo Stargaze.
	 * Si GSAP no carga, la imagen queda estática (degradación con gracia).
	 * ------------------------------------------------------------------ */
	function initCinematicZoom() {
		if ( reduceMotion ) {
			return;
		}
		var nodes = document.querySelectorAll( '[data-tf-scroll="cinematic-zoom"]' );
		if ( ! nodes.length ) {
			return;
		}
		window.tunetCore.loadGSAP().then( function ( gsap ) {
			Array.prototype.forEach.call( nodes, function ( el ) {
				var target = 'IMG' === el.tagName ? el : ( el.querySelector( 'img' ) || el );
				var scaleTo = parseFloat( el.getAttribute( 'data-tf-zoom-to' ) ) || 1.26;
				gsap.fromTo(
					target,
					{ scale: 1 },
					{
						scale: scaleTo,
						ease: 'none',
						scrollTrigger: {
							trigger: el,
							start: 'top 85%',
							end: 'bottom top',
							scrub: 1,
						},
					}
				);
			} );
		} ).catch( function () { /* sin GSAP → imagen estática */ } );
	}

	function onReady() {
		init();
		initHover();
		initScroll();
		initCinematicZoom();
	}

	/* --------------------------------------------------------------------
	 * Carga de GSAP BAJO DEMANDA — desde la copia vendorizada local del motor
	 * (runtime/vendor/gsap), nunca CDN (§3). Se inyectan gsap + ScrollTrigger
	 * como scripts clásicos (exponen globales), se registra el plugin y se
	 * resuelve con la instancia. Idempotente: una sola carga por página.
	 * La base la expone PHP en window.tunetCore.gsapBase.
	 *
	 * OJO: se define ANTES del arranque. Con strategy "defer" el script corre
	 * con readyState !== "loading", así que onReady() se ejecuta de inmediato;
	 * si loadGSAP se definiera después, initCinematicZoom no la encontraría.
	 * ------------------------------------------------------------------ */
	window.tunetCore.loadGSAP = function () {
		if ( window.tunetCore._gsapPromise ) {
			return window.tunetCore._gsapPromise;
		}
		var base = ( window.tunetCore && window.tunetCore.gsapBase ) || '';

		window.tunetCore._gsapPromise = new Promise( function ( resolve, reject ) {
			if ( ! base ) {
				reject( new Error( 'Tunet Core: window.tunetCore.gsapBase no definido.' ) );
				return;
			}
			if ( window.gsap && window.ScrollTrigger ) {
				window.gsap.registerPlugin( window.ScrollTrigger );
				resolve( window.gsap );
				return;
			}
			injectScript( base + 'gsap.min.js' )
				.then( function () { return injectScript( base + 'ScrollTrigger.min.js' ); } )
				.then( function () {
					if ( window.gsap && window.ScrollTrigger ) {
						window.gsap.registerPlugin( window.ScrollTrigger );
						resolve( window.gsap );
					} else {
						reject( new Error( 'Tunet Core: GSAP no disponible tras la carga.' ) );
					}
				} )
				.catch( reject );
		} );
		window.tunetCore._gsapPromise.catch( function () {} );
		return window.tunetCore._gsapPromise;
	};

	// Inyecta un <script> clásico y resuelve al cargar (async=false preserva orden).
	function injectScript( src ) {
		return new Promise( function ( res, rej ) {
			var s = document.createElement( 'script' );
			s.src = src;
			s.async = false;
			s.onload = function () { res(); };
			s.onerror = function () { rej( new Error( 'Tunet Core: no se pudo cargar ' + src ) ); };
			document.head.appendChild( s );
		} );
	}

	/* Arranque (al final: todo lo que usa onReady ya está definido). */
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', onReady );
	} else {
		onReady();
	}
} )();
