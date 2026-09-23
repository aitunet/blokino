<?php
/**
 * Render del block blokino/section (dinámico).
 *
 * Capas (de atrás a delante): fondo (color/gradiente/mesh/imagen/vídeo) →
 * overlay → contenido (InnerBlocks) → shape dividers. Todo configurable; los
 * valores van como CSS vars / clases. Estética desde tokens --tnt-*.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'blokino_section_divider_svg' ) ) {
	/**
	 * Devuelve el SVG de un shape divider.
	 *
	 * @param string $shape wave|slant|curve.
	 * @return string
	 */
	function blokino_section_divider_svg( $shape ) {
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

$blokino_bg_type = isset( $attributes['bgType'] ) ? sanitize_key( $attributes['bgType'] ) : 'none';
$blokino_overlay = ! empty( $attributes['overlay'] );
$blokino_valign  = isset( $attributes['verticalAlignment'] ) ? sanitize_key( $attributes['verticalAlignment'] ) : 'center';
$blokino_div_top = isset( $attributes['dividerTop'] ) ? sanitize_key( $attributes['dividerTop'] ) : 'none';
$blokino_div_bot = isset( $attributes['dividerBottom'] ) ? sanitize_key( $attributes['dividerBottom'] ) : 'none';

// --- Clases del contenedor ---
$blokino_cwidth  = isset( $attributes['contentWidth'] ) ? sanitize_key( $attributes['contentWidth'] ) : 'constrained';
if ( ! in_array( $blokino_cwidth, array( 'constrained', 'wide', 'full' ), true ) ) {
	$blokino_cwidth = 'constrained';
}
$blokino_classes = array( 'blokino-section', 'is-bg-' . $blokino_bg_type, 'is-valign-' . $blokino_valign, 'is-content-' . $blokino_cwidth );
if ( ! empty( $attributes['gradientAnimate'] ) && 'gradient' === $blokino_bg_type ) {
	$blokino_classes[] = 'is-animated';
}

// --- CSS vars inline ---
$blokino_vars = array();

$blokino_min = isset( $attributes['minHeight'] ) ? (int) $attributes['minHeight'] : 0;
if ( $blokino_min > 0 ) {
	$blokino_vars[] = '--tf-sec-min-h:' . min( 100, $blokino_min ) . 'vh';
}

if ( 'color' === $blokino_bg_type && ! empty( $attributes['bgColor'] ) ) {
	$blokino_bg_color = blokino_safe_css_color( $attributes['bgColor'] );
	if ( '' !== $blokino_bg_color ) {
		$blokino_vars[] = '--tf-sec-bg:' . $blokino_bg_color;
	}
}
if ( 'gradient' === $blokino_bg_type && ! empty( $attributes['gradient'] ) ) {
	$blokino_vars[] = '--tf-sec-gradient:' . $attributes['gradient'];
}
if ( 'mesh' === $blokino_bg_type ) {
	foreach ( array( 'meshColor1' => '--tf-mesh-1', 'meshColor2' => '--tf-mesh-2', 'meshColor3' => '--tf-mesh-3' ) as $blokino_attr => $blokino_var ) {
		if ( ! empty( $attributes[ $blokino_attr ] ) ) {
			$blokino_mesh_color = blokino_safe_css_color( $attributes[ $blokino_attr ] );
			if ( '' !== $blokino_mesh_color ) {
				$blokino_vars[] = $blokino_var . ':' . $blokino_mesh_color;
			}
		}
	}
}
if ( $blokino_overlay ) {
	$blokino_ov_type = isset( $attributes['overlayType'] ) ? sanitize_key( $attributes['overlayType'] ) : 'color';
	$blokino_ov_grad = ( 'gradient' === $blokino_ov_type && ! empty( $attributes['overlayGradient'] ) )
		? blokino_safe_css_gradient( $attributes['overlayGradient'] )
		: '';
	if ( '' !== $blokino_ov_grad ) {
		// Overlay en gradiente: --tf-sec-overlay acepta un valor de background
		// (color o gradiente) — el CSS ya hace background:var(--tf-sec-overlay).
		// OJO: WP filtra el inline-style (safecss_filter_attr) → los stops del
		// gradiente deben ser rgba()/hex; var() y color-mix() dentro del gradiente
		// se descartan (el GradientPicker produce rgba, así que el sidebar va bien).
		// El valor pasa por el validador compartido: antes se concatenaba tal cual, sin
		// comprobar ni que fuera un string (un atributo array emitía "Array").
		$blokino_vars[] = '--tf-sec-overlay:' . $blokino_ov_grad;
	} elseif ( ! empty( $attributes['overlayColor'] ) ) {
		// Si el color es irrepresentable en un inline-style, se degrada a
		// `transparent` A PROPÓSITO: sin esta línea el valor desaparecería y el CSS
		// caería al color de fondo del theme a la opacidad pedida — un panel opaco
		// que tapa la foto. Perder el scrim es malo; tapar la imagen entera es peor.
		$blokino_ov_color = blokino_safe_css_color( $attributes['overlayColor'] );
		$blokino_vars[]   = '--tf-sec-overlay:' . ( '' !== $blokino_ov_color ? $blokino_ov_color : 'transparent' );
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
		$blokino_vars[] = '--tf-sec-overlay:transparent';
	}
	$blokino_op = isset( $attributes['overlayOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayOpacity'] ) ) : 40;
	$blokino_vars[] = '--tf-sec-overlay-op:' . ( $blokino_op / 100 );

	// Refuerzo en pantallas pequeñas. Un overlay en gradiente lateral protege el
	// texto en escritorio (columna a la izquierda, foto limpia a la derecha), pero
	// en móvil el texto ocupa todo el ancho y se sale de la zona protegida: el
	// gradiente NO se puede adaptar solo porque su dirección no depende de la
	// forma de la caja. Este scrim uniforme opcional cubre ese hueco sin tocar el
	// overlay que eligió el comprador.
	if ( ! empty( $attributes['overlayMobile'] ) ) {
		$blokino_scrim = '';
		if ( ! empty( $attributes['overlayMobileColor'] ) ) {
			$blokino_scrim = blokino_safe_css_color( $attributes['overlayMobileColor'] );
		} elseif ( 'gradient' !== $blokino_ov_type && ! empty( $attributes['overlayColor'] ) ) {
			// Sin color propio hereda el del overlay: lo normal es querer "más de
			// lo mismo" en móvil, no un color distinto.
			$blokino_scrim = blokino_safe_css_color( $attributes['overlayColor'] );
		}
		$blokino_vars[]   = '--tf-sec-scrim-m:' . ( '' !== $blokino_scrim ? $blokino_scrim : '#000000' );
		$blokino_scrim_op = isset( $attributes['overlayMobileOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayMobileOpacity'] ) ) : 55;
		$blokino_vars[]   = '--tf-sec-scrim-m-op:' . ( $blokino_scrim_op / 100 );
		$blokino_classes[] = 'has-mobile-scrim';
	}
}
if ( 'none' !== $blokino_div_top || 'none' !== $blokino_div_bot ) {
	if ( ! empty( $attributes['dividerColor'] ) ) {
		$blokino_div_color = blokino_safe_css_color( $attributes['dividerColor'] );
		if ( '' !== $blokino_div_color ) {
			$blokino_vars[] = '--tf-sec-divider-color:' . $blokino_div_color;
		}
	}
	$blokino_dh = isset( $attributes['dividerHeight'] ) ? max( 0, (int) $attributes['dividerHeight'] ) : 60;
	$blokino_vars[] = '--tf-sec-divider-h:' . $blokino_dh . 'px';
}

