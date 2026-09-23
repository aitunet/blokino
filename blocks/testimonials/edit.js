/* ==========================================================================
 * Blokino · Block blokino/testimonials — editor (modelo REPEATER)
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

	var LAYOUTS = [
		{ label: __( 'Carousel', 'blokino' ), value: 'carousel' },
		{ label: __( 'Grid', 'blokino' ), value: 'grid' }
	];

	function newTestimonial() {
		return { avatarId: 0, avatarUrl: '', rating: 5, name: '', role: '', quote: '' };
	}

	function renderItem( item, index, update ) {
		return el(
			Fragment,
			{},
			el( window.blokino.MediaField, {
				id: item.avatarId,
				url: item.avatarUrl,
				round: true,
				onSelect: function ( media ) { update( { avatarId: media.id, avatarUrl: media.url } ); },
				onRemove: function () { update( { avatarId: 0, avatarUrl: '' } ); },
				setLabel: __( 'Set avatar', 'blokino' ),
				replaceLabel: __( 'Replace avatar', 'blokino' )
			} ),
			el( C.RangeControl, { label: __( 'Rating (0–5)', 'blokino' ), min: 0, max: 5, step: 0.5, value: item.rating, onChange: function ( v ) { update( { rating: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Name', 'blokino' ), value: item.name, onChange: function ( v ) { update( { name: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Role · Company', 'blokino' ), value: item.role, onChange: function ( v ) { update( { role: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextareaControl, { label: __( 'Quote', 'blokino' ), value: item.quote, onChange: function ( v ) { update( { quote: v } ); }, __nextHasNoMarginBottom: true } )
		);
	}

	registerBlockType( 'blokino/testimonials', {
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var isGrid = attrs.layout === 'grid';
			var activeState = useState( 0 );
			var activeSlide = activeState[ 0 ];
			var setActiveSlide = activeState[ 1 ];

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						C.PanelBody,
						{ title: __( 'Testimonials', 'blokino' ), initialOpen: true },
						el( C.SelectControl, { label: __( 'Layout', 'blokino' ), value: attrs.layout, options: LAYOUTS, onChange: function ( v ) { setAttributes( { layout: v } ); }, __nextHasNoMarginBottom: true } ),
						isGrid
							? el( C.RangeControl, { label: __( 'Columns', 'blokino' ), min: 1, max: 4, value: attrs.columns, onChange: function ( v ) { setAttributes( { columns: v } ); }, __nextHasNoMarginBottom: true } )
							: el( Fragment, {},
								el( C.RangeControl, { label: __( 'Slides per view', 'blokino' ), min: 1, max: 4, value: attrs.slidesPerView, onChange: function ( v ) { setAttributes( { slidesPerView: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.RangeControl, { label: __( 'Space between (px)', 'blokino' ), min: 0, max: 80, value: attrs.spaceBetween, onChange: function ( v ) { setAttributes( { spaceBetween: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Infinite loop', 'blokino' ), checked: attrs.loop, onChange: function ( v ) { setAttributes( { loop: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Autoplay', 'blokino' ), checked: attrs.autoplay, onChange: function ( v ) { setAttributes( { autoplay: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Pagination', 'blokino' ), checked: attrs.pagination, onChange: function ( v ) { setAttributes( { pagination: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Navigation arrows', 'blokino' ), checked: attrs.navigation, onChange: function ( v ) { setAttributes( { navigation: v } ); }, __nextHasNoMarginBottom: true } )
							)
					),
					el(
						C.PanelBody,
						{ title: __( 'Items', 'blokino' ), initialOpen: true },
						el( window.blokino.RepeaterControl, {
							items: attrs.items,
							onChange: function ( items ) { setAttributes( { items: items } ); },
							renderItem: renderItem,
							newItem: newTestimonial,
							onActivate: setActiveSlide,
							addLabel: __( 'Add testimonial', 'blokino' ),
							itemLabel: function ( it, i ) { return it.name || ( __( 'Testimonial', 'blokino' ) + ' ' + ( i + 1 ) ); }
						} )
					)
				),
				el(
					'div',
					blockProps,
					( attrs.items && attrs.items.length )
						? el( window.blokino.CarouselPreview, { block: 'blokino/testimonials', attributes: attrs, activeIndex: activeSlide } )
						: el( C.Placeholder, {
							icon: 'format-quote',
							label: __( 'Testimonials', 'blokino' ),
							instructions: __( 'Add testimonials from the “Items” panel in the block sidebar.', 'blokino' )
						} )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
