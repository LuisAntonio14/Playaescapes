<?php
/**
 * Módulo Agencia certificada: ventana emergente que se abre al dar clic en el sello
 * "Agencia certificada por Sandos" de la portada del Inicio.
 *
 * Solución TEMPORAL acordada mientras Marketing y Emergia crean la página de
 * certificación en sandos.com. Todo se edita en Ajustes > PlayaEscapes:
 * título, mensaje, texto del botón y URL de la página de sandos.com.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Textos del diseño que se usan si el campo de Ajustes está vacío. POR CONFIRMAR con Marketing.
function pe_certificado_textos_defecto() {
	return array(
		'titulo'  => 'PlayaEscapes, agencia certificada por Sandos Hotels & Resorts',
		'mensaje' => 'Somos una agencia especializada en Sandos Hotels & Resorts en Cancún, Riviera Maya y Los Cabos. Reserva con un especialista, sin costo adicional, y recibe respuesta en menos de 24 horas.',
		'boton'   => 'Ver certificado en sandos.com',
		'cerrar'  => 'Continuar en PlayaEscapes',
	);
}

// Registra los textos en Polylang (Languages > Translations, grupo PlayaEscapes).
function pe_certificado_registrar_textos() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}
	foreach ( pe_certificado_textos_defecto() as $texto ) {
		pll_register_string( 'Agencia certificada', $texto, 'PlayaEscapes', strlen( $texto ) > 80 );
	}
	// Textos escritos en Ajustes.
	foreach ( array( 'certificado_titulo', 'certificado_mensaje', 'certificado_boton' ) as $clave ) {
		$valor = pe_ajuste( $clave );
		if ( '' !== $valor ) {
			pll_register_string( 'Ajustes: agencia certificada', $valor, 'PlayaEscapes', strlen( $valor ) > 80 );
		}
	}
}
add_action( 'init', 'pe_certificado_registrar_textos' );

// Devuelve el texto de Ajustes (traducido) o, si está vacío, el del diseño.
function pe_certificado_texto( $clave ) {
	$defecto = pe_certificado_textos_defecto();
	$valor   = 'cerrar' === $clave ? '' : pe_ajuste( 'certificado_' . $clave );
	return pe_t( '' !== $valor ? $valor : $defecto[ $clave ] );
}

// Imprime la ventana (oculta) al final de las páginas que tienen el sello.
function pe_certificado_ventana() {
	if ( is_admin() || ! is_front_page() ) {
		return;
	}

	$url  = pe_ajuste( 'certificado_url' );
	$logo = get_template_directory_uri() . '/assets/img/logo-sandos-blanco.png';
	?>
	<div id="pe-certificado" class="pe-cert" hidden>
		<div class="pe-cert__caja" role="dialog" aria-modal="true" aria-labelledby="pe-cert-titulo" tabindex="-1">
			<button type="button" class="pe-cert__cerrar" data-pe-cert-cerrar aria-label="<?php echo esc_attr( pe_t( 'Cerrar' ) ); ?>">&times;</button>

			<div class="pe-cert__banda">
				<img src="<?php echo esc_url( $logo ); ?>" alt="Sandos Hotels &amp; Resorts" width="160" height="44">
			</div>

			<div class="pe-cert__cuerpo">
				<h2 id="pe-cert-titulo" class="pe-cert__titulo"><?php echo esc_html( pe_certificado_texto( 'titulo' ) ); ?></h2>
				<div class="pe-cert__mensaje"><?php echo wpautop( esc_html( pe_certificado_texto( 'mensaje' ) ) ); ?></div>

				<div class="pe-cert__botones">
					<button type="button" class="pe-cert__boton pe-cert__boton--principal" data-pe-cert-cerrar><?php echo esc_html( pe_certificado_texto( 'cerrar' ) ); ?></button>
					<?php if ( '' !== $url ) : ?>
						<a class="pe-cert__boton" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( pe_certificado_texto( 'boton' ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<script>
	(function () {
		var ventana = document.getElementById('pe-certificado');
		if (!ventana) { return; }
		var caja = ventana.querySelector('.pe-cert__caja');
		var ultimoFoco = null;

		function abrir() {
			ultimoFoco = document.activeElement;
			ventana.hidden = false;
			document.documentElement.classList.add('pe-ventana-abierta');
			caja.focus();
		}
		function cerrar() {
			ventana.hidden = true;
			document.documentElement.classList.remove('pe-ventana-abierta');
			if (ultimoFoco && ultimoFoco.focus) { ultimoFoco.focus(); }
		}

		document.addEventListener('click', function (e) {
			if (e.target.closest && e.target.closest('[data-pe-certificado]')) {
				e.preventDefault();
				abrir();
			}
		});
		ventana.addEventListener('click', function (e) {
			if (e.target === ventana || e.target.closest('[data-pe-cert-cerrar]')) { cerrar(); }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !ventana.hidden) { cerrar(); }
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'pe_certificado_ventana', 20 );