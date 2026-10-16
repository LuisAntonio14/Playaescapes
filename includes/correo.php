<?php
/**
 * Módulo Correo: aviso por correo de cada solicitud nueva.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Correo al que llegan los avisos. AÚN POR CONFIRMAR.
function pe_correo_destino() {
	return 'lvelasco@sandos.com'; // Para pruebas, pon temporalmente tu correo.
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