<?php
/**
 * Módulo Leads: dónde se guardan las solicitudes y cómo se ven en el administrador.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Tipo de contenido privado "Leads": visible solo en el administrador.
function pe_registrar_leads() {
	register_post_type( 'pe_lead', array(
		'labels' => array(
			'name'          => 'Leads',
			'singular_name' => 'Lead',
			'edit_item'     => 'Ver lead',
		),
		'public'       => false, // No tiene página pública
		'show_ui'      => true,  // Pero sí aparece en el administrador
		'menu_icon'    => 'dashicons-email-alt',
		'supports'     => array( 'title' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ), // No se crean a mano
		'map_meta_cap' => true,
	) );
}
add_action( 'init', 'pe_registrar_leads' );

// Función reutilizable: recibe los datos de una solicitud y la guarda.
// Devuelve el ID del lead creado, o 0 si falló.
function pe_crear_lead( $datos ) {
	$campos = array(
		'hotel', 'llegada', 'salida', 'adultos', 'ninos', 'habitaciones',
		'nombre', 'apellido', 'correo', 'telefono', 'pais',
		'promocion', 'utm_source', 'utm_medium', 'utm_campaign',
	);

	$limpios = array();
	foreach ( $campos as $campo ) {
		$valor = isset( $datos[ $campo ] ) ? $datos[ $campo ] : '';
		$limpios[ $campo ] = ( 'correo' === $campo )
			? sanitize_email( $valor )
			: sanitize_text_field( $valor );
	}

	$titulo = trim( $limpios['nombre'] . ' ' . $limpios['apellido'] );
	if ( '' === $titulo ) {
		$titulo = 'Lead sin nombre';
	}

	$id = wp_insert_post( array(
		'post_type'   => 'pe_lead',
		'post_status' => 'publish',
		'post_title'  => $titulo,
	) );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	foreach ( $limpios as $campo => $valor ) {
		update_post_meta( $id, '_pe_lead_' . $campo, $valor );
	}

	return $id;
}

// Cuadro de solo lectura para ver los datos de cada lead en el administrador.
function pe_agregar_metabox_lead() {
	add_meta_box( 'pe_datos_lead', 'Datos de la solicitud', 'pe_mostrar_metabox_lead', 'pe_lead', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_lead' );

function pe_mostrar_metabox_lead( $post ) {
	$campos = array(
		'hotel'        => 'Hotel',
		'llegada'      => 'Llegada',
		'salida'       => 'Salida',
		'adultos'      => 'Adultos',
		'ninos'        => 'Niños',
		'habitaciones' => 'Habitaciones',
		'nombre'       => 'Nombre',
		'apellido'     => 'Apellido',
		'correo'       => 'Correo',
		'telefono'     => 'Teléfono',
		'pais'         => 'País',
		'promocion'    => 'Promoción',
		'utm_source'   => 'UTM source',
		'utm_medium'   => 'UTM medium',
		'utm_campaign' => 'UTM campaign',
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $campos as $clave => $etiqueta ) {
		printf(
			'<tr><th style="width:200px;">%s</th><td>%s</td></tr>',
			esc_html( $etiqueta ),
			esc_html( get_post_meta( $post->ID, '_pe_lead_' . $clave, true ) )
		);
	}
	echo '</tbody></table>';
}