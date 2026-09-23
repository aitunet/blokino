<?php
/**
 * Render del block blokino/badge (dinámico).
 *
 * Badge circular: el texto (atributo) recorre un anillo SVG y rota con CSS; en
 * el centro, una flecha. Si hay url, el wrapper es <a>. Colores de tokens
 * --tnt-*; velocidad y sentido por atributos. Respeta prefers-reduced-motion.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blokino_text    = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
$blokino_url     = isset( $attributes['url'] ) ? esc_url( $attributes['url'] ) : '';
$blokino_speed   = isset( $attributes['speed'] ) ? max( 1, (float) $attributes['speed'] ) : 18;
$blokino_reverse = ! empty( $attributes['reverse'] );

$blokino_classes = 'blokino-badge';
if ( $blokino_reverse ) {
	$blokino_classes .= ' is-reverse';
}

$blokino_style   = sprintf( '--tnt-badge-speed:%ss;', (float) $blokino_speed );
$blokino_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $blokino_classes,
		'style' => $blokino_style,
	)
);

$blokino_tag  = $blokino_url ? 'a' : 'div';
$blokino_href = $blokino_url ? ' href="' . $blokino_url . '"' : '';
$blokino_uid  = 'tnt-badge-' . wp_unique_id();
$blokino_sr   = $blokino_text ? trim( $blokino_text, ' ·' ) : '';
// A link must always have an accessible name: fall back when the caption is cleared.
$blokino_aria = ( $blokino_url && '' === $blokino_sr ) ? ' aria-label="' . esc_attr__( 'Learn more', 'blokino' ) . '"' : '';
?>
<<?php echo esc_attr( $blokino_tag ) . $blokino_href . $blokino_aria; ?> <?php echo $blokino_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="blokino-badge__ring" aria-hidden="true">
		<svg viewBox="0 0 100 100" width="100%" height="100%">
			<defs>
				<path id="<?php echo esc_attr( $blokino_uid ); ?>" d="M 50,50 m -37,0 a 37,37 0 1,1 74,0 a 37,37 0 1,1 -74,0" />
			</defs>
			<text><textPath href="#<?php echo esc_attr( $blokino_uid ); ?>"><?php echo esc_html( $blokino_text ); ?></textPath></text>
		</svg>
	</span>
	<span class="blokino-badge__arrow" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17 17 7M9 7h8v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</span>
	<?php if ( '' !== $blokino_sr ) : ?><span class="blokino-sr-only"><?php echo esc_html( $blokino_sr ); ?></span><?php endif; ?>
</<?php echo esc_attr( $blokino_tag ); ?>>
