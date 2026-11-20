<?php
/**
 * Módulo Roles: quién puede ver Leads y Consultas.
 *
 * - Crea el rol "Ventas PlayaEscapes": lo mismo que Editor, más ver y exportar Leads y Consultas.
 * - Leads y Consultas solo los ven quienes tienen el permiso "pe_ver_leads":
 *   el rol Ventas PlayaEscapes y los administradores. Los Editores ya no los ven.
 * Acordado con Jonathan (TI): los permisos se manejan por rol, no por usuario.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Si se cambia algo de los roles, subir este número para que se actualicen.
define( 'PE_ROLES_VERSION', '1' );

// Crea o actualiza el rol de Ventas y da el permiso a los administradores.
function pe_roles_instalar() {
	if ( get_option( 'pe_roles_version' ) === PE_ROLES_VERSION ) {
		return;
	}

	$editor = get_role( 'editor' );
	if ( ! $editor ) {
		return;
	}

	// Se vuelve a crear para que tenga los mismos permisos actuales de Editor.
	remove_role( 'pe_ventas' );
	$permisos                 = $editor->capabilities;
	$permisos['pe_ver_leads'] = true;
	add_role( 'pe_ventas', 'Ventas PlayaEscapes', $permisos );

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'pe_ver_leads' );
	}

	update_option( 'pe_roles_version', PE_ROLES_VERSION );
}
add_action( 'init', 'pe_roles_instalar', 5 );

// Leads y Consultas usan el permiso "pe_ver_leads" en lugar de los permisos de entradas.
function pe_roles_permisos_leads( $args, $tipo ) {
	if ( ! in_array( $tipo, array( 'pe_lead', 'pe_consulta' ), true ) ) {
		return $args;
	}

	$args['map_meta_cap'] = false;
	$args['capabilities'] = array(
		'edit_post'              => 'pe_ver_leads',
		'read_post'              => 'pe_ver_leads',
		'delete_post'            => 'pe_ver_leads',
		'edit_posts'             => 'pe_ver_leads',
		'edit_others_posts'      => 'pe_ver_leads',
		'edit_published_posts'   => 'pe_ver_leads',
		'edit_private_posts'     => 'pe_ver_leads',
		'publish_posts'          => 'pe_ver_leads',
		'read_private_posts'     => 'pe_ver_leads',
		'delete_posts'           => 'pe_ver_leads',
		'delete_others_posts'    => 'pe_ver_leads',
		'delete_published_posts' => 'pe_ver_leads',
		'delete_private_posts'   => 'pe_ver_leads',
		'create_posts'           => 'do_not_allow', // Se crean solo desde los formularios.
	);

	return $args;
}
add_filter( 'register_post_type_args', 'pe_roles_permisos_leads', 10, 2 );