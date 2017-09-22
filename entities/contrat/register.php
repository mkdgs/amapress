<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 13/05/2016
 * Time: 11:14
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_contrat' );
function amapress_register_entities_contrat( $entities ) {
	$entities['contrat']          = array(
		'singular'                => amapress__( 'Présentation web' ),
		'plural'                  => amapress__( 'Présentations web' ),
		'public'                  => true,
		'thumb'                   => true,
		'special_options'         => array(),
		'show_in_menu'            => false,
		'slug'                    => 'contrats',
		'custom_archive_template' => true,
		'menu_icon'               => 'flaticon-note',
		'views'                   => array(
			'remove' => array( 'mine' ),
		),
		'edit_header'             => function ( $post ) {
			echo '<h1>Termes du contrat :</h1>';
		},
		'fields'                  => array(
//			'amapress_icon_id' => array(
//				'name'    => amapress__( 'Icône' ),
//				'type'    => 'upload',
//				'group'   => 'Information',
//				'desc'    => 'Icône',
//				'bare_id' => true,
//			),
//            'presentation' => array(
//                'name' => amapress__('Présentation'),
//                'type' => 'editor',
//                'required' => true,
//                'desc' => 'Présentation',
//            ),
//            'nb_visites' => array(
//                'name' => amapress__('Nombre de visites obligatoires'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => 'Nombre de visites obligatoires',
//            ),
//            'max_adherents' => array(
//                'name' => amapress__('Nombre de maximum d\'adhérents'),
//                'type' => 'number',
//                'required' => true,
//                'desc' => 'Nombre de maximum d\'adhérents',
//            ),
			'producteur'       => array(
				'name'              => amapress__( 'Producteur' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_producteur',
				'required'          => true,
				'desc'              => 'Producteur',
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_producteur',
					'placeholder' => 'Toutes les producteurs',
				),
				'searchable'        => true,
			),
		),
	);
	$entities['contrat_instance'] = array(
		'internal_name'   => 'amps_contrat_inst',
		'singular'        => amapress__( 'Modèle de contrat' ),
		'plural'          => amapress__( 'Modèles de contrat' ),
		'public'          => 'adminonly',
		'show_in_menu'    => false,
		'special_options' => array(),
		'slug'            => 'contrat_instances',
		'title_format'    => 'amapress_contrat_instance_title_formatter',
		'title'           => false,
		'slug_format'     => 'from_title',
		'editor'          => false,
		'menu_icon'       => 'flaticon-interface',
		'row_actions'     => array(
			'renew' => 'Renouveler',
		),
		'labels'          => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajout modèle de contrat',
		),
		'views'           => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_contrat_instance_views',
		),
		'fields'          => array(
			'model'          => array(
				'name'              => amapress__( 'Présentation web' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_contrat',
				'group'             => 'Gestion',
				'required'          => true,
				'desc'              => 'Sélectionner la présentation web. Si elle n’est pas présente dans la liste ci-dessus, la créer ici « <a href="' . admin_url( 'post-new.php?post_type=amps_contrat' ) . '" target="_blank">présentation web</a> »',
				'import_key'        => true,
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_contrat',
					'placeholder' => 'Toutes les présentations web',
				),
				'searchable'        => true,
			),
			'nb_visites'     => array(
				'name'     => amapress__( 'Nombre de visites obligatoires' ),
				'group'    => 'Information',
				'type'     => 'number',
				'required' => true,
				'desc'     => 'Nombre de visites obligatoires chez le producteur',
			),
			'type'           => array(
				'name'        => amapress__( 'Type de contrat' ),
				'type'        => 'select',
				'options'     => array(
					'panier'   => 'Distributions régulières',
					'commande' => 'Commandes',
				),
				'required'    => true,
				'group'       => 'Gestion',
				'desc'        => 'Type de contrat',
				'import_key'  => true,
				'default'     => 'panier',
				'conditional' => array(
					'_default_' => 'panier',
					'panier'    => array(
						'liste_dates'           => array(
							'name'          => amapress__( 'Calendrier des distributions' ),
							'type'          => 'multidate',
							'required'      => true,
							'group'         => 'Distributions',
							'show_column'   => false,
							'desc'          => 'Sélectionner les dates de distribution fournies par le producteur',
							'before_option' =>
								function ( $option ) {
									$val_id = $option->getID() . '-validate';
									echo '<p><input type="checkbox" id="' . $val_id . '" /><label for="' . $val_id . '">Cocher cette case pour modifier les dates lors du renouvellement du contrat. 
<br />Pour annuler ou reporter une distribution déjà planifiée, veuillez modifier la date dans le panier correspondant via le menu Contenus/Paniers</label></p>';
									echo '<script type="text/javascript">
jQuery(function($) {
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $("#' . $val_id . '").change(function() {
        $liste_dates.multiDatesPicker("option", {disabled: !$(this).is(\':checked\')});
    });
    $liste_dates.multiDatesPicker("option", {disabled: true});
});
</script>';
								},
						),
						'panier_variable'       => array(
							'name'     => amapress__( 'Paniers personnalisés' ),
							'type'     => 'checkbox',
							'group'    => 'Gestion',
							'required' => true,
							'desc'     => 'Cocher cette case si les paniers sont spécifiques pour chacun des adhérents',
						),
						'paiements'             => array(
							'name'     => amapress__( 'Nombres de chèques' ),
							'type'     => 'multicheck',
							'desc'     => 'Sélectionner le nombre de règlements autorisés par le producteur',
							'group'    => 'Paiements',
							'required' => true,
							'options'  => array(
								'1'  => '1 chèque',
								'2'  => '2 chèques',
								'3'  => '3 chèques',
								'4'  => '4 chèques',
								'5'  => '5 chèques',
								'6'  => '6 chèques',
								'7'  => '7 chèques',
								'8'  => '8 chèques',
								'9'  => '9 chèques',
								'10' => '10 chèques',
								'11' => '11 chèques',
								'12' => '12 chèques',
							)
						),
						'liste_dates_paiements' => array(
							'name'        => amapress__( 'Calendrier des remises de chèques' ),
							'type'        => 'multidate',
							'required'    => true,
							'group'       => 'Paiements',
							'show_column' => false,
							'desc'        => 'Sélectionner les dates auxquelles le producteur souhaite recevoir les chèques',
						),
//                        'list_quantites' => array(
//                            'name' => amapress__('Quantités'),
//                            'type' => 'show-posts',
//                            'desc' => 'Quantités',
//                            'group' => 'Distributions',
//                            'post_type' => 'amps_contrat_quant',
//                            'parent' => 'amapress_contrat_quantite_contrat_instance',
//                        ),

					),
					'commande'  => array(
						'commande_liste_dates'   => array(
							'name'        => amapress__( 'Calendrier des commandes' ),
							'type'        => 'multidate',
							'group'       => 'Commandes',
							'required'    => true,
							'show_column' => false,
							'desc'        => '',
						),
						'commande_cannot_modify' => array(
							'name'        => amapress__( 'Commandes fermes' ),
							'type'        => 'checkbox',
							'group'       => 'Commandes',
							'required'    => false,
							'show_column' => false,
							'desc'        => '',
						),
						'commande_open_before'   => array(
							'name'        => amapress__( 'Ouverture des commandes' ),
							'type'        => 'number',
							'group'       => 'Commandes',
							'required'    => false,
							'show_column' => false,
							'desc'        => 'Ouverture des commandes x jours avant (0=tout de suite)',
						),
						'commande_close_before'  => array(
							'name'        => amapress__( 'Fermeture des commandes' ),
							'group'       => 'Commandes',
							'type'        => 'number',
							'required'    => false,
							'show_column' => false,
							'desc'        => 'Fermeture des commandes x jours avant',
						),
					),
				)
			),
			'date_debut'     => array(
				'name'          => amapress__( 'Début du contrat' ),
				'type'          => 'date',
				'group'         => 'Gestion',
				'required'      => true,
				'desc'          => 'Date de début du contrat',
				'import_key'    => true,
				'before_option' =>
					function ( $option ) {
						echo '<script type="text/javascript">
jQuery(function($) {
    var $date_debut = $("#' . $option->getID() . '");
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $date_debut.change(function() {
        $liste_dates.multiDatesPicker("option", {minDate: $(this).val()});
    });
    $liste_dates.multiDatesPicker("option", {minDate: $date_debut.val()});
});
</script>';
					},
			),
			'date_fin'       => array(
				'name'          => amapress__( 'Fin du contrat' ),
				'type'          => 'date',
				'group'         => 'Gestion',
				'required'      => true,
				'desc'          => 'Date de fin du contrat',
				'import_key'    => true,
				'before_option' =>
					function ( $option ) {
						echo '<script type="text/javascript">
jQuery(function($) {
    var $date_fin = $("#' . $option->getID() . '");
    var $liste_dates = $("#amapress_contrat_instance_liste_dates-cal");
    $date_fin.on("change", function() {
        $liste_dates.multiDatesPicker("option", {maxDate: $(this).val()});
    });
    $liste_dates.multiDatesPicker("option", {maxDate: $date_fin.val()});
});
</script>';
					},
			),
			'ended'          => array(
				'name'  => amapress__( 'Clôturer' ),
				'type'  => 'checkbox',
				'group' => 'Gestion',
				'desc'  => 'Cocher cette case lorsque le contrat est terminé, penser à le renouveler d\'abord',
			),
			'date_ouverture' => array(
				'name'       => amapress__( 'Ouverture des inscriptions' ),
				'type'       => 'date',
				'group'      => 'Gestion',
				'required'   => true,
				'desc'       => 'Date d\'ouverture des inscriptions en ligne',
				'import_key' => true,
			),
			'date_cloture'   => array(
				'name'       => amapress__( 'Clôture des inscriptions' ),
				'type'       => 'date',
				'group'      => 'Gestion',
				'required'   => true,
				'desc'       => 'Date de clôture des inscriptions en ligne',
				'import_key' => true,
			),
			'lieux'          => array(
				'name'              => amapress__( 'Lieux' ),
				'type'              => 'multicheck-posts',
				'post_type'         => 'amps_lieu',
				'group'             => 'Gestion',
				'required'          => true,
				'desc'              => 'Lieux de distribution',
				'autoselect_single' => true,
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Tous les lieux'
				),
			),
			'status'         => array(
				'name'    => amapress__( 'Statut' ),
				'type'    => 'custom',
				'column'  => array( 'AmapressContrats', "contratStatus" ),
				'group'   => 'Gestion',
				'save'    => null,
				'desc'    => 'Statut',
				'show_on' => 'edit-only',
			),
			'quant_editor'   => array(
				'name'         => amapress__( 'Quantités' ),
				'type'         => 'custom',
				'group'        => 'Gestion',
				'column'       => null,
				'custom'       => 'amapress_get_contrat_quantite_editor',
				'save'         => 'amapress_save_contrat_quantite_editor',
				'show_on_edit' => 'edit-only',
//                'desc' => 'Quantités',
			),
			'max_adherents' => array(
				'name'     => amapress__( 'Nombre maximum d\'amapiens' ),
				'type'     => 'number',
				'group'    => 'Gestion',
				'required' => true,
				'desc'     => 'Nombre maximum d\'amapiens',
			),
			'contrat'       => array(
				'name'       => amapress__( 'Contrat en ligne' ),
				'type'       => 'editor',
//                'required' => true,
				'desc'       => 'Configurer le contenu du Contrat en ligne',
				'wpautop'    => false,
				'searchable' => true,
			),
			'is_principal'  => array(
				'name'     => amapress__( 'Contrat principal' ),
				'type'     => 'checkbox',
				'required' => true,
				'desc'     => 'Contrat principal',
			),
		),
	);
	$entities['contrat_quantite'] = array(
		'internal_name'    => 'amps_contrat_quant',
		'singular'         => amapress__( 'Contrat quantité' ),
		'plural'           => amapress__( 'Contrats quantités' ),
		'public'           => 'adminonly',
		'thumb'            => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'special_options'  => array(),
		'slug'             => 'contrat_quantites',
		'quick_edit'       => false,
		'fields'           => array(
//            'photo' => array(
//                'name' => amapress__('Photo'),
//                'type' => 'upload',
//                'group' => 'Information',
//                'desc' => 'Photo',
//            ),
			'contrat_instance' => array(
				'name'              => amapress__( 'Contrat' ),
				'type'              => 'select-posts',
				'post_type'         => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'required'          => true,
				'csv_required'      => true,
				'desc'              => 'Contrat',
				'import_key'        => true,
				'autoselect_single' => true,
				'searchable'        => true,
			),
			'code'             => array(
				'name'         => amapress__( 'Code' ),
				'type'         => 'text',
				'required'     => true,
				'csv_required' => true,
				'desc'         => 'Code',
				'import_key'   => true,
				'searchable'   => true,
			),
			'prix_unitaire'    => array(
				'name'         => amapress__( 'Prix unitaire' ),
				'type'         => 'price',
				'required'     => true,
				'csv_required' => true,
				'unit'         => '€',
				'desc'         => 'Prix unitaire',
			),
			//que distrib
			'quantite'         => array(
				'name' => amapress__( 'Quantité' ),
				'type' => 'float',
//                'required' => true,
				'desc' => 'Quantité',
//                'import_key' => true,
			),
			//commandes
			'produits'         => array(
				'name'         => amapress__( 'Produits' ),
				'type'         => 'select-posts',
				'post_type'    => AmapressProduit::INTERNAL_POST_TYPE,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
			),
			'unit'             => array(
				'name'    => amapress__( 'Unité' ),
				'type'    => 'select',
				'options' => array(
					'unit' => 'Prix à l\'unité',
					'kg'   => 'Prix au kg',
					'l'    => 'Prix au litre',
				),
			),
			'quantite_config'  => array(
				'name' => amapress__( 'Config quantité' ),
				'type' => 'text',
			),
			'avail_from'       => array(
				'name' => amapress__( 'Valable à partir de' ),
				'type' => 'date',
			),
			'avail_to'         => array(
				'name' => amapress__( 'Valable jusqu\'à' ),
				'type' => 'date',
			),
		),
	);
//    $entities['contrat_paiement'] = array(
//        'internal_name' => 'amps_contrat_pmt',
//        'singular' => amapress__('Contrat paiment'),
//        'plural' => amapress__('Contrats paiements'),
//        'public' => 'adminonly',
//        'show_in_menu' => false,
//        'special_options' => array(),
//        'slug' => 'contrat_paiements',
//        'fields' => array(
//            'contrat_instance' => array(
//                'name' => amapress__('Contrat'),
//                'type' => 'select-posts',
//                'post_type' => 'amps_contrat_inst',
//                'required' => true,
//                'desc' => 'Contrat',
//            ),
//            'liste_dates' => array(
//                'name' => amapress__('Dates'),
//                'type' => 'custom',
//                'custom' => array('AmapressContrats', "displayPaiementListeDates"),
//                'save' => array('AmapressContrats', "savePaiementListeDates"),
//                'required' => true,
//                'desc' => 'Dates',
//            ),
//        ),
//    );
	return $entities;
}

