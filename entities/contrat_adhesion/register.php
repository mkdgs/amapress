<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_adhesion' );
function amapress_register_entities_adhesion( $entities ) {
	$entities['adhesion'] = array(
		'singular'         => amapress__( 'Inscription Contrat' ),
		'plural'           => amapress__( 'Inscriptions Contrat' ),
		'public'           => 'adminonly',
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'title'            => false,
		'editor'           => false,
		'slug'             => 'adhesions',
		'title_format'     => 'amapress_adhesion_title_formatter',
		'slug_format'      => 'from_title',
		'menu_icon'        => 'flaticon-signature',
//		'show_admin_bar_new' => true,
		'labels'           => array(
			'add_new'      => 'Ajouter',
			'add_new_item' => 'Ajout Inscription',
		),
		'groups'           => array(
			'Infos' => [
				'context' => 'side',
			],
		),
		'row_actions'      => array(
			//visibilité checkée dans amapress_row_actions_adhesion
			'renew'             => 'Renouveler',
			'close'             => [
				'label'     => 'Clôturer à la fin',
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion::CONFIRMED == AmapressAdhesion::getBy( $adh_id )->getStatus();
				},
				'confirm'   => true,
			],
			'generate_contrat'  => [
				'label'     => 'Générer le contrat',
				'condition' => function ( $adh_id ) {
					if ( TitanFrameworkOption::isOnNewScreen() ) {
						return false;
					}

					$adh = AmapressAdhesion::getBy( $adh_id );
					if ( empty( $adh ) ) {
						return false;
					}
					if ( empty( $adh->getContrat_instance() ) ) {
						return false;
					}

					return ! empty( $adh->getContrat_instance()->getContratModelDocFileName() );
				},
			],
			'send_confirmation' => [
				'label'     => 'Envoyer mail confirmation',
				'confirm'   => true,
				'condition' => function ( $adh_id ) {
					return TitanFrameworkOption::isOnEditScreen();
				},
			],
			'accept'            => [
				'label'     => 'Confirmer inscription',
				'confirm'   => true,
				'condition' => function ( $adh_id ) {
					return AmapressAdhesion::TO_CONFIRM == AmapressAdhesion::getBy( $adh_id )->getStatus();
				},
			],
		),
		'bulk_actions'     => array(
			'amp_accept_contrat_adhesion'         => array(
				'label'    => 'Confirmer inscription',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'opération',
					'0'  => 'Une erreur s\'est produit pendant l\'opération',
					'1'  => 'Une inscription a été confirmée avec succès',
					'>1' => '%s inscriptions ont été confirmée avec succès',
				),
			),
			'amp_resend_confirm_contrat_adhesion' => array(
				'label'    => 'Envoyer le mail de confirmation inscription',
				'messages' => array(
					'<0' => 'Une erreur s\'est produit pendant l\'opération',
					'0'  => 'Une erreur s\'est produit pendant l\'opération',
					'1'  => 'Une inscription a été confirmée avec succès',
					'>1' => '%s inscriptions ont été confirmée avec succès',
				),
			),
		),
		'default_orderby'  => 'post_title',
		'default_order'    => 'ASC',
		'edit_header'      => function ( $post ) {
			TitanFrameworkOption::echoFullEditLinkAndWarning();

			$adh = AmapressAdhesion::getBy( $post );
			if ( ! $adh->getContrat_instance() || ! $adh->getAdherent() ) {
				return;
			}
			if ( $adh->getContrat_instance()->isPrincipal() ) {
				return;
			}

			$principal_contrat            = null;
			$principal_contrat_date_debut = 0;
			Amapress::setFilterForReferent( false );
			$contrats = AmapressContrats::get_active_contrat_instances( null, $adh->getDate_debut(), true );
			Amapress::setFilterForReferent( true );
			foreach ( $contrats as $contrat ) {
				if ( $contrat->isPrincipal() && $contrat->getDate_debut() < $adh->getDate_fin() && $contrat->getDate_debut() > $principal_contrat_date_debut ) {
					$principal_contrat            = $contrat;
					$principal_contrat_date_debut = $contrat->getDate_debut();
				}
			}

			if ( $principal_contrat ) {
				Amapress::setFilterForReferent( false );
				$other_adhs = AmapressAdhesion::getUserActiveAdhesions( $adh->getAdherentId(), $principal_contrat->ID, $adh->getDate_debut(), true );
				Amapress::setFilterForReferent( true );
				if ( ! empty( $other_adhs ) ) {
					return;
				}
				$message = "L'amapien {$adh->getAdherent()->getDisplayName()} n'a pas de contrat principal : {$principal_contrat->getTitle()}";
			} else {
				$message = 'Pas de contrat principal actif';
			}

			$class = 'notice notice-warning';

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

		},
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_adhesion_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'adherent_display'  => array(
				'csv_import'    => false,
				'csv_export'    => true,
				'hidden'        => true,
				'group'         => '1/ Informations',
				'name'          => amapress__( 'Adhérent' ),
				'join_meta_key' => 'amapress_adhesion_adherent',
				'sort_column'   => 'display_name',
				'type'          => 'custom',
				'column'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return Amapress::makeLink( $adh->getAdherent()->getEditLink(), $adh->getAdherent()->getDisplayName(), true, true );
				},
				'export'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getDisplayName();
				}
			),
			'adherent'          => array(
				'name'         => amapress__( 'Adhérent' ),
				'type'         => 'select-users',
				'required'     => true,
				'group'        => '1/ Informations',
				'import_key'   => true,
				'csv_required' => true,
				'autocomplete' => true,
				'searchable'   => true,
				'csv_export'   => false,
				'show_column'  => false,
				'readonly'     => function ( $post_id ) {
					return TitanFrameworkOption::isOnEditScreen();
				}
			),
			'adherent_lastname' => array(
				'csv_import'    => false,
				'csv_export'    => true,
				'hidden'        => true,
				'group'         => '1/ Informations',
				'name'          => amapress__( 'Nom' ),
				'type'          => 'custom',
				'join_meta_key' => 'amapress_adhesion_adherent',
				'sort_column'   => 'last_name',
				'column'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getUser()->last_name;
				}
			),
			'adherent_email'    => array(
				'csv_import'    => false,
				'csv_export'    => true,
				'hidden'        => true,
				'group'         => '1/ Informations',
				'name'          => amapress__( 'Mail' ),
				'type'          => 'custom',
				'join_meta_key' => 'amapress_adhesion_adherent',
				'sort_column'   => 'user_email',
				'column'        => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getUser()->user_email;
				}
			),
			'adherent_address'  => array(
				'csv_import' => false,
				'csv_export' => true,
				'hidden'     => true,
				'group'      => '1/ Informations',
				'name'       => amapress__( 'Adresse' ),
				'type'       => 'custom',
				'column'     => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );
					if ( ! $adh->getAdherentId() ) {
						return '';
					}

					return $adh->getAdherent()->getFormattedAdresse();
				}
			),
			'status'            => array(
				'name'     => amapress__( 'Statut' ),
				'type'     => 'select',
				'group'    => 'Infos',
				'options'  => array(
					'to_confirm' => 'A confirmer',
					'confirmed'  => 'Confirmée',
				),
				'default'  => function ( $option ) {
					$my_roles = AmapressContrats::getReferentProducteursAndLieux( amapress_current_user_id() );
					if ( ! empty( $my_roles ) ) {
						return 'confirmed';
					} else {
						return 'to_confirm';
					}
				},
				'required' => true,
//				'desc'     => 'Statut',
			),
			'quantites_editor'  => array(
				'name'        => amapress__( 'Contrat et Quantité(s)' ),
				'type'        => 'custom',
				'show_column' => false,
				'custom'      => 'amapress_adhesion_contrat_quantite_editor',
				'save'        => 'amapress_save_adhesion_contrat_quantite_editor',
				'desc'        => 'Sélectionner le contrat et les quantités/produits associé(s) :<br/></br/>',
				'show_desc'   => 'before',
				'group'       => '2/ Contrat',
				'csv'         => false,
//                'show_on' => 'edit',
			),
			'contrat_instance'  => array(
				'name'              => amapress__( 'Contrat' ),
				'type'              => 'select-posts',
//                'readonly' => 'edit',
				'hidden'            => true,
				'group'             => '2/ Contrat',
				'post_type'         => 'amps_contrat_inst',
				'desc'              => 'Contrat',
				'import_key'        => true,
//                'required' => true,
				'autoselect_single' => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_contrat_inst',
					'placeholder' => 'Tous les contrats'
				),
				'csv_required'      => true,
				'searchable'        => true,
				'custom_multi'      => function ( $option ) {
					$ret = [];
					foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				},
				'custom_csv_sample' => function ( $option, $arg ) {
					$ret = [];
					foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
						$ret[ $c->ID ] = $c->getTitle();
					}

					return $ret;
				}

			),
			'contrat_quantite'  => array(
				'name'              => amapress__( 'Quantité' ),
				'type'              => 'custom',
				'readonly'          => true,
				'hidden'            => true,
				'group'             => '2/ Contrat',
				'required'          => true,
				'post_type'         => 'amps_contrat_quant',
				'desc'              => 'Quantité',
				'column'            => function ( $post_id, $option = null ) {
					if ( ! $post_id ) {
						return '';
					}
					$adh = AmapressAdhesion::getBy( $post_id );

					return esc_html( $adh->getContrat_quantites_AsString() );
				},
				'custom_csv_sample' => function ( $option, $arg ) {
					if ( $arg['multi'] != - 1 ) {
						$ret = [];
						foreach ( AmapressContrats::get_contrat_quantites( $arg['multi'] ) as $q ) {
							$ret[] = $q->getTitle();
							$ret[] = $q->getCode();
							if ( $q->getQuantite() ) {
								$ret[] = $q->getQuantite();
							}
						}
						$filtered_ret = [];
						foreach ( $ret as $r ) {
							if ( ! in_array( $r, $filtered_ret ) ) {
								$filtered_ret[] = $r;
							}
						}

						return $filtered_ret;
					} else {
						$ret = [];
						foreach ( AmapressContrats::get_active_contrat_instances() as $c ) {
							$ret[]       = '**Pour le contrat <' . $c->getTitle() . '>**';
							$contrat_ret = [];
							foreach ( AmapressContrats::get_contrat_quantites( $c->ID ) as $q ) {
								$contrat_ret[] = $q->getTitle();
								$contrat_ret[] = $q->getCode();
								if ( $q->getQuantite() ) {
									$contrat_ret[] = $q->getQuantite();
								}
							}
							$filtered_ret = [];
							foreach ( $contrat_ret as $r ) {
								if ( ! in_array( $r, $filtered_ret ) ) {
									$filtered_ret[] = $r;
									$ret[]          = $r;
								}
							}
						}

						return $ret;
					}
				},
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_contrat_qt',
					'placeholder' => 'Toutes les quantités'
				),
				'csv_required'      => true,
				'wrap_edit'         => false,
