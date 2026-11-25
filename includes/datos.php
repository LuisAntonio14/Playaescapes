<?php
/**
 * Módulo Datos: listas compartidas por otros módulos del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lista de hoteles: se arma sola con los Hoteles publicados (versión en español),
// en orden alfabético. Se usa en Hot Escapes, el widget, la ventana y el formulario.
// El valor que se guarda en ofertas y leads es el título del hotel en español.
// Para agregar un hotel basta con crearlo y publicarlo en Hoteles.
function pe_lista_hoteles() {
	$nombres = array_keys( pe_hoteles_por_nombre() );
	return $nombres ? $nombres : pe_hoteles_fijos();
}

// Solo por si todavía no hay Hoteles publicados.
function pe_hoteles_fijos() {
	return array(
		'Sandos Cancun',
		'Sandos Caracol Eco Resort',
		'Sandos Playacar',
		'Sandos Finisterra',
	);
}

// Hoteles publicados en español: array( 'Nombre' => ID ).
function pe_hoteles_por_nombre() {
	static $hoteles = null;
	if ( null !== $hoteles ) {
		return $hoteles;
	}

	$args = array(
		'post_type'        => 'pe_hotel',
		'post_status'      => 'publish',
		'posts_per_page'   => 100,
		'orderby'          => 'title',
		'order'            => 'ASC',
		'fields'           => 'ids',
		'no_found_rows'    => true,
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_default_language' ) ) {
		$args['lang'] = pll_default_language();
	}

	$lista = array();
	foreach ( get_posts( $args ) as $id ) {
		$nombre = trim( (string) get_post_field( 'post_title', $id ) );
		if ( '' !== $nombre && ! isset( $lista[ $nombre ] ) ) {
			$lista[ $nombre ] = (int) $id;
		}
	}
	// Solo se guarda en memoria si encontró hoteles (antes de "init" todavía no hay).
	if ( $lista ) {
		$hoteles = $lista;
	}
	return $lista;
}