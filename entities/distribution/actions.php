<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'amapress_do_query_action_distribution_inscr_resp', 'amapress_do_query_action_distribution_inscr_resp' );
function amapress_do_query_action_distribution_inscr_resp() {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$dist_id = get_the_ID();
	$dist    = new AmapressDistribution( $dist_id );

	if ( ! $dist->isUserMemberOf( amapress_current_user_id(), true ) ) {
		wp_die( 'Vous ne faites pas partie de cette distribution.' );
	}

	$redir_url           = get_post_permalink( $dist_id );
	$responsables        = Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_responsables' );
	$needed_responsables = AmapressDistributions::get_required_responsables( $dist_id );
	if ( ! $responsables ) {
		$responsables = array();
	}
	if ( in_array( amapress_current_user_id(), $responsables ) ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'already_in_list' ), $redir_url ) );
	} else if ( count( $responsables ) >= $needed_responsables ) {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'list_full' ), $redir_url ) );
	} else {
		$responsables[] = amapress_current_user_id();
		update_post_meta( $dist_id, 'amapress_distribution_responsables', $responsables );

		amapress_mail_current_user_inscr( new AmapressDistribution( $dist_id ) );

		wp_redirect_and_exit( add_query_arg( array( 'message' => 'inscr_success' ), $redir_url ) );
	}
}

add_action( 'amapress_do_query_action_distribution_desinscr_resp', 'amapress_do_query_action_distribution_desinscr_resp' );
function amapress_do_query_action_distribution_desinscr_resp() {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$dist_id      = get_the_ID();
	$redir_url    = get_post_permalink( $dist_id );
	$responsables = Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_responsables' );
	if ( ! $responsables ) {
		$responsables = array();
	}

	if ( ( $key = array_search( amapress_current_user_id(), $responsables ) ) !== false ) {
		unset( $responsables[ $key ] );

		update_post_meta( $dist_id, 'amapress_distribution_responsables', $responsables );

		amapress_mail_current_user_desinscr( new AmapressDistribution( $dist_id ) );

		wp_redirect_and_exit( add_query_arg( array( 'message' => 'inscr_success' ), $redir_url ) );
	} else {
		wp_redirect_and_exit( add_query_arg( array( 'message' => 'not_inscr' ), $redir_url ) );
	}
}

add_action( 'amapress_do_query_action_distribution_panier_garder', 'amapress_do_query_action_distribution_panier_garder' );
function amapress_do_query_action_distribution_panier_garder() {
	//TODO
}

add_filter( 'amapress_get_custom_title_distribution_liste-emargement', 'amapress_get_custom_title_distribution_liste_emargement' );
function amapress_get_custom_title_distribution_liste_emargement( $content ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$dist_id = get_the_ID();
	$lieu    = get_post( intval( get_post_meta( $dist_id, 'amapress_distribution_lieu', true ) ) );
	$date    = intval( get_post_meta( $dist_id, 'amapress_distribution_date', true ) );
//    $dt = date('Y-m-d', $date);

	$amapress_contrat    = get_query_var( 'amapress_contrat' );
	$amapress_contrat_qt = get_query_var( 'amapress_contrat_qt' );
	//var_dump($amapress_contrat);

	if ( ! empty( $amapress_contrat ) ) {
		$contrat       = get_post( Amapress::resolve_post_id( $amapress_contrat, AmapressContrat::INTERNAL_POST_TYPE ) );
		$contrat_names = array( $contrat->post_title );
	} else {
		$contrat_ids   = Amapress::get_post_meta_array( $dist_id, 'amapress_distribution_contrats' );
		$contrats      = get_posts( array(
			'include' => $contrat_ids
		) );
		$contrat_names = array_map( 'Amapress::to_title', $contrats );
	}

	$content = sprintf( 'Liste d\'émargement de %s du %s', $lieu->post_title, date_i18n( 'd/m/Y', $date ), implode( ', ', $contrat_names ) );

	if ( ! empty( $amapress_contrat_qt ) ) {
		$contrat_qt = get_post( Amapress::resolve_post_id( $amapress_contrat_qt, AmapressContrat_quantite::INTERNAL_POST_TYPE ) );
		$content    .= ' - ' . $contrat_qt->post_title;
	}

	return $content;
}

add_filter( 'amapress_get_query_action_template_distribution_liste-emargement', 'amapress_get_query_action_template_distribution_liste_emargement' );
function amapress_get_query_action_template_distribution_liste_emargement( $template ) {
	$name            = 'liste-emargement.php';
	$exists_in_theme = locate_template( $name, false );
	if ( $exists_in_theme == '' ) {
		$file = AMAPRESS__PLUGIN_DIR . "templates/$name";
		if ( file_exists( $file ) ) {
			return $file;
		}
	} else {
		return $exists_in_theme;
	}

	return $template;
}

