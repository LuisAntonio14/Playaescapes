<?php
/**
 * PlayaEscapes – Artículo de prueba del blog (TEMPORAL).
 *
 * Uso: con sesión de administrador, abrir una sola vez
 *   /wp-admin/?pe_cargar_blog_prueba=1
 * Crea el artículo "La Catrina" (texto del diseño de Marketing) en español,
 * con la categoría Lifestyle y la imagen de portada del Inicio.
 * Después de usarlo, quitar este archivo y su require_once.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_bp_parrafo( $html ) {
	return "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function pe_bp_h2( $texto ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $texto ) . "</h2>\n<!-- /wp:heading -->\n\n";
}

function pe_bp_separador() {
	return "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n";
}

function pe_bp_imagen( $id ) {
	if ( ! $id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $id, 'large' );
	if ( ! $url ) {
		return '';
	}
	return '<!-- wp:image {"id":' . (int) $id . ',"sizeSlug":"large"} -->' . "\n"
		. '<figure class="wp-block-image size-large"><img src="' . esc_url( $url ) . '" alt="" class="wp-image-' . (int) $id . '"/></figure>' . "\n"
		. "<!-- /wp:image -->\n\n";
}

function pe_bp_lista( $items ) {
	$html = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
	foreach ( $items as $item ) {
		$html .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	return $html . "</ul>\n<!-- /wp:list -->\n\n";
}

function pe_bp_tabla( $filas ) {
	$html = "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table><tbody>";
	foreach ( $filas as $fila ) {
		$html .= '<tr><td>' . $fila[0] . '</td><td>' . $fila[1] . '</td></tr>';
	}
	return $html . "</tbody></table></figure>\n<!-- /wp:table -->\n\n";
}

function pe_cargar_blog_prueba() {
	if ( empty( $_GET['pe_cargar_blog_prueba'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Evita crear el artículo dos veces.
	$existente = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'any',
		'meta_key'       => '_pe_blog_prueba',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( $existente ) {
		wp_die( sprintf(
			'El artículo de prueba ya existe. <a href="%s">Verlo</a> · <a href="%s">Editarlo</a>',
			esc_url( get_permalink( $existente[0] ) ),
			esc_url( get_edit_post_link( $existente[0], 'url' ) )
		), 'Blog de prueba', array( 'response' => 200 ) );
	}

	// Imagen: la imagen destacada de la página de Inicio (si existe).
	$imagen = (int) get_post_thumbnail_id( (int) get_option( 'page_on_front' ) );

	// Categoría Lifestyle.
	$cat = term_exists( 'Lifestyle', 'category' );
	if ( ! $cat ) {
		$cat = wp_insert_term( 'Lifestyle', 'category' );
	}
	$cat_id = is_array( $cat ) ? (int) $cat['term_id'] : (int) $cat;

	$c  = pe_bp_parrafo( 'La Catrina (La Calavera Catrina) es la calavera femenina, vestida con elegancia, que se convirtió en el símbolo más conocido del Día de Muertos en México. Nació a principios de la década de 1910 como un grabado satírico de José Guadalupe Posada, que se burlaba de quienes negaban sus raíces para imitar la moda europea. Años después, Diego Rivera le dio cuerpo completo y su famoso nombre. Hoy representa la idea de que la muerte llega para todos, ricos o pobres.' );
	$c .= pe_bp_parrafo( 'La Catrina está en todas partes durante el Día de Muertos. La ves en los sombreros de ala ancha y los rostros pintados de los desfiles, en las figuras de barro de los mercados y en murales de todo México. Casi todos la reconocen al instante, pero pocos saben que empezó como una crítica a quienes querían aparentar lo que no eran.' );
	$c .= pe_bp_imagen( $imagen );

	$c .= pe_bp_h2( '¿Quién es la Catrina?' );
	$c .= pe_bp_parrafo( 'La Catrina es una calavera vestida como una dama de clase alta, casi siempre con un vestido largo y un sombrero enorme adornado con plumas o flores. Su nombre completo es La Calavera Catrina. "Catrina" viene de catrín, una palabra que en México se usa para describir a alguien muy elegante y bien vestido.' );
	$c .= pe_bp_parrafo( 'No es una diosa ni una figura religiosa, y tampoco fue una persona real. Es un personaje creado por un artista que, a lo largo de un siglo, se convirtió en un símbolo nacional.' );
	$c .= pe_bp_imagen( $imagen );

	$c .= pe_bp_h2( 'Origen de la Catrina: la Calavera Garbancera de Posada' );
	$c .= pe_bp_parrafo( 'La historia de la Catrina comienza con José Guadalupe Posada (1852–1913), grabador y caricaturista originario de Aguascalientes que trabajó en la Ciudad de México. Posada era famoso por sus calaveras, ilustraciones de esqueletos que acompañaban versos satíricos sobre políticos, ricos y la vida diaria.' );
	$c .= pe_bp_parrafo( 'Entre 1910 y 1913, creó un grabado de una calavera sonriente con un gran sombrero de estilo francés adornado con plumas. La llamó La Calavera Garbancera.' );

	$c .= pe_bp_h2( 'Diego Rivera y el nacimiento de la Catrina' );
	$c .= pe_bp_parrafo( 'Entre 1946 y 1947, Rivera pintó el mural Sueño de una tarde dominical en la Alameda Central. En el centro colocó a la calavera de Posada con cuerpo completo, un elegante vestido blanco y una boa de plumas.' );
	$c .= pe_bp_separador();

	$c .= pe_bp_h2( 'Significado de la Catrina' );
	$c .= pe_bp_parrafo( 'Si preguntas qué significa la Catrina, escucharás varias respuestas, todas ligadas a su historia:' );
	$c .= pe_bp_lista( array(
		'<strong>La muerte nos iguala.</strong> Ricos o pobres, todos terminamos igual.',
		'<strong>Una forma alegre de ver la muerte.</strong> El Día de Muertos entiende la muerte como parte de la vida.',
		'<strong>Identidad mexicana.</strong> Se volvió un símbolo de México reconocido en todo el mundo.',
	) );
	$c .= pe_bp_separador();

	$c .= pe_bp_h2( 'Dónde ver a la Catrina en México' );
	$c .= pe_bp_tabla( array(
		array( 'Lugar', 'Por qué vale la pena' ),
		array( 'Museo Mural Diego Rivera<br><em>Ciudad de México</em>', 'Aquí está el mural donde la Catrina obtuvo su nombre y su cuerpo completo.' ),
		array( 'Museo José Guadalupe Posada<br><em>Aguascalientes</em>', 'Dedicado a los grabados de Posada en su ciudad.' ),
		array( 'Capula<br><em>Michoacán</em>', 'Pueblo conocido por sus Catrinas de barro hechas a mano.' ),
		array( 'Mercados de todo el país', 'Figuras, grabados y accesorios de disfraz en las semanas previas al Día de Muertos.' ),
	) );
	$c .= pe_bp_separador();

	$c .= pe_bp_h2( 'Preguntas frecuentes' );
	$c .= pe_bp_parrafo( '<strong>¿La Catrina fue una persona real?</strong><br>No. Es un personaje creado por José Guadalupe Posada y desarrollado por Diego Rivera.' );
	$c .= pe_bp_parrafo( '<strong>¿Cuándo se celebra a la Catrina?</strong><br>Aparece durante toda la temporada de Día de Muertos, desde finales de octubre hasta el 2 de noviembre.' );

	$id = wp_insert_post( wp_slash( array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'post_title'    => 'La Catrina: historia, origen y significado del ícono',
		'post_excerpt'  => 'Descubre quién creó La Catrina y cómo se convirtió en uno de los símbolos más representativos del Día de Muertos en México.',
		'post_content'  => $c,
		'post_category' => $cat_id ? array( $cat_id ) : array(),
	) ), true );

	if ( is_wp_error( $id ) ) {
		wp_die( 'No se pudo crear el artículo: ' . esc_html( $id->get_error_message() ) );
	}

	update_post_meta( $id, '_pe_blog_prueba', 1 );
	if ( $imagen ) {
		set_post_thumbnail( $id, $imagen );
	}
	if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
		pll_set_post_language( $id, pll_default_language() );
	}

	wp_die( sprintf(
		'Listo. Artículo de prueba creado. <a href="%s">Verlo</a> · <a href="%s">Editarlo</a>',
		esc_url( get_permalink( $id ) ),
		esc_url( get_edit_post_link( $id, 'url' ) )
	), 'Blog de prueba', array( 'response' => 200 ) );
}
add_action( 'admin_init', 'pe_cargar_blog_prueba', 99 );
