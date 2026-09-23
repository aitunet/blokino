<?php
/**
 * Render del block bloquix/section (dinámico).
 *
 * Capas (de atrás a delante): fondo (color/gradiente/mesh/imagen/vídeo) →
 * overlay → contenido (InnerBlocks) → shape dividers. Todo configurable; los
 * valores van como CSS vars / clases. Estética desde tokens --tnt-*.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bloquix_section_divider_svg' ) ) {
	/**
	 * Devuelve el SVG de un shape divider.
	 *
	 * @param string $shape wave|slant|curve.
	 * @return string
	 */
	function bloquix_section_divider_svg( $shape ) {
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

$bloquix_bg_type = isset( $attributes['bgType'] ) ? sanitize_key( $attributes['bgType'] ) : 'none';
$bloquix_overlay = ! empty( $attributes['overlay'] );
$bloquix_valign  = isset( $attributes['verticalAlignment'] ) ? sanitize_key( $attributes['verticalAlignment'] ) : 'center';
$bloquix_div_top = isset( $attributes['dividerTop'] ) ? sanitize_key( $attributes['dividerTop'] ) : 'none';
$bloquix_div_bot = isset( $attributes['dividerBottom'] ) ? sanitize_key( $attributes['dividerBottom'] ) : 'none';

// --- Clases del contenedor ---
$bloquix_cwidth  = isset( $attributes['contentWidth'] ) ? sanitize_key( $attributes['contentWidth'] ) : 'constrained';
if ( ! in_array( $bloquix_cwidth, array( 'constrained', 'wide', 'full' ), true ) ) {
	$bloquix_cwidth = 'constrained';
}
$bloquix_classes = array( 'bloquix-section', 'is-bg-' . $bloquix_bg_type, 'is-valign-' . $bloquix_valign, 'is-content-' . $bloquix_cwidth );
if ( ! empty( $attributes['gradientAnimate'] ) && 'gradient' === $bloquix_bg_type ) {
	$bloquix_classes[] = 'is-animated';
}

// --- CSS vars inline ---
$bloquix_vars = array();

$bloquix_min = isset( $attributes['minHeight'] ) ? (int) $attributes['minHeight'] : 0;
if ( $bloquix_min > 0 ) {
	$bloquix_vars[] = '--tf-sec-min-h:' . min( 100, $bloquix_min ) . 'vh';
}

if ( 'color' === $bloquix_bg_type && ! empty( $attributes['bgColor'] ) ) {
	$bloquix_bg_color = bloquix_safe_css_color( $attributes['bgColor'] );
	if ( '' !== $bloquix_bg_color ) {
		$bloquix_vars[] = '--tf-sec-bg:' . $bloquix_bg_color;
	}
}
if ( 'gradient' === $bloquix_bg_type && ! empty( $attributes['gradient'] ) ) {
	$bloquix_vars[] = '--tf-sec-gradient:' . $attributes['gradient'];
}
if ( 'mesh' === $bloquix_bg_type ) {
	foreach ( array( 'meshColor1' => '--tf-mesh-1', 'meshColor2' => '--tf-mesh-2', 'meshColor3' => '--tf-mesh-3' ) as $bloquix_attr => $bloquix_var ) {
		if ( ! empty( $attributes[ $bloquix_attr ] ) ) {
			$bloquix_mesh_color = bloquix_safe_css_color( $attributes[ $bloquix_attr ] );
			if ( '' !== $bloquix_mesh_color ) {
				$bloquix_vars[] = $bloquix_var . ':' . $bloquix_mesh_color;
			}
		}
	}
}
if ( $bloquix_overlay ) {
	$bloquix_ov_type = isset( $attributes['overlayType'] ) ? sanitize_key( $attributes['overlayType'] ) : 'color';
	$bloquix_ov_grad = ( 'gradient' === $bloquix_ov_type && ! empty( $attributes['overlayGradient'] ) )
		? bloquix_safe_css_gradient( $attributes['overlayGradient'] )
		: '';
	if ( '' !== $bloquix_ov_grad ) {
		// Overlay en gradiente: --tf-sec-overlay acepta un valor de background
		// (color o gradiente) — el CSS ya hace background:var(--tf-sec-overlay).
		// OJO: WP filtra el inline-style (safecss_filter_attr) → los stops del
		// gradiente deben ser rgba()/hex; var() y color-mix() dentro del gradiente
		// se descartan (el GradientPicker produce rgba, así que el sidebar va bien).
		// El valor pasa por el validador compartido: antes se concatenaba tal cual, sin
		// comprobar ni que fuera un string (un atributo array emitía "Array").
		$bloquix_vars[] = '--tf-sec-overlay:' . $bloquix_ov_grad;
	} elseif ( ! empty( $attributes['overlayColor'] ) ) {
		// Si el color es irrepresentable en un inline-style, se degrada a
		// `transparent` A PROPÓSITO: sin esta línea el valor desaparecería y el CSS
		// caería al color de fondo del theme a la opacidad pedida — un panel opaco
		// que tapa la foto. Perder el scrim es malo; tapar la imagen entera es peor.
		$bloquix_ov_color = bloquix_safe_css_color( $attributes['overlayColor'] );
		$bloquix_vars[]   = '--tf-sec-overlay:' . ( '' !== $bloquix_ov_color ? $bloquix_ov_color : 'transparent' );
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
		$bloquix_vars[] = '--tf-sec-overlay:transparent';
	}
	$bloquix_op = isset( $attributes['overlayOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayOpacity'] ) ) : 40;
	$bloquix_vars[] = '--tf-sec-overlay-op:' . ( $bloquix_op / 100 );

	// Refuerzo en pantallas pequeñas. Un overlay en gradiente lateral protege el
	// texto en escritorio (columna a la izquierda, foto limpia a la derecha), pero
	// en móvil el texto ocupa todo el ancho y se sale de la zona protegida: el
	// gradiente NO se puede adaptar solo porque su dirección no depende de la
	// forma de la caja. Este scrim uniforme opcional cubre ese hueco sin tocar el
	// overlay que eligió el comprador.
	if ( ! empty( $attributes['overlayMobile'] ) ) {
		$bloquix_scrim = '';
		if ( ! empty( $attributes['overlayMobileColor'] ) ) {
			$bloquix_scrim = bloquix_safe_css_color( $attributes['overlayMobileColor'] );
		} elseif ( 'gradient' !== $bloquix_ov_type && ! empty( $attributes['overlayColor'] ) ) {
			// Sin color propio hereda el del overlay: lo normal es querer "más de
			// lo mismo" en móvil, no un color distinto.
			$bloquix_scrim = bloquix_safe_css_color( $attributes['overlayColor'] );
		}
		$bloquix_vars[]   = '--tf-sec-scrim-m:' . ( '' !== $bloquix_scrim ? $bloquix_scrim : '#000000' );
		$bloquix_scrim_op = isset( $attributes['overlayMobileOpacity'] ) ? max( 0, min( 100, (int) $attributes['overlayMobileOpacity'] ) ) : 55;
		$bloquix_vars[]   = '--tf-sec-scrim-m-op:' . ( $bloquix_scrim_op / 100 );
		$bloquix_classes[] = 'has-mobile-scrim';
	}
}
if ( 'none' !== $bloquix_div_top || 'none' !== $bloquix_div_bot ) {
	if ( ! empty( $attributes['dividerColor'] ) ) {
		$bloquix_div_color = bloquix_safe_css_color( $attributes['dividerColor'] );
		if ( '' !== $bloquix_div_color ) {
			$bloquix_vars[] = '--tf-sec-divider-color:' . $bloquix_div_color;
		}
	}
	$bloquix_dh = isset( $attributes['dividerHeight'] ) ? max( 0, (int) $attributes['dividerHeight'] ) : 60;
	$bloquix_vars[] = '--tf-sec-divider-h:' . $bloquix_dh . 'px';
}

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $bloquix_classes ),
		'style' => implode( ';', $bloquix_vars ),
	)
);
?>
<section <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?><?php if ( 'video' === $bloquix_bg_type ) : ?> data-pause-label="<?php echo esc_attr__( 'Pause background video', 'bloquix' ); ?>" data-play-label="<?php echo esc_attr__( 'Play background video', 'bloquix' ); ?>"<?php endif; ?>>
	<?php if ( 'none' !== $bloquix_div_top ) : ?>
		<div class="bloquix-section__divider bloquix-section__divider--top">
			<?php echo bloquix_section_divider_svg( $bloquix_div_top ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>

	<?php if ( 'image' === $bloquix_bg_type && ! empty( $attributes['bgImageUrl'] ) ) : ?>
		<div class="bloquix-section__bg" style="background-image:url('<?php echo esc_url( $attributes['bgImageUrl'] ); ?>')"></div>
	<?php elseif ( 'video' === $bloquix_bg_type && ! empty( $attributes['bgVideoUrl'] ) ) : ?>
		<?php
		$bloquix_vmime   = ! empty( $attributes['bgVideoId'] ) ? get_post_mime_type( (int) $attributes['bgVideoId'] ) : '';
		$bloquix_vposter = ! empty( $attributes['bgVideoPosterUrl'] ) ? (string) $attributes['bgVideoPosterUrl'] : '';
		$bloquix_vauto   = ! isset( $attributes['bgVideoAutoplay'] ) || (bool) $attributes['bgVideoAutoplay'];
		$bloquix_vloop   = ! isset( $attributes['bgVideoLoop'] ) || (bool) $attributes['bgVideoLoop'];
		/*
		 * Carga perezosa cuando HAY póster: el vídeo sale sin `autoplay` y con
		 * preload="none", así que el navegador no descarga nada; lo arranca view.js
		 * salvo que el visitante pida menos movimiento o el autoplay esté apagado.
		 * Tiene que decidirse aquí porque al parsear el HTML el navegador no sabe
		 * nada de prefers-reduced-motion, y con `autoplay` ya habría descargado.
		 * SIN póster se mantiene el autoplay en el HTML: si no, quedaría un hueco
		 * vacío hasta que despierte el JS.
		 */
		$bloquix_vlazy = ( '' !== $bloquix_vposter );
		?>
		<video class="bloquix-section__bg" muted playsinline aria-hidden="true"<?php
			echo $bloquix_vloop ? ' loop' : '';
			echo $bloquix_vposter ? ' poster="' . esc_url( $bloquix_vposter ) . '"' : '';
			echo ( $bloquix_vauto && ! $bloquix_vlazy ) ? ' autoplay' : '';
			echo ' preload="' . ( $bloquix_vlazy ? 'none' : 'metadata' ) . '"';
			echo ' data-tnt-autoplay="' . ( $bloquix_vauto ? '1' : '0' ) . '"';
		?>>
			<source src="<?php echo esc_url( $attributes['bgVideoUrl'] ); ?>"<?php echo $bloquix_vmime ? ' type="' . esc_attr( $bloquix_vmime ) . '"' : ''; ?> />
		</video>
	<?php elseif ( in_array( $bloquix_bg_type, array( 'color', 'gradient', 'mesh' ), true ) ) : ?>
		<div class="bloquix-section__bg"></div>
	<?php endif; ?>

	<?php if ( $bloquix_overlay ) : ?>
		<div class="bloquix-section__overlay"></div>
		<?php if ( ! empty( $attributes['overlayMobile'] ) ) : ?>
			<div class="bloquix-section__scrim"></div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="bloquix-section__inner">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado. ?>
	</div>

	<?php if ( 'none' !== $bloquix_div_bot ) : ?>
		<div class="bloquix-section__divider bloquix-section__divider--bottom">
			<?php echo bloquix_section_divider_svg( $bloquix_div_bot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG con path escapado. ?>
		</div>
	<?php endif; ?>
</section>