add_filter( 'amapress_get_custom_content_distribution_liste-emargement', 'amapress_get_custom_content_distribution_liste_emargement' );
function amapress_get_custom_content_distribution_liste_emargement( $content ) {
	if ( ! amapress_is_user_logged_in() ) {
		wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
	}

	$dist = new AmapressDistribution( get_the_ID() );

	if ( ! AmapressDistributions::isCurrentUserResponsable( $dist->ID )
	     && ! amapress_can_access_admin()
	) {
		wp_die( 'Accès non autorisé' );
	}

//    $lieu_id = intval(get_post_meta($dist_id, 'amapress_distribution_lieu', true));
//    $date = intval(get_post_meta($dist_id, 'amapress_distribution_date', true));
//    $dt = date('Y-m-d', $date);

//    $contrat_ids = Amapress::get_post_meta_array($dist_id, 'amapress_distribution_contrats');
//    $contrat_ids_arr = $contrat_ids;
//    $contrat_ids = implode(',', $contrat_ids);

//    $query_string = "post_type=amps_adhesion&amapress_contrat_inst=$contrat_ids&amapress_date=$dt";
//    $amapress_contrat = get_query_var('amapress_contrat');
//    $amapress_contrat_qt = get_query_var('amapress_contrat_qt');
//    if (!empty($amapress_contrat) && !empty($amapress_contrat_qt))
//        $query_string = "post_type=amps_adhesion&amapress_contrat=$amapress_contrat&amapress_contrat_qt=$amapress_contrat_qt&amapress_date=$dt";
//    else if (!empty($amapress_contrat))
//        $query_string = "post_type=amps_adhesion&amapress_contrat=$amapress_contrat&amapress_date=$dt";

	$columns = array(
		array(
			'title' => 'Nom',
			'data'  => array(
				'_'    => 'last_name',
				'sort' => 'last_name',
			)
		),
		array(
			'title' => 'Prénom',
			'data'  => array(
				'_'    => 'first_name',
				'sort' => 'first_name',
			)
		),
//        array(
//            'title' => 'Adresse',
//            'data' => array(
//                '_' => 'adresse.full',
//                'sort' => 'adresse.ville',
//            )
//        ),
//        array(
//            'title' => 'Email',
//            'data' => array(
//                '_' => 'email',
//                'sort' => 'email',
//            )
//        ),
		array(
			'title' => 'Téléphone',
			'data'  => array(
				'_'    => 'tel',
				'sort' => 'tel',
			)
		),
//        array(
//            'title' => 'Contrat',
//            'data' => array(
//                '_' => 'contrat.link',
//                'sort' => 'contrat.name',
//            )
//        ),
//        array(
//            'title' => 'Quantité',
//            'data' => array(
//                '_' => 'quantite.name',
//                'sort' => 'quantite.name',
//            )
//        ),

	);

	foreach ( $dist->getContrats() as $contrat ) {
		$columns[] = array(
			'title' => $contrat->getModel()->getTitle(),
			'data'  => array(
				'_'    => 'contrat_' . $contrat->ID,
				'sort' => 'contrat_' . $contrat->ID,
			)
		);
	}

//	var_dump($dist);
	$all_adhs = AmapressContrats::get_active_adhesions( $dist->getContratIds(), null, $dist->getLieuId(), $dist->getDate(), true );
	$liste = array();
//    $query = new WP_Query($query_string);
	$adhesions = array_group_by(
		$all_adhs,
		function ( $adh ) {
			/** @var AmapressAdhesion $adh */
			$user_ids = array_unique( AmapressContrats::get_related_users( $adh->getAdherent()->getUser()->ID ) );

			return implode( '_', $user_ids );
		} );

//	var_dump($all_adhs);
	/** @var AmapressAdhesion[] $adhs */
	foreach ( $adhesions as $user_ids => $adhs ) {
		$line = array();

		$user_ids = explode( '_', $user_ids );
		$users    = array_map( function ( $user_id ) {
			return get_user_by( 'ID', intval( $user_id ) );
		}, $user_ids );

		$line['first_name'] = implode( ' / ', array_map( function ( $user ) {
			return $user->first_name;
		}, $users ) );
		$line['last_name']  = implode( ' / ', array_map( function ( $user ) {
			return ! empty( $user->last_name ) ? $user->last_name : $user->display_name;
		}, $users ) );
		$line['tel']        = implode( '<br/>', array_map( function ( $user ) {
			$adh = AmapressUser::getBy( $user );

			return $adh->getTelTo();
		}, $users ) );

		foreach ( $adhs as $adh ) {
			$line[ 'contrat_' . $adh->getContrat_instance()->ID ] = $adh->getContrat_quantites_Codes_AsString( $dist->getDate() );
		}
		foreach ( $dist->getContrats() as $contrat ) {
			if ( ! isset( $line[ 'contrat_' . $contrat->ID ] ) ) {
				$line[ 'contrat_' . $contrat->ID ] = '';
			}
		}

		$liste[] = $line;
	}

	ob_start();
	echo '<style type="text/css">
            p {
                margin: 0 !important;
                padding:0 !important;
            }
            body { margin: 15px; }
            @media print {
                * { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important;}
                #liste-emargement a.contrat { box-shadow: none !important; text-decoration: none !important; color: #000000!important; border: none !important;}
                #paniers-a-echanger a { box-shadow: none !important; text-decoration: none !important; color: #000000!important; border: none !important;}
                a:after {
                    content: \'\' !important;
                }
                .liste-emargement-contrat-variable, .liste-emargement-instructions { page-break-before: always; }
                #liste-emargement_filter { display: none !important}
                #paniers-a-echanger_filter { display: none !important}
                #liste-emargement_info { display: none !important}
                #paniers-a-echanger_info { display: none !important}
                .distrib-resp-missing, .dist-inscrire-button, .dist-desinscrire-button, .btn-print-liste { display: none !important}
                table.distrib-inscr-list { table-layout: fixed  !important}
                .btn-print { display: none !important}
                td, th { padding: 2px !important; line-height: normal !important; }
                body {
                    background-color:#FFFFFF !important;
                    border: none !important;
                    margin: 0 !important;  /* the margin on the content before printing */
                }
                @page { margin: 0 !important; }
            }
            </style>';

	the_title( '<h2>', '</h2>' );
	echo '<br/>';
	echo '<div><a href="javascript:window.print()" class="btn btn-default btn-print">Imprimer</a></div>';
	echo '<br/>';

	echo Amapress::getOption( 'liste-emargement-general-message' );
	echo '<br/>';
	echo $dist->getInformations();
	echo '<br/>';

//    amapress_display_messages_for_post('dist-messages', $dist->ID);

	$query                        = array(//        'status' => 'to_exchange',
	);
	$query['contrat_instance_id'] = $dist->getContratIds();
	$query['date']                = $dist->getDate();
	$paniers                      = AmapressPaniers::getPanierIntermittents( $query );
	if ( count( $paniers ) > 0 ) {
		echo '<h3 class="liste-emargement-intermittent">Panier(s) intermittent(s)</h3>';
		echo amapress_get_paniers_intermittents_table( 'paniers-exchs', $paniers,
			function ( $state, $status, $adh ) {
				return $state;
			},
			array( 'paging' => false, 'searching' => false ),
			array(
				'show_avatar'     => 'false',
				'show_email'      => 'false',
				'show_tel'        => 'default',
				'show_tel_fixe'   => 'default',
				'show_tel_mobile' => 'default',
				'show_adresse'    => 'false',
				'show_roles'      => 'false',
			),
			array(
				'date'      => false,
				'panier'    => true,
				'quantite'  => true,
				'lieu'      => false,
				'prix'      => false,
				'adherent'  => true,
				'repreneur' => true,
				'etat'      => true,
				'for_print' => true,
			) );
	}

	echo '<br/>';
	echo '<h3 class="liste-emargement">Liste</h3>';
	amapress_echo_datatable( 'liste-emargement', $columns, $liste, array( 'paging' => false, 'searching' => false ) );

	foreach ( $dist->getContrats() as $contrat ) {
		if ( $contrat->isPanierVariable() ) {
			$panier_commandes = AmapressPaniers::getPanierVariableCommandes( $contrat->ID, $dist->getDate() );

			if ( ! empty( $panier_commandes['data'] ) ) {
				echo '<br/>';
				echo '<h3 class="liste-emargement-contrat-variable">Détails des paniers - ' . esc_html( $contrat->getTitle() ) . '</h3>';
				amapress_echo_datatable( 'liste-emargement-contrat-variable-' . $contrat->ID,
					$panier_commandes['columns'], $panier_commandes['data'],
					array( 'paging' => false, 'searching' => false ),
					array(
						Amapress::DATATABLES_EXPORT_EXCEL,
						Amapress::DATATABLES_EXPORT_PDF,
						Amapress::DATATABLES_EXPORT_PRINT
					) );
			}
		}
	}

	echo '<br/>';
	echo '<h3 class="liste-emargement-next-resps">' . esc_html( 'Responsables aux prochaines distributions' ) . '</h3>';
	echo do_shortcode( '[inscription-distrib show_past=false show_for_resp=false max_dates=8]' );

	if ( Amapress::toBool( Amapress::getOption( 'liste-emargement-show-lieu-instructions' ) ) ) {
		$lieu = ( $dist->getLieuSubstitution() ? $dist->getLieuSubstitution() : $dist->getLieu() );

		if ( strlen( trim( strip_tags( $lieu->getInstructions_privee() ) ) ) > 0 ) {
			echo '<br/>';
			echo '<h3 class="liste-emargement-instructions">' . esc_html( 'Instructions pour ' . $lieu->getShortName() ) . '</h3>';
			echo $lieu->getInstructions_privee();
		}
	}

	$content = ob_get_contents();
	ob_clean();
	//|amapress_adhesion_adherent,amapress_adhesion_co-adherents|amapress_post=$dist_id|amapress_distribution_date", "Les amapiens inscrit à {$distrib->post_title}", "distribution");

	//$cnt[$lieu_id] -= 1;

	return $content;
}

//add_action('amapress_get_query_action_template_distribution_liste-emargement','amapress_get_query_action_template_distribution_liste_emargement');
//function amapress_get_query_action_template_distribution_liste_emargement($template) {
//    return AMAPRESS__PLUGIN_DIR . 'templates/blank_page.php';
//}