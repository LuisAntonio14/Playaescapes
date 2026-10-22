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


// Columnas de la lista de Leads en el administrador.
function pe_columnas_leads( $columnas ) {
	$nuevas = array();
	if ( isset( $columnas['cb'] ) ) {
		$nuevas['cb'] = $columnas['cb'];
	}
	$nuevas['title']           = 'Nombre';
	$nuevas['pe_col_hotel']    = 'Hotel';
	$nuevas['pe_col_fechas']   = 'Fechas';
	$nuevas['pe_col_telefono'] = 'Teléfono';
	$nuevas['pe_col_promo']    = 'Promoción';
	$nuevas['pe_col_origen']   = 'Origen (UTM)';
	$nuevas['date']            = 'Recibido';
	return $nuevas;
}
add_filter( 'manage_pe_lead_posts_columns', 'pe_columnas_leads' );

function pe_contenido_columnas_leads( $columna, $post_id ) {
	$dato = function ( $campo ) use ( $post_id ) {
		return get_post_meta( $post_id, '_pe_lead_' . $campo, true );
	};

	switch ( $columna ) {
		case 'pe_col_hotel':
			echo esc_html( $dato( 'hotel' ) );
			break;
		case 'pe_col_fechas':
			if ( $dato( 'llegada' ) || $dato( 'salida' ) ) {
				echo esc_html( $dato( 'llegada' ) . ' a ' . $dato( 'salida' ) );
			}
			break;
		case 'pe_col_telefono':
			echo esc_html( $dato( 'telefono' ) );
			break;
		case 'pe_col_promo':
			echo esc_html( $dato( 'promocion' ) );
			break;
		case 'pe_col_origen':
			$partes = array_filter( array( $dato( 'utm_source' ), $dato( 'utm_medium' ), $dato( 'utm_campaign' ) ) );
			echo esc_html( implode( ' / ', $partes ) );
			break;
	}
}
add_action( 'manage_pe_lead_posts_custom_column', 'pe_contenido_columnas_leads', 10, 2 );


// Botón "Exportar a Excel (CSV)" arriba de la lista de Leads (solo administradores).
function pe_boton_exportar_leads( $post_type ) {
	if ( 'pe_lead' !== $post_type || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=pe_exportar_leads' ), 'pe_exportar_leads' );
	printf( '<a href="%s" class="button">Exportar a Excel (CSV)</a>', esc_url( $url ) );
}
add_action( 'restrict_manage_posts', 'pe_boton_exportar_leads' );

// Evita que Excel interprete como fórmula un texto escrito en el formulario.
function pe_csv_celda( $valor ) {
	$valor = (string) $valor;
	if ( '' !== $valor && in_array( $valor[0], array( '=', '+', '-', '@' ), true ) ) {
		$valor = "'" . $valor;
	}
	return $valor;
}

// Genera y descarga el archivo CSV con todos los leads.
function pe_exportar_leads() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No tienes permiso para exportar.' );
	}
	check_admin_referer( 'pe_exportar_leads' );

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

	$ids = get_posts( array(
		'post_type'      => 'pe_lead',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="leads-playaescapes-' . current_time( 'Y-m-d' ) . '.csv"' );

	$salida = fopen( 'php://output', 'w' );
	fwrite( $salida, "\xEF\xBB\xBF" ); // Para que Excel muestre bien los acentos.
	fputcsv( $salida, array_merge( array( 'Recibido' ), array_values( $campos ) ) );

	foreach ( $ids as $id ) {
		$fila = array( get_the_date( 'Y-m-d H:i', $id ) );
		foreach ( array_keys( $campos ) as $campo ) {
			$fila[] = pe_csv_celda( get_post_meta( $id, '_pe_lead_' . $campo, true ) );
		}
		fputcsv( $salida, $fila );
	}

	fclose( $salida );
	exit;
}
add_action( 'admin_post_pe_exportar_leads', 'pe_exportar_leads' );