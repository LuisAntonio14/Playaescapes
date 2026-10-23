<?php
/**
 * Módulo Formulario: shortcode [pe_formulario] y recepción de la solicitud.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Formulario: se coloca en cualquier página con el shortcode [pe_formulario].
// Opcional: [pe_formulario hotel="Sandos Caracol Eco Resort"] lo deja prellenado.
function pe_formulario_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'hotel'     => '',
		'promocion' => '',
	), $atts );

	// También se puede prellenar desde la dirección: ?pe_reserva_hotel=...&pe_reserva_promo=...
	// (No usar "pe_hotel": WordPress lo reserva para el tipo de contenido Hoteles.)
	if ( '' === $atts['hotel'] && isset( $_GET['pe_reserva_hotel'] ) ) {
		$hotel_url = sanitize_text_field( wp_unslash( $_GET['pe_reserva_hotel'] ) );
		if ( in_array( $hotel_url, pe_lista_hoteles(), true ) ) {
			$atts['hotel'] = $hotel_url;
		}
	}
	if ( '' === $atts['promocion'] && isset( $_GET['pe_reserva_promo'] ) ) {
		$atts['promocion'] = sanitize_text_field( wp_unslash( $_GET['pe_reserva_promo'] ) );
	}

	$hoteles = pe_lista_hoteles();
	$paises = array( 'México', 'Argentina', 'Canadá', 'Colombia', 'USA', 'Otro' );

	// Si viene de un error, recupera lo que el cliente ya había escrito.
	$previos = array();
	if ( isset( $_GET['pe_datos'] ) ) {
		$token     = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['pe_datos'] ) );
		$guardados = get_transient( 'pe_form_' . $token );
		if ( is_array( $guardados ) ) {
			$previos = $guardados;
		}
	}
	if ( isset( $previos['hotel'] ) && in_array( $previos['hotel'], $hoteles, true ) ) {
		$atts['hotel'] = $previos['hotel'];
	}
	$valor = function ( $campo, $defecto = '' ) use ( $previos ) {
		return ( isset( $previos[ $campo ] ) && '' !== $previos[ $campo ] ) ? $previos[ $campo ] : $defecto;
	};
	// Las UTM de la campaña viajan ocultas con el lead.
	$utm = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $clave ) {
		$utm[ $clave ] = isset( $_GET[ $clave ] ) ? sanitize_text_field( wp_unslash( $_GET[ $clave ] ) ) : '';
	}

	ob_start();

	if ( isset( $_GET['pe_error'] ) ) {
		$mensajes = array(
			'sesion'         => 'La página estuvo abierta mucho tiempo. Recárgala e inténtalo de nuevo.',
			'hotel'          => 'Selecciona un hotel.',
			'fechas'         => 'Indica las fechas de llegada y salida.',
			'llegada_pasada' => 'La fecha de llegada no puede ser anterior a hoy.',
			'salida'         => 'La fecha de salida debe ser posterior a la de llegada.',
			'nombre'         => 'Escribe tu nombre.',
			'apellido'       => 'Escribe tu apellido.',
			'correo'         => 'Escribe un correo electrónico válido.',
			'correo2'        => 'Los correos no coinciden.',
			'telefono'       => 'El teléfono debe tener de 10 a 15 dígitos.',
		);

		$codigos = explode( ',', sanitize_text_field( wp_unslash( $_GET['pe_error'] ) ) );
		$lista   = array();
		foreach ( $codigos as $codigo ) {
			if ( isset( $mensajes[ $codigo ] ) ) {
				$lista[] = $mensajes[ $codigo ];
			}
		}

		echo '<div class="pe-mensaje-error">';
		echo '<p>Revisa los datos e inténtalo de nuevo:</p>';
		if ( $lista ) {
			echo '<ul>';
			foreach ( $lista as $mensaje ) {
				echo '<li>' . esc_html( $mensaje ) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}
	?>
	<form class="pe-formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pe_enviar_lead">
		<?php wp_nonce_field( 'pe_enviar_lead', 'pe_form_nonce' ); ?>
		<input type="hidden" name="promocion" value="<?php echo esc_attr( $atts['promocion'] ); ?>">
		<?php foreach ( $utm as $clave => $valor_utm ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $clave ); ?>" value="<?php echo esc_attr( $valor_utm ); ?>">
		<?php endforeach; ?>

		<!-- Campo trampa contra spam: las personas no lo ven ni lo llenan. -->
		<div style="position:absolute;left:-9999px;" aria-hidden="true">
			<input type="text" name="pe_sitio_web" tabindex="-1" autocomplete="off">
		</div>

		<p>
			<label for="pe_f_hotel">Hotel</label>
			<select name="hotel" id="pe_f_hotel" required>
				<option value="">Selecciona un hotel</option>
				<?php foreach ( $hoteles as $hotel ) : ?>
					<option value="<?php echo esc_attr( $hotel ); ?>" <?php selected( $atts['hotel'], $hotel ); ?>><?php echo esc_html( $hotel ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
				<p>
			<label for="pe_f_llegada">Fecha de llegada</label>
			<input type="date" name="llegada" id="pe_f_llegada" value="<?php echo esc_attr( $valor( 'llegada' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_salida">Fecha de salida</label>
			<input type="date" name="salida" id="pe_f_salida" value="<?php echo esc_attr( $valor( 'salida' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_adultos">Adultos</label>
			<input type="number" name="adultos" id="pe_f_adultos" min="1" max="20" value="<?php echo esc_attr( $valor( 'adultos', '2' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_ninos">Niños</label>
			<input type="number" name="ninos" id="pe_f_ninos" min="0" max="10" value="<?php echo esc_attr( $valor( 'ninos', '0' ) ); ?>">
		</p>
		<p>
			<label for="pe_f_habitaciones">Habitaciones</label>
			<input type="number" name="habitaciones" id="pe_f_habitaciones" min="1" max="10" value="<?php echo esc_attr( $valor( 'habitaciones', '1' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_nombre">Nombre</label>
			<input type="text" name="nombre" id="pe_f_nombre" value="<?php echo esc_attr( $valor( 'nombre' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_apellido">Apellido</label>
			<input type="text" name="apellido" id="pe_f_apellido" value="<?php echo esc_attr( $valor( 'apellido' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_correo">Correo electrónico</label>
			<input type="email" name="correo" id="pe_f_correo" value="<?php echo esc_attr( $valor( 'correo' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_correo2">Confirma tu correo</label>
			<input type="email" name="correo2" id="pe_f_correo2" value="<?php echo esc_attr( $valor( 'correo2' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_telefono">Teléfono</label>
			<input type="tel" name="telefono" id="pe_f_telefono" value="<?php echo esc_attr( $valor( 'telefono' ) ); ?>" required>
		</p>
		<p>
			<label for="pe_f_pais">País de residencia</label>
			<select name="pais" id="pe_f_pais" required>
				<option value="">Selecciona un país</option>
				<?php foreach ( $paises as $pais ) : ?>
					<option value="<?php echo esc_attr( $pais ); ?>" <?php selected( $valor( 'pais' ), $pais ); ?>><?php echo esc_html( $pais ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php echo pe_leyenda_privacidad( true ); ?>
		<p>
			<button type="submit">Proceder a reservar</button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'pe_formulario', 'pe_formulario_shortcode' );

// Recibe el formulario, lo valida y lo guarda como lead.
function pe_procesar_formulario() {
	$volver = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$error  = add_query_arg( 'pe_error', '1', remove_query_arg( 'pe_error', $volver ) );

	// Llave de seguridad (nonce).
	if ( ! isset( $_POST['pe_form_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_form_nonce'], 'pe_enviar_lead' ) ) {
		wp_safe_redirect( add_query_arg( 'pe_error', 'sesion', remove_query_arg( 'pe_error', $volver ) ) );
		exit;
	}

	// Si el campo trampa viene lleno, es un robot: se finge éxito y no se guarda nada.
	if ( ! empty( $_POST['pe_sitio_web'] ) ) {
		wp_safe_redirect( home_url( '/gracias/' ) );
		exit;
	}

	$datos = wp_unslash( $_POST );

	$correo   = isset( $datos['correo'] ) ? sanitize_email( $datos['correo'] ) : '';
	$correo2  = isset( $datos['correo2'] ) ? sanitize_email( $datos['correo2'] ) : '';
	$telefono = isset( $datos['telefono'] ) ? preg_replace( '/[\s\-\(\)\+]/', '', $datos['telefono'] ) : '';
	$nombre   = isset( $datos['nombre'] ) ? trim( $datos['nombre'] ) : '';
	$apellido = isset( $datos['apellido'] ) ? trim( $datos['apellido'] ) : '';
	$hotel    = isset( $datos['hotel'] ) ? trim( $datos['hotel'] ) : '';

	$llegada = isset( $datos['llegada'] ) ? sanitize_text_field( $datos['llegada'] ) : '';
	$salida  = isset( $datos['salida'] ) ? sanitize_text_field( $datos['salida'] ) : '';
	$hoy     = current_time( 'Y-m-d' );
	$formato = '/^\d{4}-\d{2}-\d{2}$/';

	// Validaciones campo por campo. Cada falla agrega un código al aviso.
	$errores = array();

	if ( ! in_array( $hotel, pe_lista_hoteles(), true ) ) {
		$errores[] = 'hotel';
	}
	if ( ! preg_match( $formato, $llegada ) || ! preg_match( $formato, $salida ) ) {
		$errores[] = 'fechas';
	} elseif ( $llegada < $hoy ) {
		$errores[] = 'llegada_pasada';
	} elseif ( $salida <= $llegada ) {
		$errores[] = 'salida';
	}
	if ( '' === $nombre ) {
		$errores[] = 'nombre';
	}
	if ( '' === $apellido ) {
		$errores[] = 'apellido';
	}
	if ( ! is_email( $correo ) ) {
		$errores[] = 'correo';
	} elseif ( $correo !== $correo2 ) {
		$errores[] = 'correo2';
	}
	// Teléfono de 10 a 15 dígitos (misma regla del sitio actual).
	if ( ! preg_match( '/^\d{10,15}$/', $telefono ) ) {
		$errores[] = 'telefono';
	}

	if ( $errores ) {
		// Guarda lo escrito 10 minutos para no hacer que el cliente lo llene de nuevo.
		// En la dirección solo viaja un código aleatorio, nunca los datos.
		$token   = wp_generate_password( 20, false );
		$guardar = array();
		foreach ( array( 'llegada', 'salida', 'adultos', 'ninos', 'habitaciones', 'nombre', 'apellido', 'correo', 'correo2', 'telefono', 'pais', 'hotel' ) as $campo ) {
			$guardar[ $campo ] = isset( $datos[ $campo ] ) ? sanitize_text_field( $datos[ $campo ] ) : '';
		}
		set_transient( 'pe_form_' . $token, $guardar, 10 * MINUTE_IN_SECONDS );

		$destino = add_query_arg(
			array(
				'pe_error' => implode( ',', $errores ),
				'pe_datos' => $token,
			),
			remove_query_arg( array( 'pe_error', 'pe_datos' ), $volver )
		);
		wp_safe_redirect( $destino );
		exit;
	}

	// Evita duplicados: misma solicitud (correo, hotel y fechas) en los últimos 10 minutos.
	$duplicado = get_posts( array(
		'post_type'      => 'pe_lead',
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
			array( 'key' => '_pe_lead_correo', 'value' => $correo ),
			array( 'key' => '_pe_lead_hotel', 'value' => $hotel ),
			array( 'key' => '_pe_lead_llegada', 'value' => $llegada ),
			array( 'key' => '_pe_lead_salida', 'value' => $salida ),
		),
	) );

	if ( $duplicado ) {
		wp_safe_redirect( home_url( '/gracias/' ) );
		exit;
	}

	$id = pe_crear_lead( $datos );

	if ( ! $id ) {
		wp_safe_redirect( $error );
		exit;
	}

	pe_enviar_aviso_lead( $id );

	wp_safe_redirect( home_url( '/gracias/' ) );
	exit;
}
add_action( 'admin_post_nopriv_pe_enviar_lead', 'pe_procesar_formulario' ); // visitantes
add_action( 'admin_post_pe_enviar_lead', 'pe_procesar_formulario' );        // con sesión


// Dirección de la página que tiene el formulario.
// Cuando exista la página definitiva, se cambia solo aquí.
function pe_url_formulario() {
	return home_url( '/formulario-de-prueba/' );
}

// Botón "Reservar": [pe_boton_reservar]
// En una ficha de hotel o una promoción toma el hotel (y la promoción) automáticamente.
// Texto opcional: [pe_boton_reservar texto="Quiero esta promoción"]
function pe_boton_reservar_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'texto' => 'Reservar',
	), $atts );

	$args = array();
	$post = get_post();

	if ( $post && in_array( $post->post_type, array( 'pe_hotel', 'hot_escape' ), true ) ) {
		$hotel = get_post_meta( $post->ID, '_pe_hotel', true );
		if ( '' !== $hotel ) {
			$args['pe_reserva_hotel'] = $hotel;
		}
		if ( 'hot_escape' === $post->post_type ) {
			$args['pe_reserva_promo'] = $post->post_title;		}
	}

	$url = add_query_arg( array_map( 'rawurlencode', $args ), pe_url_formulario() );

	return sprintf(
		'<a class="pe-boton-reservar" href="%s">%s</a>',
		esc_url( $url ),
		esc_html( $atts['texto'] )
	);
}
add_shortcode( 'pe_boton_reservar', 'pe_boton_reservar_shortcode' );