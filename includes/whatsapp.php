<?php
/**
 * Módulo WhatsApp: botón fijo en todas las páginas del sitio público.
 * Número y mensaje se configuran en Ajustes > PlayaEscapes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_boton_whatsapp() {
	if ( is_admin() ) {
		return;
	}

	$numero = pe_ajuste( 'whatsapp_numero' );
	if ( '' === $numero ) {
		return;
	}

	$mensaje = pe_ajuste( 'whatsapp_mensaje' );

	// En una ficha de hotel o una promoción, el mensaje indica de dónde viene.
	if ( is_singular( array( 'pe_hotel', 'hot_escape' ) ) ) {
		$origen  = is_singular( 'hot_escape' ) ? 'Promoción: ' : 'Hotel: ';
		$mensaje = trim( $mensaje . ' (' . $origen . get_queried_object()->post_title . ')' );
	}

	$url = 'https://wa.me/' . $numero;
	if ( '' !== $mensaje ) {
		$url .= '?text=' . rawurlencode( $mensaje );
	}

	// Estilo básico temporal; el definitivo lo dará la plantilla de Marketing.
	printf(
		'<a class="pe-whatsapp" href="%s" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp" style="position:fixed;right:20px;bottom:20px;z-index:9999;padding:12px 18px;border-radius:30px;background:#25D366;color:#fff;font-weight:bold;text-decoration:none;">WhatsApp</a>',
		esc_url( $url )
	);
}
add_action( 'wp_footer', 'pe_boton_whatsapp' );