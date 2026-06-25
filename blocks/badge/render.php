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

$tnt_text    = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
$tnt_url     = isset( $attributes['url'] ) ? esc_url( $attributes['url'] ) : '';
$tnt_speed   = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 18;
$tnt_reverse = ! empty( $attributes['reverse'] );

$tnt_classes = 'tunet-badge';
if ( $tnt_reverse ) {
	$tnt_classes .= ' is-reverse';
}

$tnt_style   = sprintf( '--tnt-badge-speed:%ss;', (float) $tnt_speed );
$tnt_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $tnt_classes,
		'style' => $tnt_style,
	)
);

$tnt_tag  = $tnt_url ? 'a' : 'div';
$tnt_href = $tnt_url ? ' href="' . $tnt_url . '"' : '';
$tnt_uid  = 'tnt-badge-' . wp_unique_id();
?>
<<?php echo esc_attr( $tnt_tag ) . $tnt_href; ?> <?php echo $tnt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="tunet-badge__ring" aria-hidden="true">
		<svg viewBox="0 0 100 100" width="100%" height="100%">
			<defs>
				<path id="<?php echo esc_attr( $tnt_uid ); ?>" d="M 50,50 m -37,0 a 37,37 0 1,1 74,0 a 37,37 0 1,1 -74,0" />
			</defs>
			<text><textPath href="#<?php echo esc_attr( $tnt_uid ); ?>"><?php echo esc_html( $tnt_text ); ?></textPath></text>
		</svg>
	</span>
	<span class="tunet-badge__arrow" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17 17 7M9 7h8v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</span>
	<?php if ( $tnt_text ) : ?><span class="screen-reader-text"><?php echo esc_html( trim( $tnt_text, ' ·' ) ); ?></span><?php endif; ?>
</<?php echo esc_attr( $tnt_tag ); ?>>
