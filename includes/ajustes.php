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
			</table>
			<?php submit_button( 'Guardar ajustes' ); ?>
		</form>
	</div>
	<?php
}