add_filter( 'amapress_import_adhesion_multi', 'amapress_import_adhesion_multi', 5, 4 );
function amapress_import_adhesion_multi( $postmulti, $postdata, $postmeta, $posttaxo ) {
	foreach ( $postmulti as $k => $v ) {
		$postmulti[ $k ] = amapress_resolve_contrat_quantite_ids( $k, $v );
	}

	return $postmulti;
}

add_filter( 'amapress_get_edit_url_for_contrat_quantite', 'amapress_get_edit_url_for_contrat_quantite' );
function amapress_get_edit_url_for_contrat_quantite( $url ) {
	return admin_url( 'edit.php?post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE );
}

add_filter( 'amapress_import_adhesion_apply_default_values_to_posts_meta', 'amapress_import_adhesion_apply_default_values_to_posts_meta' );
function amapress_import_adhesion_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_contrat_instance'] ) && empty( $postmeta['amapress_adhesion_contrat_instance'] ) ) {
		$postmeta['amapress_adhesion_contrat_instance'] = $_REQUEST['amapress_import_adhesion_default_contrat_instance'];
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_lieu'] ) && empty( $postmeta['amapress_adhesion_lieu'] ) ) {
		$postmeta['amapress_adhesion_lieu'] = $_REQUEST['amapress_import_adhesion_default_lieu'];
	}
	if ( ! empty( $_REQUEST['amapress_import_adhesion_default_date_debut'] ) && empty( $postmeta['amapress_adhesion_date_debut'] ) ) {
		$vals                                     = AmapressEntities::getPostFieldsValidators();
		$val                                      = $vals['amapress_adhesion_date_debut'];
		$postmeta['amapress_adhesion_date_debut'] = call_user_func( $val, $_REQUEST['amapress_import_adhesion_default_date_debut'] );
	}

	return $postmeta;
}

