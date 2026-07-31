<?php
/**
 * Render del block tunet/marquee (dinámico).
 *
 * Itera los bloques internos e interleava un separador tras cada ítem (incluido
 * el último → también cae en la costura del loop). Duplica el contenido en dos
 * grupos (el 2º aria-hidden) para el loop sin costuras con translateX(-50%).
 * Velocidad, dirección, separación, separador y color salen de los atributos;
 * nada hardcodeado en el motor.
 *
 * Variables disponibles: $attributes, $content, $block.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tunet_speed     = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 30;
$tunet_gap       = isset( $attributes['gap'] ) ? max( 0, (float) $attributes['gap'] ) : 3;
$tunet_direction = ( isset( $attributes['direction'] ) && 'right' === $attributes['direction'] ) ? 'right' : 'left';
$tunet_pause     = ! empty( $attributes['pauseOnHover'] );

// --- Separador (opt-in) ---------------------------------------------------
$tunet_sep_type = isset( $attributes['separator'] ) ? sanitize_key( $attributes['separator'] ) : 'none';
$tunet_sep      = '';
if ( 'icon' === $tunet_sep_type ) {
	require_once __DIR__ . '/../icon/icons.php';
	$tunet_sep_icon = isset( $attributes['separatorIcon'] ) ? sanitize_key( $attributes['separatorIcon'] ) : '';
	$tunet_icon_svg = tunet_core_icon_svg(
		$tunet_sep_icon,
		array(
			'size'   => 0, // Lo dimensiona el CSS (1em).
			'stroke' => 2,
			'class'  => 'tunet-marquee__sep-icon',
		)
	);
	if ( '' !== $tunet_icon_svg ) {
		$tunet_sep = '<span class="tunet-marquee__sep tunet-marquee__sep--icon" aria-hidden="true">' . $tunet_icon_svg . '</span>';
	}
} elseif ( in_array( $tunet_sep_type, array( 'dot', 'dash', 'slash', 'pipe' ), true ) ) {
	$tunet_sep = '<span class="tunet-marquee__sep tunet-marquee__sep--' . $tunet_sep_type . '" aria-hidden="true"></span>';
}

// --- Ítems + separador ----------------------------------------------------
$tunet_items = '';
if ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $tunet_inner ) {
		$tunet_items .= $tunet_inner->render();
		if ( '' !== $tunet_sep ) {
			$tunet_items .= $tunet_sep;
		}
	}
} else {
	$tunet_items = $content; // Defensivo: sin inner_blocks, volcar el contenido tal cual.
}

$tunet_classes = 'tunet-marquee is-' . $tunet_direction;
if ( $tunet_pause ) {
	$tunet_classes .= ' is-pausable';
}

$tunet_style = sprintf(
	'--tnt-marquee-duration:%ss;--tnt-marquee-gap:%srem;',
	(float) $tunet_speed,
	(float) $tunet_gap
);

// Color del separador (opt-in). Se normaliza a un valor que sobreviva a
// safecss_filter_attr: el saneador de abajo dejaba pasar rgba(), pero WP lo
// DESCARTA después en el inline-style y el separador volvía en silencio al
// primary del theme (el control lleva enableAlpha, así que pasaba de verdad).
// Vacío ⇒ el CSS resuelve var(--tnt-color-primary), que es el degradado correcto.
$tunet_sep_color = isset( $attributes['separatorColor'] ) ? (string) $attributes['separatorColor'] : '';
$tunet_sep_color = tunet_core_safe_css_color( $tunet_sep_color );
$tunet_sep_color = preg_replace( '/[^a-zA-Z0-9#(),.%\s\-]/', '', (string) $tunet_sep_color );
if ( 'none' !== $tunet_sep_type && '' !== trim( (string) $tunet_sep_color ) ) {
	$tunet_style .= '--tnt-marquee-sep-color:' . $tunet_sep_color . ';';
}

$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $tunet_classes,
		'style' => $tunet_style,
	)
);
?>
<div <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?> data-pause-label="<?php echo esc_attr__( 'Pause', 'tunet-core' ); ?>" data-play-label="<?php echo esc_attr__( 'Play', 'tunet-core' ); ?>">
	<div class="tunet-marquee__viewport">
		<div class="tunet-marquee__track">
			<div class="tunet-marquee__group"><?php echo $tunet_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado + separador del motor. ?></div>
			<div class="tunet-marquee__group" aria-hidden="true" inert><?php echo $tunet_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- duplicado decorativo. ?></div>
		</div>
	</div>
	<?php /* El control de pausa/play (WCAG 2.2.2) lo inyecta view.js (progressive enhancement); sin JS no hay animación problemática que pausar en la mayoría de casos, y con reduced-motion la banda es estática. */ ?>
</div>
