<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_get_plugin_install_link( $plugin_slug ) {
	$action = 'install-plugin';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $plugin_slug
			),
			admin_url( 'update.php' )
		),
		$action . '_' . $plugin_slug
	);
}

function amapress_get_plugin_activate_link( $plugin_slug ) {
	$installed_plugins = array_keys( get_plugins() );
	$installed_plugins = array_combine( array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $installed_plugins ), array_values( $installed_plugins ) );
	$plugin_slug       = isset( $installed_plugins[ $plugin_slug ] ) ? $installed_plugins[ $plugin_slug ] : $plugin_slug;
	$action            = 'activate';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $plugin_slug
			),
			admin_url( 'plugins.php' )
		),
		'activate-plugin_' . $plugin_slug
	);
}

function amapress_get_check_state( $state, $name, $message, $link, $values = null ) {
	return array(
		'state'   => $state,
		'name'    => $name,
		'message' => $message,
		'link'    => $link,
		'values'  => $values,
	);
}

function amapress_is_plugin_active( $plugin_slug ) {
	$installed_plugins      = array_keys( get_plugins() );
	$network_active_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
	$active_plugins         = array_values( get_option( 'active_plugins', array() ) );
	$active_plugins         = array_merge( $active_plugins, $network_active_plugins );
//    var_dump($active_plugins);
	$active_plugins    = array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $active_plugins );
	$installed_plugins = array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $installed_plugins );

	return in_array( $plugin_slug, $active_plugins ) ? 'active' : ( in_array( $plugin_slug, $installed_plugins ) ? 'installed' : 'not-installed' );
}

function amapress_check_plugin_install( $plugin_slug, $plugin_name, $message_if_install_needed, $not_installed_level = 'warning' ) {
	$is_active = amapress_is_plugin_active( $plugin_slug );

	return amapress_get_check_state(
		$is_active == 'active' ? 'success' : $not_installed_level,
		$plugin_name . ( $is_active != 'active' ? ' (' . ( $is_active == 'not-installed' ? 'installer' : 'activer' ) . ')' : '' ),
		$message_if_install_needed,
		$is_active == 'not-installed' ? amapress_get_plugin_install_link( $plugin_slug ) : ( $is_active == 'installed' ? amapress_get_plugin_activate_link( $plugin_slug ) : '' )
	);
}

