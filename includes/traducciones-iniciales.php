<?php
/**
 * PlayaEscapes – Carga inicial de traducciones al inglés (TEMPORAL).
 *
 * Uso: con sesión de administrador, abrir una sola vez
 *   /wp-admin/?pe_cargar_traducciones=1
 * Solo llena las cadenas que todavía NO tienen traducción
 * (no sobrescribe lo que ya se haya capturado a mano).
 * Después de usarlo, quitar este archivo y su require_once.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_traducciones_iniciales_en() {
	return array(
		'Contenido' => 'Contents',
		'Introducción' => 'Introduction',
	);
}

function pe_cargar_traducciones_iniciales() {
	if ( empty( $_GET['pe_cargar_traducciones'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) ) {
		wp_die( 'Polylang no está activo.' );
	}

	$idioma = PLL()->model->get_language( 'en' );
	if ( ! $idioma ) {
		wp_die( 'No se encontró el idioma inglés (en) en Polylang.' );
	}

	$mo = new PLL_MO();
	$mo->import_from_db( $idioma );

	$cargadas = 0;
	$omitidas = 0;
	foreach ( pe_traducciones_iniciales_en() as $original => $traduccion ) {
		$actual = $mo->translate( $original );
		if ( '' !== $actual && $actual !== $original ) {
			$omitidas++; // Ya tenía traducción: no se toca.
			continue;
		}
		$mo->add_entry( $mo->make_entry( $original, $traduccion ) );
		$cargadas++;
	}
	$mo->export_to_db( $idioma );

	wp_die(
		sprintf(
			'Listo. Traducciones cargadas: %d. Ya existían (no se tocaron): %d. <br><br><a href="%s">Ir a Languages &gt; Translations</a>',
			$cargadas,
			$omitidas,
			esc_url( admin_url( 'admin.php?page=mlang_strings' ) )
		),
		'Traducciones PlayaEscapes',
		array( 'response' => 200 )
	);
}
add_action( 'admin_init', 'pe_cargar_traducciones_iniciales', 99 );
