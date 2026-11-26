<?php
/**
 * Módulo Leads (administrador): hace más fácil de usar Leads y Consultas.
 * - Ayuda arriba de cada lista.
 * - Filtros por hotel e idioma.
 * - Vista de cada lead y consulta ordenada por grupos, con botones para responder y llamar.
 * No cambia cómo se guardan los datos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ===== Ayuda arriba de las listas =====
function pe_leads_ayuda() {
	$pantalla = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $pantalla || 'edit' !== $pantalla->base ) {
		return;
	}
	if ( 'pe_lead' === $pantalla->post_type ) {
		?>
		<div class="notice notice-info pe-ayuda-lista">
			<p><strong>Leads: solicitudes de reserva que llegan desde el sitio.</strong></p>
			<ul>
				<li>Cada solicitud también llega por correo a los correos de avisos (Ajustes &gt; PlayaEscapes).</li>
				<li>Da clic en el <strong>nombre</strong> para ver todos los datos y responder o llamar.</li>
				<li>Usa los filtros de <strong>hotel</strong> e <strong>idioma</strong> y da clic en <strong>Filtrar</strong>.</li>
				<li><strong>Exportar a Excel (CSV)</strong> descarga todos los leads para trabajarlos en Excel.</li>
			</ul>
		</div>
		<?php
	} elseif ( 'pe_consulta' === $pantalla->post_type ) {
		?>
		<div class="notice notice-info pe-ayuda-lista">
			<p><strong>Consultas: mensajes del formulario de Contacto</strong> (preguntas generales, no solicitudes de reserva).</p>
			<ul>
				<li>Cada consulta también llega por correo a los correos de avisos (Ajustes &gt; PlayaEscapes).</li>
				<li>Da clic en el <strong>nombre</strong> para leer el mensaje completo y responder.</li>
			</ul>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pe_leads_ayuda' );

// ===== Filtros por hotel e idioma =====
function pe_leads_filtros( $post_type ) {
	if ( ! in_array( $post_type, array( 'pe_lead', 'pe_consulta' ), true ) ) {
		return;
	}
	global $wpdb;

	if ( 'pe_lead' === $post_type ) {
		$hoteles = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_pe_lead_hotel' AND meta_value <> '' ORDER BY meta_value"
		);
		$actual = isset( $_GET['pe_f_hotel'] ) ? sanitize_text_field( wp_unslash( $_GET['pe_f_hotel'] ) ) : '';
		echo '<label class="screen-reader-text" for="pe_f_hotel">Filtrar por hotel</label>';
		echo '<select name="pe_f_hotel" id="pe_f_hotel"><option value="">Todos los hoteles</option>';
		foreach ( $hoteles as $hotel ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $hotel ), selected( $actual, $hotel, false ), esc_html( $hotel ) );
		}
		echo '</select>';
	}

	if ( function_exists( 'pll_languages_list' ) ) {
		$actual = isset( $_GET['pe_f_idioma'] ) ? sanitize_key( wp_unslash( $_GET['pe_f_idioma'] ) ) : '';
		echo '<label class="screen-reader-text" for="pe_f_idioma">Filtrar por idioma</label>';
		echo '<select name="pe_f_idioma" id="pe_f_idioma"><option value="">Todos los idiomas</option>';
		foreach ( pll_languages_list( array( 'fields' => '' ) ) as $idioma ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $idioma->slug ), selected( $actual, $idioma->slug, false ), esc_html( $idioma->name ) );
		}
		echo '</select>';
	}
}
add_action( 'restrict_manage_posts', 'pe_leads_filtros', 5 );

function pe_leads_aplicar_filtros( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$tipo = $query->get( 'post_type' );
	if ( ! in_array( $tipo, array( 'pe_lead', 'pe_consulta' ), true ) ) {
		return;
	}

	$condiciones = array();
	if ( 'pe_lead' === $tipo && ! empty( $_GET['pe_f_hotel'] ) ) {
		$condiciones[] = array(
			'key'   => '_pe_lead_hotel',
			'value' => sanitize_text_field( wp_unslash( $_GET['pe_f_hotel'] ) ),
		);
	}
	if ( ! empty( $_GET['pe_f_idioma'] ) ) {
		$condiciones[] = array(
			'key'   => 'pe_idioma',
			'value' => sanitize_key( wp_unslash( $_GET['pe_f_idioma'] ) ),
		);
	}
	if ( $condiciones ) {
		$query->set( 'meta_query', array_merge( array( 'relation' => 'AND' ), $condiciones ) );
	}
}
add_action( 'pre_get_posts', 'pe_leads_aplicar_filtros' );

// ===== Vista de cada lead y consulta =====
function pe_leads_cambiar_vista() {
	remove_meta_box( 'pe_datos_lead', 'pe_lead', 'normal' );
	add_meta_box( 'pe_datos_lead', 'Datos de la solicitud', 'pe_leads_vista_lead', 'pe_lead', 'normal', 'high' );

	remove_meta_box( 'pe_datos_consulta', 'pe_consulta', 'normal' );
	add_meta_box( 'pe_datos_consulta', 'Datos de la consulta', 'pe_leads_vista_consulta', 'pe_consulta', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pe_leads_cambiar_vista', 20 );

// Botones de contacto: responder por correo y llamar.
function pe_leads_botones( $correo, $telefono, $asunto ) {
	$botones = array();
	if ( is_email( $correo ) ) {
		$botones[] = sprintf(
			'<a class="button button-primary" href="%s">Responder por correo</a>',
			esc_url( 'mailto:' . $correo . '?subject=' . rawurlencode( $asunto ) )
		);
	}
	$digitos = preg_replace( '/[^\d+]/', '', (string) $telefono );
	if ( '' !== $digitos ) {
		$botones[] = sprintf( '<a class="button" href="%s">Llamar</a>', esc_url( 'tel:' . $digitos ) );
	}
	if ( $botones ) {
		echo '<p class="pe-lead__botones">' . implode( ' ', $botones ) . '</p>'; // Ya escapado.
	}
}

// Tabla de un grupo de datos.
function pe_leads_grupo( $titulo, $filas ) {
	echo '<h4 class="pe-lead__grupo">' . esc_html( $titulo ) . '</h4>';
	echo '<table class="widefat striped pe-lead__tabla"><tbody>';
	foreach ( $filas as $etiqueta => $valor ) {
		printf(
			'<tr><th>%s</th><td>%s</td></tr>',
			esc_html( $etiqueta ),
			'' === (string) $valor ? '<span class="pe-lead__vacio">—</span>' : nl2br( esc_html( $valor ) )
		);
	}
	echo '</tbody></table>';
}

function pe_leads_idioma( $post_id ) {
	$slug = (string) get_post_meta( $post_id, 'pe_idioma', true );
	return function_exists( 'pe_nombre_idioma' ) ? pe_nombre_idioma( $slug ) : $slug;
}

function pe_leads_vista_lead( $post ) {
	$d = function ( $campo ) use ( $post ) {
		return (string) get_post_meta( $post->ID, '_pe_lead_' . $campo, true );
	};

	$noches = '';
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d( 'llegada' ) ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d( 'salida' ) ) ) {
		$dias = (int) round( ( strtotime( $d( 'salida' ) ) - strtotime( $d( 'llegada' ) ) ) / DAY_IN_SECONDS );
		if ( $dias > 0 ) {
			$noches = $dias . ( 1 === $dias ? ' noche' : ' noches' );
		}
	}
	$fecha = function ( $f ) {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $f ) ? date_i18n( 'j \d\e F \d\e Y', strtotime( $f ) ) : $f;
	};

	echo '<div class="pe-ayuda-panel"><p>Esta pantalla es solo para consultar. Los datos llegan del formulario del sitio y no se editan aquí.</p></div>';
	pe_leads_botones( $d( 'correo' ), $d( 'telefono' ), 'Tu solicitud en PlayaEscapes' . ( '' !== $d( 'hotel' ) ? ' – ' . $d( 'hotel' ) : '' ) );

	pe_leads_grupo( 'Reserva', array(
		'Hotel'        => $d( 'hotel' ),
		'Llegada'      => $fecha( $d( 'llegada' ) ),
		'Salida'       => $fecha( $d( 'salida' ) ),
		'Estancia'     => $noches,
		'Huéspedes'    => trim( $d( 'adultos' ) . ' adultos, ' . ( '' !== $d( 'ninos' ) ? $d( 'ninos' ) : '0' ) . ' niños' ),
		'Habitaciones' => $d( 'habitaciones' ),
		'Promoción'    => $d( 'promocion' ),
	) );
	pe_leads_grupo( 'Cliente', array(
		'Nombre'   => trim( $d( 'nombre' ) . ' ' . $d( 'apellido' ) ),
		'Correo'   => $d( 'correo' ),
		'Teléfono' => $d( 'telefono' ),
		'País'     => $d( 'pais' ),
	) );
	pe_leads_grupo( 'Origen', array(
		'Recibido'     => get_the_date( 'j \d\e F \d\e Y, H:i', $post ),
		'Idioma'       => pe_leads_idioma( $post->ID ),
		'UTM source'   => $d( 'utm_source' ),
		'UTM medium'   => $d( 'utm_medium' ),
		'UTM campaign' => $d( 'utm_campaign' ),
	) );
}

function pe_leads_vista_consulta( $post ) {
	$d = function ( $campo ) use ( $post ) {
		return (string) get_post_meta( $post->ID, '_pe_consulta_' . $campo, true );
	};

	echo '<div class="pe-ayuda-panel"><p>Esta pantalla es solo para consultar. Los datos llegan del formulario de Contacto y no se editan aquí.</p></div>';
	pe_leads_botones( $d( 'correo' ), $d( 'telefono' ), 'Tu consulta en PlayaEscapes' );

	pe_leads_grupo( 'Mensaje', array( 'Mensaje' => $d( 'mensaje' ) ) );
	pe_leads_grupo( 'Cliente', array(
		'Nombre'   => $d( 'nombre' ),
		'Correo'   => $d( 'correo' ),
		'Teléfono' => $d( 'telefono' ),
	) );
	pe_leads_grupo( 'Origen', array(
		'Recibida'     => get_the_date( 'j \d\e F \d\e Y, H:i', $post ),
		'Idioma'       => pe_leads_idioma( $post->ID ),
		'UTM source'   => $d( 'utm_source' ),
		'UTM medium'   => $d( 'utm_medium' ),
		'UTM campaign' => $d( 'utm_campaign' ),
	) );
}

// Estilos de la vista y de la ayuda de las listas.
function pe_leads_estilos() {
	$pantalla = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $pantalla || ! in_array( $pantalla->post_type, array( 'pe_lead', 'pe_consulta' ), true ) ) {
		return;
	}
	?>
	<style>
		.pe-ayuda-lista ul { margin: 0 0 8px 18px; list-style: disc; }
		.pe-lead__botones { margin: 8px 0 16px; }
		.pe-lead__grupo { margin: 18px 0 6px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #50575e; }
		.pe-lead__tabla th { width: 180px; font-weight: 600; }
		.pe-lead__vacio { color: #a7aaad; }
	</style>
	<?php
}
add_action( 'admin_head', 'pe_leads_estilos' );