//                'import_key' => true,
//                'csv_required' => true,
			),
			'date_debut'        => array(
				'name'          => amapress__( 'Date de début' ),
				'type'          => 'date',
				'required'      => true,
				'group'         => '2/ Contrat',
				'desc'          => 'Date à laquelle démarre le contrat',
				'csv_required'  => true,
				'default'       => function ( $option = null ) {
					return amapress_time();
				},
				'top_filter'    => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'before_option' =>
					function ( $option ) {
						/** @var TitanFrameworkOption $option */
						if ( TitanFrameworkOption::isOnEditScreen() ) {
							$adh = AmapressAdhesion::getBy( $option->getPostID() );
							echo '<script type="text/javascript">
jQuery(function($) {
    var $date_debut = $("#amapress_adhesion_date_debut");
    $date_debut.datepicker("option", 
    	{
    	    minDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_debut() ) . '",
    	    maxDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_fin() ) . '"
    	}
    );
});
</script>';
						}
					},
			),
			'paiements'         => array(
				'name'        => amapress__( 'Nombre de chèque' ),
				'type'        => 'custom',
				'group'       => '3/ Paiements',
				'required'    => true,
				'desc'        => 'Nombre de paiements. <b>Lorsque vous changer la valeur de ce champs, il est nécessaire d\'enregistrer l\'adhésion</b>',
				'custom'      => 'amapress_paiements_count_editor',
				'show_on'     => 'edit-only',
				'show_column' => false,
				'csv'         => false,
//                'csv_required' => true,
			),
			'paiements_editor'  => array(
				'name'        => amapress__( 'Details des paiements' ),
				'type'        => 'custom',
				'show_column' => false,
				'custom'      => 'amapress_paiements_editor',
				'save'        => 'amapress_save_paiements_editor',
//                'desc' => 'Details des',
				'group'       => '3/ Paiements',
				'csv'         => false,
				'show_on'     => 'edit-only',
			),
			'lieu'              => array(
				'name'              => amapress__( 'Lieu' ),
				'type'              => 'select-posts',
				'post_type'         => 'amps_lieu',
				'required'          => true,
				'desc'              => 'Sélectionner le lieu de distribution',
				'group'             => '2/ Contrat',
				'import_key'        => true,
				'csv_required'      => true,
				'autoselect_single' => true,
				'searchable'        => true,
				'orderby'           => 'post_title',
				'order'             => 'ASC',
				'top_filter'        => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Tous les lieux'
				),
				'default'           => function ( $option = null ) {
					if ( ! empty( $_GET['amapress_adhesion_adherent'] ) ) {
						$user_lieux = AmapressUsers::get_user_lieu_ids( intval( $_GET['amapress_adhesion_adherent'] ) );

						return array_shift( $user_lieux );
					}

					return 0;
				}
			),
			'message'           => array(
				'name'        => amapress__( 'Message' ),
				'type'        => 'textarea',
				'readonly'    => true,
				'show_column' => false,
				'hidden'      => true,
				'desc'        => 'Message',
				'csv'         => false,
			),
			'all-coadherents'   => array(
				'name'            => amapress__( 'Co-adhérents' ),
				'group'           => '4/ Coadhérents',
				'show_column'     => false,
				'include_columns' => array(
					'name',
					'email',
					'role',
					'amapress_user_telephone',
					'amapress_user_adresse',
				),
				'type'            => 'related-users',
				'query'           => function ( $post_id ) {
					$adh = AmapressAdhesion::getBy( $post_id );

					return 'amapress_coadherents=' . $adh->getAdherent()->ID;
				},
			),
			'adherent2'         => array(
				'name'         => amapress__( 'Co-Adhérent 1' ),
				'type'         => 'select-users',
				'required'     => false,
				'desc'         => 'Sélectionner un Co-Adhérent 1. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'group'        => '4/ Coadhérents',
				'autocomplete' => true,
				'searchable'   => true,
			),
			'adherent3'         => array(
				'name'         => amapress__( 'Co-Adhérent 2' ),
				'type'         => 'select-users',
				'required'     => false,
				'desc'         => 'Sélectionner un Co-Adhérent 2. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'group'        => '4/ Coadhérents',
				'autocomplete' => true,
				'searchable'   => true,
			),
			'adherent4'         => array(
				'name'         => amapress__( 'Co-Adhérent 3' ),
				'type'         => 'select-users',
				'required'     => false,
				'desc'         => 'Sélectionner un Co-Adhérent 3. S\'il ne se trouve pas dans la liste ci-dessus, créer son compte depuis « <a href="' . admin_url( 'user-new.php' ) . '" target="_blank">Ajouter un utilisateur</a> » puis fermer la page et rafraîchir la liste avec le bouton accolé au champs',
				'group'        => '4/ Coadhérents',
				'autocomplete' => true,
				'searchable'   => true,
			),
			'date_fin'          => array(
				'name'          => amapress__( 'Date de fin' ),
				'type'          => 'date',
				'group'         => '5/ Fin de contrat avant terme',
				'desc'          => 'Date à laquelle se termine le contrat',
				'show_column'   => false,
				'show_on'       => 'edit-only',
				'csv'           => false,
				'before_option' =>
					function ( $option ) {
						/** @var TitanFrameworkOption $option */
						if ( TitanFrameworkOption::isOnEditScreen() ) {
							$adh = AmapressAdhesion::getBy( $option->getPostID() );
							echo '<script type="text/javascript">
jQuery(function($) {
    var $date_debut = $("#amapress_adhesion_date_fin");
    $date_debut.datepicker("option", 
    	{
    	    minDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_debut() ) . '",
    	    maxDate: "' . date_i18n( TitanFrameworkOptionDate::$default_date_format, $adh->getContrat_instance()->getDate_fin() ) . '"
    	}
    );
});
</script>';
						}
					},
			),
			'pmt_fin'           => array(
				'name'        => amapress__( 'Date fin des paiements' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'group'       => '5/ Fin de contrat avant terme',
				'desc'        => 'Prendre en compte la date de fin pour recalculer le montant de l\'inscription',
				'show_column' => false,
				'show_on'     => 'edit-only',
				'csv'         => false,
			),
			'fin_raison'        => array(
				'name'        => amapress__( 'Motif' ),
				'type'        => 'textarea',
				'group'       => '5/ Fin de contrat avant terme',
				'desc'        => 'Motif de départ (Déménagement, insatisfaction, ...)',
				'show_column' => false,
				'show_on'     => 'edit-only',
				'csv'         => false,
			),
//			'no_renew'       => array(
//				'name'        => amapress__( 'Pas de renouvellement' ),
//				'type'        => 'checkbox',
//				'group'       => '4/ Renouvellement',
//				'desc'        => 'Cocher cette case si l\'amapien ne souhaite pas renouveller son contrat',
//				'show_column' => false,
//			),
//            'co-adherents' => array(
//                'name' => amapress__('Binômes'),
//                'type' => 'multicheck-users',
//                'desc' => 'Binômes',
//                'csv' => false,
//            ),
//            'pstatus' => array(
//                'name' => amapress__('Statut'),
//                'type' => 'custom',
//                'column' => array('AmapressContrats', "paiementStatus"),
//                'save' => null,
//                'desc' => 'Statut',
//                'csv' => false,
//            ),
		),
	);

	return $entities;
}


