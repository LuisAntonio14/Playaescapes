<?php
/**
 * Módulo Hot Escapes: tipo de contenido, datos de la promoción y filtro de vigencia.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_registrar_hot_escapes() {
	register_post_type( 'hot_escape', array(
		'labels' => array(
			'name'          => 'Hot Escapes',
			'singular_name' => 'Hot Escape',
			'add_new_item'  => 'Agregar promoción',
			'edit_item'     => 'Editar promoción',
		),
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'hot-escapes' ),
		'menu_icon'    => 'dashicons-megaphone',
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
	) );
}
add_action( 'init', 'pe_registrar_hot_escapes' );

// Cuadro "Datos de la promoción" dentro de cada Hot Escape.
function pe_agregar_metabox_hot_escape() {
	add_meta_box(
		'pe_datos_promocion',
		'Datos de la promoción',
		'pe_mostrar_metabox_hot_escape',
		'hot_escape',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_hot_escape' );

function pe_mostrar_metabox_hot_escape( $post ) {
	wp_nonce_field( 'pe_guardar_hot_escape', 'pe_hot_escape_nonce' );

	$hotel_guardado = get_post_meta( $post->ID, '_pe_hotel', true );
	$hoteles        = array(
		'Sandos Cancún',
		'Sandos Caracol Eco Resort',
		'Sandos Playacar',
		'Sandos Finisterra',
	);

	echo '<p><label for="pe_hotel"><strong>Hotel</strong></label><br>';
	echo '<select name="pe_hotel" id="pe_hotel">';
	echo '<option value="">Selecciona un hotel</option>';
	foreach ( $hoteles as $hotel ) {
		printf(
			'<option value="%1$s" %2$s>%1$s</option>',
			esc_attr( $hotel ),
			selected( $hotel_guardado, $hotel, false )
		);
	}
	echo '</select></p>';

	$precio       = get_post_meta( $post->ID, '_pe_precio_desde', true );
	$vigencia_ini = get_post_meta( $post->ID, '_pe_vigencia_inicio', true );
	$vigencia_fin = get_post_meta( $post->ID, '_pe_vigencia_fin', true );

	echo '<p><label for="pe_precio_desde"><strong>Precio desde (MXN por persona por noche)</strong></label><br>';
	printf(
		'<input type="number" min="0" step="1" name="pe_precio_desde" id="pe_precio_desde" value="%s">',
		esc_attr( $precio )
	);
	echo '</p>';

	echo '<p><label for="pe_vigencia_inicio"><strong>Vigencia desde</strong></label><br>';
	printf(
		'<input type="date" name="pe_vigencia_inicio" id="pe_vigencia_inicio" value="%s">',
		esc_attr( $vigencia_ini )
	);
	echo '</p>';

	echo '<p><label for="pe_vigencia_fin"><strong>Vigencia hasta</strong></label><br>';
	printf(
		'<input type="date" name="pe_vigencia_fin" id="pe_vigencia_fin" value="%s">',
		esc_attr( $vigencia_fin )
	);
	echo '</p>';
}

// Guardar los datos al publicar o actualizar.
function pe_guardar_metabox_hot_escape( $post_id ) {
	if ( ! isset( $_POST['pe_hot_escape_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_hot_escape_nonce'], 'pe_guardar_hot_escape' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['pe_hotel'] ) ) {
		update_post_meta(
			$post_id,
			'_pe_hotel',
			sanitize_text_field( wp_unslash( $_POST['pe_hotel'] ) )
		);
	}

	if ( isset( $_POST['pe_precio_desde'] ) ) {
		$precio = '' === $_POST['pe_precio_desde'] ? '' : absint( $_POST['pe_precio_desde'] );
		update_post_meta( $post_id, '_pe_precio_desde', $precio );
	}

	foreach ( array( 'pe_vigencia_inicio', 'pe_vigencia_fin' ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) ) {
			$fecha = sanitize_text_field( wp_unslash( $_POST[ $campo ] ) );
			if ( '' === $fecha || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) ) {
				update_post_meta( $post_id, '_' . $campo, $fecha );
			}
		}
	}
}
add_action( 'save_post_hot_escape', 'pe_guardar_metabox_hot_escape' );

// Mostrar solo las promociones vigentes en el sitio público.
function pe_filtrar_hot_escapes_vigentes( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive( 'hot_escape' ) ) {
		return;
	}

	$hoy = current_time( 'Y-m-d' );

	$query->set( 'meta_query', array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array(
				'key'     => '_pe_vigencia_inicio',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_pe_vigencia_inicio',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_pe_vigencia_inicio',
				'value'   => $hoy,
				'compare' => '<=',
				'type'    => 'DATE',
			),
		),
		array(
			'relation' => 'OR',
			array(
				'key'     => '_pe_vigencia_fin',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_pe_vigencia_fin',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_pe_vigencia_fin',
				'value'   => $hoy,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	) );
}
add_action( 'pre_get_posts', 'pe_filtrar_hot_escapes_vigentes' );