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

$tunet_bg_type = isset( $attributes['bgType'] ) ? sanitize_key( $attributes['bgType'] ) : 'none';
$tunet_overlay = ! empty( $attributes['overlay'] );
$tunet_valign  = isset( $attributes['verticalAlignment'] ) ? sanitize_key( $attributes['verticalAlignment'] ) : 'center';
$tunet_div_top = isset( $attributes['dividerTop'] ) ? sanitize_key( $attributes['dividerTop'] ) : 'none';
$tunet_div_bot = isset( $attributes['dividerBottom'] ) ? sanitize_key( $attributes['dividerBottom'] ) : 'none';

// --- Clases del contenedor ---
$tunet_cwidth  = isset( $attributes['contentWidth'] ) ? sanitize_key( $attributes['contentWidth'] ) : 'constrained';
if ( ! in_array( $tunet_cwidth, array( 'constrained', 'wide', 'full' ), true ) ) {
	$tunet_cwidth = 'constrained';
}
$tunet_classes = array( 'tunet-section', 'is-bg-' . $tunet_bg_type, 'is-valign-' . $tunet_valign, 'is-content-' . $tunet_cwidth );
if ( ! empty( $attributes['gradientAnimate'] ) && 'gradient' === $tunet_bg_type ) {
	$tunet_classes[] = 'is-animated';
}

// --- CSS vars inline ---
$tunet_vars = array();

$tunet_min = isset( $attributes['minHeight'] ) ? (int) $attributes['minHeight'] : 0;
if ( $tunet_min > 0 ) {
	$tunet_vars[] = '--tf-sec-min-h:' . min( 100, $tunet_min ) . 'vh';
}

if ( 'color' === $tunet_bg_type && ! empty( $attributes['bgColor'] ) ) {
	$tunet_bg_color = tunet_core_safe_css_color( $attributes['bgColor'] );
	if ( '' !== $tunet_bg_color ) {
		$tunet_vars[] = '--tf-sec-bg:' . $tunet_bg_color;
	}
}
if ( 'gradient' === $tunet_bg_type && ! empty( $attributes['gradient'] ) ) {
	$tunet_vars[] = '--tf-sec-gradient:' . $attributes['gradient'];
}
if ( 'mesh' === $tunet_bg_type ) {
	foreach ( array( 'meshColor1' => '--tf-mesh-1', 'meshColor2' => '--tf-mesh-2', 'meshColor3' => '--tf-mesh-3' ) as $tunet_attr => $tunet_var ) {
		if ( ! empty( $attributes[ $tunet_attr ] ) ) {
			$tunet_mesh_color = tunet_core_safe_css_color( $attributes[ $tunet_attr ] );
			if ( '' !== $tunet_mesh_color ) {
				$tunet_vars[] = $tunet_var . ':' . $tunet_mesh_color;
			}
		}
	}
}
if ( $tunet_overlay ) {
	$tunet_ov_type = isset( $attributes['overlayType'] ) ? sanitize_key( $attributes['overlayType'] ) : 'color';
	$tunet_ov_grad = ( 'gradient' === $tunet_ov_type && ! empty( $attributes['overlayGradient'] ) )
		? tunet_core_safe_css_gradient( $attributes['overlayGradient'] )
		: '';
	if ( '' !== $tunet_ov_grad ) {
		// Overlay en gradiente: --tf-sec-overlay acepta un valor de background
		// (color o gradiente) — el CSS ya hace background:var(--tf-sec-overlay).
		// OJO: WP filtra el inline-style (safecss_filter_attr) → los stops del
		// gradiente deben ser rgba()/hex; var() y color-mix() dentro del gradiente
		// se descartan (el GradientPicker produce rgba, así que el sidebar va bien).
		// El valor pasa por el validador compartido: antes se concatenaba tal cual, sin
		// comprobar ni que fuera un string (un atributo array emitía "Array").
		$tunet_vars[] = '--tf-sec-overlay:' . $tunet_ov_grad;
	} elseif ( ! empty( $attributes['overlayColor'] ) ) {
		// Si el color es irrepresentable en un inline-style, se degrada a
		// `transparent` A PROPÓSITO: sin esta línea el valor desaparecería y el CSS
		// caería al color de fondo del theme a la opacidad pedida — un panel opaco
		// que tapa la foto. Perder el scrim es malo; tapar la imagen entera es peor.
		$tunet_ov_color = tunet_core_safe_css_color( $attributes['overlayColor'] );
		$tunet_vars[]   = '--tf-sec-overlay:' . ( '' !== $tunet_ov_color ? $tunet_ov_color : 'transparent' );
	} else {
		/*
		 * Overlay activado pero sin ningún valor utilizable (tipo gradiente con un
		 * gradiente que no valida, y sin color de respaldo). Hay que emitir
		 * `transparent` EXPLÍCITAMENTE: si la variable se queda sin definir, el CSS
		 * cae a `var(--tf-sec-overlay, var(--tnt-color-bg))` A LA OPACIDAD PEDIDA, o
		 * sea un panel opaco del color de fondo tapando la foto entera. Es
		 * exactamente el bug del 0.1.18, y al meter la validación del gradiente se
		 * volvía a abrir esa puerta por el otro lado.
		 */
		$tunet_vars[] = '--tf-sec-overlay:transparent';
	}
	$tunet_op = isset( $attributes['overlayOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayOpacity'] ) ) : 40;
	$tunet_vars[] = '--tf-sec-overlay-op:' . ( $tunet_op / 100 );

	// Refuerzo en pantallas pequeñas. Un overlay en gradiente lateral protege el
	// texto en escritorio (columna a la izquierda, foto limpia a la derecha), pero
	// en móvil el texto ocupa todo el ancho y se sale de la zona protegida: el
	// gradiente NO se puede adaptar solo porque su dirección no depende de la
	// forma de la caja. Este scrim uniforme opcional cubre ese hueco sin tocar el
	// overlay que eligió el comprador.
	if ( ! empty( $attributes['overlayMobile'] ) ) {
		$tunet_scrim = '';
		if ( ! empty( $attributes['overlayMobileColor'] ) ) {
			$tunet_scrim = tunet_core_safe_css_color( $attributes['overlayMobileColor'] );
		} elseif ( 'gradient' !== $tunet_ov_type && ! empty( $attributes['overlayColor'] ) ) {
			// Sin color propio hereda el del overlay: lo normal es querer "más de
			// lo mismo" en móvil, no un color distinto.
			$tunet_scrim = tunet_core_safe_css_color( $attributes['overlayColor'] );
		}
		$tunet_vars[]   = '--tf-sec-scrim-m:' . ( '' !== $tunet_scrim ? $tunet_scrim : '#000000' );
		$tunet_scrim_op = isset( $attributes['overlayMobileOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayMobileOpacity'] ) ) : 55;
		$tunet_vars[]   = '--tf-sec-scrim-m-op:' . ( $tunet_scrim_op / 100 );
		$tunet_classes[] = 'has-mobile-scrim';
	}
}
if ( 'none' !== $tunet_div_top || 'none' !== $tunet_div_bot ) {
	if ( ! empty( $attributes['dividerColor'] ) ) {
		$tunet_div_color = tunet_core_safe_css_color( $attributes['dividerColor'] );
		if ( '' !== $tunet_div_color ) {
			$tunet_vars[] = '--tf-sec-divider-color:' . $tunet_div_color;
		}
	}
	$tunet_dh = isset( $attributes['dividerHeight'] ) ? max( 0, (int) $attributes['dividerHeight'] ) : 60;
	$tunet_vars[] = '--tf-sec-divider-h:' . $tunet_dh . 'px';
}

