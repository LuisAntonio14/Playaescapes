<?php
/**
 * Módulo Correo: avisos por correo de solicitudes de reserva y consultas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Correo al que llegan los avisos: se configura en Ajustes > PlayaEscapes.
// Si está vacío o no es válido, no se envía aviso (el lead queda guardado igual).
function pe_correo_destino() {
	$correo = pe_ajuste( 'correo_avisos' );
	return is_email( $correo ) ? $correo : '';
}

// Envía el aviso de un lead ya guardado. Si falla, el lead sigue guardado.
function pe_enviar_aviso_lead( $lead_id ) {
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

	$lineas = array();
	foreach ( $campos as $clave => $etiqueta ) {
		$valor    = get_post_meta( $lead_id, '_pe_lead_' . $clave, true );
		$lineas[] = $etiqueta . ': ' . $valor;
	}

	$nombre = trim(
		get_post_meta( $lead_id, '_pe_lead_nombre', true ) . ' ' .
		get_post_meta( $lead_id, '_pe_lead_apellido', true )
	);
	$hotel   = get_post_meta( $lead_id, '_pe_lead_hotel', true );
	$asunto  = 'Nueva solicitud PlayaEscapes: ' . $nombre . ' - ' . $hotel;
	$asunto  = str_replace( array( "\r", "\n" ), ' ', $asunto );
	$mensaje = "Se recibió una nueva solicitud desde PlayaEscapes.\n\n" . implode( "\n", $lineas );

	$correo_cliente = get_post_meta( $lead_id, '_pe_lead_correo', true );
	$cabeceras      = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $correo_cliente ) ) {
		$cabeceras[] = 'Reply-To: ' . $correo_cliente;
	}

	return wp_mail( pe_correo_destino(), $asunto, $mensaje, $cabeceras );
}

// Envía el aviso de una consulta de Contacto ya guardada. Si falla, la consulta sigue guardada.
function pe_enviar_aviso_consulta( $consulta_id ) {
	$destino = pe_correo_destino();
	if ( '' === $destino ) {
		return false;
	}

	$lineas = array();
	foreach ( pe_campos_consulta() as $clave => $etiqueta ) {
		$valor = get_post_meta( $consulta_id, '_pe_consulta_' . $clave, true );
		// El mensaje va en su propio renglón porque puede ser largo.
		$lineas[] = ( 'mensaje' === $clave ) ? "\n" . $etiqueta . ":\n" . $valor . "\n" : $etiqueta . ': ' . $valor;
	}

	$nombre  = get_post_meta( $consulta_id, '_pe_consulta_nombre', true );
	$asunto  = 'Nueva consulta PlayaEscapes: ' . $nombre;
	$asunto  = str_replace( array( "\r", "\n" ), ' ', $asunto );
	$mensaje = "Se recibió una nueva consulta desde la página de Contacto de PlayaEscapes.\n\n" . implode( "\n", $lineas );

	$correo_cliente = get_post_meta( $consulta_id, '_pe_consulta_correo', true );
	$cabeceras      = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $correo_cliente ) ) {
		$cabeceras[] = 'Reply-To: ' . $correo_cliente;
	}

	return wp_mail( $destino, $asunto, $mensaje, $cabeceras );
}