<?php
/**
 * Módulo Portadas: textos editables de la portada de cada página
 * (texto pequeño, título y subtítulo). Si un campo está vacío, el tema usa el texto del diseño.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lee un texto de portada de una página. Devuelve $defecto si está vacío.
function pe_portada_texto( $campo, $defecto = '', $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_queried_object_id();
	$valor   = $post_id ? get_post_meta( $post_id, '_pe_portada_' . $campo, true ) : '';
	return '' !== $valor ? $valor : $defecto;
}

// Cuadro "Textos de portada" en el editor de páginas.
function pe_agregar_metabox_portada() {
	add_meta_box(
		'pe_portada',
		'Textos de portada',
		'pe_mostrar_metabox_portada',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_portada' );

function pe_mostrar_metabox_portada( $post ) {
	wp_nonce_field( 'pe_guardar_portada', 'pe_portada_nonce' );

	$campos = array(
		'etiqueta'  => 'Texto pequeño (arriba del título)',
		'titulo'    => 'Título',
		'subtitulo' => 'Subtítulo',
	);

	echo '<p>Si un campo se deja vacío, la página usa el texto del diseño.</p>';

	foreach ( $campos as $clave => $etiqueta ) {
		$valor = get_post_meta( $post->ID, '_pe_portada_' . $clave, true );
		printf(
			'<p><label for="pe_portada_%1$s"><strong>%2$s</strong></label><br><input type="text" class="widefat" id="pe_portada_%1$s" name="pe_portada_%1$s" value="%3$s"></p>',
			esc_attr( $clave ),
			esc_html( $etiqueta ),
			esc_attr( $valor )
		);
	}
}

// Guardar los textos al actualizar la página.
function pe_guardar_metabox_portada( $post_id ) {
	if ( ! isset( $_POST['pe_portada_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_portada_nonce'], 'pe_guardar_portada' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'etiqueta', 'titulo', 'subtitulo' ) as $clave ) {
		if ( isset( $_POST[ 'pe_portada_' . $clave ] ) ) {
			update_post_meta(
				$post_id,
				'_pe_portada_' . $clave,
				sanitize_text_field( wp_unslash( $_POST[ 'pe_portada_' . $clave ] ) )
			);
		}
	}
}
add_action( 'save_post_page', 'pe_guardar_metabox_portada' );