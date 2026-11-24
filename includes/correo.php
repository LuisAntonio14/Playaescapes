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
	return pe_lista_correos( pe_ajuste( 'correo_avisos' ) );
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

	$enviado = wp_mail( pe_correo_destino(), $asunto, $mensaje, $cabeceras );

	// Confirmación al cliente (se envía aunque falle el aviso a call center).
	pe_enviar_confirmacion_cliente( $lead_id );

	return $enviado;
}
// Envía el aviso de una consulta de Contacto ya guardada. Si falla, la consulta sigue guardada.
function pe_enviar_aviso_consulta( $consulta_id ) {
	$destino = pe_correo_destino();
	if ( empty( $destino ) ) {
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


// ===== Correo de confirmación al cliente =====

// Textos del correo al cliente. PROPUESTA: por confirmar con Dani.
// Se corrigen y traducen desde "Textos en inglés" (grupo "Correo al cliente").
function pe_textos_confirmacion() {
	return array(
		'asunto'  => 'Recibimos tu solicitud de reservación',
		'saludo'  => 'Hola %s,',
		'gracias' => 'Gracias por tu solicitud. Un especialista de PlayaEscapes se pondrá en contacto contigo.',
		'resumen' => 'Resumen de tu solicitud:',
		'aviso'   => 'Esto no es una reservación confirmada. Si tienes dudas, responde a este correo.',
		'firma'   => 'Equipo PlayaEscapes',
	);
}

function pe_confirmacion_registrar_textos() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}
	foreach ( pe_textos_confirmacion() as $texto ) {
		pll_register_string( 'Correo al cliente', $texto, 'PlayaEscapes' );
	}
}
add_action( 'init', 'pe_confirmacion_registrar_textos' );

// Traduce un texto al idioma del lead (no al de la página actual).
function pe_confirmacion_t( $texto, $idioma ) {
	if ( '' === $idioma || ! function_exists( 'pll_translate_string' ) ) {
		return $texto;
	}
	return pll_translate_string( $texto, $idioma );
}

// Envía al cliente la confirmación de que recibimos su solicitud.
function pe_enviar_confirmacion_cliente( $lead_id ) {
	$correo = get_post_meta( $lead_id, '_pe_lead_correo', true );
	if ( ! is_email( $correo ) ) {
		return false;
	}

	$idioma = (string) get_post_meta( $lead_id, 'pe_idioma', true );
	if ( '' === $idioma && function_exists( 'pll_default_language' ) ) {
		$idioma = pll_default_language();
	}

	$textos = pe_textos_confirmacion();
	$campos = array(
		'hotel'        => 'Hotel',
		'llegada'      => 'Llegada',
		'salida'       => 'Salida',
		'adultos'      => 'Adultos',
		'ninos'        => 'Niños',
		'habitaciones' => 'Habitaciones',
		'promocion'    => 'Promoción',
	);

	$lineas = array();
	foreach ( $campos as $clave => $etiqueta ) {
		$valor = (string) get_post_meta( $lead_id, '_pe_lead_' . $clave, true );
		if ( '' !== $valor ) {
			$lineas[] = pe_confirmacion_t( $etiqueta, $idioma ) . ': ' . $valor;
		}
	}

	$nombre  = get_post_meta( $lead_id, '_pe_lead_nombre', true );
	$mensaje = sprintf( pe_confirmacion_t( $textos['saludo'], $idioma ), $nombre ) . "\n\n"
		. pe_confirmacion_t( $textos['gracias'], $idioma ) . "\n\n"
		. pe_confirmacion_t( $textos['resumen'], $idioma ) . "\n"
		. implode( "\n", $lineas ) . "\n\n"
		. pe_confirmacion_t( $textos['aviso'], $idioma ) . "\n\n"
		. pe_confirmacion_t( $textos['firma'], $idioma );

	$asunto = pe_confirmacion_t( $textos['asunto'], $idioma ) . ' - PlayaEscapes';
	$asunto = str_replace( array( "\r", "\n" ), ' ', $asunto );

	// Si el cliente responde, la respuesta llega al primer correo de avisos (call center).
	$cabeceras = array( 'Content-Type: text/plain; charset=UTF-8' );
	$destino   = pe_correo_destino();
	if ( ! empty( $destino ) ) {
		$cabeceras[] = 'Reply-To: ' . $destino[0];
	}

	// Sin la línea "Idioma" que se agrega a los avisos internos.
	remove_filter( 'wp_mail', 'pe_idioma_en_correo' );
	$enviado = wp_mail( $correo, $asunto, $mensaje, $cabeceras );
	add_filter( 'wp_mail', 'pe_idioma_en_correo' );

	return $enviado;
}
