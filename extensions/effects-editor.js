/* ==========================================================================
 * Blokino · Extensiones tf* sobre blocks nativos (editor)
 * --------------------------------------------------------------------------
 * Pipeline del editor:
 *   1. blocks.registerBlockType → declara los atributos tf* (viven en el
 *      comentario del block; NO se tocan el markup guardado).
 *   2. editor.BlockEdit (HOC)   → panel "Blokino Effects".
 *   La inyección de data-tf-* ocurre SOLO en render (PHP render_block), nunca
 *   en save(), para no romper la validación de bloques del editor.
 *
 * FASE B · LOTE 1 — animaciones de entrada (vanilla, sin GSAP):
 *   tfAnimation: fade-up, clip-reveal, mask-up, blur-in, scale-in,
 *                slide-left, slide-right, text-stagger.
 *   tfStagger:   escalona la entrada de los hijos directos (y de las palabras
 *                en text-stagger).
 *   El resto del catálogo (hover, scroll, blend, border-fx) llega en los
 *   lotes 2 y 3; sus atributos siguen declarados pero sin controles.
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

	// Blocks nativos a los que se aplica la extensión (allowlist, FASE A).
	var ALLOWED = [
		'core/group',
		'core/columns',
		'core/cover',
		'core/image',
		'core/heading',
		'core/paragraph',
		'core/buttons',
		'core/button',
		// Blocks propios: también pueden tener efectos de entrada/hover/scroll.
		'blokino/section',
		'blokino/marquee',
		'blokino/counter',
		'blokino/before-after'
	];

	function isAllowed( name ) {
		return ALLOWED.indexOf( name ) !== -1;
	}

	/* ----------------------------------------------------------------------
	 * 1) Declaración de atributos tf*.
	 *    FASE A implementa animation/delay/duration/easing. El resto del
	 *    catálogo (§4.1) queda declarado pero sin controles ni salida todavía.
	 * -------------------------------------------------------------------- */
	function addTfAttributes( settings, name ) {
		if ( ! isAllowed( name ) ) {
			return settings;
		}

		settings.attributes = Object.assign( {}, settings.attributes, {
			// Implementados en FASE A:
			tfAnimation:    { type: 'string', default: '' },
			tfAnimDelay:    { type: 'number', default: 0 },
			tfAnimDuration: { type: 'number', default: 0 },
			tfAnimEasing:   { type: 'string', default: '' },
			// Declarados, sin implementar hasta FASE B:
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
	 * 2) Panel "Blokino Effects" en InspectorControls (HOC sobre BlockEdit).
	 *    Las animaciones corren en el FRONTEND; aquí solo exponemos controles.
	 * -------------------------------------------------------------------- */
	var withBlokinoEffects = createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( ! isAllowed( props.name ) ) {
				return el( BlockEdit, props );
			}

			var a = props.attributes;
			var set = props.setAttributes;
			var themePalette = useSettings
				? ( useSettings( 'color.palette' )[0] || [] )
				: ( useSetting ? ( useSetting( 'color.palette' ) || [] ) : [] );

			var animationControl = el( SelectControl, {
				label: __( 'Entrance animation', 'blokino' ),
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
				},
				__nextHasNoMarginBottom: true
			} );

			var displayControl = el( SelectControl, {
				label: __( 'Display style', 'blokino' ),
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
							{ label: __( 'Theme default (expo)', 'blokino' ), value: '' },
							{ label: 'Expo', value: 'expo' }, // Easing curve names are technical keywords, left raw (untranslated) on purpose.
							{ label: 'Power3', value: 'power3' },
							{ label: 'Spring', value: 'spring' },
							{ label: 'Circ', value: 'circ' }
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
					el(
						'p',
						{ style: { fontStyle: 'italic', opacity: 0.7, marginTop: '8px' } },
						__( 'It will animate once published (the animation runs on the front-end).', 'blokino' )
					),
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

			return el(
				Fragment,
				{},
				el( BlockEdit, props ),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Blokino Effects', 'blokino' ), initialOpen: false },
						animationControl,
						displayControl,
						detailControls,
						hr(),
						hoverControl,
						hr(),
						scrollControl,
						parallaxControl,
						hr(),
						blendControl,
						borderControl,
						borderDetail
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
	 *    corren en el frontend, así que no se pierde nada en el editor.
	 * -------------------------------------------------------------------- */
} )( window.wp );
