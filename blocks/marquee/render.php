<?php
/**
 * Render del block bloquix/marquee (dinámico).
 *
 * Itera los bloques internos e interleava un separador tras cada ítem (incluido
 * el último → también cae en la costura del loop). Duplica el contenido en dos
 * grupos (el 2º aria-hidden) para el loop sin costuras con translateX(-50%).
 * Velocidad, dirección, separación, separador y color salen de los atributos;
 * nada hardcodeado en el motor.
 *
 * Variables disponibles: $attributes, $content, $block.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bloquix_speed     = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 30;
$bloquix_gap       = isset( $attributes['gap'] ) ? max( 0, (float) $attributes['gap'] ) : 3;
$bloquix_direction = ( isset( $attributes['direction'] ) && 'right' === $attributes['direction'] ) ? 'right' : 'left';
$bloquix_pause     = ! empty( $attributes['pauseOnHover'] );

// --- Separador (opt-in) ---------------------------------------------------
$bloquix_sep_type = isset( $attributes['separator'] ) ? sanitize_key( $attributes['separator'] ) : 'none';
$bloquix_sep      = '';
if ( 'icon' === $bloquix_sep_type ) {
	require_once __DIR__ . '/../icon/icons.php';
	$bloquix_sep_icon = isset( $attributes['separatorIcon'] ) ? sanitize_key( $attributes['separatorIcon'] ) : '';
	$bloquix_icon_svg = bloquix_icon_svg(
		$bloquix_sep_icon,
		array(
			'size'   => 0, // Lo dimensiona el CSS (1em).
			'stroke' => 2,
			'class'  => 'bloquix-marquee__sep-icon',
		)
	);
	if ( '' !== $bloquix_icon_svg ) {
		$bloquix_sep = '<span class="bloquix-marquee__sep bloquix-marquee__sep--icon" aria-hidden="true">' . $bloquix_icon_svg . '</span>';
	}
} elseif ( in_array( $bloquix_sep_type, array( 'dot', 'dash', 'slash', 'pipe' ), true ) ) {
	$bloquix_sep = '<span class="bloquix-marquee__sep bloquix-marquee__sep--' . $bloquix_sep_type . '" aria-hidden="true"></span>';
}

// --- Ítems + separador ----------------------------------------------------
$bloquix_items = '';
if ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $bloquix_inner ) {
		$bloquix_items .= $bloquix_inner->render();
		if ( '' !== $bloquix_sep ) {
			$bloquix_items .= $bloquix_sep;
		}
	}
} else {
	$bloquix_items = $content; // Defensivo: sin inner_blocks, volcar el contenido tal cual.
}

$bloquix_classes = 'bloquix-marquee is-' . $bloquix_direction;
if ( $bloquix_pause ) {
	$bloquix_classes .= ' is-pausable';
}

$bloquix_style = sprintf(
	'--tnt-marquee-duration:%ss;--tnt-marquee-gap:%srem;',
	(float) $bloquix_speed,
	(float) $bloquix_gap
);

// Color del separador (opt-in). Se normaliza a un valor que sobreviva a
// safecss_filter_attr: el saneador de abajo dejaba pasar rgba(), pero WP lo
// DESCARTA después en el inline-style y el separador volvía en silencio al
// primary del theme (el control lleva enableAlpha, así que pasaba de verdad).
// Vacío ⇒ el CSS resuelve var(--tnt-color-primary), que es el degradado correcto.
$bloquix_sep_color = isset( $attributes['separatorColor'] ) ? (string) $attributes['separatorColor'] : '';
$bloquix_sep_color = bloquix_safe_css_color( $bloquix_sep_color );
$bloquix_sep_color = preg_replace( '/[^a-zA-Z0-9#(),.%\s\-]/', '', (string) $bloquix_sep_color );
if ( 'none' !== $bloquix_sep_type && '' !== trim( (string) $bloquix_sep_color ) ) {
	$bloquix_style .= '--tnt-marquee-sep-color:' . $bloquix_sep_color . ';';
}

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $bloquix_classes,
		'style' => $bloquix_style,
	)
);
?>
<div <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?> data-pause-label="<?php echo esc_attr__( 'Pause', 'bloquix' ); ?>" data-play-label="<?php echo esc_attr__( 'Play', 'bloquix' ); ?>">
	<div class="bloquix-marquee__viewport">
		<div class="bloquix-marquee__track">
			<div class="bloquix-marquee__group"><?php echo $bloquix_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado + separador del motor. ?></div>
			<div class="bloquix-marquee__group" aria-hidden="true" inert><?php echo $bloquix_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- duplicado decorativo. ?></div>
		</div>
	</div>
	<?php /* El control de pausa/play (WCAG 2.2.2) lo inyecta view.js (progressive enhancement); sin JS no hay animación problemática que pausar en la mayoría de casos, y con reduced-motion la banda es estática. */ ?>
</div>