add_filter( 'amapress_import_contrat_quantite_apply_default_values_to_posts_meta', 'amapress_import_contrat_quantite_apply_default_values_to_posts_meta' );
function amapress_import_contrat_quantite_apply_default_values_to_posts_meta( $postmeta ) {
	if ( ! empty( $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'] ) && empty( $postmeta['amapress_contrat_quantite_contrat_instance'] ) ) {
		$postmeta['amapress_contrat_quantite_contrat_instance'] = $_REQUEST['amapress_import_contrat_quantite_default_contrat_instance'];
	}

	if ( empty( $postmeta['amapress_contrat_quantite_quantite'] ) ) {
		$postmeta['amapress_contrat_quantite_quantite'] = 1;
	}
//    if (empty($postmeta['amapress_contrat_quantite_unit']))
//        $postmeta['amapress_contrat_quantite_quantite'] = 'unit';

	return $postmeta;
}

add_filter( 'amapress_import_adhesion_meta', 'amapress_import_adhesion_meta', 5, 4 );
function amapress_import_adhesion_meta( $postmeta, $postdata, $posttaxo, $postmulti ) {
	if ( ! empty( $postmulti ) ) {
		return $postmeta;
	}

	if ( is_wp_error( $postmeta['amapress_adhesion_contrat_instance'] ) || is_wp_error( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return $postmeta;
	}

	if ( empty( $postmeta['amapress_adhesion_contrat_instance'] ) || empty( $postmeta['amapress_adhesion_contrat_quantite'] ) ) {
		return new WP_Error( 'ignore_contrat', "Colonne contrat vide. La ligne sera ignorée." );
	}

	$contrat_instance = Amapress::resolve_post_id( $postmeta['amapress_adhesion_contrat_instance'], AmapressContrat_instance::INTERNAL_POST_TYPE );
	if ( empty( $contrat_instance ) || $contrat_instance <= 0 ) {
		return new WP_Error( 'cannot_find_contrat', "Impossible de trouver le contrat '{$postmeta['amapress_adhesion_contrat_instance']}'" );
	}

	$postmeta['amapress_adhesion_contrat_instance'] = $contrat_instance;

	$ids = amapress_resolve_contrat_quantite_ids( $contrat_instance, $postmeta['amapress_adhesion_contrat_quantite'] );
	if ( is_wp_error( $ids ) ) {
		return $ids;
	}

	$postmeta['amapress_adhesion_contrat_quantite'] = $ids;

	return $postmeta;
}

function amapress_resolve_contrat_quantite_ids( $contrat_instance_id, $contrat_quantite_name ) {
	if ( is_string( $contrat_quantite_name ) ) {
		$contrat_quantite_name = trim( $contrat_quantite_name );
		if ( empty( $contrat_quantite_name ) ) {
			return null;
		}

		$id = amapress_resolve_contrat_quantite_id( $contrat_instance_id, $contrat_quantite_name );
		if ( $id > 0 ) {
			return $id;
		}
	}

	$values = Amapress::get_array( $contrat_quantite_name );
	if ( ! is_array( $values ) ) {
		$values = array( $values );
	}

	$errors = array();
	$res    = array();
	foreach ( $values as $v) {
//        $v = trim($v);
		$id = amapress_resolve_contrat_quantite_id( $contrat_instance_id, $v );
		if ( $id <= 0 ) {
			$contrat_instance = new AmapressContrat_instance( $contrat_instance_id );
			$url              = admin_url( "post.php?post=$contrat_instance_id&action=edit" );
			$errors[]         = "Valeur '$v' non valide pour '{$contrat_instance->getTitle()}' (Voir <$url>)";
		} else {
			$res[] = $id;
		}
	}
	if ( ! empty( $errors ) ) {
		return new WP_Error( 'cannot_parse', implode( ' ; ', $errors ) );
	}

	if ( count( $res ) == 1 )
		return array_shift( $res );
	else
        return $res;
}

//add_filter('amapress_resolve_contrat_quantite_id','amapress_resolve_contrat_quantite_id', 10, 2);
function amapress_resolve_contrat_quantite_id( $contrat_instance_id, $contrat_quantite_name ) {
	$quants = AmapressContrats::get_contrat_quantites($contrat_instance_id);
//    $cn = $contrat_quantite_name;
	$contrat_quantite_name = wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $contrat_quantite_name ) ) );
	if ( empty( $contrat_quantite_name ) ) {
		return 0;
	}
	foreach ( $quants as $quant ) {
		if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getCode() ) ) ), $contrat_quantite_name ) === 0 ) {
			return $quant->ID;
		} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getSlug() ) ) ), $contrat_quantite_name ) === 0 ) {
			return $quant->ID;
		} else if ( strcasecmp( wptexturize( trim( \ForceUTF8\Encoding::toLatin1( $quant->getTitle() ) ) ), $contrat_quantite_name ) === 0 ) {
			return $quant->ID;
//                } else if (abs($quant->getQuantite() - @floatval(str_replace(',', '.', $cq))) < 0.01) {
		} else if ( str_replace( ',', '.', strval( $quant->getQuantite() ) ) == str_replace( ',', '.', $contrat_quantite_name ) ) {
			return $quant->ID;
        }
    }
