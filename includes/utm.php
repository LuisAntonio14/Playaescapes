<?php
/**
 * Módulo UTM: guarda las UTM 30 días en una cookie y las pasa al formulario.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pe_script_utm() {
	?>
	<script>
	(function () {
		var claves = ['utm_source', 'utm_medium', 'utm_campaign'];
		var dias = 30;

		function leerCookie() {
			var m = document.cookie.match(/(?:^|; )pe_utm=([^;]*)/);
			if (!m) { return {}; }
			try { return JSON.parse(decodeURIComponent(m[1])) || {}; } catch (e) { return {}; }
		}

		function guardarCookie(datos) {
			var caduca = new Date(Date.now() + dias * 24 * 60 * 60 * 1000).toUTCString();
			var segura = location.protocol === 'https:' ? '; Secure' : '';
			document.cookie = 'pe_utm=' + encodeURIComponent(JSON.stringify(datos)) +
				'; expires=' + caduca + '; path=/; SameSite=Lax' + segura;
		}

		// 1) Si la dirección trae UTM, se guardan (reemplazan las anteriores).
		var params = new URLSearchParams(window.location.search);
		var nuevas = {};
		var hay = false;
		claves.forEach(function (c) {
			var v = params.get(c);
			if (v) { nuevas[c] = v.substring(0, 100); hay = true; }
		});
		if (hay) { guardarCookie(nuevas); }

		// 2) Rellena los campos ocultos del formulario con lo guardado.
		var guardadas = hay ? nuevas : leerCookie();
		document.addEventListener('DOMContentLoaded', function () {
			document.querySelectorAll('.pe-formulario').forEach(function (form) {
				claves.forEach(function (c) {
					var campo = form.querySelector('input[name="' + c + '"]');
					if (campo && !campo.value && guardadas[c]) {
						campo.value = guardadas[c];
					}
				});
			});
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'pe_script_utm' );