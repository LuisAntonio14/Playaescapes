<?php
/**
 * PlayaEscapes – Enlaces según el idioma (Polylang).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve la traducción de una página (ID o URL) al idioma indicado.
 * Si no hay traducción, devuelve el valor original sin cambios.
 */
function pe_valor_traducido( $valor, $idioma ) {
	if ( ! function_exists( 'pll_get_post' ) || '' === $valor || null === $valor ) {
		return $valor;
	}

	// Caso 1: el ajuste guarda el ID de la página.
	if ( is_numeric( $valor ) ) {
		$traducida = pll_get_post( (int) $valor, $idioma );
		return $traducida ? $traducida : $valor;
	}

	// Caso 2: el ajuste guarda la URL de la página.
	if ( is_string( $valor ) && 0 === strpos( $valor, home_url() ) ) {
		$id = url_to_postid( $valor );
		if ( $id ) {
			$traducida = pll_get_post( $id, $idioma );
			if ( $traducida && $traducida !== $id ) {
				return get_permalink( $traducida );
			}
		}
	}

	return $valor;
}

/**
 * 1. Ajustes con páginas: se traducen solo en el sitio público.
 */
function pe_ajustes_segun_idioma( $ajustes ) {
	if ( is_admin() || ! is_array( $ajustes ) || ! function_exists( 'pll_current_language' ) ) {
		return $ajustes;
	}

	$idioma        = pll_current_language();
	$predeterminado = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
	if ( ! $idioma || $idioma === $predeterminado ) {
		return $ajustes;
	}

	foreach ( array( 'aviso_privacidad', 'terminos', 'pagina_reserva' ) as $clave ) {
		if ( isset( $ajustes[ $clave ] ) ) {
			$ajustes[ $clave ] = pe_valor_traducido( $ajustes[ $clave ], $idioma );
		}
	}

	return $ajustes;
}
add_filter( 'option_pe_ajustes', 'pe_ajustes_segun_idioma' );

/**
 * Detecta el idioma de la página desde la que se envió el formulario.
 */
function pe_idioma_de_origen() {
	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
		return '';
	}

	$origen = wp_get_referer();
	if ( ! $origen ) {
		return '';
	}

	// Compara solo la ruta (/en/...), porque la página de origen puede venir completa o relativa.
	$ruta = trailingslashit( (string) wp_parse_url( $origen, PHP_URL_PATH ) );
	$base = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
	foreach ( pll_languages_list() as $slug ) {
		if ( $slug === pll_default_language() ) {
			continue;
		}
		if ( 0 === strpos( $ruta, $base . '/' . $slug . '/' ) ) {
			return $slug;
		}
	}
	return '';
}

/**
 * 2. Redirección a "Gracias" en el idioma del formulario.
 */
function pe_gracias_segun_idioma( $destino ) {
	$gracias = home_url( '/gracias/' );
	if ( ! is_string( $destino ) || 0 !== strpos( $destino, $gracias ) ) {
		return $destino;
	}

	$idioma = pe_idioma_de_origen();
	if ( '' === $idioma ) {
		return $destino;
	}

	$pagina = get_page_by_path( 'gracias' );
	if ( ! $pagina ) {
		return $destino;
	}

	$traducida = pll_get_post( $pagina->ID, $idioma );
	if ( ! $traducida ) {
		return $destino;
	}

	// Conserva los parámetros que traiga la URL original (?...).
	$parametros = (string) substr( $destino, strlen( $gracias ) );
	return get_permalink( $traducida ) . $parametros;
}
add_filter( 'wp_redirect', 'pe_gracias_segun_idioma' );

/**
 * 3. Polylang no copia entre idiomas los textos que deben ser distintos en cada idioma
 *    (textos de portada y "Qué incluye" de los hoteles).
 */
function pe_no_sincronizar_textos( $metas ) {
	return array_values( array_filter( (array) $metas, function ( $meta ) {
		return 0 !== strpos( $meta, '_pe_portada_' )
			&& 0 !== strpos( $meta, '_pe_oferta_' )
			&& ! in_array( $meta, array( '_pe_hotel_incluye', '_pe_hotel_ubicacion', '_pe_hotel_tipo' ), true );
	} ) );
}
add_filter( 'pll_copy_post_metas', 'pe_no_sincronizar_textos' );

/**
 * 4. Idioma en leads y consultas.
 */
function pe_nombre_idioma( $slug ) {
	if ( '' === $slug ) {
		return '—';
	}
	if ( function_exists( 'PLL' ) ) {
		$idioma = PLL()->model->get_language( $slug );
		if ( $idioma ) {
			return $idioma->name;
		}
	}
	return strtoupper( $slug );
}

// Idioma del lead o consulta que se está guardando en esta petición.
$GLOBALS['pe_idioma_registro'] = '';

function pe_guardar_idioma_registro( $post_id, $post, $actualizacion ) {
	if ( $actualizacion || 'auto-draft' === $post->post_status ) {
		return;
	}
	if ( ! in_array( $post->post_type, array( 'pe_lead', 'pe_consulta' ), true ) ) {
		return;
	}
	if ( ! function_exists( 'pll_default_language' ) ) {
		return;
	}

	$idioma = pe_idioma_de_origen();
	if ( '' === $idioma ) {
		$idioma = pll_default_language();
	}

	update_post_meta( $post_id, 'pe_idioma', $idioma );
	$GLOBALS['pe_idioma_registro'] = $idioma;
}
add_action( 'wp_insert_post', 'pe_guardar_idioma_registro', 10, 3 );

// Columna "Idioma" en las listas de Leads y Consultas.
function pe_columna_idioma( $columnas ) {
	$columnas['pe_idioma'] = 'Idioma';
	return $columnas;
}
add_filter( 'manage_pe_lead_posts_columns', 'pe_columna_idioma', 20 );
add_filter( 'manage_pe_consulta_posts_columns', 'pe_columna_idioma', 20 );

function pe_columna_idioma_valor( $columna, $post_id ) {
	if ( 'pe_idioma' === $columna ) {
		echo esc_html( pe_nombre_idioma( (string) get_post_meta( $post_id, 'pe_idioma', true ) ) );
	}
}
add_action( 'manage_pe_lead_posts_custom_column', 'pe_columna_idioma_valor', 10, 2 );
add_action( 'manage_pe_consulta_posts_custom_column', 'pe_columna_idioma_valor', 10, 2 );

// Línea "Idioma" al final del correo de aviso.
function pe_idioma_en_correo( $datos ) {
	$idioma = $GLOBALS['pe_idioma_registro'];
	if ( '' === $idioma || empty( $datos['message'] ) ) {
		return $datos;
	}

	$linea   = 'Idioma: ' . pe_nombre_idioma( $idioma );
	$es_html = false !== strpos( $datos['message'], '</' );
	$datos['message'] .= $es_html ? '<p>' . esc_html( $linea ) . '</p>' : "\n\n" . $linea;

	return $datos;
}
add_filter( 'wp_mail', 'pe_idioma_en_correo' );