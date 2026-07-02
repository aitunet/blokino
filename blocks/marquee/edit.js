/* ==========================================================================
 * Tunet Core · Block tunet/marquee — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*). El contenido son bloques internos (InnerBlocks).
 * Block dinámico (render.php); save devuelve InnerBlocks.Content. El separador
 * (forma + icono del set Tunet + color) se configura en el Inspector.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var Fragment = wp.element.Fragment;
	var blockEditor = wp.blockEditor;
	var useBlockProps = blockEditor.useBlockProps;
	var InnerBlocks = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var useSettings = blockEditor.useSettings;
	var useSetting = blockEditor.useSetting || function () { return undefined; };
	var c = wp.components;

	registerBlockType( 'tunet/marquee', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			var qState = useState( '' );
			var query = qState[ 0 ];
			var setQuery = qState[ 1 ];

			var wrapStyle = {};
			if ( a.gap !== undefined && a.gap !== null ) {
				wrapStyle[ '--tnt-marquee-gap' ] = a.gap + 'rem';
			}
			if ( a.separatorColor && 'none' !== a.separator ) {
				wrapStyle[ '--tnt-marquee-sep-color' ] = a.separatorColor;
			}
			var blockProps = useBlockProps( {
				className: 'tunet-marquee-editor is-sep-' + ( a.separator || 'none' ),
				style: wrapStyle
			} );

			var palette = ( useSettings ? useSettings( 'color.palette' )[ 0 ] : useSetting( 'color.palette' ) ) || [];

			// Picker de icono (solo cuando separator === 'icon').
			var iconPicker = null;
			if ( 'icon' === a.separator ) {
				var ICONS = window.tunetIcons || {};
				var q = query ? query.toLowerCase() : '';
				var matches = function ( n ) {
					if ( ! q ) { return true; }
					return n.indexOf( q ) !== -1 || ( ICONS[ n ].label || '' ).toLowerCase().indexOf( q ) !== -1;
				};
				var sepBtn = function ( n ) {
					return el( 'button', {
						key: n, type: 'button',
						className: n === a.separatorIcon ? 'is-active' : '',
						'aria-label': ICONS[ n ].label || n,
						title: ICONS[ n ].label || n,
						onClick: function () { set( { separatorIcon: n } ); },
						dangerouslySetInnerHTML: { __html: window.tunetIconSvg( n, { size: 24 } ) }
					} );
				};
				var allNames = Object.keys( ICONS );
				var gridChildren;
				if ( q ) {
					var flat = allNames.filter( matches );
					gridChildren = flat.length
						? [ el( 'div', { className: 'tunet-marquee-editor__sep-grid', key: 'flat' }, flat.map( sepBtn ) ) ]
						: [ el( 'p', { key: 'empty' }, __( 'No icons match.', 'tunet' ) ) ];
				} else {
					gridChildren = [
						{ key: 'general', label: __( 'General', 'tunet' ) },
						{ key: 'nav', label: __( 'Nav / UI', 'tunet' ) },
						{ key: 'contact', label: __( 'Contact', 'tunet' ) },
						{ key: 'brand', label: __( 'Brand / Social', 'tunet' ) }
					].map( function ( cat ) {
						var inCat = allNames.filter( function ( n ) { return ( ICONS[ n ].category || 'general' ) === cat.key; } );
						if ( ! inCat.length ) { return null; }
						return el( Fragment, { key: cat.key },
							el( 'p', { className: 'tunet-marquee-editor__sep-cat' }, cat.label ),
							el( 'div', { className: 'tunet-marquee-editor__sep-grid' }, inCat.map( sepBtn ) )
						);
					} ).filter( Boolean );
				}
				iconPicker = el( Fragment, {},
					el( c.SearchControl, {
						value: query, onChange: setQuery,
						label: __( 'Search icons', 'tunet' ), placeholder: __( 'Search…', 'tunet' ),
						__nextHasNoMarginBottom: true
					} ),
					el( 'div', { className: 'tunet-marquee-editor__sep-picker' }, gridChildren )
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
						{ title: __( 'Marquee', 'tunet' ), initialOpen: true },
						el( c.RangeControl, {
							label: __( 'Speed (s per loop)', 'tunet' ),
							value: a.speed,
							min: 5,
							max: 120,
							step: 1,
							onChange: function ( v ) { set( { speed: v || 30 } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.SelectControl, {
							label: __( 'Direction', 'tunet' ),
							value: a.direction,
							options: [
								{ label: __( 'Left', 'tunet' ), value: 'left' },
								{ label: __( 'Right', 'tunet' ), value: 'right' }
							],
							onChange: function ( v ) { set( { direction: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.RangeControl, {
							label: __( 'Spacing (rem)', 'tunet' ),
							value: a.gap,
							min: 0,
							max: 10,
							step: 0.5,
							onChange: function ( v ) { set( { gap: ( v === undefined || v === null ) ? 3 : v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Pause on hover', 'tunet' ),
							checked: !! a.pauseOnHover,
							onChange: function ( v ) { set( { pauseOnHover: v } ); },
							__nextHasNoMarginBottom: true
						} )
					),
					el(
						c.PanelBody,
						{ title: __( 'Separator', 'tunet' ), initialOpen: false },
						el( c.SelectControl, {
							label: __( 'Shape', 'tunet' ),
							value: a.separator,
							options: [
								{ label: __( 'None', 'tunet' ), value: 'none' },
								{ label: __( 'Dot', 'tunet' ), value: 'dot' },
								{ label: __( 'Dash', 'tunet' ), value: 'dash' },
								{ label: __( 'Slash', 'tunet' ), value: 'slash' },
								{ label: __( 'Pipe', 'tunet' ), value: 'pipe' },
								{ label: __( 'Icon', 'tunet' ), value: 'icon' }
							],
							onChange: function ( v ) { set( { separator: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						iconPicker,
						'none' !== a.separator ? el(
							'div',
							{ style: { marginBlockStart: '12px' } },
							el( 'p', { style: { margin: '0 0 8px' } }, __( 'Separator color', 'tunet' ) ),
							el( c.ColorPalette, {
								colors: palette,
								value: a.separatorColor,
								onChange: function ( v ) { set( { separatorColor: v || '' } ); },
								clearable: true,
								__experimentalIsRenderedInSidebar: true
							} )
						) : null
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