//    var_dump($contrat_quantite_name);
//    var_dump($cn);
//    die();
	return 0;
}

function amapress_quantite_editor_line( AmapressContrat_instance $contrat_instance, $id, $title, $code, $description, $price, $unit, $quantite_conf, $from, $to, $quantite, $produits, $photo ) {
	if ( $contrat_instance->getModel() == null ) {
		return '';
	}
	$contrat_produits = array();
	foreach ( $contrat_instance->getModel()->getProducteur()->getProduits() as $prod ) {
		$contrat_produits[ $prod->ID ] = $prod->getTitle();
	}
	echo '<tr>';
	echo '<td style="border-top: 1pt solid #8c8c8c; border-collapse: collapse" class="quant-conf">';
	echo "<div><label>Intitulé: </label><input type='text' class='required' name='amapress_quant_data[$id][title]' placeholder='Intitulé' value='$title' /></div>";
	echo "<div><label>Code: </label><input type='text' class='required' name='amapress_quant_data[$id][code]' placeholder='Code' value='$code' /></div>";
	echo "<div><label>Description: </label><textarea class='' name='amapress_quant_data[$id][desc]' placeholder='Description'>{$description}</textarea></div>";
//    echo '</td>';
//    echo '<td>';
	echo "<div><label>Prix: </label><input type='number' class='required number' name='amapress_quant_data[$id][price]' min='0' step='0.01' placeholder='Prix unitaire' value='$price' /></div>";
//    echo '</td>';
//    echo '<td>';
	if ( $contrat_instance->isPanierVariable()) {
//        echo '<fieldset>';
		echo "<div><label>Unité: </label><select class='required' name='amapress_quant_data[$id][unit]'>";
		echo '<option value="">--Unité de prix--</option>';
		echo '<option ' . selected( 'unit', $unit, false ) . ' value="unit">A l\'unité</option>';
		echo '<option ' . selected( 'kg', $unit, false ) . ' value="kg">Au kg</option>';
		echo '<option ' . selected( 'l', $unit, false ) . ' value="l">Au litre</option>';
		echo '</select></div>';
		echo "<div><label>Quantité(s): </label><input type='text' class='text' name='amapress_quant_data[$id][quant_conf]' placeholder='Config' value='$quantite_conf' /></div>";
		echo "<div><label>Dispo de </label><input type='text' class='input-date date' name='amapress_quant_data[$id][avail_from]' placeholder='Date début' value='$from' /></div>";
		echo "<div><label> - à </label><input type='text' class='input-date date' name='amapress_quant_data[$id][avail_to]' placeholder='Date fin' value='$to' /></div>";
//        echo '</fieldset>';
	} else {
		echo "<div><label>Quantité: </label><input type='number' class='required number' name='amapress_quant_data[$id][quant]' min='0' step='0.01' placeholder='Quantité' value='$quantite' /></div>";
    }
//    echo '</td>';
//    echo '<td>';
	?>
    <div><label>Produits: </label><select id="<?php echo 'amapress_quant_data[$id][produits]' ?>"
                                          name="<?php echo 'amapress_quant_data[$id][produits]'; ?>"
                                          class="quant-produit" multiple="multiple"
                                          data-placeholder="Produits associés"
    ><?php
		tf_parse_select_options( $contrat_produits, $produits );
		?></select></div><?php
    echo '</td>';
//            echo "<td><input type='number' class='required number' name='amapress_quant_data[$id][max_quant]' placeholder='Quantité commandable max' value='$max' /></td>";

	echo "<td class='tf-upload'  style='border-top: 1pt solid #8c8c8c; border-collapse: collapse'>";
	TitanFrameworkOptionUpload::echo_uploader( "amapress_quant_data[$id][photo]", $photo, '' );
	echo "</td>";
	if ( amapress_can_delete_contrat_quantite( '', $id ) === true ) {
		echo "<td><span class='btn del-model-tab dashicons dashicons-dismiss' onclick='amapress_del_quant(this)'></span></td>";
	} else {
		echo "<td></td>";
	}

	echo '</tr>';
}

