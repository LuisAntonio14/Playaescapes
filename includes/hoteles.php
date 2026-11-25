<?php
/**
 * Módulo Hoteles: tipo de contenido para la ficha informativa de cada hotel.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	) );
}
add_action( 'init', 'pe_registrar_hoteles' );


// Cuadro "Datos del hotel" dentro de cada ficha de hotel.
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

	$ubicacion = (string) get_post_meta( $post->ID, '_pe_hotel_ubicacion', true );
	$tipo      = (string) get_post_meta( $post->ID, '_pe_hotel_tipo', true );
	$precio    = (string) get_post_meta( $post->ID, '_pe_hotel_precio_desde', true );
	$incluye   = (string) get_post_meta( $post->ID, '_pe_hotel_incluye', true );

	// Lista de pendientes para que el hotel se vea completo.
	$pendientes = array();
	if ( ! has_post_thumbnail( $post ) ) {
		$pendientes[] = 'Imagen destacada (barra lateral derecha).';
	}
	if ( '' === trim( (string) $post->post_excerpt ) ) {
		$pendientes[] = 'Extracto: el resumen corto de la tarjeta (barra lateral derecha).';
	}
	if ( '' === trim( $ubicacion ) ) {
		$pendientes[] = 'Ubicación.';
	}
	if ( '' === trim( $precio ) ) {
		$pendientes[] = 'Precio desde.';
	}
	?>
	<div class="pe-ayuda-panel">
		<p><strong>Dónde se ve este hotel</strong></p>
		<ul>
			<li><strong>Inicio, sección "Nuestros hoteles":</strong> imagen destacada, ubicación, tipo, nombre, extracto, los 3 primeros puntos de "Qué incluye" y precio desde.</li>
			<li><strong>Listas de hoteles</strong> (widget de reserva, formulario y promociones): nombre en español + tipo + ubicación.</li>
			<li><strong>Página del hotel:</strong> el texto del editor de arriba (la página está en diseño).</li>
		</ul>
		<p class="description">Al publicar el hotel, aparece solo en las listas. Para quitarlo, pásalo a borrador. Si cambias el nombre en español, sus promociones se actualizan solas. Imagen recomendada: horizontal, de al menos 1200 × 800 px.</p>
	</div>

	<?php if ( 'auto-draft' !== $post->post_status && $pendientes ) : ?>
		<div class="pe-ayuda-pendiente">
			<p><strong>Para que el hotel se vea completo, falta:</strong></p>
			<ul>
				<?php foreach ( $pendientes as $pendiente ) : ?>
					<li><?php echo esc_html( $pendiente ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php pe_aviso_traduccion( $post ); ?>

	<div class="pe-campos-panel">
		<p>
			<label for="pe_hotel_ubicacion"><strong>Ubicación</strong></label>
			<input type="text" class="regular-text" name="pe_hotel_ubicacion" id="pe_hotel_ubicacion" value="<?php echo esc_attr( $ubicacion ); ?>" placeholder="Ejemplo: Playa del Carmen">
			<span class="description">Etiqueta con el ícono de ubicación en la tarjeta del Inicio.</span>
		</p>
		<p>
			<label for="pe_hotel_tipo"><strong>Tipo</strong></label>
			<input type="text" class="regular-text" name="pe_hotel_tipo" id="pe_hotel_tipo" value="<?php echo esc_attr( $tipo ); ?>" placeholder="Ejemplo: Familiar, Solo adultos">
			<span class="description">Etiquetas separadas por coma. Cada una sale como una etiqueta en la tarjeta.</span>
		</p>
		<p>
			<label for="pe_hotel_precio_desde"><strong>Precio desde (MXN por persona por noche)</strong></label>
			<input type="number" min="0" step="1" name="pe_hotel_precio_desde" id="pe_hotel_precio_desde" value="<?php echo esc_attr( $precio ); ?>" placeholder="Ejemplo: 3500">
			<span class="description">Solo el número, sin $ ni comas. Si queda vacío, la tarjeta no muestra precio.</span>
		</p>
		<p>
			<label for="pe_hotel_incluye"><strong>Qué incluye</strong></label>
			<textarea name="pe_hotel_incluye" id="pe_hotel_incluye" rows="6" placeholder="Un punto por línea. Ejemplo:&#10;Todo incluido&#10;Club de niños&#10;Playa privada"><?php echo esc_textarea( $incluye ); ?></textarea>
			<span class="description">Un punto por línea. En la tarjeta del Inicio se muestran los 3 primeros. <span id="pe-incluye-cuenta"></span></span>
		</p>

		<div class="pe-vista-previa">
			<p><strong>Así se verá en las listas de hoteles:</strong></p>
			<p class="pe-vista-previa__lista" id="pe-vista-lista"></p>
		</div>
	</div>

	<script>
	(function () {
		var ubicacion = document.getElementById('pe_hotel_ubicacion');
		var tipo = document.getElementById('pe_hotel_tipo');
		var incluye = document.getElementById('pe_hotel_incluye');
		var lista = document.getElementById('pe-vista-lista');
		var cuenta = document.getElementById('pe-incluye-cuenta');

		function titulo() {
			var t = document.querySelector('.editor-post-title__input, .editor-post-title, #title');
			if (!t) { return ''; }
			return (t.value !== undefined ? t.value : t.textContent).trim();
		}
		function pintar() {
			var detalles = [tipo.value.trim(), ubicacion.value.trim()].filter(Boolean).join(' · ');
			lista.textContent = (titulo() || 'Nombre del hotel') + (detalles ? ' — ' + detalles : '');
			var n = incluye.value.split(/\r?\n/).filter(function (l) { return l.trim(); }).length;
			cuenta.textContent = n ? '(Llevas ' + n + (n === 1 ? ' punto' : ' puntos') + (n > 3 ? '; los demás no salen en el Inicio' : '') + '.)' : '';
		}
		[ubicacion, tipo, incluye].forEach(function (c) { c.addEventListener('input', pintar); });
		document.addEventListener('input', pintar, true);
		pintar();
		setTimeout(pintar, 1500); // El título del editor carga después.
	})();
	</script>
	<?php
}

// Guardar los datos al publicar o actualizar.
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

	foreach ( array( 'pe_hotel_ubicacion', 'pe_hotel_tipo' ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) ) {
			update_post_meta( $post_id, '_' . $campo, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
		}
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


// Columnas de la lista de Hoteles en el administrador.
function pe_columnas_hoteles( $columnas ) {
	$nuevas = array();
	if ( isset( $columnas['cb'] ) ) {
		$nuevas['cb'] = $columnas['cb'];
	}
	$nuevas['pe_col_imagen'] = 'Imagen';
	$nuevas['title']         = 'Hotel';
	$nuevas['pe_col_precio'] = 'Precio desde';
	$nuevas['pe_col_falta']  = 'Revisión';
	// Columnas de idioma de Polylang.
	foreach ( $columnas as $clave => $nombre ) {
		if ( 0 === strpos( $clave, 'language_' ) ) {
			$nuevas[ $clave ] = $nombre;
		}
	}
	$nuevas['date'] = 'Fecha';
	return $nuevas;
}
add_filter( 'manage_pe_hotel_posts_columns', 'pe_columnas_hoteles' );

function pe_contenido_columnas_hoteles( $columna, $post_id ) {
	switch ( $columna ) {
		case 'pe_col_imagen':
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, array( 60, 40 ), array( 'style' => 'width:60px;height:40px;object-fit:cover;border-radius:4px;' ) );
			} else {
				echo '<span style="color:#b32d2e;">Sin imagen</span>';
			}
			break;

		case 'pe_col_falta':
			$falta = array();
			if ( ! has_post_thumbnail( $post_id ) ) {
				$falta[] = 'imagen';
			}
			if ( '' === trim( (string) get_post_field( 'post_excerpt', $post_id ) ) ) {
				$falta[] = 'extracto';
			}
			if ( '' === trim( (string) get_post_meta( $post_id, '_pe_hotel_ubicacion', true ) ) ) {
				$falta[] = 'ubicación';
			}
			if ( '' === trim( (string) get_post_meta( $post_id, '_pe_hotel_precio_desde', true ) ) ) {
				$falta[] = 'precio';
			}
			echo $falta
				? '<span style="color:#b32d2e;">Falta: ' . esc_html( implode( ', ', $falta ) ) . '</span>'
				: '<span style="color:#008a20;">&#10003; Completo</span>';
			break;

		case 'pe_col_precio':
			$precio = get_post_meta( $post_id, '_pe_hotel_precio_desde', true );
			if ( '' !== $precio ) {
				echo esc_html( '$' . number_format_i18n( (int) $precio ) . ' MXN' );
			}
			break;
	}
}
add_action( 'manage_pe_hotel_posts_custom_column', 'pe_contenido_columnas_hoteles', 10, 2 );

// Ficha del hotel en el idioma actual a partir de su nombre en español (0 si no existe).
function pe_hotel_id( $hotel ) {
	$hoteles = function_exists( 'pe_hoteles_por_nombre' ) ? pe_hoteles_por_nombre() : array();
	if ( empty( $hoteles[ $hotel ] ) ) {
		return 0;
	}
	$id = $hoteles[ $hotel ];
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) && pll_current_language() ) {
		$traducido = pll_get_post( $id, pll_current_language() );
		if ( $traducido ) {
			$id = $traducido;
		}
	}
	return (int) $id;
}

// Texto de un hotel para las listas desplegables: "Nombre — Tipo · Ubicación".
// Toma Tipo y Ubicación de la ficha del hotel (en el idioma actual). Si no hay ficha, solo el nombre.
function pe_etiqueta_hotel( $hotel ) {
	static $cache = array();
	if ( isset( $cache[ $hotel ] ) ) {
		return $cache[ $hotel ];
	}

	$etiqueta = $hotel;
	$id       = pe_hotel_id( $hotel );
	if ( $id ) {
		$tipo      = trim( (string) get_post_meta( $id, '_pe_hotel_tipo', true ) );
		$ubicacion = trim( (string) get_post_meta( $id, '_pe_hotel_ubicacion', true ) );
		$detalles  = implode( ' · ', array_filter( array( $tipo, $ubicacion ) ) );
		if ( '' !== $detalles ) {
			$etiqueta .= ' — ' . $detalles;
		}
	}

	$cache[ $hotel ] = $etiqueta;
	return $etiqueta;
}

// Si cambia el nombre de un hotel (en español), sus promociones se actualizan solas.
// Los leads conservan el nombre con el que llegaron.
function pe_hotel_renombrado( $post_id, $despues, $antes ) {
	if ( 'pe_hotel' !== $despues->post_type || 'publish' !== $antes->post_status ) {
		return;
	}
	$viejo = trim( (string) $antes->post_title );
	$nuevo = trim( (string) $despues->post_title );
	if ( '' === $viejo || '' === $nuevo || $viejo === $nuevo ) {
		return;
	}
	if ( function_exists( 'pll_get_post_language' ) && function_exists( 'pll_default_language' )
		&& pll_get_post_language( $post_id ) !== pll_default_language() ) {
		return; // Solo cuenta el nombre en español.
	}

	$ofertas = get_posts( array(
		'post_type'      => 'hot_escape',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_pe_hotel',
		'meta_value'     => $viejo,
		'lang'           => '', // Todos los idiomas.
	) );
	foreach ( $ofertas as $oferta_id ) {
		update_post_meta( $oferta_id, '_pe_hotel', $nuevo );
	}
}
add_action( 'post_updated', 'pe_hotel_renombrado', 10, 3 );