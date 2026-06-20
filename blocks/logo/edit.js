/* ==========================================================================
 * Tunet Core · Block tunet/logo — editor
 * --------------------------------------------------------------------------
 * Sin JSX. Block dinámico (save → null). El preview usa las URLs localizadas
 * en window.tunetLogos; si no hay logo configurado, muestra un aviso.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var useBlockProps = be.useBlockProps;
	var InspectorControls = be.InspectorControls;
	var c = wp.components;

	registerBlockType( 'tunet/logo', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var logos = window.tunetLogos || {};
			var url = a.variant === 'alt' ? ( logos.alt || logos.main ) : logos.main;

			var blockProps = useBlockProps( {
				className: 'tunet-logo',
				style: { maxWidth: ( a.maxWidth || 160 ) + 'px' }
			} );

			var preview;
			if ( url ) {
				preview = el( 'img', { className: 'tunet-logo__img', src: url, alt: '' } );
			} else {
				preview = el(
					'span',
					{ style: { display: 'block', padding: '14px 18px', border: '1px dashed #c3c4c7', borderRadius: '6px', color: '#787c82', fontSize: '13px', whiteSpace: 'nowrap' } },
					__( 'Upload a logo in Tunet Core → Logos', 'tunet' )
				);
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Logo', 'tunet' ), initialOpen: true },
						el( c.SelectControl, {
							label: __( 'Variant', 'tunet' ),
							value: a.variant,
							options: [
								{ label: __( 'Main', 'tunet' ), value: 'main' },
								{ label: __( 'Alternative', 'tunet' ), value: 'alt' }
							],
							onChange: function ( v ) { set( { variant: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Max width (px)', 'tunet' ),
							value: a.maxWidth,
							min: 40,
							max: 480,
							step: 4,
							onChange: function ( v ) { set( { maxWidth: v || 160 } ); },
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el( 'div', blockProps, preview )
			);
		},

		save: function () {
			return null;
		}
	} );
} )( window.wp );
