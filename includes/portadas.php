<?php
/**
 * Módulo Portadas: textos editables de la portada de cada página
 * (texto pequeño, título y subtítulo). Si un campo está vacío, el tema usa el texto del diseño.
 * En la página Inicio (y su traducción) el cuadro muestra solo lo que se usa y una guía
 * de dónde se edita cada parte del Inicio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lee un texto de portada de una página. Devuelve $defecto si está vacío.
function pe_portada_texto( $campo, $defecto = '', $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_queried_object_id();
	$valor   = $post_id ? get_post_meta( $post_id, '_pe_portada_' . $campo, true ) : '';
	return '' !== $valor ? $valor : $defecto;
}

// ¿Es la página Inicio o una de sus traducciones?
function pe_portada_es_inicio( $post_id ) {
	$inicio = (int) get_option( 'page_on_front' );
	if ( ! $inicio ) {
		return false;
	}
	if ( $inicio === (int) $post_id ) {
		return true;
	}
	return function_exists( 'pll_get_post_translations' ) && in_array( (int) $post_id, array_map( 'intval', pll_get_post_translations( $inicio ) ), true );
}

// Texto del diseño traducido al idioma de la página (para mostrarlo como ejemplo).
function pe_portada_defecto( $texto, $post_id ) {
	if ( function_exists( 'pll_get_post_language' ) && function_exists( 'pll_translate_string' ) ) {
		$idioma = pll_get_post_language( $post_id );
		if ( $idioma ) {
			return pll_translate_string( $texto, $idioma );
		}
	}
	return $texto;
}

// Cuadro "Textos de portada" en el editor de páginas.
function pe_agregar_metabox_portada() {
	add_meta_box(
		'pe_portada',
		'Textos de portada',
		'pe_mostrar_metabox_portada',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'pe_agregar_metabox_portada' );

function pe_mostrar_metabox_portada( $post ) {
	wp_nonce_field( 'pe_guardar_portada', 'pe_portada_nonce' );

	$es_inicio = pe_portada_es_inicio( $post->ID );

	if ( $es_inicio ) {
		$campos = array(
			'titulo'    => array( 'Título de la portada', pe_portada_defecto( 'Tu vacación perfecta en *Sandos Hotels*', $post->ID ), 'Lo que pongas entre *asteriscos* sale en cursiva.' ),
			'subtitulo' => array( 'Subtítulo', pe_portada_defecto( 'Centro de promociones exclusivo de la cadena Sandos, en México', $post->ID ), '' ),
		);
		?>
		<div class="pe-ayuda-panel">
			<p><strong>Esta es la página Inicio. Así se edita cada parte:</strong></p>
			<ul>
				<li><strong>Portada:</strong> el título y el subtítulo de este cuadro. La foto de fondo es la <strong>Imagen destacada</strong> (barra lateral derecha); recomendada horizontal, de al menos 1920 × 1080 px.</li>
				<li><strong>Sobre nosotros:</strong> el texto del editor de arriba. Si queda vacío, se usa el texto del diseño.</li>
				<li><strong>Ofertas:</strong> menú <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hot_escape' ) ); ?>">Hot Escapes</a>.</li>
				<li><strong>Nuestros hoteles:</strong> menú <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pe_hotel' ) ); ?>">Hoteles</a>.</li>
				<li><strong>Títulos de secciones, sellos, pasos y textos de contacto:</strong> menú <a href="<?php echo esc_url( admin_url( 'admin.php?page=pe-textos-ingles' ) ); ?>">Textos del sitio</a>, sección "Inicio".</li>
				<li><strong>Teléfonos, WhatsApp, correo y Agencia certificada:</strong> Ajustes &gt; PlayaEscapes.</li>
				<li><strong>Widget de reserva:</strong> se arma solo con los hoteles publicados; sus textos están en Textos del sitio, sección "Ventana de reserva".</li>
			</ul>
		</div>
		<?php if ( 'auto-draft' !== $post->post_status && ! has_post_thumbnail( $post ) ) : ?>
			<div class="pe-ayuda-pendiente"><p><strong>Falta la Imagen destacada:</strong> sin ella, la portada no tiene foto de fondo.</p></div>
		<?php endif; ?>
		<?php
	} else {
		$campos = array(
			'etiqueta'  => array( 'Texto pequeño (arriba del título)', 'Texto del diseño', '' ),
			'titulo'    => array( 'Título', 'Texto del diseño', '' ),
			'subtitulo' => array( 'Subtítulo', 'Texto del diseño', '' ),
		);
		?>
		<div class="pe-ayuda-panel">
			<p>Textos de la parte de arriba de esta página. Si un campo queda vacío, se usa el texto del diseño. El contenido de la página se escribe en el editor de arriba.</p>
		</div>
		<?php
	}

	pe_aviso_traduccion( $post );

	echo '<div class="pe-campos-panel">';
	foreach ( $campos as $clave => $datos ) {
		$valor = get_post_meta( $post->ID, '_pe_portada_' . $clave, true );
		?>
		<p>
			<label for="pe_portada_<?php echo esc_attr( $clave ); ?>"><strong><?php echo esc_html( $datos[0] ); ?></strong></label>
			<input type="text" class="widefat" id="pe_portada_<?php echo esc_attr( $clave ); ?>" name="pe_portada_<?php echo esc_attr( $clave ); ?>" value="<?php echo esc_attr( $valor ); ?>" placeholder="<?php echo esc_attr( $datos[1] ); ?>">
			<span class="description">Si queda vacío, se ve el texto de ejemplo en gris.<?php echo '' !== $datos[2] ? ' ' . esc_html( $datos[2] ) : ''; ?></span>
		</p>
		<?php
	}
	echo '</div>';
}

// Guardar los textos al actualizar la página. Solo se guardan los campos que se muestran.
function pe_guardar_metabox_portada( $post_id ) {
	if ( ! isset( $_POST['pe_portada_nonce'] ) ||
		! wp_verify_nonce( $_POST['pe_portada_nonce'], 'pe_guardar_portada' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'etiqueta', 'titulo', 'subtitulo' ) as $clave ) {
		if ( isset( $_POST[ 'pe_portada_' . $clave ] ) ) {
			update_post_meta(
				$post_id,
				'_pe_portada_' . $clave,
				sanitize_text_field( wp_unslash( $_POST[ 'pe_portada_' . $clave ] ) )
			);
		}
	}
}
add_action( 'save_post_page', 'pe_guardar_metabox_portada' );