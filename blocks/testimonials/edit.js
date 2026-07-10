/* ==========================================================================
 * Tunet Core · Block tunet/testimonials — editor (modelo REPEATER)
 * Repeater en el sidebar (RepeaterControl) + preview ServerSideRender.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var C = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var InspectorControls = be.InspectorControls;
	var useBlockProps = be.useBlockProps;
	var MediaUpload = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;

	var LAYOUTS = [
		{ label: __( 'Carousel', 'tunet' ), value: 'carousel' },
		{ label: __( 'Grid', 'tunet' ), value: 'grid' }
	];

	function newTestimonial() {
		return { avatarId: 0, avatarUrl: '', rating: 5, name: '', role: '', quote: '' };
	}

	function renderItem( item, index, update ) {
		return el(
			Fragment,
			{},
			el( MediaUploadCheck, {},
				el( MediaUpload, {
					allowedTypes: [ 'image' ],
					value: item.avatarId,
					onSelect: function ( media ) { update( { avatarId: media.id, avatarUrl: media.url } ); },
					render: function ( o ) {
						return el( C.Button, { variant: 'secondary', onClick: o.open }, item.avatarId ? __( 'Replace avatar', 'tunet' ) : __( 'Set avatar', 'tunet' ) );
					}
				} )
			),
			el( C.RangeControl, { label: __( 'Rating (0–5)', 'tunet' ), min: 0, max: 5, step: 0.5, value: item.rating, onChange: function ( v ) { update( { rating: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Name', 'tunet' ), value: item.name, onChange: function ( v ) { update( { name: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Role · Company', 'tunet' ), value: item.role, onChange: function ( v ) { update( { role: v } ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextareaControl, { label: __( 'Quote', 'tunet' ), value: item.quote, onChange: function ( v ) { update( { quote: v } ); }, __nextHasNoMarginBottom: true } )
		);
	}

	registerBlockType( 'tunet/testimonials', {
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var isGrid = attrs.layout === 'grid';

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						C.PanelBody,
						{ title: __( 'Testimonials', 'tunet' ), initialOpen: true },
						el( C.SelectControl, { label: __( 'Layout', 'tunet' ), value: attrs.layout, options: LAYOUTS, onChange: function ( v ) { setAttributes( { layout: v } ); }, __nextHasNoMarginBottom: true } ),
						isGrid
							? el( C.RangeControl, { label: __( 'Columns', 'tunet' ), min: 1, max: 4, value: attrs.columns, onChange: function ( v ) { setAttributes( { columns: v } ); }, __nextHasNoMarginBottom: true } )
							: el( Fragment, {},
								el( C.RangeControl, { label: __( 'Slides per view', 'tunet' ), min: 1, max: 4, value: attrs.slidesPerView, onChange: function ( v ) { setAttributes( { slidesPerView: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.RangeControl, { label: __( 'Space between (px)', 'tunet' ), min: 0, max: 80, value: attrs.spaceBetween, onChange: function ( v ) { setAttributes( { spaceBetween: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Infinite loop', 'tunet' ), checked: attrs.loop, onChange: function ( v ) { setAttributes( { loop: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Autoplay', 'tunet' ), checked: attrs.autoplay, onChange: function ( v ) { setAttributes( { autoplay: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Pagination', 'tunet' ), checked: attrs.pagination, onChange: function ( v ) { setAttributes( { pagination: v } ); }, __nextHasNoMarginBottom: true } ),
								el( C.ToggleControl, { label: __( 'Navigation arrows', 'tunet' ), checked: attrs.navigation, onChange: function ( v ) { setAttributes( { navigation: v } ); }, __nextHasNoMarginBottom: true } )
							)
					),
					el(
						C.PanelBody,
						{ title: __( 'Items', 'tunet' ), initialOpen: true },
						el( window.tunet.RepeaterControl, {
							items: attrs.items,
							onChange: function ( items ) { setAttributes( { items: items } ); },
							renderItem: renderItem,
							newItem: newTestimonial,
							addLabel: __( 'Add testimonial', 'tunet' ),
							itemLabel: function ( it, i ) { return it.name || ( __( 'Testimonial', 'tunet' ) + ' ' + ( i + 1 ) ); }
						} )
					)
				),
				el(
					'div',
					blockProps,
					( attrs.items && attrs.items.length )
						? el( window.tunet.CarouselPreview, { block: 'tunet/testimonials', attributes: attrs } )
						: el( C.Placeholder, {
							icon: 'format-quote',
							label: __( 'Testimonials', 'tunet' ),
							instructions: __( 'Add testimonials from the “Items” panel in the block sidebar.', 'tunet' )
						} )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
