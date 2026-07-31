<?php
/**
 * Render del block tunet/badge (dinámico).
 *
 * Badge circular: el texto (atributo) recorre un anillo SVG y rota con CSS; en
 * el centro, una flecha. Si hay url, el wrapper es <a>. Colores de tokens
 * --tnt-*; velocidad y sentido por atributos. Respeta prefers-reduced-motion.
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tunet_text    = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
$tunet_url     = isset( $attributes['url'] ) ? esc_url( $attributes['url'] ) : '';
$tunet_speed   = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 18;
$tunet_reverse = ! empty( $attributes['reverse'] );

$tunet_classes = 'tunet-badge';
if ( $tunet_reverse ) {
	$tunet_classes .= ' is-reverse';
}

$tunet_style   = sprintf( '--tnt-badge-speed:%ss;', (float) $tunet_speed );
$tunet_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $tunet_classes,
		'style' => $tunet_style,
	)
);

$tunet_tag  = $tunet_url ? 'a' : 'div';
$tunet_href = $tunet_url ? ' href="' . $tunet_url . '"' : '';
$tunet_uid  = 'tnt-badge-' . wp_unique_id();
$tunet_sr   = $tunet_text ? trim( $tunet_text, ' ·' ) : '';
// A link must always have an accessible name: fall back when the caption is cleared.
$tunet_aria = ( $tunet_url && '' === $tunet_sr ) ? ' aria-label="' . esc_attr__( 'Learn more', 'tunet-core' ) . '"' : '';
?>
<<?php echo esc_attr( $tunet_tag ) . $tunet_href . $tunet_aria; ?> <?php echo $tunet_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="tunet-badge__ring" aria-hidden="true">
		<svg viewBox="0 0 100 100" width="100%" height="100%">
			<defs>
				<path id="<?php echo esc_attr( $tunet_uid ); ?>" d="M 50,50 m -37,0 a 37,37 0 1,1 74,0 a 37,37 0 1,1 -74,0" />
			</defs>
			<text><textPath href="#<?php echo esc_attr( $tunet_uid ); ?>"><?php echo esc_html( $tunet_text ); ?></textPath></text>
		</svg>
	</span>
	<span class="tunet-badge__arrow" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17 17 7M9 7h8v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</span>
	<?php if ( '' !== $tunet_sr ) : ?><span class="tunet-sr-only"><?php echo esc_html( $tunet_sr ); ?></span><?php endif; ?>
</<?php echo esc_attr( $tunet_tag ); ?>>
