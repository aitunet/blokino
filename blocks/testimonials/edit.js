/* ==========================================================================
 * BloqUIX · Block bloquix/testimonials — editor (modelo REPEATER)
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
		{ label: __( 'Carousel', 'bloquix' ), value: 'carousel' },
		{ label: __( 'Grid', 'bloquix' ), value: 'grid' }
	];

	function newTestimonial() {
		return { avatarId: 0, avatarUrl: '', rating: 5, name: '', role: '', quote: '' };
	}

	function renderItem( item, index, update ) {
		return el(
			Fragment,
			{},
			el( window.bloquix.MediaField, {
				id: item.avatarId,
				url: item.avatarUrl,
				round: true,
				onSelect: function ( media ) { update( { avatarId: media.id, avatarUrl: media.url } ); },
				onRemove: function () { update( { avatarId: 0, avatarUrl: '' } ); },
				setLabel: __( 'Set avatar', 'bloquix' ),
				replaceLabel: __( 'Replace avatar', 'bloquix' )
			} ),
			el( C.RangeControl, { label: __( 'Rating (0–5)', 'bloquix' ), min: 0, max: 5, step: 0.5, value: item.rating, onChange: function ( v ) { update( { rating: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Name', 'bloquix' ), value: item.name, onChange: function ( v ) { update( { name: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Role · Company', 'bloquix' ), value: item.role, onChange: function ( v ) { update( { role: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextareaControl, { label: __( 'Quote', 'bloquix' ), value: item.quote, onChange: function ( v ) { update( { quote: v } ); }, __nextHasNoMarginBottom: true } )
		);
	}

	registerBlockType( 'bloquix/testimonials', {
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
						{ title: __( 'Testimonials', 'bloquix' ), initialOpen: true },
						el( C.SelectControl, { label: __( 'Layout', 'bloquix' ), value: attrs.layout, options: LAYOUTS, onChange: function ( v ) { setAttributes( { layout: v } ); }, __nextHasNoMarginBottom: true } ),
						isGrid
							? el( C.RangeControl, { label: __( 'Columns', 'bloquix' ), min: 1, max: 4, value: attrs.columns, onChange: function ( v ) { setAttributes( { columns: v } ); }, __nextHasNoMarginBottom: true } )
							: el( Fragment, {},
								el( C.RangeControl, { label: __( 'Slides per view', 'bloquix' ), min: 1, max: 4, value: attrs.slidesPerView, onChange: function ( v ) { setAttributes( { slidesPerView: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.RangeControl, { label: __( 'Space between (px)', 'bloquix' ), min: 0, max: 80, value: attrs.spaceBetween, onChange: function ( v ) { setAttributes( { spaceBetween: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Infinite loop', 'bloquix' ), checked: attrs.loop, onChange: function ( v ) { setAttributes( { loop: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Autoplay', 'bloquix' ), checked: attrs.autoplay, onChange: function ( v ) { setAttributes( { autoplay: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Pagination', 'bloquix' ), checked: attrs.pagination, onChange: function ( v ) { setAttributes( { pagination: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Navigation arrows', 'bloquix' ), checked: attrs.navigation, onChange: function ( v ) { setAttributes( { navigation: v } ); }, __nextHasNoMarginBottom: true } )
							)
					),
					el(
						C.PanelBody,
						{ title: __( 'Items', 'bloquix' ), initialOpen: true },
						el( window.bloquix.RepeaterControl, {
							items: attrs.items,
							onChange: function ( items ) { setAttributes( { items: items } ); },
							renderItem: renderItem,
							newItem: newTestimonial,
							onActivate: setActiveSlide,
							addLabel: __( 'Add testimonial', 'bloquix' ),
							itemLabel: function ( it, i ) { return it.name || ( __( 'Testimonial', 'bloquix' ) + ' ' + ( i + 1 ) ); }
						} )
					)
				),
				el(
					'div',
					blockProps,
					( attrs.items && attrs.items.length )
						? el( window.bloquix.CarouselPreview, { block: 'bloquix/testimonials', attributes: attrs, activeIndex: activeSlide } )
						: el( C.Placeholder, {
							icon: 'format-quote',
							label: __( 'Testimonials', 'bloquix' ),
							instructions: __( 'Add testimonials from the “Items” panel in the block sidebar.', 'bloquix' )
						} )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
