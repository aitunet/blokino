<?php
/**
 * Render del block bloquix/breadcrumbs (dinámico).
 *
 * Rastro desde la home hasta la página actual, sin depender de ningún plugin de
 * SEO. Cubre página, entrada, CPT (con su archivo si lo tiene), taxonomía,
 * archivo de fechas, autor, búsqueda y 404.
 *
 * El marcado es <nav><ol>: la lista ordenada es lo que hace que un lector de
 * pantalla anuncie "1 de 3" y sepa dónde está en el rastro. El separador se pinta
 * con ::before en CSS y va aria-hidden, para que no se lea "barra" entre cada
 * eslabón.
 *
 * Emite además JSON-LD BreadcrumbList (opt-out por atributo), que es la mitad del
 * valor del bloque: Google usa ese esquema para el rastro del resultado.
 *
 * @package Bloquix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bloquix_breadcrumb_trail' ) ) {
	/**
	 * Construye el rastro como lista de pares [label, url].
	 *
	 * La URL del último eslabón va vacía: es la página actual y no se enlaza.
	 *
	 * @param array $args home_label(string).
	 * @return array<int,array{label:string,url:string}>
	 */
	function bloquix_breadcrumb_trail( $args = array() ) {
		$home_label = isset( $args['home_label'] ) && '' !== $args['home_label']
			? (string) $args['home_label']
			: __( 'Home', 'bloquix' );

		$trail = array(
			array(
				'label' => $home_label,
				'url'   => home_url( '/' ),
			),
		);

		if ( is_front_page() ) {
			return $trail;
		}

		if ( is_home() ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id ) {
				$trail[] = array(
					'label' => get_the_title( $blog_id ),
					'url'   => '',
				);
			}
			return $trail;
		}

		if ( is_search() ) {
			$trail[] = array(
				/* translators: %s: search query. */
				'label' => sprintf( __( 'Search: %s', 'bloquix' ), get_search_query() ),
				'url'   => '',
			);
			return $trail;
		}

		if ( is_404() ) {
			$trail[] = array(
				'label' => __( 'Not found', 'bloquix' ),
				'url'   => '',
			);
			return $trail;
		}

		if ( is_author() ) {
			$trail[] = array(
				'label' => get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ),
				'url'   => '',
			);
			return $trail;
		}

		if ( is_year() || is_month() || is_day() ) {
			$trail[] = array(
				'label' => get_the_archive_title(),
				'url'   => '',
			);
			return $trail;
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				// Las taxonomías jerárquicas traen padres: se recorren de arriba abajo.
				$ancestors = array_reverse( (array) get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor = get_term( (int) $ancestor_id, $term->taxonomy );
					if ( $ancestor instanceof WP_Term ) {
						$trail[] = array(
							'label' => $ancestor->name,
							'url'   => (string) get_term_link( $ancestor ),
						);
					}
				}
				$trail[] = array(
					'label' => $term->name,
					'url'   => '',
				);
			}
			return $trail;
		}

		if ( is_post_type_archive() ) {
			$trail[] = array(
				'label' => post_type_archive_title( '', false ),
				'url'   => '',
			);
			return $trail;
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( ! $post instanceof WP_Post ) {
				return $trail;
			}

			// Un CPT con archivo propio gana un eslabón: /work antes del caso.
			$type = get_post_type_object( $post->post_type );
			if ( $type && ! empty( $type->has_archive ) && 'post' !== $post->post_type ) {
				$archive = get_post_type_archive_link( $post->post_type );
				if ( $archive ) {
					$trail[] = array(
						'label' => $type->labels->name,
						'url'   => (string) $archive,
					);
				}
			}

			// Las entradas cuelgan de la página de blog, si la hay.
			if ( 'post' === $post->post_type ) {
				$blog_id = (int) get_option( 'page_for_posts' );
				if ( $blog_id ) {
					$trail[] = array(
						'label' => get_the_title( $blog_id ),
						'url'   => (string) get_permalink( $blog_id ),
					);
				}
			}

			// Páginas anidadas: toda la rama, no solo el padre inmediato.
			foreach ( array_reverse( (array) get_post_ancestors( $post ) ) as $ancestor_id ) {
				$trail[] = array(
					'label' => get_the_title( (int) $ancestor_id ),
					'url'   => (string) get_permalink( (int) $ancestor_id ),
				);
			}

			$trail[] = array(
				'label' => get_the_title( $post ),
				'url'   => '',
			);
		}

		/**
		 * Permite reescribir el rastro completo antes de pintarlo.
		 *
		 * @since 0.1.28
		 *
		 * @param array $trail Lista de pares label/url; el último sin url.
		 */
		return apply_filters( 'bloquix_breadcrumb_trail', $trail );
	}
}

$bloquix_trail = bloquix_breadcrumb_trail(
	array( 'home_label' => isset( $attributes['homeLabel'] ) ? (string) $attributes['homeLabel'] : '' )
);

// Un solo eslabón es la home: un rastro de un elemento no informa de nada.
if ( count( $bloquix_trail ) < 2 ) {
	return;
}

$bloquix_show_current = ! isset( $attributes['showCurrent'] ) || ! empty( $attributes['showCurrent'] );
if ( ! $bloquix_show_current ) {
	array_pop( $bloquix_trail );
	if ( count( $bloquix_trail ) < 2 ) {
		return;
	}
}

$bloquix_separator = isset( $attributes['separator'] ) && '' !== $attributes['separator']
	? (string) $attributes['separator']
	: '/';

$bloquix_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'bloquix-breadcrumbs',
		'style' => '--tnt-breadcrumb-sep:"' . esc_attr( $bloquix_separator ) . '";',
	)
);

$bloquix_last = count( $bloquix_trail ) - 1;
?>
<nav <?php echo $bloquix_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr__( 'Breadcrumb', 'bloquix' ); ?>">
	<ol class="bloquix-breadcrumbs__list">
		<?php foreach ( $bloquix_trail as $bloquix_i => $bloquix_crumb ) : ?>
			<li class="bloquix-breadcrumbs__item">
				<?php if ( '' !== $bloquix_crumb['url'] && $bloquix_i !== $bloquix_last ) : ?>
					<a class="bloquix-breadcrumbs__link" href="<?php echo esc_url( $bloquix_crumb['url'] ); ?>"><?php echo esc_html( $bloquix_crumb['label'] ); ?></a>
				<?php else : ?>
					<span class="bloquix-breadcrumbs__current" aria-current="page"><?php echo esc_html( $bloquix_crumb['label'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
<?php
if ( isset( $attributes['structuredData'] ) && ! $attributes['structuredData'] ) {
	return;
}

$bloquix_items = array();
foreach ( $bloquix_trail as $bloquix_i => $bloquix_crumb ) {
	$bloquix_item = array(
		'@type'    => 'ListItem',
		'position' => $bloquix_i + 1,
		'name'     => $bloquix_crumb['label'],
	);
	if ( '' !== $bloquix_crumb['url'] ) {
		$bloquix_item['item'] = $bloquix_crumb['url'];
	}
	$bloquix_items[] = $bloquix_item;
}

$bloquix_jsonld = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => $bloquix_items,
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $bloquix_jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
