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

// Quién puede entrar a Ajustes > PlayaEscapes.
// Hoy: solo administradores. Si TI aprueba que Editor y Ventas entren, cambiar a 'edit_others_pages'.
define( 'PE_AJUSTES_PERMISO', 'manage_options' );

// Ajustes que solo un administrador puede cambiar (si se cambian mal, se pierden leads).
function pe_ajustes_solo_admin() {
	return array( 'correo_avisos', 'pagina_reserva' );
}

// Agrega "PlayaEscapes" dentro del menú Ajustes.
function pe_ajustes_menu() {
	add_options_page(
		'Ajustes de PlayaEscapes',
		'PlayaEscapes',
		PE_AJUSTES_PERMISO,
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

// Permite guardar con el mismo permiso con el que se entra a la pantalla.
function pe_ajustes_permiso_guardar() {
	return PE_AJUSTES_PERMISO;
}
add_filter( 'option_page_capability_pe_ajustes', 'pe_ajustes_permiso_guardar' );

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
	$limpio  = array(
		'correo_avisos'       => isset( $entrada['correo_avisos'] ) ? implode( ', ', pe_lista_correos( $entrada['correo_avisos'] ) ) : '',
		'whatsapp_numero'     => isset( $entrada['whatsapp_numero'] ) ? preg_replace( '/\D/', '', $entrada['whatsapp_numero'] ) : '',
		'whatsapp_mensaje'    => isset( $entrada['whatsapp_mensaje'] ) ? sanitize_text_field( $entrada['whatsapp_mensaje'] ) : '',
		'aviso_privacidad'    => isset( $entrada['aviso_privacidad'] ) ? absint( $entrada['aviso_privacidad'] ) : 0,
		'terminos'            => isset( $entrada['terminos'] ) ? absint( $entrada['terminos'] ) : 0,
		'telefono'            => isset( $entrada['telefono'] ) ? sanitize_text_field( $entrada['telefono'] ) : '',
		'telefonos_paises'    => isset( $entrada['telefonos_paises'] ) ? sanitize_textarea_field( $entrada['telefonos_paises'] ) : '',
		'correo_contacto'     => isset( $entrada['correo_contacto'] ) ? sanitize_email( $entrada['correo_contacto'] ) : '',
		'direccion'           => isset( $entrada['direccion'] ) ? sanitize_textarea_field( $entrada['direccion'] ) : '',
		'facebook'            => isset( $entrada['facebook'] ) ? esc_url_raw( $entrada['facebook'] ) : '',
		'instagram'           => isset( $entrada['instagram'] ) ? esc_url_raw( $entrada['instagram'] ) : '',
		'pagina_reserva'      => isset( $entrada['pagina_reserva'] ) ? absint( $entrada['pagina_reserva'] ) : 0,
		'certificado_texto'   => isset( $entrada['certificado_texto'] ) ? sanitize_text_field( $entrada['certificado_texto'] ) : '',
		'certificado_url'     => isset( $entrada['certificado_url'] ) ? esc_url_raw( $entrada['certificado_url'] ) : '',
		'certificado_titulo'  => isset( $entrada['certificado_titulo'] ) ? sanitize_text_field( $entrada['certificado_titulo'] ) : '',
		'certificado_mensaje' => isset( $entrada['certificado_mensaje'] ) ? sanitize_textarea_field( $entrada['certificado_mensaje'] ) : '',
		'certificado_boton'   => isset( $entrada['certificado_boton'] ) ? sanitize_text_field( $entrada['certificado_boton'] ) : '',
	);

	// Quien no es administrador no puede cambiar los ajustes protegidos: se conservan los guardados.
	if ( ! current_user_can( 'manage_options' ) ) {
		$guardados = get_option( 'pe_ajustes', array() );
		foreach ( pe_ajustes_solo_admin() as $clave ) {
			$limpio[ $clave ] = ( is_array( $guardados ) && isset( $guardados[ $clave ] ) ) ? $guardados[ $clave ] : $limpio[ $clave ];
		}
	}

	return $limpio;
}

// ===== Pantalla =====

// Una fila de la pantalla: etiqueta, campo y ayuda.
function pe_ajustes_fila( $clave, $etiqueta, $campo, $ayuda = '', $donde = '' ) {
	?>
	<div class="pe-aj__fila" data-clave="<?php echo esc_attr( $clave ); ?>">
		<label class="pe-aj__etiqueta" for="pe_<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $etiqueta ); ?></label>
		<div class="pe-aj__campo">
			<?php echo $campo; // Ya escapado al armarlo. ?>
			<?php if ( '' !== $ayuda ) : ?>
				<p class="description"><?php echo wp_kses( $ayuda, array( 'code' => array(), 'strong' => array() ) ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $donde ) : ?>
				<p class="pe-aj__donde">Dónde se ve: <?php echo esc_html( $donde ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

function pe_ajustes_texto( $clave, $tipo = 'text', $clase = 'regular-text', $placeholder = '', $extra = '' ) {
	return sprintf(
		'<input type="%1$s" class="%2$s" id="pe_%3$s" name="pe_ajustes[%3$s]" value="%4$s" placeholder="%5$s" %6$s>',
		esc_attr( $tipo ),
		esc_attr( $clase ),
		esc_attr( $clave ),
		esc_attr( pe_ajuste( $clave ) ),
		esc_attr( $placeholder ),
		$extra
	);
}

function pe_ajustes_area( $clave, $filas, $placeholder = '' ) {
	return sprintf(
		'<textarea class="large-text" rows="%1$d" id="pe_%2$s" name="pe_ajustes[%2$s]" placeholder="%3$s">%4$s</textarea>',
		(int) $filas,
		esc_attr( $clave ),
		esc_attr( $placeholder ),
		esc_textarea( pe_ajuste( $clave ) )
	);
}

function pe_ajustes_paginas( $clave ) {
	return wp_dropdown_pages( array(
		'name'              => 'pe_ajustes[' . $clave . ']',
		'id'                => 'pe_' . $clave,
		'selected'          => absint( pe_ajuste( $clave ) ),
		'show_option_none'  => '— Ninguna —',
		'option_none_value' => '0',
		'echo'              => 0,
	) );
}

function pe_ajustes_pagina() {
	if ( ! current_user_can( PE_AJUSTES_PERMISO ) ) {
		return;
	}
	$es_admin = current_user_can( 'manage_options' );

	// Revisiones rápidas.
	$avisos  = array();
	$reserva = absint( pe_ajuste( 'pagina_reserva' ) );
	if ( ! $reserva ) {
		$avisos[] = 'No hay <strong>página del formulario de reserva</strong>: el widget y los botones "Reservar" no tienen a dónde llevar.';
	} elseif ( ! has_shortcode( (string) get_post_field( 'post_content', $reserva ), 'pe_formulario' ) ) {
		$avisos[] = 'La <strong>página del formulario de reserva</strong> elegida no tiene el formulario <code>[pe_formulario]</code>.';
	}
	if ( ! pe_lista_correos( pe_ajuste( 'correo_avisos' ) ) ) {
		$avisos[] = 'No hay <strong>correo para avisos</strong>: las solicitudes se guardan en Leads, pero no llega ningún correo.';
	}
	if ( ! absint( pe_ajuste( 'aviso_privacidad' ) ) ) {
		$avisos[] = 'No hay <strong>Aviso de privacidad</strong>: los formularios no muestran su enlace.';
	}

	$secciones = array(
		'contacto'    => 'Contacto',
		'whatsapp'    => 'WhatsApp',
		'reservas'    => 'Reservas y avisos',
		'legales'     => 'Páginas legales',
		'certificado' => 'Agencia certificada',
	);
	?>
	<div class="wrap pe-aj">
		<h1>Ajustes de PlayaEscapes</h1>
		<p>Datos que se usan en varias partes del sitio. Cambia lo que necesites y da clic en <strong>Guardar ajustes</strong>. Las páginas en inglés toman su versión traducida automáticamente.</p>

		<?php if ( $avisos ) : ?>
			<div class="notice notice-warning inline"><p><strong>Para revisar:</strong></p><ul>
				<?php foreach ( $avisos as $aviso ) : ?>
					<li><?php echo wp_kses( $aviso, array( 'strong' => array(), 'code' => array() ) ); ?></li>
				<?php endforeach; ?>
			</ul></div>
		<?php endif; ?>

		<nav class="pe-aj__indice" aria-label="Secciones">
			<?php foreach ( $secciones as $id => $nombre ) : ?>
				<a href="#pe-aj-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $nombre ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="options.php" id="pe-aj-form">
			<?php settings_fields( 'pe_ajustes' ); ?>

			<section class="pe-aj__seccion" id="pe-aj-contacto">
				<h2>Contacto</h2>
				<?php
				pe_ajustes_fila( 'telefono', 'Teléfono principal', pe_ajustes_texto( 'telefono', 'text', 'regular-text', '+52 (998) 000-0000' ), 'Tal como quieres que se lea. Se puede marcar desde el celular.', 'barra superior de todas las páginas.' );
				pe_ajustes_fila( 'telefonos_paises', 'Teléfonos por país', pe_ajustes_area( 'telefonos_paises', 4, "México: +52 998 000 0000\nToll Free USA / CAN: +1 800 000 0000" ), 'Uno por línea, con el formato <code>País: número</code>. El nombre del país se traduce en Textos del sitio.', 'Inicio (sección Contacto) y página de Contacto.' );
				pe_ajustes_fila( 'correo_contacto', 'Correo de contacto (público)', pe_ajustes_texto( 'correo_contacto', 'email', 'regular-text', 'reservas@dominio.com' ), 'Es el que ven los clientes. No es el que recibe los avisos de solicitudes.', 'Inicio (sección Contacto) y página de Contacto.' );
				pe_ajustes_fila( 'direccion', 'Dirección (pública)', pe_ajustes_area( 'direccion', 2 ), '', 'página de Contacto.' );
				pe_ajustes_fila( 'facebook', 'Facebook', pe_ajustes_texto( 'facebook', 'url', 'regular-text', 'https://www.facebook.com/...' ), 'Dirección completa de la página.' );
				pe_ajustes_fila( 'instagram', 'Instagram', pe_ajustes_texto( 'instagram', 'url', 'regular-text', 'https://www.instagram.com/...' ), 'Dirección completa del perfil.' );
				?>
			</section>

			<section class="pe-aj__seccion" id="pe-aj-whatsapp">
				<h2>WhatsApp</h2>
				<?php
				pe_ajustes_fila( 'whatsapp_numero', 'Número de WhatsApp', pe_ajustes_texto( 'whatsapp_numero', 'text', 'regular-text', '529980000000' ), 'Con código de país, sin espacios ni signos: 52 y los 10 dígitos. <span id="pe-aj-wa-prueba"></span>', 'botón flotante de WhatsApp y botón "Chatea con un especialista" del Inicio.' );
				pe_ajustes_fila( 'whatsapp_mensaje', 'Mensaje con el que se abre el chat', pe_ajustes_texto( 'whatsapp_mensaje', 'text', 'large-text', 'Hola, me interesa una promoción de PlayaEscapes' ), 'Puede quedar vacío. Su versión en inglés se escribe en Textos del sitio.' );
				?>
			</section>

			<section class="pe-aj__seccion" id="pe-aj-reservas">
				<h2>Reservas y avisos</h2>
				<?php if ( ! $es_admin ) : ?>
					<p class="description">Estos dos ajustes solo los puede cambiar un administrador.</p>
				<?php endif; ?>
				<?php
				$bloqueo = $es_admin ? '' : 'disabled';
				pe_ajustes_fila( 'correo_avisos', 'Correos que reciben las solicitudes', pe_ajustes_texto( 'correo_avisos', 'email', 'large-text', 'correo1@dominio.com, correo2@dominio.com', 'multiple ' . $bloqueo ), 'Aquí llega el aviso de cada solicitud y consulta nueva. Para varios correos, sepáralos con coma.' );
				$paginas = pe_ajustes_paginas( 'pagina_reserva' );
				if ( ! $es_admin ) {
					$paginas = str_replace( '<select ', '<select disabled ', $paginas );
				}
				pe_ajustes_fila( 'pagina_reserva', 'Página del formulario de reserva', $paginas, 'Debe tener el formulario <code>[pe_formulario]</code>. A esta página llevan el widget y los botones "Reservar".' );
				?>
			</section>

			<section class="pe-aj__seccion" id="pe-aj-legales">
				<h2>Páginas legales</h2>
				<?php
				pe_ajustes_fila( 'aviso_privacidad', 'Aviso de privacidad', pe_ajustes_paginas( 'aviso_privacidad' ), '', 'enlace debajo de los formularios de reserva y de contacto.' );
				pe_ajustes_fila( 'terminos', 'Términos y condiciones', pe_ajustes_paginas( 'terminos' ), '', 'enlace debajo del formulario de reserva.' );
				?>
			</section>

			<section class="pe-aj__seccion" id="pe-aj-certificado">
				<h2>Agencia certificada</h2>
				<p class="description">El sello de la portada del Inicio abre una ventana con estos textos. Si un campo queda vacío, se usa el texto provisional (en gris). Su versión en inglés se escribe en Textos del sitio.</p>
				<?php
				pe_ajustes_fila( 'certificado_texto', 'Texto del sello', pe_ajustes_texto( 'certificado_texto', 'text', 'regular-text', 'Agencia certificada por' ), 'Va junto al logo de Sandos.', 'portada del Inicio.' );
				pe_ajustes_fila( 'certificado_titulo', 'Título de la ventana', pe_ajustes_texto( 'certificado_titulo', 'text', 'large-text', 'PlayaEscapes, agencia certificada por Sandos Hotels & Resorts' ) );
				pe_ajustes_fila( 'certificado_mensaje', 'Mensaje de la ventana', pe_ajustes_area( 'certificado_mensaje', 4, 'Somos una agencia especializada en Sandos Hotels & Resorts…' ) );
				pe_ajustes_fila( 'certificado_url', 'Enlace al certificado en sandos.com', pe_ajustes_texto( 'certificado_url', 'url', 'regular-text', 'https://www.sandos.com/...' ), 'Si queda vacío, la ventana no muestra el botón a sandos.com.' );
				pe_ajustes_fila( 'certificado_boton', 'Texto del botón a sandos.com', pe_ajustes_texto( 'certificado_boton', 'text', 'regular-text', 'Ver certificado en sandos.com' ) );
				?>
			</section>

			<div class="pe-aj__guardar">
				<span class="pe-aj__guardar-texto" hidden>Tienes cambios sin guardar.</span>
				<?php submit_button( 'Guardar ajustes', 'primary large', 'submit', false ); ?>
			</div>
		</form>
	</div>

	<style>
		.pe-aj__indice { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0 16px; }
		.pe-aj__indice a { padding: 4px 12px; border: 1px solid #c3c4c7; border-radius: 16px; background: #fff; text-decoration: none; }
		.pe-aj__seccion { max-width: 1000px; margin: 0 0 16px; padding: 4px 20px 8px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px; scroll-margin-top: 48px; }
		.pe-aj__seccion > h2 { margin: 12px 0; padding-bottom: 8px; border-bottom: 1px solid #f0f0f1; }
		.pe-aj__fila { display: grid; grid-template-columns: 240px 1fr; gap: 8px 20px; padding: 12px 0; border-bottom: 1px solid #f0f0f1; }
		.pe-aj__fila:last-child { border-bottom: 0; }
		.pe-aj__etiqueta { font-weight: 600; padding-top: 6px; }
		.pe-aj__donde { margin: 4px 0 0; color: #2271b1; font-size: 12px; }
		.pe-aj__guardar { position: sticky; bottom: 0; z-index: 10; display: flex; align-items: center; justify-content: flex-end; gap: 16px; max-width: 1000px; padding: 12px 20px; background: #fff; border: 1px solid #dcdcde; box-shadow: 0 -4px 12px rgba(0,0,0,.06); }
		.pe-aj__guardar-texto { font-weight: 600; color: #8a6d00; }
		.pe-aj .pe-aj__cambiado { background: #fcf9e8; border-color: #dba617; }
		@media (max-width: 782px) {
			.pe-aj__fila { grid-template-columns: 1fr; }
		}
	</style>

	<script>
	(function () {
		var form = document.getElementById('pe-aj-form');
		if (!form) { return; }
		var aviso = form.querySelector('.pe-aj__guardar-texto');
		var campos = form.querySelectorAll('input[name^="pe_ajustes"], textarea[name^="pe_ajustes"], select[name^="pe_ajustes"]');
		var iniciales = Array.prototype.map.call(campos, function (c) { return c.value; });
		var guardando = false;

		function revisar() {
			var hay = false;
			Array.prototype.forEach.call(campos, function (c, i) {
				var cambio = c.value !== iniciales[i];
				c.classList.toggle('pe-aj__cambiado', cambio);
				if (cambio) { hay = true; }
			});
			aviso.hidden = !hay;
			return hay;
		}
		form.addEventListener('input', revisar);
		form.addEventListener('change', revisar);
		form.addEventListener('submit', function () { guardando = true; });
		window.addEventListener('beforeunload', function (e) {
			if (!guardando && revisar()) { e.preventDefault(); e.returnValue = ''; }
		});

		// Enlace para probar el WhatsApp.
		var wa = document.getElementById('pe_whatsapp_numero');
		var prueba = document.getElementById('pe-aj-wa-prueba');
		function pintarWa() {
			var n = wa.value.replace(/\D/g, '');
			prueba.innerHTML = '';
			if (n.length >= 11) {
				var a = document.createElement('a');
				a.href = 'https://wa.me/' + n;
				a.target = '_blank';
				a.rel = 'noopener';
				a.textContent = 'Probar este número';
				prueba.appendChild(a);
			} else if (n) {
				prueba.textContent = 'Parece incompleto: faltan dígitos o el código de país.';
				prueba.style.color = '#b32d2e';
			}
		}
		wa.addEventListener('input', pintarWa);
		pintarWa();
	})();
	</script>
	<?php
}