$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $tunet_classes ),
		'style' => implode( ';', $tunet_vars ),
	)
);
?>
<section <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?><?php if ( 'video' === $tunet_bg_type ) : ?> data-pause-label="<?php echo esc_attr__( 'Pause background video', 'tunet-core' ); ?>" data-play-label="<?php echo esc_attr__( 'Play background video', 'tunet-core' ); ?>"<?php endif; ?>>
	<?php if ( 'none' !== $tunet_div_top ) : ?>
		<div class="tunet-section__divider tunet-section__divider--top">
			<?php echo tunet_section_divider_svg( $tunet_div_top ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>

	<?php if ( 'image' === $tunet_bg_type && ! empty( $attributes['bgImageUrl'] ) ) : ?>
		<div class="tunet-section__bg" style="background-image:url('<?php echo esc_url( $attributes['bgImageUrl'] ); ?>')"></div>
	<?php elseif ( 'video' === $tunet_bg_type && ! empty( $attributes['bgVideoUrl'] ) ) : ?>
		<?php
		$tunet_vmime   = ! empty( $attributes['bgVideoId'] ) ? get_post_mime_type( (int) $attributes['bgVideoId'] ) : '';
		$tunet_vposter = ! empty( $attributes['bgVideoPosterUrl'] ) ? (string) $attributes['bgVideoPosterUrl'] : '';
		$tunet_vauto   = ! isset( $attributes['bgVideoAutoplay'] ) || (bool) $attributes['bgVideoAutoplay'];
		$tunet_vloop   = ! isset( $attributes['bgVideoLoop'] ) || (bool) $attributes['bgVideoLoop'];
		/*
		 * Carga perezosa cuando HAY póster: el vídeo sale sin `autoplay` y con
		 * preload="none", así que el navegador no descarga nada; lo arranca view.js
		 * salvo que el visitante pida menos movimiento o el autoplay esté apagado.
		 * Tiene que decidirse aquí porque al parsear el HTML el navegador no sabe
		 * nada de prefers-reduced-motion, y con `autoplay` ya habría descargado.
		 * SIN póster se mantiene el autoplay en el HTML: si no, quedaría un hueco
		 * vacío hasta que despierte el JS.
		 */
		$tunet_vlazy = ( '' !== $tunet_vposter );
		?>
		<video class="tunet-section__bg" muted playsinline aria-hidden="true"<?php
			echo $tunet_vloop ? ' loop' : '';
			echo $tunet_vposter ? ' poster="' . esc_url( $tunet_vposter ) . '"' : '';
			echo ( $tunet_vauto && ! $tunet_vlazy ) ? ' autoplay' : '';
			echo ' preload="' . ( $tunet_vlazy ? 'none' : 'metadata' ) . '"';
			echo ' data-tnt-autoplay="' . ( $tunet_vauto ? '1' : '0' ) . '"';
		?>>
			<source src="<?php echo esc_url( $attributes['bgVideoUrl'] ); ?>"<?php echo $tunet_vmime ? ' type="' . esc_attr( $tunet_vmime ) . '"' : ''; ?> />
		</video>
	<?php elseif ( in_array( $tunet_bg_type, array( 'color', 'gradient', 'mesh' ), true ) ) : ?>
		<div class="tunet-section__bg"></div>
	<?php endif; ?>

	<?php if ( $tunet_overlay ) : ?>
		<div class="tunet-section__overlay"></div>
		<?php if ( ! empty( $attributes['overlayMobile'] ) ) : ?>
			<div class="tunet-section__scrim"></div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="tunet-section__inner">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado. ?>
	</div>

	<?php if ( 'none' !== $tunet_div_bot ) : ?>
		<div class="tunet-section__divider tunet-section__divider--bottom">
			<?php echo tunet_section_divider_svg( $tunet_div_bot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>
</section>
