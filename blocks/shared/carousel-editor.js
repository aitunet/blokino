/* ==========================================================================
 * Tunet Core · Preview de CARRUSEL en el EDITOR (Swiper real sobre el SSR)
 * --------------------------------------------------------------------------
 * `window.tunet.CarouselPreview` envuelve un ServerSideRender (el markup lo sigue
 * generando render.php — DRY) y corre **Swiper de verdad** dentro del iframe del
 * canvas, para que un slider se vea y se comporte como slider en el editor y no
 * como una pila vertical de slides.
 *
 * Mitigaciones (el preview es SSR → se re-renderiza al editar):
 *  - Re-inicializa cuando el SSR reemplaza el subárbol (aparece un `.tunet-carousel`
 *    nuevo), detectado por un MutationObserver de `childList`.
 *  - **Preserva la slide activa** entre re-inicializaciones (no salta al slide 1).
 *  - **Autoplay SIEMPRE off** en el editor (no distrae mientras se edita).
 *  - Grid (testimonials sin `.tunet-carousel`) → no-op: queda estático.
 *
 * Contexto: este script corre en la ventana EXTERNA del editor, pero el markup del
 * SSR vive en el IFRAME del canvas. Por eso se opera sobre `ref.ownerDocument`
 * (el iframe): Swiper (bundle vendorizado) y el CSS del carrusel se inyectan en
 * ESE documento, y se instancia con el constructor `Swiper` de ese iframe. El
 * front NO se toca (usa runtime/carousel.js).
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element ) {
		return;
	}

	window.tunet = window.tunet || {};

	var el = wp.element.createElement;
	var useRef = wp.element.useRef;
	var useEffect = wp.element.useEffect;
	var ServerSideRender = wp.serverSideRender;

	function num( v, fallback ) {
		var n = parseFloat( v );
		return isNaN( n ) ? fallback : n;
	}

	/**
	 * Carga Swiper (JS + CSS del bundle vendorizado) y el CSS del carrusel DENTRO
	 * del documento dado (el iframe del canvas). Cachea la promesa por-documento.
	 * Sin red externa: todo sale de la copia local del plugin.
	 */
	function loadAssets( doc, base ) {
		var win = doc.defaultView || window;

		// CSS del carrusel (flechas / paginación / layout / fallback) en el iframe.
		if ( window.tunet.carouselCssUrl && ! doc.getElementById( 'tnt-carousel-css' ) ) {
			var chrome = doc.createElement( 'link' );
			chrome.id = 'tnt-carousel-css';
			chrome.rel = 'stylesheet';
			chrome.href = window.tunet.carouselCssUrl;
			doc.head.appendChild( chrome );
		}

		if ( doc.__tntSwiperPromise ) {
			return doc.__tntSwiperPromise;
		}

		doc.__tntSwiperPromise = new Promise( function ( resolve, reject ) {
			if ( win.Swiper ) {
				resolve();
				return;
			}
			var link = doc.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = base + 'swiper-bundle.min.css';
			doc.head.appendChild( link );

			var script = doc.createElement( 'script' );
			script.src = base + 'swiper-bundle.min.js';
			script.onload = resolve;
			script.onerror = reject;
			doc.head.appendChild( script );
		} );
		return doc.__tntSwiperPromise;
	}

	/**
	 * Opciones de Swiper para el editor (espejo de runtime/carousel.js, pero con
	 * autoplay SIEMPRE off y con `initialSlide` para preservar la posición).
	 */
	function buildOptions( elc, initialSlide ) {
		var d = elc.dataset;
		var effect = d.effect || 'slide';
		var spv = effect === 'fade' || effect === 'cards' ? 1 : num( d.spv, 1 );
		var slideCount = elc.querySelectorAll( '.swiper-slide' ).length;

		var opts = {
			effect: effect,
			speed: num( d.speed, 600 ),
			loop: d.loop === '1' && slideCount > Math.ceil( spv ),
			spaceBetween: num( d.space, 0 ),
			slidesPerView: spv,
			initialSlide: initialSlide || 0,
			autoplay: false,
			a11y: { enabled: false }
		};

		if ( d.pagination === '1' ) {
			opts.pagination = { el: elc.querySelector( '.swiper-pagination' ), clickable: true };
		}
		if ( d.navigation === '1' ) {
			opts.navigation = {
				nextEl: elc.querySelector( '.swiper-button-next' ),
				prevEl: elc.querySelector( '.swiper-button-prev' )
			};
		}
		if ( effect === 'fade' ) {
			opts.fadeEffect = { crossFade: true };
		}
		if ( effect === 'coverflow' ) {
			opts.centeredSlides = true;
			opts.slidesPerView = num( d.spv, 1 ) > 1 ? num( d.spv, 1 ) : 'auto';
		}
		return opts;
	}

	/**
	 * Componente: renderiza el SSR y gestiona el ciclo de vida de Swiper sobre él.
	 * Props: { block: 'tunet/…', attributes: {…} }.
	 */
	function CarouselPreview( props ) {
		var ref = useRef( null );
		var indexRef = useRef( 0 );

		useEffect( function () {
			var container = ref.current;
			if ( ! container ) {
				return undefined;
			}
			var doc = container.ownerDocument;
			var win = doc.defaultView || window;
			var swiper = null;
			var initializedEl = null;
			var timer = 0;

			function destroy() {
				if ( swiper ) {
					try {
						indexRef.current = ( typeof swiper.realIndex === 'number' ? swiper.realIndex : swiper.activeIndex ) || 0;
						swiper.destroy( true, true );
					} catch ( e ) { /* noop */ }
					swiper = null;
				}
				initializedEl = null;
			}

			function build() {
				var elc = container.querySelector( '.tunet-carousel' );
				if ( ! elc ) {
					// SSR sin contenido todavía, o layout grid (sin carrusel): no-op.
					return;
				}
				if ( elc === initializedEl && swiper ) {
					// Ese mismo nodo ya está inicializado (mutación propia de Swiper).
					return;
				}
				destroy();
				initializedEl = elc;
				var base = elc.getAttribute( 'data-swiper-base' ) || '';
				loadAssets( doc, base ).then( function () {
					// ¿sigue montado exactamente el nodo que empezamos a inicializar?
					if ( ! win.Swiper || initializedEl !== elc || ! doc.contains( elc ) ) {
						return;
					}
					var count = elc.querySelectorAll( '.swiper-slide' ).length;
					var start = Math.max( 0, Math.min( indexRef.current, count - 1 ) );
					swiper = new win.Swiper( elc, buildOptions( elc, start ) );
				} ).catch( function () {
					/* Sin Swiper: carousel.css deja visible la primera slide. */
				} );
			}

			function schedule() {
				// Coalesce la ráfaga de mutaciones del SSR (a veces limpia y luego
				// inserta) en una sola llamada. setTimeout (no rAF): rAF se PAUSA
				// cuando el iframe/tab no está visible y dejaría el preview sin init.
				if ( timer ) {
					win.clearTimeout( timer );
				}
				timer = win.setTimeout( function () {
					timer = 0;
					build();
				}, 30 );
			}

			// Solo childList: el SSR reemplaza nodos (re-init); los cambios de estilo/
			// clase de Swiper no disparan nada. Coalescido por timer; build() es idempotente.
			var mo = new win.MutationObserver( schedule );
			mo.observe( container, { childList: true, subtree: true } );
			build();

			return function () {
				mo.disconnect();
				if ( timer ) {
					win.clearTimeout( timer );
				}
				destroy();
			};
		}, [] );

		return el(
			'div',
			{ ref: ref, className: 'tunet-carousel-editor' },
			el( ServerSideRender, { block: props.block, attributes: props.attributes } )
		);
	}

	window.tunet.CarouselPreview = CarouselPreview;
} )( window.wp );
