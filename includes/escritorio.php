<?php
/**
 * Módulo Escritorio: cuadro "PlayaEscapes" al inicio del administrador, con accesos directos,
 * lo que hay que revisar y una mini guía. Cada persona ve solo lo que su rol puede usar.
 * Para Editor y Ventas se quitan del Escritorio los cuadros de WordPress que no usan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_escritorio_registrar() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return; // Suscriptores: Escritorio normal.
	}
	wp_add_dashboard_widget( 'pe_escritorio', 'PlayaEscapes: ¿qué quieres hacer?', 'pe_escritorio_cuadro' );

	// Ponerlo primero.
	global $wp_meta_boxes;
	if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['pe_escritorio'] ) ) {
		$nuestro = array( 'pe_escritorio' => $wp_meta_boxes['dashboard']['normal']['core']['pe_escritorio'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['pe_escritorio'] );
		$wp_meta_boxes['dashboard']['normal']['core'] = $nuestro + $wp_meta_boxes['dashboard']['normal']['core'];
	}

	// Para quienes no son administradores, quitar cuadros de WordPress que no usan.
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );       // Eventos y noticias de WordPress.
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );   // Borrador rápido.
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' ); // Salud del sitio.
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
}
add_action( 'wp_dashboard_setup', 'pe_escritorio_registrar', 99 );

// Cuántas promociones vigentes hay y cuáles vencen en los próximos 7 días (versión en español).
function pe_escritorio_promociones() {
	$hoy  = current_time( 'Y-m-d' );
	$args = array(
		'post_type'        => 'hot_escape',
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'no_found_rows'    => true,
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_default_language' ) ) {
		$args['lang'] = pll_default_language();
	}

	$vigentes = 0;
	$por_vencer = array();
	foreach ( get_posts( $args ) as $id ) {
		$inicio = (string) get_post_meta( $id, '_pe_vigencia_inicio', true );
		$fin    = (string) get_post_meta( $id, '_pe_vigencia_fin', true );
		if ( ( '' === $inicio || $inicio <= $hoy ) && ( '' === $fin || $fin >= $hoy ) ) {
			$vigentes++;
			if ( '' !== $fin && $fin <= gmdate( 'Y-m-d', strtotime( $hoy . ' +7 days' ) ) ) {
				$por_vencer[] = $id;
			}
		}
	}
	return array( $vigentes, $por_vencer );
}

// Hoteles a los que les falta algo para verse completos.
function pe_escritorio_hoteles_incompletos() {
	$ids = get_posts( array(
		'post_type'      => 'pe_hotel',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'lang'           => '', // Todos los idiomas.
	) );
	$faltan = 0;
	foreach ( $ids as $id ) {
		if ( ! has_post_thumbnail( $id )
			|| '' === trim( (string) get_post_field( 'post_excerpt', $id ) )
			|| '' === trim( (string) get_post_meta( $id, '_pe_hotel_ubicacion', true ) )
			|| '' === trim( (string) get_post_meta( $id, '_pe_hotel_precio_desde', true ) ) ) {
			$faltan++;
		}
	}
	return $faltan;
}

// Textos del sitio sin traducir al inglés.
function pe_escritorio_textos_pendientes() {
	if ( ! function_exists( 'pe_textos_registrados' ) || ! class_exists( 'PLL_MO' ) || ! function_exists( 'pll_languages_list' ) ) {
		return 0;
	}
	$pendientes = 0;
	$grupos     = pe_textos_registrados();
	foreach ( pll_languages_list( array( 'fields' => '' ) ) as $idioma ) {
		if ( $idioma->slug === pll_default_language() ) {
			continue;
		}
		$mo = new PLL_MO();
		$mo->import_from_db( $idioma );
		foreach ( $grupos as $textos ) {
			foreach ( $textos as $texto => $largo ) {
				if ( empty( $mo->entries[ $texto ]->translations[0] ) ) {
					$pendientes++;
				}
			}
		}
	}
	return $pendientes;
}

// Leads recibidos hoy y en los últimos 7 días.
function pe_escritorio_leads() {
	$contar = function ( $desde ) {
		$q = new WP_Query( array(
			'post_type'      => 'pe_lead',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => $desde, 'inclusive' => true ) ),
		) );
		return (int) $q->found_posts;
	};
	return array( $contar( current_time( 'Y-m-d' ) . ' 00:00:00' ), $contar( '7 days ago' ) );
}

function pe_escritorio_cuadro() {
	$puede_textos  = defined( 'PE_TEXTOS_PERMISO' ) && current_user_can( PE_TEXTOS_PERMISO );
	$puede_ajustes = defined( 'PE_AJUSTES_PERMISO' ) && current_user_can( PE_AJUSTES_PERMISO );
	$puede_leads   = current_user_can( 'pe_ver_leads' );

	// Accesos directos.
	$accesos = array(
		array( 'Crear promoción', admin_url( 'post-new.php?post_type=hot_escape' ), 'dashicons-megaphone', true ),
		array( 'Promociones', admin_url( 'edit.php?post_type=hot_escape' ), 'dashicons-list-view', false ),
		array( 'Hoteles', admin_url( 'edit.php?post_type=pe_hotel' ), 'dashicons-building', false ),
		array( 'Escribir en el blog', admin_url( 'post-new.php' ), 'dashicons-edit', false ),
		array( 'Editar el Inicio', get_option( 'page_on_front' ) ? get_edit_post_link( (int) get_option( 'page_on_front' ) ) : '', 'dashicons-admin-home', false ),
	);
	if ( $puede_textos ) {
		$accesos[] = array( 'Textos del sitio', admin_url( 'admin.php?page=pe-textos-ingles' ), 'dashicons-translation', false );
	}
	if ( $puede_leads ) {
		$accesos[] = array( 'Ver leads', admin_url( 'edit.php?post_type=pe_lead' ), 'dashicons-email-alt', false );
		$accesos[] = array( 'Ver consultas', admin_url( 'edit.php?post_type=pe_consulta' ), 'dashicons-format-chat', false );
	}
	if ( $puede_ajustes ) {
		$accesos[] = array( 'Ajustes', admin_url( 'options-general.php?page=pe-ajustes' ), 'dashicons-admin-generic', false );
	}
	$accesos[] = array( 'Ver el sitio', home_url( '/' ), 'dashicons-external', false );

	// Revisión.
	list( $vigentes, $por_vencer ) = pe_escritorio_promociones();
	$incompletos = pe_escritorio_hoteles_incompletos();
	$pendientes  = $puede_textos ? pe_escritorio_textos_pendientes() : 0;
	?>
	<div class="pe-esc">
		<div class="pe-esc__accesos">
			<?php foreach ( $accesos as $acceso ) : ?>
				<?php if ( '' === (string) $acceso[1] ) { continue; } ?>
				<a class="button <?php echo $acceso[3] ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $acceso[1] ); ?>"<?php echo 'Ver el sitio' === $acceso[0] ? ' target="_blank" rel="noopener"' : ''; ?>>
					<span class="dashicons <?php echo esc_attr( $acceso[2] ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $acceso[0] ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<h3>Para revisar</h3>
		<ul class="pe-esc__estado">
			<?php if ( $puede_leads ) : ?>
				<?php list( $hoy, $semana ) = pe_escritorio_leads(); ?>
				<li><span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pe_lead' ) ); ?>"><strong><?php echo esc_html( $hoy ); ?></strong> <?php echo esc_html( 1 === $hoy ? 'lead hoy' : 'leads hoy' ); ?></a>
					· <?php echo esc_html( $semana . ' en los últimos 7 días' ); ?>
				</li>
			<?php endif; ?>

			<li class="<?php echo $vigentes ? 'pe-esc--ok' : 'pe-esc--alerta'; ?>"><span class="dashicons dashicons-megaphone" aria-hidden="true"></span>
				<?php echo esc_html( $vigentes ? $vigentes . ( 1 === $vigentes ? ' promoción vigente en el Inicio.' : ' promociones vigentes en el Inicio.' ) : 'No hay promociones vigentes: el carrusel de ofertas no se muestra.' ); ?>
			</li>

			<?php foreach ( $por_vencer as $id ) : ?>
				<li class="pe-esc--aviso"><span class="dashicons dashicons-clock" aria-hidden="true"></span>
					Vence pronto: <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( wp_strip_all_tags( str_replace( '*', '', get_the_title( $id ) ) ) ); ?></a>
					(<?php echo esc_html( date_i18n( 'j \d\e F', strtotime( (string) get_post_meta( $id, '_pe_vigencia_fin', true ) ) ) ); ?>)
				</li>
			<?php endforeach; ?>

			<li class="<?php echo $incompletos ? 'pe-esc--aviso' : 'pe-esc--ok'; ?>"><span class="dashicons dashicons-building" aria-hidden="true"></span>
				<?php if ( $incompletos ) : ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pe_hotel&lang=all' ) ); ?>"><?php echo esc_html( $incompletos . ( 1 === $incompletos ? ' hotel incompleto' : ' hoteles incompletos' ) ); ?></a> (revisa la columna "Revisión").
				<?php else : ?>
					Todos los hoteles están completos.
				<?php endif; ?>
			</li>

			<?php if ( $puede_textos ) : ?>
				<li class="<?php echo $pendientes ? 'pe-esc--aviso' : 'pe-esc--ok'; ?>"><span class="dashicons dashicons-translation" aria-hidden="true"></span>
					<?php if ( $pendientes ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pe-textos-ingles' ) ); ?>"><?php echo esc_html( $pendientes . ( 1 === $pendientes ? ' texto sin traducir al inglés' : ' textos sin traducir al inglés' ) ); ?></a> (usa el filtro "Solo textos sin traducir").
					<?php else : ?>
						Todos los textos del sitio están traducidos.
					<?php endif; ?>
				</li>
			<?php endif; ?>
		</ul>

		<details class="pe-esc__guia">
			<summary><strong>Mini guía</strong></summary>
			<ul>
				<li><strong>Promociones:</strong> Hot Escapes &gt; Agregar promoción. Ponle imagen destacada, hotel, precio y vigencia. Al vencer, desaparece sola del sitio.</li>
				<li><strong>Versión en inglés:</strong> dentro de cada promoción, hotel o página, usa el botón "Crear versión en English".</li>
				<li><strong>Textos fijos</strong> (botones, títulos, formularios): menú Textos del sitio.</li>
				<li><strong>Inicio:</strong> al editar la página Inicio, el cuadro azul dice dónde se cambia cada parte.</li>
				<li><strong>Si no ves un cambio en el sitio:</strong> espera unos minutos y ábrelo en una ventana de incógnito (es la caché).</li>
			</ul>
		</details>
	</div>

	<style>
		.pe-esc__accesos { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
		.pe-esc__accesos .button { display: inline-flex; align-items: center; gap: 6px; }
		.pe-esc__accesos .dashicons { font-size: 16px; width: 16px; height: 16px; }
		.pe-esc h3 { margin: 16px 0 6px; }
		.pe-esc__estado { margin: 0; }
		.pe-esc__estado li { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; margin: 0; padding: 6px 0; border-bottom: 1px solid #f0f0f1; }
		.pe-esc__estado .dashicons { color: #646970; margin-right: 4px; }
		.pe-esc--ok .dashicons { color: #00a32a; }
		.pe-esc--aviso .dashicons { color: #dba617; }
		.pe-esc--alerta .dashicons, .pe-esc--alerta { color: #b32d2e; }
		.pe-esc__guia { margin-top: 12px; }
		.pe-esc__guia ul { margin: 6px 0 0 18px; list-style: disc; }
	</style>
	<?php
}