function amapress_get_contrat_quantite_editor( $contrat_instance_id ) {
	$contrat_instance = new AmapressContrat_instance( $contrat_instance_id );
	if ( $contrat_instance->getModel() == null ) {
		return '';
	}

	ob_start();
	?>
    <input type="hidden" name="amapress_quant_data_contrat_instance_id" value="<?php echo $contrat_instance_id; ?>" />
    <table class="table" style="width: 100%;">
        <thead>
        <tr>
            <th>Configuration</th>
            <th style="width: 60px">Photo</th>
            <th style="width: 35px"></th>
        </tr>
        </thead>
        <tbody>
	    <?php
	    foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance_id ) as $quant ) {
		    $id = $quant->ID;


		    $tit  = esc_attr( $quant->getTitle() );
		    $q    = esc_attr( $quant->getQuantite() );
		    $c    = esc_attr( $quant->getCode() );
		    $pr   = esc_attr( $quant->getPrix_unitaire() );
		    $qc   = esc_attr( $quant->getQuantiteConfig() );
		    $desc = esc_textarea( stripslashes( $quant->getDescription() ) );
		    $af   = esc_attr( $quant->getAvailFrom() ? date_i18n( TitanFrameworkOptionDate::$default_date_format, intval( $quant->getAvailFrom() ) ) : null );
		    $at   = esc_attr( $quant->getAvailTo() ? date_i18n( TitanFrameworkOptionDate::$default_date_format, intval($quant->getAvailTo())) : null);
//            $max = esc_attr($quant->getMax_Commandable());

		    amapress_quantite_editor_line( $contrat_instance, $id, $tit, $c, $desc, $pr, $quant->getPriceUnit(),
			    $qc, $af, $at, $q, implode( ',', $quant->getProduitsIds() ), get_post_thumbnail_id( $quant->ID));
        }
        ?>
        <tr>
            <td colspan="3"><span class="btn add-model dashicons dashicons-plus-alt"
                                  onclick="amapress_add_quant(this)"></span> Ajouter une quantité</td>
        </tr>
        </tbody>
    </table>

	<?php
	$contents = ob_get_contents();
	ob_clean();

    ob_start();
