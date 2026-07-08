/* ==========================================================================
 * Tunet Core · Block tunet/testimonial — editor
 * --------------------------------------------------------------------------
 * Child tipado de tunet/testimonials. Edición inline: avatar (MediaUpload),
 * quote/name/role (RichText), rating (RangeControl 0–5 paso 0.5) con preview de
 * estrellas vía window.tunetIconSvg. Sin JSX. save=null (dinámico: render.php).
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var useBlockProps = be.useBlockProps;
	var RichText = be.RichText;
	var InspectorControls = be.InspectorControls;
	var MediaUpload = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;
	var c = wp.components;

	function stars( rating ) {
		var svg = ( window.tunetIconSvg ? window.tunetIconSvg( 'star', { size: 16 } ) : '★' );
		var full = Math.round( ( rating / 5 ) * 100 );
		return el(
			'span',
			{ className: 'tunet-rating', style: { position: 'relative', display: 'inline-flex' } },
			el( 'span', { className: 'tunet-rating__layer tunet-rating__layer--empty', dangerouslySetInnerHTML: { __html: svg + svg + svg + svg + svg } } ),
			el( 'span', {
				className: 'tunet-rating__layer tunet-rating__layer--full',
				style: { position: 'absolute', top: 0, left: 0, overflow: 'hidden', whiteSpace: 'nowrap', width: full + '%' },
				dangerouslySetInnerHTML: { __html: svg + svg + svg + svg + svg }
			} )
		);
	}

	registerBlockType( 'tunet/testimonial', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps( { className: 'tunet-testimonial' } );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						c.PanelBody,
						{ title: __( 'Testimonial', 'tunet' ), initialOpen: true },
						el( c.RangeControl, {
							label: __( 'Rating (0–5)', 'tunet' ),
							value: a.rating,
							min: 0,
							max: 5,
							step: 0.5,
							help: __( '0 = no stars.', 'tunet' ),
							onChange: function ( v ) { set( { rating: ( v === undefined || v === null ) ? 0 : v } ); },
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el(
					'div',
					blockProps,
					a.rating > 0 ? stars( a.rating ) : null,
					el( MediaUploadCheck, {},
						el( MediaUpload, {
							onSelect: function ( media ) { set( { avatarId: media.id, avatarUrl: media.url } ); },
							allowedTypes: [ 'image' ],
							value: a.avatarId,
							render: function ( o ) {
								return el(
									c.Button,
									{ variant: a.avatarUrl ? 'tertiary' : 'secondary', onClick: o.open, className: 'tunet-testimonial__avatar-pick' },
									a.avatarUrl
										? el( 'img', { src: a.avatarUrl, alt: '', style: { width: 48, height: 48, borderRadius: '50%', objectFit: 'cover' } } )
										: __( 'Set avatar', 'tunet' )
								);
							}
						} )
					),
					el( RichText, {
						tagName: 'blockquote',
						className: 'tunet-testimonial__quote',
						value: a.quote,
						allowedFormats: [ 'core/bold', 'core/italic' ],
						placeholder: __( 'Testimonial quote…', 'tunet' ),
						onChange: function ( v ) { set( { quote: v } ); }
					} ),
					el( RichText, {
						tagName: 'span',
						className: 'tunet-testimonial__name',
						value: a.name,
						allowedFormats: [],
						placeholder: __( 'Name', 'tunet' ),
						onChange: function ( v ) { set( { name: v } ); }
					} ),
					el( RichText, {
						tagName: 'span',
						className: 'tunet-testimonial__role',
						value: a.role,
						allowedFormats: [],
						placeholder: __( 'Role · Company', 'tunet' ),
						onChange: function ( v ) { set( { role: v } ); }
					} )
				)
			);
		},

		save: function () {
			return null; // dinámico
		}
	} );
} )( window.wp );
