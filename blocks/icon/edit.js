/* ==========================================================================
 * Tunet Core · Block tunet/icon — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*). Block dinámico: save devuelve null, lo pinta
 * render.php. El set de iconos llega en window.tunetIcons (localize).
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var Fragment = wp.element.Fragment;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var c = wp.components;

	var CATS = [
		{ key: 'general', label: __( 'General', 'tunet-core' ) },
		{ key: 'nav',     label: __( 'Nav / UI', 'tunet-core' ) },
		{ key: 'contact', label: __( 'Contact', 'tunet-core' ) },
		{ key: 'brand',   label: __( 'Brand / Social', 'tunet-core' ) }
	];

	registerBlockType( 'tunet/icon', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var state = useState( '' );
			var query = state[ 0 ];
			var setQuery = state[ 1 ];
			var blockProps = useBlockProps( { className: 'tunet-icon' } );
			var ICONS = window.tunetIcons || {};
			var selMode = ( ( window.tunetIcons || {} )[ a.icon ] || {} ).mode || 'stroke';

			var q = query ? query.toLowerCase() : '';
			var matches = function ( n ) {
				if ( ! q ) { return true; }
				return n.indexOf( q ) !== -1 || ( ICONS[ n ].label || '' ).toLowerCase().indexOf( q ) !== -1;
			};
			var iconButton = function ( n ) {
				return el( 'button', {
					key: n,
					type: 'button',
					className: 'tunet-icon-picker__item' + ( n === a.icon ? ' is-active' : '' ),
					'aria-label': ICONS[ n ].label || n,
					title: ICONS[ n ].label || n,
					onClick: function () { set( { icon: n } ); },
					dangerouslySetInnerHTML: { __html: window.tunetIconSvg( n, { stroke: 2, size: 24 } ) }
				} );
			};
			var allNames = Object.keys( ICONS );
			var pickerChildren;
			if ( q ) {
				var flat = allNames.filter( matches );
				pickerChildren = flat.length
					? [ el( 'div', { className: 'tunet-icon-picker__grid', key: 'flat' }, flat.map( iconButton ) ) ]
					: [ el( 'p', { className: 'tunet-icon-picker__empty', key: 'empty' }, __( 'No icons match.', 'tunet-core' ) ) ];
			} else {
				pickerChildren = CATS.map( function ( cat ) {
					var inCat = allNames.filter( function ( n ) { return ( ICONS[ n ].category || 'general' ) === cat.key; } );
					if ( ! inCat.length ) { return null; }
					return el( Fragment, { key: cat.key },
						el( 'p', { className: 'tunet-icon-picker__cat' }, cat.label ),
						el( 'div', { className: 'tunet-icon-picker__grid' }, inCat.map( iconButton ) )
					);
				} ).filter( Boolean );
			}
			var picker = el( 'div', { className: 'tunet-icon-picker' }, pickerChildren );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Icon', 'tunet-core' ), initialOpen: true },
						el( c.SearchControl, {
							value: query,
							onChange: setQuery,
							label: __( 'Search icons', 'tunet-core' ),
							placeholder: __( 'Search…', 'tunet-core' ),
							__nextHasNoMarginBottom: true
						} ),
						picker
					),
					el(
						c.PanelBody,
						{ title: __( 'Size & label', 'tunet-core' ), initialOpen: false },
						el( c.RangeControl, {
							label: __( 'Size (px)', 'tunet-core' ),
							value: a.size || 24,
							min: 12,
							max: 96,
							step: 1,
							onChange: function ( v ) {
								set( { size: v || 24 } );
							},
							__nextHasNoMarginBottom: true
						} ),
						'fill' !== selMode ? el( c.RangeControl, {
							label: __( 'Stroke width', 'tunet-core' ),
							value: a.strokeWidth || 2,
							min: 1,
							max: 3,
							step: 0.25,
							onChange: function ( v ) {
								set( { strokeWidth: v || 2 } );
							},
							__nextHasNoMarginBottom: true
						} ) : null,
						el( c.TextControl, {
							label: __( 'Accessibility label', 'tunet-core' ),
							help: __( 'Leave empty if the icon is purely decorative.', 'tunet-core' ),
							value: a.label || '',
							onChange: function ( v ) {
								set( { label: v } );
							},
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el( 'span', Object.assign( {}, blockProps, {
					dangerouslySetInnerHTML: { __html: window.tunetIconSvg( a.icon, { stroke: a.strokeWidth, size: a.size } ) }
				} ) )
			);
		},

		save: function () {
			return null;
		}
	} );
} )( window.wp );
