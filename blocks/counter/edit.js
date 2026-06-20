/* ==========================================================================
 * Tunet Core · Block tunet/counter — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*). Block dinámico: save devuelve null y el render lo
 * hace render.php. El canvas muestra el valor final formateado (sin animar).
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var c = wp.components;

	function format( value, decimals, separator ) {
		var fixed = ( Number( value ) || 0 ).toFixed( decimals );
		if ( ! separator ) {
			return fixed;
		}
		var parts = fixed.split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		return parts.join( '.' );
	}

	function numberField( label, value, onChange, help ) {
		return el( c.TextControl, {
			label: label,
			type: 'number',
			value: value,
			help: help,
			onChange: onChange,
			__nextHasNoMarginBottom: true
		} );
	}

	registerBlockType( 'tunet/counter', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps( { className: 'tunet-counter' } );

			var decimals = a.decimals || 0;

			var preview = el(
				Fragment,
				{},
				a.prefix ? el( 'span', { className: 'tunet-counter__affix tunet-counter__prefix' }, a.prefix ) : null,
				el( 'span', { className: 'tunet-counter__num' }, format( a.end, decimals, !! a.separator ) ),
				a.suffix ? el( 'span', { className: 'tunet-counter__affix tunet-counter__suffix' }, a.suffix ) : null
			);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Counter', 'tunet' ), initialOpen: true },
						numberField( __( 'Start value', 'tunet' ), a.start, function ( v ) {
							set( { start: parseFloat( v ) || 0 } );
						} ),
						numberField( __( 'End value', 'tunet' ), a.end, function ( v ) {
							set( { end: parseFloat( v ) || 0 } );
						} ),
						numberField( __( 'Duration (ms)', 'tunet' ), a.duration, function ( v ) {
							set( { duration: parseInt( v, 10 ) || 0 } );
						}, __( '0 = no animation (shows the final value).', 'tunet' ) ),
						numberField( __( 'Decimals', 'tunet' ), a.decimals, function ( v ) {
							var n = parseInt( v, 10 ) || 0;
							set( { decimals: Math.max( 0, Math.min( 4, n ) ) } );
						} ),
						el( c.ToggleControl, {
							label: __( 'Thousands separator', 'tunet' ),
							checked: !! a.separator,
							onChange: function ( v ) {
								set( { separator: v } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.TextControl, {
							label: __( 'Prefix', 'tunet' ),
							value: a.prefix || '',
							onChange: function ( v ) {
								set( { prefix: v } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( c.TextControl, {
							label: __( 'Suffix', 'tunet' ),
							value: a.suffix || '',
							help: __( 'e.g. +, %, K, M', 'tunet' ),
							onChange: function ( v ) {
								set( { suffix: v } );
							},
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el( 'p', blockProps, preview )
			);
		},

		save: function () {
			// Block dinámico: lo renderiza render.php.
			return null;
		}
	} );
} )( window.wp );