$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $blokino_classes ),
		'style' => implode( ';', $blokino_vars ),
	)
);
?>
<section <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?><?php if ( 'video' === $blokino_bg_type ) : ?> data-pause-label="<?php echo esc_attr__( 'Pause background video', 'blokino' ); ?>" data-play-label="<?php echo esc_attr__( 'Play background video', 'blokino' ); ?>"<?php endif; ?>>
	<?php if ( 'none' !== $blokino_div_top ) : ?>
		<div class="blokino-section__divider blokino-section__divider--top">
			<?php echo blokino_section_divider_svg( $blokino_div_top ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>

	<?php if ( 'image' === $blokino_bg_type && ! empty( $attributes['bgImageUrl'] ) ) : ?>
		<div class="blokino-section__bg" style="background-image:url('<?php echo esc_url( $attributes['bgImageUrl'] ); ?>')"></div>
	<?php elseif ( 'video' === $blokino_bg_type && ! empty( $attributes['bgVideoUrl'] ) ) : ?>
		<?php
		$blokino_vmime   = ! empty( $attributes['bgVideoId'] ) ? get_post_mime_type( (int) $attributes['bgVideoId'] ) : '';
		$blokino_vposter = ! empty( $attributes['bgVideoPosterUrl'] ) ? (string) $attributes['bgVideoPosterUrl'] : '';
		$blokino_vauto   = ! isset( $attributes['bgVideoAutoplay'] ) || (bool) $attributes['bgVideoAutoplay'];
		$blokino_vloop   = ! isset( $attributes['bgVideoLoop'] ) || (bool) $attributes['bgVideoLoop'];
		/*
		 * Carga perezosa cuando HAY póster: el vídeo sale sin `autoplay` y con
		 * preload="none", así que el navegador no descarga nada; lo arranca view.js
		 * salvo que el visitante pida menos movimiento o el autoplay esté apagado.
		 * Tiene que decidirse aquí porque al parsear el HTML el navegador no sabe
		 * nada de prefers-reduced-motion, y con `autoplay` ya habría descargado.
		 * SIN póster se mantiene el autoplay en el HTML: si no, quedaría un hueco
		 * vacío hasta que despierte el JS.
		 */
		$blokino_vlazy = ( '' !== $blokino_vposter );
		?>
		<video class="blokino-section__bg" muted playsinline aria-hidden="true"<?php
			echo $blokino_vloop ? ' loop' : '';
			echo $blokino_vposter ? ' poster="' . esc_url( $blokino_vposter ) . '"' : '';
			echo ( $blokino_vauto && ! $blokino_vlazy ) ? ' autoplay' : '';
			echo ' preload="' . ( $blokino_vlazy ? 'none' : 'metadata' ) . '"';
			echo ' data-tnt-autoplay="' . ( $blokino_vauto ? '1' : '0' ) . '"';
		?>>
			<source src="<?php echo esc_url( $attributes['bgVideoUrl'] ); ?>"<?php echo $blokino_vmime ? ' type="' . esc_attr( $blokino_vmime ) . '"' : ''; ?> />
		</video>
	<?php elseif ( in_array( $blokino_bg_type, array( 'color', 'gradient', 'mesh' ), true ) ) : ?>
		<div class="blokino-section__bg"></div>
	<?php endif; ?>

	<?php if ( $blokino_overlay ) : ?>
		<div class="blokino-section__overlay"></div>
		<?php if ( ! empty( $attributes['overlayMobile'] ) ) : ?>
			<div class="blokino-section__scrim"></div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="blokino-section__inner">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado. ?>
	</div>

	<?php if ( 'none' !== $blokino_div_bot ) : ?>
		<div class="blokino-section__divider blokino-section__divider--bottom">
			<?php echo blokino_section_divider_svg( $blokino_div_bot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>
</section>
