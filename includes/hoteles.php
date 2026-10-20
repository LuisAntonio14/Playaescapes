<?php
/**
 * Módulo Hoteles: tipo de contenido para la ficha informativa de cada hotel.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_registrar_hoteles() {
	register_post_type( 'pe_hotel', array(
		'labels' => array(
			'name'          => 'Hoteles',
			'singular_name' => 'Hotel',
			'add_new_item'  => 'Agregar hotel',
			'edit_item'     => 'Editar hotel',
		),
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'hoteles' ),
		'menu_icon'    => 'dashicons-building',
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	) );
}
add_action( 'init', 'pe_registrar_hoteles' );