/* ==========================================================================
 * BloqUIX · Block bloquix/content-slider — editor
 * Repeater en el sidebar (RepeaterControl) + preview ServerSideRender.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var C = wp.components;
	var InspectorControls = be.InspectorControls;
	var useBlockProps = be.useBlockProps;

	var ALIGN_FIELD = [
		{ label: __( 'Inherit', 'bloquix' ), value: 'inherit' },
		{ label: __( 'Left', 'bloquix' ), value: 'left' },
		{ label: __( 'Center', 'bloquix' ), value: 'center' },
		{ label: __( 'Right', 'bloquix' ), value: 'right' }
	];
	var ALIGN_CONTENT = [
		{ label: __( 'Left', 'bloquix' ), value: 'left' },
		{ label: __( 'Center', 'bloquix' ), value: 'center' },
		{ label: __( 'Right', 'bloquix' ), value: 'right' }
	];
	var CTA_STYLES = [
		{ label: __( 'Filled', 'bloquix' ), value: 'filled' },
		{ label: __( 'Outline', 'bloquix' ), value: 'outline' },
		{ label: __( 'Text', 'bloquix' ), value: 'text' }
	];
	var BG_SIZE = [
		{ label: __( 'Cover (fill)', 'bloquix' ), value: 'cover' },
		{ label: __( 'Contain (fit)', 'bloquix' ), value: 'contain' },
		{ label: __( 'Auto (original)', 'bloquix' ), value: 'auto' }
	];
	var BG_REPEAT = [
		{ label: __( 'No repeat', 'bloquix' ), value: 'no-repeat' },
		{ label: __( 'Repeat', 'bloquix' ), value: 'repeat' },
		{ label: __( 'Repeat X', 'bloquix' ), value: 'repeat-x' },
		{ label: __( 'Repeat Y', 'bloquix' ), value: 'repeat-y' }
	];
	// Posiciones de fondo por palabra clave (CSS background-position). El valor es
	// una cadena CSS válida; render.php la valida contra esta misma lista blanca.
	var BG_POSITION = [
		{ label: __( 'Top left', 'bloquix' ), value: 'left top' },
		{ label: __( 'Top center', 'bloquix' ), value: 'center top' },
		{ label: __( 'Top right', 'bloquix' ), value: 'right top' },
		{ label: __( 'Center left', 'bloquix' ), value: 'left center' },
		{ label: __( 'Center', 'bloquix' ), value: 'center center' },
		{ label: __( 'Center right', 'bloquix' ), value: 'right center' },
		{ label: __( 'Bottom left', 'bloquix' ), value: 'left bottom' },
		{ label: __( 'Bottom center', 'bloquix' ), value: 'center bottom' },
		{ label: __( 'Bottom right', 'bloquix' ), value: 'right bottom' }
	];

	function newSlide() {
		return {
			imageId: 0, imageUrl: '',
			bgPosition: 'center center', bgSize: 'cover', bgRepeat: 'no-repeat', bgOverlay: 40, bgOverlayColor: '',
			title: '', titleAlign: 'inherit',
			subtitle: '', subtitleAlign: 'inherit', subtitleFirst: false,
			description: '', descAlign: 'inherit',
			contentAlign: 'left',
			ctas: [ { text: '', url: '', style: 'filled', newTab: false }, { text: '', url: '', style: 'outline', newTab: false } ]
		};
	}

	function ctaField( item, ctaIndex, update ) {
		var cta = ( item.ctas && item.ctas[ ctaIndex ] ) || { text: '', url: '', style: ctaIndex === 0 ? 'filled' : 'outline', newTab: false };
		function setCta( patch ) {
			var ctas = ( item.ctas || [] ).slice();
			while ( ctas.length <= ctaIndex ) { ctas.push( { text: '', url: '', style: 'filled', newTab: false } ); }
			ctas[ ctaIndex ] = Object.assign( {}, cta, patch );
			update( { ctas: ctas } );
		}
		return el(
			C.BaseControl,
			{ key: 'cta' + ctaIndex, label: __( 'CTA', 'bloquix' ) + ' ' + ( ctaIndex + 1 ), __nextHasNoMarginBottom: true },
			el( C.TextControl, { label: __( 'Text', 'bloquix' ), value: cta.text, onChange: function ( v ) { setCta( { text: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Link (URL)', 'bloquix' ), value: cta.url, onChange: function ( v ) { setCta( { url: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Style', 'bloquix' ), value: cta.style, options: CTA_STYLES, onChange: function ( v ) { setCta( { style: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.ToggleControl, { label: __( 'Open in new tab', 'bloquix' ), checked: !! cta.newTab, onChange: function ( v ) { setCta( { newTab: v } ); }, __nextHasNoMarginBottom: true } )
		);
	}

	// Controles de la imagen de FONDO (posición por palabra clave, tamaño, repetición,
	// overlay + color). Solo se muestran cuando el slide tiene imagen; el fondo es
	// opt-in por slide. `palette` = colores del theme (para el color del overlay).
	function bgFields( item, update, palette, gradients ) {
		if ( ! item.imageId && ! item.imageUrl ) {
			return null;
		}
		return el(
			C.BaseControl,
			{ key: 'bg', label: __( 'Background image', 'bloquix' ), __nextHasNoMarginBottom: true },
			el( C.SelectControl, { label: __( 'Position', 'bloquix' ), value: item.bgPosition || 'center center', options: BG_POSITION, onChange: function ( v ) { update( { bgPosition: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Size', 'bloquix' ), value: item.bgSize || 'cover', options: BG_SIZE, onChange: function ( v ) { update( { bgSize: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Repeat', 'bloquix' ), value: item.bgRepeat || 'no-repeat', options: BG_REPEAT, onChange: function ( v ) { update( { bgRepeat: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.RangeControl, { label: __( 'Overlay (%)', 'bloquix' ), help: __( 'Darkens the image so the text stays readable.', 'bloquix' ), min: 0, max: 90, value: ( item.bgOverlay == null ? 40 : item.bgOverlay ), onChange: function ( v ) { update( { bgOverlay: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { key: 'ovtype', label: __( 'Overlay type', 'bloquix' ), value: item.bgOverlayType || 'color', options: [ { label: __( 'Color', 'bloquix' ), value: 'color' }, { label: __( 'Gradient', 'bloquix' ), value: 'gradient' } ], onChange: function ( v ) { update( { bgOverlayType: v } ); }, __nextHasNoMarginBottom: true } ),
			( ( item.bgOverlayType || 'color' ) === 'color' ) ? el(
				C.BaseControl,
				{ key: 'ovcolor', label: __( 'Overlay color', 'bloquix' ), help: __( 'Defaults to the theme ink color.', 'bloquix' ), __nextHasNoMarginBottom: true },
				el( C.ColorPalette, {
					colors: palette || [],
					value: item.bgOverlayColor || '',
					clearable: true,
					onChange: function ( v ) { update( { bgOverlayColor: v || '' } ); }
				} )
			) : null,
			( item.bgOverlayType === 'gradient' ) ? el(
				C.BaseControl,
				{ key: 'ovgrad', label: __( 'Overlay gradient', 'bloquix' ), help: __( 'A CSS gradient over the image (for a directional scrim).', 'bloquix' ), __nextHasNoMarginBottom: true },
				el( C.GradientPicker, {
					value: item.bgOverlayGradient || null,
					gradients: gradients || [],
					onChange: function ( v ) { update( { bgOverlayGradient: v || '' } ); }
				} )
			) : null
		);
	}

	function renderItem( item, index, update, palette, gradients ) {
		return el(
			Fragment,
			{},
			el( window.bloquix.MediaField, {
				id: item.imageId,
				url: item.imageUrl,
				onSelect: function ( media ) { update( { imageId: media.id, imageUrl: media.url } ); },
				onRemove: function () { update( { imageId: 0, imageUrl: '' } ); },
				setLabel: __( 'Set image', 'bloquix' ),
				replaceLabel: __( 'Replace image', 'bloquix' )
			} ),
			bgFields( item, update, palette, gradients ),
			el( C.TextControl, { label: __( 'Title', 'bloquix' ), value: item.title, onChange: function ( v ) { update( { title: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Title alignment', 'bloquix' ), value: item.titleAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { titleAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.ToggleControl, { label: __( 'Subtitle before title', 'bloquix' ), checked: !! item.subtitleFirst, onChange: function ( v ) { update( { subtitleFirst: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Subtitle', 'bloquix' ), value: item.subtitle, onChange: function ( v ) { update( { subtitle: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Subtitle alignment', 'bloquix' ), value: item.subtitleAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { subtitleAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextareaControl, { label: __( 'Description', 'bloquix' ), value: item.description, onChange: function ( v ) { update( { description: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Description alignment', 'bloquix' ), value: item.descAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { descAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Content alignment (slide)', 'bloquix' ), value: item.contentAlign, options: ALIGN_CONTENT, onChange: function ( v ) { update( { contentAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			ctaField( item, 0, update ),
			ctaField( item, 1, update )
		);
	}

	registerBlockType( 'bloquix/content-slider', {
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var activeState = useState( 0 );
			var activeSlide = activeState[ 0 ];
			var setActiveSlide = activeState[ 1 ];

			// Paleta del theme (editor settings) para el color del overlay: array plano
			// de colores del editor, vía useSelect para reaccionar si cambia.
			var palette = wp.data.useSelect( function ( select ) {
				var s = select( 'core/block-editor' ).getSettings();
				return ( s && s.colors ) || [];
			}, [] );
			var gradients = wp.data.useSelect( function ( select ) {
				var s = select( 'core/block-editor' ).getSettings();
				return ( s && s.gradients ) || [];
			}, [] );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						C.PanelBody,
						{ title: __( 'Slides', 'bloquix' ), initialOpen: true },
						el( window.bloquix.RepeaterControl, {
							items: attrs.items,
							onChange: function ( items ) { setAttributes( { items: items } ); },
							renderItem: function ( item, i, update ) { return renderItem( item, i, update, palette, gradients ); },
							newItem: newSlide,
							onActivate: setActiveSlide,
							addLabel: __( 'Add slide', 'bloquix' ),
							itemLabel: function ( it, i ) { return it.title || ( __( 'Slide', 'bloquix' ) + ' ' + ( i + 1 ) ); }
						} )
					),
					el(
						C.PanelBody,
						{ title: __( 'Carousel', 'bloquix' ), initialOpen: false },
						el( C.RangeControl, { label: __( 'Slides per view', 'bloquix' ), min: 1, max: 4, value: attrs.slidesPerView, onChange: function ( v ) { setAttributes( { slidesPerView: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.RangeControl, { label: __( 'Space between (px)', 'bloquix' ), min: 0, max: 80, value: attrs.spaceBetween, onChange: function ( v ) { setAttributes( { spaceBetween: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Infinite loop', 'bloquix' ), checked: attrs.loop, onChange: function ( v ) { setAttributes( { loop: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Autoplay', 'bloquix' ), checked: attrs.autoplay, onChange: function ( v ) { setAttributes( { autoplay: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Pagination', 'bloquix' ), checked: attrs.pagination, onChange: function ( v ) { setAttributes( { pagination: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Navigation arrows', 'bloquix' ), checked: attrs.navigation, onChange: function ( v ) { setAttributes( { navigation: v } ); }, __nextHasNoMarginBottom: true } ),
						attrs.navigation
							? el( C.ToggleControl, {
								label: __( 'Arrows outside', 'bloquix' ),
								help: __( 'Place the arrows in the side margins instead of over the slides.', 'bloquix' ),
								checked: !! attrs.arrowsOutside,
								onChange: function ( v ) { setAttributes( { arrowsOutside: v } ); },
								__nextHasNoMarginBottom: true
							} )
							: null
					)
				),
				el(
					'div',
					blockProps,
					( attrs.items && attrs.items.length )
						? el( window.bloquix.CarouselPreview, { block: 'bloquix/content-slider', attributes: attrs, activeIndex: activeSlide } )
						: el( C.Placeholder, {
							icon: 'images-alt',
							label: __( 'Content Slider', 'bloquix' ),
							instructions: __( 'Add slides from the “Slides” panel in the block sidebar.', 'bloquix' )
						} )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
