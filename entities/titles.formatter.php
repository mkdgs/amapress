<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_visite_title_formatter', 'amapress_visite_title_formatter', 10, 2 );
function amapress_visite_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$date       = get_post_meta( $post_id, 'amapress_visite_date', true );
	$producteur = get_post( get_post_meta( $post_id, 'amapress_visite_producteur', true ) );

	return sprintf( 'Visite du %s chez %s',
		date_i18n( 'l j F Y', intval( $date ) ),
		$producteur->post_title );
}

add_filter( 'amapress_mailinglist_title_formatter', 'amapress_mailinglist_title_formatter', 10, 2 );
function amapress_mailinglist_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	return get_post_meta( $post_id, 'amapress_mailinglist_name', true );
}

add_filter( 'amapress_distribution_title_formatter', 'amapress_distribution_title_formatter', 10, 2 );
function amapress_distribution_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$date              = get_post_meta( $post_id, 'amapress_distribution_date', true );
	$lieu_id           = get_post_meta( $post_id, 'amapress_distribution_lieu', true );
	$lieu_substitution = get_post_meta( $post_id, 'amapress_distribution_lieu_substitution', true );
	if ( ! empty( $lieu_substitution ) ) {
		$lieu = get_post( intval( $lieu_substitution ) );

		return sprintf( 'Distribution du %s exceptionnellement à %s',
			date_i18n( 'd/m/Y', intval( $date ) ),
			$lieu->post_title );
	} else {
		$lieu = get_post( intval( $lieu_id ) );

		return sprintf( 'Distribution du %s à %s',
			date_i18n( 'd/m/Y', intval( $date ) ),
			$lieu->post_title );
	}
}

add_filter( 'amapress_commande_title_formatter', 'amapress_commande_title_formatter', 10, 2 );
function amapress_commande_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$date          = get_post_meta( $post_id, 'amapress_commande_date_distrib', true );
	$lieu          = get_post( get_post_meta( $post_id, 'amapress_commande_lieu', true ) );
	$contrat_model = get_post( get_post_meta( get_post_meta( $post_id, 'amapress_commande_contrat_instance', true ), 'amapress_contrat_instance_model', true ) );

	return sprintf( 'Distribution ponctuelle de %s du %s à %s',
		$contrat_model->post_title,
		date_i18n( 'd/m/Y', intval( $date ) ),
		$lieu->post_title );
}

add_filter( 'amapress_user_commande_title_formatter', 'amapress_user_commande_title_formatter', 10, 2 );
function amapress_user_commande_title_formatter( $post_title, WP_Post $post ) {
	$post_id     = $post->ID;
	$commande_id = get_post_meta( $post_id, 'amapress_user_commande_commande', true );

	$date          = get_post_meta( $commande_id, 'amapress_distribution_date', true );
	$lieu          = get_post( get_post_meta( $commande_id, 'amapress_distribution_lieu', true ) );
	$contrat_model = get_post( get_post_meta( get_post_meta( $commande_id, 'amapress_commande_contrat_instance', true ), 'amapress_contrat_instance_model', true ) );

	return sprintf( 'Commande n°%d de %s du %s à %s',
		$post_id,
		$contrat_model->post_title,
		date_i18n( 'd/m/Y', intval( $date ) ),
		$lieu->post_title );
}

add_filter( 'amapress_assemblee_generale_title_formatter', 'amapress_assemblee_generale_title_formatter', 10, 2 );
function amapress_assemblee_generale_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$date = get_post_meta( $post_id, 'amapress_assemblee_generale_date', true );
	$lieu = get_post( get_post_meta( $post_id, 'amapress_assemblee_generale_lieu', true ) );

	return sprintf( 'Assemblée générale du %s à %s',
		date_i18n( 'l j F Y', intval( $date ) ),
		$lieu->post_title );
}

add_filter( 'amapress_adhesion_request_title_formatter', 'amapress_adhesion_request_title_formatter', 10, 2 );
function amapress_adhesion_request_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$first_name = get_post_meta( $post_id, 'amapress_adhesion_request_first_name', true );
	$last_name  = get_post_meta( $post_id, 'amapress_adhesion_request_last_name', true );
	$email      = get_post_meta( $post_id, 'amapress_adhesion_request_email', true );

	return sprintf( 'Demande de préinscription de %s %s (%s)',
		$first_name, $last_name, $email );
}

add_filter( 'amapress_panier_title_formatter', 'amapress_panier_title_formatter', 10, 2 );
function amapress_panier_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$date          = get_post_meta( $post_id, 'amapress_panier_date', true );
	$contrat_model = get_post( get_post_meta( get_post_meta( $post_id, 'amapress_panier_contrat_instance', true ), 'amapress_contrat_instance_model', true ) );

	return sprintf( 'Panier de %s du %s',
		$contrat_model->post_title,
		date_i18n( 'd/m/Y', intval( $date ) ) );
}

