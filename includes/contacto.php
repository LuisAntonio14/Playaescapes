<?php
/**
 * Módulo Contacto: consultas generales (distintas de las solicitudes de reserva).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Campos de una consulta: clave => etiqueta. Se usa para guardar, mostrar y avisar.
function pe_campos_consulta() {
	return array(
		'nombre'       => 'Nombre',
		'correo'       => 'Correo',
		'telefono'     => 'Teléfono',
		'mensaje'      => 'Mensaje',
		'utm_source'   => 'UTM source',
		'utm_medium'   => 'UTM medium',
		'utm_campaign' => 'UTM campaign',
	);
}

// Tipo de contenido privado "Consultas": visible solo en el administrador.
function pe_registrar_consultas() {
	register_post_type( 'pe_consulta', array(
		'labels' => array(
			'name'          => 'Consultas',
			'singular_name' => 'Consulta',
			'edit_item'     => 'Ver consulta',
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-format-chat',
		'supports'     => array( 'title' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ), // No se crean a mano
		'map_meta_cap' => true,
	) );
}
add_action( 'init', 'pe_registrar_consultas' );

// Guarda una consulta. Devuelve el ID creado, o 0 si falló.
function pe_crear_consulta( $datos ) {
	$limpios = array();
	foreach ( array_keys( pe_campos_consulta() ) as $campo ) {
		$valor = isset( $datos[ $campo ] ) ? $datos[ $campo ] : '';
		if ( 'correo' === $campo ) {
			$limpios[ $campo ] = sanitize_email( $valor );
		} elseif ( 'mensaje' === $campo ) {
			$limpios[ $campo ] = sanitize_textarea_field( $valor );
		} else {
			$limpios[ $campo ] = sanitize_text_field( $valor );
		}
	}

	$titulo = '' !== $limpios['nombre'] ? $limpios['nombre'] : 'Consulta sin nombre';

	$id = wp_insert_post( array(
		'post_type'   => 'pe_consulta',
		'post_status' => 'publish',
		'post_title'  => $titulo,
	) );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	foreach ( $limpios as $campo => $valor ) {
		update_post_meta( $id, '_pe_consulta_' . $campo, $valor );
	}

	return $id;
}

// Cuadro de solo lectura para ver cada consulta en el administrador.
function pe_agregar_metabox_consulta() {
	add_meta_box( 'pe_datos_consulta', 'Datos de la consulta', 'pe_mostrar_metabox_consulta', 'pe_consulta', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_consulta' );

function pe_mostrar_metabox_consulta( $post ) {
	echo '<table class="widefat striped"><tbody>';
	foreach ( pe_campos_consulta() as $clave => $etiqueta ) {
		printf(
			'<tr><th style="width:200px;">%s</th><td>%s</td></tr>',
			esc_html( $etiqueta ),
			nl2br( esc_html( get_post_meta( $post->ID, '_pe_consulta_' . $clave, true ) ) )
		);
	}
	echo '</tbody></table>';
}