function amapress_adhesion_contrat_quantite_editor( $post_id ) {
	$ret                   = '';
	$adh                   = AmapressAdhesion::getBy( $post_id );
	$date_debut            = $adh->getDate_debut() ? $adh->getDate_debut() : amapress_time();
	$adhesion_quantite_ids = $adh->getContrat_instance() ? $adh->getContrat_quantites_IDs() : array();
	$adhesion_quantites    = $adh->getContrat_quantites( null );
	$paniers_variables     = $adh->getPaniersVariables();
	$ret                   .= '<fieldset style="min-width: inherit">';
	$contrats              = AmapressContrats::get_active_contrat_instances(
		$adh->getContrat_instance() ? $adh->getContrat_instance()->ID : null,
		$date_debut );
	$excluded_contrat_ids  = [];
	if ( TitanFrameworkOption::isOnNewScreen() && ! empty( $_GET['amapress_adhesion_adherent'] ) ) {
		foreach ( AmapressAdhesion::getUserActiveAdhesions( intval( $_GET['amapress_adhesion_adherent'] ) ) as $user_adh ) {
			$excluded_contrat_ids[] = $user_adh->getContrat_instanceId();
		}
	}
	$had_contrat = false;
	foreach ( $contrats as $contrat_instance ) {
		if ( in_array( $contrat_instance->ID, $excluded_contrat_ids ) ) {
			continue;
		}
		$id = 'contrat-' . $contrat_instance->ID;
		if ( $contrat_instance->isPanierVariable() ) {
			if ( TitanFrameworkOption::isOnNewScreen() ) {
				$had_contrat = true;
				$ret         .= '';
				$ret         .= sprintf( '<b>%s</b><div><label for="%s"><input class="%s" id="%s" type="checkbox" name="%s[]" value="%s" data-excl="%s" data-contrat-date-debut="%s" data-contrat-date-fin="%s"/> Panier personnalisé</label></div>',
					esc_html( $contrat_instance->getTitle() ),
					$id,
					'multicheckReq exclusiveContrat contrat-quantite onlyOneInscription', //multicheckReq
					$id,
					'amapress_adhesion_contrat_vars',
					esc_attr( $contrat_instance->ID ),
					esc_attr( $contrat_instance->ID ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_debut() ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_fin() )
				);
				$ret         .= "<script type='text/javascript'>jQuery('#$id').change(function() {
    var this_option = jQuery(this);
  jQuery('#amapress_adhesion_date_debut').datepicker('option', {minDate:this_option.data('contrat-date-debut'), maxDate: this_option.data('contrat-date-fin')});
})</script>";
			} else {
				if ( ! $paniers_variables ) {
					$paniers_variables = array();
				}

				$columns = array(
					array(
						'title' => 'Produit',
						'data'  => 'produit',
					),
				);
				foreach ( $contrat_instance->getListe_dates() as $date ) {
					$columns[] = array(
						'title' => date_i18n( 'd/m/y', $date ),
						'data'  => 'd-' . $date,
					);
				}

				if ( ! TitanFrameworkOption::isOnNewScreen() && ! isset( $_REQUEST['full_edit'] ) ) {
					$ret .= '<input id="amapress_adhesion_adherent" name="amapress_adhesion_adherent" type="hidden" value="' . $adh->getAdherentId() . '" />';
				}

				$data = array();
				foreach ( AmapressContrats::get_contrat_quantites( $contrat_instance->ID ) as $quant ) {
					$row     = array(
						'produit' => esc_html( $quant->getTitle() ),
					);
					$options = $quant->getQuantiteOptions();
					foreach ( $contrat_instance->getListe_dates() as $date ) {
						$val         = isset( $paniers_variables[ $date ][ $quant->ID ] ) ? $paniers_variables[ $date ][ $quant->ID ] : '';
						$is_empty    = empty( $val );
						$empty_class = $is_empty ? 'contrat_panier_vars-empty' : 'contrat_panier_vars-with-value';
//                        $display_val = $is_empty ? '' : esc_html($val);
						$ed = '';
//                        $ed .= '<div class="contrat_panier_vars-wrapper">';
//                        $ed .= "<span class='contrat_panier_vars-value $empty_class' id='panier_vars_{$date}_{$quant->ID}-value'>{$display_val}</span>";
//                        $ed .= "<select id='panier_vars_{$date}_{$quant->ID}-select'
//data-value-id='panier_vars_{$date}_{$quant->ID}-value'
//name='amapress_adhesion_contrat_panier_vars[$date][{$quant->ID}]' class='contrat_panier_vars-select $empty_class'>";
						$ed .= "<select name='amapress_adhesion_contrat_panier_vars[$date][{$quant->ID}]' class='contrat_panier_vars-select $empty_class'>";
						$ed .= tf_parse_select_options( $options, $val, false );
						$ed .= '</select>';
//                        $ed .= '</div>';
						if ( $quant->getAvailFrom() && $quant->getAvailTo() ) {
							if ( $date < Amapress::start_of_day( $quant->getAvailFrom() ) || $date > Amapress::end_of_day( $quant->getAvailTo() ) ) {
								$ed = '<span class="contrat_panier_vars-na">NA</span>';
							}
						}
						$row[ 'd-' . $date ] = $ed;
					}
					$data[] = $row;
				}

//                $ret .= '<table class="display nowrap dataTable no-footer" width="100%" cellspacing="0" role="grid" style="margin-left: 0px; width: 9875px;"><thead><tr role="row"><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 60px;">Produit</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/09/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">29/10/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/11/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">31/12/15</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/01/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/02/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">31/03/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/04/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/05/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">30/06/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">07/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">14/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">28/07/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/08/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">21/09/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/09/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/10/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">17/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">24/11/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/12/16</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">05/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">12/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">19/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">26/01/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/02/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">02/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">09/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">16/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">23/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">30/03/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/04/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">04/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">11/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">18/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">25/05/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">01/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">08/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">15/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">22/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">29/06/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">06/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">13/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">20/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">27/07/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">03/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 61px;">10/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">17/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">24/08/17</th><th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">31/08/17</th></tr></thead></table>';


				$had_contrat = true;
				$ret         .= amapress_get_datatable( 'quant-commandes', $columns, $data, array(
					'bSort'        => true,
					'paging'       => false,
					'searching'    => true,
					'bAutoWidth'   => true,
					'responsive'   => false,
					'initComplete' => 'function() {
                    jQuery(".contrat_panier_vars-select").parent().click(
                        function() {
                            jQuery(this).find(".contrat_panier_vars-select").css(\'visibility\', \'visible\');
                        }
                    );
                    jQuery(".contrat_panier_vars-select.contrat_panier_vars-empty").css(\'visibility\', \'hidden\');
                    }',
					'scrollX'      => true,
//                    'fixedHeader' => array(
//                        'headerOffset' => 32,
//                    ),
					'fixedColumns' => array( 'leftColumns' => 1 ),
				) );
			}
		} else {
			$contrat_quants     = AmapressContrats::get_contrat_quantites( $contrat_instance->ID );
			$contrat_quants_ids = array_map( function ( $c ) {
				return $c->ID;
			}, $contrat_quants );
			if ( empty( $contrat_quants ) || count( $contrat_quants ) == 0 ) {
				continue;
			}
			if ( count( $adhesion_quantite_ids ) > 0 && count( array_intersect( $adhesion_quantite_ids, $contrat_quants_ids ) ) == 0 ) {
				continue;
			}

			$had_contrat = true;
			$ret         .= '<b>' . Amapress::makeLink( $contrat_instance->getAdminEditLink(), $contrat_instance->getTitle(), true, true ) . '</b>';
			$ret         .= '<div>';
			if ( ! TitanFrameworkOption::isOnNewScreen() && ! isset( $_REQUEST['full_edit'] ) ) {
				$ret .= '<input id="amapress_adhesion_adherent" name="amapress_adhesion_adherent" type="hidden" value="' . $adh->getAdherentId() . '" />';
			}

			foreach ( $contrat_quants as $quantite ) {
				if ( empty( $quantite ) ) {
					continue;
				}
				$id        = 'contrat-' . $contrat_instance->ID . '-quant-' . $quantite->ID;
				$id_factor = 'contrat-' . $contrat_instance->ID . '-quant-' . $quantite->ID . '-factor';

				$quant_var_editor = '';
				if ( $contrat_instance->isQuantiteVariable() ) {
					$disabled         = disabled( in_array( $quantite->ID, $adhesion_quantite_ids ), false, false );
					$quant_var_editor .= "<select id='$id_factor' name='amapress_adhesion_contrat_quants_factors[{$quantite->ID}]' style='display: inline-block;min-width: auto' $disabled>";

					$quant_var_editor .= tf_parse_select_options(
						$quantite->getQuantiteOptions(),
						! empty( $adhesion_quantites[ $quantite->ID ] ) ? $adhesion_quantites[ $quantite->ID ]->getFactor() : null,
						false );
					$quant_var_editor .= '</select>';
				}


				$type = $contrat_instance->isQuantiteMultiple() ? 'checkbox' : 'radio';
				if ( empty( $quantite->getContrat_instance() ) || empty( $contrat_instance ) ) {
					continue;
				}
				$ret .= sprintf( '<label for="%s" style="white-space: nowrap;"><input class="%s" id="%s" type="%s" name="%s[]" value="%s" %s data-excl="%s" data-contrat-date-debut="%s" data-contrat-date-fin="%s"/> %s %s </label> <br />',
					$id,
					'multicheckReq exclusiveContrat contrat-quantite onlyOneInscription', //multicheckReq
					$id,
					$type,
					'amapress_adhesion_contrat_quants',
					esc_attr( $quantite->ID ),
					checked( in_array( $quantite->ID, $adhesion_quantite_ids ), true, false ),
					esc_attr( $quantite->getContrat_instance()->ID ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_debut() ),
					date_i18n( TitanFrameworkOptionDate::$default_date_format, $contrat_instance->getDate_fin() ),
					$quant_var_editor,
					esc_html( $quantite->getTitle() . ' ( ' . Amapress::formatPrice( $quantite->getPrix_unitaire() ) . ' € ' . $quantite->getPriceUnitDisplay() . ')' )
				);

				$ret .= "<script type='text/javascript'>jQuery('#$id').change(function() {
    var this_option = jQuery(this);
  jQuery('#amapress_adhesion_date_debut').datepicker('option', {minDate:this_option.data('contrat-date-debut'), maxDate: this_option.data('contrat-date-fin')});
  jQuery('#$id_factor').prop('disabled', !this_option.is(':checked'));
})</script>";
			}
			$ret .= '</div>';
		}
	}
	if ( ! $had_contrat ) {
		$ret .= '<p class="adhesion-date-error">La date de début (' . esc_html( date_i18n( 'd/m/Y', $date_debut ) ) . ') est en dehors des dates du contrat associé</p>';
	}
	$ret .= '</fieldset>';

	return $ret;
}

