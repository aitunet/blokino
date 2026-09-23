( function ( wp ) {
	'use strict';
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var c = wp.components;

	wp.blocks.registerBlockType( 'blokino/breadcrumbs', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var sep = a.separator || '/';

			// Muestra de ejemplo: el rastro real depende de la petición y este
			// block se renderiza en servidor, así que en el canvas se dibuja una
			// forma representativa en vez de llamar a ServerSideRender por cada
			// tecla (el panel no cambia el rastro, solo su presentación).
			var sample = [ a.homeLabel || __( 'Home', 'blokino' ), __( 'Work', 'blokino' ), __( 'Project name', 'blokino' ) ];
			if ( a.showCurrent === false ) {
				sample.pop();
			}

			return el( wp.element.Fragment, {},
				el( InspectorControls, {},
					el( c.PanelBody, { title: __( 'Breadcrumbs', 'blokino' ), initialOpen: true },
						el( c.TextControl, {
							label: __( 'Home label', 'blokino' ),
							help: __( 'Leave empty to use "Home".', 'blokino' ),
							value: a.homeLabel,
							onChange: function ( v ) { set( { homeLabel: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.TextControl, {
							label: __( 'Separator', 'blokino' ),
							value: a.separator,
							onChange: function ( v ) { set( { separator: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Show current page', 'blokino' ),
							checked: a.showCurrent !== false,
							onChange: function ( v ) { set( { showCurrent: !! v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( c.ToggleControl, {
							label: __( 'Output BreadcrumbList structured data', 'blokino' ),
							help: __( 'Search engines use it to draw the trail in results.', 'blokino' ),
							checked: a.structuredData !== false,
							onChange: function ( v ) { set( { structuredData: !! v } ); },
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el( 'nav', useBlockProps( { className: 'blokino-breadcrumbs' } ),
					el( 'ol', { className: 'blokino-breadcrumbs__list', style: { '--tnt-breadcrumb-sep': '"' + sep + '"' } },
						sample.map( function ( label, i ) {
							return el( 'li', { key: i, className: 'blokino-breadcrumbs__item' },
								el( 'span', { className: i === sample.length - 1 ? 'blokino-breadcrumbs__current' : 'blokino-breadcrumbs__link' }, label )
							);
						} )
					)
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
