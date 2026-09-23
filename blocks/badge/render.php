<?php
/**
 * Render del block bloquix/badge (dinámico).
 *
 * Badge circular: el texto (atributo) recorre un anillo SVG y rota con CSS; en
 * el centro, una flecha. Si hay url, el wrapper es <a>. Colores de tokens
 * --tnt-*; velocidad y sentido por atributos. Respeta prefers-reduced-motion.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bloquix_text    = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
$bloquix_url     = isset( $attributes['url'] ) ? esc_url( $attributes['url'] ) : '';
$bloquix_speed   = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 18;
$bloquix_reverse = ! empty( $attributes['reverse'] );

$bloquix_classes = 'bloquix-badge';
if ( $bloquix_reverse ) {
	$bloquix_classes .= ' is-reverse';
}

$bloquix_style   = sprintf( '--tnt-badge-speed:%ss;', (float) $bloquix_speed );
$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $bloquix_classes,
		'style' => $bloquix_style,
	)
);

$bloquix_tag  = $bloquix_url ? 'a' : 'div';
$bloquix_href = $bloquix_url ? ' href="' . $bloquix_url . '"' : '';
$bloquix_uid  = 'tnt-badge-' . wp_unique_id();
$bloquix_sr   = $bloquix_text ? trim( $bloquix_text, ' ·' ) : '';
// A link must always have an accessible name: fall back when the caption is cleared.
$bloquix_aria = ( $bloquix_url && '' === $bloquix_sr ) ? ' aria-label="' . esc_attr__( 'Learn more', 'bloquix' ) . '"' : '';
?>
<<?php echo esc_attr( $bloquix_tag ) . $bloquix_href . $bloquix_aria; ?> <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="bloquix-badge__ring" aria-hidden="true">
		<svg viewBox="0 0 100 100" width="100%" height="100%">
			<defs>
				<path id="<?php echo esc_attr( $bloquix_uid ); ?>" d="M 50,50 m -37,0 a 37,37 0 1,1 74,0 a 37,37 0 1,1 -74,0" />
			</defs>
			<text><textPath href="#<?php echo esc_attr( $bloquix_uid ); ?>"><?php echo esc_html( $bloquix_text ); ?></textPath></text>
		</svg>
	</span>
	<span class="bloquix-badge__arrow" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17 17 7M9 7h8v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</span>
	<?php if ( '' !== $bloquix_sr ) : ?><span class="bloquix-sr-only"><?php echo esc_html( $bloquix_sr ); ?></span><?php endif; ?>
</<?php echo esc_attr( $bloquix_tag ); ?>>
