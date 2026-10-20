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


// Cuadro "Datos del hotel" dentro de cada ficha de hotel.
function pe_agregar_metabox_hotel() {
	add_meta_box(
		'pe_datos_hotel',
		'Datos del hotel',
		'pe_mostrar_metabox_hotel',
		'pe_hotel',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_hotel' );

function pe_mostrar_metabox_hotel( $post ) {
	wp_nonce_field( 'pe_guardar_hotel', 'pe_hotel_nonce' );

	$hotel_guardado = get_post_meta( $post->ID, '_pe_hotel', true );

	echo '<p><label for="pe_hotel_lista"><strong>Hotel de la lista</strong></label><br>';
	echo '<select name="pe_hotel_lista" id="pe_hotel_lista">';
	echo '<option value="">Selecciona un hotel</option>';
	foreach ( pe_lista_hoteles() as $hotel ) {
		printf(
			'<option value="%1$s" %2$s>%1$s</option>',
			esc_attr( $hotel ),
			selected( $hotel_guardado, $hotel, false )
		);
	}
	echo '</select></p>';

	$precio  = get_post_meta( $post->ID, '_pe_hotel_precio_desde', true );
	$incluye = get_post_meta( $post->ID, '_pe_hotel_incluye', true );

	echo '<p><label for="pe_hotel_precio_desde"><strong>Precio desde (MXN por persona por noche)</strong></label><br>';
	printf(
		'<input type="number" min="0" step="1" name="pe_hotel_precio_desde" id="pe_hotel_precio_desde" value="%s">',
		esc_attr( $precio )
	);
	echo '</p>';

	echo '<p><label for="pe_hotel_incluye"><strong>Qué incluye (un elemento por línea)</strong></label><br>';
	printf(
		'<textarea name="pe_hotel_incluye" id="pe_hotel_incluye" rows="6" style="width:100%%;">%s</textarea>',
		esc_textarea( $incluye )
	);
	echo '</p>';
}

// Guardar los datos al publicar o actualizar.
function pe_guardar_metabox_hotel( $post_id ) {
	if ( ! isset( $_POST['pe_hotel_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_hotel_nonce'], 'pe_guardar_hotel' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['pe_hotel_lista'] ) ) {
		$hotel = sanitize_text_field( wp_unslash( $_POST['pe_hotel_lista'] ) );
		if ( '' === $hotel || in_array( $hotel, pe_lista_hoteles(), true ) ) {
			update_post_meta( $post_id, '_pe_hotel', $hotel );
		}
	}

	if ( isset( $_POST['pe_hotel_precio_desde'] ) ) {
		$precio = '' === $_POST['pe_hotel_precio_desde'] ? '' : absint( $_POST['pe_hotel_precio_desde'] );
		update_post_meta( $post_id, '_pe_hotel_precio_desde', $precio );
	}

	if ( isset( $_POST['pe_hotel_incluye'] ) ) {
		update_post_meta(
			$post_id,
			'_pe_hotel_incluye',
			sanitize_textarea_field( wp_unslash( $_POST['pe_hotel_incluye'] ) )
		);
	}
}
add_action( 'save_post_pe_hotel', 'pe_guardar_metabox_hotel' );