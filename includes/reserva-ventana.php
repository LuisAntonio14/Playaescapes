<?php
/**
 * Módulo Ventana de reserva (paso 1 de la solicitud de reserva).
 *
 * Ventana "Solicita tu reservación" con hotel, llegada, salida y huéspedes,
 * basada en el prototipo de Vercel (acordado en la reunión del 17 de noviembre).
 * Se abre con cualquier botón o enlace que lleve a la página del formulario
 * (Ajustes > PlayaEscapes > Página del formulario de reserva). Al dar clic en
 * "Continuar", lleva a esa página con los datos ya llenos (paso 2).
 * Sin JavaScript, los botones llevan directo al formulario, como antes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Textos de la ventana que se pueden traducir en Languages > Translations.
function pe_ventana_textos() {
	return array(
		'Solicita tu reservación',
		'Llegada',
		'Salida',
		'Huéspedes',
		'Mayor de 12 años',
		'De 0 a 12 años',
		'Adulto',
		'Niño',
		'Hab.',
		'Restar',
		'Sumar',
		'Confirmar',
		'Continuar',
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

// Imprime la ventana (oculta) al final de cada página del sitio público.
function pe_ventana_reserva() {
	if ( is_admin() || ! function_exists( 'pe_url_formulario' ) ) {
		return;
	}

	$hoy     = current_time( 'Y-m-d' );
	$resumen = sprintf( '2 %s, 0 %s, 1 %s', pe_t( 'Adultos' ), pe_t( 'Niños' ), pe_t( 'Hab.' ) );
	?>
	<div id="pe-reserva-ventana" class="pe-ventana" hidden>
		<div class="pe-ventana__caja" role="dialog" aria-modal="true" aria-labelledby="pe-ventana-titulo">
			<button type="button" class="pe-ventana__cerrar" data-pe-cerrar aria-label="<?php echo esc_attr( pe_t( 'Cerrar' ) ); ?>">&times;</button>
			<p class="pe-ventana__etiqueta">Sandos Hotels &amp; Resorts</p>
			<h2 id="pe-ventana-titulo" class="pe-ventana__titulo"><?php echo esc_html( pe_t( 'Solicita tu reservación' ) ); ?></h2>

			<form class="pe-ventana__barra" method="get" action="<?php echo esc_url( pe_url_formulario() ); ?>">
				<input type="hidden" name="pe_reserva_promo" value="">

				<div class="pe-ventana__campo pe-ventana__campo--hotel">
					<label for="pe_v_hotel"><?php echo esc_html( pe_t( 'Hotel' ) ); ?></label>
					<select id="pe_v_hotel" name="pe_reserva_hotel" required>
						<option value=""><?php echo esc_html( pe_t( 'Selecciona un hotel' ) ); ?></option>
						<?php foreach ( pe_lista_hoteles() as $hotel ) : ?>
							<option value="<?php echo esc_attr( $hotel ); ?>"><?php echo esc_html( $hotel ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pe-ventana__campo">
					<label for="pe_v_llegada"><?php echo esc_html( pe_t( 'Llegada' ) ); ?></label>
					<input type="date" id="pe_v_llegada" name="llegada" min="<?php echo esc_attr( $hoy ); ?>" required>
				</div>

				<div class="pe-ventana__campo">
					<label for="pe_v_salida"><?php echo esc_html( pe_t( 'Salida' ) ); ?></label>
					<input type="date" id="pe_v_salida" name="salida" min="<?php echo esc_attr( $hoy ); ?>" required>
				</div>

				<div class="pe-ventana__campo pe-ventana__campo--huespedes">
					<span class="pe-ventana__rotulo" id="pe_v_huespedes_rotulo"><?php echo esc_html( pe_t( 'Huéspedes' ) ); ?></span>
					<button type="button" id="pe_v_huespedes_boton" class="pe-ventana__huespedes-boton" aria-expanded="false" aria-controls="pe_v_huespedes" aria-describedby="pe_v_huespedes_rotulo"><?php echo esc_html( $resumen ); ?></button>
					<div id="pe_v_huespedes" class="pe-ventana__huespedes" hidden
						data-adulto="<?php echo esc_attr( pe_t( 'Adulto' ) ); ?>"
						data-adultos="<?php echo esc_attr( pe_t( 'Adultos' ) ); ?>"
						data-nino="<?php echo esc_attr( pe_t( 'Niño' ) ); ?>"
						data-ninos="<?php echo esc_attr( pe_t( 'Niños' ) ); ?>"
						data-hab="<?php echo esc_attr( pe_t( 'Hab.' ) ); ?>"
						data-error-salida="<?php echo esc_attr( pe_t( 'La fecha de salida debe ser posterior a la de llegada.' ) ); ?>">
						<?php
						// Mismos límites que el formulario de reserva.
						pe_ventana_contador( 'adultos', pe_t( 'Adultos' ), pe_t( 'Mayor de 12 años' ), 2, 1, 20 );
						pe_ventana_contador( 'ninos', pe_t( 'Niños' ), pe_t( 'De 0 a 12 años' ), 0, 0, 10 );
						pe_ventana_contador( 'habitaciones', pe_t( 'Habitaciones' ), '', 1, 1, 10 );
						?>
						<button type="button" class="pe-ventana__confirmar" data-pe-confirmar><?php echo esc_html( pe_t( 'Confirmar' ) ); ?></button>
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

		var form    = ventana.querySelector('form');
		var hotel   = document.getElementById('pe_v_hotel');
		var llegada = document.getElementById('pe_v_llegada');
		var salida  = document.getElementById('pe_v_salida');
		var promo   = form.querySelector('input[name="pe_reserva_promo"]');
		var boton   = document.getElementById('pe_v_huespedes_boton');
		var panel   = document.getElementById('pe_v_huespedes');
		var textos  = panel.dataset;
		var ultimoFoco = null;

		// Dirección de la página del formulario, sin parámetros, para reconocer sus enlaces.
		function limpia(url) { return String(url).split('#')[0].split('?')[0].replace(/\/+$/, ''); }
		var destino = limpia(form.action);

		// En la propia página del formulario no se abre la ventana.
		var enFormulario = !!document.querySelector('.pe-formulario input[name="action"][value="pe_enviar_lead"]');

		// ---- Fechas ----
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
					salida.setCustomValidity(textos.errorSalida);
				}
			}
		}
		llegada.addEventListener('change', revisarFechas);
		salida.addEventListener('change', revisarFechas);

		// ---- Huéspedes ----
		function valor(campo) {
			return parseInt(form.querySelector('input[name="' + campo + '"]').value, 10) || 0;
		}
		function resumen() {
			var a = valor('adultos'), n = valor('ninos'), h = valor('habitaciones');
			boton.textContent = a + ' ' + (a === 1 ? textos.adulto : textos.adultos) + ', ' +
				n + ' ' + (n === 1 ? textos.nino : textos.ninos) + ', ' +
				h + ' ' + textos.hab;
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
			if (!panel.hidden && !panel.contains(e.target) && e.target !== boton) { verHuespedes(false); }
		});

		// ---- Abrir y cerrar la ventana ----
		function abrir(hotelElegido, promocion) {
			if (hotelElegido) {
				for (var i = 0; i < hotel.options.length; i++) {
					if (hotel.options[i].value === hotelElegido) { hotel.selectedIndex = i; }
				}
			}
			promo.value = promocion || '';
			ultimoFoco = document.activeElement;
			ventana.hidden = false;
			document.documentElement.classList.add('pe-ventana-abierta');
			(hotel.value ? llegada : hotel).focus();
		}
		function cerrar() {
			verHuespedes(false);
			ventana.hidden = true;
			document.documentElement.classList.remove('pe-ventana-abierta');
			if (ultimoFoco && ultimoFoco.focus) { ultimoFoco.focus(); }
		}
		ventana.addEventListener('click', function (e) {
			if (e.target === ventana || e.target.closest('[data-pe-cerrar]')) { cerrar(); }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape' || ventana.hidden) { return; }
			if (!panel.hidden) { verHuespedes(false); boton.focus(); } else { cerrar(); }
		});

		// Cualquier enlace a la página del formulario abre la ventana
		// (botón Reservar del encabezado, [pe_boton_reservar], etc.).
		document.addEventListener('click', function (e) {
			if (enFormulario || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) { return; }
			var a = e.target.closest ? e.target.closest('a[href]') : null;
			if (!a || a.target === '_blank' || limpia(a.href) !== destino) { return; }
			e.preventDefault();
			var params = new URLSearchParams(a.search);
			abrir(params.get('pe_reserva_hotel'), params.get('pe_reserva_promo'));
		});

		// La promoción solo viaja si existe.
		form.addEventListener('submit', function () { promo.disabled = !promo.value; });
		window.addEventListener('pageshow', function () { promo.disabled = false; });
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'pe_ventana_reserva', 20 );