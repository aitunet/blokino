<?php
/**
 * Render del block tunet/section (dinámico).
 *
 * Capas (de atrás a delante): fondo (color/gradiente/mesh/imagen/vídeo) →
 * overlay → contenido (InnerBlocks) → shape dividers. Todo configurable; los
 * valores van como CSS vars / clases. Estética desde tokens --tnt-*.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tunet_section_divider_svg' ) ) {
	/**
	 * Devuelve el SVG de un shape divider.
	 *
	 * @param string $shape wave|slant|curve.
	 * @return string
	 */
	function tunet_section_divider_svg( $shape ) {
		$paths = array(
			'wave'  => 'M0,40 C300,120 900,-40 1200,40 L1200,120 L0,120 Z',
			'slant' => 'M0,120 L1200,0 L1200,120 Z',
			'curve' => 'M0,120 Q600,-20 1200,120 Z',
		);
		if ( empty( $paths[ $shape ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[ $shape ] ) . '"></path></svg>';
	}
}

$tnt_bg_type = isset( $attributes['bgType'] ) ? sanitize_key( $attributes['bgType'] ) : 'none';
$tnt_overlay = ! empty( $attributes['overlay'] );
$tnt_valign  = isset( $attributes['verticalAlignment'] ) ? sanitize_key( $attributes['verticalAlignment'] ) : 'center';
$tnt_div_top = isset( $attributes['dividerTop'] ) ? sanitize_key( $attributes['dividerTop'] ) : 'none';
$tnt_div_bot = isset( $attributes['dividerBottom'] ) ? sanitize_key( $attributes['dividerBottom'] ) : 'none';

// --- Clases del contenedor ---
$tnt_cwidth  = isset( $attributes['contentWidth'] ) ? sanitize_key( $attributes['contentWidth'] ) : 'constrained';
if ( ! in_array( $tnt_cwidth, array( 'constrained', 'wide', 'full' ), true ) ) {
	$tnt_cwidth = 'constrained';
}
$tnt_classes = array( 'tunet-section', 'is-bg-' . $tnt_bg_type, 'is-valign-' . $tnt_valign, 'is-content-' . $tnt_cwidth );
if ( ! empty( $attributes['gradientAnimate'] ) && 'gradient' === $tnt_bg_type ) {
	$tnt_classes[] = 'is-animated';
}

// --- CSS vars inline ---
$tnt_vars = array();

$tnt_min = isset( $attributes['minHeight'] ) ? (int) $attributes['minHeight'] : 0;
if ( $tnt_min > 0 ) {
	$tnt_vars[] = '--tf-sec-min-h:' . min( 100, $tnt_min ) . 'vh';
}

if ( 'color' === $tnt_bg_type && ! empty( $attributes['bgColor'] ) ) {
	$tnt_vars[] = '--tf-sec-bg:' . $attributes['bgColor'];
}
if ( 'gradient' === $tnt_bg_type && ! empty( $attributes['gradient'] ) ) {
	$tnt_vars[] = '--tf-sec-gradient:' . $attributes['gradient'];
}
if ( 'mesh' === $tnt_bg_type ) {
	foreach ( array( 'meshColor1' => '--tf-mesh-1', 'meshColor2' => '--tf-mesh-2', 'meshColor3' => '--tf-mesh-3' ) as $attr => $var ) {
		if ( ! empty( $attributes[ $attr ] ) ) {
			$tnt_vars[] = $var . ':' . $attributes[ $attr ];
		}
	}
}
if ( $tnt_overlay ) {
	if ( ! empty( $attributes['overlayColor'] ) ) {
		$tnt_vars[] = '--tf-sec-overlay:' . $attributes['overlayColor'];
	}
	$tnt_op = isset( $attributes['overlayOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayOpacity'] ) ) : 40;
	$tnt_vars[] = '--tf-sec-overlay-op:' . ( $tnt_op / 100 );
}
if ( 'none' !== $tnt_div_top || 'none' !== $tnt_div_bot ) {
	if ( ! empty( $attributes['dividerColor'] ) ) {
		$tnt_vars[] = '--tf-sec-divider-color:' . $attributes['dividerColor'];
	}
	$tnt_dh = isset( $attributes['dividerHeight'] ) ? max( 0, (int) $attributes['dividerHeight'] ) : 60;
	$tnt_vars[] = '--tf-sec-divider-h:' . $tnt_dh . 'px';
}

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $tnt_classes ),
		'style' => implode( ';', $tnt_vars ),
	)
);
?>
<section <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<?php if ( 'none' !== $tnt_div_top ) : ?>
		<div class="tunet-section__divider tunet-section__divider--top">
			<?php echo tunet_section_divider_svg( $tnt_div_top ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>

	<?php if ( 'image' === $tnt_bg_type && ! empty( $attributes['bgImageUrl'] ) ) : ?>
		<div class="tunet-section__bg" style="background-image:url('<?php echo esc_url( $attributes['bgImageUrl'] ); ?>')"></div>
	<?php elseif ( 'video' === $tnt_bg_type && ! empty( $attributes['bgVideoUrl'] ) ) : ?>
		<video class="tunet-section__bg" autoplay muted loop playsinline preload="metadata">
			<source src="<?php echo esc_url( $attributes['bgVideoUrl'] ); ?>" type="video/mp4" />
		</video>
	<?php elseif ( in_array( $tnt_bg_type, array( 'color', 'gradient', 'mesh' ), true ) ) : ?>
		<div class="tunet-section__bg"></div>
	<?php endif; ?>

	<?php if ( $tnt_overlay ) : ?>
		<div class="tunet-section__overlay"></div>
	<?php endif; ?>

	<div class="tunet-section__inner">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado. ?>
	</div>

	<?php if ( 'none' !== $tnt_div_bot ) : ?>
		<div class="tunet-section__divider tunet-section__divider--bottom">
			<?php echo tunet_section_divider_svg( $tnt_div_bot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>
</section>
