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

	echo '<p class="description">Al publicar un hotel, aparece solo en las listas de hoteles del sitio (widget, formulario y promociones). Para quitarlo de las listas, pásalo a borrador.</p>';

	$ubicacion = get_post_meta( $post->ID, '_pe_hotel_ubicacion', true );
	$tipo      = get_post_meta( $post->ID, '_pe_hotel_tipo', true );

	echo '<p><label for="pe_hotel_ubicacion"><strong>Ubicación (etiqueta de la tarjeta)</strong></label><br>';
	printf(
		'<input type="text" class="regular-text" name="pe_hotel_ubicacion" id="pe_hotel_ubicacion" value="%s" placeholder="Ejemplo: Playa del Carmen">',
		esc_attr( $ubicacion )
	);
	echo '</p>';

	echo '<p><label for="pe_hotel_tipo"><strong>Tipo (etiquetas, separadas por coma)</strong></label><br>';
	printf(
		'<input type="text" class="regular-text" name="pe_hotel_tipo" id="pe_hotel_tipo" value="%s" placeholder="Ejemplo: Familiar, Solo adultos">',
		esc_attr( $tipo )
	);
	echo '</p>';

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

	foreach ( array( 'pe_hotel_ubicacion', 'pe_hotel_tipo' ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) ) {
			update_post_meta( $post_id, '_' . $campo, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
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


// Columnas de la lista de Hoteles en el administrador.
function pe_columnas_hoteles( $columnas ) {
	$nuevas = array();
	if ( isset( $columnas['cb'] ) ) {
		$nuevas['cb'] = $columnas['cb'];
	}
	$nuevas['title']         = 'Hotel';
	$nuevas['pe_col_precio'] = 'Precio desde';
	$nuevas['date']          = 'Fecha';
	return $nuevas;
}
add_filter( 'manage_pe_hotel_posts_columns', 'pe_columnas_hoteles' );

function pe_contenido_columnas_hoteles( $columna, $post_id ) {
	switch ( $columna ) {
		case 'pe_col_precio':
			$precio = get_post_meta( $post_id, '_pe_hotel_precio_desde', true );
			if ( '' !== $precio ) {
				echo esc_html( '$' . number_format_i18n( (int) $precio ) . ' MXN' );
			}
			break;
	}
}
add_action( 'manage_pe_hotel_posts_custom_column', 'pe_contenido_columnas_hoteles', 10, 2 );

// Ficha del hotel en el idioma actual a partir de su nombre en español (0 si no existe).
function pe_hotel_id( $hotel ) {
	$hoteles = function_exists( 'pe_hoteles_por_nombre' ) ? pe_hoteles_por_nombre() : array();
	if ( empty( $hoteles[ $hotel ] ) ) {
		return 0;
	}
	$id = $hoteles[ $hotel ];
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) && pll_current_language() ) {
		$traducido = pll_get_post( $id, pll_current_language() );
		if ( $traducido ) {
			$id = $traducido;
		}
	}
	return (int) $id;
}

// Texto de un hotel para las listas desplegables: "Nombre — Tipo · Ubicación".
// Toma Tipo y Ubicación de la ficha del hotel (en el idioma actual). Si no hay ficha, solo el nombre.
function pe_etiqueta_hotel( $hotel ) {
	static $cache = array();
	if ( isset( $cache[ $hotel ] ) ) {
		return $cache[ $hotel ];
	}

	$etiqueta = $hotel;
	$id       = pe_hotel_id( $hotel );
	if ( $id ) {
		$tipo      = trim( (string) get_post_meta( $id, '_pe_hotel_tipo', true ) );
		$ubicacion = trim( (string) get_post_meta( $id, '_pe_hotel_ubicacion', true ) );
		$detalles  = implode( ' · ', array_filter( array( $tipo, $ubicacion ) ) );
		if ( '' !== $detalles ) {
			$etiqueta .= ' — ' . $detalles;
		}
	}

	$cache[ $hotel ] = $etiqueta;
	return $etiqueta;
}

// Si cambia el nombre de un hotel (en español), sus promociones se actualizan solas.
// Los leads conservan el nombre con el que llegaron.
function pe_hotel_renombrado( $post_id, $despues, $antes ) {
	if ( 'pe_hotel' !== $despues->post_type || 'publish' !== $antes->post_status ) {
		return;
	}
	$viejo = trim( (string) $antes->post_title );
	$nuevo = trim( (string) $despues->post_title );
	if ( '' === $viejo || '' === $nuevo || $viejo === $nuevo ) {
		return;
	}
	if ( function_exists( 'pll_get_post_language' ) && function_exists( 'pll_default_language' )
		&& pll_get_post_language( $post_id ) !== pll_default_language() ) {
		return; // Solo cuenta el nombre en español.
	}

	$ofertas = get_posts( array(
		'post_type'      => 'hot_escape',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_pe_hotel',
		'meta_value'     => $viejo,
		'lang'           => '', // Todos los idiomas.
	) );
	foreach ( $ofertas as $oferta_id ) {
		update_post_meta( $oferta_id, '_pe_hotel', $nuevo );
	}
}
add_action( 'post_updated', 'pe_hotel_renombrado', 10, 3 );