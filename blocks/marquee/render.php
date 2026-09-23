<?php
/**
 * Render del block blokino/marquee (dinámico).
 *
 * Itera los bloques internos e interleava un separador tras cada ítem (incluido
 * el último → también cae en la costura del loop). Duplica el contenido en dos
 * grupos (el 2º aria-hidden) para el loop sin costuras con translateX(-50%).
 * Velocidad, dirección, separación, separador y color salen de los atributos;
 * nada hardcodeado en el motor.
 *
 * Variables disponibles: $attributes, $content, $block.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blokino_speed     = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 30;
$blokino_gap       = isset( $attributes['gap'] ) ? max( 0, (float) $attributes['gap'] ) : 3;
$blokino_direction = ( isset( $attributes['direction'] ) && 'right' === $attributes['direction'] ) ? 'right' : 'left';
$blokino_pause     = ! empty( $attributes['pauseOnHover'] );

// --- Separador (opt-in) ---------------------------------------------------
$blokino_sep_type = isset( $attributes['separator'] ) ? sanitize_key( $attributes['separator'] ) : 'none';
$blokino_sep      = '';
if ( 'icon' === $blokino_sep_type ) {
	require_once __DIR__ . '/../icon/icons.php';
	$blokino_sep_icon = isset( $attributes['separatorIcon'] ) ? sanitize_key( $attributes['separatorIcon'] ) : '';
	$blokino_icon_svg = blokino_icon_svg(
		$blokino_sep_icon,
		array(
			'size'   => 0, // Lo dimensiona el CSS (1em).
			'stroke' => 2,
			'class'  => 'blokino-marquee__sep-icon',
		)
	);
	if ( '' !== $blokino_icon_svg ) {
		$blokino_sep = '<span class="blokino-marquee__sep blokino-marquee__sep--icon" aria-hidden="true">' . $blokino_icon_svg . '</span>';
	}
} elseif ( in_array( $blokino_sep_type, array( 'dot', 'dash', 'slash', 'pipe' ), true ) ) {
	$blokino_sep = '<span class="blokino-marquee__sep blokino-marquee__sep--' . $blokino_sep_type . '" aria-hidden="true"></span>';
}

// --- Ítems + separador ----------------------------------------------------
$blokino_items = '';
if ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $blokino_inner ) {
		$blokino_items .= $blokino_inner->render();
		if ( '' !== $blokino_sep ) {
			$blokino_items .= $blokino_sep;
		}
	}
} else {
	$blokino_items = $content; // Defensivo: sin inner_blocks, volcar el contenido tal cual.
}

$blokino_classes = 'blokino-marquee is-' . $blokino_direction;
if ( $blokino_pause ) {
	$blokino_classes .= ' is-pausable';
}

$blokino_style = sprintf(
	'--tnt-marquee-duration:%ss;--tnt-marquee-gap:%srem;',
	(float) $blokino_speed,
	(float) $blokino_gap
);

// Color del separador (opt-in). Se normaliza a un valor que sobreviva a
// safecss_filter_attr: el saneador de abajo dejaba pasar rgba(), pero WP lo
// DESCARTA después en el inline-style y el separador volvía en silencio al
// primary del theme (el control lleva enableAlpha, así que pasaba de verdad).
// Vacío ⇒ el CSS resuelve var(--tnt-color-primary), que es el degradado correcto.
$blokino_sep_color = isset( $attributes['separatorColor'] ) ? (string) $attributes['separatorColor'] : '';
$blokino_sep_color = blokino_safe_css_color( $blokino_sep_color );
$blokino_sep_color = preg_replace( '/[^a-zA-Z0-9#(),.%\s\-]/', '', (string) $blokino_sep_color );
if ( 'none' !== $blokino_sep_type && '' !== trim( (string) $blokino_sep_color ) ) {
	$blokino_style .= '--tnt-marquee-sep-color:' . $blokino_sep_color . ';';
}

$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $blokino_classes,
		'style' => $blokino_style,
	)
);
?>
<div <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?> data-pause-label="<?php echo esc_attr__( 'Pause', 'blokino' ); ?>" data-play-label="<?php echo esc_attr__( 'Play', 'blokino' ); ?>">
	<div class="blokino-marquee__viewport">
		<div class="blokino-marquee__track">
			<div class="blokino-marquee__group"><?php echo $blokino_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado + separador del motor. ?></div>
			<div class="blokino-marquee__group" aria-hidden="true" inert><?php echo $blokino_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- duplicado decorativo. ?></div>
		</div>
	</div>
	<?php /* El control de pausa/play (WCAG 2.2.2) lo inyecta view.js (progressive enhancement); sin JS no hay animación problemática que pausar en la mayoría de casos, y con reduced-motion la banda es estática. */ ?>
</div>
