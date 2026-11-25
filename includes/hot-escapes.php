<?php
/**
 * Módulo Hot Escapes: tipo de contenido, datos de la promoción y filtro de vigencia.
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

	$hotel_guardado = (string) get_post_meta( $post->ID, '_pe_hotel', true );
	$etiqueta       = (string) get_post_meta( $post->ID, '_pe_oferta_etiqueta', true );
	$insignia       = (string) get_post_meta( $post->ID, '_pe_oferta_insignia', true );
	$subtitulo      = (string) get_post_meta( $post->ID, '_pe_oferta_subtitulo', true );
	$anterior       = (string) get_post_meta( $post->ID, '_pe_precio_anterior', true );
	$precio         = (string) get_post_meta( $post->ID, '_pe_precio_desde', true );
	$vigencia_ini   = (string) get_post_meta( $post->ID, '_pe_vigencia_inicio', true );
	$vigencia_fin   = (string) get_post_meta( $post->ID, '_pe_vigencia_fin', true );

	// Lista de pendientes para que la promoción se vea completa.
	$pendientes = array();
	if ( ! has_post_thumbnail( $post ) ) {
		$pendientes[] = 'Imagen destacada (barra lateral derecha): es el fondo de la tarjeta.';
	}
	if ( '' === $hotel_guardado ) {
		$pendientes[] = 'Hotel: sin hotel, "Me interesa la oferta" no lo deja elegido en el widget.';
	}
	if ( '' === $precio ) {
		$pendientes[] = 'Precio desde.';
	}
	?>
	<div class="pe-ayuda-panel">
		<p><strong>Dónde se ve esta promoción</strong></p>
		<ul>
			<li><strong>Inicio, carrusel de ofertas:</strong> la imagen destacada de fondo y los textos de este cuadro. El texto del editor de arriba <strong>no</strong> se muestra en el Inicio.</li>
			<li>El botón <strong>"Me interesa la oferta"</strong> lleva al widget de reserva con el hotel y el nombre de la promoción ya puestos.</li>
			<li>Solo se muestra mientras está <strong>vigente</strong>. Al vencer desaparece sola del sitio (no se borra).</li>
		</ul>
		<p class="description">Imagen recomendada: horizontal, de al menos 1600 × 900 px, sin textos dentro de la imagen.</p>
	</div>

	<?php if ( 'auto-draft' !== $post->post_status && $pendientes ) : ?>
		<div class="pe-ayuda-pendiente">
			<p><strong>Para que la promoción se vea completa, falta:</strong></p>
			<ul>
				<?php foreach ( $pendientes as $pendiente ) : ?>
					<li><?php echo esc_html( $pendiente ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php pe_aviso_traduccion( $post ); ?>

	<div class="pe-campos-panel">
		<div class="pe-campos-grupo">
			<h4>1. Hotel</h4>
			<p>
				<label for="pe_hotel"><strong>Hotel de la promoción</strong></label>
				<select name="pe_hotel" id="pe_hotel">
					<option value="">Selecciona un hotel</option>
					<?php foreach ( pe_lista_hoteles() as $hotel ) : ?>
						<option value="<?php echo esc_attr( $hotel ); ?>" <?php selected( $hotel_guardado, $hotel ); ?>><?php echo esc_html( $hotel ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="description">Para una promoción de todos los hoteles, déjalo en "Selecciona un hotel": el cliente lo elige en el widget.</span>
			</p>
		</div>

		<div class="pe-campos-grupo">
			<h4>2. Textos de la tarjeta</h4>
			<p>
				<label for="pe_oferta_etiqueta"><strong>Texto pequeño (arriba del título)</strong></label>
				<input type="text" class="regular-text" name="pe_oferta_etiqueta" id="pe_oferta_etiqueta" value="<?php echo esc_attr( $etiqueta ); ?>" placeholder="Si queda vacío: Oferta limitada">
			</p>
			<p>
				<label for="pe_oferta_insignia"><strong>Insignia</strong></label>
				<input type="text" class="regular-text" name="pe_oferta_insignia" id="pe_oferta_insignia" value="<?php echo esc_attr( $insignia ); ?>" placeholder="Ejemplo: 25% OFF — Hot Escape">
				<span class="description">Recuadro junto al texto pequeño. Si queda vacío, no se muestra.</span>
			</p>
			<p>
				<label for="pe_oferta_subtitulo"><strong>Subtítulo</strong></label>
				<input type="text" class="large-text" name="pe_oferta_subtitulo" id="pe_oferta_subtitulo" value="<?php echo esc_attr( $subtitulo ); ?>" placeholder="Ejemplo: Todo incluido para 2 personas">
				<span class="description">Debajo del título. El título es el nombre de la promoción (arriba): lo que pongas entre *asteriscos* sale en cursiva.</span>
			</p>
		</div>

		<div class="pe-campos-grupo">
			<h4>3. Precio (MXN por persona por noche)</h4>
			<p>
				<label for="pe_precio_desde"><strong>Precio especial</strong></label>
				<input type="number" min="0" step="1" name="pe_precio_desde" id="pe_precio_desde" value="<?php echo esc_attr( $precio ); ?>" placeholder="Ejemplo: 15000">
				<span class="description">Solo el número, sin $ ni comas. Si queda vacío, la tarjeta no muestra precio.</span>
			</p>
			<p>
				<label for="pe_precio_anterior"><strong>Precio anterior, tachado (opcional)</strong></label>
				<input type="number" min="0" step="1" name="pe_precio_anterior" id="pe_precio_anterior" value="<?php echo esc_attr( $anterior ); ?>" placeholder="Ejemplo: 20000">
				<span class="description" id="pe-ahorro"></span>
			</p>
		</div>

		<div class="pe-campos-grupo">
			<h4>4. Vigencia</h4>
			<p>
				<label for="pe_vigencia_inicio"><strong>Desde</strong></label>
				<input type="date" name="pe_vigencia_inicio" id="pe_vigencia_inicio" value="<?php echo esc_attr( $vigencia_ini ); ?>">
			</p>
			<p>
				<label for="pe_vigencia_fin"><strong>Hasta</strong></label>
				<input type="date" name="pe_vigencia_fin" id="pe_vigencia_fin" value="<?php echo esc_attr( $vigencia_fin ); ?>">
				<span class="description">En la tarjeta sale "Válido hasta" con esta fecha. Si las dos quedan vacías, la promoción se muestra siempre.</span>
			</p>
			<p class="description" id="pe-vigencia-estado"></p>
		</div>

		<div class="pe-vista-previa">
			<p><strong>Así se leerá la tarjeta</strong> <span class="description">(solo textos; el diseño final está en el Inicio)</span></p>
			<p id="pe-vista-cabecera"></p>
			<p class="pe-vista-previa__lista" id="pe-vista-titulo"></p>
			<p id="pe-vista-subtitulo"></p>
			<p id="pe-vista-precio"></p>
		</div>
	</div>

	<script>
	(function () {
		var c = function (id) { return document.getElementById(id); };
		var hoy = '<?php echo esc_js( current_time( 'Y-m-d' ) ); ?>';
		var dinero = function (n) { return '$' + Number(n).toLocaleString('es-MX'); };
		var fecha = function (f) { var p = f.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }); };

		function titulo() {
			var t = document.querySelector('.editor-post-title__input, .editor-post-title, #title');
			if (!t) { return ''; }
			return (t.value !== undefined ? t.value : t.textContent).trim();
		}
		function pintar() {
			var precio = parseInt(c('pe_precio_desde').value, 10);
			var anterior = parseInt(c('pe_precio_anterior').value, 10);
			var ini = c('pe_vigencia_inicio').value, fin = c('pe_vigencia_fin').value;

			// Ahorro.
			var ahorro = c('pe-ahorro');
			if (anterior && precio && anterior > precio) {
				ahorro.textContent = 'En la tarjeta sale tachado y con "Ahorras ' + dinero(anterior - precio) + ' MXN".';
				ahorro.style.color = '';
			} else if (anterior && precio) {
				ahorro.textContent = 'No se mostrará: el precio anterior debe ser mayor que el precio especial.';
				ahorro.style.color = '#b32d2e';
			} else {
				ahorro.textContent = 'Si queda vacío, no se muestra el precio tachado.';
				ahorro.style.color = '';
			}

			// Estado de la vigencia.
			var estado = c('pe-vigencia-estado');
			var texto = '', color = '#008a20';
			if (ini && fin && fin < ini) {
				texto = '⚠ La fecha "Hasta" es anterior a "Desde": la promoción no se mostrará.'; color = '#b32d2e';
			} else if (fin && fin < hoy) {
				texto = '⚠ Ya venció: no se muestra en el sitio.'; color = '#b32d2e';
			} else if (ini && ini > hoy) {
				texto = 'Próxima: se mostrará a partir del ' + fecha(ini) + (fin ? ' y hasta el ' + fecha(fin) : '') + '.'; color = '#996800';
			} else {
				texto = '✓ Vigente: se muestra en el sitio' + (fin ? ' hasta el ' + fecha(fin) : ' sin fecha de fin') + '.';
			}
			estado.textContent = texto;
			estado.style.color = color;

			// Vista previa.
			var cab = c('pe_oferta_etiqueta').value.trim() || 'Oferta limitada';
			var ins = c('pe_oferta_insignia').value.trim();
			c('pe-vista-cabecera').textContent = cab + (ins ? '   [' + ins + ']' : '');
			c('pe-vista-titulo').innerHTML = '';
			(titulo() || 'Título de la promoción').split(/(\*[^*]+\*)/).forEach(function (parte) {
				if (/^\*[^*]+\*$/.test(parte)) {
					var em = document.createElement('em'); em.textContent = parte.slice(1, -1); c('pe-vista-titulo').appendChild(em);
				} else {
					c('pe-vista-titulo').appendChild(document.createTextNode(parte));
				}
			});
			c('pe-vista-subtitulo').textContent = c('pe_oferta_subtitulo').value.trim();
			c('pe-vista-precio').textContent = precio
				? 'Precio especial ' + (anterior > precio ? '(antes ' + dinero(anterior) + ' MXN) ' : '') + dinero(precio) + ' MXN por persona / noche' + (fin ? ' · Válido hasta ' + fecha(fin) : '')
				: '';
		}
		document.addEventListener('input', pintar, true);
		document.addEventListener('change', pintar, true);
		pintar();
		setTimeout(pintar, 1500); // El título del editor carga después.
	})();
	</script>
	<?php
}

// Guardar los datos al publicar o actualizar.
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

	// Solo se acepta un hotel de la lista única (o ninguno).
	if ( isset( $_POST['pe_hotel'] ) ) {
		$hotel = sanitize_text_field( wp_unslash( $_POST['pe_hotel'] ) );
		if ( '' === $hotel || in_array( $hotel, pe_lista_hoteles(), true ) ) {
			update_post_meta( $post_id, '_pe_hotel', $hotel );
		}
	}

	foreach ( array( 'pe_oferta_etiqueta', 'pe_oferta_insignia', 'pe_oferta_subtitulo' ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) ) {
			update_post_meta( $post_id, '_' . $campo, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
		}
	}

	if ( isset( $_POST['pe_precio_anterior'] ) ) {
		$anterior = '' === $_POST['pe_precio_anterior'] ? '' : absint( $_POST['pe_precio_anterior'] );
		update_post_meta( $post_id, '_pe_precio_anterior', $anterior );
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

// Columnas de la lista de Hot Escapes en el administrador.
function pe_columnas_hot_escapes( $columnas ) {
	$nuevas = array();
	if ( isset( $columnas['cb'] ) ) {
		$nuevas['cb'] = $columnas['cb'];
	}
	$nuevas['pe_col_imagen']   = 'Imagen';
	$nuevas['title']           = 'Promoción';
	$nuevas['pe_col_hotel']    = 'Hotel';
	$nuevas['pe_col_precio']   = 'Precio desde';
	$nuevas['pe_col_vigencia'] = 'Vigencia';
	$nuevas['pe_col_estado']   = 'Estado';
	// Columnas de idioma de Polylang.
	foreach ( $columnas as $clave => $nombre ) {
		if ( 0 === strpos( $clave, 'language_' ) ) {
			$nuevas[ $clave ] = $nombre;
		}
	}
	$nuevas['date'] = 'Fecha';
	return $nuevas;
}
add_filter( 'manage_hot_escape_posts_columns', 'pe_columnas_hot_escapes' );

function pe_contenido_columnas_hot_escapes( $columna, $post_id ) {
	$inicio = get_post_meta( $post_id, '_pe_vigencia_inicio', true );
	$fin    = get_post_meta( $post_id, '_pe_vigencia_fin', true );

	switch ( $columna ) {
		case 'pe_col_imagen':
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, array( 60, 40 ), array( 'style' => 'width:60px;height:40px;object-fit:cover;border-radius:4px;' ) );
			} else {
				echo '<span style="color:#b32d2e;">Sin imagen</span>';
			}
			break;

		case 'pe_col_hotel':
			$hotel = get_post_meta( $post_id, '_pe_hotel', true );
			if ( '' === $hotel ) {
				echo '<span style="color:#646970;">Todos / sin hotel</span>';
			} else {
				echo esc_html( $hotel );
			}
			break;

		case 'pe_col_precio':
			$precio = get_post_meta( $post_id, '_pe_precio_desde', true );
			if ( '' !== $precio ) {
				echo esc_html( '$' . number_format_i18n( (int) $precio ) . ' MXN' );
			}
			break;

		case 'pe_col_vigencia':
			if ( '' === $inicio && '' === $fin ) {
				echo 'Sin límite';
			} elseif ( '' === $fin ) {
				echo esc_html( 'Desde ' . $inicio );
			} elseif ( '' === $inicio ) {
				echo esc_html( 'Hasta ' . $fin );
			} else {
				echo esc_html( $inicio . ' a ' . $fin );
			}
			break;

		case 'pe_col_estado':
			// Misma regla que el filtro del sitio público.
			$hoy = current_time( 'Y-m-d' );
			if ( '' !== $inicio && '' !== $fin && $fin < $inicio ) {
				echo '<span style="color:#b32d2e;font-weight:bold;">Revisar fechas</span>';
			} elseif ( '' !== $fin && $fin < $hoy ) {
				echo '<span style="color:#b32d2e;font-weight:bold;">Vencida</span>';
			} elseif ( '' !== $inicio && $inicio > $hoy ) {
				echo '<span style="color:#996800;font-weight:bold;">Próxima</span>';
			} else {
				echo '<span style="color:#008a20;font-weight:bold;">Vigente</span>';
			}
			if ( 'publish' !== get_post_status( $post_id ) ) {
				echo '<br><span style="color:#646970;">(No publicada)</span>';
			}
			break;
	}
}
add_action( 'manage_hot_escape_posts_custom_column', 'pe_contenido_columnas_hot_escapes', 10, 2 );