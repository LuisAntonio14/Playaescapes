<?php
/**
 * Plugin Name: PlayaEscapes Core
 * Description: Funcionalidad de PlayaEscapes (promociones, hoteles, formulario y leads).
 * Version: 0.5.0
 * Text Domain: playaescapes-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Cada módulo vive en su propio archivo dentro de /includes.
require_once plugin_dir_path( __FILE__ ) . 'includes/datos.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/hot-escapes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/hoteles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/leads.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/correo.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/formulario.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/utm.php';