/* ==========================================================================
 * BloqUIX · Block bloquix/brand — editor
 * --------------------------------------------------------------------------
 * Sin JSX. Block dinámico (save → null). El preview usa wp.serverSideRender:
 * renderiza el render.php real, así el editor muestra el logo/título verdadero
 * sin duplicar la lógica de resolución en JS (DRY, como el topic #2).
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
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'bloquix/brand', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Brand', 'bloquix' ), initialOpen: true },
						el( c.SelectControl, {
							label: __( 'Variant', 'bloquix' ),
							value: a.variant,
							options: [
								{ label: __( 'Automatic (follows the palette)', 'bloquix' ), value: 'auto' },
								{ label: __( 'Main', 'bloquix' ), value: 'main' },
								{ label: __( 'Alternative', 'bloquix' ), value: 'alt' }
							],
							onChange: function ( v ) { set( { variant: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						// El comprador sube el logo claro y espera que salga solo en la paleta
						// oscura: hasta ahora no pasaba y parecia un fallo del theme.
						el(
							'p',
							{ className: 'bloquix-editor-note' },
							__( 'Automatic shows the alternative logo on dark palettes and style variations, and the main one otherwise. Set both in BloqUIX → Logos.', 'bloquix' )
						),
						el( c.RangeControl, {
							label: __( 'Max width (px)', 'bloquix' ),
							value: a.maxWidth,
							min: 40,
							max: 320,
							step: 4,
							onChange: function ( v ) { set( { maxWidth: v || 140 } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Link to Home', 'bloquix' ),
							checked: !! a.linkToHome,
							onChange: function ( v ) { set( { linkToHome: !! v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el(
							'p',
							{ style: { marginTop: '4px' } },
							el(
								c.ExternalLink,
								{ href: 'admin.php?page=bloquix-settings' },
								__( 'Manage logo in BloqUIX → Logos', 'bloquix' )
							)
						)
					)
				),
				el(
					'div',
					blockProps,
					// The preview renders render.php via ServerSideRender, which emits a
					// live <a href="/"> when linkToHome is on. pointer-events:none keeps
					// the canvas preview inert so a click selects the block instead of
					// navigating; the front-end anchor is unaffected.
					el(
						'div',
						{ className: 'bloquix-brand__ssr', style: { pointerEvents: 'none' } },
						el( ServerSideRender, {
							block: 'bloquix/brand',
							attributes: a
						} )
					)
				)
			);
		},

		save: function () {
			return null;
		}
	} );
} )( window.wp );
