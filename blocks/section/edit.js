/* ==========================================================================
 * BloqUIX · Block bloquix/section — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*). Replica la estructura del frontend para un preview
 * WYSIWYG (bg → overlay → inner(InnerBlocks) → dividers). Block dinámico:
 * save devuelve InnerBlocks.Content y render.php envuelve el contenido.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var useBlockProps = be.useBlockProps;
	var InnerBlocks = be.InnerBlocks;
	var InspectorControls = be.InspectorControls;
	var ColorPalette = be.ColorPalette;
	var MediaUpload = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;
	var useSetting = be.useSetting || be.__experimentalUseSetting;
	var c = wp.components;

	var DIVIDER_PATHS = {
		wave: 'M0,40 C300,120 900,-40 1200,40 L1200,120 L0,120 Z',
		slant: 'M0,120 L1200,0 L1200,120 Z',
		curve: 'M0,120 Q600,-20 1200,120 Z'
	};

	function dividerSvg( shape ) {
		if ( ! DIVIDER_PATHS[ shape ] ) {
			return null;
		}
		return el( 'svg', { viewBox: '0 0 1200 120', preserveAspectRatio: 'none', 'aria-hidden': true },
			el( 'path', { d: DIVIDER_PATHS[ shape ] } ) );
	}

	function colorRow( label, value, palette, onChange ) {
		return el( c.BaseControl, { label: label, __nextHasNoMarginBottom: true },
			el( ColorPalette, {
				value: value || undefined,
				colors: palette,
				onChange: function ( v ) { onChange( v || '' ); },
				enableAlpha: true,
				__experimentalIsRenderedInSidebar: true
			} ) );
	}

	/* Campo de medio CON PREVIEW. Antes esto era solo un botón: el sidebar nunca
	   decía QUÉ imagen estaba puesta (había que fiarse del lienzo, que además va
	   atenuado por el overlay). Ahora: miniatura + Replace + Remove. */
	function mediaField( label, type, valueId, valueUrl, onSelect, onClear ) {
		var hasUrl = !! valueUrl;
		return el( c.BaseControl, { label: label, __nextHasNoMarginBottom: true },
			el( 'div', { className: 'bloquix-media-field' },
				hasUrl
					? el( 'div', { className: 'bloquix-media-field__frame' },
						'video' === type
							? el( 'video', { className: 'bloquix-media-field__media', src: valueUrl, muted: true, playsInline: true, preload: 'metadata' } )
							: el( 'img', { className: 'bloquix-media-field__media', src: valueUrl, alt: '' } ) )
					: null,
				el( MediaUploadCheck, {},
					el( MediaUpload, {
						allowedTypes: [ type ],
						value: valueId,
						onSelect: onSelect,
						render: function ( o ) {
							return el( 'div', { className: 'bloquix-media-field__actions' },
								el( c.Button, { variant: hasUrl ? 'secondary' : 'primary', onClick: o.open },
									hasUrl ? __( 'Replace', 'bloquix' ) : label ),
								hasUrl
									? el( c.Button, { variant: 'tertiary', isDestructive: true, onClick: onClear }, __( 'Remove', 'bloquix' ) )
									: null );
						}
					} ) ) ) );
	}

	var DIVIDER_OPTS = [
		{ label: __( 'None', 'bloquix' ), value: 'none' },
		{ label: __( 'Wave', 'bloquix' ), value: 'wave' },
		{ label: __( 'Diagonal', 'bloquix' ), value: 'slant' },
		{ label: __( 'Curve', 'bloquix' ), value: 'curve' }
	];

	registerBlockType( 'bloquix/section', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var palette = useSetting ? ( useSetting( 'color.palette' ) || [] ) : [];
			var gradients = useSetting ? ( useSetting( 'color.gradients' ) || [] ) : [];

			/* ---- Estilos (CSS vars) del preview ---- */
			var style = {};
			if ( a.minHeight > 0 ) { style[ '--tf-sec-min-h' ] = a.minHeight + 'vh'; }
			if ( a.bgType === 'color' && a.bgColor ) { style[ '--tf-sec-bg' ] = a.bgColor; }
			if ( a.bgType === 'gradient' && a.gradient ) { style[ '--tf-sec-gradient' ] = a.gradient; }
			if ( a.bgType === 'mesh' ) {
				if ( a.meshColor1 ) { style[ '--tf-mesh-1' ] = a.meshColor1; }
				if ( a.meshColor2 ) { style[ '--tf-mesh-2' ] = a.meshColor2; }
				if ( a.meshColor3 ) { style[ '--tf-mesh-3' ] = a.meshColor3; }
			}
			if ( a.overlay ) {
				var ovVal = ( a.overlayType === 'gradient' ) ? a.overlayGradient : a.overlayColor;
				if ( ovVal ) { style[ '--tf-sec-overlay' ] = ovVal; }
				style[ '--tf-sec-overlay-op' ] = ( a.overlayOpacity || 0 ) / 100;
				if ( a.overlayMobile ) {
					var scrimVal = a.overlayMobileColor || ( a.overlayType !== 'gradient' ? a.overlayColor : '' );
					style[ '--tf-sec-scrim-m' ] = scrimVal || '#000000';
					style[ '--tf-sec-scrim-m-op' ] = ( a.overlayMobileOpacity === undefined ? 55 : a.overlayMobileOpacity ) / 100;
				}
			}
			if ( a.dividerTop !== 'none' || a.dividerBottom !== 'none' ) {
				if ( a.dividerColor ) { style[ '--tf-sec-divider-color' ] = a.dividerColor; }
				style[ '--tf-sec-divider-h' ] = ( a.dividerHeight || 0 ) + 'px';
			}

			var classes = 'bloquix-section is-bg-' + a.bgType + ' is-valign-' + a.verticalAlignment + ' is-content-' + ( a.contentWidth || 'constrained' );
			if ( a.gradientAnimate && a.bgType === 'gradient' ) { classes += ' is-animated'; }
			if ( a.overlay && a.overlayMobile ) { classes += ' has-mobile-scrim'; }

			var blockProps = useBlockProps( { className: classes, style: style } );

			/* ---- Capa de fondo del preview ---- */
			var bgLayer = null;
			if ( a.bgType === 'image' && a.bgImageUrl ) {
				bgLayer = el( 'div', { className: 'bloquix-section__bg', style: { backgroundImage: 'url(' + a.bgImageUrl + ')' } } );
			} else if ( a.bgType === 'video' && a.bgVideoUrl ) {
				// El preview del editor refleja lo configurado (poster, loop, autoplay) para
				// que se vea aqui lo mismo que en el front.
				bgLayer = el( 'video', { className: 'bloquix-section__bg', src: a.bgVideoUrl, poster: a.bgVideoPosterUrl || undefined, muted: true, loop: a.bgVideoLoop !== false, autoPlay: a.bgVideoAutoplay !== false, playsInline: true } );
			} else if ( a.bgType === 'color' || a.bgType === 'gradient' || a.bgType === 'mesh' ) {
				bgLayer = el( 'div', { className: 'bloquix-section__bg' } );
			}

			/* ---- Controles ---- */
			var bgControls = [
				el( c.SelectControl, {
					key: 'bgtype',
					label: __( 'Background type', 'bloquix' ),
					value: a.bgType,
					options: [
						{ label: __( 'None', 'bloquix' ), value: 'none' },
						{ label: __( 'Color', 'bloquix' ), value: 'color' },
						{ label: __( 'Gradient', 'bloquix' ), value: 'gradient' },
						{ label: __( 'Mesh', 'bloquix' ), value: 'mesh' },
						{ label: __( 'Image', 'bloquix' ), value: 'image' },
						{ label: __( 'Video', 'bloquix' ), value: 'video' }
					],
					onChange: function ( v ) { set( { bgType: v } ); },
					__nextHasNoMarginBottom: true
				} )
			];
			if ( a.bgType === 'color' ) {
				bgControls.push( colorRow( __( 'Background color', 'bloquix' ), a.bgColor, palette, function ( v ) { set( { bgColor: v } ); } ) );
			} else if ( a.bgType === 'gradient' ) {
				bgControls.push( el( c.BaseControl, { key: 'grad', label: __( 'Gradient', 'bloquix' ), __nextHasNoMarginBottom: true },
					el( c.GradientPicker, { value: a.gradient || null, gradients: gradients, onChange: function ( v ) { set( { gradient: v || '' } ); } } ) ) );
				bgControls.push( el( c.ToggleControl, { key: 'ganim', label: __( 'Animate gradient', 'bloquix' ), checked: !! a.gradientAnimate, onChange: function ( v ) { set( { gradientAnimate: v } ); }, __nextHasNoMarginBottom: true } ) );
			} else if ( a.bgType === 'mesh' ) {
				bgControls.push( colorRow( __( 'Mesh — color 1', 'bloquix' ), a.meshColor1, palette, function ( v ) { set( { meshColor1: v } ); } ) );
				bgControls.push( colorRow( __( 'Mesh — color 2', 'bloquix' ), a.meshColor2, palette, function ( v ) { set( { meshColor2: v } ); } ) );
				bgControls.push( colorRow( __( 'Mesh — color 3', 'bloquix' ), a.meshColor3, palette, function ( v ) { set( { meshColor3: v } ); } ) );
			} else if ( a.bgType === 'image' ) {
				bgControls.push( el( 'div', { key: 'img', style: { marginBottom: '12px' } },
					mediaField( __( 'Choose image', 'bloquix' ), 'image', a.bgImageId, a.bgImageUrl,
						function ( m ) { set( { bgImageId: m.id, bgImageUrl: m.url } ); },
						function () { set( { bgImageId: undefined, bgImageUrl: '' } ); } ) ) );
			} else if ( a.bgType === 'video' ) {
				bgControls.push( el( 'div', { key: 'vid', style: { marginBottom: '12px' } },
					mediaField( __( 'Choose video', 'bloquix' ), 'video', a.bgVideoId, a.bgVideoUrl,
						function ( m ) { set( { bgVideoId: m.id, bgVideoUrl: m.url } ); },
						function () { set( { bgVideoId: undefined, bgVideoUrl: '' } ); } ) ) );
				// Poster: es el control que mas aporta. Se ve mientras el video carga, en
				// conexiones lentas y —sobre todo— es lo que ve quien pide menos movimiento,
				// que hasta ahora se comia el fotograma 0 (puede ser un fundido a negro).
				// Ademas activa la carga perezosa: con poster el video no se descarga hasta
				// que hace falta (ver render.php).
				bgControls.push( el( 'div', { key: 'vidposter', style: { marginBottom: '12px' } },
					mediaField( __( 'Poster image', 'bloquix' ), 'image', a.bgVideoPosterId, a.bgVideoPosterUrl,
						function ( m ) { set( { bgVideoPosterId: m.id, bgVideoPosterUrl: m.url } ); },
						function () { set( { bgVideoPosterId: undefined, bgVideoPosterUrl: '' } ); } ) ) );
				bgControls.push( el( c.ToggleControl, {
					key: 'vidauto',
					label: __( 'Play automatically', 'bloquix' ),
					checked: a.bgVideoAutoplay !== false,
					onChange: function ( v ) { set( { bgVideoAutoplay: !! v } ); },
					__nextHasNoMarginBottom: true
				} ) );
				bgControls.push( el( c.ToggleControl, {
					key: 'vidloop',
					label: __( 'Loop', 'bloquix' ),
					checked: a.bgVideoLoop !== false,
					onChange: function ( v ) { set( { bgVideoLoop: !! v } ); },
					__nextHasNoMarginBottom: true
				} ) );
				// Sin decir esto se buscan opciones que no existen (le paso al tester): el
				// fondo va mudo SIEMPRE porque los navegadores bloquean el autoplay con audio.
				bgControls.push( el( 'p', { key: 'vidnote', className: 'bloquix-editor-note' },
					__( 'Background video always plays muted, with a pause control. Visitors who ask for reduced motion see the poster instead, with a play control — and the video is not downloaded until they ask for it.', 'bloquix' ) ) );
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el( c.PanelBody, { title: __( 'Background', 'bloquix' ), initialOpen: true }, bgControls ),
					el( c.PanelBody, { title: __( 'Overlay', 'bloquix' ), initialOpen: false },
						el( c.ToggleControl, { label: __( 'Enable overlay', 'bloquix' ), checked: !! a.overlay, onChange: function ( v ) { set( { overlay: v } ); }, __nextHasNoMarginBottom: true } ),
						a.overlay ? el( c.SelectControl, {
							key: 'ovtype',
							label: __( 'Overlay type', 'bloquix' ),
							value: a.overlayType || 'color',
							options: [
								{ label: __( 'Color', 'bloquix' ), value: 'color' },
								{ label: __( 'Gradient', 'bloquix' ), value: 'gradient' }
							],
							onChange: function ( v ) { set( { overlayType: v } ); },
							__nextHasNoMarginBottom: true
						} ) : null,
						( a.overlay && ( a.overlayType || 'color' ) === 'color' ) ? colorRow( __( 'Overlay color', 'bloquix' ), a.overlayColor, palette, function ( v ) { set( { overlayColor: v } ); } ) : null,
						( a.overlay && a.overlayType === 'gradient' ) ? el( c.BaseControl, { key: 'ovgrad', label: __( 'Overlay gradient', 'bloquix' ), __nextHasNoMarginBottom: true },
							el( c.GradientPicker, { value: a.overlayGradient || null, gradients: gradients, onChange: function ( v ) { set( { overlayGradient: v || '' } ); } } ) ) : null,
						a.overlay ? el( c.RangeControl, { label: __( 'Opacity (%)', 'bloquix' ), value: a.overlayOpacity, min: 0, max: 100, onChange: function ( v ) { set( { overlayOpacity: ( v === undefined || v === null ) ? 0 : v } ); }, __nextHasNoMarginBottom: true } ) : null,
						// Un overlay en gradiente lateral protege el texto en escritorio pero
						// no en móvil, donde el texto ocupa todo el ancho: la dirección de un
						// gradiente no depende de la forma de la caja. Este refuerzo uniforme
						// es una capa aparte, así que el overlay elegido no se toca.
						a.overlay ? el( c.ToggleControl, {
							key: 'ovmob',
							label: __( 'Reinforce on small screens', 'bloquix' ),
							help: __( 'Adds a flat scrim under 782px, on top of the overlay. Useful when a side gradient stops covering the text on mobile.', 'bloquix' ),
							checked: !! a.overlayMobile,
							onChange: function ( v ) { set( { overlayMobile: v } ); },
							__nextHasNoMarginBottom: true
						} ) : null,
						( a.overlay && a.overlayMobile ) ? colorRow( __( 'Small-screen scrim color', 'bloquix' ), a.overlayMobileColor, palette, function ( v ) { set( { overlayMobileColor: v } ); } ) : null,
						( a.overlay && a.overlayMobile ) ? el( c.RangeControl, { key: 'ovmobop', label: __( 'Small-screen opacity (%)', 'bloquix' ), value: a.overlayMobileOpacity, min: 0, max: 100, onChange: function ( v ) { set( { overlayMobileOpacity: ( v === undefined || v === null ) ? 0 : v } ); }, __nextHasNoMarginBottom: true } ) : null
					),
					el( c.PanelBody, { title: __( 'Layout', 'bloquix' ), initialOpen: false },
						el( c.RangeControl, { label: __( 'Minimum height (vh)', 'bloquix' ), value: a.minHeight, min: 0, max: 100, help: __( '0 = automatic.', 'bloquix' ), onChange: function ( v ) { set( { minHeight: ( v === undefined || v === null ) ? 0 : v } ); }, __nextHasNoMarginBottom: true } ),
						el( c.SelectControl, { label: __( 'Vertical alignment', 'bloquix' ), value: a.verticalAlignment, options: [ { label: __( 'Top', 'bloquix' ), value: 'top' }, { label: __( 'Center', 'bloquix' ), value: 'center' }, { label: __( 'Bottom', 'bloquix' ), value: 'bottom' } ], onChange: function ( v ) { set( { verticalAlignment: v } ); }, __nextHasNoMarginBottom: true } ),
						el( c.SelectControl, {
							label: __( 'Content width', 'bloquix' ),
							value: a.contentWidth,
							options: [
								{ label: __( 'Constrained (content)', 'bloquix' ), value: 'constrained' },
								{ label: __( 'Wide', 'bloquix' ), value: 'wide' },
								{ label: __( 'Full', 'bloquix' ), value: 'full' }
							],
							help: __( 'Independent of the section width. Set the section to “Full width” (toolbar) and constrain the content here.', 'bloquix' ),
							onChange: function ( v ) { set( { contentWidth: v } ); },
							__nextHasNoMarginBottom: true
						} )
					),
					el( c.PanelBody, { title: __( 'Shape dividers', 'bloquix' ), initialOpen: false },
						el( c.SelectControl, { label: __( 'Top divider', 'bloquix' ), value: a.dividerTop, options: DIVIDER_OPTS, onChange: function ( v ) { set( { dividerTop: v } ); }, __nextHasNoMarginBottom: true } ),
						el( c.SelectControl, { label: __( 'Bottom divider', 'bloquix' ), value: a.dividerBottom, options: DIVIDER_OPTS, onChange: function ( v ) { set( { dividerBottom: v } ); }, __nextHasNoMarginBottom: true } ),
						( a.dividerTop !== 'none' || a.dividerBottom !== 'none' ) ? colorRow( __( 'Divider color', 'bloquix' ), a.dividerColor, palette, function ( v ) { set( { dividerColor: v } ); } ) : null,
						( a.dividerTop !== 'none' || a.dividerBottom !== 'none' ) ? el( c.RangeControl, { label: __( 'Divider height (px)', 'bloquix' ), value: a.dividerHeight, min: 10, max: 240, onChange: function ( v ) { set( { dividerHeight: v || 60 } ); }, __nextHasNoMarginBottom: true } ) : null
					)
				),
				el(
					'section',
					blockProps,
					a.dividerTop !== 'none' ? el( 'div', { className: 'bloquix-section__divider bloquix-section__divider--top' }, dividerSvg( a.dividerTop ) ) : null,
					bgLayer,
					a.overlay ? el( 'div', { className: 'bloquix-section__overlay' } ) : null,
					( a.overlay && a.overlayMobile ) ? el( 'div', { className: 'bloquix-section__scrim' } ) : null,
					el( 'div', { className: 'bloquix-section__inner' }, el( InnerBlocks, { renderAppender: InnerBlocks.ButtonBlockAppender } ) ),
					a.dividerBottom !== 'none' ? el( 'div', { className: 'bloquix-section__divider bloquix-section__divider--bottom' }, dividerSvg( a.dividerBottom ) ) : null
				)
			);
		},

		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
