<?php
/**
 * Blokino — patterns del plugin y categoría "Blokino" (U-08).
 *
 * Sirven a cualquier theme: solo usan bloques core y blokino/*, sin presets de
 * color del theme ni imágenes externas. Los colores fijos (héroes oscuros con
 * texto blanco) son valores de bloque editables, elegidos para que se lean con
 * cualquier paleta.
 *
 * Cada plantilla vive en patterns/<slug>.html, generada con el propio editor
 * (wp.blocks.serialize) para que el markup sea válido. Los textos van como
 * marcadores: @@clave@@ en HTML (se escapa con esc_html) y %%clave%% dentro del
 * JSON de atributos (se escapa como cadena JSON). Así se traducen con gettext
 * sin tocar el markup.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra la categoría de bloques/patterns y los patterns del plugin.
 */
class Blokino_Patterns {

	/** Slug de la categoría (insertador de bloques y de patterns). */
	const CATEGORY = 'blokino';

	/**
	 * Cablea los hooks.
	 */
	public function __construct() {
		add_filter( 'block_categories_all', array( $this, 'block_category' ) );
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Añade "Blokino" al insertador de bloques (los diez bloques del plugin).
	 *
	 * @param array $categories Categorías existentes.
	 * @return array
	 */
	public function block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && self::CATEGORY === $category['slug'] ) {
				return $categories;
			}
		}
		$categories[] = array(
			'slug'  => self::CATEGORY,
			'title' => __( 'Blokino', 'blokino' ),
			'icon'  => null,
		);
		return $categories;
	}

	/**
	 * Registra la categoría de patterns y cada pattern.
	 */
	public function register() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}
		register_block_pattern_category( self::CATEGORY, array( 'label' => __( 'Blokino', 'blokino' ) ) );

		foreach ( self::patterns() as $slug => $pattern ) {
			$file = BLOKINO_PATH . 'patterns/' . $slug . '.html';
			if ( ! is_readable( $file ) ) {
				continue;
			}
			register_block_pattern(
				'blokino/' . $slug,
				array(
					'title'       => $pattern['title'],
					'description' => $pattern['description'],
					'categories'  => array( self::CATEGORY ),
					'keywords'    => $pattern['keywords'],
					'content'     => self::fill( (string) file_get_contents( $file ), $pattern['strings'] ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- archivo local del plugin.
				)
			);
		}
	}

	/**
	 * Sustituye los marcadores por los textos traducidos, escapados según su sitio.
	 *
	 * @param string $markup  Plantilla.
	 * @param array  $strings clave => texto traducido.
	 * @return string
	 */
	private static function fill( $markup, $strings ) {
		$map = array();
		foreach ( $strings as $key => $text ) {
			$map[ '@@' . $key . '@@' ] = esc_html( $text );
			// Dentro del comentario del bloque: cadena JSON sin las comillas, con
			// los mismos escapes que usa WordPress al serializar atributos.
			$map[ '%%' . $key . '%%' ] = substr( wp_json_encode( $text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ), 1, -1 );
		}
		return strtr( $markup, $map );
	}

	/**
	 * Patterns del plugin con sus textos.
	 *
	 * @return array
	 */
	private static function patterns() {
		return array(
			'hero-gradient' => array(
				'title'       => __( 'Hero on an animated gradient', 'blokino' ),
				'description' => __( 'Full-width hero whose title, text and button enter one after another.', 'blokino' ),
				'keywords'    => array( 'hero', 'gradient', 'animation' ),
				'strings'     => array(
					'hg_title' => __( 'A headline that moves', 'blokino' ),
					'hg_text'  => __( 'Say what you do in one clear sentence. Everything here is editable.', 'blokino' ),
					'hg_btn'   => __( 'Start now', 'blokino' ),
				),
			),
			'hero-image'    => array(
				'title'       => __( 'Hero on a photo', 'blokino' ),
				'description' => __( 'Full-width hero with a dark gradient overlay. Choose the photo in the sidebar.', 'blokino' ),
				'keywords'    => array( 'hero', 'image', 'overlay' ),
				'strings'     => array(
					'hi_title' => __( 'Your story, front and center', 'blokino' ),
					'hi_text'  => __( 'Choose the photo in the sidebar (Background → Choose image).', 'blokino' ),
					'hi_btn'   => __( 'Learn more', 'blokino' ),
				),
			),
			'metrics'       => array(
				'title'       => __( 'Key figures', 'blokino' ),
				'description' => __( 'Three counters that count up when they scroll into view.', 'blokino' ),
				'keywords'    => array( 'counter', 'numbers', 'stats' ),
				'strings'     => array(
					'm_title' => __( 'Numbers that speak for us', 'blokino' ),
					'm_s1'    => '+',
					'm_l1'    => __( 'Projects delivered', 'blokino' ),
					'm_s2'    => '%',
					'm_l2'    => __( 'Clients who come back', 'blokino' ),
					'm_s3'    => '',
					'm_l3'    => __( 'Years in business', 'blokino' ),
				),
			),
			'logo-band'     => array(
				'title'       => __( 'Logo band', 'blokino' ),
				'description' => __( 'Names or logos scrolling in an endless band. Replace them with your clients.', 'blokino' ),
				'keywords'    => array( 'marquee', 'logos', 'clients' ),
				'strings'     => array(
					'l_label' => __( 'Trusted by teams like', 'blokino' ),
				),
			),
			'testimonials'  => array(
				'title'       => __( 'Testimonials', 'blokino' ),
				'description' => __( 'Three client quotes with name, role and rating.', 'blokino' ),
				'keywords'    => array( 'testimonials', 'reviews', 'quotes' ),
				'strings'     => array(
					't_title' => __( 'What our clients say', 'blokino' ),
					't_q1'    => __( 'They understood what we needed from the first call, and the site shows it.', 'blokino' ),
					't_n1'    => 'Laura Méndez',
					't_r1'    => __( 'Marketing lead', 'blokino' ),
					't_q2'    => __( 'Fast, clear and always one step ahead. We launched on time.', 'blokino' ),
					't_n2'    => 'Daniel Ortiz',
					't_r2'    => __( 'Founder', 'blokino' ),
					't_q3'    => __( 'Our visitors stay longer and write to us more often.', 'blokino' ),
					't_n3'    => 'Sofía Ramírez',
					't_r3'    => __( 'Operations manager', 'blokino' ),
				),
			),
			'cards'         => array(
				'title'       => __( 'Cards with hover', 'blokino' ),
				'description' => __( 'Three cards that enter one after another and lift when you point at them.', 'blokino' ),
				'keywords'    => array( 'cards', 'features', 'services', 'hover' ),
				'strings'     => array(
					'c_title' => __( 'What we do', 'blokino' ),
					'c_t1'    => __( 'Strategy', 'blokino' ),
					'c_d1'    => __( 'We start with your goals and turn them into a clear plan.', 'blokino' ),
					'c_t2'    => __( 'Design', 'blokino' ),
					'c_d2'    => __( 'Pages that look like you and guide visitors to act.', 'blokino' ),
					'c_t3'    => __( 'Care', 'blokino' ),
					'c_d3'    => __( 'We stay after launch to measure, fix and improve.', 'blokino' ),
				),
			),
		);
	}
}