//    echo '<tr>';
//
//    echo  "<td><input type='text' class='required' name='amapress_quant_data[%%id%%][title]' placeholder='Intitulé' /></td>";
//    echo  "<td><input type='text' class='required' name='amapress_quant_data[%%id%%][code]' placeholder='Code' /></td>";
//    echo  "<td><input type='number' class='required number' name='amapress_quant_data[%%id%%][quant]' placeholder='Quantité' /></td>";
//    echo  "<td><input type='number' class='required number' name='amapress_quant_data[%%id%%][price]' placeholder='Prix unitaire' /></td>";
////    echo  "<td><input type='number' class='required number' name='amapress_quant_data[%%id%%][max_quant]' placeholder='Quantité commandable max' /></td>";
//
//    echo  "<td  class='tf-upload'>";
//    TitanFrameworkOptionUpload::echo_uploader("amapress_quant_data[%%id%%][photo]", '', '');
//    echo  "</td>";
//    echo  "<td><span class='btn del-model-tab dashicons dashicons-dismiss' onclick='amapress_del_quant(this)'></span></td>";
//
//    echo  '</tr>';
	amapress_quantite_editor_line( $contrat_instance, '%%id%%', '', '', '', 0, 0,
		'', null, null, 0, '', '' );

	$new_row = ob_get_contents();
	ob_clean();

	$new_row  = json_encode( array( 'html' => $new_row ) );
	$contents .= "<script type='text/javascript'>//<![CDATA[
    jQuery(function() {
        amapress_quant_load_tags();
    });
    function amapress_quant_load_tags() {
        jQuery('.quant-produit').select2({
            allowClear: true,
              escapeMarkup: function(markup) {
        return markup;
    },
              templateResult: function(data) {
        return jQuery('<span>'+data.text+'</span>');
    },
              templateSelection: function(data) {
        return jQuery('<span>'+data.text+'</span>');
    }
        });
    }
    function amapress_add_quant(e) {
        var max = jQuery(e).data('max') || 0;
        max -= 1;
        jQuery(e).data('max', max);
        var html = {$new_row}['html'];
        html = html.replace(/%%id%%/g, max);
        jQuery(html).insertBefore(jQuery(e).closest('tr'));
        amapress_quant_load_tags();
    };
    function amapress_del_quant(e) {
        if (!confirm('Voulez-vous vraiment supprimer cette quantité ?')) return;
        jQuery(e).closest('tr').remove();
    };
    //]]>
