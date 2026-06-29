/* ==========================================================================
 * Tunet Core · Block tunet/slider — runtime de frontend (Swiper lazy)
 * --------------------------------------------------------------------------
 * Carga Swiper (vendorizado) de forma LAZY: solo cuando un slider se acerca al
 * viewport, inyectando el bundle una sola vez. Luego inicializa cada slider con
 * las opciones de sus data-attributes. Sin red en runtime (copia local).
 *
 * prefers-reduced-motion → desactiva el autoplay (sin movimiento automático).
 * Si Swiper no carga, queda la primera slide visible (fallback en el CSS).
 * ========================================================================== */
( function () {
	'use strict';

	if ( typeof document === 'undefined' ) {
		return;
	}

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var swiperPromise = null;

	function loadSwiper( base ) {
		if ( window.Swiper ) {
			return Promise.resolve();
		}
		if ( swiperPromise ) {
			return swiperPromise;
		}
		swiperPromise = new Promise( function ( resolve, reject ) {
			var link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = base + 'swiper-bundle.min.css';
			document.head.appendChild( link );

			var script = document.createElement( 'script' );
			script.src = base + 'swiper-bundle.min.js';
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild( script );
		} );
		return swiperPromise;
	}

	function num( v, fallback ) {
		var n = parseFloat( v );
		return isNaN( n ) ? fallback : n;
	}

	function buildOptions( el ) {
		var d = el.dataset;
		var effect = d.effect || 'slide';
		var spv = effect === 'fade' || effect === 'cards' ? 1 : num( d.spv, 1 );
		var slideCount = el.querySelectorAll( '.swiper-slide' ).length;

		var opts = {
			effect: effect,
			speed: num( d.speed, 600 ),
			// Loop only when there are genuinely more slides than fit in view.
			// With too few, Swiper warns + disables loop anyway, so we pre-empt it:
			// the base degrades gracefully no matter how a theme configures it.
			loop: d.loop === '1' && slideCount > Math.ceil( spv ),
			spaceBetween: num( d.space, 0 ),
			slidesPerView: spv,
			a11y: { enabled: true }
		};

		if ( d.pagination === '1' ) {
			opts.pagination = { el: el.querySelector( '.swiper-pagination' ), clickable: true };
		}
		if ( d.navigation === '1' ) {
			opts.navigation = {
				nextEl: el.querySelector( '.swiper-button-next' ),
				prevEl: el.querySelector( '.swiper-button-prev' )
			};
		}
		// Autoplay solo si se pidió Y el usuario no prefiere menos movimiento.
		if ( d.autoplay === '1' && ! reduceMotion ) {
			opts.autoplay = {
				delay: num( d.autoplayDelay, 4000 ),
				disableOnInteraction: false,
				pauseOnMouseEnter: true
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

	function initSlider( el ) {
		var base = el.getAttribute( 'data-swiper-base' ) || '';
		loadSwiper( base ).then( function () {
			if ( window.Swiper ) {
				new window.Swiper( el, buildOptions( el ) );
			}
		} ).catch( function () {
			/* Sin Swiper: el CSS deja visible la primera slide. */
		} );
	}

	function init() {
		var sliders = document.querySelectorAll( '.tunet-slider' );
		if ( ! sliders.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			for ( var i = 0; i < sliders.length; i++ ) {
				initSlider( sliders[ i ] );
			}
			return;
		}

		// Carga ANTICIPADA (200px antes) para que esté listo al entrar.
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						initSlider( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '200px 0px' }
		);

		for ( var j = 0; j < sliders.length; j++ ) {
			observer.observe( sliders[ j ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