function amapress_echo_and_check_amapress_state_page() {
	$labels              = array(
		'01_plugins' => '1/ Plugins',
		'02_config'  => '2/ Configuration',
		'03_users'   => '3/ Comptes utilisateurs',
		'04_posts'   => '4/ Votre AMAP',
		'05_import'  => '5/ Import CSV',
	);
	$state               = array();
	$state['01_plugins'] = array();
//    $state['01_plugins'][] = amapress_check_plugin_install('google-sitemap-generator', 'Google XML Sitemaps',
//        'Permet un meilleur référencement par les moteurs de recherche pour votre AMAP');
	$state['01_plugins'][] = amapress_check_plugin_install( 'backupwordpress', 'BackUpWordPress',
		'<strong>Recommandé</strong> : Sauvegarde du site. Permet de réinstaller en cas de panne, bug, hack' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'akismet', 'Akismet',
		'<strong>Recommandé</strong> : Protège le site du SPAM.', 'error' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'new-user-approve', 'New User Approve',
		'<strong>Optionnel</strong> : Installer ce plugin si le paramètre « Création de compte sur le site » (Section 2 – configuration) est activé. Une inscription en ligne nécessitera une validation de l’utilisateur par un administrateur.',
		Amapress::userCanRegister() ? 'error' : 'warning' );
//    $state['01_plugins'][] = amapress_check_plugin_install('smtp-mailing-queue', 'SMTP Mailing Queue',
//        'Installer ce plugin permet d\'envoyer les mails aux adhérents au fur et à mesure pour éviter une blocage SMTP (par ex, lors des imports CSV)');
	$state['01_plugins'][] = amapress_check_plugin_install( 'tinymce-advanced', 'TinyMCE Advanced',
		'<strong>Recommandé</strong> : Enrichi l\'éditeur de texte intégré de Wordpress afin de faciliter la création de contenu sur le site' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'all-in-one-seo-pack', 'All in One SEO Pack',
		'<strong>Optionnel</strong> :  Améliore le référencement du site. Ce plugin ajoute de nombreuse options dans le back-office, à installer par un webmaster.' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'unconfirmed', 'Unconfirmed',
		'<strong>Recommandé</strong> : Permet de gérer les inscriptions en cours (Renvoyer le mail de bienvenue avec le lien pour activer le compte utilisateur…)' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'user-switching', 'User Switching',
		'<strong>Optionnel</strong> : Permet aux administrateurs de consulter Amapress avec un autre compte utilisateur. Ce plugin est à installer par un webmaster. ',
		'info' );

	$state['01_plugins'][] = amapress_check_plugin_install( 'contact-form-7', 'Contact Form 7',
		'<strong>Optionnel</strong> : Permet de créer des formulaires de préinscription à l’AMAP, de contacter les auteurs de recettes…',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'external-media', 'External Media',
		'<strong>Optionnel</strong> : Permet de référencer des documents accessibles sur GoogleDrive, OneDrive, DropBox sans les importer via la «Media Library » de Wordpress',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'enable-media-replace', 'Enable Media Replace',
		'<strong>Recommandé</strong> : Permet de remplacer facilement une image dans la « Media Library » de Wordpress',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'imsanity', 'Imsanity',
		'<strong>Optionnel</strong> : Permet d’optimiser le poids des images dans la « Media Library » de Wordpress. Ce plugin est à installer par un webmaster. ',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'bbpress', 'bbPress',
		'<strong>Optionnel</strong> : Permet de gérer un forum (avec toutes ses fonctionnalités) sur le site.' );

	$state['02_config'] = array();

	$blog_desc            = get_bloginfo( 'description' );
	$state['02_config'][] = amapress_get_check_state(
		empty( $blog_desc ) ? 'warning' : 'success',
		'Description de l\'AMAP',
		'Cette section permet le référencement dans les moteurs de recherche. <br/>Remplir les champs <strong>Titre</strong> et <strong>Slogan</strong>',
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
	$site_icon            = get_option( 'site_icon' );
	$state['02_config'][] = amapress_get_check_state(
		empty( $site_icon ) ? 'warning' : 'success',
		'Icône de l\'AMAP',
		'Ajouter une icône pour personnaliser l\'entête du navigateur et les signets/favoris',
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
	$state['02_config'][] = amapress_get_check_state(
		! Amapress::userCanRegister() ? 'success' : ( ! amapress_is_plugin_active( 'new-user-approve' ) ? 'error' : 'warning' ),
		'Création de compte sur le site',
		'<strong>Non recommandé</strong> : Cette option permet aux nouveaux visiteurs de créer un compte utilisateur en direct. Sans cette option, seuls les responsables pourront créer des comptes utilisateurs. ',
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
//    $blog_desc = get_theme_mod('custom_logo');
//    $state['02_config'][] = amapress_get_check_state(
//        empty($blog_desc) ? 'warning' : 'success',
//        'Icone de l\'AMAP',
//        'Ajouter une icone pour l\'AMAP personnalise l\'entête du navigateur et les signets',
//        admin_url('customize.php?autofocus[section]=title_tagline')
//    );

	$static_front_id      = get_option( 'page_on_front' );
	$state['02_config'][] = amapress_get_check_state(
		empty( $static_front_id ) ? 'error' : 'success',
		'Page d\'accueil statique',
		'Vérifier que votre thème est configuré avec l’option « page d\'accueil statique »<br/>Sélectionner votre page d’accueil existante, ou configurer une nouvelle page.',
		admin_url( 'customize.php?autofocus[section]=static_front_page' )
	);
	$front_page_content   = null;
	$front_page_logo      = null;
	if ( ! empty( $static_front_id ) ) {
		$page = get_post( $static_front_id );
		if ( $page ) {
			$front_page_content = $page->post_content;
			$front_page_logo    = get_post_thumbnail_id( $page->ID );
		}
	}
	$state['02_config'][] = amapress_get_check_state(
		empty( $front_page_content ) ? 'warning' : 'success',
		'Contenu à la page d\'accueil',
		'Ajouter le texte de présentation de votre Amap',
		admin_url( 'post.php?post=' . $static_front_id . '&action=edit' )
	);
	$contact_page         = Amapress::getOption( 'contrat_info_anonymous' );
	$state['02_config'][] = amapress_get_check_state(
		empty( $contact_page ) ? 'warning' : 'success',
		'Contenu de la page de contact',
		'Ajouter les informations nécessaires pour contacter l’Amap pour une nouvelle inscription.',
		admin_url( 'admin.php?page=amapress_contact_options_page' )
	);
	$state['02_config'][] = amapress_get_check_state(
		empty( $front_page_logo ) ? 'warning' : 'success',
		'Logo de la page d\'accueil',
		'Ajouter votre logo sur la page d\'accueil',
		admin_url( 'post.php?post=' . $static_front_id . '&action=edit' )
	);
//    $contrat_anon = Amapress::getOption('contrat_info_anonymous');
//    $state['02_config'][] = amapress_get_check_state(
//        empty($contrat_anon) ? 'warning' : 'success',
//        'Information sur les contrats',
//        empty($contrat_anon) ?
//            'Ajouter le texte d\'information sur les contrats' :
//            'Cliquer sur le lien ci-dessus pour éditer le texte d\'information sur les contrats',
//        admin_url('admin.php?page=amapress_options_page&tab=contrats')
//    );

//    $menu_name = 'primary';
//    $locations = get_nav_menu_locations();

//    $state['02_config'][] = amapress_get_check_state(
//        empty($main_menu) || count($main_menu) == 0 ? 'error' : 'success',
//        'Menu principal du site',
//        empty($main_menu) || count($main_menu) == 0 ?
//            'Remplir le menu principal du site' :
//            'Cliquer sur le lien ci-dessus pour éditer le menu',
//        admin_url('customize.php?autofocus[panel]=nav_menus')
//    );
	$info_page_menu_item_found = false;
	$info_page_id              = Amapress::getOption( 'mes-infos-page' );
	foreach ( get_nav_menu_locations() as $menu_name => $menu_id ) {
		foreach ( wp_get_nav_menu_items( $menu_id ) as $menu_item ) {
			if ( $menu_item->object_id == $info_page_id ) {
				$info_page_menu_item_found = true;
			}
		}
	}
	$state['02_config'][] = amapress_get_check_state(
		! $info_page_menu_item_found ? 'error' : 'success',
		'Entrée de menu - Mes Infos',
		'<strong>Important</strong> : Créer obligatoirement une entrée dans le menu principal vers la page « Mes Infos » (menu permettant la connexion).',
		admin_url( 'customize.php?autofocus[panel]=nav_menus' )
	);

//    $state['02_config'][] = amapress_get_check_state(
//        empty($front_page_logo) ? 'warning' : 'success',
//        'Logo de la page d\'accueil',
//        empty($front_page_logo) ?
//            'Ajouter un logo à la page d\'accueil' :
//            'Cliquer sur le lien ci-dessus pour éditer la page d\'accueil et son logo',
//        admin_url('post.php?post=' . $static_front_id . '&action=edit')
//    );


	$state['03_users'] = array();

	$users               = get_users( array( 'role' => 'responsable_amap' ) );
	$state['03_users'][] = amapress_get_check_state(
		count( $users ) == 0 ? 'error' : 'success',
		'Compte Responsable AMAP',
		'Créer les comptes des Responsables de l\'AMAP',
		admin_url( 'user-new.php?role=responsable_amap' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}'>{$dn->getDisplayName()}</a>";
		}, $users ) )
	);
	$prod_users          = get_users( array( 'role' => 'producteur' ) );
	$state['03_users'][] = amapress_get_check_state(
		count( $prod_users ) == 0 ? 'error' : 'success',
		'Compte Producteur',
		'Créer les comptes des producteurs',
		admin_url( 'user-new.php?role=producteur' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}'>{$dn->getDisplayName()}</a>";
		}, $prod_users ) )
	);
	$users               = get_users( array( 'role' => 'referent' ) );
	$state['03_users'][] = amapress_get_check_state(
		count( $users ) == 0 ? 'error' : 'success',
		'Compte Référent Producteur',
		'Créer les comptes des Référents Producteurs',
		admin_url( 'user-new.php?role=referent' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}'>{$dn->getDisplayName()}</a>";
		}, $users ) )
	);

	$state['04_posts'] = array();

	$posts               = get_posts( array(
		'post_type'      => AmapressLieu_distribution::INTERNAL_POST_TYPE,
		'posts_per_page' => - 1,
	) );
	$state['04_posts'][] = amapress_get_check_state(
		count( $posts ) == 0 ? 'error' : 'success',
		'Lieu de distribution',
		'Créer au moins un lieu de distribution',
		admin_url( 'post-new.php?post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $u ) {
			$dn = new AmapressLieu_distribution( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}'>{$dn->getTitle()}</a>";
		}, $posts ) )
	);


	$producteurs         = get_posts( array(
		'post_type'      => AmapressProducteur::INTERNAL_POST_TYPE,
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'amapress_producteur_user',
				'value'   => amapress_prepare_in( array_map( 'Amapress::to_id', $prod_users ) ),
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			)
		)
	) );
	$state['04_posts'][] = amapress_get_check_state(
		count( $prod_users ) == 0 ? 'error' : ( count( $producteurs ) != count( $prod_users ) ? 'warning' : 'success' ),
		'Présentation Producteurs',
		'Créer les Producteur correspondant à leur compte utilisateur',
		admin_url( 'post-new.php?post_type=' . AmapressProducteur::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $u ) {
			$dn = new AmapressProducteur( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}'>{$dn->getTitle()}</a>";
		}, $producteurs ) )
	);

	$contrat_types       = get_posts( array(
		'post_type'      => AmapressContrat::INTERNAL_POST_TYPE,
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'amapress_contrat_producteur',
				'value'   => amapress_prepare_in( array_map( 'Amapress::to_id', $producteurs ) ),
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			)
		)
	) );
	$state['04_posts'][] = amapress_get_check_state(
		count( $contrat_types ) == 0 || count( $contrat_types ) != count( $producteurs ) ? 'error' : 'success',
		'Présentation Web des contrats',
		'Créer une présentation web par producteur pour présenter son offre',
		admin_url( 'post-new.php?post_type=' . AmapressContrat::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $u ) {
			$dn = new AmapressContrat( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}'>{$dn->getTitle()}</a>";
		}, $contrat_types ) )
	);

	$posts               = AmapressContrats::get_subscribable_contrat_instances();
	$state['04_posts'][] = amapress_get_check_state(
		count( $posts ) == 0 ? 'error' : ( count( $posts ) < count( $contrat_types ) ? 'warning' : 'success' ),
		'Modèles de contrats',
		'Créer au moins un modèle de contrat par contrat pour permettre au amapien d\'adhérer',
		admin_url( 'post-new.php?post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $dn ) {
			$l = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}'>{$dn->getTitle()}</a>";
		}, $posts ) )
	);

	$state['05_import']   = array();
	$state['05_import'][] = amapress_get_check_state(
		'do',
		'Amapiens',
		'Importer des amapiens',
		admin_url( 'admin.php?page=amapress_import_page' )
	);
	$state['05_import'][] = amapress_get_check_state(
		'do',
		'Adhésions',
		'Importer des adhésions',
		admin_url( 'admin.php?page=amapress_import_page&tab=adhésions' )
	);

	foreach ( $state as $categ => $checks ) {
		amapress_echo_panel_start( $labels[ $categ ] );

		foreach ( $checks as $check ) {
			$title  = $check['name'];
			$state  = $check['state'];
			$desc   = $check['message'];
			$link   = $check['link'];
			$values = isset( $check['values'] ) ? $check['values'] : '';
			echo "<div class='amapress-check'>";
			echo "<p class='check-item state {$state}'><a href='$link' target='_blank'>{$title}</a><span class='dashicons dashicons-external'></span></p>";
			echo "<div class='amapress-check-content'>";
			if ( ! empty( $values ) ) {
				echo "<p class='values'>{$values}</p>";
			}
			if ( ! empty( $desc ) ) {
				echo "<p class='description'>{$desc}</p>";
			}
			echo "</div>";
			echo "</div>";
		}

		amapress_echo_panel_end();
	}
}