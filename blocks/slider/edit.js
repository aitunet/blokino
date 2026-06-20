/* ==========================================================================
 * Tunet Core · Block tunet/slider — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*). Cada bloque interno es una slide (InnerBlocks).
 * En el editor se muestran apiladas para editar; el carrusel se monta en el
 * frontend (render.php + Swiper lazy). save devuelve InnerBlocks.Content.
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

	registerBlockType( 'tunet/slider', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps( { className: 'tunet-slider-editor' } );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Slider', 'tunet' ), initialOpen: true },
						el( c.SelectControl, {
							label: __( 'Effect', 'tunet' ),
							value: a.effect,
							options: [
								{ label: __( 'Slide', 'tunet' ), value: 'slide' },
								{ label: 'Fade', value: 'fade' },
								{ label: 'Cards', value: 'cards' },
								{ label: 'Coverflow', value: 'coverflow' }
							],
							onChange: function ( v ) { set( { effect: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Slides per view', 'tunet' ),
							value: a.slidesPerView,
							min: 1,
							max: 5,
							step: 0.5,
							help: __( 'Forced to 1 for Fade/Cards.', 'tunet' ),
							onChange: function ( v ) { set( { slidesPerView: v || 1 } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Space between slides (px)', 'tunet' ),
							value: a.spaceBetween,
							min: 0,
							max: 96,
							step: 2,
							onChange: function ( v ) { set( { spaceBetween: ( v === undefined || v === null ) ? 0 : v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Speed (ms)', 'tunet' ),
							value: a.speed,
							min: 100,
							max: 2000,
							step: 50,
							onChange: function ( v ) { set( { speed: v || 600 } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Infinite loop', 'tunet' ),
							checked: !! a.loop,
							onChange: function ( v ) { set( { loop: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Autoplay', 'tunet' ),
							checked: !! a.autoplay,
							onChange: function ( v ) { set( { autoplay: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						a.autoplay ? el( c.RangeControl, {
							label: __( 'Autoplay interval (ms)', 'tunet' ),
							value: a.autoplayDelay,
							min: 1000,
							max: 10000,
							step: 250,
							onChange: function ( v ) { set( { autoplayDelay: v || 4000 } ); },
							__nextHasNoMarginBottom: true
						} ) : null,
						el( c.ToggleControl, {
							label: __( 'Pagination', 'tunet' ),
							checked: !! a.pagination,
							onChange: function ( v ) { set( { pagination: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Navigation arrows', 'tunet' ),
							checked: !! a.navigation,
							onChange: function ( v ) { set( { navigation: v } ); },
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( InnerBlocks, {
						renderAppender: InnerBlocks.ButtonBlockAppender
					} )
				)
			);
		},

		save: function () {
			// Block dinámico: render.php envuelve cada inner block en una slide.
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
