/* ==========================================================================
 * Tunet Core · Block tunet/marquee — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*), coherente con las extensiones tf*. El contenido son
 * bloques internos (InnerBlocks): el usuario mete logos, texto o métricas.
 * Es un block dinámico (render.php); save devuelve InnerBlocks.Content.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var blockEditor = wp.blockEditor;
	var useBlockProps = blockEditor.useBlockProps;
	var InnerBlocks = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var c = wp.components;

	registerBlockType( 'tunet/marquee', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			var blockProps = useBlockProps( { className: 'tunet-marquee-editor' } );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Marquee', 'tunet' ), initialOpen: true },
						el( c.RangeControl, {
							label: __( 'Speed (s per loop)', 'tunet' ),
							value: a.speed,
							min: 5,
							max: 120,
							step: 1,
							onChange: function ( v ) {
								set( { speed: v || 30 } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.SelectControl, {
							label: __( 'Direction', 'tunet' ),
							value: a.direction,
							options: [
								{ label: __( 'Left', 'tunet' ), value: 'left' },
								{ label: __( 'Right', 'tunet' ), value: 'right' }
							],
							onChange: function ( v ) {
								set( { direction: v } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Spacing (rem)', 'tunet' ),
							value: a.gap,
							min: 0,
							max: 10,
							step: 0.5,
							onChange: function ( v ) {
								set( { gap: ( v === undefined || v === null ) ? 3 : v } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Pause on hover', 'tunet' ),
							checked: !! a.pauseOnHover,
							onChange: function ( v ) {
								set( { pauseOnHover: v } );
							},
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( InnerBlocks, {
						orientation: 'horizontal',
						renderAppender: InnerBlocks.ButtonBlockAppender
					} )
				)
			);
		},

		save: function () {
			// Block dinámico: el contenido interno se guarda y lo envuelve render.php.
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
