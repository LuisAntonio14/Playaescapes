<?php
/**
 * Módulo Textos en inglés: pantalla para que Editores y Ventas corrijan las traducciones
 * de los textos fijos del sitio (grupo "PlayaEscapes" de Polylang) sin entrar a la
 * configuración de Polylang, que es solo para administradores.
 * Usa las mismas traducciones que Languages > Translations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Permiso para usar la pantalla: lo tienen Editor, Ventas PlayaEscapes y Administrador.
define( 'PE_TEXTOS_PERMISO', 'edit_others_pages' );

function pe_textos_menu() {
	if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) ) {
		return;
	}
	add_menu_page(
		'Textos del sitio',
		'Textos del sitio',
		PE_TEXTOS_PERMISO,
		'pe-textos-ingles',
		'pe_textos_pagina',
		'dashicons-translation',
		58
	);
}
add_action( 'admin_menu', 'pe_textos_menu' );

// Textos registrados del grupo PlayaEscapes, agrupados por sección.
function pe_textos_registrados() {
	if ( ! class_exists( 'PLL_Admin_Strings' ) ) {
		return array();
	}
	$grupos = array();
	foreach ( PLL_Admin_Strings::get_strings() as $s ) {
		if ( ! isset( $s['context'], $s['string'] ) || 'PlayaEscapes' !== $s['context'] || '' === $s['string'] ) {
			continue;
		}
		$seccion                              = isset( $s['name'] ) ? $s['name'] : 'Otros';
		$grupos[ $seccion ][ $s['string'] ] = ! empty( $s['multiline'] ) || strlen( $s['string'] ) > 80;
	}
	return $grupos;
}

// Idiomas: primero el predeterminado (español, para cambiar el texto original) y luego los demás.
function pe_textos_idiomas() {
	$principal = array();
	$otros     = array();
	foreach ( pll_languages_list( array( 'fields' => '' ) ) as $idioma ) {
		if ( $idioma->slug === pll_default_language() ) {
			$principal[] = $idioma;
		} else {
			$otros[] = $idioma;
		}
	}
	return array_merge( $principal, $otros );
}

// Traducción guardada de un texto, o vacío si no tiene.
function pe_textos_traduccion( $mo, $texto ) {
	return isset( $mo->entries[ $texto ] ) && isset( $mo->entries[ $texto ]->translations[0] )
		? (string) $mo->entries[ $texto ]->translations[0]
		: '';
}

function pe_textos_pagina() {
	if ( ! current_user_can( PE_TEXTOS_PERMISO ) ) {
		return;
	}

	$grupos  = pe_textos_registrados();
	$idiomas = pe_textos_idiomas();
	$aviso   = '';

	// Guardar.
	if ( isset( $_POST['pe_textos_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['pe_textos_nonce'] ), 'pe_textos_guardar' ) ) {
		$enviados = isset( $_POST['pe_textos'] ) && is_array( $_POST['pe_textos'] ) ? wp_unslash( $_POST['pe_textos'] ) : array();
		$validos  = array();
		foreach ( $grupos as $textos ) {
			foreach ( $textos as $texto => $largo ) {
				$validos[ md5( $texto ) ] = $texto;
			}
		}

		foreach ( $idiomas as $idioma ) {
			if ( empty( $enviados[ $idioma->slug ] ) || ! is_array( $enviados[ $idioma->slug ] ) ) {
				continue;
			}
			$mo = new PLL_MO();
			$mo->import_from_db( $idioma );
			foreach ( $enviados[ $idioma->slug ] as $clave => $traduccion ) {
				if ( ! isset( $validos[ $clave ] ) ) {
					continue; // Solo textos del grupo PlayaEscapes.
				}
				$texto      = $validos[ $clave ];
				$traduccion = trim( sanitize_textarea_field( $traduccion ) );
				if ( '' === $traduccion ) {
					unset( $mo->entries[ $texto ] ); // Vacío: se muestra el texto en español.
				} else {
					$mo->add_entry( $mo->make_entry( $texto, $traduccion ) );
				}
			}
			$mo->export_to_db( $idioma );
		}

		do_action( 'breeze_clear_all_cache' ); // Limpia la caché de Breeze si está disponible.
		$aviso = 'Cambios guardados. Si no los ves en el sitio, puede tardar unos minutos por la caché.';
	}

	// Traducciones actuales.
	$mos = array();
	foreach ( $idiomas as $idioma ) {
		$mo = new PLL_MO();
		$mo->import_from_db( $idioma );
		$mos[ $idioma->slug ] = $mo;
	}
	?>
	<div class="wrap">
		<h1>Textos del sitio</h1>
		<p>Textos fijos del sitio (menú, botones, formularios, Inicio, Blog…). Escribe el texto nuevo y da clic en <strong>Guardar cambios</strong>.</p>
		<p><strong>Español:</strong> escribe solo si quieres cambiar el texto original; si queda vacío, se usa el original. <strong>English:</strong> si queda vacío, en inglés se muestra el texto original en español.</p>
		<p>Las páginas, entradas, hoteles y promociones se traducen desde su propio editor, con el botón "+" de la bandera.</p>

		<?php if ( '' !== $aviso ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $aviso ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $grupos || ! $idiomas ) : ?>
			<p>No hay textos para traducir.</p>
		<?php else : ?>
			<p><input type="search" id="pe-textos-buscar" class="regular-text" placeholder="Buscar un texto…"></p>

			<form method="post">
				<?php wp_nonce_field( 'pe_textos_guardar', 'pe_textos_nonce' ); ?>

				<?php foreach ( $grupos as $seccion => $textos ) : ?>
					<h2 class="pe-textos-seccion"><?php echo esc_html( $seccion ); ?></h2>
					<table class="widefat striped pe-textos-tabla">
						<thead>
							<tr>
								<th style="width:30%;">Texto original</th>
								<?php foreach ( $idiomas as $idioma ) : ?>
									<th><?php echo esc_html( $idioma->slug === pll_default_language() ? $idioma->name . ' (texto nuevo)' : $idioma->name ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $textos as $texto => $largo ) : ?>
								<tr class="pe-textos-fila">
									<td><?php echo esc_html( $texto ); ?></td>
									<?php foreach ( $idiomas as $idioma ) : ?>
										<?php
										$nombre = 'pe_textos[' . $idioma->slug . '][' . md5( $texto ) . ']';
										$valor  = pe_textos_traduccion( $mos[ $idioma->slug ], $texto );
										?>
										<td>
											<?php if ( $largo ) : ?>
												<textarea class="large-text" rows="3" name="<?php echo esc_attr( $nombre ); ?>"><?php echo esc_textarea( $valor ); ?></textarea>
											<?php else : ?>
												<input type="text" class="large-text" name="<?php echo esc_attr( $nombre ); ?>" value="<?php echo esc_attr( $valor ); ?>">
											<?php endif; ?>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>

				<?php submit_button( 'Guardar cambios' ); ?>
			</form>

			<p class="description">Los textos con <code>%s</code>, <code>%1$s</code> o <code>%2$s</code> deben conservarlos igual en la traducción: ahí se insertan enlaces o datos.</p>

			<script>
			// Buscador: muestra solo las filas que contienen el texto buscado.
			(function () {
				var buscar = document.getElementById('pe-textos-buscar');
				if (!buscar) { return; }
				buscar.addEventListener('input', function () {
					var q = buscar.value.trim().toLowerCase();
					document.querySelectorAll('.pe-textos-tabla').forEach(function (tabla) {
						var visibles = 0;
						tabla.querySelectorAll('.pe-textos-fila').forEach(function (fila) {
							var campos = Array.prototype.map.call(fila.querySelectorAll('input, textarea'), function (c) { return c.value; }).join(' ');
							var ver = !q || (fila.textContent + ' ' + campos).toLowerCase().indexOf(q) !== -1;
							fila.style.display = ver ? '' : 'none';
							if (ver) { visibles++; }
						});
						tabla.style.display = visibles ? '' : 'none';
						tabla.previousElementSibling.style.display = visibles ? '' : 'none';
					});
				});
			})();
			</script>
		<?php endif; ?>
	</div>
	<?php
}