add_filter( 'amapress_adhesion_title_formatter', 'amapress_adhesion_title_formatter', 10, 2 );
function amapress_adhesion_title_formatter( $post_title, WP_Post $post ) {
	$post_id = $post->ID;

	$adh = new AmapressAdhesion( $post );
	if ( $adh->getContrat_instance() == null ) {
		return $post->post_title;
	}
	if ( $adh->getAdherent() == null ) {
		return $post->post_title;
	}

	return sprintf( '%s - %s - %s > %s (%s) (%d)',
		( $adh->hasDate_fin() ? '[arrêté] ' : '' ) . $adh->getAdherent()->getDisplayName(),
		$adh->getContrat_instance()->getTitle(),
		date_i18n( 'd/m/Y', intval( $adh->getDate_debut() ) ),
		date_i18n( 'd/m/Y', intval( $adh->getDate_fin() ) ),
		$adh->getLieu()->getShortName(),
		$post_id );
}

add_filter( 'amapress_contrat_paiement_title_formatter', 'amapress_contrat_paiement_title_formatter', 10, 2 );
function amapress_contrat_paiement_title_formatter( $post_title, WP_Post $post ) {
	$pmt = new AmapressAmapien_paiement( $post->ID );
	if ( $pmt->getAdhesion() == null ) {
		return $post->post_title;
	}

	return sprintf( '%s - %s - %s - %.02f',
		$pmt->getEmetteur(),
		date_i18n( 'd/m/Y', $pmt->getDate() ),
		$pmt->getNumero(),
		$pmt->getAmount() );
}

add_filter( 'amapress_adhesion_period_title_formatter', 'amapress_adhesion_period_title_formatter', 10, 2 );
function amapress_adhesion_period_title_formatter( $post_title, WP_Post $post ) {
	$pmt = new AmapressAdhesionPeriod( $post->ID );
	if ( ! $pmt->getDate_debut() ) {
		return $post->post_title;
	}

	return sprintf( 'Période adhésions - %s > %s',
		date_i18n( 'd/m/Y', $pmt->getDate_debut() ),
		date_i18n( 'd/m/Y', $pmt->getDate_fin() )
	);
}

add_filter( 'amapress_adhesion_paiement_title_formatter', 'amapress_adhesion_paiement_title_formatter', 10, 2 );
function amapress_adhesion_paiement_title_formatter( $post_title, WP_Post $post ) {
	$pmt = new AmapressAdhesion_paiement( $post->ID );
	//$date = get_post_meta($post_id, 'amapress_contrat_paiement_date', true);
	if ( $pmt->getUser() == null ) {
		return $post->post_title;
	}

	return sprintf( '%s - %s - %s - %.02f',
		$pmt->getUser()->getDisplayName(),
		date_i18n( 'd/m/Y', $pmt->getDate() ),
		$pmt->getNumero(),
		$pmt->getAmount() );
}

//add_filter('amapress_adhesion_intermittence_title_formatter', 'amapress_adhesion_intermittence_title_formatter', 10, 2);
//function amapress_adhesion_intermittence_title_formatter($post_title, WP_Post $post) {
//    $adh = new AmapressAdhesion_intermittence($post->ID);
//    if (!$adh->getUser()) return $post->post_title;
//
//    return sprintf('%s - %s',
//        $adh->getUser()->getDisplayName(),
//        date_i18n('d/m/Y', intval($adh->getDate_debut())));
//}
add_filter( 'amapress_intermittence_panier_title_formatter', 'amapress_intermittence_panier_title_formatter', 10, 2 );
function amapress_intermittence_panier_title_formatter( $post_title, WP_Post $post ) {
	$adh = new AmapressIntermittence_panier( $post->ID );
	if ( $adh->getPanier() == null ) {
		return $post->post_title;
	}

	return sprintf( '%s - %s',
		$adh->getPanier()->getTitle(),
		$adh->getAdherent()->getDisplayName() );
}

add_filter( 'amapress_contrat_instance_title_formatter', 'amapress_contrat_instance_title_formatter', 10, 2 );
function amapress_contrat_instance_title_formatter( $post_title, WP_Post $post ) {
	$adh = new AmapressContrat_instance( $post );
	if ( $adh->getModel() == null ) {
		return $post->post_title;
	}

	return sprintf( '%s - %s > %s',
		$adh->getModel()->getTitle(),
		date_i18n( 'm/Y', intval( $adh->getDate_debut() ) ),
		date_i18n( 'm/Y', intval( $adh->getDate_fin() ) ) );
}

add_action( 'edit_form_after_title', 'amapress_edit_post_title_handler' );
function amapress_edit_post_title_handler( WP_Post $post ) {
	if ( ! post_type_supports( $post->post_type, 'title' ) ) {
		$post_type = get_post_type_object( $post->post_type );
		if ( empty( $post->post_title ) ) {
			return;
		}
		?>
        <div id="titlediv">
            <div id="titlewrap">
                <input type="text" disabled="disabled" size="30" value="<?php echo esc_attr( $post->post_title ) ?>"
                       id="title" spellcheck="true" autocomplete="off"/>
            </div>
			<?php

			if ( $post_type->public ) {
				?>
                <div class="inside">
                    <strong>Permalien&nbsp;:</strong>
                    <span id="sample-permalink"><a href="<?php echo esc_attr( get_permalink( $post ) ) ?>"
                                                   target="_blank"><?php echo esc_html( get_permalink( $post ) ) ?></a></span>
                </div>
				<?php
			}
			?>
        </div>
		<?php
	}

	$pt      = amapress_simplify_post_type( $post->post_type );
	$options = AmapressEntities::getPostType( $pt );
	if ( isset( $options['edit_header'] ) && is_callable( $options['edit_header'], false ) ) {
		call_user_func( $options['edit_header'], $post );
	}
}