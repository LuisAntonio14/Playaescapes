<?php
/**
 * Módulo Contacto: consultas generales (distintas de las solicitudes de reserva).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Campos de una consulta: clave => etiqueta. Se usa para guardar, mostrar y avisar.
function pe_campos_consulta() {
	return array(
		'nombre'       => 'Nombre',
		'correo'       => 'Correo',
		'telefono'     => 'Teléfono',
		'mensaje'      => 'Mensaje',
		'utm_source'   => 'UTM source',
		'utm_medium'   => 'UTM medium',
		'utm_campaign' => 'UTM campaign',
	);
}

// Tipo de contenido privado "Consultas": visible solo en el administrador.
function pe_registrar_consultas() {
	register_post_type( 'pe_consulta', array(
		'labels' => array(
			'name'          => 'Consultas',
			'singular_name' => 'Consulta',
			'edit_item'     => 'Ver consulta',
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-format-chat',
		'supports'     => array( 'title' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ), // No se crean a mano
		'map_meta_cap' => true,
	) );
}
add_action( 'init', 'pe_registrar_consultas' );

// Guarda una consulta. Devuelve el ID creado, o 0 si falló.
function pe_crear_consulta( $datos ) {
	$limpios = array();
	foreach ( array_keys( pe_campos_consulta() ) as $campo ) {
		$valor = isset( $datos[ $campo ] ) ? $datos[ $campo ] : '';
		if ( 'correo' === $campo ) {
			$limpios[ $campo ] = sanitize_email( $valor );
		} elseif ( 'mensaje' === $campo ) {
			$limpios[ $campo ] = sanitize_textarea_field( $valor );
		} else {
			$limpios[ $campo ] = sanitize_text_field( $valor );
		}
	}

	$titulo = '' !== $limpios['nombre'] ? $limpios['nombre'] : 'Consulta sin nombre';

	$id = wp_insert_post( array(
		'post_type'   => 'pe_consulta',
		'post_status' => 'publish',
		'post_title'  => $titulo,
	) );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	foreach ( $limpios as $campo => $valor ) {
		update_post_meta( $id, '_pe_consulta_' . $campo, $valor );
	}

	return $id;
}

// Cuadro de solo lectura para ver cada consulta en el administrador.
function pe_agregar_metabox_consulta() {
	add_meta_box( 'pe_datos_consulta', 'Datos de la consulta', 'pe_mostrar_metabox_consulta', 'pe_consulta', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_consulta' );

function pe_mostrar_metabox_consulta( $post ) {
	echo '<table class="widefat striped"><tbody>';
	foreach ( pe_campos_consulta() as $clave => $etiqueta ) {
		printf(
			'<tr><th style="width:200px;">%s</th><td>%s</td></tr>',
			esc_html( $etiqueta ),
			nl2br( esc_html( get_post_meta( $post->ID, '_pe_consulta_' . $clave, true ) ) )
		);
	}
	echo '</tbody></table>';
}


// Formulario de consultas: [pe_contacto]
function pe_contacto_shortcode() {
	// Si viene de un error, recupera lo que la persona ya había escrito.
	$previos = array();
	if ( isset( $_GET['pe_contacto_datos'] ) ) {
		$token     = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['pe_contacto_datos'] ) );
		$guardados = get_transient( 'pe_contacto_' . $token );
		if ( is_array( $guardados ) ) {
			$previos = $guardados;
		}
	}
	$previo = function ( $campo ) use ( $previos ) {
		return isset( $previos[ $campo ] ) ? $previos[ $campo ] : '';
	};

	// Las UTM de la campaña viajan ocultas con la consulta.
	$utm = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $clave ) {
		$utm[ $clave ] = isset( $_GET[ $clave ] ) ? sanitize_text_field( wp_unslash( $_GET[ $clave ] ) ) : '';
	}

	ob_start();

	if ( isset( $_GET['pe_contacto_ok'] ) ) {
		echo '<div class="pe-mensaje-exito"><p>Gracias, recibimos tu consulta. Te responderemos a la brevedad.</p></div>';
	}

	if ( isset( $_GET['pe_contacto_error'] ) ) {
		$mensajes = array(
			'sesion'   => 'La página estuvo abierta mucho tiempo. Recárgala e inténtalo de nuevo.',
			'nombre'   => 'Escribe tu nombre.',
			'correo'   => 'Escribe un correo electrónico válido.',
			'telefono' => 'Si escribes un teléfono, debe tener de 10 a 15 dígitos.',
			'mensaje'  => 'Escribe tu mensaje.',
			'guardar'  => 'No pudimos enviar tu consulta. Inténtalo de nuevo.',
		);

		$codigos = explode( ',', sanitize_text_field( wp_unslash( $_GET['pe_contacto_error'] ) ) );
		echo '<div class="pe-mensaje-error"><p>Revisa los datos e inténtalo de nuevo:</p><ul>';
		foreach ( $codigos as $codigo ) {
			if ( isset( $mensajes[ $codigo ] ) ) {
				echo '<li>' . esc_html( $mensajes[ $codigo ] ) . '</li>';
			}
		}
		echo '</ul></div>';
	}
	?>
	<form id="pe-contacto" class="pe-formulario pe-contacto" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pe_enviar_consulta">
		<?php wp_nonce_field( 'pe_enviar_consulta', 'pe_contacto_nonce' ); ?>
		<?php foreach ( $utm as $clave => $valor_utm ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $clave ); ?>" value="<?php echo esc_attr( $valor_utm ); ?>">
		<?php endforeach; ?>

		<!-- Campo trampa contra spam: las personas no lo ven ni lo llenan. -->
		<div style="position:absolute;left:-9999px;" aria-hidden="true">
			<input type="text" name="pe_sitio_web" tabindex="-1" autocomplete="off">
		</div>

		<p>
			<label for="pe_c_nombre">Nombre</label>
			<input type="text" name="nombre" id="pe_c_nombre" value="<?php echo esc_attr( $previo( 'nombre' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_c_correo">Correo electrónico</label>
			<input type="email" name="correo" id="pe_c_correo" value="<?php echo esc_attr( $previo( 'correo' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_c_telefono">Teléfono (opcional)</label>
			<input type="tel" name="telefono" id="pe_c_telefono" value="<?php echo esc_attr( $previo( 'telefono' ) ); ?>">
		</p>
		<p>
			<label for="pe_c_mensaje">Mensaje</label>
			<textarea name="mensaje" id="pe_c_mensaje" rows="5" maxlength="2000" required><?php echo esc_textarea( $previo( 'mensaje' ) ); ?></textarea>
		</p>
		<?php echo pe_leyenda_privacidad(); ?>
		<p>
			<button type="submit">Enviar consulta</button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'pe_contacto', 'pe_contacto_shortcode' );

// Recibe el formulario de consultas, lo valida y lo guarda.
function pe_procesar_contacto() {
	$volver = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$volver = remove_query_arg( array( 'pe_contacto_ok', 'pe_contacto_error', 'pe_contacto_datos' ), $volver );

	// Llave de seguridad (nonce).
	if ( ! isset( $_POST['pe_contacto_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_contacto_nonce'], 'pe_enviar_consulta' ) ) {
		wp_safe_redirect( add_query_arg( 'pe_contacto_error', 'sesion', $volver ) . '#pe-contacto' );
		exit;
	}

	// Si el campo trampa viene lleno, es un robot: se finge éxito y no se guarda nada.
	if ( ! empty( $_POST['pe_sitio_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'pe_contacto_ok', '1', $volver ) . '#pe-contacto' );
		exit;
	}

	$datos = wp_unslash( $_POST );

	$nombre   = isset( $datos['nombre'] ) ? trim( $datos['nombre'] ) : '';
	$correo   = isset( $datos['correo'] ) ? sanitize_email( $datos['correo'] ) : '';
	$telefono = isset( $datos['telefono'] ) ? preg_replace( '/[\s\-\(\)\+]/', '', $datos['telefono'] ) : '';
	$mensaje  = isset( $datos['mensaje'] ) ? trim( $datos['mensaje'] ) : '';

	$errores = array();
	if ( '' === $nombre ) {
		$errores[] = 'nombre';
	}
	if ( ! is_email( $correo ) ) {
		$errores[] = 'correo';
	}
	if ( '' !== $telefono && ! preg_match( '/^\d{10,15}$/', $telefono ) ) {
		$errores[] = 'telefono';
	}
	if ( '' === $mensaje ) {
		$errores[] = 'mensaje';
	}

	if ( $errores ) {
		// Guarda lo escrito 10 minutos; en la dirección solo viaja un código aleatorio.
		$token = wp_generate_password( 20, false );
		set_transient( 'pe_contacto_' . $token, array(
			'nombre'   => sanitize_text_field( $nombre ),
			'correo'   => isset( $datos['correo'] ) ? sanitize_text_field( $datos['correo'] ) : '',
			'telefono' => isset( $datos['telefono'] ) ? sanitize_text_field( $datos['telefono'] ) : '',
			'mensaje'  => sanitize_textarea_field( $mensaje ),
		), 10 * MINUTE_IN_SECONDS );

		$destino = add_query_arg(
			array(
				'pe_contacto_error' => implode( ',', $errores ),
				'pe_contacto_datos' => $token,
			),
			$volver
		);
		wp_safe_redirect( $destino . '#pe-contacto' );
		exit;
	}

	// Evita duplicados: mismo correo y mismo mensaje en los últimos 10 minutos.
	$duplicada = get_posts( array(
		'post_type'      => 'pe_consulta',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'date_query'     => array(
			array(
				'column' => 'post_date_gmt',
				'after'  => gmdate( 'Y-m-d H:i:s', time() - 10 * MINUTE_IN_SECONDS ),
			),
		),
		'meta_query'     => array(
			array( 'key' => '_pe_consulta_correo', 'value' => $correo ),
			array( 'key' => '_pe_consulta_mensaje', 'value' => sanitize_textarea_field( $mensaje ) ),
		),
	) );

	if ( $duplicada ) {
		wp_safe_redirect( add_query_arg( 'pe_contacto_ok', '1', $volver ) . '#pe-contacto' );
		exit;
	}

	$datos['telefono'] = $telefono;
	$id = pe_crear_consulta( $datos );

	if ( ! $id ) {
		wp_safe_redirect( add_query_arg( 'pe_contacto_error', 'guardar', $volver ) . '#pe-contacto' );
		exit;
	}

	pe_enviar_aviso_consulta( $id );

	wp_safe_redirect( add_query_arg( 'pe_contacto_ok', '1', $volver ) . '#pe-contacto' );
	exit;
}
add_action( 'admin_post_nopriv_pe_enviar_consulta', 'pe_procesar_contacto' ); // visitantes
add_action( 'admin_post_pe_enviar_consulta', 'pe_procesar_contacto' );        // con sesión


// Columnas de la lista de Consultas en el administrador.
function pe_columnas_consultas( $columnas ) {
	$nuevas = array();
	if ( isset( $columnas['cb'] ) ) {
		$nuevas['cb'] = $columnas['cb'];
	}
	$nuevas['title']           = 'Nombre';
	$nuevas['pe_col_correo']   = 'Correo';
	$nuevas['pe_col_telefono'] = 'Teléfono';
	$nuevas['pe_col_mensaje']  = 'Mensaje';
	$nuevas['pe_col_origen']   = 'Origen (UTM)';
	$nuevas['date']            = 'Recibida';
	return $nuevas;
}
add_filter( 'manage_pe_consulta_posts_columns', 'pe_columnas_consultas' );

function pe_contenido_columnas_consultas( $columna, $post_id ) {
	$dato = function ( $campo ) use ( $post_id ) {
		return get_post_meta( $post_id, '_pe_consulta_' . $campo, true );
	};

	switch ( $columna ) {
		case 'pe_col_correo':
			echo esc_html( $dato( 'correo' ) );
			break;
		case 'pe_col_telefono':
			echo esc_html( $dato( 'telefono' ) );
			break;
		case 'pe_col_mensaje':
			echo esc_html( wp_trim_words( $dato( 'mensaje' ), 15 ) );
			break;
		case 'pe_col_origen':
			$partes = array_filter( array( $dato( 'utm_source' ), $dato( 'utm_medium' ), $dato( 'utm_campaign' ) ) );
			echo esc_html( implode( ' / ', $partes ) );
			break;
	}
}
add_action( 'manage_pe_consulta_posts_custom_column', 'pe_contenido_columnas_consultas', 10, 2 );