add_action( 'wp_ajax_check_inscription_unique', function () {
	$contrats = $_POST['contrats'];
	$user     = $_POST['user'];
	$post_ID  = $_POST['post_ID'];

	$contrats = array_unique( array_map( 'intval', explode( ',', $contrats ) ) );

	$adhs = array();
	foreach ( $contrats as $contrat ) {
		foreach ( AmapressAdhesion::getUserActiveAdhesions( intval( $user ), $contrat ) as $adh ) {
			if ( $adh->getID() == $post_ID ) {
				continue;
			}

			$adhs[] = $adh;
		}
	}
	if ( empty( $adhs ) ) {
		echo json_encode( true );
	} else {
		echo json_encode( 'L\'amapien possède déjà un contrat de ce type' );
	}

	wp_die();
} );

function amapress_save_adhesion_contrat_quantite_editor( $adhesion_id ) {
	if ( ! empty( $_REQUEST['amapress_adhesion_contrat_vars'] ) ) {
		update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_instance', intval( $_REQUEST['amapress_adhesion_contrat_vars'][0] ) );
	} else if ( isset( $_REQUEST['amapress_adhesion_contrat_panier_vars'] ) ) {
		update_post_meta( $adhesion_id, 'amapress_adhesion_panier_variables', $_REQUEST['amapress_adhesion_contrat_panier_vars'] );
	} else if ( isset( $_REQUEST['amapress_adhesion_contrat_quants'] ) ) {
		$quants = array_map( 'intval', $_REQUEST['amapress_adhesion_contrat_quants'] );
		if ( ! empty( $quants ) ) {
			$first_quant = AmapressContrat_quantite::getBy( $quants[0] );
			update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_instance', $first_quant->getContrat_instance()->ID );
			update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_quantite', $quants );

			if ( isset( $_REQUEST['amapress_adhesion_contrat_quants_factors'] ) ) {
				$factors = array_map( 'intval', $_REQUEST['amapress_adhesion_contrat_quants_factors'] );
				update_post_meta( $adhesion_id, 'amapress_adhesion_contrat_quantite_factors', $factors );
			}
		}
	}
}

//add_filter('amapress_can_delete_contrat_adhesion', 'amapress_can_delete_contrat_adhesion', 10, 2);
//function amapress_can_delete_contrat_adhesion($can, $post_id) {
//    return false;
//}

add_action( 'amapress_row_action_adhesion_renew', 'amapress_row_action_adhesion_renew' );
function amapress_row_action_adhesion_renew( $post_id ) {
	$adhesion     = AmapressAdhesion::getBy( $post_id );
	$new_adhesion = $adhesion->cloneAdhesion();
	if ( ! $new_adhesion ) {
		wp_die( 'Une erreur s\'est produit lors du renouvèlement de l\'adhésion. Veuillez réessayer' );
	}

	wp_redirect_and_exit( admin_url( "post.php?post={$new_adhesion->ID}&action=edit" ) );
}

add_action( 'amapress_row_action_adhesion_generate_contrat', 'amapress_row_action_adhesion_generate_contrat' );
function amapress_row_action_adhesion_generate_contrat( $post_id ) {
	$adhesion       = AmapressAdhesion::getBy( $post_id );
	$full_file_name = $adhesion->generateContratDoc();
	$file_name      = basename( $full_file_name );
	Amapress::sendDocumentFile( $full_file_name, $file_name );
}

