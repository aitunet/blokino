<?php
/**
 * Tunet Core · Módulo de CONTENIDO — framework declarativo de tipos de contenido.
 *
 * La funcionalidad (registrar CPTs/taxonomías/meta) vive en el motor (§2); la
 * PRESENTACIÓN (plantillas, patterns, estilos) la pone cada theme. Así el
 * contenido del usuario sobrevive a un cambio de theme.
 *
 * Antes esta clase registraba a fuego el CPT `project`. Ahora describe los tipos
 * como DATOS y los registra en bloque, de modo que un theme o un plugin pueda
 * declarar los suyos —`service`, `testimonial`, lo que sea— sin editar el motor:
 *
 *     tunet_core_register_content_type(
 *         'service',
 *         array(
 *             'labels' => array( 'singular' => 'Service', 'plural' => 'Services' ),
 *             'args'   => array( 'has_archive' => 'services' ),
 *             'meta'   => array( 'price_from' => array( 'label' => 'Price from' ) ),
 *         )
 *     );
 *
 * ...o con el filtro `tunet_core_content_types` para modificar los ajenos.
 *
 * El motor sigue trayendo de serie:
 *  - CPT `project` (portfolio) con archivo en /work, soporte de bloques (REST),
 *    imagen destacada y extracto.
 *  - Taxonomía `project_type` (filtro/categoría del portfolio).
 *  - Meta de caso de estudio (client/year/role/website) expuesta a REST para
 *    poder enlazarla con Block Bindings desde el theme (editable, no hardcode).
 *
 * @package Tunet\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra los tipos de contenido del motor y los declarados desde fuera.
 */
class Tunet_Core_Content {

	const CPT      = 'project';
	const TAXONOMY = 'project_type';

	/**
	 * Opción con la huella de las reglas de reescritura ya volcadas.
	 */
	const SIGNATURE_OPTION = 'tunet_core_content_signature';

	/**
	 * Tipos declarados desde fuera vía tunet_core_register_content_type().
	 *
	 * @var array<string,array>
	 */
	private static $declared = array();

	/**
	 * Mapa resuelto (núcleo + declarados + filtro), ya normalizado.
	 *
	 * @var array<string,array>|null
	 */
	private static $types = null;

	/**
	 * Cablea los hooks de registro.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_types' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 99 );
	}

	/**
	 * Añade un tipo de contenido a la cola de registro.
	 *
	 * Hay que llamarla ANTES de `init` (prioridad 10): en el archivo del plugin,
	 * en `plugins_loaded` o en `after_setup_theme`. Si el slug ya existe, la
	 * definición nueva se funde sección a sección con la anterior, así que se
	 * puede añadir un meta a `project` sin repetir el resto.
	 *
	 * @since 0.1.29
	 *
	 * @param string $slug Slug del post type (máx. 20 caracteres).
	 * @param array  $args Definición declarativa. Claves: `labels`, `args`,
	 *                     `taxonomies`, `meta`.
	 * @return void
	 */
	public static function declare_type( $slug, $args = array() ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! is_array( $args ) ) {
			return;
		}

		self::$declared[ $slug ] = isset( self::$declared[ $slug ] )
			? self::merge_definitions( self::$declared[ $slug ], $args )
			: $args;

