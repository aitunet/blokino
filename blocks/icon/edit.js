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

	registerBlockType( 'tunet/icon', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var state = useState( '' );
			var query = state[ 0 ];
			var setQuery = state[ 1 ];
			var blockProps = useBlockProps( { className: 'tunet-icon' } );
			var ICONS = window.tunetIcons || {};

			var names = Object.keys( ICONS ).filter( function ( n ) {
				if ( ! query ) {
					return true;
				}
				var q = query.toLowerCase();
				return n.indexOf( q ) !== -1 || ( ICONS[ n ].label || '' ).toLowerCase().indexOf( q ) !== -1;
			} );

			var picker = el(
				'div',
				{ className: 'tunet-icon-picker' },
				names.length
					? names.map( function ( n ) {
						return el( 'button', {
							key: n,
							type: 'button',
							className: 'tunet-icon-picker__item' + ( n === a.icon ? ' is-active' : '' ),
							'aria-label': ICONS[ n ].label || n,
							title: ICONS[ n ].label || n,
							onClick: function () {
								set( { icon: n } );
							},
							dangerouslySetInnerHTML: { __html: window.tunetIconSvg( n, { stroke: 2, size: 24 } ) }
						} );
					} )
					: el( 'p', { className: 'tunet-icon-picker__empty' }, __( 'No icons match.', 'tunet' ) )
			);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Icon', 'tunet' ), initialOpen: true },
						el( c.SearchControl, {
							value: query,
							onChange: setQuery,
							label: __( 'Search icons', 'tunet' ),
							placeholder: __( 'Search…', 'tunet' ),
							__nextHasNoMarginBottom: true
						} ),
						picker
					),
					el(
						c.PanelBody,
						{ title: __( 'Size & label', 'tunet' ), initialOpen: false },
						el( c.RangeControl, {
							label: __( 'Size (px)', 'tunet' ),
							value: a.size || 24,
							min: 12,
							max: 96,
							step: 1,
							onChange: function ( v ) {
								set( { size: v || 24 } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Stroke width', 'tunet' ),
							value: a.strokeWidth || 2,
							min: 1,
							max: 3,
							step: 0.25,
							onChange: function ( v ) {
								set( { strokeWidth: v || 2 } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.TextControl, {
							label: __( 'Accessibility label', 'tunet' ),
							help: __( 'Leave empty if the icon is purely decorative.', 'tunet' ),
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
