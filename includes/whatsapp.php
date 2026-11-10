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

	// Estilo e ícono tomados del diseño de Marketing (Figma).
	$icono = '<svg aria-hidden="true" focusable="false" width="22" height="22" viewBox="0 0 22 22"><path fill="currentColor" d="M16.016 13.1835C15.7437 13.047 14.4045 12.3888 14.1552 12.2971C13.9049 12.2064 13.7234 12.1615 13.541 12.4346C13.3604 12.7069 12.8379 13.3201 12.6793 13.5016C12.5207 13.684 12.3612 13.706 12.089 13.5704C11.8167 13.4329 10.9386 13.146 9.89816 12.2183C9.08875 11.496 8.5415 10.604 8.38291 10.3309C8.22433 10.0586 8.36641 9.91104 8.50208 9.77537C8.62491 9.65345 8.77525 9.45729 8.91091 9.29871C9.04749 9.1392 9.09241 9.02554 9.18408 8.84312C9.27483 8.66162 9.22991 8.50304 9.16116 8.36646C9.09241 8.22987 8.54791 6.88879 8.3215 6.34337C8.09966 5.81262 7.87508 5.88504 7.70825 5.87587C7.54966 5.86854 7.36816 5.8667 7.18575 5.8667C7.00425 5.8667 6.70908 5.93454 6.45975 6.2077C6.21041 6.47995 5.50641 7.13904 5.50641 8.48012C5.50641 9.82029 6.48266 11.1155 6.61833 11.298C6.75491 11.4795 8.53966 14.2313 11.2722 15.411C11.9222 15.6915 12.4291 15.8593 12.8251 15.984C13.4777 16.192 14.0717 16.1627 14.5402 16.0921C15.0636 16.0142 16.1517 15.433 16.379 14.7969C16.6063 14.1607 16.6063 13.6153 16.5376 13.5016C16.4697 13.388 16.2892 13.3201 16.016 13.1835ZM11.0458 19.9696H11.0422C9.41945 19.9698 7.82657 19.5335 6.43041 18.7065L6.0995 18.5103L2.67025 19.4105L3.58508 16.0665L3.36966 15.7236C2.46257 14.2792 1.98269 12.6076 1.9855 10.902C1.98641 5.90612 6.05183 1.84162 11.0495 1.84162C13.4695 1.84162 15.7447 2.78579 17.4552 4.49812C18.2991 5.33835 18.9681 6.33762 19.4233 7.43809C19.8785 8.53857 20.1109 9.71839 20.1071 10.9093C20.1043 15.9051 16.0398 19.9696 11.0458 19.9696ZM18.7577 3.19737C17.7478 2.18072 16.546 1.37461 15.2222 0.825771C13.8984 0.276929 12.4789 -0.00373656 11.0458 3.75628e-05C5.03708 3.75628e-05 0.146663 4.89045 0.143913 10.901C0.143913 12.8224 0.645329 14.6979 1.59958 16.3506L0.0522461 22L5.83183 20.4839C7.43012 21.3546 9.22115 21.811 11.0412 21.8112H11.0458C17.0537 21.8112 21.945 16.9208 21.9477 10.9093C21.9522 9.47679 21.6725 8.05763 21.125 6.7339C20.5774 5.41017 19.7728 4.20815 18.7577 3.19737Z"/></svg>';

	printf(
		'<a class="pe-whatsapp" href="%s" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp" style="position:fixed;right:20px;bottom:20px;z-index:9999;display:inline-flex;align-items:center;gap:8px;padding:12px 16px;border-radius:999px;background:#25D366;color:#fff;font-family:Inter,Arial,sans-serif;font-size:14px;font-weight:600;line-height:20px;text-decoration:none;box-shadow:0 4px 6px -4px rgba(0,0,0,0.1),0 10px 15px -3px rgba(0,0,0,0.1);">%s<span>WhatsApp</span></a>',
		esc_url( $url ),
		$icono
	);
}
add_action( 'wp_footer', 'pe_boton_whatsapp' );