add_action( 'amapress_row_action_adhesion_send_confirmation', 'amapress_row_action_adhesion_send_confirmation' );
function amapress_row_action_adhesion_send_confirmation( $post_id ) {
	$adhesion = AmapressAdhesion::getBy( $post_id );
	$adhesion->sendConfirmationMail();
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_adhesion_close', 'amapress_row_action_adhesion_close' );
function amapress_row_action_adhesion_close( $post_id ) {
	$adhesion = AmapressAdhesion::getBy( $post_id );
	$adhesion->markClosed();
	wp_redirect_and_exit( wp_get_referer() );
}

add_filter( 'amapress_row_actions_adhesion', 'amapress_row_actions_adhesion', 10, 2 );
function amapress_row_actions_adhesion( $actions, $adhesion_id ) {
	$adh = AmapressAdhesion::getBy( $adhesion_id );

//    $contrat_instance_id = $adh->getContrat_instanceId();
//    $contrat_instances_ids = AmapressContrats::get_active_contrat_instances_ids_by_contrat($adh->getContrat_instance()->getModel()->ID,
//        null, true);
//    $contrat_instances_ids = array_filter(
//        $contrat_instances_ids,
//        function($id) use ($contrat_instance_id) {
//            return $id != $contrat_instance_id;
//        }
//    );

	if ( ! $adh->canRenew() ) {
		unset( $actions['renew'] );
		unset( $actions['no_renew'] );
	}

	return $actions;
}

//function amapress_echo_all_contrat_quantite() {
//	$ret    = '';
//	$ret    .= '<div><ul class="nav nav-tabs" role="tablist">';
//	$active = 'active';
//	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat_instance ) {
//		$ret    .= '<li role="presentation" class="'.$active.'"><a href="#contrat-instance-'.$contrat_instance->ID.'" aria-controls="'.$contrat_instance->ID . '" role="tab" data-toggle="tab">'.esc_html($contrat_instance->getTitle()).'</a></li>';
//		$active = '';
//	}
//	$ret .= '</ul>';
//
//	$ret    .= '<div class="tab-content">';
//	$active = 'active';
//	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
//		$ret    .= '<div role = "tabpanel" class="tab-pane '.$active.'" id="contrat-instance-' . $contrat_instance->ID . '" >'.
//		           amapress_get_contrat_quantite_datatable( $contrat_instances_id ) .'</div >';
//		$active = '';
//	}
//	$ret .= '</div >';
//
//	return $ret;
//}

function amapress_get_contrat_quantite_datatable(
	$contrat_instance_id,
	$lieu_id = null,
	$date = null,
	$options = array()
) {

	$options = wp_parse_args(
		$options,
		array(
			'show_next_distrib'       => true,
			'show_contact_producteur' => true,
			'show_adherents'          => true,
			'show_equiv_quantite'     => true,
			'no_script'               => false,
			'mode'                    => 'both',
		)
	);

	$show_adherents      = $options['show_adherents'];
	$show_equiv_quantite = $options['show_equiv_quantite'];

	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );

	$columns = array(
		array(
			'title' => 'Quantité',
			'data'  => array(
				'_'    => 'quant',
				'sort' => 'quant',
			)
		),
	);
	$lieux   = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$columns[] = array(
				'title' => $lieu->getShortName(),
				'data'  => array(
					'_'    => "lieu_{$lieu->ID}",
					'sort' => "lieu_{$lieu->ID}",
				)
			);
		}
	}
	$columns[] = array(
		'title' => 'Tous',
		'data'  => array(
			'_'    => 'all',
			'sort' => 'all',
		)
	);

	$data      = array();
	$adhesions = AmapressContrats::get_active_adhesions( $contrat_instance_id, null, $lieu_id, $date, true );
	$quants    = AmapressContrats::get_contrat_quantites( $contrat_instance_id );
	$quants[]  = null;
	foreach ( $quants as $quant ) {
		/** @var AmapressContrat_quantite $quant */
		$row          = array();
		$quant_title  = $quant ? $quant->getTitle() : '-toutes-';
		$row['quant'] = $quant ? $quant->getTitle() : '¤-Toutes-¤';
		$quand_id     = $quant ? $quant->getID() : 0;
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu ) {
				$lieu_quant_adh_count = 0;
				$lieu_quant_count     = 0;
				$lieu_quant_sum       = 0;
				foreach ( $adhesions as $adh ) {
					if ( $adh->getLieuId() != $lieu->ID ) {
						continue;
					}
					if ( empty( $quand_id ) ) {
						$lieu_quant_adh_count += 1;
					}
					if ( $contrat_instance->isPanierVariable() ) {
						foreach ( $adh->getVariables_Contrat_quantites( $date ) as $adh_quant ) {
							if ( ! empty( $quand_id ) && $adh_quant['contrat_quantite']->ID != $quand_id ) {
								continue;
							}

							if ( ! empty( $quand_id ) ) {
								$lieu_quant_adh_count += 1;
							}
							$lieu_quant_count += 1;
							$lieu_quant_sum   += $adh_quant['quantite'];
						}
					} else {
						foreach ( $adh->getContrat_quantites( $date ) as $adh_quant ) {
							if ( ! empty( $quand_id ) && $adh_quant->getId() != $quand_id ) {
								continue;
							}

							if ( ! empty( $quand_id ) ) {
								$lieu_quant_adh_count += 1;
							}
							$lieu_quant_count += $adh_quant->getFactor();
							$lieu_quant_sum   += $adh_quant->getQuantite();
						}
					}
				}
				if ( empty( $lieu_quant_adh_count ) ) {
					$row["lieu_{$lieu->ID}"]     = '';
					$row["lieu_{$lieu->ID}_txt"] = '';
				} else if ( abs( $lieu_quant_sum ) < 0.001 || ! $show_equiv_quantite ) {
					$row["lieu_{$lieu->ID}"]     = ( $show_adherents ?
							"$lieu_quant_adh_count adhérents ; " : '' ) . "$lieu_quant_count x $quant_title";
					$row["lieu_{$lieu->ID}_txt"] = "$lieu_quant_count x $quant_title";
				} else {
					$row["lieu_{$lieu->ID}"]     = ( $show_adherents ?
							"$lieu_quant_adh_count adhérents ; " : '' ) . "$lieu_quant_count x $quant_title <i>équivalent quantité : $lieu_quant_sum</i>";
					$row["lieu_{$lieu->ID}_txt"] = "$lieu_quant_count x $quant_title";
				}
			}
		}
		$all_quant_adh_count = 0;
		$all_quant_count     = 0;
		$all_quant_sum       = 0;
		foreach ( $adhesions as $adh ) {
			if ( empty( $quand_id ) ) {
				$all_quant_adh_count += 1;
			}
			foreach ( $adh->getContrat_quantites( $date ) as $adh_quant ) {
				if ( ! empty( $quand_id ) && $adh_quant->getId() != $quand_id ) {
					continue;
				}

				if ( ! empty( $quand_id ) ) {
					$all_quant_adh_count += 1;
				}
				$all_quant_count += $adh_quant->getFactor();
				$all_quant_sum   += $adh_quant->getQuantite();
			}
		}
		if ( empty( $all_quant_adh_count ) ) {
			$row['all']     = '';
			$row['all_txt'] = '';
		} else if ( abs( $all_quant_sum ) < 0.001 || ! $show_equiv_quantite ) {
			$row['all']     = ( $show_adherents ? "$all_quant_adh_count adhérents ; " : '' ) .
			                  "$all_quant_count x $quant_title";
			$row['all_txt'] = "$all_quant_count x $quant_title";
		} else {
			$row['all']     = ( $show_adherents ? "$all_quant_adh_count adhérents ; " : '' ) .
			                  "$all_quant_count x $quant_title <em>équivalent quantité : $all_quant_sum</em>";
			$row['all_txt'] = "$all_quant_count x $quant_title";
		}
		$data[] = $row;
	}

//	<h4>' . esc_html( $contrat_instance->getTitle() ) . '</h4>

	/** @var AmapressDistribution $dist */
	$next_distribs = AmapressDistribution::get_next_distributions( amapress_time(), 'ASC' );
	$dist          = null;
	foreach ( $next_distribs as $distrib ) {
		if ( in_array( $contrat_instance_id, $distrib->getContratIds() ) && ( empty( $lieu_id ) || $distrib->getLieuId() == $lieu_id ) ) {
			$dist = $distrib;
			break;
		}
	}

	$next_distrib_text = '';
	if ( $options['show_next_distrib'] ) {
		$next_distrib_text = '<p>Prochaine distribution: ' .
		                     ( $dist ? Amapress::makeLink( $dist->getPermalink(),
			                     (
			                     Amapress::end_of_week( amapress_time() ) > $dist->getDate() ?
				                     '<strong>Cette semaine</strong> - ' :
				                     '' ) . date_i18n( 'd/m/Y H:i', $dist->getStartDateAndHour() ), false ) : 'non planifiée' ) . '</p>';
	}
	$contact_producteur = '';
	if ( $options['show_contact_producteur'] ) {
		$contact_producteur = '<div><h5>Contact producteur:</h5>' .
		                      $contrat_instance->getModel()->getProducteur()->getUser()->getDisplay(
			                      array(
				                      'show_avatar' => 'false',
				                      'show_tel'    => 'force',
				                      'show_sms'    => 'force',
				                      'show_email'  => 'force',
				                      'show_roles'  => 'false',
			                      ) ) . '</div>';
	}

	$output = '';
	if ( 'table' == $options['mode'] || 'both' == $options['mode'] ) {
		$output .= amapress_get_datatable( 'contrat-instance-recap-' . $contrat_instance_id,
			$columns, $data,
			array(
				'paging'       => false,
				'bSort'        => true,
				'init_as_html' => true,
				'no_script'    => $options['no_script'],
			),
			array(
				Amapress::DATATABLES_EXPORT_EXCEL,
				Amapress::DATATABLES_EXPORT_PRINT
			) );
	}
	if ( 'text' == $options['mode'] || 'both' == $options['mode'] ) {
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu ) {
				$output        .= '<p>A ' . esc_html( $lieu->getShortName() ) . ' : ';
				$output_quants = [];
				foreach ( $data as $row ) {
					if ( ! empty( $row["lieu_{$lieu->ID}_txt"] ) ) {
						$output_quants[] = esc_html( $row["lieu_{$lieu->ID}_txt"] );
					}
				}
				$output .= implode( ', ', $output_quants );
				$output .= '</p>';
			}
		}
		$output        .= '<p>En tout : ';
		$output_quants = [];
		foreach ( $data as $row ) {
			if ( ! empty( $row["all_txt"] ) ) {
				$output_quants[] = esc_html( $row["all_txt"] );
			}
		}
		$output .= implode( ', ', $output_quants );
		$output .= '</p>';
	}

	return '<div class="contrat-instance-recap contrat-instance-' . $contrat_instance_id . '">' .
	       $next_distrib_text .
	       $contact_producteur .
	       //	       '<p class="producteur">' . $contrat_instance->getModel()->getProducteur()->getUser()->getDisplay() . '</p>' .
	       $output . '</div>';
}

