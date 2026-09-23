/* ==========================================================================
 * Blokino · Block blokino/content-slider — editor
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
		{ label: __( 'Inherit', 'blokino' ), value: 'inherit' },
		{ label: __( 'Left', 'blokino' ), value: 'left' },
		{ label: __( 'Center', 'blokino' ), value: 'center' },
		{ label: __( 'Right', 'blokino' ), value: 'right' }
	];
	var ALIGN_CONTENT = [
		{ label: __( 'Left', 'blokino' ), value: 'left' },
		{ label: __( 'Center', 'blokino' ), value: 'center' },
		{ label: __( 'Right', 'blokino' ), value: 'right' }
	];
	var CTA_STYLES = [
		{ label: __( 'Filled', 'blokino' ), value: 'filled' },
		{ label: __( 'Outline', 'blokino' ), value: 'outline' },
		{ label: __( 'Text', 'blokino' ), value: 'text' }
	];
	var BG_SIZE = [
		{ label: __( 'Cover (fill)', 'blokino' ), value: 'cover' },
		{ label: __( 'Contain (fit)', 'blokino' ), value: 'contain' },
		{ label: __( 'Auto (original)', 'blokino' ), value: 'auto' }
	];
	var BG_REPEAT = [
		{ label: __( 'No repeat', 'blokino' ), value: 'no-repeat' },
		{ label: __( 'Repeat', 'blokino' ), value: 'repeat' },
		{ label: __( 'Repeat X', 'blokino' ), value: 'repeat-x' },
		{ label: __( 'Repeat Y', 'blokino' ), value: 'repeat-y' }
	];
	// Posiciones de fondo por palabra clave (CSS background-position). El valor es
	// una cadena CSS válida; render.php la valida contra esta misma lista blanca.
	var BG_POSITION = [
		{ label: __( 'Top left', 'blokino' ), value: 'left top' },
		{ label: __( 'Top center', 'blokino' ), value: 'center top' },
		{ label: __( 'Top right', 'blokino' ), value: 'right top' },
		{ label: __( 'Center left', 'blokino' ), value: 'left center' },
		{ label: __( 'Center', 'blokino' ), value: 'center center' },
		{ label: __( 'Center right', 'blokino' ), value: 'right center' },
		{ label: __( 'Bottom left', 'blokino' ), value: 'left bottom' },
		{ label: __( 'Bottom center', 'blokino' ), value: 'center bottom' },
		{ label: __( 'Bottom right', 'blokino' ), value: 'right bottom' }
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
			{ key: 'cta' + ctaIndex, label: __( 'CTA', 'blokino' ) + ' ' + ( ctaIndex + 1 ), __nextHasNoMarginBottom: true },
			el( C.TextControl, { label: __( 'Text', 'blokino' ), value: cta.text, onChange: function ( v ) { setCta( { text: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Link (URL)', 'blokino' ), value: cta.url, onChange: function ( v ) { setCta( { url: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Style', 'blokino' ), value: cta.style, options: CTA_STYLES, onChange: function ( v ) { setCta( { style: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.ToggleControl, { label: __( 'Open in new tab', 'blokino' ), checked: !! cta.newTab, onChange: function ( v ) { setCta( { newTab: v } ); }, __nextHasNoMarginBottom: true } )
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
			{ key: 'bg', label: __( 'Background image', 'blokino' ), __nextHasNoMarginBottom: true },
			el( C.SelectControl, { label: __( 'Position', 'blokino' ), value: item.bgPosition || 'center center', options: BG_POSITION, onChange: function ( v ) { update( { bgPosition: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Size', 'blokino' ), value: item.bgSize || 'cover', options: BG_SIZE, onChange: function ( v ) { update( { bgSize: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Repeat', 'blokino' ), value: item.bgRepeat || 'no-repeat', options: BG_REPEAT, onChange: function ( v ) { update( { bgRepeat: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.RangeControl, { label: __( 'Overlay (%)', 'blokino' ), help: __( 'Darkens the image so the text stays readable.', 'blokino' ), min: 0, max: 90, value: ( item.bgOverlay == null ? 40 : item.bgOverlay ), onChange: function ( v ) { update( { bgOverlay: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { key: 'ovtype', label: __( 'Overlay type', 'blokino' ), value: item.bgOverlayType || 'color', options: [ { label: __( 'Color', 'blokino' ), value: 'color' }, { label: __( 'Gradient', 'blokino' ), value: 'gradient' } ], onChange: function ( v ) { update( { bgOverlayType: v } ); }, __nextHasNoMarginBottom: true } ),
			( ( item.bgOverlayType || 'color' ) === 'color' ) ? el(
				C.BaseControl,
				{ key: 'ovcolor', label: __( 'Overlay color', 'blokino' ), help: __( 'Defaults to the theme ink color.', 'blokino' ), __nextHasNoMarginBottom: true },
				el( C.ColorPalette, {
					colors: palette || [],
					value: item.bgOverlayColor || '',
					clearable: true,
					onChange: function ( v ) { update( { bgOverlayColor: v || '' } ); }
				} )
			) : null,
			( item.bgOverlayType === 'gradient' ) ? el(
				C.BaseControl,
				{ key: 'ovgrad', label: __( 'Overlay gradient', 'blokino' ), help: __( 'A CSS gradient over the image (for a directional scrim).', 'blokino' ), __nextHasNoMarginBottom: true },
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
			el( window.blokino.MediaField, {
				id: item.imageId,
				url: item.imageUrl,
				onSelect: function ( media ) { update( { imageId: media.id, imageUrl: media.url } ); },
				onRemove: function () { update( { imageId: 0, imageUrl: '' } ); },
				setLabel: __( 'Set image', 'blokino' ),
				replaceLabel: __( 'Replace image', 'blokino' )
			} ),
			bgFields( item, update, palette, gradients ),
			el( C.TextControl, { label: __( 'Title', 'blokino' ), value: item.title, onChange: function ( v ) { update( { title: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Title alignment', 'blokino' ), value: item.titleAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { titleAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.ToggleControl, { label: __( 'Subtitle before title', 'blokino' ), checked: !! item.subtitleFirst, onChange: function ( v ) { update( { subtitleFirst: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Subtitle', 'blokino' ), value: item.subtitle, onChange: function ( v ) { update( { subtitle: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Subtitle alignment', 'blokino' ), value: item.subtitleAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { subtitleAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextareaControl, { label: __( 'Description', 'blokino' ), value: item.description, onChange: function ( v ) { update( { description: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Description alignment', 'blokino' ), value: item.descAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { descAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.SelectControl, { label: __( 'Content alignment (slide)', 'blokino' ), value: item.contentAlign, options: ALIGN_CONTENT, onChange: function ( v ) { update( { contentAlign: v } ); }, __nextHasNoMarginBottom: true } ),
			ctaField( item, 0, update ),
			ctaField( item, 1, update )
		);
	}

	registerBlockType( 'blokino/content-slider', {
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
						{ title: __( 'Slides', 'blokino' ), initialOpen: true },
						el( window.blokino.RepeaterControl, {
							items: attrs.items,
							onChange: function ( items ) { setAttributes( { items: items } ); },
							renderItem: function ( item, i, update ) { return renderItem( item, i, update, palette, gradients ); },
							newItem: newSlide,
							onActivate: setActiveSlide,
							addLabel: __( 'Add slide', 'blokino' ),
							itemLabel: function ( it, i ) { return it.title || ( __( 'Slide', 'blokino' ) + ' ' + ( i + 1 ) ); }
						} )
					),
					el(
						C.PanelBody,
						{ title: __( 'Carousel', 'blokino' ), initialOpen: false },
						el( C.RangeControl, { label: __( 'Slides per view', 'blokino' ), min: 1, max: 4, value: attrs.slidesPerView, onChange: function ( v ) { setAttributes( { slidesPerView: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.RangeControl, { label: __( 'Space between (px)', 'blokino' ), min: 0, max: 80, value: attrs.spaceBetween, onChange: function ( v ) { setAttributes( { spaceBetween: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Infinite loop', 'blokino' ), checked: attrs.loop, onChange: function ( v ) { setAttributes( { loop: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Autoplay', 'blokino' ), checked: attrs.autoplay, onChange: function ( v ) { setAttributes( { autoplay: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Pagination', 'blokino' ), checked: attrs.pagination, onChange: function ( v ) { setAttributes( { pagination: v } ); }, __nextHasNoMarginBottom: true } ),
						el( C.ToggleControl, { label: __( 'Navigation arrows', 'blokino' ), checked: attrs.navigation, onChange: function ( v ) { setAttributes( { navigation: v } ); }, __nextHasNoMarginBottom: true } ),
						attrs.navigation
							? el( C.ToggleControl, {
								label: __( 'Arrows outside', 'blokino' ),
								help: __( 'Place the arrows in the side margins instead of over the slides.', 'blokino' ),
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
						? el( window.blokino.CarouselPreview, { block: 'blokino/content-slider', attributes: attrs, activeIndex: activeSlide } )
						: el( C.Placeholder, {
							icon: 'images-alt',
							label: __( 'Content Slider', 'blokino' ),
							instructions: __( 'Add slides from the “Slides” panel in the block sidebar.', 'blokino' )
						} )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
