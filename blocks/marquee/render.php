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

$tnt_speed     = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 30;
$tnt_gap       = isset( $attributes['gap'] ) ? max( 0, (float) $attributes['gap'] ) : 3;
$tnt_direction = ( isset( $attributes['direction'] ) && 'right' === $attributes['direction'] ) ? 'right' : 'left';
$tnt_pause     = ! empty( $attributes['pauseOnHover'] );

// --- Separador (opt-in) ---------------------------------------------------
$tnt_sep_type = isset( $attributes['separator'] ) ? sanitize_key( $attributes['separator'] ) : 'none';
$tnt_sep      = '';
if ( 'icon' === $tnt_sep_type ) {
	require_once __DIR__ . '/../icon/icons.php';
	$tnt_sep_icon = isset( $attributes['separatorIcon'] ) ? sanitize_key( $attributes['separatorIcon'] ) : '';
	$tnt_icon_svg = tunet_core_icon_svg(
		$tnt_sep_icon,
		array(
			'size'   => 0, // Lo dimensiona el CSS (1em).
			'stroke' => 2,
			'class'  => 'tunet-marquee__sep-icon',
		)
	);
	if ( '' !== $tnt_icon_svg ) {
		$tnt_sep = '<span class="tunet-marquee__sep tunet-marquee__sep--icon" aria-hidden="true">' . $tnt_icon_svg . '</span>';
	}
} elseif ( in_array( $tnt_sep_type, array( 'dot', 'dash', 'slash', 'pipe' ), true ) ) {
	$tnt_sep = '<span class="tunet-marquee__sep tunet-marquee__sep--' . $tnt_sep_type . '" aria-hidden="true"></span>';
}

// --- Ítems + separador ----------------------------------------------------
$tnt_items = '';
if ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $tnt_inner ) {
		$tnt_items .= $tnt_inner->render();
		if ( '' !== $tnt_sep ) {
			$tnt_items .= $tnt_sep;
		}
	}
} else {
	$tnt_items = $content; // Defensivo: sin inner_blocks, volcar el contenido tal cual.
}

$tnt_classes = 'tunet-marquee is-' . $tnt_direction;
if ( $tnt_pause ) {
	$tnt_classes .= ' is-pausable';
}

$tnt_style = sprintf(
	'--tnt-marquee-duration:%ss;--tnt-marquee-gap:%srem;',
	(float) $tnt_speed,
	(float) $tnt_gap
);

// Color del separador (opt-in). Sanea a un valor CSS seguro (sin ;{}: para no
// inyectar otras declaraciones). Vacío ⇒ el CSS resuelve var(--tnt-color-primary).
$tnt_sep_color = isset( $attributes['separatorColor'] ) ? (string) $attributes['separatorColor'] : '';
$tnt_sep_color = preg_replace( '/[^a-zA-Z0-9#(),.%\s\-]/', '', $tnt_sep_color );
if ( '' !== trim( (string) $tnt_sep_color ) ) {
	$tnt_style .= '--tnt-marquee-sep-color:' . $tnt_sep_color . ';';
}

$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $tnt_classes,
		'style' => $tnt_style,
	)
);
?>
<div <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida segura de WP. ?>>
	<div class="tunet-marquee__track">
		<div class="tunet-marquee__group"><?php echo $tnt_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de bloques internos ya renderizado + separador del motor. ?></div>
		<div class="tunet-marquee__group" aria-hidden="true"><?php echo $tnt_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- duplicado decorativo. ?></div>
	</div>
</div>
