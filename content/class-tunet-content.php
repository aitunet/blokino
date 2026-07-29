<?php
/**
 * Tunet Core · Módulo de CONTENIDO — tipos de contenido compartidos del motor.
 *
 * La funcionalidad (registrar CPTs/taxonomías) vive en el motor (§2); la
 * PRESENTACIÓN (plantillas, patterns, estilos) la pone cada theme. Así el
 * contenido del usuario sobrevive a un cambio de theme.
 *
 * Registra:
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
 * Registra los tipos de contenido del motor.
 */
class Tunet_Core_Content {

	const CPT      = 'project';
	const TAXONOMY = 'project_type';

	/**
	 * Cablea los hooks de registro.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * CPT `project` — portfolio. Archivo en /work.
	 */
	public function register_cpt() {
		$labels = array(
			'name'               => _x( 'Projects', 'post type general name', 'tunet' ),
			'singular_name'      => _x( 'Project', 'post type singular name', 'tunet' ),
			'menu_name'          => _x( 'Work', 'admin menu', 'tunet' ),
			'add_new'            => __( 'Add New', 'tunet' ),
			'add_new_item'       => __( 'Add New Project', 'tunet' ),
			'edit_item'          => __( 'Edit Project', 'tunet' ),
			'new_item'           => __( 'New Project', 'tunet' ),
			'view_item'          => __( 'View Project', 'tunet' ),
			'search_items'       => __( 'Search Projects', 'tunet' ),
			'not_found'          => __( 'No projects found', 'tunet' ),
			'all_items'          => __( 'All Projects', 'tunet' ),
		);

		register_post_type(
			self::CPT,
			array(
				'labels'        => $labels,
				'public'        => true,
				'has_archive'   => 'work',
				'rewrite'       => array( 'slug' => 'work', 'with_front' => false ),
				'menu_icon'     => 'dashicons-portfolio',
				'menu_position' => 5,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
				'taxonomies'    => array( self::TAXONOMY ),
			)
		);
	}

	/**
	 * Taxonomía `project_type` (p. ej. Web, Brand, Product, Motion).
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::CPT,
			array(
				'labels'            => array(
					'name'          => _x( 'Project Types', 'taxonomy general name', 'tunet' ),
					'singular_name' => _x( 'Project Type', 'taxonomy singular name', 'tunet' ),
					'menu_name'     => __( 'Types', 'tunet' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'work-type', 'with_front' => false ),
			)
		);
	}

	/**
	 * Meta del caso de estudio, expuesta a REST (para Block Bindings en el theme).
	 */
	public function register_meta() {
		$fields = array(
			'tunet_project_client'  => __( 'Client', 'tunet' ),
			'tunet_project_year'    => __( 'Year', 'tunet' ),
			'tunet_project_role'    => __( 'Role', 'tunet' ),
			'tunet_project_website' => __( 'Website', 'tunet' ),
		);
		foreach ( $fields as $key => $label ) {
			register_post_meta(
				self::CPT,
				$key,
				array(
					'type'         => 'string',
					'description'  => $label,
					'single'       => true,
					'default'      => '',
					'show_in_rest' => true,
					// The website field is a URL → sanitize as one (esc_url_raw); the rest as text.
					'sanitize_callback' => ( 'tunet_project_website' === $key ) ? 'sanitize_url' : 'sanitize_text_field',
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
					'auth_callback'     => function ( $allowed, $meta_key, $object_id ) {
						return current_user_can( 'edit_post', (int) $object_id );
					},
				)
			);
		}
	}
}
