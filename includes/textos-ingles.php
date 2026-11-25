<?php
/**
 * Módulo Textos del sitio: pantalla para que Editores y Ventas cambien los textos fijos
 * del sitio en español y en inglés (grupo "PlayaEscapes" de Polylang) sin entrar a la
 * configuración de Polylang, que es solo para administradores.
 * Organizada por secciones, con buscador, filtro de pendientes y aviso de cambios sin guardar.
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

// Descripción de cada sección: dónde se ve en el sitio.
function pe_textos_descripcion( $seccion ) {
	$descripciones = array(
		'Encabezado'            => 'Barra superior y menú, en todas las páginas.',
		'Pie de página'         => 'Parte inferior de todas las páginas.',
		'Inicio'                => 'Página principal: portada, sellos, ofertas, hoteles, pasos y contacto.',
		'Blog'                  => 'Listado de artículos del blog.',
		'Artículo del blog'     => 'Página de cada artículo del blog.',
		'Contacto'              => 'Página y formulario de Contacto.',
		'Formulario de reserva' => 'Página donde el cliente completa su solicitud.',
		'Ventana de reserva'    => 'Widget de reserva (hotel, fechas y huéspedes).',
		'Correo al cliente'     => 'Correo que recibe el cliente al enviar su solicitud.',
		'Agencia certificada'   => 'Aviso que se abre al dar clic en el sello de la portada.',
		'Avisos legales'        => 'Enlaces al aviso de privacidad y a los términos.',
		'WhatsApp'              => 'Botón de WhatsApp.',
	);
	if ( isset( $descripciones[ $seccion ] ) ) {
		return $descripciones[ $seccion ];
	}
	if ( 0 === strpos( $seccion, 'Ajustes' ) ) {
		return 'Textos escritos en Ajustes > PlayaEscapes.';
	}
	return '';
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
					unset( $mo->entries[ $texto ] ); // Vacío: se usa el texto original.
				} else {
					$mo->add_entry( $mo->make_entry( $texto, $traduccion ) );
				}
			}
			$mo->export_to_db( $idioma );
		}

		do_action( 'breeze_clear_all_cache' ); // Limpia la caché de Breeze si está disponible.
		$aviso = 'Cambios guardados. Si no los ves en el sitio, espera unos minutos y recarga en una ventana de incógnito.';
	}

	// Textos actuales.
	$mos = array();
	foreach ( $idiomas as $idioma ) {
		$mo = new PLL_MO();
		$mo->import_from_db( $idioma );
		$mos[ $idioma->slug ] = $mo;
	}
	$principal = pll_default_language();
	?>
	<div class="wrap pe-tx">
		<h1>Textos del sitio</h1>

		<div class="pe-tx__ayuda">
			<p><strong>Cómo usar esta pantalla</strong></p>
			<ol>
				<li>Abre la sección que quieres cambiar o usa el buscador.</li>
				<li><strong>Español (texto nuevo):</strong> escribe solo si quieres cambiar el texto original. Si lo dejas vacío, se usa el original.</li>
				<li><strong>English:</strong> escribe la traducción. Si la dejas vacía, en inglés se ve el texto original en español.</li>
				<li>Da clic en <strong>Guardar cambios</strong>. El botón aparece en cuanto cambias algo.</li>
			</ol>
			<p class="description">Las páginas, artículos, hoteles y promociones se editan desde su propio menú; su versión en inglés se crea con el botón "+" de la bandera. Los textos con <code>%s</code> deben conservarlo igual: ahí se pone un dato (por ejemplo, el nombre o un precio). En los textos del Inicio, lo que va entre *asteriscos* sale en cursiva y entre **dobles asteriscos** en negritas.</p>
		</div>

		<?php if ( '' !== $aviso ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $aviso ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $grupos || ! $idiomas ) : ?>
			<p>No hay textos para editar.</p>
		<?php else : ?>
			<div class="pe-tx__barra">
				<input type="search" id="pe-tx-buscar" class="regular-text" placeholder="Buscar un texto (en español o en inglés)…" aria-label="Buscar un texto">
				<label><input type="checkbox" id="pe-tx-pendientes"> Solo textos sin traducir al inglés</label>
				<button type="button" class="button-link" id="pe-tx-abrir-todo">Abrir todas</button>
				<button type="button" class="button-link" id="pe-tx-cerrar-todo">Cerrar todas</button>
			</div>
			<p class="pe-tx__sin-resultados" hidden>No se encontró ningún texto.</p>

			<form method="post" id="pe-tx-form">
				<?php wp_nonce_field( 'pe_textos_guardar', 'pe_textos_nonce' ); ?>

				<?php
				$n = 0;
				foreach ( $grupos as $seccion => $textos ) :
					$n++;
					$pendientes = 0;
					foreach ( $textos as $texto => $largo ) {
						foreach ( $idiomas as $idioma ) {
							if ( $idioma->slug !== $principal && '' === pe_textos_traduccion( $mos[ $idioma->slug ], $texto ) ) {
								$pendientes++;
							}
						}
					}
					$descripcion = pe_textos_descripcion( $seccion );
					?>
					<details class="pe-tx__seccion" id="pe-tx-<?php echo esc_attr( $n ); ?>">
						<summary>
							<span class="pe-tx__nombre"><?php echo esc_html( $seccion ); ?></span>
							<?php if ( '' !== $descripcion ) : ?>
								<span class="pe-tx__donde"><?php echo esc_html( $descripcion ); ?></span>
							<?php endif; ?>
							<span class="pe-tx__cuenta"><?php echo esc_html( count( $textos ) . ( 1 === count( $textos ) ? ' texto' : ' textos' ) ); ?></span>
							<?php if ( $pendientes ) : ?>
								<span class="pe-tx__pendiente"><?php echo esc_html( $pendientes . ' sin traducir' ); ?></span>
							<?php endif; ?>
							<span class="pe-tx__cambios" hidden></span>
						</summary>

						<table class="widefat striped pe-tx__tabla">
							<thead>
								<tr>
									<th style="width:28%;">Texto original</th>
									<?php foreach ( $idiomas as $idioma ) : ?>
										<th><?php echo esc_html( $idioma->slug === $principal ? $idioma->name . ' (texto nuevo)' : $idioma->name ); ?></th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $textos as $texto => $largo ) : ?>
									<?php
									$sin_ingles = false;
									foreach ( $idiomas as $idioma ) {
										if ( $idioma->slug !== $principal && '' === pe_textos_traduccion( $mos[ $idioma->slug ], $texto ) ) {
											$sin_ingles = true;
										}
									}
									?>
									<tr class="pe-tx__fila<?php echo $sin_ingles ? ' pe-tx__fila--pendiente' : ''; ?>">
										<td class="pe-tx__original"><?php echo esc_html( $texto ); ?></td>
										<?php foreach ( $idiomas as $idioma ) : ?>
											<?php
											$nombre = 'pe_textos[' . $idioma->slug . '][' . md5( $texto ) . ']';
											$valor  = pe_textos_traduccion( $mos[ $idioma->slug ], $texto );
											$ayuda  = $idioma->slug === $principal ? 'Igual que el original' : 'Sin traducir: se ve en español';
											?>
											<td>
												<?php if ( $largo ) : ?>
													<textarea class="large-text" rows="3" name="<?php echo esc_attr( $nombre ); ?>" placeholder="<?php echo esc_attr( $ayuda ); ?>" aria-label="<?php echo esc_attr( $idioma->name . ': ' . $texto ); ?>"><?php echo esc_textarea( $valor ); ?></textarea>
												<?php else : ?>
													<input type="text" class="large-text" name="<?php echo esc_attr( $nombre ); ?>" value="<?php echo esc_attr( $valor ); ?>" placeholder="<?php echo esc_attr( $ayuda ); ?>" aria-label="<?php echo esc_attr( $idioma->name . ': ' . $texto ); ?>">
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<p class="pe-tx__pie" hidden>
							<button type="submit" class="button button-primary">Guardar cambios</button>
							<span class="description">Se guardan los cambios de todas las secciones.</span>
						</p>
					</details>
				<?php endforeach; ?>

				<div class="pe-tx__guardar" hidden>
					<span class="pe-tx__guardar-texto"></span>
					<button type="button" class="button" id="pe-tx-descartar">Descartar cambios</button>
					<button type="submit" class="button button-primary button-large">Guardar cambios</button>
				</div>
			</form>

			<style>
				.pe-tx__ayuda { max-width: 960px; margin: 12px 0 16px; padding: 12px 16px; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #2271b1; }
				.pe-tx__ayuda ol { margin: 4px 0 8px 20px; }
				.pe-tx__barra { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 20px; margin: 0 0 12px; }
				.pe-tx__seccion { margin: 0 0 8px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px; }
				.pe-tx__seccion > summary { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 12px; padding: 12px 16px; cursor: pointer; }
				.pe-tx__seccion[open] > summary { border-bottom: 1px solid #dcdcde; }
				.pe-tx__nombre { font-size: 15px; font-weight: 600; }
				.pe-tx__donde { flex: 1 1 260px; color: #646970; }
				.pe-tx__cuenta { color: #646970; }
				.pe-tx__pendiente { padding: 2px 8px; border-radius: 10px; background: #fcf0f1; color: #b32d2e; font-weight: 600; }
				.pe-tx__cambios { padding: 2px 8px; border-radius: 10px; background: #fcf9e8; color: #8a6d00; font-weight: 600; }
				.pe-tx__tabla { border: 0; }
				.pe-tx__tabla td { vertical-align: top; }
				.pe-tx__fila--pendiente .pe-tx__original { box-shadow: inset 3px 0 0 #d63638; }
				.pe-tx .pe-tx__cambiado { background: #fcf9e8; border-color: #dba617; }
				.pe-tx__pie { margin: 0; padding: 12px 16px; }
				.pe-tx__guardar { position: sticky; bottom: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 16px; padding: 12px 16px; background: #fff; border: 1px solid #dba617; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08); }
				.pe-tx__guardar[hidden] { display: none; }
				.pe-tx__guardar-texto { flex: 1 1 auto; font-weight: 600; }
				@media (max-width: 782px) {
					.pe-tx__tabla thead { display: none; }
					.pe-tx__tabla tr, .pe-tx__tabla td { display: block; width: auto !important; }
				}
			</style>

			<script>
			(function () {
				var form = document.getElementById('pe-tx-form');
				if (!form) { return; }
				var secciones = form.querySelectorAll('.pe-tx__seccion');
				var campos = form.querySelectorAll('input[type="text"], textarea');
				var barra = form.querySelector('.pe-tx__guardar');
				var barraTexto = barra.querySelector('.pe-tx__guardar-texto');
				var buscar = document.getElementById('pe-tx-buscar');
				var pendientes = document.getElementById('pe-tx-pendientes');
				var sinResultados = document.querySelector('.pe-tx__sin-resultados');
				var guardando = false;

				// Recordar qué secciones estaban abiertas (para volver a ellas después de guardar).
				var clave = 'pe-tx-abiertas';
				try {
					(JSON.parse(sessionStorage.getItem(clave) || '[]')).forEach(function (id) {
						var d = document.getElementById(id);
						if (d) { d.open = true; }
					});
				} catch (e) {}
				function recordar() {
					try {
						sessionStorage.setItem(clave, JSON.stringify(Array.prototype.filter.call(secciones, function (d) { return d.open; }).map(function (d) { return d.id; })));
					} catch (e) {}
				}
				Array.prototype.forEach.call(secciones, function (d) { d.addEventListener('toggle', recordar); });

				// Cambios sin guardar.
				function revisar() {
					var total = 0;
					Array.prototype.forEach.call(secciones, function (d) {
						var n = 0;
						d.querySelectorAll('input[type="text"], textarea').forEach(function (c) {
							var cambio = c.value !== c.defaultValue;
							c.classList.toggle('pe-tx__cambiado', cambio);
							if (cambio) { n++; }
						});
						var etiqueta = d.querySelector('.pe-tx__cambios');
						etiqueta.hidden = !n;
						etiqueta.textContent = n === 1 ? '1 cambio sin guardar' : n + ' cambios sin guardar';
						d.querySelector('.pe-tx__pie').hidden = !n;
						total += n;
					});
					barra.hidden = !total;
					barraTexto.textContent = total === 1 ? 'Tienes 1 cambio sin guardar.' : 'Tienes ' + total + ' cambios sin guardar.';
					return total;
				}
				form.addEventListener('input', revisar);

				document.getElementById('pe-tx-descartar').addEventListener('click', function () {
					campos.forEach(function (c) { c.value = c.defaultValue; });
					revisar();
				});
				form.addEventListener('submit', function () { guardando = true; recordar(); });
				window.addEventListener('beforeunload', function (e) {
					if (!guardando && revisar()) { e.preventDefault(); e.returnValue = ''; }
				});

				// Buscador y filtro de pendientes.
				function filtrar() {
					var q = buscar.value.trim().toLowerCase();
					var solo = pendientes.checked;
					var hay = 0;
					Array.prototype.forEach.call(secciones, function (d) {
						var visibles = 0;
						d.querySelectorAll('.pe-tx__fila').forEach(function (fila) {
							var texto = fila.textContent + ' ' + Array.prototype.map.call(fila.querySelectorAll('input, textarea'), function (c) { return c.value; }).join(' ');
							var ver = (!q || texto.toLowerCase().indexOf(q) !== -1) && (!solo || fila.classList.contains('pe-tx__fila--pendiente'));
							fila.hidden = !ver;
							if (ver) { visibles++; }
						});
						d.hidden = !visibles;
						if ((q || solo) && visibles) { d.open = true; }
						hay += visibles;
					});
					sinResultados.hidden = !!hay;
				}
				buscar.addEventListener('input', filtrar);
				pendientes.addEventListener('change', filtrar);

				document.getElementById('pe-tx-abrir-todo').addEventListener('click', function () {
					Array.prototype.forEach.call(secciones, function (d) { d.open = true; });
				});
				document.getElementById('pe-tx-cerrar-todo').addEventListener('click', function () {
					Array.prototype.forEach.call(secciones, function (d) { d.open = false; });
				});
			})();
			</script>
		<?php endif; ?>
	</div>
	<?php
}