</script>";

	return $contents;
}

function amapress_save_contrat_quantite_editor($contrat_instance_id) {
//    global $amapress_save_contrat_quantite_editor;

//    if ($amapress_save_contrat_quantite_editor) return;

	if ( isset( $_POST['amapress_quant_data'] ) && isset( $_POST['amapress_quant_data_contrat_instance_id'])) {
//        $amapress_save_contrat_quantite_editor = true;

		$quants     = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
		$quants_ids = array_map( function ( $q ) {
			return $q->ID;
		}, $quants );

		foreach ( array_diff( $quants_ids, array_keys( $_POST['amapress_quant_data'] ) ) as $qid ) {
			wp_delete_post( $qid );
		}
		foreach ( $_POST['amapress_quant_data'] as $quant_id => $quant_data ) {
			$quant_id = intval( $quant_id );
			$my_post  = array(
				'post_title'   => $quant_data['title'],
				'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => get_post_status( $contrat_instance_id ),
				'meta_input'   => array(
					'amapress_contrat_quantite_contrat_instance' => $contrat_instance_id,
					'amapress_contrat_quantite_prix_unitaire'    => $quant_data['price'],
					'amapress_contrat_quantite_code'             => $quant_data['code'],
					'amapress_contrat_quantite_description'      => $quant_data['desc'],
					'amapress_contrat_quantite_quantite_config'  => isset( $quant_data['quant_conf'] ) ? $quant_data['quant_conf'] : null,
					'amapress_contrat_quantite_unit'             => isset( $quant_data['unit'] ) ? $quant_data['unit'] : null,
					'amapress_contrat_quantite_produits'         => isset( $quant_data['produits'] ) ? $quant_data['produits'] : null,
					'amapress_contrat_quantite_avail_from'       => ! empty( $quant_data['avail_from'] ) ? TitanEntity::to_date( $quant_data['avail_from'] ) : null,
					'amapress_contrat_quantite_avail_to'         => ! empty( $quant_data['avail_to'] ) ? TitanEntity::to_date( $quant_data['avail_to'] ) : null,
					'amapress_contrat_quantite_quantite'         => isset( $quant_data['quant'] ) ? $quant_data['quant'] : null,
					'_thumbnail_id'                              => $quant_data['photo'],
				),
			);
			if ( $quant_id < 0 ) {
				wp_insert_post( $my_post );
			} else {
				$my_post['ID'] = $quant_id;
//                $my_post['post_status'] = 'publish';
				wp_update_post( $my_post, true );
			}
		}
		unset($_POST['amapress_quant_data']);

//        $amapress_save_contrat_quantite_editor = false;
	}
}