//function amapress_echo_all_contrat_paiements_by_date() {
//	$ret = '';
//	foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
//		$ret .= amapress_get_paiement_table_by_dates( $contrat_instances_id );
//	}
//
//	return $ret;
//}

function amapress_get_paiement_table_by_dates(
	$contrat_instance_id,
	$lieu_id = null,
	$options = array()
) {

	$options = wp_parse_args(
		$options,
		array(
			'show_next_distrib'       => true,
			'show_contact_producteur' => true,
			'show_all_dates'          => true,
			'for_pdf'                 => false,
		)
	);

	$for_pdf         = $options['for_pdf'];
	$lien_export_pdf = '';
	if ( ! $for_pdf ) {
		$lien_export_pdf = '<p>';
		$lien_export_pdf .= '<a class="button button-primary" href="' . esc_attr( admin_url( 'admin-post.php?action=paiement_table_pdf&lieu=' . $lieu_id . '&contrat=' . $contrat_instance_id ) ) . '">Exporter en PDF</a>';
		$lien_export_pdf .= '<a class="button button-primary" href="' . esc_attr( admin_url( 'admin-post.php?action=paiement_table_xlsx&lieu=' . $lieu_id . '&contrat=' . $contrat_instance_id ) ) . '">Exporter en Excel</a>';
		$lien_export_pdf .= '</p>';
	}

	$title      = 'Calendrier des paiements';
	$lieu_title = '';
	if ( ! empty( $lieu_id ) ) {
		$lieu       = AmapressLieu_distribution::getBy( $lieu_id );
		$title      .= ' - ' . $lieu->getTitle();
		$lieu_title = '<h1>' . esc_html( $lieu->getTitle() ) . '</h1>';
	}
	$contrat_instance = AmapressContrat_instance::getBy( $contrat_instance_id );
	$paiements        = AmapressContrats::get_all_paiements( $contrat_instance_id, null, $lieu_id );
	$user_ids         = array_map( function ( $p ) {
		/** @var AmapressAmapien_paiement $p */
		return $p->getAdhesion()->getAdherentId();
	}, $paiements );
	update_meta_cache( 'user', $user_ids );
	cache_users( $user_ids );
	$dates = array_map(
		function ( $p ) {
			/** @var AmapressAmapien_paiement $p */
			return $p->getDate();
		}, $paiements );
	$dates = array_merge( $dates, $contrat_instance->getPaiements_Liste_dates() );
	$dates = array_unique( $dates );
	if ( ! $options['show_all_dates'] ) {
		$dates = array_filter( $dates, function ( $d ) {
			return $d > Amapress::start_of_day( amapress_time() );
		} );
	}
	$dates = array_filter( $dates, function ( $d ) {
		return ! empty( $d );
	} );
	sort( $dates );
	$emetteurs = array_map(
		function ( $p ) use ( $paiements ) {
			/** @var AmapressAmapien_paiement $p */
			$all_emetteurs =
				array_map(
					function ( $op ) {
						/** @var AmapressAmapien_paiement $op */
						return $op->getEmetteur();
					},
					array_filter(
						$paiements,
						function ( $op ) use ( $p ) {
							/** @var AmapressAmapien_paiement $op */
							return $op->getAdhesionId() == $p->getAdhesionId();
						}
					)
				);
			if ( $p->getAdhesion()->getAdherent() ) {
				$all_emetteurs[] = $p->getAdhesion()->getAdherent()->getDisplayName();
			}
			if ( $p->getAdhesion()->getAdherent2() ) {
				$all_emetteurs[] = $p->getAdhesion()->getAdherent2()->getDisplayName();
			}
			if ( $p->getAdhesion()->getAdherent3() ) {
				$all_emetteurs[] = $p->getAdhesion()->getAdherent3()->getDisplayName();
			}
			$all_emetteurs = array_unique( $all_emetteurs );
			$all_emetteurs = array_filter( $all_emetteurs,
				function ( $em ) {
					return ! empty( $em );
				} );
			sort( $all_emetteurs );
			$all_emetteurs = array_map(
				function ( $em ) use ( $p ) {
					if ( $em == $p->getEmetteur() ) {
						return '<strong>[' . esc_html( $em ) . ']</strong>';
					} else {
						return esc_html( $em );
					}
				},
				$all_emetteurs
			);

			$date_debut = date_i18n( 'd/m/Y', $p->getAdhesion()->getDate_debut() );
			if ( $p->getAdhesion()->hasDate_fin() ) {
				$date_debut .= '>' . date_i18n( 'd/m/Y', $p->getAdhesion()->getDate_fin() );
			}

			return array(
				'emetteur'       => $p->getEmetteur(),
				'banque'         => $p->getBanque(),
				'last_name'      => $p->getAdhesion()->getAdherent()->getUser()->last_name,
				'lieu'           => $p->getAdhesion()->getLieu()->getShortName(),
				'quantite'       => $p->getAdhesion()->getContrat_quantites_Codes_AsString(),
				'label'          => implode( ', ', $all_emetteurs ),
				'href'           => $p->getAdhesion()->getAdminEditLink(),
				'date_debut'     => $date_debut,
				'date_debut_raw' => $p->getAdhesion()->getDate_debut()
			);
		}, $paiements );
	$emitters  = array();
	foreach ( $emetteurs as $emetteur ) {
		$emitters[ $emetteur['emetteur'] ] = $emetteur;
	}
	usort( $emitters,
		function ( $a, $b ) {
			$a_emetteur = $a['last_name'];
			$b_emetteur = $b['last_name'];
			if ( $a_emetteur == $b_emetteur ) {
				return 0;
			}

			return $a_emetteur < $b_emetteur ? - 1 : 1;
		} );

	$columns = [];
	if ( empty( $lieu_id ) ) {
		$columns[] =
			array(
				'title' => 'Lieu',
				'data'  => 'lieu',
			);
	}
	$columns[] =
		array(
			'title' => 'Nom',
			'data'  => 'last_name',
		);
	$columns[] =
		array(
			'title' => 'Début',
			'data'  => 'date_debut',
		);
	$columns[] =
		array(
			'title' => 'Emetteur',
			'data'  => 'emetteur',
		);
	$columns[] =
		array(
			'title' => 'Quantité',
			'data'  => 'quantite',
		);
	$columns[] =
		array(
			'title' => 'Banque',
			'data'  => 'banque',
		);

	foreach ( $dates as $date ) {
		$columns[] = array(
			'title' => Amapress::makeLink( admin_url( 'edit.php?post_type=amps_cont_pmt&amapress_contrat_inst=' . $contrat_instance_id . '&amapress_date=' . date( 'Y-m-d', $date ) . '&amapress_lieu=' . $lieu_id ), date_i18n( 'd/m/Y', $date ), true, true ),
			'data'  => "date_{$date}",
		);
	}

	$data = array();
	foreach ( $emitters as $emetteur_obj ) {
		$emetteur           = $emetteur_obj['emetteur'];
		$emetteur_label     = $emetteur_obj['label'];
		$emetteur_href      = $emetteur_obj['href'];
		$row                = array(
			'emetteur'   => Amapress::makeLink( $emetteur_href, $emetteur_label, false ),
			'last_name'  => esc_html( $emetteur_obj['last_name'] ),
			'lieu'       => esc_html( $emetteur_obj['lieu'] ),
			'banque'     => esc_html( $emetteur_obj['banque'] ),
			'quantite'   => esc_html( $emetteur_obj['quantite'] ),
			'date_debut' => Amapress::makeLink( admin_url( 'edit.php?post_type=amps_adhesion&amapress_contrat_inst=' . $contrat_instance_id . '&amapress_date=' . date( 'Y-m', $emetteur_obj['date_debut_raw'] ) . '&amapress_lieu=' . $lieu_id ), $emetteur_obj['date_debut'], true, true ),
		);
		$emetteur_paiements = array_filter(
			$paiements,
			function ( $p ) use ( $emetteur ) {
				/** @var AmapressAmapien_paiement $p */
				return $p->getEmetteur() == $emetteur;
			}
		);

		foreach ( $dates as $date ) {
			$emetteur_date_paiements = array_filter(
				$emetteur_paiements,
				function ( $p ) use ( $date ) {
					/** @var AmapressAmapien_paiement $p */
					return $p->getDate() == $date;
				}
			);

			$contrat_adhesion = null;
			if ( count( $emetteur_paiements ) > 0 ) {
				$emetteur_paiements2 = array_values( $emetteur_paiements );
				$contrat_adhesion    = array_shift( $emetteur_paiements2 )->getAdhesion();
			}
			if ( $contrat_adhesion && $date < $contrat_adhesion->getDate_debut() ) {
				$val = [
					'value' => '&gt;&gt;&gt;',
					'style' => 'background-color: #ccc;',
				];
			} else if ( $contrat_adhesion && $date > $contrat_adhesion->getDate_fin() ) {
				$val = [
					'value' => '&lt;&lt;&lt;',
					'style' => 'background-color: #ccc;',
				];
			} else {
				$val = implode( ',', array_filter(
					array_map(
						function ( $p ) use ( $emetteur_obj ) {
							/** @var AmapressAmapien_paiement $p */
							$banque = $p->getBanque();
							if ( ! empty( $banque ) && $emetteur_obj['banque'] != $banque ) {
								return esc_html( "{$p->getNumero()} ({$banque})" );
							} else {
								return esc_html( "{$p->getNumero()}" );
							}
						}, $emetteur_date_paiements )
					, function ( $e ) {
					return ! empty( $e );
				} ) );
			}
			$row["date_{$date}"] = $val;
		}
		$data[] = $row;
	}

	$next_distribs = AmapressDistribution::get_next_distributions( amapress_time(), 'ASC' );
	$dist          = null;
	foreach ( $next_distribs as $distrib ) {
		if ( in_array( $contrat_instance_id, $distrib->getContratIds() ) && ( empty( $lieu_id ) || $distrib->getLieuId() == $lieu_id ) ) {
			$dist = $distrib;
			break;
		}
	}

	$id = "contrat-$contrat_instance_id-paiements-month-$lieu_id";
	$fn = "contrat_{$contrat_instance_id}_paiements_month_$lieu_id";

	$next_distrib_text = '';
	if ( $options['show_next_distrib'] ) {
		$next_distrib_text = '<p>Prochaine distribution: ' . ( $dist ? ( $dist && Amapress::end_of_week( amapress_time() ) > $dist->getDate() ? '<strong>Cette semaine</strong> - ' : '' ) . date_i18n( 'd/m/Y H:i', $dist->getStartDateAndHour() ) : 'non planifiée' ) . '</p>';
	}
	$contact_producteur = '';
	if ( $options['show_contact_producteur'] ) {
		$contact_producteur = '<div><h5>Contact producteur:</h5>' .
		                      $contrat_instance->getModel()->getProducteur()->getUser()->getDisplay(
			                      array(
				                      'show_avatar' => 'false',
				                      'show_tel'    => 'force',
				                      'show_sms'    => 'force',
				                      'show_email'  => 'force',
				                      'show_roles'  => 'false',
			                      ) ) . '</div>';
	}

	$ret = '<div class="contrat-instance-recap contrat-instance-' . $contrat_instance_id . '">
' . $lieu_title . $next_distrib_text . $contact_producteur . $lien_export_pdf;
	$ret .= amapress_get_datatable(
		$id,
		$columns, $data,
		array(
			'bSort'          => true,
			'paging'         => false,
			'searching'      => true,
			'bAutoWidth'     => true,
			'responsive'     => false,
			'scrollX'        => true,
			'scrollY'        => '250px',
			'scrollCollapse' => true,
			'cell-border'    => true,
			'fixedColumns'   => array( 'leftColumns' => 1 ),
			'fixedHeader'    => true,
			//			       'initComplete' => $fn,
			'init_as_html'   => true,
			'no_script'      => $for_pdf,
//			       'dom'          => 'Bfrtip',
//			       'buttons'      => [],
		)
	);
	if ( $for_pdf ) {
		$ret .= '<style type="text/css">
a {
	color: black;
	text-decoration: none;
}
table, td { 
	border: 1px solid black; 
	border-collapse: collapse; 
	padding: 2px; 
	font-size: 8pt;
}
.odd { 
	background-color: #EEEEEE; 
}
th {
	border: 1px solid black; 
	border-collapse: collapse; 
	padding: 2px; 
	font-size: 8pt;
	font-weight: bold;
}
</style>';
	} else {
//		$ret .= '
//<script type="text/javascript">
//function ' . $fn . '() {
//var table = jQuery(\'#' . $id . '\').DataTable();
//new jQuery.fn.dataTable.Buttons( table, {
//    buttons: [
//        \'excel\',
//        {
//            extend: \'print\',
//            text: \'Imprimer\',
//            title: \'' . $title . '\',
//            autoPrint: false,
//            exportOptions: {
//                columns: \':visible\',
//                stripHtml: false,
//            },
//            customize: function (win) {
//                jQuery(win.document.body).css(\'background-color\', \'#fff\');
//                jQuery(win.document.body).find(\'table\').addClass(\'display\');/*.css(\'font-size\', \'9px\');*/
//                jQuery(win.document.body).find(\'table td\').css(\'border\', \'1px solid black\');
//                jQuery(win.document.body).find(\'tr:nth-child(odd) td\').each(function(index){
//                    jQuery(this).css(\'background-color\',\'#eee\');
//                });
//                jQuery(win.document.body).find(\'a\').css(\'color\',\'black\').css(\'text-decoration\',\'none\');
//            }
//        }
//    ]
//} );
//    table.buttons( 0, null ).container().prependTo(
//        table.table().container()
//    );
//}
//</script>';
	}
	$ret .= '</div>';

	return $ret;
}

