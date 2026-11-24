<?php
/**
 * Módulo Reserva (paso 1 de la solicitud de reserva).
 *
 * - Widget: barra de reserva en la portada del Inicio, según el diseño de Figma de Carlos
 *   (acordado en la reunión del 23 de noviembre). Al bajar con el scroll se queda pegada arriba.
 * - Ventana "Solicita tu reservación": se sigue usando en las páginas que todavía no tienen
 *   el widget (acordado en la reunión del 17 de noviembre).
 * Los dos llevan a la página del formulario (Ajustes > PlayaEscapes) con los datos ya llenos (paso 2).
 * Cualquier botón o enlace al formulario (Reservar ahora, Me interesa la oferta…) llena el widget
 * si la página lo tiene; si no, abre la ventana. Sin JavaScript, llevan directo al formulario.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Textos que se pueden traducir en "Textos en inglés" (grupo "Ventana de reserva").
function pe_ventana_textos() {
	return array(
		'Solicita tu reservación',
		'Llegada',
		'Salida',
		'Huéspedes',
		'Mayor de 12 años',
		'De 0 a 12 años',
		'adulto',
		'adultos',
		'niño',
		'niños',
		'habitación',
		'habitaciones',
		'Restar',
		'Sumar',
		'Confirmar',
		'Continuar',
		'Ver disponibilidad',
		'Cerrar',
		'Sin costo adicional',
		'Respuesta en 24 hrs',
		'Especialistas Sandos',
	);
}

function pe_ventana_registrar_textos() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}
	foreach ( pe_ventana_textos() as $texto ) {
		pll_register_string( 'Ventana de reserva', $texto, 'PlayaEscapes' );
	}
}
add_action( 'init', 'pe_ventana_registrar_textos' );

// Resumen de huéspedes: "2 adultos / 1 habitación" (los niños solo si hay).
function pe_reserva_resumen( $adultos, $ninos, $habitaciones ) {
	$texto = $adultos . ' ' . pe_t( 1 === $adultos ? 'adulto' : 'adultos' );
	if ( $ninos > 0 ) {
		$texto .= ', ' . $ninos . ' ' . pe_t( 1 === $ninos ? 'niño' : 'niños' );
	}
	return $texto . ' / ' . $habitaciones . ' ' . pe_t( 1 === $habitaciones ? 'habitación' : 'habitaciones' );
}

// Textos que usa el JavaScript, como atributos del formulario.
function pe_reserva_atributos_textos() {
	$textos = array(
		'adulto'       => pe_t( 'adulto' ),
		'adultos'      => pe_t( 'adultos' ),
		'nino'         => pe_t( 'niño' ),
		'ninos'        => pe_t( 'niños' ),
		'habitacion'   => pe_t( 'habitación' ),
		'habitaciones' => pe_t( 'habitaciones' ),
		'error-salida' => pe_t( 'La fecha de salida debe ser posterior a la de llegada.' ),
	);
	$html = '';
	foreach ( $textos as $clave => $valor ) {
		$html .= ' data-' . $clave . '="' . esc_attr( $valor ) . '"';
	}
	return $html;
}

// Un contador con botones − y + (Adultos, Niños, Habitaciones).
function pe_ventana_contador( $campo, $etiqueta, $nota, $valor, $minimo, $maximo ) {
	?>
	<div class="pe-contador" data-campo="<?php echo esc_attr( $campo ); ?>" data-min="<?php echo esc_attr( $minimo ); ?>" data-max="<?php echo esc_attr( $maximo ); ?>">
		<div class="pe-contador__texto">
			<span class="pe-contador__nombre"><?php echo esc_html( $etiqueta ); ?></span>
			<?php if ( '' !== $nota ) : ?>
				<span class="pe-contador__nota"><?php echo esc_html( $nota ); ?></span>
			<?php endif; ?>
		</div>
		<div class="pe-contador__controles">
			<button type="button" data-paso="-1" aria-label="<?php echo esc_attr( pe_t( 'Restar' ) . ': ' . $etiqueta ); ?>">&minus;</button>
			<output aria-live="polite"><?php echo esc_html( $valor ); ?></output>
			<button type="button" data-paso="1" aria-label="<?php echo esc_attr( pe_t( 'Sumar' ) . ': ' . $etiqueta ); ?>">+</button>
		</div>
		<input type="hidden" name="<?php echo esc_attr( $campo ); ?>" value="<?php echo esc_attr( $valor ); ?>">
	</div>
	<?php
}

// Contadores y botón Confirmar del panel de huéspedes (mismos límites que el formulario).
function pe_reserva_contadores() {
	pe_ventana_contador( 'adultos', pe_t( 'Adultos' ), pe_t( 'Mayor de 12 años' ), 2, 1, 20 );
	pe_ventana_contador( 'ninos', pe_t( 'Niños' ), pe_t( 'De 0 a 12 años' ), 0, 0, 10 );
	pe_ventana_contador( 'habitaciones', pe_t( 'Habitaciones' ), '', 1, 1, 10 );
	?>
	<button type="button" class="pe-ventana__confirmar" data-pe-confirmar><?php echo esc_html( pe_t( 'Confirmar' ) ); ?></button>
	<?php
}

// Opciones de la lista de hoteles.
function pe_reserva_opciones_hotel() {
	?>
	<option value=""><?php echo esc_html( pe_t( 'Selecciona un hotel' ) ); ?></option>
	<?php foreach ( pe_lista_hoteles() as $hotel ) : ?>
		<option value="<?php echo esc_attr( $hotel ); ?>"><?php echo esc_html( function_exists( 'pe_etiqueta_hotel' ) ? pe_etiqueta_hotel( $hotel ) : $hotel ); ?></option>
	<?php endforeach; ?>
	<?php
}

// ===== Widget de reserva (barra del diseño de Figma) =====
// Se coloca desde el tema: pe_widget_reserva().
function pe_widget_reserva() {
	if ( ! function_exists( 'pe_url_formulario' ) ) {
		return;
	}
	$hoy = current_time( 'Y-m-d' );
	?>
	<div class="pe-widget" data-pe-widget>
		<div class="pe-widget__caja">
			<form class="pe-barra pe-reserva-form" method="get" action="<?php echo esc_url( pe_url_formulario() ); ?>"<?php echo pe_reserva_atributos_textos(); // Ya escapado. ?>>
				<input type="hidden" name="pe_reserva_promo" value="">

				<div class="pe-barra__campo pe-barra__campo--hotel">
					<label for="pe_w_hotel" class="pe-sr"><?php echo esc_html( pe_t( 'Hotel' ) ); ?></label>
					<select id="pe_w_hotel" name="pe_reserva_hotel" required data-pe-hotel>
						<?php pe_reserva_opciones_hotel(); ?>
					</select>
					<svg class="pe-barra__flecha" viewBox="0 0 20 20" aria-hidden="true"><path d="M1 6l9 9 9-9" fill="none" stroke="currentColor" stroke-width="2.07" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</div>

				<div class="pe-barra__campo pe-barra__campo--fechas">
					<svg class="pe-barra__icono" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
					<label for="pe_w_llegada" class="pe-sr"><?php echo esc_html( pe_t( 'Llegada' ) ); ?></label>
					<input type="date" id="pe_w_llegada" name="llegada" min="<?php echo esc_attr( $hoy ); ?>" required data-pe-llegada>
					<span class="pe-barra__guion" aria-hidden="true"></span>
					<label for="pe_w_salida" class="pe-sr"><?php echo esc_html( pe_t( 'Salida' ) ); ?></label>
					<input type="date" id="pe_w_salida" name="salida" min="<?php echo esc_attr( $hoy ); ?>" required data-pe-salida>
				</div>

				<div class="pe-barra__campo pe-barra__campo--huespedes">
					<span class="pe-sr" id="pe_w_huespedes_rotulo"><?php echo esc_html( pe_t( 'Huéspedes' ) ); ?></span>
					<button type="button" class="pe-barra__huespedes-boton" aria-expanded="false" aria-controls="pe_w_huespedes" aria-describedby="pe_w_huespedes_rotulo" data-pe-huespedes-boton>
						<svg class="pe-barra__icono" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
						<span data-pe-resumen><?php echo esc_html( pe_reserva_resumen( 2, 0, 1 ) ); ?></span>
					</button>
					<div id="pe_w_huespedes" class="pe-barra__panel" hidden data-pe-huespedes-panel>
						<?php pe_reserva_contadores(); ?>
					</div>
				</div>

				<button type="submit" class="pe-barra__enviar"><?php echo esc_html( pe_t( 'Ver disponibilidad' ) ); ?></button>
			</form>
		</div>
	</div>
	<?php
}

// ===== Ventana "Solicita tu reservación" (oculta, al final de cada página) =====
function pe_ventana_reserva() {
	if ( is_admin() || ! function_exists( 'pe_url_formulario' ) ) {
		return;
	}

	$hoy = current_time( 'Y-m-d' );
	?>
	<div id="pe-reserva-ventana" class="pe-ventana" hidden>
		<div class="pe-ventana__caja" role="dialog" aria-modal="true" aria-labelledby="pe-ventana-titulo" tabindex="-1">
			<button type="button" class="pe-ventana__cerrar" data-pe-cerrar aria-label="<?php echo esc_attr( pe_t( 'Cerrar' ) ); ?>">&times;</button>
			<p class="pe-ventana__etiqueta">Sandos Hotels &amp; Resorts</p>
			<h2 id="pe-ventana-titulo" class="pe-ventana__titulo"><?php echo esc_html( pe_t( 'Solicita tu reservación' ) ); ?></h2>

			<form class="pe-ventana__barra pe-reserva-form" method="get" action="<?php echo esc_url( pe_url_formulario() ); ?>"<?php echo pe_reserva_atributos_textos(); // Ya escapado. ?>>
				<input type="hidden" name="pe_reserva_promo" value="">

				<div class="pe-ventana__campo pe-ventana__campo--hotel">
					<label for="pe_v_hotel"><?php echo esc_html( pe_t( 'Hotel' ) ); ?></label>
					<select id="pe_v_hotel" name="pe_reserva_hotel" required data-pe-hotel>
						<?php pe_reserva_opciones_hotel(); ?>
					</select>
				</div>

				<div class="pe-ventana__campo">
					<label for="pe_v_llegada"><?php echo esc_html( pe_t( 'Llegada' ) ); ?></label>
					<input type="date" id="pe_v_llegada" name="llegada" min="<?php echo esc_attr( $hoy ); ?>" required data-pe-llegada>
				</div>

				<div class="pe-ventana__campo">
					<label for="pe_v_salida"><?php echo esc_html( pe_t( 'Salida' ) ); ?></label>
					<input type="date" id="pe_v_salida" name="salida" min="<?php echo esc_attr( $hoy ); ?>" required data-pe-salida>
				</div>

				<div class="pe-ventana__campo pe-ventana__campo--huespedes">
					<span class="pe-ventana__rotulo" id="pe_v_huespedes_rotulo"><?php echo esc_html( pe_t( 'Huéspedes' ) ); ?></span>
					<button type="button" id="pe_v_huespedes_boton" class="pe-ventana__huespedes-boton" aria-expanded="false" aria-controls="pe_v_huespedes" aria-describedby="pe_v_huespedes_rotulo" data-pe-huespedes-boton data-pe-resumen><?php echo esc_html( pe_reserva_resumen( 2, 0, 1 ) ); ?></button>
					<div id="pe_v_huespedes" class="pe-ventana__huespedes" hidden data-pe-huespedes-panel>
						<?php pe_reserva_contadores(); ?>
					</div>
				</div>

				<button type="submit" class="pe-ventana__continuar"><?php echo esc_html( pe_t( 'Continuar' ) ); ?> <span aria-hidden="true">&rarr;</span></button>
			</form>

			<p class="pe-ventana__sellos">
				<span>&#10003; <?php echo esc_html( pe_t( 'Sin costo adicional' ) ); ?></span>
				<span>&#10003; <?php echo esc_html( pe_t( 'Respuesta en 24 hrs' ) ); ?></span>
				<span>&#10003; <?php echo esc_html( pe_t( 'Especialistas Sandos' ) ); ?></span>
			</p>
		</div>
	</div>

	<script>
	(function () {
		var ventana = document.getElementById('pe-reserva-ventana');
		if (!ventana) { return; }

		// ---- Comportamiento de cada formulario (widget y ventana) ----
		function prepararFormulario(form) {
			var llegada = form.querySelector('[data-pe-llegada]');
			var salida  = form.querySelector('[data-pe-salida]');
			var promo   = form.querySelector('input[name="pe_reserva_promo"]');
			var boton   = form.querySelector('[data-pe-huespedes-boton]');
			var panel   = form.querySelector('[data-pe-huespedes-panel]');
			var resumenEl = form.querySelector('[data-pe-resumen]');
			var t = form.dataset;

			// Fechas: la salida debe ser posterior a la llegada.
			function siguienteDia(fecha) {
				var p = fecha.split('-');
				var d = new Date(+p[0], +p[1] - 1, +p[2] + 1);
				return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
			}
			function revisarFechas() {
				salida.setCustomValidity('');
				if (llegada.value) {
					salida.min = siguienteDia(llegada.value);
					if (salida.value && salida.value <= llegada.value) {
						salida.setCustomValidity(t.errorSalida);
					}
				}
			}
			llegada.addEventListener('change', revisarFechas);
			salida.addEventListener('change', revisarFechas);

			// Huéspedes.
			function valor(campo) {
				return parseInt(form.querySelector('input[name="' + campo + '"]').value, 10) || 0;
			}
			function resumen() {
				var a = valor('adultos'), n = valor('ninos'), h = valor('habitaciones');
				var texto = a + ' ' + (a === 1 ? t.adulto : t.adultos);
				if (n > 0) { texto += ', ' + n + ' ' + (n === 1 ? t.nino : t.ninos); }
				resumenEl.textContent = texto + ' / ' + h + ' ' + (h === 1 ? t.habitacion : t.habitaciones);
			}
			function pintar(c) {
				var v = valor(c.dataset.campo);
				c.querySelector('output').textContent = v;
				c.querySelector('[data-paso="-1"]').disabled = v <= +c.dataset.min;
				c.querySelector('[data-paso="1"]').disabled = v >= +c.dataset.max;
			}
			Array.prototype.forEach.call(panel.querySelectorAll('.pe-contador'), function (c) {
				pintar(c);
				c.addEventListener('click', function (e) {
					var b = e.target.closest('[data-paso]');
					if (!b) { return; }
					var v = Math.min(+c.dataset.max, Math.max(+c.dataset.min, valor(c.dataset.campo) + (+b.dataset.paso)));
					form.querySelector('input[name="' + c.dataset.campo + '"]').value = v;
					pintar(c);
					resumen();
				});
			});

			function verHuespedes(mostrar) {
				panel.hidden = !mostrar;
				boton.setAttribute('aria-expanded', mostrar ? 'true' : 'false');
			}
			boton.addEventListener('click', function () { verHuespedes(panel.hidden); });
			panel.querySelector('[data-pe-confirmar]').addEventListener('click', function () {
				verHuespedes(false);
				boton.focus();
			});
			document.addEventListener('click', function (e) {
				if (!panel.hidden && !panel.contains(e.target) && !boton.contains(e.target)) { verHuespedes(false); }
			});

			// La promoción solo viaja si existe.
			form.addEventListener('submit', function () { promo.disabled = !promo.value; });
			window.addEventListener('pageshow', function () { promo.disabled = false; });

			form.peCerrarHuespedes = function () {
				if (panel.hidden) { return false; }
				verHuespedes(false);
				boton.focus();
				return true;
			};
			form.peLlenar = function (hotelElegido, promocion) {
				var hotel = form.querySelector('[data-pe-hotel]');
				if (hotelElegido) {
					for (var i = 0; i < hotel.options.length; i++) {
						if (hotel.options[i].value === hotelElegido) { hotel.selectedIndex = i; }
					}
				}
				promo.value = promocion || '';
			};
		}

		var formularios = document.querySelectorAll('form.pe-reserva-form');
		Array.prototype.forEach.call(formularios, prepararFormulario);

		var formVentana = ventana.querySelector('form');
		var widget      = document.querySelector('[data-pe-widget]');
		var formWidget  = widget ? widget.querySelector('form') : null;
		var ultimoFoco  = null;

		// ---- Abrir y cerrar la ventana ----
		function abrir(hotelElegido, promocion) {
			formVentana.peLlenar(hotelElegido, promocion);
			ultimoFoco = document.activeElement;
			ventana.hidden = false;
			document.documentElement.classList.add('pe-ventana-abierta');
			ventana.querySelector('.pe-ventana__caja').focus();
		}
		function cerrar() {
			formVentana.peCerrarHuespedes();
			ventana.hidden = true;
			document.documentElement.classList.remove('pe-ventana-abierta');
			if (ultimoFoco && ultimoFoco.focus) { ultimoFoco.focus(); }
		}
		ventana.addEventListener('click', function (e) {
			if (e.target === ventana || e.target.closest('[data-pe-cerrar]')) { cerrar(); }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') { return; }
			if (formWidget && formWidget.peCerrarHuespedes()) { return; }
			if (ventana.hidden) { return; }
			if (!formVentana.peCerrarHuespedes()) { cerrar(); }
		});

		// ---- Llevar al widget (cuando la página lo tiene) ----
		function irAlWidget(hotelElegido, promocion) {
			formWidget.peLlenar(hotelElegido, promocion);
			var hotel = formWidget.querySelector('[data-pe-hotel]');
			var campo = hotel.value ? formWidget.querySelector('[data-pe-llegada]') : hotel;
			if (getComputedStyle(widget.querySelector('.pe-widget__caja')).position !== 'fixed') {
				widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
			campo.focus({ preventScroll: true });
		}

		// Cualquier enlace a la página del formulario llena el widget o abre la ventana
		// (botón Reservar del encabezado, Me interesa la oferta, [pe_boton_reservar], etc.).
		function limpia(url) { return String(url).split('#')[0].split('?')[0].replace(/\/+$/, ''); }
		var destino = limpia(formVentana.action);
		// En la propia página del formulario no se hace nada.
		var enFormulario = !!document.querySelector('.pe-formulario input[name="action"][value="pe_enviar_lead"]');

		document.addEventListener('click', function (e) {
			if (enFormulario || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) { return; }
			var a = e.target.closest ? e.target.closest('a[href]') : null;
			if (!a || a.target === '_blank' || limpia(a.href) !== destino) { return; }
			e.preventDefault();
			var params = new URLSearchParams(a.search);
			if (formWidget) {
				irAlWidget(params.get('pe_reserva_hotel'), params.get('pe_reserva_promo'));
			} else {
				abrir(params.get('pe_reserva_hotel'), params.get('pe_reserva_promo'));
			}
		});

		// ---- Widget pegado arriba al bajar con el scroll ----
		if (widget && 'IntersectionObserver' in window) {
			var encabezado = document.querySelector('.pe-header');
			function alturaArriba() {
				var arriba = 0;
				var barraAdmin = document.getElementById('wpadminbar');
				if (barraAdmin && getComputedStyle(barraAdmin).position === 'fixed') { arriba = barraAdmin.offsetHeight; }
				if (encabezado) {
					var pos = getComputedStyle(encabezado).position;
					if (pos === 'fixed' || pos === 'sticky') { arriba = Math.max(arriba, encabezado.getBoundingClientRect().bottom); }
				}
				return arriba + 12;
			}
			function ajustarArriba() { widget.style.setProperty('--pe-widget-top', alturaArriba() + 'px'); }
			ajustarArriba();
			window.addEventListener('resize', ajustarArriba);

			new IntersectionObserver(function (entradas) {
				var e = entradas[0];
				widget.classList.toggle('pe-widget--fijo', !e.isIntersecting && e.boundingClientRect.top < 0);
				ajustarArriba();
			}).observe(widget);
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'pe_ventana_reserva', 20 );