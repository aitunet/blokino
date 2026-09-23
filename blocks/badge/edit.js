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
				el( 'div', useBlockProps( { className: 'blokino-badge-editor' } ),
					el( 'span', { style: { fontFamily: 'monospace', fontSize: '11px', opacity: 0.7 } }, '◍ ' + ( a.text || 'Badge' ) )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