add_action( 'tf_custom_admin_amapress_action_existing_user', function () {
	wp_redirect_and_exit( add_query_arg( 'user_id', $_POST['user_id'] ) );
} );
add_action( 'tf_custom_admin_amapress_action_new_user', function () {
	$email      = $_POST['email'];
	$last_name  = $_POST['last_name'];
	$first_name = $_POST['first_name'];
	$address    = $_POST['address'];
	$tel        = $_POST['tel'];

	$user_id = amapress_create_user_if_not_exists( $email, $first_name, $last_name, $address, $tel, 'user' );

	wp_redirect_and_exit( add_query_arg( 'user_id', $user_id ) );
} );

function amapress_create_user_and_adhesion_assistant( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		if ( isset( $_REQUEST['assistant'] ) ) {
			echo do_shortcode( '[inscription-en-ligne admin_mode=true]' );
		} else {

			echo '<h4>2/ Inscription</h4>';

			$user = AmapressUser::getBy( $_REQUEST['user_id'] );

			echo '<hr />';
			echo $user->getDisplay();
			echo '<p>' . Amapress::makeButtonLink( $user->getEditLink(), 'Modifier', true, true ) . '</p>';
			echo '<hr />';


			Amapress::setFilterForReferent( false );
			$adhs = AmapressAdhesion::getUserActiveAdhesions( $user->ID );
			Amapress::setFilterForReferent( true );
			usort( $adhs, function ( $a, $b ) {
				return strcmp( $a->getTitle(), $b->getTitle() );
			} );
			echo '<p><strong>Ses contrats :</strong></p>';
			echo '<ul style="list-style-type: circle">';
			foreach ( $adhs as $adh ) {
				$renew_url = '';
				if ( Amapress::start_of_day( $adh->getDate_fin() ) < Amapress::start_of_day( amapress_time() ) ) {
					$renew_url = 'edit.php?post_type=amps_adhesion&action=renew&amp_id=' . $adh->ID;
					$renew_url = esc_url( wp_nonce_url( $renew_url,
							"renew_{$adh->ID}" )
					);
				}
				echo '<li style="margin-left: 35px">';
				$lnk = current_user_can( 'edit_post', $adh->ID ) ?
					'<a target="_blank" href="' . esc_attr( $adh->getAdminEditLink() ) . '" >Voir</a>&nbsp;:&nbsp;' : '';
				echo $lnk . esc_html( $adh->getTitle() );
				if ( ! empty( $renew_url ) ) {
					echo '<br/><a target="_blank" href="' . $renew_url . '" class="button button-secondary">Renouveller</a>';
				}
				echo '</li>';
			}
			if ( empty( $adhs ) ) {
				echo '<li>Aucun contrat</li>';
			}
			echo '</ul>';

			$add_url = add_query_arg( 'assistant', true );
			echo '<p><a target="_blank" href="' . $add_url . '" class="button button-secondary">Inscription avec l\'assistant</a></p>';
			echo '<br />';

			$add_url = admin_url( 'post-new.php?post_type=amps_adhesion&amapress_adhesion_adherent=' . $user->ID );
			echo '<h4>Configuration avancée</h4>';
			echo '<p><a target="_blank" href="' . $add_url . '" class="button button-secondary">Inscription classique</a></p>';
		}
	} else {
		echo '<h4>1/ Choisir un utilisateur ou le créer</h4>';
		$options = [];
		Amapress::setFilterForReferent( false );
		$all_user_adhs = AmapressContrats::get_active_adhesions();
		Amapress::setFilterForReferent( true );
		/** @var WP_User $user */
		foreach ( get_users() as $user ) {
			$user_adhs            = from( $all_user_adhs )
				->count( function ( $a ) use ( $user ) {
					/** @var AmapressAdhesion $a */
					return $a->getAdherentId() == $user->ID;
				} );
			$options[ $user->ID ] = $user->display_name . '[' . $user->user_email . '] (' . $user_adhs . ' contrat(s))';
		}

		echo '<form method="post" id="existing_user">';
		echo '<input type="hidden" name="action" value="existing_user" />';
		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<select style="max-width: none; min-width: 50%;" id="user_id" name="user_id" class="autocomplete" data-placeholder="Sélectionner un utilisateur">';
		tf_parse_select_options( $options, isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null );
		echo '</select><br />';
		echo '<input type="submit" class="button button-primary" value="Choisir" />';
		echo '</form>';

		echo '<p><strong>OU</strong></p>';

		echo '<form method="post" id="new_user">';
		echo '<input type="hidden" name="action" value="new_user" />';
		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">Email: </label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="required email emailDoesNotExists" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">Nom: </label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">Prénom: </label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">Téléphone: </label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">Adresse: </label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%" type="submit" class="button button-primary" value="Créer l\'amapien" />';
		echo '</form>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( [
			'user_id',
			'step',
			'assistant'
		] ) . '" class="button button-primary">Choisir un autre amapien</a></p>';
	echo '<script type="text/javascript">jQuery(function() {
    jQuery("#user_id").select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
		return markup;
	},
		  templateResult: function(data) {
		return jQuery("<span>"+data.text+"</span>");
	},
		  templateSelection: function(data) {
		return jQuery("<span>"+data.text+"</span>");
	},
    });
    jQuery("form#new_user").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
}

