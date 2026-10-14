<?php
/**
 * Plugin Name: PlayaEscapes Core
 * Description: Funcionalidad de PlayaEscapes (promociones Hot Escapes).
 * Version: 0.4.0
 * Text Domain: playaescapes-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_registrar_hot_escapes() {
	register_post_type( 'hot_escape', array(
		'labels' => array(
			'name'          => 'Hot Escapes',
			'singular_name' => 'Hot Escape',
			'add_new_item'  => 'Agregar promoción',
			'edit_item'     => 'Editar promoción',
		),
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'hot-escapes' ),
		'menu_icon'    => 'dashicons-megaphone',
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
	) );
}
add_action( 'init', 'pe_registrar_hot_escapes' );

// Cuadro "Datos de la promoción" dentro de cada Hot Escape.
function pe_agregar_metabox_hot_escape() {
	add_meta_box(
		'pe_datos_promocion',
		'Datos de la promoción',
		'pe_mostrar_metabox_hot_escape',
		'hot_escape',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_hot_escape' );

function pe_mostrar_metabox_hot_escape( $post ) {
	wp_nonce_field( 'pe_guardar_hot_escape', 'pe_hot_escape_nonce' );

	$hotel_guardado = get_post_meta( $post->ID, '_pe_hotel', true );
	$hoteles        = array(
		'Sandos Cancún',
		'Sandos Caracol Eco Resort',
		'Sandos Playacar',
		'Sandos Finisterra',
	);

	echo '<p><label for="pe_hotel"><strong>Hotel</strong></label><br>';
	echo '<select name="pe_hotel" id="pe_hotel">';
	echo '<option value="">Selecciona un hotel</option>';
	foreach ( $hoteles as $hotel ) {
		printf(
			'<option value="%1$s" %2$s>%1$s</option>',
			esc_attr( $hotel ),
			selected( $hotel_guardado, $hotel, false )
		);
	}
	echo '</select></p>';
    
	$precio       = get_post_meta( $post->ID, '_pe_precio_desde', true );
	$vigencia_ini = get_post_meta( $post->ID, '_pe_vigencia_inicio', true );
	$vigencia_fin = get_post_meta( $post->ID, '_pe_vigencia_fin', true );

	echo '<p><label for="pe_precio_desde"><strong>Precio desde (MXN por persona por noche)</strong></label><br>';
	printf(
		'<input type="number" min="0" step="1" name="pe_precio_desde" id="pe_precio_desde" value="%s">',
		esc_attr( $precio )
	);
	echo '</p>';

	echo '<p><label for="pe_vigencia_inicio"><strong>Vigencia desde</strong></label><br>';
	printf(
		'<input type="date" name="pe_vigencia_inicio" id="pe_vigencia_inicio" value="%s">',
		esc_attr( $vigencia_ini )
	);
	echo '</p>';

	echo '<p><label for="pe_vigencia_fin"><strong>Vigencia hasta</strong></label><br>';
	printf(
		'<input type="date" name="pe_vigencia_fin" id="pe_vigencia_fin" value="%s">',
		esc_attr( $vigencia_fin )
	);
	echo '</p>';
}

// Guardar los datos al publicar o actualizar
function pe_guardar_metabox_hot_escape( $post_id ) {
	if ( ! isset( $_POST['pe_hot_escape_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_hot_escape_nonce'], 'pe_guardar_hot_escape' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['pe_hotel'] ) ) {
		update_post_meta(
			$post_id,
			'_pe_hotel',
			sanitize_text_field( wp_unslash( $_POST['pe_hotel'] ) )
		);
	}
    
	if ( isset( $_POST['pe_precio_desde'] ) ) {
		$precio = '' === $_POST['pe_precio_desde'] ? '' : absint( $_POST['pe_precio_desde'] );
		update_post_meta( $post_id, '_pe_precio_desde', $precio );
	}

	foreach ( array( 'pe_vigencia_inicio', 'pe_vigencia_fin' ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) ) {
			$fecha = sanitize_text_field( wp_unslash( $_POST[ $campo ] ) );
			if ( '' === $fecha || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) ) {
				update_post_meta( $post_id, '_' . $campo, $fecha );
			}
		}
	}
}
add_action( 'save_post_hot_escape', 'pe_guardar_metabox_hot_escape' );

// Mostrar solo las promociones vigentes en el sitio público.
function pe_filtrar_hot_escapes_vigentes( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive( 'hot_escape' ) ) {
		return;
	}

	$hoy = current_time( 'Y-m-d' );

	$query->set( 'meta_query', array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array(
				'key'     => '_pe_vigencia_inicio',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_pe_vigencia_inicio',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_pe_vigencia_inicio',
				'value'   => $hoy,
				'compare' => '<=',
				'type'    => 'DATE',
			),
		),
		array(
			'relation' => 'OR',
			array(
				'key'     => '_pe_vigencia_fin',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_pe_vigencia_fin',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_pe_vigencia_fin',
				'value'   => $hoy,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	) );
}
add_action( 'pre_get_posts', 'pe_filtrar_hot_escapes_vigentes' );

// Tipo de contenido "Hoteles" (una ficha por hotel).
function pe_registrar_hoteles() {
	register_post_type( 'pe_hotel', array(
		'labels' => array(
			'name'          => 'Hoteles',
			'singular_name' => 'Hotel',
			'add_new_item'  => 'Agregar hotel',
			'edit_item'     => 'Editar hotel',
		),
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'hoteles' ),
		'menu_icon'    => 'dashicons-building',
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
	) );
}
add_action( 'init', 'pe_registrar_hoteles' );

// Cuadro "Datos del hotel".
function pe_agregar_metabox_hotel() {
	add_meta_box(
		'pe_datos_hotel',
		'Datos del hotel',
		'pe_mostrar_metabox_hotel',
		'pe_hotel',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_hotel' );

function pe_mostrar_metabox_hotel( $post ) {
	wp_nonce_field( 'pe_guardar_hotel', 'pe_hotel_nonce' );

	$precio  = get_post_meta( $post->ID, '_pe_hotel_precio_desde', true );
	$incluye = get_post_meta( $post->ID, '_pe_hotel_incluye', true );

	echo '<p><label for="pe_hotel_precio_desde"><strong>Precio desde (MXN por persona por noche)</strong></label><br>';
	printf(
		'<input type="number" min="0" step="1" name="pe_hotel_precio_desde" id="pe_hotel_precio_desde" value="%s">',
		esc_attr( $precio )
	);
	echo '</p>';

	echo '<p><label for="pe_hotel_incluye"><strong>Resumen de lo que incluye</strong></label><br>';
	printf(
		'<textarea name="pe_hotel_incluye" id="pe_hotel_incluye" rows="4" style="width:100%%;">%s</textarea>',
		esc_textarea( $incluye )
	);
	echo '</p>';
}

function pe_guardar_metabox_hotel( $post_id ) {
	if ( ! isset( $_POST['pe_hotel_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_hotel_nonce'], 'pe_guardar_hotel' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['pe_hotel_precio_desde'] ) ) {
		$precio = '' === $_POST['pe_hotel_precio_desde'] ? '' : absint( $_POST['pe_hotel_precio_desde'] );
		update_post_meta( $post_id, '_pe_hotel_precio_desde', $precio );
	}
	if ( isset( $_POST['pe_hotel_incluye'] ) ) {
		update_post_meta(
			$post_id,
			'_pe_hotel_incluye',
			sanitize_textarea_field( wp_unslash( $_POST['pe_hotel_incluye'] ) )
		);
	}
}
add_action( 'save_post_pe_hotel', 'pe_guardar_metabox_hotel' );



// LEADS (solicitudes del formulario). Paso 1: dónde se guardan.

// Tipo de contenido privado "Leads": visible solo en el administrador.
function pe_registrar_leads() {
	register_post_type( 'pe_lead', array(
		'labels' => array(
			'name'          => 'Leads',
			'singular_name' => 'Lead',
			'edit_item'     => 'Ver lead',
		),
		'public'       => false, // No tiene página pública
		'show_ui'      => true,  // Pero sí aparece en el administrador
		'menu_icon'    => 'dashicons-email-alt',
		'supports'     => array( 'title' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ), // No se crean a mano
		'map_meta_cap' => true,
	) );
}
add_action( 'init', 'pe_registrar_leads' );

// Función reutilizable: recibe los datos de una solicitud y la guarda.
// Devuelve el ID del lead creado, o 0 si falló.
function pe_crear_lead( $datos ) {
	$campos = array(
		'hotel', 'llegada', 'salida', 'adultos', 'ninos', 'habitaciones',
		'nombre', 'apellido', 'correo', 'telefono', 'pais',
		'promocion', 'utm_source', 'utm_medium', 'utm_campaign',
	);

	$limpios = array();
	foreach ( $campos as $campo ) {
		$valor = isset( $datos[ $campo ] ) ? $datos[ $campo ] : '';
		$limpios[ $campo ] = ( 'correo' === $campo )
			? sanitize_email( $valor )
			: sanitize_text_field( $valor );
	}

	$titulo = trim( $limpios['nombre'] . ' ' . $limpios['apellido'] );
	if ( '' === $titulo ) {
		$titulo = 'Lead sin nombre';
	}

	$id = wp_insert_post( array(
		'post_type'   => 'pe_lead',
		'post_status' => 'publish',
		'post_title'  => $titulo,
	) );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	foreach ( $limpios as $campo => $valor ) {
		update_post_meta( $id, '_pe_lead_' . $campo, $valor );
	}

	return $id;
}

// LEADS. Paso 2: formulario, recepción y vista del lead.

// Formulario: se coloca en cualquier página con el shortcode [pe_formulario].
// Opcional: [pe_formulario hotel="Sandos Caracol Eco Resort"] lo deja prellenado.
function pe_formulario_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'hotel'     => '',
		'promocion' => '',
	), $atts );

	$hoteles = array(
		'Sandos Cancún',
		'Sandos Caracol Eco Resort',
		'Sandos Playacar',
		'Sandos Finisterra',
	);
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

	$correo     = isset( $datos['correo'] ) ? sanitize_email( $datos['correo'] ) : '';
	$correo2    = isset( $datos['correo2'] ) ? sanitize_email( $datos['correo2'] ) : '';
	$telefono   = isset( $datos['telefono'] ) ? preg_replace( '/[\s\-\(\)\+]/', '', $datos['telefono'] ) : '';
	$nombre     = isset( $datos['nombre'] ) ? trim( $datos['nombre'] ) : '';
	$apellido   = isset( $datos['apellido'] ) ? trim( $datos['apellido'] ) : '';
	$hotel      = isset( $datos['hotel'] ) ? trim( $datos['hotel'] ) : '';

	// Validaciones mínimas: campos obligatorios, correo válido y repetido igual,
	// teléfono de 10 a 15 dígitos (misma regla del sitio actual).
	if ( '' === $nombre || '' === $apellido || '' === $hotel
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

	wp_safe_redirect( home_url( '/gracias/' ) );
	exit;
}
add_action( 'admin_post_nopriv_pe_enviar_lead', 'pe_procesar_formulario' ); // visitantes
add_action( 'admin_post_pe_enviar_lead', 'pe_procesar_formulario' );        // con sesión

// Cuadro de solo lectura para ver los datos de cada lead en el administrador.
function pe_agregar_metabox_lead() {
	add_meta_box( 'pe_datos_lead', 'Datos de la solicitud', 'pe_mostrar_metabox_lead', 'pe_lead', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_lead' );

function pe_mostrar_metabox_lead( $post ) {
	$campos = array(
		'hotel'        => 'Hotel',
		'llegada'      => 'Llegada',
		'salida'       => 'Salida',
		'adultos'      => 'Adultos',
		'ninos'        => 'Niños',
		'habitaciones' => 'Habitaciones',
		'nombre'       => 'Nombre',
		'apellido'     => 'Apellido',
		'correo'       => 'Correo',
		'telefono'     => 'Teléfono',
		'pais'         => 'País',
		'promocion'    => 'Promoción',
		'utm_source'   => 'UTM source',
		'utm_medium'   => 'UTM medium',
		'utm_campaign' => 'UTM campaign',
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $campos as $clave => $etiqueta ) {
		printf(
			'<tr><th style="width:200px;">%s</th><td>%s</td></tr>',
			esc_html( $etiqueta ),
			esc_html( get_post_meta( $post->ID, '_pe_lead_' . $clave, true ) )
		);
	}
	echo '</tbody></table>';
}