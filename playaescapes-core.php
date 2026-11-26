<?php
/**
 * Plugin Name: PlayaEscapes Core
 * Description: Funcionalidad de PlayaEscapes (promociones, hoteles, formulario y leads).
 * Version: 0.9.0
 * Text Domain: playaescapes-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Módulos del plugin en el folder "includes".
require_once plugin_dir_path( __FILE__ ) . 'includes/datos.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/idiomas.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/idiomas-enlaces.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajustes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ayudas-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/portadas.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/hot-escapes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/hoteles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/textos-ingles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/leads.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/leads-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/escritorio.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/correo.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/formulario.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/contacto.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/utm.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/whatsapp.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/reserva-ventana.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/certificado.php';