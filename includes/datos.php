<?php
/**
 * Módulo Datos: listas compartidas por otros módulos del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lista única de hoteles. Se usa en Hot Escapes y en el formulario.
// Importante: el texto es el valor que se guarda; no cambiarlo sin revisar
// las promociones y leads ya guardados.
function pe_lista_hoteles() {
	return array(
		'Sandos Cancún',
		'Sandos Caracol Eco Resort',
		'Sandos Playacar',
		'Sandos Finisterra',
	);
}