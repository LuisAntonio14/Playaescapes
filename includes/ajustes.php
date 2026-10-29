<?php
/**
 * Módulo Ajustes: datos de configuración editables desde el administrador.
 * Ajustes > PlayaEscapes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lee un ajuste guardado. Devuelve texto vacío si no existe.
function pe_ajuste( $clave ) {
	$ajustes = get_option( 'pe_ajustes', array() );
	return ( is_array( $ajustes ) && isset( $ajustes[ $clave ] ) ) ? $ajustes[ $clave ] : '';
}

// Dirección de una página elegida en Ajustes (aviso_privacidad o terminos). Vacío si no hay.
function pe_url_pagina_ajuste( $clave ) {
	$pagina = absint( pe_ajuste( $clave ) );
	if ( ! $pagina || 'publish' !== get_post_status( $pagina ) ) {
		return '';
	}
	return get_permalink( $pagina );
}

// Leyenda para debajo de los formularios. Texto POR CONFIRMAR con Marketing.
// $con_terminos = true agrega el enlace a Términos y condiciones (formulario de reserva).
function pe_leyenda_privacidad( $con_terminos = false ) {
	$aviso    = pe_url_pagina_ajuste( 'aviso_privacidad' );
	$terminos = $con_terminos ? pe_url_pagina_ajuste( 'terminos' ) : '';

	$partes = array();
	if ( '' !== $aviso ) {
		$partes[] = sprintf(
			'que sean tratados conforme a nuestro <a href="%s" target="_blank" rel="noopener">Aviso de privacidad</a>',
			esc_url( $aviso )
		);
	}
	if ( '' !== $terminos ) {
		$partes[] = sprintf(
			'los <a href="%s" target="_blank" rel="noopener">Términos y condiciones</a> de la promoción',
			esc_url( $terminos )
		);
	}

	if ( ! $partes ) {
		return '';
	}

	return '<p class="pe-leyenda-privacidad">Al enviar tus datos aceptas ' . implode( ', así como ', $partes ) . '.</p>';
}

// Teléfono de contacto tal como se muestra en el sitio (Ajustes > PlayaEscapes).
function pe_telefono_texto() {
	return pe_ajuste( 'telefono' );
}

// Enlace "tel:" para que el teléfono se pueda marcar desde el celular.
function pe_telefono_enlace() {
	$digitos = preg_replace( '/[^\d+]/', '', pe_ajuste( 'telefono' ) );
	return '' !== $digitos ? 'tel:' . $digitos : '';
}

// Agrega "PlayaEscapes" dentro del menú Ajustes (solo administradores).
function pe_ajustes_menu() {
	add_options_page(
		'Ajustes de PlayaEscapes',
		'PlayaEscapes',
		'manage_options',
		'pe-ajustes',
		'pe_ajustes_pagina'
	);
}
add_action( 'admin_menu', 'pe_ajustes_menu' );

function pe_ajustes_registrar() {
	register_setting( 'pe_ajustes', 'pe_ajustes', array(
		'sanitize_callback' => 'pe_ajustes_limpiar',
		'default'           => array(),
	) );
}
add_action( 'admin_init', 'pe_ajustes_registrar' );

// Limpia lo que se escribe antes de guardarlo.
function pe_ajustes_limpiar( $entrada ) {
	$entrada = is_array( $entrada ) ? $entrada : array();
	return array(
		'correo_avisos'    => isset( $entrada['correo_avisos'] ) ? sanitize_email( $entrada['correo_avisos'] ) : '',
		'whatsapp_numero'  => isset( $entrada['whatsapp_numero'] ) ? preg_replace( '/\D/', '', $entrada['whatsapp_numero'] ) : '',
		'whatsapp_mensaje' => isset( $entrada['whatsapp_mensaje'] ) ? sanitize_text_field( $entrada['whatsapp_mensaje'] ) : '',
		'aviso_privacidad' => isset( $entrada['aviso_privacidad'] ) ? absint( $entrada['aviso_privacidad'] ) : 0,
		'terminos'         => isset( $entrada['terminos'] ) ? absint( $entrada['terminos'] ) : 0,
		'telefono'         => isset( $entrada['telefono'] ) ? sanitize_text_field( $entrada['telefono'] ) : '',
	);
}

function pe_ajustes_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Ajustes de PlayaEscapes</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'pe_ajustes' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pe_correo_avisos">Correo para avisos de solicitudes</label></th>
					<td>
						<input type="email" class="regular-text" id="pe_correo_avisos" name="pe_ajustes[correo_avisos]" value="<?php echo esc_attr( pe_ajuste( 'correo_avisos' ) ); ?>">
						<p class="description">Aquí llega el aviso de cada solicitud nueva.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_whatsapp_numero">Número de WhatsApp</label></th>
					<td>
						<input type="text" class="regular-text" id="pe_whatsapp_numero" name="pe_ajustes[whatsapp_numero]" value="<?php echo esc_attr( pe_ajuste( 'whatsapp_numero' ) ); ?>">
						<p class="description">Con código de país y sin espacios ni signos. Ejemplo de formato: 52 seguido de los 10 dígitos.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_whatsapp_mensaje">Mensaje predeterminado de WhatsApp</label></th>
					<td>
						<input type="text" class="large-text" id="pe_whatsapp_mensaje" name="pe_ajustes[whatsapp_mensaje]" value="<?php echo esc_attr( pe_ajuste( 'whatsapp_mensaje' ) ); ?>">
						<p class="description">Texto con el que se abre la conversación. Puede quedar vacío.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_aviso_privacidad">Página del Aviso de privacidad</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'pe_ajustes[aviso_privacidad]',
							'id'                => 'pe_aviso_privacidad',
							'selected'          => absint( pe_ajuste( 'aviso_privacidad' ) ),
							'show_option_none'  => '— Ninguna —',
							'option_none_value' => '0',
						) );
						?>
						<p class="description">Se enlaza debajo de los formularios de reserva y de contacto.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_telefono">Teléfono de contacto</label></th>
					<td>
						<input type="text" class="regular-text" id="pe_telefono" name="pe_ajustes[telefono]" value="<?php echo esc_attr( pe_ajuste( 'telefono' ) ); ?>">
						<p class="description">Se muestra en el encabezado tal como lo escribas. Ejemplo de formato: +52 (998) 000-0000.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_terminos">Página de Términos y condiciones</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'pe_ajustes[terminos]',
							'id'                => 'pe_terminos',
							'selected'          => absint( pe_ajuste( 'terminos' ) ),
							'show_option_none'  => '— Ninguna —',
							'option_none_value' => '0',
						) );
						?>
						<p class="description">Se enlaza debajo del formulario de reserva.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Guardar ajustes' ); ?>
		</form>
	</div>
	<?php
}