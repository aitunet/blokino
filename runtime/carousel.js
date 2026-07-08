/* ==========================================================================
 * Tunet Core · Runtime de carrusel COMPARTIDO (Swiper lazy)
 * --------------------------------------------------------------------------
 * Escanea cualquier .tunet-carousel (tunet/slider, tunet/testimonials…),
 * carga Swiper (vendorizado en runtime/vendor/swiper) de forma LAZY (solo cerca
 * del viewport) e inicializa con las opciones de sus data-attributes. Sin red
 * en runtime (copia local). prefers-reduced-motion → autoplay off. Si Swiper no
 * carga, queda la 1ª slide visible (fallback en runtime/carousel.css).
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

	function initCarousel( el ) {
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
		var carousels = document.querySelectorAll( '.tunet-carousel' );
		if ( ! carousels.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			for ( var i = 0; i < carousels.length; i++ ) {
				initCarousel( carousels[ i ] );
			}
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						initCarousel( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '200px 0px' }
		);

		for ( var j = 0; j < carousels.length; j++ ) {
			observer.observe( carousels[ j ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
