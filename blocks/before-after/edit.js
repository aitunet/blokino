/* ==========================================================================
 * Tunet Core · Block tunet/before-after — editor
 * --------------------------------------------------------------------------
 * Sin JSX (globales wp.*). Block dinámico (save → null; render.php pinta).
 * Selección de dos imágenes con MediaUpload; el canvas muestra una vista
 * previa estática en la posición inicial.
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
	var MediaUpload = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;
	var c = wp.components;

	// Complete msgids (no sentence assembly from a translated 'before'/'after' word:
	// gender agreement can't be resolved when fragments are translated in isolation).
	function pickerLabel( kind, hasUrl ) {
		if ( 'before' === kind ) {
			return hasUrl ? __( 'Change before image', 'tunet' ) : __( 'Choose before image', 'tunet' );
		}
		return hasUrl ? __( 'Change after image', 'tunet' ) : __( 'Choose after image', 'tunet' );
	}

	function picker( kind, valueId, hasUrl, onSelect ) {
		return el(
			MediaUploadCheck,
			{},
			el( MediaUpload, {
				allowedTypes: [ 'image' ],
				value: valueId,
				onSelect: onSelect,
				render: function ( o ) {
					return el(
						c.Button,
						{ variant: 'secondary', onClick: o.open, style: { marginBottom: '8px' } },
						pickerLabel( kind, hasUrl )
					);
				}
			} )
		);
	}

	registerBlockType( 'tunet/before-after', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'tunet-ba',
				style: { '--tnt-ba-pos': ( a.startPosition || 50 ) + '%' }
			} );

			var hasBoth = a.beforeUrl && a.afterUrl;

			var controls = el(
				InspectorControls,
				{},
				el(
					c.PanelBody,
					{ title: __( 'Images', 'tunet' ), initialOpen: true },
					picker( 'before', a.beforeId, !! a.beforeUrl, function ( m ) {
						set( { beforeId: m.id, beforeUrl: m.url, beforeAlt: m.alt || '', width: m.width, height: m.height } );
					} ),
					el( 'br' ),
					picker( 'after', a.afterId, !! a.afterUrl, function ( m ) {
						set( { afterId: m.id, afterUrl: m.url, afterAlt: m.alt || '' } );
					} )
				),
				el(
					c.PanelBody,
					{ title: __( 'Settings', 'tunet' ), initialOpen: true },
					el( c.RangeControl, {
						label: __( 'Start position (%)', 'tunet' ),
						value: a.startPosition || 50,
						min: 0,
						max: 100,
						step: 1,
						onChange: function ( v ) {
							set( { startPosition: ( v === undefined || v === null ) ? 50 : v } );
						},
						__nextHasNoMarginBottom: true
					} ),
					el( c.TextControl, {
						label: __( 'Before label', 'tunet' ),
						value: a.beforeLabel || '',
						onChange: function ( v ) { set( { beforeLabel: v } ); },
						__nextHasNoMarginBottom: true
					} ),
					el( c.TextControl, {
						label: __( 'After label', 'tunet' ),
						value: a.afterLabel || '',
						onChange: function ( v ) { set( { afterLabel: v } ); },
						__nextHasNoMarginBottom: true
					} )
				)
			);

			var canvas;
			if ( hasBoth ) {
				canvas = el(
					'div',
					blockProps,
					el( 'img', { className: 'tunet-ba__img tunet-ba__before', src: a.beforeUrl, alt: a.beforeAlt || '', draggable: false } ),
					el(
						'div',
						{ className: 'tunet-ba__after' },
						el( 'img', { className: 'tunet-ba__img', src: a.afterUrl, alt: a.afterAlt || '', draggable: false } ),
						a.afterLabel ? el( 'span', { className: 'tunet-ba__label tunet-ba__label--after' }, a.afterLabel ) : null
					),
					a.beforeLabel ? el( 'span', { className: 'tunet-ba__label tunet-ba__label--before' }, a.beforeLabel ) : null,
					el( 'div', { className: 'tunet-ba__handle' }, el( 'span', { className: 'tunet-ba__grip' } ) )
				);
			} else {
				canvas = el(
					'div',
					useBlockProps(),
					el(
						c.Placeholder,
						{
							icon: 'image-flip-horizontal',
							label: __( 'Tunet Before / After', 'tunet' ),
							instructions: __( 'Choose the before and after images to compare.', 'tunet' )
						},
						picker( 'before', a.beforeId, !! a.beforeUrl, function ( m ) {
							set( { beforeId: m.id, beforeUrl: m.url, beforeAlt: m.alt || '', width: m.width, height: m.height } );
						} ),
						picker( 'after', a.afterId, !! a.afterUrl, function ( m ) {
							set( { afterId: m.id, afterUrl: m.url, afterAlt: m.alt || '' } );
						} )
					)
				);
			}

			return el( Fragment, {}, controls, canvas );
		},

		save: function () {
			// Block dinámico: lo renderiza render.php.
			return null;
		}
	} );
} )( window.wp );
