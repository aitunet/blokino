/* ==========================================================================
 * Tunet Core · RepeaterControl — editor UI COMPARTIDO
 * --------------------------------------------------------------------------
 * Acordeón de items en el InspectorControls: agregar / borrar / reordenar.
 * Cada bloque define sus campos vía la render-prop `renderItem`. Guarda todo
 * en un atributo `items` (array) del bloque. Sin InnerBlocks, sin build step.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var C = wp.components;

	window.tunet = window.tunet || {};

	window.tunet.RepeaterControl = function ( props ) {
		var items = props.items || [];
		var onChange = props.onChange;
		var renderItem = props.renderItem;
		var newItem = props.newItem;
		var max = props.max || 0;
		var addLabel = props.addLabel || __( 'Add item', 'tunet' );
		var itemLabel = props.itemLabel || function ( it, i ) {
			return __( 'Item', 'tunet' ) + ' ' + ( i + 1 );
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
			return el(
				C.PanelBody,
				{ key: index, title: itemLabel( item, index ), initialOpen: false },
				renderItem( item, index, function ( patch ) { update( index, patch ); } ),
				el(
					'div',
					{ className: 'tunet-repeater__actions', style: { display: 'flex', gap: '4px', marginTop: '12px' } },
					el( C.Button, { variant: 'secondary', size: 'small', disabled: index === 0, onClick: function () { move( index, -1 ); }, label: __( 'Move up', 'tunet' ) }, '↑' ),
					el( C.Button, { variant: 'secondary', size: 'small', disabled: index === items.length - 1, onClick: function () { move( index, 1 ); }, label: __( 'Move down', 'tunet' ) }, '↓' ),
					el( C.Button, { variant: 'secondary', isDestructive: true, size: 'small', onClick: function () { remove( index ); } }, __( 'Remove', 'tunet' ) )
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
