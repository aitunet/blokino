/* ==========================================================================
 * Tunet Core · Block tunet/testimonials — editor
 * --------------------------------------------------------------------------
 * Wrapper standalone. InnerBlocks bloqueado a tunet/testimonial + botón
 * "+ Add Testimonial". layout carousel|grid (front); en el editor las tarjetas
 * se muestran apiladas. Sin JSX. save = InnerBlocks.Content (render.php arma
 * el carrusel o el grid).
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
	var c = wp.components;

	var TEMPLATE = [ [ 'tunet/testimonial' ], [ 'tunet/testimonial' ], [ 'tunet/testimonial' ] ];

	registerBlockType( 'tunet/testimonials', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps( { className: 'tunet-testimonials-editor' } );
			var isGrid = 'grid' === a.layout;

			var controls = [
				el( c.SelectControl, {
					key: 'layout',
					label: __( 'Layout', 'tunet' ),
					value: a.layout,
					options: [
						{ label: __( 'Carousel', 'tunet' ), value: 'carousel' },
						{ label: __( 'Grid', 'tunet' ), value: 'grid' }
					],
					onChange: function ( v ) { set( { layout: 'grid' === v ? 'grid' : 'carousel' } ); },
					__nextHasNoMarginBottom: true
				} )
			];

			if ( isGrid ) {
				controls.push( el( c.RangeControl, {
					key: 'columns',
					label: __( 'Columns', 'tunet' ),
					value: a.columns,
					min: 1,
					max: 4,
					step: 1,
					onChange: function ( v ) { set( { columns: v || 3 } ); },
					__nextHasNoMarginBottom: true
				} ) );
			} else {
				controls.push(
					el( c.RangeControl, {
						key: 'spv',
						label: __( 'Slides per view', 'tunet' ),
						value: a.slidesPerView,
						min: 1,
						max: 4,
						step: 0.5,
						onChange: function ( v ) { set( { slidesPerView: v || 1 } ); },
						__nextHasNoMarginBottom: true
					} ),
					el( c.RangeControl, {
						key: 'space',
						label: __( 'Space between (px)', 'tunet' ),
						value: a.spaceBetween,
						min: 0,
						max: 96,
						step: 2,
						onChange: function ( v ) { set( { spaceBetween: ( v === undefined || v === null ) ? 0 : v } ); },
						__nextHasNoMarginBottom: true
					} ),
					el( c.ToggleControl, {
						key: 'loop',
						label: __( 'Infinite loop', 'tunet' ),
						checked: !! a.loop,
						onChange: function ( v ) { set( { loop: v } ); },
						__nextHasNoMarginBottom: true
					} ),
					el( c.ToggleControl, {
						key: 'autoplay',
						label: __( 'Autoplay', 'tunet' ),
						checked: !! a.autoplay,
						onChange: function ( v ) { set( { autoplay: v } ); },
						__nextHasNoMarginBottom: true
					} ),
					el( c.ToggleControl, {
						key: 'pag',
						label: __( 'Pagination', 'tunet' ),
						checked: !! a.pagination,
						onChange: function ( v ) { set( { pagination: v } ); },
						__nextHasNoMarginBottom: true
					} ),
					el( c.ToggleControl, {
						key: 'nav',
						label: __( 'Navigation arrows', 'tunet' ),
						checked: !! a.navigation,
						onChange: function ( v ) { set( { navigation: v } ); },
						__nextHasNoMarginBottom: true
					} )
				);
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el( c.PanelBody, { title: __( 'Testimonials', 'tunet' ), initialOpen: true }, controls )
				),
				el(
					'div',
					blockProps,
					el( InnerBlocks, {
						allowedBlocks: [ 'tunet/testimonial' ],
						template: TEMPLATE,
						renderAppender: function () {
							return el(
								'div',
								{ className: 'tunet-testimonials__appender' },
								el(
									c.Button,
									{
										className: 'tunet-testimonials__add',
										variant: 'secondary',
										icon: 'plus',
										onClick: function () {
											var item = wp.blocks.createBlock( 'tunet/testimonial' );
											wp.data.dispatch( 'core/block-editor' ).insertBlock( item, undefined, props.clientId );
										}
									},
									__( 'Add Testimonial', 'tunet' )
								)
							);
						}
					} )
				)
			);
		},

		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