		// Una declaración tardía invalida el mapa cacheado.
		self::$types = null;
	}

	/**
	 * Devuelve el mapa completo de tipos, ya validado y normalizado.
	 *
	 * @return array<string,array>
	 */
	public static function get_types() {
		if ( null !== self::$types ) {
			return self::$types;
		}

		$core = self::core_types();

		$candidates = $core;
		foreach ( self::$declared as $slug => $definition ) {
			$candidates[ $slug ] = isset( $candidates[ $slug ] )
				? self::merge_definitions( $candidates[ $slug ], $definition )
				: $definition;
		}

		/**
		 * Permite a un theme o plugin AMPLIAR o modificar los tipos de contenido.
		 *
		 * Recibe el mapa ya compuesto (núcleo + los declarados con
		 * `tunet_core_register_content_type()`) y devuelve el mapa final. Cada
		 * entrada se valida y las inválidas se descartan en silencio, para que un
		 * filtro mal escrito no tumbe el registro entero.
		 *
		 * Los tipos del núcleo NO se pueden eliminar desde el filtro (los themes
		 * que consumen el motor tienen plantillas para `project` y se quedarían
		 * sin contenido); sí se pueden modificar por slug.
		 *
		 * El resultado se cachea, así que el filtro debe estar registrado ANTES
		 * de `init` prioridad 10.
		 *
		 * @since 0.1.29
		 *
		 * @param array<string,array> $candidates Mapa de definiciones.
		 */
		$filtered = apply_filters( 'tunet_core_content_types', $candidates );

		$types = array();
		if ( is_array( $filtered ) ) {
			foreach ( $filtered as $slug => $definition ) {
				$normalized = self::normalize_type( $slug, $definition );
				if ( null !== $normalized ) {
					$types[ $normalized['slug'] ] = $normalized;
				}
			}
		}

		/*
		 * Red de seguridad: lo del núcleo vuelve si el filtro lo tiró. Vuelve la
		 * definición COMPUESTA, no la cruda del motor: si otro plugin le había
		 * añadido un meta a `project` con la función de registro, ese meta no lo
		 * puede borrar de rebote un filtro ajeno.
		 */
		foreach ( array_keys( $core ) as $slug ) {
			if ( ! isset( $types[ $slug ] ) ) {
				$normalized = self::normalize_type( $slug, $candidates[ $slug ] );
				if ( null !== $normalized ) {
					$types[ $slug ] = $normalized;
				}
			}
		}

		// Solo se cachea a partir de `init`: antes las etiquetas no están traducidas.
		if ( did_action( 'init' ) ) {
			self::$types = $types;
		}

		return $types;
	}

	/**
	 * Registra en WordPress todo lo declarado. Se dispara en `init`.
	 *
	 * @return void
	 */
	public function register_types() {
		foreach ( self::get_types() as $slug => $type ) {
			register_post_type( $slug, $type['args'] );

			foreach ( $type['taxonomies'] as $taxonomy => $taxonomy_args ) {
				register_taxonomy( $taxonomy, $slug, $taxonomy_args );
			}

			$this->register_type_meta( $slug, $type['meta'] );

			if ( is_admin() ) {
				$this->wire_admin_columns( $slug, $type['meta'] );
			}
		}
	}

	/**
	 * Registra la meta de un tipo, expuesta a REST para Block Bindings.
	 *
	 * @param string $post_type Post type.
	 * @param array  $fields    Campos ya normalizados.
	 * @return void
	 */
	private function register_type_meta( $post_type, $fields ) {
		foreach ( $fields as $key => $field ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $field['type'],
					'label'             => $field['label'],
					'description'       => $field['description'],
					'single'            => $field['single'],
					'default'           => $field['default'],
					'show_in_rest'      => $field['show_in_rest'],
					'sanitize_callback' => $field['sanitize_callback'],
					/*
					 * Capability POR OBJETO, no genérica. Antes era
					 * `current_user_can( 'edit_posts' )`, que ignora el post y responde lo
					 * mismo para cualquiera: quien pudiera editar UN post quedaba
					 * autorizado a escribir este meta en CUALQUIER otro. En el flujo REST
					 * normal no se llegaba a explotar —el controlador comprueba antes
					 * `edit_post` sobre ese ID concreto—, pero eso deja la segunda puerta
					 * abierta y dependiendo de que la primera nunca falle. El idioma
					 * correcto usa el $object_id que WP ya pasa al callback.
					 */
					'auth_callback'     => $field['auth_callback'],
				)
			);
		}
	}

	/**
	 * Añade a la lista del admin una columna por cada meta que la pida.
	 *
	 * @param string $post_type Post type.
	 * @param array  $fields    Campos ya normalizados.
	 * @return void
	 */
	private function wire_admin_columns( $post_type, $fields ) {
		$columns = array();
		foreach ( $fields as $key => $field ) {
			if ( false !== $field['admin_column'] ) {
				$columns[ $key ] = $field['admin_column'];
			}
		}

		if ( empty( $columns ) ) {
			return;
		}

		add_filter(
			"manage_{$post_type}_posts_columns",
			static function ( $existing ) use ( $columns ) {
				if ( ! is_array( $existing ) ) {
					return $existing;
				}
				// Se cuelan antes de la fecha, que siempre cierra la tabla.
				$date = isset( $existing['date'] ) ? array( 'date' => $existing['date'] ) : array();
				unset( $existing['date'] );
				return array_merge( $existing, $columns, $date );
			}
		);

		add_action(
			"manage_{$post_type}_posts_custom_column",
			static function ( $column, $post_id ) use ( $columns ) {
				if ( ! isset( $columns[ $column ] ) ) {
					return;
				}
				$value = get_post_meta( (int) $post_id, $column, true );
				echo esc_html( is_scalar( $value ) ? (string) $value : '' );
			},
			10,
			2
		);
	}

	/**
	 * Vuelca las reglas de reescritura cuando cambian los tipos registrados.
	 *
	 * Un CPT nuevo sin flush da 404 en su archivo y en sus permalinks, que es la
	 * trampa clásica de este terreno. La huella se guarda en una opción, así que
	 * el flush ocurre una sola vez por cambio, no en cada carga.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrites() {
		$signature = self::rewrite_signature();

		if ( get_option( self::SIGNATURE_OPTION ) === $signature ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::SIGNATURE_OPTION, $signature );
	}

	/**
	 * Huella determinista de todo lo que afecta a las reglas de reescritura.
	 *
	 * @return string
	 */
	private static function rewrite_signature() {
		$relevant = array();

		foreach ( self::get_types() as $slug => $type ) {
			$args = $type['args'];

			$relevant[ $slug ] = array(
				'has_archive' => isset( $args['has_archive'] ) ? $args['has_archive'] : false,
				'public'      => isset( $args['public'] ) ? $args['public'] : false,
				'rewrite'     => isset( $args['rewrite'] ) ? $args['rewrite'] : true,
				'taxonomies'  => array(),
			);

			foreach ( $type['taxonomies'] as $taxonomy => $taxonomy_args ) {
				$relevant[ $slug ]['taxonomies'][ $taxonomy ] = array(
					'hierarchical' => isset( $taxonomy_args['hierarchical'] ) ? $taxonomy_args['hierarchical'] : false,
					'public'       => isset( $taxonomy_args['public'] ) ? $taxonomy_args['public'] : true,
					'rewrite'      => isset( $taxonomy_args['rewrite'] ) ? $taxonomy_args['rewrite'] : true,
				);
			}
			ksort( $relevant[ $slug ]['taxonomies'] );
		}

		ksort( $relevant );

		return md5( (string) wp_json_encode( $relevant ) );
	}

	/**
	 * Funde dos definiciones sección a sección.
	 *
	 * `args`, `labels`, `taxonomies` y `meta` se funden por clave, de modo que
	 * añadir un meta o cambiar un solo argumento no obliga a repetir el resto.
	 *
	 * @param array $base     Definición previa.
	 * @param array $override Definición nueva.
	 * @return array
	 */
	private static function merge_definitions( $base, $override ) {
		$merged = array_merge( $base, $override );

		foreach ( array( 'labels', 'args', 'taxonomies', 'meta' ) as $section ) {
			if ( ! empty( $base[ $section ] ) && ! empty( $override[ $section ] )
				&& is_array( $base[ $section ] ) && is_array( $override[ $section ] ) ) {
				$merged[ $section ] = array_merge( $base[ $section ], $override[ $section ] );
			}
		}

		return $merged;
	}

	/**
	 * Valida y normaliza una definición. Devuelve null si no sirve.
	 *
	 * @param string $slug       Slug propuesto.
	 * @param array  $definition Definición cruda.
	 * @return array|null
	 */
	private static function normalize_type( $slug, $definition ) {
		$slug = sanitize_key( (string) $slug );

		// register_post_type() rechaza slugs vacíos o de más de 20 caracteres.
		if ( '' === $slug || strlen( $slug ) > 20 || ! is_array( $definition ) ) {
			return null;
		}

		$labels = ( isset( $definition['labels'] ) && is_array( $definition['labels'] ) ) ? $definition['labels'] : array();
		$args   = ( isset( $definition['args'] ) && is_array( $definition['args'] ) ) ? $definition['args'] : array();

		if ( empty( $args['labels'] ) ) {
			$args['labels'] = self::build_post_type_labels( $labels, $slug );
		}

		$args = array_merge(
			array(
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			),
			$args
		);

		$taxonomies = array();
		if ( ! empty( $definition['taxonomies'] ) && is_array( $definition['taxonomies'] ) ) {
			foreach ( $definition['taxonomies'] as $taxonomy_slug => $taxonomy ) {
				$normalized = self::normalize_taxonomy( $taxonomy_slug, $taxonomy );
				if ( null !== $normalized ) {
					$taxonomies[ $normalized['slug'] ] = $normalized['args'];
				}
			}
		}

		$meta = array();
		if ( ! empty( $definition['meta'] ) && is_array( $definition['meta'] ) ) {
			foreach ( $definition['meta'] as $meta_key => $field ) {
				$normalized = self::normalize_meta( $meta_key, $field );
				if ( null !== $normalized ) {
					$meta[ $normalized['key'] ] = $normalized['field'];
				}
			}
		}

		return array(
			'slug'       => $slug,
			'args'       => $args,
			'taxonomies' => $taxonomies,
			'meta'       => $meta,
		);
	}

	/**
	 * Valida y normaliza una taxonomía. Devuelve null si no sirve.
	 *
	 * @param string $slug     Slug propuesto.
	 * @param array  $taxonomy Definición cruda.
	 * @return array|null
	 */
	private static function normalize_taxonomy( $slug, $taxonomy ) {
		$slug = sanitize_key( (string) $slug );

		// register_taxonomy() rechaza slugs vacíos o de más de 32 caracteres.
		if ( '' === $slug || strlen( $slug ) > 32 || ! is_array( $taxonomy ) ) {
			return null;
		}

		$labels = ( isset( $taxonomy['labels'] ) && is_array( $taxonomy['labels'] ) ) ? $taxonomy['labels'] : array();
		$args   = ( isset( $taxonomy['args'] ) && is_array( $taxonomy['args'] ) ) ? $taxonomy['args'] : array();

		if ( empty( $args['labels'] ) ) {
			$args['labels'] = self::build_taxonomy_labels( $labels, $slug );
		}

		$args = array_merge(
			array(
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			),
			$args
		);

		return array(
			'slug' => $slug,
			'args' => $args,
		);
	}

	/**
	 * Valida y normaliza un campo meta. Devuelve null si no sirve.
	 *
	 * @param string $key   Clave meta propuesta.
	 * @param array  $field Definición cruda.
	 * @return array|null
	 */
	private static function normalize_meta( $key, $field ) {
		$key = (string) $key;

		// Las claves meta admiten mayúsculas y guiones; sanitize_key las destrozaría.
		if ( ! preg_match( '/^[A-Za-z0-9_-]{1,255}$/', $key ) || ! is_array( $field ) ) {
			return null;
		}

		$allowed = array( 'string', 'boolean', 'integer', 'number', 'array', 'object' );
		$type    = ( isset( $field['type'] ) && in_array( $field['type'], $allowed, true ) ) ? $field['type'] : 'string';

		$label = isset( $field['label'] ) ? sanitize_text_field( (string) $field['label'] ) : $key;

		$defaults = array(
			'string'  => '',
			'boolean' => false,
			'integer' => 0,
			'number'  => 0,
			'array'   => array(),
			'object'  => array(),
		);

		$sanitizers = array(
			'string'  => 'sanitize_text_field',
			'boolean' => 'rest_sanitize_boolean',
			'integer' => 'intval',
			'number'  => 'floatval',
			'array'   => null,
			'object'  => null,
		);

		$sanitize = ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) )
			? $field['sanitize_callback']
			: $sanitizers[ $type ];

		if ( isset( $field['auth_callback'] ) && is_callable( $field['auth_callback'] ) ) {
			$auth = $field['auth_callback'];
		} else {
			$capability = isset( $field['auth_capability'] ) ? (string) $field['auth_capability'] : 'edit_post';
			$auth       = static function ( $allowed, $meta_key, $object_id ) use ( $capability ) {
				return current_user_can( $capability, (int) $object_id );
			};
		}

		$admin_column = false;
		if ( ! empty( $field['admin_column'] ) ) {
			$admin_column = is_string( $field['admin_column'] )
				? sanitize_text_field( $field['admin_column'] )
				: $label;
		}

		return array(
			'key'   => $key,
			'field' => array(
				'type'              => $type,
				'label'             => $label,
				'description'       => isset( $field['description'] ) ? sanitize_text_field( (string) $field['description'] ) : $label,
				'single'            => isset( $field['single'] ) ? (bool) $field['single'] : true,
				'default'           => array_key_exists( 'default', $field ) ? $field['default'] : $defaults[ $type ],
				'show_in_rest'      => array_key_exists( 'show_in_rest', $field ) ? $field['show_in_rest'] : true,
				'sanitize_callback' => $sanitize,
				'auth_callback'     => $auth,
				'admin_column'      => $admin_column,
			),
		);
	}

	/**
	 * Compone el juego de etiquetas de un post type a partir del atajo.
	 *
	 * @param array  $labels Atajo: `singular`, `plural`, `menu`.
	 * @param string $slug   Slug, como último recurso.
	 * @return array
	 */
	private static function build_post_type_labels( $labels, $slug ) {
		$singular = isset( $labels['singular'] ) ? (string) $labels['singular'] : ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );
		$plural   = isset( $labels['plural'] ) ? (string) $labels['plural'] : $singular . 's';
		$menu     = isset( $labels['menu'] ) ? (string) $labels['menu'] : $plural;

		return array(
			'name'          => $plural,
			'singular_name' => $singular,
			'menu_name'     => $menu,
			'add_new'       => __( 'Add New', 'tunet-core' ),
			/* translators: %s: singular name of the content type. */
			'add_new_item'  => sprintf( __( 'Add New %s', 'tunet-core' ), $singular ),
			/* translators: %s: singular name of the content type. */
			'edit_item'     => sprintf( __( 'Edit %s', 'tunet-core' ), $singular ),
			/* translators: %s: singular name of the content type. */
			'new_item'      => sprintf( __( 'New %s', 'tunet-core' ), $singular ),
			/* translators: %s: singular name of the content type. */
			'view_item'     => sprintf( __( 'View %s', 'tunet-core' ), $singular ),
			/* translators: %s: plural name of the content type. */
			'search_items'  => sprintf( __( 'Search %s', 'tunet-core' ), $plural ),
			/* translators: %s: plural name of the content type. */
			'not_found'     => sprintf( __( 'No %s found', 'tunet-core' ), $plural ),
			/* translators: %s: plural name of the content type. */
			'all_items'     => sprintf( __( 'All %s', 'tunet-core' ), $plural ),
		);
	}

	/**
	 * Compone el juego de etiquetas de una taxonomía a partir del atajo.
	 *
	 * @param array  $labels Atajo: `singular`, `plural`, `menu`.
	 * @param string $slug   Slug, como último recurso.
	 * @return array
	 */
	private static function build_taxonomy_labels( $labels, $slug ) {
		$singular = isset( $labels['singular'] ) ? (string) $labels['singular'] : ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );
		$plural   = isset( $labels['plural'] ) ? (string) $labels['plural'] : $singular . 's';
		$menu     = isset( $labels['menu'] ) ? (string) $labels['menu'] : $plural;

		return array(
			'name'          => $plural,
			'singular_name' => $singular,
			'menu_name'     => $menu,
			/* translators: %s: plural name of the taxonomy. */
			'all_items'     => sprintf( __( 'All %s', 'tunet-core' ), $plural ),
			/* translators: %s: singular name of the taxonomy. */
			'edit_item'     => sprintf( __( 'Edit %s', 'tunet-core' ), $singular ),
			/* translators: %s: singular name of the taxonomy. */
			'update_item'   => sprintf( __( 'Update %s', 'tunet-core' ), $singular ),
			/* translators: %s: singular name of the taxonomy. */
			'add_new_item'  => sprintf( __( 'Add New %s', 'tunet-core' ), $singular ),
			/* translators: %s: singular name of the taxonomy. */
			'new_item_name' => sprintf( __( 'New %s Name', 'tunet-core' ), $singular ),
			/* translators: %s: plural name of the taxonomy. */
			'search_items'  => sprintf( __( 'Search %s', 'tunet-core' ), $plural ),
			/* translators: %s: plural name of the taxonomy. */
			'not_found'     => sprintf( __( 'No %s found', 'tunet-core' ), $plural ),
		);
	}

	/**
	 * Los tipos que trae el motor de serie.
	 *
	 * Se declaran con `args` completos (no con el atajo de etiquetas) para que
	 * los literales sean exactamente los que ya viajan en los catálogos de
	 * traducción: pasar `project` al framework no cambia una sola cadena.
	 *
	 * @return array<string,array>
	 */
	private static function core_types() {
		return array(
			self::CPT => array(
				'args'       => array(
					'labels'        => array(
						'name'          => _x( 'Projects', 'post type general name', 'tunet-core' ),
						'singular_name' => _x( 'Project', 'post type singular name', 'tunet-core' ),
						'menu_name'     => _x( 'Work', 'admin menu', 'tunet-core' ),
						'add_new'       => __( 'Add New', 'tunet-core' ),
						'add_new_item'  => __( 'Add New Project', 'tunet-core' ),
						'edit_item'     => __( 'Edit Project', 'tunet-core' ),
						'new_item'      => __( 'New Project', 'tunet-core' ),
						'view_item'     => __( 'View Project', 'tunet-core' ),
						'search_items'  => __( 'Search Projects', 'tunet-core' ),
						'not_found'     => __( 'No projects found', 'tunet-core' ),
						'all_items'     => __( 'All Projects', 'tunet-core' ),
					),
					'public'        => true,
					'has_archive'   => 'work',
					'rewrite'       => array(
						'slug'       => 'work',
						'with_front' => false,
					),
					'menu_icon'     => 'dashicons-portfolio',
					'menu_position' => 5,
					'show_in_rest'  => true,
					'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
					'taxonomies'    => array( self::TAXONOMY ),
				),
				'taxonomies' => array(
					self::TAXONOMY => array(
						'args' => array(
							'labels'            => array(
								'name'          => _x( 'Project Types', 'taxonomy general name', 'tunet-core' ),
								'singular_name' => _x( 'Project Type', 'taxonomy singular name', 'tunet-core' ),
								'menu_name'     => __( 'Types', 'tunet-core' ),
							),
							'public'            => true,
							'hierarchical'      => true,
							'show_in_rest'      => true,
							'show_admin_column' => true,
							'rewrite'           => array(
								'slug'       => 'work-type',
								'with_front' => false,
							),
						),
					),
				),
				'meta'       => array(
					'tunet_project_client'  => array(
						'label' => __( 'Client', 'tunet-core' ),
					),
					'tunet_project_year'    => array(
						'label' => __( 'Year', 'tunet-core' ),
					),
					'tunet_project_role'    => array(
						'label' => __( 'Role', 'tunet-core' ),
					),
					'tunet_project_website' => array(
						'label'             => __( 'Website', 'tunet-core' ),
						// The website field is a URL → sanitize as one, the rest as text.
						'sanitize_callback' => 'sanitize_url',
					),
				),
			),
		);
	}
}
