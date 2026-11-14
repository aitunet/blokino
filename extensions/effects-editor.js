/* ==========================================================================
 * Blokino · Extensiones tf* sobre blocks nativos (editor)
 * --------------------------------------------------------------------------
 * Pipeline del editor:
 *   1. blocks.registerBlockType → declara los atributos tf* (viven en el
 *      comentario del block; NO se tocan el markup guardado).
 *   2. editor.BlockEdit (HOC)   → panel "Blokino Effects".
 *   3. editor.BlockListBlock    → vista previa en el lienzo (wrapperProps) y
 *      botón "Preview animation" (Web Animations), sin tocar el markup.
 *   La inyección de data-tf-* ocurre SOLO en render (PHP render_block), nunca
 *   en save(), para no romper la validación de bloques del editor.
 *
 * Catálogo (vanilla, sin GSAP): entradas (tfAnimation + tfStagger), hover,
 *   scroll, blend, borde animado y outline. Los valores válidos son las
 *   constantes de Blokino_Runtime; el CSS y el JS del front viven en runtime/.
 *
 * Sin JSX a propósito: usa los globales wp.* para no exigir paso de build en
 * esta fase. El bundle con @wordpress/scripts llega con los blocks propios.
 *
 * Regla: cada atributo por defecto vacío/none → el block sale limpio.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var addFilter = wp.hooks.addFilter;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var ColorPalette = wp.blockEditor.ColorPalette;
	// WP 6.5+ replaced useSetting with the variadic useSettings (returns an array); keep the old ones as fallbacks.
	var useSettings = wp.blockEditor.useSettings;
	var useSetting = wp.blockEditor.useSetting || wp.blockEditor.__experimentalUseSetting;
	var components = wp.components;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var BaseControl = components.BaseControl;
	var TextControl = components.TextControl;

	// Bloques con panel de efectos (U-09): la lista la manda PHP
	// (Blokino_Extensions::effect_blocks, filtrable con blokino_effect_blocks).
	// Esta copia es solo la red si el inline script no llegara.
	var ALLOWED = Array.isArray( window.blokinoEffectBlocks ) ? window.blokinoEffectBlocks : [
		'core/group', 'core/columns', 'core/cover', 'core/image', 'core/heading',
		'core/paragraph', 'core/buttons', 'core/button',
		'blokino/section', 'blokino/marquee', 'blokino/counter', 'blokino/before-after'
	];

	// U-10: opciones que solo se notan en ciertos bloques.
	var TEXT_BLOCKS = [ 'core/heading', 'core/paragraph', 'core/site-title', 'core/post-title' ];
	var BLEND_BLOCKS = [ 'core/image', 'core/cover', 'core/heading', 'core/post-featured-image', 'core/video', 'core/media-text' ];

	function isAllowed( name ) {
		return ALLOWED.indexOf( name ) !== -1;
	}

	/* ----------------------------------------------------------------------
	 * 1) Declaración de atributos tf* (catálogo completo del §4.1).
	 * -------------------------------------------------------------------- */
	function addTfAttributes( settings, name ) {
		if ( ! isAllowed( name ) ) {
			return settings;
		}

		settings.attributes = Object.assign( {}, settings.attributes, {
			tfAnimation:    { type: 'string', default: '' },
			tfAnimDelay:    { type: 'number', default: 0 },
			tfAnimDuration: { type: 'number', default: 0 },
			tfAnimEasing:   { type: 'string', default: '' },
			tfStagger:       { type: 'number', default: 0 },
			tfHover:         { type: 'string', default: '' },
			tfScroll:        { type: 'string', default: '' },
			tfParallaxSpeed: { type: 'number', default: 0 },
			tfBlend:         { type: 'string', default: '' },
			tfBorderFx:      { type: 'string', default: '' },
			tfBorderWidth:   { type: 'number', default: 0 },
			tfBorderSpeed:   { type: 'number', default: 0 },
			tfBorderColor1:  { type: 'string', default: '' },
			tfBorderColor2:  { type: 'string', default: '' },
			// Text-fill (firma editorial): índices (1-based) de palabras a cobalto.
			tfFillAccent:    { type: 'string', default: '' },
			tfDisplay:       { type: 'string', default: '' }
		} );

		return settings;
	}

	addFilter( 'blocks.registerBlockType', 'blokino/tf-attributes', addTfAttributes );

	/* ----------------------------------------------------------------------
	 * 1b) Vista previa en el lienzo (U-02).
	 *    Los mismos data-tf-* y variables --tf-* que el render PHP pone en el
	 *    front, pero como wrapperProps del editor: viven solo en el lienzo, nunca
	 *    en el markup guardado (cero errores de validación). effects.css entra al
	 *    iframe (Blokino_Runtime::enqueue_editor_preview), así que hover, borde,
	 *    blend, outline, parallax y zoom se ven en vivo. La ENTRADA no se marca
	 *    aquí: sus estados ocultos dependen de .blokino-tf-ready (solo front); se
	 *    reproduce a demanda con el botón "Preview animation".
	 * -------------------------------------------------------------------- */
	var HOVERS = [ 'lift', 'glow', 'tilt', 'magnetic', 'underline-grow', 'image-zoom' ];
	// sticky-pin y progress no tienen sentido dentro del lienzo (fijarían o
	// pintarían una barra sobre el editor): se ven en la página publicada.
	var CANVAS_SCROLLS = [ 'parallax', 'reveal-on-scroll', 'zoom' ];
	var BLENDS = [ 'multiply', 'screen', 'overlay', 'difference', 'exclusion', 'luminosity' ];
	var BORDER_FX = [ 'gradient', 'conic-rotate' ];
	var EASINGS = [ 'expo', 'power3', 'spring', 'circ' ];
	var COLOR_RE = /^(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\))$/i;

	function has( list, v ) {
		return !! v && list.indexOf( v ) !== -1;
	}

	/** data-* + style del lienzo, o null si el bloque no tiene nada que mostrar. */
	function canvasProps( a ) {
		var data = {};
		if ( has( HOVERS, a.tfHover ) ) { data[ 'data-tf-hover' ] = a.tfHover; }
		// reveal-on-scroll se ve en el lienzo solo por su transform compuesto
		// (sin gate no se oculta): inofensivo y coherente con el front.
		if ( has( CANVAS_SCROLLS, a.tfScroll ) ) { data[ 'data-tf-scroll' ] = a.tfScroll; }
		if ( has( BLENDS, a.tfBlend ) ) { data[ 'data-tf-blend' ] = a.tfBlend; }
		if ( has( BORDER_FX, a.tfBorderFx ) ) { data[ 'data-tf-border-fx' ] = a.tfBorderFx; }
		if ( a.tfDisplay === 'outline' ) { data[ 'data-tf-display' ] = 'outline'; }
		if ( ! Object.keys( data ).length ) {
			return null;
		}
		var style = {};
		if ( a.tfScroll === 'parallax' && a.tfParallaxSpeed ) { style[ '--tf-parallax-speed' ] = String( parseInt( a.tfParallaxSpeed, 10 ) || 0 ); }
		if ( has( BORDER_FX, a.tfBorderFx ) ) {
			if ( a.tfBorderWidth ) { style[ '--tf-bw' ] = ( parseInt( a.tfBorderWidth, 10 ) || 0 ) + 'px'; }
			if ( a.tfBorderSpeed ) { style[ '--tf-bdur' ] = ( parseFloat( a.tfBorderSpeed ) || 0 ) + 's'; }
			if ( COLOR_RE.test( a.tfBorderColor1 || '' ) ) { style[ '--tf-bc1' ] = a.tfBorderColor1; }
			if ( COLOR_RE.test( a.tfBorderColor2 || '' ) ) { style[ '--tf-bc2' ] = a.tfBorderColor2; }
		}
		return { data: data, style: style };
	}

	var withCanvasPreview = createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			if ( ! isAllowed( props.name ) ) {
				return el( BlockListBlock, props );
			}
			var extra = canvasProps( props.attributes || {} );
			if ( ! extra ) {
				return el( BlockListBlock, props );
			}
			var wp0 = props.wrapperProps || {};
			var wrapperProps = Object.assign( {}, wp0, extra.data, {
				style: Object.assign( {}, wp0.style || {}, extra.style )
			} );
			return el( BlockListBlock, Object.assign( {}, props, { wrapperProps: wrapperProps } ) );
		};
	}, 'withBlokinoCanvasPreview' );

	addFilter( 'editor.BlockListBlock', 'blokino/tf-canvas-preview', withCanvasPreview );

	/* ----------------------------------------------------------------------
	 * Reproducción de la entrada a demanda (botón "Preview animation").
	 * Web Animations API sobre el nodo del lienzo: no toca el DOM editable ni
	 * los atributos, y al terminar no deja rastro. Valores equivalentes a los
	 * de effects.css, leídos de los tokens --tnt-* del theme en el iframe.
	 * -------------------------------------------------------------------- */
	var PREVIEWABLE = [ 'fade-up', 'clip-reveal', 'mask-up', 'blur-in', 'scale-in', 'slide-left', 'slide-right' ];

	function cssVar( node, name, fallback ) {
		var v = node.ownerDocument.defaultView.getComputedStyle( node ).getPropertyValue( name ).trim();
		return v || fallback;
	}
	function toMs( v, fallback ) {
		var n = parseFloat( v );
		if ( isNaN( n ) ) { return fallback; }
		return /ms\s*$/.test( v ) ? n : ( /s\s*$/.test( v ) ? n * 1000 : n );
	}
	function enterFrames( type, node ) {
		var s4 = cssVar( node, '--tnt-space-4', '1.5rem' );
		var s5 = cssVar( node, '--tnt-space-5', '2rem' );
		var s6 = cssVar( node, '--tnt-space-6', '3rem' );
		switch ( type ) {
			case 'fade-up': return [ { opacity: 0, translate: '0 ' + s5 }, { opacity: 1, translate: '0 0' } ];
			case 'slide-left': return [ { opacity: 0, translate: 'calc(-1 * ' + s6 + ') 0' }, { opacity: 1, translate: '0 0' } ];
			case 'slide-right': return [ { opacity: 0, translate: s6 + ' 0' }, { opacity: 1, translate: '0 0' } ];
			case 'scale-in': return [ { opacity: 0, scale: '0.92' }, { opacity: 1, scale: '1' } ];
			case 'blur-in': return [ { opacity: 0, filter: 'blur(12px)' }, { opacity: 1, filter: 'blur(0)' } ];
			case 'mask-up': return [ { clipPath: 'inset(0 0 100% 0)', translate: '0 ' + s4 }, { clipPath: 'inset(0)', translate: '0 0' } ];
			case 'clip-reveal': return [ { clipPath: 'inset(100% 0 0 0)' }, { clipPath: 'inset(0)' } ];
		}
		return null;
	}
	function playEntrance( clientId, a ) {
		var node = canvasDoc().querySelector( '[data-block="' + clientId + '"]' );
		var type = a.tfAnimation || ( a.tfScroll === 'reveal-on-scroll' ? 'fade-up' : '' );
		if ( ! node || ! node.animate || PREVIEWABLE.indexOf( type ) === -1 ) {
			return;
		}
		var frames = enterFrames( type, node );
		var ease = cssVar( node, '--tnt-ease-' + ( has( EASINGS, a.tfAnimEasing ) ? a.tfAnimEasing : 'expo' ), 'cubic-bezier(0.16, 1, 0.3, 1)' );
		var opts = {
			duration: a.tfAnimDuration || toMs( cssVar( node, '--tnt-dur-base', '600ms' ), 600 ),
			delay: a.tfAnimDelay || 0,
			easing: ease,
			fill: 'backwards'
		};
		// Escalonado: como en el front, anima los hijos directos (no con clip/mask).
		var kids = [];
		if ( a.tfStagger && type !== 'clip-reveal' && type !== 'mask-up' ) {
			kids = Array.prototype.filter.call( node.children, function ( c ) {
				return ! c.classList.contains( 'block-list-appender' );
			} );
		}
		if ( kids.length ) {
			kids.forEach( function ( c, i ) {
				c.animate( frames, Object.assign( {}, opts, { delay: opts.delay + i * a.tfStagger } ) );
			} );
		} else {
			node.animate( frames, opts );
		}
	}

	function reducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function anyEffect( a ) {
		return !! ( a.tfAnimation || a.tfHover || a.tfScroll || a.tfBlend || a.tfBorderFx || a.tfDisplay );
	}

	function permalink() {
		try {
			var ed = wp.data && wp.data.select( 'core/editor' );
			var p = ed && ed.getPermalink && ed.getPermalink();
			var status = ed && ed.getEditedPostAttribute && ed.getEditedPostAttribute( 'status' );
			return p && status === 'publish' ? p : '';
		} catch ( e ) {
			return '';
		}
	}

	/* ----------------------------------------------------------------------
	 * 2) Panel "Blokino Effects" en InspectorControls (HOC sobre BlockEdit).
	 *    Las animaciones corren en el FRONTEND; aquí solo exponemos controles.
	 * -------------------------------------------------------------------- */
	/* --------------------------------------------------------------------
	 * Vista previa de text-fill en el editor: las palabras acento (tfFillAccent)
	 * se pintan con el primary del theme SIN tocar el texto editable — CSS Custom
	 * Highlight API (rangos, no spans). Mismo conteo de palabras que runtime/
	 * effects.js (split por espacios, 1-based). Sin soporte (navegador viejo) no
	 * pasa nada: el texto queda como estaba.
	 * ------------------------------------------------------------------ */
	var HL = 'blokino-tf-accent';
	var fillRanges = {};
	function canvasDoc() {
		var f = document.querySelector( 'iframe[name="editor-canvas"]' );
		return ( f && f.contentDocument ) || document;
	}
	function paintFill( doc ) {
		var win = doc.defaultView;
		if ( ! win || ! win.CSS || ! win.CSS.highlights || typeof win.Highlight !== 'function' ) {
			return;
		}
		var all = [];
		Object.keys( fillRanges ).forEach( function ( k ) { all = all.concat( fillRanges[ k ] ); } );
		if ( ! all.length ) {
			win.CSS.highlights.delete( HL );
			return;
		}
		var h = new win.Highlight();
		all.forEach( function ( r ) { h.add( r ); } );
		win.CSS.highlights.set( HL, h );
		if ( ! doc.getElementById( HL + '-css' ) ) {
			var st = doc.createElement( 'style' );
			st.id = HL + '-css';
			doc.head.appendChild( st );
		}
		var c = win.getComputedStyle( doc.body ).getPropertyValue( '--tnt-color-primary' ).trim() || 'currentColor';
		doc.getElementById( HL + '-css' ).textContent = '::highlight(' + HL + '){color:' + c + ';}';
	}
	function useFillPreview( clientId, a ) {
		var useEffect = wp.element.useEffect;
		var on = a.tfAnimation === 'text-fill' && !! a.tfFillAccent;
		var key = on ? String( a.tfFillAccent ) + '|' + String( a.content || '' ) : '';
		useEffect( function () {
			var doc = canvasDoc();
			var t = setTimeout( function () {
				delete fillRanges[ clientId ];
				var host = on && doc.querySelector( '[data-block="' + clientId + '"]' );
				if ( host ) {
					var want = {};
					String( a.tfFillAccent ).split( /[\s,]+/ ).forEach( function ( n ) { var i = parseInt( n, 10 ); if ( i > 0 ) { want[ i - 1 ] = true; } } );
					var walker = doc.createTreeWalker( host, 4 );
					var n = 0, node, m, re, list = [];
					while ( ( node = walker.nextNode() ) ) {
						re = /\S+/g;
						while ( ( m = re.exec( node.nodeValue ) ) ) {
							if ( want[ n ] ) {
								var r = doc.createRange();
								r.setStart( node, m.index );
								r.setEnd( node, m.index + m[ 0 ].length );
								list.push( r );
							}
							n++;
						}
					}
					fillRanges[ clientId ] = list;
				}
				paintFill( doc );
			}, 60 );
			return function () {
				clearTimeout( t );
				delete fillRanges[ clientId ];
				paintFill( doc );
			};
		}, [ clientId, key ] );
	}

	var withBlokinoEffects = createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( ! isAllowed( props.name ) ) {
				return el( BlockEdit, props );
			}

			var a = props.attributes;
			var set = props.setAttributes;
			useFillPreview( props.clientId, a );
			var themePalette = useSettings
				? ( useSettings( 'color.palette' )[0] || [] )
				: ( useSetting ? ( useSetting( 'color.palette' ) || [] ) : [] );

			var animationControl = el( SelectControl, {
				label: __( 'Entrance animation', 'blokino' ),
				help: __( 'How the block appears when it scrolls into view.', 'blokino' ),
				value: a.tfAnimation || '',
				options: [
					{ label: __( 'None (clean block)', 'blokino' ), value: '' },
					{ label: __( 'Fade up', 'blokino' ), value: 'fade-up' },
					{ label: __( 'Clip reveal', 'blokino' ), value: 'clip-reveal' },
					{ label: __( 'Mask up', 'blokino' ), value: 'mask-up' },
					{ label: __( 'Blur in', 'blokino' ), value: 'blur-in' },
					{ label: __( 'Scale in', 'blokino' ), value: 'scale-in' },
					{ label: __( 'Slide left', 'blokino' ), value: 'slide-left' },
					{ label: __( 'Slide right', 'blokino' ), value: 'slide-right' },
					{ label: __( 'Text stagger', 'blokino' ), value: 'text-stagger' },
					{ label: __( 'Text fill', 'blokino' ), value: 'text-fill' }
				],
				onChange: function ( value ) {
					set( { tfAnimation: value } );
					// Elegir una entrada la enseña al momento en el lienzo (U-02).
					if ( PREVIEWABLE.indexOf( value ) !== -1 && ! reducedMotion() ) {
						var next = Object.assign( {}, a, { tfAnimation: value } );
						setTimeout( function () { playEntrance( props.clientId, next ); }, 60 );
					}
				},
				__nextHasNoMarginBottom: true
			} );

			var displayControl = el( SelectControl, {
				label: __( 'Display style', 'blokino' ),
				help: __( 'Outline draws the letters as a line. Best on large headings.', 'blokino' ),
				value: a.tfDisplay || '',
				options: [
					{ label: __( 'Solid (default)', 'blokino' ), value: '' },
					{ label: __( 'Outline', 'blokino' ), value: 'outline' }
				],
				onChange: function ( value ) {
					set( { tfDisplay: value } );
				},
				__nextHasNoMarginBottom: true
			} );

			var detailControls = null;
			if ( a.tfAnimation ) {
				var isTextStagger = a.tfAnimation === 'text-stagger';

				detailControls = el(
					Fragment,
					{},
					el( RangeControl, {
						label: __( 'Delay (ms)', 'blokino' ),
						value: a.tfAnimDelay || 0,
						min: 0,
						max: 2000,
						step: 50,
						onChange: function ( value ) {
							set( { tfAnimDelay: value || 0 } );
						},
						__nextHasNoMarginBottom: true
					} ),
					el( RangeControl, {
						label: __( 'Duration (ms)', 'blokino' ),
						value: a.tfAnimDuration || 0,
						min: 0,
						max: 3000,
						step: 50,
						help: __( '0 = use the theme duration (--tnt-dur-base).', 'blokino' ),
						onChange: function ( value ) {
							set( { tfAnimDuration: value || 0 } );
						},
						__nextHasNoMarginBottom: true
					} ),
					el( SelectControl, {
						label: __( 'Easing curve', 'blokino' ),
						value: a.tfAnimEasing || '',
						options: [
							{ label: __( 'Theme default', 'blokino' ), value: '' },
							{ label: __( 'Smooth (expo)', 'blokino' ), value: 'expo' },
							{ label: __( 'Soft (power3)', 'blokino' ), value: 'power3' },
							{ label: __( 'Bouncy (spring)', 'blokino' ), value: 'spring' },
							{ label: __( 'Sharp (circ)', 'blokino' ), value: 'circ' }
						],
						onChange: function ( value ) {
							set( { tfAnimEasing: value } );
						},
						__nextHasNoMarginBottom: true
					} ),
					el( RangeControl, {
						label: __( 'Stagger (ms)', 'blokino' ),
						value: a.tfStagger || 0,
						min: 0,
						max: 300,
						step: 10,
						help: isTextStagger
							? __( 'Cadence between words. 0 = default cadence.', 'blokino' )
							: __( 'Staggers the entrance of direct children. 0 = no stagger.', 'blokino' ),
						onChange: function ( value ) {
							set( { tfStagger: value || 0 } );
						},
						__nextHasNoMarginBottom: true
					} ),
					( a.tfAnimation === 'text-fill' )
						? el( TextControl, {
							label: __( 'Accent words (indices, e.g. "2,5")', 'blokino' ),
							value: a.tfFillAccent || '',
							help: __( 'These words fill to the brand accent instead of ink. 1-based.', 'blokino' ),
							onChange: function ( value ) {
								set( { tfFillAccent: value.replace( /[^0-9,\s]/g, '' ) } );
							},
							__nextHasNoMarginBottom: true
						} )
						: null
				);
			}

			// Hover is independent of the entrance animation.
			var hoverControl = el( SelectControl, {
				label: __( 'Hover effect', 'blokino' ),
				help: __( 'Reacts when the pointer is over the block, or when it has keyboard focus.', 'blokino' ),
				value: a.tfHover || '',
				options: [
					{ label: __( 'None', 'blokino' ), value: '' },
					{ label: __( 'Lift', 'blokino' ), value: 'lift' },
					{ label: __( 'Glow', 'blokino' ), value: 'glow' },
					{ label: __( 'Tilt 3D', 'blokino' ), value: 'tilt' },
					{ label: __( 'Magnetic', 'blokino' ), value: 'magnetic' },
					{ label: __( 'Underline grow', 'blokino' ), value: 'underline-grow' },
					{ label: __( 'Image zoom', 'blokino' ), value: 'image-zoom' }
				],
				onChange: function ( value ) {
					set( { tfHover: value } );
				},
				__nextHasNoMarginBottom: true
			} );

			// Scroll.
			var scrollControl = el( SelectControl, {
				label: __( 'Scroll effect', 'blokino' ),
				help: __( 'Parallax and Zoom move with the scroll; Sticky pin keeps the block in place while its section scrolls; Progress bar draws a reading bar.', 'blokino' ),
				value: a.tfScroll || '',
				options: [
					{ label: __( 'None', 'blokino' ), value: '' },
					{ label: __( 'Parallax', 'blokino' ), value: 'parallax' },
					{ label: __( 'Sticky pin', 'blokino' ), value: 'sticky-pin' },
					{ label: __( 'Reveal on scroll', 'blokino' ), value: 'reveal-on-scroll' },
					{ label: __( 'Progress bar', 'blokino' ), value: 'progress' },
					{ label: __( 'Zoom on scroll', 'blokino' ), value: 'zoom' }
				],
				onChange: function ( value ) {
					set( { tfScroll: value } );
				},
				__nextHasNoMarginBottom: true
			} );

			var parallaxControl = a.tfScroll === 'parallax'
				? el( RangeControl, {
					label: __( 'Parallax intensity', 'blokino' ),
					value: a.tfParallaxSpeed || 0,
					min: 0,
					max: 100,
					step: 5,
					help: __( '0 = no movement. Default ~20.', 'blokino' ),
					onChange: function ( value ) {
						set( { tfParallaxSpeed: value || 0 } );
					},
					__nextHasNoMarginBottom: true
				} )
				: null;

			// Blend + border.
			var blendControl = el( SelectControl, {
				label: __( 'Blend mode', 'blokino' ),
				help: __( 'Mixes the block with what is behind it. Most visible on images over a color or a photo.', 'blokino' ),
				value: a.tfBlend || '',
				options: [
					{ label: __( 'None', 'blokino' ), value: '' },
					{ label: 'Multiply', value: 'multiply' }, // mix-blend-mode CSS keywords are technical proper-nouns, left raw on purpose.
					{ label: 'Screen', value: 'screen' },
					{ label: 'Overlay', value: 'overlay' },
					{ label: 'Difference', value: 'difference' },
					{ label: 'Exclusion', value: 'exclusion' },
					{ label: 'Luminosity', value: 'luminosity' }
				],
				onChange: function ( value ) {
					set( { tfBlend: value } );
				},
				__nextHasNoMarginBottom: true
			} );

			var borderControl = el( SelectControl, {
				label: __( 'Animated border', 'blokino' ),
				help: __( 'A moving gradient around the edge of the block.', 'blokino' ),
				value: a.tfBorderFx || '',
				options: [
					{ label: __( 'None', 'blokino' ), value: '' },
					{ label: __( 'Gradient', 'blokino' ), value: 'gradient' },
					{ label: __( 'Rotating conic', 'blokino' ), value: 'conic-rotate' }
				],
				onChange: function ( value ) {
					set( { tfBorderFx: value } );
				},
				__nextHasNoMarginBottom: true
			} );

			// Border parameters (only when a border-fx is active).
			var borderDetail = a.tfBorderFx
				? el(
					Fragment,
					{},
					el( RangeControl, {
						label: __( 'Border width (px)', 'blokino' ),
						value: a.tfBorderWidth || 0,
						min: 0,
						max: 12,
						step: 1,
						help: __( '0 = default width (2px).', 'blokino' ),
						onChange: function ( value ) {
							set( { tfBorderWidth: value || 0 } );
						},
						__nextHasNoMarginBottom: true
					} ),
					el( RangeControl, {
						label: __( 'Speed (s per loop)', 'blokino' ),
						value: a.tfBorderSpeed || 0,
						min: 0,
						max: 20,
						step: 0.5,
						help: __( '0 = theme speed (--tnt-dur-slow).', 'blokino' ),
						onChange: function ( value ) {
							set( { tfBorderSpeed: value || 0 } );
						},
						__nextHasNoMarginBottom: true
					} ),
					el(
						BaseControl,
						{ label: __( 'Start color (empty = theme accent)', 'blokino' ), __nextHasNoMarginBottom: true },
						el( ColorPalette, {
							value: a.tfBorderColor1 || undefined,
							colors: themePalette,
							onChange: function ( value ) {
								set( { tfBorderColor1: value || '' } );
							},
							enableAlpha: true,
							__experimentalIsRenderedInSidebar: true
						} )
					),
					el(
						BaseControl,
						{ label: __( 'End color (empty = theme accent 2)', 'blokino' ), __nextHasNoMarginBottom: true },
						el( ColorPalette, {
							value: a.tfBorderColor2 || undefined,
							colors: themePalette,
							onChange: function ( value ) {
								set( { tfBorderColor2: value || '' } );
							},
							enableAlpha: true,
							__experimentalIsRenderedInSidebar: true
						} )
					)
				)
				: null;

			var hr = function () {
				return el( 'hr', { style: { margin: '16px 0', opacity: 0.4 } } );
			};

			// U-02: la entrada se reproduce aquí mismo; el resto, lo que de verdad
			// solo corre al publicar, se dice siempre (no escondido en los detalles).
			var canPreview = PREVIEWABLE.indexOf( a.tfAnimation ) !== -1 || ( ! a.tfAnimation && a.tfScroll === 'reveal-on-scroll' );
			var previewButton = canPreview
				? el(
					'div',
					{ style: { margin: '12px 0 20px' } },
					el( components.Button, {
						variant: 'secondary',
						icon: 'controls-play',
						disabled: reducedMotion(),
						onClick: function () { playEntrance( props.clientId, a ); }
					}, __( 'Preview animation', 'blokino' ) ),
					reducedMotion()
						? el( 'p', { className: 'components-base-control__help', style: { marginTop: '8px' } },
							__( 'Your device asks for reduced motion, so the preview is off. Visitors with that setting see the block without movement.', 'blokino' ) )
						: null
				)
				: null;

			var frontOnly = [];
			if ( a.tfAnimation === 'text-stagger' || a.tfAnimation === 'text-fill' ) {
				frontOnly.push( __( 'Text animations split the words on the published page, so they play there.', 'blokino' ) );
			}
			if ( a.tfScroll === 'sticky-pin' || a.tfScroll === 'progress' ) {
				frontOnly.push( __( 'Sticky pin and Progress bar work on the published page, not inside the editor.', 'blokino' ) );
			}
			if ( a.tfHover === 'tilt' || a.tfHover === 'magnetic' ) {
				frontOnly.push( __( 'Tilt and Magnetic follow the pointer on the published page.', 'blokino' ) );
			}
			if ( a.tfAnimation || a.tfScroll ) {
				frontOnly.push( __( 'On the page, entrance and scroll effects play as the block scrolls into view.', 'blokino' ) );
			}
			var link = frontOnly.length ? permalink() : '';
			var frontNotice = frontOnly.length
				? el(
					'p',
					{ className: 'components-base-control__help', style: { marginTop: '12px' } },
					frontOnly.join( ' ' ),
					link
						? el( Fragment, {}, ' ', el( 'a', { href: link, target: '_blank', rel: 'noopener noreferrer' }, __( 'View page', 'blokino' ) ) )
						: null
				)
				: null;

			return el(
				Fragment,
				{},
				el( BlockEdit, props ),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Blokino Effects', 'blokino' ), initialOpen: anyEffect( a ) },
						animationControl,
						previewButton,
						detailControls,
						hr(),
						hoverControl,
						hr(),
						scrollControl,
						parallaxControl,
						frontNotice,
						hr(),
						( BLEND_BLOCKS.indexOf( props.name ) !== -1 || a.tfBlend ) ? blendControl : null,
						borderControl,
						borderDetail,
						( TEXT_BLOCKS.indexOf( props.name ) !== -1 || a.tfDisplay ) ? el( Fragment, {}, hr(), displayControl ) : null
					)
				)
			);
		};
	}, 'withBlokinoEffects' );

	addFilter( 'editor.BlockEdit', 'blokino/tf-controls', withBlokinoEffects );

	/* ----------------------------------------------------------------------
	 * 3) Inyección en el markup: NO se toca el markup guardado.
	 *
	 *    Los atributos tf* viven en el comentario del block (registrados en el
	 *    paso 1) y se inyectan como data-tf-* y CSS vars ÚNICAMENTE en RENDER
	 *    (filtro PHP render_block, en runtime/class-blokino-runtime.php), tanto
	 *    para blocks estáticos como dinámicos.
	 *
	 *    Por qué NO usamos blocks.getSaveContent.extraProps: inyectar en el
	 *    save haría que el HTML guardado dependa de los atributos, y cualquier
	 *    divergencia entre el HTML almacenado y lo que save() regenera dispara
	 *    "Block validation failed" en el editor y puede romper contenido. Al
	 *    inyectar solo en render, save() produce markup core LIMPIO → cero
	 *    errores de validación y contenido a prueba de futuro. Las animaciones
	 *    se previsualizan en el lienzo con wrapperProps (paso 1b), que no se guardan.
	 * -------------------------------------------------------------------- */
} )( window.wp );
