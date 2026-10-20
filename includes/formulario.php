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

	// Las UTM de la campaña viajan ocultas con el lead.
	$utm = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $clave ) {
		$utm[ $clave ] = isset( $_GET[ $clave ] ) ? sanitize_text_field( wp_unslash( $_GET[ $clave ] ) ) : '';
	}

	ob_start();

	if ( isset( $_GET['pe_error'] ) ) {
		echo '<p class="pe-mensaje-error">Revisa los datos e inténtalo de nuevo.</p>';
	}
	?>
	<form class="pe-formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pe_enviar_lead">
		<?php wp_nonce_field( 'pe_enviar_lead', 'pe_form_nonce' ); ?>
		<input type="hidden" name="promocion" value="<?php echo esc_attr( $atts['promocion'] ); ?>">
		<?php foreach ( $utm as $clave => $valor ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $clave ); ?>" value="<?php echo esc_attr( $valor ); ?>">
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
			<input type="date" name="llegada" id="pe_f_llegada" required>
		</p>
		<p>
			<label for="pe_f_salida">Fecha de salida</label>
			<input type="date" name="salida" id="pe_f_salida" required>
		</p>
		<p>
			<label for="pe_f_adultos">Adultos</label>
			<input type="number" name="adultos" id="pe_f_adultos" min="1" max="20" value="2" required>
		</p>
		<p>
			<label for="pe_f_ninos">Niños</label>
			<input type="number" name="ninos" id="pe_f_ninos" min="0" max="10" value="0">
		</p>
		<p>
			<label for="pe_f_habitaciones">Habitaciones</label>
			<input type="number" name="habitaciones" id="pe_f_habitaciones" min="1" max="10" value="1" required>
		</p>
		<p>
			<label for="pe_f_nombre">Nombre</label>
			<input type="text" name="nombre" id="pe_f_nombre" required>
		</p>
		<p>
			<label for="pe_f_apellido">Apellido</label>
			<input type="text" name="apellido" id="pe_f_apellido" required>
		</p>
		<p>
			<label for="pe_f_correo">Correo electrónico</label>
			<input type="email" name="correo" id="pe_f_correo" required>
		</p>
		<p>
			<label for="pe_f_correo2">Confirma tu correo</label>
			<input type="email" name="correo2" id="pe_f_correo2" required>
		</p>
		<p>
			<label for="pe_f_telefono">Teléfono</label>
			<input type="tel" name="telefono" id="pe_f_telefono" required>
		</p>
		<p>
			<label for="pe_f_pais">País de residencia</label>
			<select name="pais" id="pe_f_pais" required>
				<option value="">Selecciona un país</option>
				<?php foreach ( $paises as $pais ) : ?>
					<option value="<?php echo esc_attr( $pais ); ?>"><?php echo esc_html( $pais ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
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
		wp_safe_redirect( $error );
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

	// Validaciones mínimas: campos obligatorios, correo válido y repetido igual,
	// teléfono de 10 a 15 dígitos (misma regla del sitio actual).
	if ( '' === $nombre || '' === $apellido || ! in_array( $hotel, pe_lista_hoteles(), true )
		|| ! is_email( $correo ) || $correo !== $correo2
		|| ! preg_match( '/^\d{10,15}$/', $telefono ) ) {
		wp_safe_redirect( $error );
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