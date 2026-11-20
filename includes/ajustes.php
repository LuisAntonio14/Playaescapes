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

	$enlace_aviso    = '' !== $aviso ? sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $aviso ), esc_html( pe_t( 'Aviso de privacidad' ) ) ) : '';
	$enlace_terminos = '' !== $terminos ? sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $terminos ), esc_html( pe_t( 'Términos y condiciones' ) ) ) : '';

	// Oraciones completas para que cada idioma pueda acomodar las palabras.
	if ( $enlace_aviso && $enlace_terminos ) {
		$texto = sprintf( esc_html( pe_t( 'Al enviar tus datos aceptas que sean tratados conforme a nuestro %1$s, así como los %2$s de la promoción.' ) ), $enlace_aviso, $enlace_terminos );
	} elseif ( $enlace_aviso ) {
		$texto = sprintf( esc_html( pe_t( 'Al enviar tus datos aceptas que sean tratados conforme a nuestro %s.' ) ), $enlace_aviso );
	} elseif ( $enlace_terminos ) {
		$texto = sprintf( esc_html( pe_t( 'Al enviar tus datos aceptas los %s de la promoción.' ) ), $enlace_terminos );
	} else {
		return '';
	}

	return '<p class="pe-leyenda-privacidad">' . $texto . '</p>';
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

