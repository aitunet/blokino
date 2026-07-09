/* ==========================================================================
 * Tunet Core · Block tunet/content-slider — editor
 * Repeater en el sidebar (RepeaterControl) + preview ServerSideRender.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var C = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var InspectorControls = be.InspectorControls;
	var useBlockProps = be.useBlockProps;
	var MediaUpload = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;

	var ALIGN_FIELD = [
		{ label: __( 'Inherit', 'tunet' ), value: 'inherit' },
		{ label: __( 'Left', 'tunet' ), value: 'left' },
		{ label: __( 'Center', 'tunet' ), value: 'center' },
		{ label: __( 'Right', 'tunet' ), value: 'right' }
	];
	var ALIGN_CONTENT = [
		{ label: __( 'Left', 'tunet' ), value: 'left' },
		{ label: __( 'Center', 'tunet' ), value: 'center' },
		{ label: __( 'Right', 'tunet' ), value: 'right' }
	];
	var CTA_STYLES = [
		{ label: __( 'Filled', 'tunet' ), value: 'filled' },
		{ label: __( 'Outline', 'tunet' ), value: 'outline' },
		{ label: __( 'Text', 'tunet' ), value: 'text' }
	];

	function newSlide() {
		return {
			imageId: 0, imageUrl: '',
			title: '', titleAlign: 'inherit',
			subtitle: '', subtitleAlign: 'inherit', subtitleFirst: false,
			description: '', descAlign: 'inherit',
			contentAlign: 'left',
			ctas: [ { text: '', url: '', style: 'filled', newTab: false }, { text: '', url: '', style: 'outline', newTab: false } ]
		};
	}

	function ctaField( item, ctaIndex, update ) {
		var cta = ( item.ctas && item.ctas[ ctaIndex ] ) || { text: '', url: '', style: ctaIndex === 0 ? 'filled' : 'outline', newTab: false };
		function setCta( patch ) {
			var ctas = ( item.ctas || [] ).slice();
			while ( ctas.length <= ctaIndex ) { ctas.push( { text: '', url: '', style: 'filled', newTab: false } ); }
			ctas[ ctaIndex ] = Object.assign( {}, cta, patch );
			update( { ctas: ctas } );
		}
		return el(
			C.BaseControl,
			{ key: 'cta' + ctaIndex, label: __( 'CTA', 'tunet' ) + ' ' + ( ctaIndex + 1 ) },
			el( C.TextControl, { label: __( 'Text', 'tunet' ), value: cta.text, onChange: function ( v ) { setCta( { text: v } ); } } ),
			el( C.TextControl, { label: __( 'Link (URL)', 'tunet' ), value: cta.url, onChange: function ( v ) { setCta( { url: v } ); } } ),
			el( C.SelectControl, { label: __( 'Style', 'tunet' ), value: cta.style, options: CTA_STYLES, onChange: function ( v ) { setCta( { style: v } ); } } ),
			el( C.ToggleControl, { label: __( 'Open in new tab', 'tunet' ), checked: !! cta.newTab, onChange: function ( v ) { setCta( { newTab: v } ); } } )
		);
	}

	function renderItem( item, index, update ) {
		return el(
			Fragment,
			{},
			el( MediaUploadCheck, {},
				el( MediaUpload, {
					allowedTypes: [ 'image' ],
					value: item.imageId,
					onSelect: function ( media ) { update( { imageId: media.id, imageUrl: media.url } ); },
					render: function ( o ) {
						return el( C.Button, { variant: 'secondary', onClick: o.open }, item.imageId ? __( 'Replace image', 'tunet' ) : __( 'Set image', 'tunet' ) );
					}
				} )
			),
			el( C.TextControl, { label: __( 'Title', 'tunet' ), value: item.title, onChange: function ( v ) { update( { title: v } ); } } ),
			el( C.SelectControl, { label: __( 'Title alignment', 'tunet' ), value: item.titleAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { titleAlign: v } ); } } ),
			el( C.ToggleControl, { label: __( 'Subtitle before title', 'tunet' ), checked: !! item.subtitleFirst, onChange: function ( v ) { update( { subtitleFirst: v } ); } } ),
			el( C.TextControl, { label: __( 'Subtitle', 'tunet' ), value: item.subtitle, onChange: function ( v ) { update( { subtitle: v } ); } } ),
			el( C.SelectControl, { label: __( 'Subtitle alignment', 'tunet' ), value: item.subtitleAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { subtitleAlign: v } ); } } ),
			el( C.TextareaControl, { label: __( 'Description', 'tunet' ), value: item.description, onChange: function ( v ) { update( { description: v } ); } } ),
			el( C.SelectControl, { label: __( 'Description alignment', 'tunet' ), value: item.descAlign, options: ALIGN_FIELD, onChange: function ( v ) { update( { descAlign: v } ); } } ),
			el( C.SelectControl, { label: __( 'Content alignment (slide)', 'tunet' ), value: item.contentAlign, options: ALIGN_CONTENT, onChange: function ( v ) { update( { contentAlign: v } ); } } ),
			ctaField( item, 0, update ),
			ctaField( item, 1, update )
		);
	}

	registerBlockType( 'tunet/content-slider', {
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						C.PanelBody,
						{ title: __( 'Slides', 'tunet' ), initialOpen: true },
						el( window.tunet.RepeaterControl, {
							items: attrs.items,
							onChange: function ( items ) { setAttributes( { items: items } ); },
							renderItem: renderItem,
							newItem: newSlide,
							addLabel: __( 'Add slide', 'tunet' ),
							itemLabel: function ( it, i ) { return it.title || ( __( 'Slide', 'tunet' ) + ' ' + ( i + 1 ) ); }
						} )
					),
					el(
						C.PanelBody,
						{ title: __( 'Carousel', 'tunet' ), initialOpen: false },
						el( C.RangeControl, { label: __( 'Slides per view', 'tunet' ), min: 1, max: 4, value: attrs.slidesPerView, onChange: function ( v ) { setAttributes( { slidesPerView: v } ); } } ),
						el( C.RangeControl, { label: __( 'Space between (px)', 'tunet' ), min: 0, max: 80, value: attrs.spaceBetween, onChange: function ( v ) { setAttributes( { spaceBetween: v } ); } } ),
						el( C.ToggleControl, { label: __( 'Infinite loop', 'tunet' ), checked: attrs.loop, onChange: function ( v ) { setAttributes( { loop: v } ); } } ),
						el( C.ToggleControl, { label: __( 'Autoplay', 'tunet' ), checked: attrs.autoplay, onChange: function ( v ) { setAttributes( { autoplay: v } ); } } ),
						el( C.ToggleControl, { label: __( 'Pagination', 'tunet' ), checked: attrs.pagination, onChange: function ( v ) { setAttributes( { pagination: v } ); } } ),
						el( C.ToggleControl, { label: __( 'Navigation arrows', 'tunet' ), checked: attrs.navigation, onChange: function ( v ) { setAttributes( { navigation: v } ); } } )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, { block: 'tunet/content-slider', attributes: attrs } )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