add_action( 'tf_custom_admin_amapress_action_new_user_distrib', function () {
	$email = $_POST['email'];
	if ( empty( $email ) ) {
		$email = uniqid( 'm' ) . '@nomail.org';
	}
	$last_name  = $_POST['last_name'];
	$first_name = $_POST['first_name'];
	$address    = $_POST['address'];
	$tel        = $_POST['tel'];

	$user_id = amapress_create_user_if_not_exists( $email, $first_name, $last_name, $address, $tel, 'user' );

	wp_redirect_and_exit( add_query_arg( 'user_id', $user_id ) );
} );
function amapress_create_user_for_distribution( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		echo '<h4>Utilisateur créé:</h4>';

		$user = AmapressUser::getBy( $_REQUEST['user_id'] );

		echo '<hr />';
		echo $user->getDisplay();
		echo '<hr />';
	} else {
		echo '<h4>Entrer les informations sur la personne hors AMAP</h4>';
		echo '<form method="post" id="new_user_distrib">';
		echo '<input type="hidden" name="action" value="new_user_distrib" />';
		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">Email: </label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="email emailDoesNotExists" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">Nom: </label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">Prénom: </label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">Téléphone: </label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">Adresse: </label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%" type="submit" class="button button-primary" value="Créer la personne" />';
		echo '</form>';
		echo '<script type="text/javascript">jQuery(function() {
    jQuery("form#new_user_distrib").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( 'user_id' ) . '" class="button button-primary">Ajouter une autre personne</a></p>';
}

add_action( 'tf_custom_admin_amapress_action_new_coadherent', function () {
	$email      = $_POST['email'];
	$last_name  = $_POST['last_name'];
	$first_name = $_POST['first_name'];
	$address    = $_POST['address'];
	$tel        = $_POST['tel'];

	$co_id = amapress_create_user_if_not_exists( $email, $first_name, $last_name, $address, $tel, 'user' );

	$user_id = intval( $_REQUEST['user_id'] );
	$user    = AmapressUser::getBy( $user_id );
	$user->addCoadherent( $co_id );

	wp_redirect_and_exit( add_query_arg( 'user_id', $user_id ) );
} );
function amapress_create_ooadhesion_assistant( $post_id, TitanFrameworkOption $option ) {
	if ( isset( $_REQUEST['user_id'] ) ) {
		$user_id = intval( $_REQUEST['user_id'] );
		$user    = AmapressUser::getBy( $user_id );

		echo '<h4>3/ Adhérent principal</h4>';
		echo '<hr />';
		echo $user->getDisplay();
		echo '<hr />';
		echo '<h4>4/ Coadhérents :</h4>';

		foreach ( AmapressContrats::get_related_users( $user_id ) as $co_id ) {
			if ( $co_id == $user_id ) {
				continue;
			}
			$co = AmapressUser::getBy( $co_id );
			echo $co->getDisplay();
		}
	} else {
		echo '<form method="post" id="new_coadherent">';
		echo '<input type="hidden" name="action" value="new_coadherent" />';

		echo '<h4>1/ Choisir l\'adhérent principal</h4>';
		$options       = [];
		$all_user_adhs = AmapressContrats::get_active_adhesions();
		/** @var WP_User $user */
		foreach ( get_users() as $user ) {
			$user_adhs            = from( $all_user_adhs )
				->count( function ( $a ) use ( $user ) {
					/** @var AmapressAdhesion $a */
					return $a->getAdherentId() == $user->ID;
				} );
			$options[ $user->ID ] = $user->display_name . '[' . $user->user_email . '] (' . $user_adhs . ' contrat(s))';
		}

		echo '<select style="max-width: none; min-width: 50%;" id="user_id" name="user_id" class="autocomplete required" data-placeholder="Sélectionner un utilisateur">';
		tf_parse_select_options( $options, isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null );
		echo '</select><br />';

		echo '<p><strong>2/ Saisir les coordonnées du coadhérent :</strong></p>';

		wp_nonce_field( 'amapress_gestion_amapiens_page', TF . '_nonce' );
		echo '<table style="min-width: 50%">';
		echo '<tr>';
		echo '<th style="text-align: left; width: auto"><label style="width: 10%" for="email">Email: </label></th>
<td><input style="width: 100%" type="text" id="email" name="email" class="required email" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="last_name">Nom: </label></th>
<td><input style="width: 100%" type="text" id="last_name" name="last_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="first_name">Prénom: </label></th>
<td><input style="width: 100%" type="text" id="first_name" name="first_name" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="tel">Téléphone: </label></th>
<td><input style="width: 100%" type="text" id="tel" name="tel" class="required" />';
		echo '</tr><tr>';
		echo '<th style="text-align: left; width: auto"><label for="address">Adresse: </label></th>
<td><textarea style="width: 100%" rows="8" id="address" name="address" class=""></textarea>';
		echo '</tr>';
		echo '</table>';
		echo '<input style="min-width: 50%" type="submit" class="button button-primary" value="Ajouter le coadhérent" />';
		echo '</form>';
	}
	echo '<hr />';
	echo '<p><a href="' . remove_query_arg( 'user_id' ) . '" class="button button-primary">Associer un autre amapien</a></p>';
	echo '<script type="text/javascript">jQuery(function() {
    jQuery("#user_id").select2({
        allowClear: true,
		  escapeMarkup: function(markup) {
		return markup;
	},
		  templateResult: function(data) {
		return jQuery("<span>"+data.text+"</span>");
	},
		  templateSelection: function(data) {
		return jQuery("<span>"+data.text+"</span>");
	},
    });
    jQuery("form#new_coadherent").validate({
                onkeyup: false,
        }
    );
});
</script>
<style type="text/css">
	.error {
		font-weight: bold;
		color: red;
	}
</style>';
}

add_action( 'wp_ajax_check_email_exists', function () {
	$email = $_POST['email'];
	$user  = get_user_by( 'email', $email );
	if ( ! $user ) {
		echo json_encode( true );
	} else {
		echo json_encode( 'Cette adresse est déjà utilisée' );
	}

	wp_die();
} );

add_action( 'delete_post', function ( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( AmapressAdhesion::INTERNAL_POST_TYPE == $post_type ) {
		$paiements_ids = get_posts(
			[
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_paiement_adhesion',
						'value'   => $post_id,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				)
			]
		);
		foreach ( $paiements_ids as $id ) {
			wp_delete_post( $id );
		}
	}
}, 1000 );

add_filter( 'hidden_meta_boxes', function ( $hidden ) {
	return array_filter( $hidden,
		function ( $v ) {
			return ! preg_match( '/^\d+\/-/', $v );
		}
	);

	return $hidden;
} );

add_filter( 'amapress_can_edit_adhesion', function ( $can, $post_id ) {
	if ( is_admin() && amapress_can_access_admin() && ! amapress_is_admin_or_responsable() && ! TitanFrameworkOption::isOnNewScreen() ) {
		$refs = AmapressContrats::getReferentProducteursAndLieux();
		if ( count( $refs ) > 0 ) {
			$adhesion = AmapressAdhesion::getBy( $post_id );
			if ( $adhesion ) {
				foreach ( $refs as $r ) {
					if ( in_array( $adhesion->getContrat_instanceId(), $r['contrat_instance_ids'] ) ) {
						return $can;
					}
				}
			}

			return false;
		}
	}

	return $can;
}, 10, 2 );