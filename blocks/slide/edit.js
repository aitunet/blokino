/* ==========================================================================
 * Tunet Core · Block tunet/slide — editor
 * --------------------------------------------------------------------------
 * Child del tunet/slider. Wrapper fino: adentro va cualquier bloque
 * (core/* + tunet/*). Sin campos tipados, sin estética (la da el CSS del
 * padre vía .swiper-slide). save = InnerBlocks.Content (bloque estático);
 * el render.php del padre envuelve el contenido en .swiper-slide.
 * ========================================================================== */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var useBlockProps = be.useBlockProps;
	var InnerBlocks = be.InnerBlocks;

	registerBlockType( 'tunet/slide', {
		edit: function () {
			var blockProps = useBlockProps( { className: 'tunet-slide' } );
			return el(
				'div',
				blockProps,
				el( InnerBlocks, {
					template: [ [ 'core/paragraph', { placeholder: __( 'Slide content…', 'tunet' ) } ] ],
					templateLock: false
				} )
			);
		},

		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
