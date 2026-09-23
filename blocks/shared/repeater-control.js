/* ==========================================================================
 * Blokino · UI de repeater COMPARTIDA (editor)
 * --------------------------------------------------------------------------
 * `window.blokino.RepeaterControl` — acordeón de items en el InspectorControls:
 * agregar / borrar / reordenar. Cada bloque define sus campos vía la render-prop
 * `renderItem`. Guarda todo en un atributo `items` (array). Sin InnerBlocks, sin
 * build step. Prop opcional `onActivate(index)`: se dispara al EXPANDIR un item
 * (para, p. ej., mover el preview del carrusel a ese slide).
 *
 * `window.blokino.MediaField` — campo de imagen/avatar NATIVO (MediaUpload de WP)
 * con **miniatura de preview** + Reemplazar/Quitar. Compartido por los repeaters
 * de content-slider (imagen) y testimonials (avatar, variante redonda).
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var C = wp.components;
	var be = wp.blockEditor || {};
	var MediaUpload = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;

	window.blokino = window.blokino || {};

	/* ----------------------------------------------------------------------
	 * MediaField — selector de imagen nativo con miniatura de preview.
	 * props: { id, url, onSelect(media), onRemove(), allowedTypes, round,
	 *          setLabel, replaceLabel }
	 * ------------------------------------------------------------------- */
	window.blokino.MediaField = function ( props ) {
		var id = props.id || 0;
		var url = props.url || '';
		var setLabel = props.setLabel || __( 'Set image', 'blokino' );
		var replaceLabel = props.replaceLabel || __( 'Replace image', 'blokino' );
		var cls = 'blokino-media-field' + ( props.round ? ' is-round' : '' );

		if ( ! MediaUpload || ! MediaUploadCheck ) {
			return null;
		}

		return el(
			MediaUploadCheck,
			{},
			el( MediaUpload, {
				allowedTypes: props.allowedTypes || [ 'image' ],
				value: id,
				onSelect: props.onSelect,
				render: function ( o ) {
					return el(
						'div',
						{ className: cls },
						url
							? el(
								'button',
								{ type: 'button', className: 'blokino-media-field__preview', onClick: o.open, 'aria-label': replaceLabel },
								el( 'img', { src: url, alt: '' } )
							)
							: el(
								'button',
								{ type: 'button', className: 'blokino-media-field__placeholder', onClick: o.open },
								setLabel
							),
						el(
							'div',
							{ className: 'blokino-media-field__actions' },
							el( C.Button, { variant: 'secondary', size: 'small', onClick: o.open }, url ? replaceLabel : setLabel ),
							( id || url )
								? el( C.Button, { variant: 'tertiary', size: 'small', isDestructive: true, onClick: props.onRemove }, __( 'Remove', 'blokino' ) )
								: null
						)
					);
				}
			} )
		);
	};

	/* ----------------------------------------------------------------------
	 * RepeaterControl — acordeón de items.
	 * ------------------------------------------------------------------- */
	window.blokino.RepeaterControl = function ( props ) {
		var items = props.items || [];
		var onChange = props.onChange;
		var renderItem = props.renderItem;
		var newItem = props.newItem;
		var onActivate = props.onActivate;
		var max = props.max || 0;
		var addLabel = props.addLabel || __( 'Add item', 'blokino' );
		var itemLabel = props.itemLabel || function ( it, i ) {
			return __( 'Item', 'blokino' ) + ' ' + ( i + 1 );
		};

		function update( index, patch ) {
			var next = items.slice();
			next[ index ] = Object.assign( {}, next[ index ], patch );
			onChange( next );
		}
		function add() {
			if ( max && items.length >= max ) { return; }
			onChange( items.concat( [ newItem() ] ) );
		}
		function remove( index ) {
			var next = items.slice();
			next.splice( index, 1 );
			onChange( next );
		}
		function move( index, delta ) {
			var target = index + delta;
			if ( target < 0 || target >= items.length ) { return; }
			var next = items.slice();
			var tmp = next[ index ];
			next[ index ] = next[ target ];
			next[ target ] = tmp;
			onChange( next );
		}

		var panels = items.map( function ( item, index ) {
			function activate() {
				if ( onActivate ) { onActivate( index ); }
			}
			return el(
				C.PanelBody,
				{
					key: index,
					title: itemLabel( item, index ),
					initialOpen: false,
					// Al EXPANDIR el item.
					onToggle: function ( isOpen ) {
						if ( isOpen ) { activate(); }
					}
				},
				// Cualquier clic o foco DENTRO del item también activa (aunque ya esté
				// abierto): así "hacer clic en un item" siempre mueve el preview a él.
				el(
					'div',
					{ className: 'blokino-repeater__item', onMouseDownCapture: activate, onFocusCapture: activate },
					renderItem( item, index, function ( patch ) { update( index, patch ); } ),
					el(
						'div',
						{ className: 'blokino-repeater__actions', style: { display: 'flex', gap: '4px', marginTop: '12px' } },
						el( C.Button, { variant: 'secondary', size: 'small', disabled: index === 0, onClick: function () { move( index, -1 ); }, label: __( 'Move up', 'blokino' ) }, '↑' ),
						el( C.Button, { variant: 'secondary', size: 'small', disabled: index === items.length - 1, onClick: function () { move( index, 1 ); }, label: __( 'Move down', 'blokino' ) }, '↓' ),
						el( C.Button, { variant: 'secondary', isDestructive: true, size: 'small', onClick: function () { remove( index ); } }, __( 'Remove', 'blokino' ) )
					)
				)
			);
		} );

		return el(
			Fragment,
			{},
			panels,
			el(
				C.Button,
				{ variant: 'primary', onClick: add, disabled: !! ( max && items.length >= max ), style: { marginTop: '8px' } },
				addLabel
			)
		);
	};
} )( window.wp );
