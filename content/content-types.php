<?php
/**
 * Blokino · API pública del framework de tipos de contenido.
 *
 * Dos funciones, mismo patrón que el set de iconos: una para declarar y otra
 * para leer lo ya resuelto. La lógica vive en `Blokino_Content`.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'blokino_register_content_type' ) ) {
	/**
	 * Declara un tipo de contenido (CPT + taxonomías + meta) sin editar el motor.
	 *
	 * Llamarla antes de `init` prioridad 10, y preferiblemente en **`init` con
	 * prioridad 0 o 1**: si las etiquetas pasan por `__()`, hacerlo antes de
	 * `init` hace que WordPress 6.7 se niegue a cargar el text domain y devuelva
	 * la cadena sin traducir. `plugins_loaded` solo sirve con literales crudos.
	 *
	 * Declarar dos veces el mismo slug funde las definiciones sección a sección,
	 * así que se puede añadir un meta a `project` sin repetir el resto.
	 *
	 * Forma de $args (todo opcional salvo el sentido común):
	 *
	 *     array(
	 *         // Atajo de etiquetas. Se ignora si pasas 'args' => array( 'labels' => ... ).
	 *         'labels'     => array( 'singular' => '', 'plural' => '', 'menu' => '' ),
	 *
	 *         // Argumentos crudos de register_post_type(). Mandan sobre los
	 *         // predeterminados (public, show_in_rest, supports).
	 *         'args'       => array( 'has_archive' => 'services' ),
	 *
	 *         // Taxonomías del tipo. Misma forma: 'labels' de atajo, 'args' crudos.
	 *         'taxonomies' => array(
	 *             'service_area' => array( 'labels' => array( 'singular' => '' ) ),
	 *         ),
	 *
	 *         // Meta expuesta a REST, lista para Block Bindings.
	 *         'meta'       => array(
	 *             'price_from' => array(
	 *                 'label'             => '',      // También el título REST.
	 *                 'type'              => 'string', // string|boolean|integer|number|array|object.
	 *                 'single'            => true,
	 *                 'default'           => '',
	 *                 'show_in_rest'      => true,
	 *                 'sanitize_callback' => 'sanitize_text_field',
	 *                 'auth_capability'   => 'edit_post', // Se comprueba por objeto.
	 *                 'admin_column'      => false,       // true o el rótulo de la columna.
	 *             ),
	 *         ),
	 *     )
	 *
	 * @since 0.1.29
	 *
	 * @param string $slug Slug del post type (máx. 20 caracteres).
	 * @param array  $args Definición declarativa.
	 * @return void
	 */
	function blokino_register_content_type( $slug, $args = array() ) {
		Blokino_Content::declare_type( $slug, $args );
	}
}

if ( ! function_exists( 'blokino_content_types' ) ) {
	/**
	 * Devuelve el mapa de tipos de contenido ya validado y normalizado.
	 *
	 * Útil para introspección (tests, importador de demo, admin). Llamarla en
	 * `init` o después: antes las etiquetas no están traducidas y el resultado
	 * no se cachea.
	 *
	 * @since 0.1.29
	 *
	 * @return array<string,array> Mapa slug => array{slug,args,taxonomies,meta}.
	 */
	function blokino_content_types() {
		return Blokino_Content::get_types();
	}
}
