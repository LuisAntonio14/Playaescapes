<?php
/**
 * Módulo Ayudas del administrador: piezas compartidas para que los paneles
 * (Hoteles, Hot Escapes, Inicio, Leads…) se entiendan solos.
 * - Estilos de las cajas de ayuda, pendientes y vista previa.
 * - Aviso de la versión en otro idioma, con botón para crearla.
 * - Texto de ejemplo en el título de cada tipo de contenido.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Aviso de idiomas: si falta la versión en otro idioma, botón para crearla.
function pe_aviso_traduccion( $post ) {
	if ( ! function_exists( 'pll_get_post_language' ) || ! function_exists( 'pll_languages_list' ) || 'auto-draft' === $post->post_status ) {
		return;
	}
	$actual = pll_get_post_language( $post->ID );
	if ( ! $actual ) {
		return;
	}

	$faltan  = array();
	$existen = array();
	foreach ( pll_languages_list( array( 'fields' => '' ) ) as $idioma ) {
		if ( $idioma->slug === $actual ) {
			continue;
		}
		$traducido = pll_get_post( $post->ID, $idioma->slug );
		if ( $traducido ) {
			$existen[] = array( $idioma->name, get_edit_post_link( $traducido ) );
		} else {
			$faltan[] = array(
				$idioma->name,
				wp_nonce_url(
					admin_url( 'post-new.php?post_type=' . $post->post_type . '&from_post=' . $post->ID . '&new_lang=' . $idioma->slug ),
					'new-post-translation'
				),
			);
		}
	}
	?>
	<div class="pe-ayuda-idioma<?php echo $faltan ? ' pe-ayuda-idioma--falta' : ''; ?>">
		<?php foreach ( $faltan as $falta ) : ?>
			<p>
				<strong>Falta la versión en <?php echo esc_html( $falta[0] ); ?>.</strong>
				Mientras no exista, no aparece en el sitio en ese idioma.
				<a class="button button-small" href="<?php echo esc_url( $falta[1] ); ?>">Crear versión en <?php echo esc_html( $falta[0] ); ?></a>
			</p>
		<?php endforeach; ?>
		<?php foreach ( $existen as $existe ) : ?>
			<p>
				&#10003; Tiene versión en <?php echo esc_html( $existe[0] ); ?>.
				<a href="<?php echo esc_url( $existe[1] ); ?>">Editar versión en <?php echo esc_html( $existe[0] ); ?></a>
				<span class="description">(Guarda antes los cambios de esta pantalla.)</span>
			</p>
		<?php endforeach; ?>
	</div>
	<?php
}

// Texto de ejemplo en el título.
function pe_ayuda_titulo( $texto, $post ) {
	$titulos = array(
		'pe_hotel'   => 'Nombre del hotel (el nombre en español es el que sale en las listas y en los leads)',
		'hot_escape' => 'Título de la promoción (lo que va entre *asteriscos* sale en cursiva)',
	);
	return isset( $titulos[ $post->post_type ] ) ? $titulos[ $post->post_type ] : $texto;
}
add_filter( 'enter_title_here', 'pe_ayuda_titulo', 10, 2 );

// Estilos de las ayudas (solo en las pantallas de edición del administrador).
function pe_ayudas_estilos() {
	$pantalla = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $pantalla || ! in_array( $pantalla->base, array( 'post', 'edit' ), true ) ) {
		return;
	}
	?>
	<style>
		.pe-ayuda-panel { margin: 8px 0 12px; padding: 10px 14px; background: #f0f6fc; border-left: 4px solid #2271b1; }
		.pe-ayuda-panel p, .pe-ayuda-pendiente p, .pe-ayuda-idioma p { margin: 4px 0; }
		.pe-ayuda-panel ul, .pe-ayuda-pendiente ul { margin: 4px 0 6px 18px; list-style: disc; }
		.pe-ayuda-pendiente { margin: 0 0 12px; padding: 10px 14px; background: #fcf9e8; border-left: 4px solid #dba617; }
		.pe-ayuda-idioma { margin: 0 0 12px; padding: 8px 14px; background: #edfaef; border-left: 4px solid #00a32a; }
		.pe-ayuda-idioma--falta { background: #fcf0f1; border-left-color: #d63638; }
		.pe-ayuda-idioma .button { margin-left: 6px; vertical-align: middle; }
		.pe-campos-panel p { margin: 0 0 16px; }
		.pe-campos-panel label { display: block; margin-bottom: 4px; }
		.pe-campos-panel input[type="text"], .pe-campos-panel input[type="number"], .pe-campos-panel input[type="date"], .pe-campos-panel select { max-width: 100%; }
		.pe-campos-panel textarea { width: 100%; }
		.pe-campos-panel .description { display: block; margin-top: 4px; }
		.pe-campos-grupo { margin: 0 0 16px; padding: 12px 14px 2px; border: 1px solid #dcdcde; border-radius: 4px; }
		.pe-campos-grupo > h4 { margin: 0 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #50575e; }
		.pe-vista-previa { padding: 10px 14px; background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 4px; }
		.pe-vista-previa p { margin: 4px 0; }
		.pe-vista-previa__lista { font-size: 14px; color: #003873; }
	</style>
	<?php
}
add_action( 'admin_head', 'pe_ayudas_estilos' );
