( function ( wp ) {
	'use strict';
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var c = wp.components;

	wp.blocks.registerBlockType( 'blokino/badge', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			return el( wp.element.Fragment, {},
				el( InspectorControls, {},
					el( c.PanelBody, { title: __( 'Badge', 'blokino' ), initialOpen: true },
						el( c.TextControl, { label: __( 'Caption (repeat with · )', 'blokino' ), value: a.text, onChange: function ( v ) { set( { text: v } ); }, __nextHasNoMarginBottom: true } ),
						el( c.TextControl, { label: __( 'Link URL', 'blokino' ), value: a.url, onChange: function ( v ) { set( { url: v } ); }, __nextHasNoMarginBottom: true } ),
						el( c.RangeControl, { label: __( 'Seconds per turn', 'blokino' ), value: a.speed, min: 4, max: 60, onChange: function ( v ) { set( { speed: v || 18 } ); }, __nextHasNoMarginBottom: true } ),
						el( c.ToggleControl, { label: __( 'Reverse direction', 'blokino' ), checked: !! a.reverse, onChange: function ( v ) { set( { reverse: !! v } ); }, __nextHasNoMarginBottom: true } )
					)
				),
				// Mismo marcado que render.php (anillo SVG con el texto + flecha): el editor muestra el badge como el front,
				// con su style.css; antes era solo el texto en una línea.
				el( 'div', useBlockProps( { className: 'blokino-badge blokino-badge-editor' + ( a.reverse ? ' is-reverse' : '' ), style: { '--tnt-badge-speed': ( a.speed || 18 ) + 's' } } ),
					el( 'span', { className: 'blokino-badge__ring', 'aria-hidden': 'true' },
						el( 'svg', { viewBox: '0 0 100 100', width: '100%', height: '100%' },
							el( 'defs', {}, el( 'path', { id: 'tnt-badge-ed-' + props.clientId, d: 'M 50,50 m -37,0 a 37,37 0 1,1 74,0 a 37,37 0 1,1 -74,0' } ) ),
							el( 'text', {}, el( 'textPath', { href: '#tnt-badge-ed-' + props.clientId }, a.text || '' ) )
						)
					),
					el( 'span', { className: 'blokino-badge__arrow', 'aria-hidden': 'true' },
						el( 'svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '1.6' },
							el( 'path', { d: 'M7 17 17 7M9 7h8v8', strokeLinecap: 'round', strokeLinejoin: 'round' } )
						)
					)
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
