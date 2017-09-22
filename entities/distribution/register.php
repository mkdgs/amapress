<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_distribution' );
function amapress_register_entities_distribution( $entities ) {
	$entities['distribution'] = array(
		'singular'         => amapress__( 'Distribution hebdomadaire' ),
		'plural'           => amapress__( 'Distributions hebdomadaires' ),
		'public'           => true,
//                'logged_or_public' => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'title_format'     => 'amapress_distribution_title_formatter',
		'slug_format'      => 'from_title',
		'slug'             => amapress__( 'distributions' ),
		'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'dashicons-store',
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_distribution_views',
		),
		'default_orderby'  => 'amapress_distribution_date',
		'default_order'    => 'ASC',
		'fields'           => array(
			'info'              => array(
				'name'  => amapress__( 'Informations spécifiques' ),
				'type'  => 'editor',
				'group' => 'Informations',
				'desc'  => 'Informations complémentaires',
			),
			'date'              => array(
				'name'       => amapress__( 'Date de distribution' ),
				'type'       => 'date',
				'time'       => true,
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'group'      => 'Livraison',
				'readonly'   => true,
				'desc'       => 'Date de distribution',
			),
			'lieu'              => array(
				'name'       => amapress__( 'Lieu de distribution' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => 'Livraison',
				'top_filter' => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Toutes les lieux',
				),
				'readonly'   => true,
				'desc'       => 'Lieu de distribution',
				'searchable' => true,
			),
			'lieu_substitution' => array(
				'name'       => amapress__( 'Lieu de substitution' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => 'Livraison',
				'desc'       => 'Lieu de substitution',
				'searchable' => true,
			),
			'nb_resp_supp'      => array(
				'name'     => amapress__( 'Nombre de responsables de distributions supplémentaires' ),
				'type'     => 'number',
				'required' => true,
				'desc'     => 'Nombre de responsables de distributions supplémentaires',
				'group'    => 'Gestion',
				'default'  => 0,
			),
			'contrats'          => array(
				'name'      => amapress__( 'Contrats' ),
				'type'      => 'multicheck-posts',
				'post_type' => 'amps_contrat_inst',
				'group'     => 'Gestion',
				'readonly'  => true,
				'desc'      => 'Contrats',
//                'searchable' => true,
			),
			'responsables'      => array(
				'name'         => amapress__( 'Responsables' ),
				'group'        => 'Livraison',
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Responsables',
//                'searchable' => true,
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_distribution', 'amapress_can_delete_distribution', 10, 2 );
function amapress_can_delete_distribution( $can, $post_id ) {
	return false;
}

function amapress_get_active_contrat_month_options( $args ) {
	$months    = array();
	$min_month = amapress_time();
	$max_month = amapress_time();
	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
		$min_month = $contrat->getDate_debut() < $min_month ? $contrat->getDate_debut() : $min_month;
		$max_month = $contrat->getDate_fin() > $max_month ? $contrat->getDate_fin() : $max_month;
	}
	$min_month = Amapress::start_of_month( $min_month );
	$max_month = Amapress::end_of_month( $max_month );
	$month     = $min_month;
	while ( $month <= $max_month ) {
		$months[ date_i18n( 'Y-m', $month ) ] = date_i18n( 'F Y', $month );
		$month                                = Amapress::add_a_month( $month );
	}

	return $months;
}