add_filter( 'amapress_can_delete_contrat', 'amapress_can_delete_contrat', 10, 2 );
function amapress_can_delete_contrat( $can, $post_id ) {
	return count( AmapressContrats::get_all_contrat_instances_by_contrat( $post_id ) ) == 0;
}

add_filter( 'amapress_can_delete_contrat_instance', 'amapress_can_delete_contrat_instance', 10, 2 );
function amapress_can_delete_contrat_instance( $can, $post_id ) {
	return count( AmapressContrats::get_all_adhesions( $post_id ) ) == 0;
}

add_filter( 'amapress_can_delete_contrat_quantite', 'amapress_can_delete_contrat_quantite', 10, 2 );
function amapress_can_delete_contrat_quantite($can, $post_id) {
//    $posts = get_posts(
//        array(
//            'post_type' => AmapressAdhesion::INTERNAL_POST_TYPE,
//            'post_status' => 'any',
//            'meta_query' => array(
//                array(
//                    'key' => 'amapress_adhesion_lieu',
//                    'value' => $post_id,
//                )
//            ),
//        )
//    );

	return count( AmapressContrats::get_all_adhesions( null, $post_id ) ) == 0;
}

add_action( 'amapress_row_action_contrat_instance_renew', 'amapress_row_action_contrat_instance_renew' );
function amapress_row_action_contrat_instance_renew( $post_id ) {
	$contrat_inst         = new AmapressContrat_instance( $post_id );
	$new_contrat_instance = $contrat_inst->cloneContrat();
	if ( ! $new_contrat_instance ) {
		wp_die( 'Une erreur s\'est produit lors du renouvèlement du contrat. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_contrat_instance->ID}&action=edit"));
}