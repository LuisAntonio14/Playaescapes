<?php
/**
 * Módulo Idiomas: textos fijos del sitio (tema y plugin) traducibles con Polylang.
 * Las traducciones se escriben en Languages > Translations, grupo "PlayaEscapes".
 * Si Polylang no está activo, se muestra el texto en español.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Traduce un texto fijo al idioma actual de la página.
function pe_t( $texto ) {
	return function_exists( 'pll__' ) ? pll__( $texto ) : $texto;
}

// Lista de textos fijos del sitio, agrupados por sección.
// Importante: deben coincidir EXACTAMENTE con los que usan el tema y el plugin.
function pe_textos_traducibles() {
	return array(
		'Encabezado' => array(
			'Reservar',
			'Playa Escapes, ir al inicio',
			'Menú principal',
			'Abrir menú',
			'Cerrar menú',
		),
		'Pie de página' => array(
			'Agencia especializada en Sandos Hotels & Resorts. Cancún, Riviera Maya y Los Cabos.',
			'Destinos',
			'Legal',
			'Aviso de privacidad',
			'Términos y condiciones',
			'© %s Playa Escapes. Todos los derechos reservados.',
			'Especialistas en Sandos Hotels & Resorts',
		),
		'Blog' => array(
			'Blog de viajes',
			'Guías e inspiración para Sandos Hotels',
			'Ver más',
			'Anterior',
			'Siguiente',
			'Muy pronto publicaremos nuestras primeras guías de viaje.',
			'j \d\e F \d\e Y',
		),
		'Artículo del blog' => array(
			'Volver al blog',
			'¿Te interesa este hotel?',
			'Reserva con un especialista Sandos',
			'Servicio sin costo adicional. Cotización en menos de un día hábil.',
			'Reservar ahora',
			'WhatsApp directo',
			'Artículo:',
		),
		'Contacto' => array(
			'Estamos aquí para ti',
			'Tu próxima experiencia comienza con nosotros.',
			'¿Cómo podemos ayudarte?',
			'Correo',
			'Teléfono',
			'Ubicación',
			'Ponte en contacto con nosotros',
			'Facebook de Playa Escapes',
			'Instagram de Playa Escapes',
			'Nombre completo',
			'Tu nombre completo',
			'Correo electrónico',
			'Tu correo electrónico',
			'Teléfono (opcional)',
			'Tu teléfono (opcional)',
			'Mensaje',
			'Enviar mensaje',
			'Gracias, recibimos tu consulta. Te responderemos a la brevedad.',
			'Escribe tu nombre.',
			'Escribe un correo electrónico válido.',
			'Si escribes un teléfono, debe tener de 10 a 15 dígitos.',
			'Escribe tu mensaje.',
			'No pudimos enviar tu consulta. Inténtalo de nuevo.',
		),
		'Formulario de reserva' => array(
			'Hotel',
			'Selecciona un hotel',
			'Fecha de llegada',
			'Fecha de salida',
			'Adultos',
			'Niños',
			'Habitaciones',
			'Nombre',
			'Apellido',
			'Confirma tu correo',
			'País de residencia',
			'Selecciona un país',
			'México',
			'Argentina',
			'Canadá',
			'Colombia',
			'USA',
			'Otro',
			'Proceder a reservar',
			'Revisa los datos e inténtalo de nuevo:',
			'La página estuvo abierta mucho tiempo. Recárgala e inténtalo de nuevo.',
			'Selecciona un hotel.',
			'Indica las fechas de llegada y salida.',
			'La fecha de llegada no puede ser anterior a hoy.',
			'La fecha de salida debe ser posterior a la de llegada.',
			'Escribe tu apellido.',
			'Los correos no coinciden.',
			'El teléfono debe tener de 10 a 15 dígitos.',
		),
		'Avisos legales' => array(
			'Al enviar tus datos aceptas que sean tratados conforme a nuestro %1$s, así como los %2$s de la promoción.',
			'Al enviar tus datos aceptas que sean tratados conforme a nuestro %s.',
			'Al enviar tus datos aceptas los %s de la promoción.',
		),
		'WhatsApp' => array(
			'Escríbenos por WhatsApp',
			'Promoción:',
			'Hotel:',
		),
	);
}

// Registra los textos en Polylang (Languages > Translations).
function pe_registrar_textos() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$vistos = array();

	foreach ( pe_textos_traducibles() as $seccion => $textos ) {
		foreach ( $textos as $texto ) {
			if ( isset( $vistos[ $texto ] ) ) {
				continue;
			}
			$vistos[ $texto ] = true;
			pll_register_string( $seccion, $texto, 'PlayaEscapes', strlen( $texto ) > 80 );
		}
	}

	// Textos que vienen de Ajustes > PlayaEscapes (países de los teléfonos y mensaje de WhatsApp).
	if ( function_exists( 'pe_telefonos_paises' ) ) {
		foreach ( pe_telefonos_paises() as $pais ) {
			if ( ! isset( $vistos[ $pais['pais'] ] ) ) {
				$vistos[ $pais['pais'] ] = true;
				pll_register_string( 'Ajustes: teléfonos por país', $pais['pais'], 'PlayaEscapes' );
			}
		}
	}
	if ( function_exists( 'pe_ajuste' ) && '' !== pe_ajuste( 'whatsapp_mensaje' ) ) {
		pll_register_string( 'Ajustes: mensaje de WhatsApp', pe_ajuste( 'whatsapp_mensaje' ), 'PlayaEscapes' );
	}
}
add_action( 'init', 'pe_registrar_textos' );