// Teléfonos por país (Ajustes > PlayaEscapes). Una línea por país con el formato "País: número".
// Devuelve una lista con el país, el número tal como se escribió y su enlace "tel:".
function pe_telefonos_paises() {
	$lista  = array();
	$lineas = preg_split( '/\r\n|\r|\n/', pe_ajuste( 'telefonos_paises' ) );

	foreach ( $lineas as $linea ) {
		$partes = array_map( 'trim', explode( ':', $linea, 2 ) );
		if ( 2 !== count( $partes ) || '' === $partes[0] || '' === $partes[1] ) {
			continue;
		}
		$digitos = preg_replace( '/[^\d+]/', '', $partes[1] );
		$lista[] = array(
			'pais'   => $partes[0],
			'numero' => $partes[1],
			'enlace' => '' !== $digitos ? 'tel:' . $digitos : '',
		);
	}

	return $lista;
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

// Convierte un texto con correos separados por coma en una lista de correos válidos, sin repetidos.
function pe_lista_correos( $texto ) {
	$correos = array();
	foreach ( preg_split( '/[,;\s]+/', (string) $texto ) as $correo ) {
		$correo = sanitize_email( $correo );
		if ( is_email( $correo ) && ! in_array( $correo, $correos, true ) ) {
			$correos[] = $correo;
		}
	}
	return $correos;
}

// Limpia lo que se escribe antes de guardarlo.
function pe_ajustes_limpiar( $entrada ) {
	$entrada = is_array( $entrada ) ? $entrada : array();
	return array(
		'correo_avisos'    => isset( $entrada['correo_avisos'] ) ? implode( ', ', pe_lista_correos( $entrada['correo_avisos'] ) ) : '',		'whatsapp_numero'  => isset( $entrada['whatsapp_numero'] ) ? preg_replace( '/\D/', '', $entrada['whatsapp_numero'] ) : '',
		'whatsapp_mensaje' => isset( $entrada['whatsapp_mensaje'] ) ? sanitize_text_field( $entrada['whatsapp_mensaje'] ) : '',
		'aviso_privacidad' => isset( $entrada['aviso_privacidad'] ) ? absint( $entrada['aviso_privacidad'] ) : 0,
		'terminos'         => isset( $entrada['terminos'] ) ? absint( $entrada['terminos'] ) : 0,
		'telefono'         => isset( $entrada['telefono'] ) ? sanitize_text_field( $entrada['telefono'] ) : '',
		'telefonos_paises' => isset( $entrada['telefonos_paises'] ) ? sanitize_textarea_field( $entrada['telefonos_paises'] ) : '',
		'correo_contacto'  => isset( $entrada['correo_contacto'] ) ? sanitize_email( $entrada['correo_contacto'] ) : '',
		'direccion'        => isset( $entrada['direccion'] ) ? sanitize_textarea_field( $entrada['direccion'] ) : '',
		'facebook'         => isset( $entrada['facebook'] ) ? esc_url_raw( $entrada['facebook'] ) : '',
		'instagram'        => isset( $entrada['instagram'] ) ? esc_url_raw( $entrada['instagram'] ) : '',
		'pagina_reserva'   => isset( $entrada['pagina_reserva'] ) ? absint( $entrada['pagina_reserva'] ) : 0,		'pagina_reserva'   => isset( $entrada['pagina_reserva'] ) ? absint( $entrada['pagina_reserva'] ) : 0,
		'certificado_texto' => isset( $entrada['certificado_texto'] ) ? sanitize_text_field( $entrada['certificado_texto'] ) : '',
		'certificado_url'   => isset( $entrada['certificado_url'] ) ? esc_url_raw( $entrada['certificado_url'] ) : '',
		'certificado_titulo'  => isset( $entrada['certificado_titulo'] ) ? sanitize_text_field( $entrada['certificado_titulo'] ) : '',
		'certificado_mensaje' => isset( $entrada['certificado_mensaje'] ) ? sanitize_textarea_field( $entrada['certificado_mensaje'] ) : '',
		'certificado_boton'   => isset( $entrada['certificado_boton'] ) ? sanitize_text_field( $entrada['certificado_boton'] ) : '',	);
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
						<input type="email" multiple class="large-text" id="pe_correo_avisos" name="pe_ajustes[correo_avisos]" value="<?php echo esc_attr( pe_ajuste( 'correo_avisos' ) ); ?>">
						<p class="description">Aquí llega el aviso de cada solicitud y consulta nueva. Para varios destinatarios, sepáralos con coma. Ejemplo de formato: correo1@dominio.com, correo2@dominio.com</p>
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
					<th scope="row"><label for="pe_telefonos_paises">Teléfonos por país</label></th>
					<td>
						<textarea class="large-text" rows="5" id="pe_telefonos_paises" name="pe_ajustes[telefonos_paises]"><?php echo esc_textarea( pe_ajuste( 'telefonos_paises' ) ); ?></textarea>
						<p class="description">Uno por línea, con el formato <code>País: número</code>. Se mostrará en la página de Contacto.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_correo_contacto">Correo de contacto (público)</label></th>
					<td>
						<input type="email" class="regular-text" id="pe_correo_contacto" name="pe_ajustes[correo_contacto]" value="<?php echo esc_attr( pe_ajuste( 'correo_contacto' ) ); ?>">
						<p class="description">Se muestra en el sitio. Es distinto del correo que recibe los avisos de solicitudes.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_direccion">Dirección (pública)</label></th>
					<td>
						<textarea class="large-text" rows="2" id="pe_direccion" name="pe_ajustes[direccion]"><?php echo esc_textarea( pe_ajuste( 'direccion' ) ); ?></textarea>
						<p class="description">Se muestra en la página de Contacto.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_facebook">Facebook (dirección de la página)</label></th>
					<td>
						<input type="url" class="regular-text" id="pe_facebook" name="pe_ajustes[facebook]" value="<?php echo esc_attr( pe_ajuste( 'facebook' ) ); ?>" placeholder="https://www.facebook.com/...">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_instagram">Instagram (dirección del perfil)</label></th>
					<td>
						<input type="url" class="regular-text" id="pe_instagram" name="pe_ajustes[instagram]" value="<?php echo esc_attr( pe_ajuste( 'instagram' ) ); ?>" placeholder="https://www.instagram.com/...">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_pagina_reserva">Página del formulario de reserva</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'pe_ajustes[pagina_reserva]',
							'id'                => 'pe_pagina_reserva',
							'selected'          => absint( pe_ajuste( 'pagina_reserva' ) ),
							'show_option_none'  => '— Ninguna —',
							'option_none_value' => '0',
						) );
						?>
						<p class="description">A esta página llevan los botones "Reservar" del sitio. Debe tener el formulario [pe_formulario].</p>
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
				<tr>
					<th scope="row"><label for="pe_certificado_texto">Texto de "Agencia certificada"</label></th>
					<td>
						<input type="text" class="regular-text" id="pe_certificado_texto" name="pe_ajustes[certificado_texto]" value="<?php echo esc_attr( pe_ajuste( 'certificado_texto' ) ); ?>" placeholder="Agencia certificada por">
						<p class="description">Se muestra en la portada del Inicio, junto al logo de Sandos. Si queda vacío, se usa "Agencia certificada por".</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_certificado_url">Enlace de "Agencia certificada"</label></th>
					<td>
						<input type="url" class="regular-text" id="pe_certificado_url" name="pe_ajustes[certificado_url]" value="<?php echo esc_attr( pe_ajuste( 'certificado_url' ) ); ?>" placeholder="https://www.sandos.com/...">
						<p class="description">Página de sandos.com con el certificado. Aparece como botón dentro de la ventana "Agencia certificada" y se abre en una pestaña nueva. Si queda vacío, ese botón no se muestra.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_certificado_titulo">Ventana "Agencia certificada": título</label></th>
					<td>
						<input type="text" class="large-text" id="pe_certificado_titulo" name="pe_ajustes[certificado_titulo]" value="<?php echo esc_attr( pe_ajuste( 'certificado_titulo' ) ); ?>" placeholder="PlayaEscapes, agencia certificada por Sandos Hotels & Resorts">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_certificado_mensaje">Ventana "Agencia certificada": mensaje</label></th>
					<td>
						<textarea class="large-text" rows="4" id="pe_certificado_mensaje" name="pe_ajustes[certificado_mensaje]"><?php echo esc_textarea( pe_ajuste( 'certificado_mensaje' ) ); ?></textarea>
						<p class="description">Se muestra al dar clic en el sello de la portada del Inicio. Si queda vacío, se usa el texto provisional.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pe_certificado_boton">Ventana "Agencia certificada": texto del botón a sandos.com</label></th>
					<td>
						<input type="text" class="regular-text" id="pe_certificado_boton" name="pe_ajustes[certificado_boton]" value="<?php echo esc_attr( pe_ajuste( 'certificado_boton' ) ); ?>" placeholder="Ver certificado en sandos.com">
					</td>
				</tr>
			</table>
			<?php submit_button( 'Guardar ajustes' ); ?>
		</form>
	</div>
	<?php
}