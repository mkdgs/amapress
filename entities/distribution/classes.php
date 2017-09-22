<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressDistribution extends Amapress_EventBase {
	const INTERNAL_POST_TYPE = 'amps_distribution';
	const POST_TYPE = 'distribution';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getDefaultSortValue() {
		return $this->getDate();
	}

	public function getStartDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getLieu()->getHeure_debut() );
	}

	public function getEndDateAndHour() {
		return Amapress::make_date_and_hour( $this->getDate(), $this->getLieu()->getHeure_fin() );
	}

	public function getNb_responsables_Supplementaires() {
		return $this->getCustomAsInt( 'amapress_distribution_nb_resp_supp', 0 );
	}

	public function getInformations() {
		return $this->getCustom( 'amapress_distribution_info', '' );
	}

	public function getDate() {
		return $this->getCustomAsDate( 'amapress_distribution_date' );
	}

	/** @return AmapressLieu_distribution */
	public function getRealLieu() {
		$lieu_subst = $this->getLieuSubstitution();
		if ( $lieu_subst ) {
			return $lieu_subst;
		}

		return $this->getLieu();
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_distribution_lieu', 'AmapressLieu_distribution' );
	}

	/** @return int */
	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_distribution_lieu', - 1 );
	}

	/** @return AmapressLieu_distribution */
	public function getLieuSubstitution() {
		return $this->getCustomAsEntity( 'amapress_distribution_lieu_substitution', 'AmapressLieu_distribution' );
	}

	/** @return int */
	public function getLieuSubstitutionId() {
		return $this->getCustomAsInt( 'amapress_distribution_lieu_substitution', - 1 );
	}

	/** @return AmapressUser[] */
	public function getResponsables() {
		return $this->getCustomAsEntityArray( 'amapress_distribution_responsables', 'AmapressUser' );
	}

	/** @return int[] */
	public function getResponsablesIds() {
		return $this->getCustomAsIntArray( 'amapress_distribution_responsables' );
	}

	/** @return AmapressContrat_instance[] */
	public function getContrats() {
		return $this->getCustomAsEntityArray( 'amapress_distribution_contrats', 'AmapressContrat_instance' );
	}

	/** @return int[] */
	public function getContratIds() {
		return $this->getCustomAsIntArray( 'amapress_distribution_contrats' );
	}

	public function isUserMemberOf( $user_id, $guess_renew = false ) {
		$user_contrats_ids = AmapressContrats::get_user_active_contrat_instances( $user_id,
			null,
			$this->getDate(),
			$guess_renew );
		$dist_contrat_ids  = array_map( function ( $c ) {
			return $c->ID;
		}, $this->getContrats() );

		if ( count( array_intersect( $user_contrats_ids, $dist_contrat_ids ) ) > 0 ) {
			return true;
		}
		if ( ! $guess_renew ) {
			return false;
		}

		$user_lieu_ids = AmapressUsers::get_user_lieu_ids( $user_id,
			$this->getDate() );

		return in_array( $this->getLieuId(), $user_lieu_ids );
	}

	public function inscrireResponsable( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		if ( ! $this->isUserMemberOf( $user_id, true ) ) {
			wp_die( 'Vous ne faites pas partie de cette distribution.' );
		}

		$responsables        = Amapress::get_post_meta_array( $this->ID, 'amapress_distribution_responsables' );
		$needed_responsables = AmapressDistributions::get_required_responsables( $this->ID );
		if ( ! $responsables ) {
			$responsables = array();
		}
		if ( in_array( $user_id, $responsables ) ) {
			return 'already_in_list';
		} else if ( count( $responsables ) >= $needed_responsables ) {
			return 'list_full';
		} else {
			$responsables[] = $user_id;
			update_post_meta( $this->ID, 'amapress_distribution_responsables', $responsables );

			amapress_mail_current_user_inscr( $this, $user_id );

			return 'ok';
		}
	}

	public function desinscrireResponsable( $user_id ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( 'Vous devez avoir un compte pour effectuer cette opération.' );
		}

		$responsables = Amapress::get_post_meta_array( $this->ID, 'amapress_distribution_responsables' );
		if ( ! $responsables ) {
			$responsables = array();
		}

		if ( ( $key = array_search( $user_id, $responsables ) ) !== false ) {
			unset( $responsables[ $key ] );

			update_post_meta( $this->ID, 'amapress_distribution_responsables', $responsables );

			amapress_mail_current_user_desinscr( $this, $user_id );

			return 'ok';
		} else {
			return 'not_inscr';
		}
	}

	/** @return AmapressDistribution[] */
	public static function get_next_distributions( $date = null, $order = 'NONE' ) {
		if ( ! $date ) {
			$date = amapress_time();
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_distribution_date',
					'value'   => Amapress::start_of_day( $date ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return AmapressDistribution[] */
	public static function get_distributions( $start_date = null, $end_date = null, $order = 'NONE' ) {
		if ( ! $start_date ) {
			$start_date = Amapress::start_of_day( amapress_time() );
		}
		if ( ! $end_date ) {
			$end_date = Amapress::end_of_week( amapress_time() );
		}

		return self::query_events(
			array(
				array(
					'key'     => 'amapress_distribution_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				),
			),
			$order );
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		$ret = array();
		if ( empty( $user_id ) || $user_id <= 0 ) {
			$lieu              = $this->getLieu();
			$lieu_substitution = $this->getLieuSubstitution();
			if ( ! empty( $lieu_substitution ) ) {
				$lieu = $lieu_substitution;
			}
			$dist_date     = $this->getStartDateAndHour();
			$dist_date_end = $this->getEndDateAndHour();
			$contrats      = $this->getContrats();
			foreach ( $contrats as $contrat ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "dist-{$this->ID}",
					'date'     => $dist_date,
					'date_end' => $dist_date_end,
					'type'     => 'distribution',
					'category' => 'Distributions',
					'priority' => 30,
					'lieu'     => $lieu,
					'label'    => $contrat->getModel()->getTitle(),
					'alt'      => 'Distribution de ' . $contrat->getModel()->getTitle() . ' à ' . $lieu->getShortName(),
					'class'    => "agenda-contrat-{$contrat->getModel()->ID}",
					'icon'     => Amapress::coalesce_icons( Amapress::getOption( "contrat_{$contrat->getModel()->ID}_icon" ), amapress_get_avatar_url( $contrat->getModel()->ID, null, 'produit-thumb', 'default_contrat.jpg' ) ),
					'href'     => $this->getPermalink()
				) );
			}
		} else {
			$adhesions         = AmapressContrats::get_user_active_adhesion();
			$lieu              = $this->getLieu();
			$lieu_substitution = $this->getLieuSubstitution();
			if ( ! empty( $lieu_substitution ) ) {
				$lieu = $lieu_substitution;
			}
			$dist_date     = $this->getStartDateAndHour();
			$dist_date_end = $this->getEndDateAndHour();
			$resps         = $this->getResponsablesIds();
			if ( in_array( $user_id, $resps ) ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "dist-{$this->ID}-resp",
					'date'     => $dist_date,
					'date_end' => $dist_date_end,
					'class'    => 'agenda-resp-distrib',
					'category' => 'Responsable de distribution',
					'lieu'     => $lieu,
					'type'     => 'resp-distribution',
					'priority' => 45,
					'label'    => 'Responsable de distribution',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_resp_distrib_icon" ) ),
					'alt'      => 'Vous êtes responsable de distribution à ' . $lieu->getShortName(),
					'href'     => $this->getPermalink()
				) );
			}
			$contrats = $this->getContratIds();
			foreach ( $adhesions as $adhesion ) {
				if ( $adhesion->getLieuId() == $this->getLieuId()
				     && in_array( $adhesion->getContrat_instance()->ID, $contrats )
				) {
					$ret[] = new Amapress_EventEntry( array(
						'ev_id'    => "dist-{$this->ID}",
						'id'       => $this->ID,
						'date'     => $dist_date,
						'date_end' => $dist_date_end,
						'class'    => "agenda-contrat-{$adhesion->getContrat_instance()->getModel()->getTitle()}",
						'type'     => 'distribution',
						'category' => 'Distributions',
						'priority' => 30,
						'lieu'     => $lieu,
						'label'    => $adhesion->getContrat_instance()->getModel()->getTitle(),
						'icon'     => Amapress::coalesce_icons( Amapress::getOption( "contrat_{$adhesion->getContrat_instance()->getModel()->ID}_icon" ), amapress_get_avatar_url( $adhesion->getContrat_instance()->getModel()->ID, null, 'produit-thumb', 'default_contrat.jpg' ) ),
						'alt'      => 'Distribution de ' . $adhesion->getContrat_instance()->getModel()->getTitle() . ' à ' . $lieu->getShortName(),
						'href'     => $this->getPermalink()
					) );
				}
			}
		}

		if ( Amapress::isIntermittenceEnabled() ) {
			$status_count = array(
				'me_to_exchange'    => 0,
				'other_to_exchange' => 0,
				'me_exchanged'      => 0,
				'me_recup'          => 0,
			);
			$paniers      = AmapressPaniers::getPanierIntermittents(
				array(
					'date' => $this->getDate(),
					'lieu' => $this->getLieuId(),
				)
			);
			foreach ( $paniers as $panier ) {
				if ( $panier->getAdherent()->ID == $user_id ) {
					if ( $panier->getStatus() == 'to_exchange' ) {
						$status_count['me_to_exchange'] += 1;
					} else {
						$status_count['me_exchanged'] += 1;
					}
				} else if ( $panier->getRepreneur() != null && $panier->getRepreneur()->ID == $user_id ) {
					$status_count['me_recup'] += 1;
				} else {
					if ( $panier->getStatus() == 'to_exchange' ) {
						$status_count['other_to_exchange'] += 1;
					}
				}
			}

			$date     = $this->getStartDateAndHour();
			$date_end = $this->getEndDateAndHour();
			if ( $status_count['me_to_exchange'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-to-exchange",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-intermittence",
					'type'     => 'intermittence',
					'category' => 'Paniers à échanger',
					'priority' => 10,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_to_exchange'] . '</span> à échanger',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['me_to_exchange'] . ' à échanger',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}
			if ( $status_count['me_exchanged'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-exchanged",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-intermittence",
					'type'     => 'intermittence',
					'category' => 'Paniers échangé',
					'priority' => 5,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_exchanged'] . '</span> échangé(s)',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['me_exchanged'] . ' échangé(s)',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}

			if ( $status_count['me_recup'] > 0 ) {
				$ret[] = new Amapress_EventEntry( array(
					'ev_id'    => "intermittence-{$this->ID}-recup",
					'date'     => $date,
					'date_end' => $date_end,
					'class'    => "agenda-inter-panier-recup",
					'type'     => 'inter-recup',
					'category' => 'Paniers à récupérer',
					'priority' => 15,
					'lieu'     => $this->getRealLieu(),
					'label'    => '<span class="badge">' . $status_count['me_recup'] . '</span> à récupérer',
					'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
					'alt'      => $status_count['me_recup'] . ' à récupérer',
					'href'     => Amapress::getPageLink( 'mes-paniers-intermittents-page' )
				) );
			}
			if ( $status_count['other_to_exchange'] > 0 ) {
				$dist = AmapressPaniers::getDistribution( $this->getDate(), $this->getLieu()->ID );
				if ( $dist ) {
					$paniers_url = Amapress::getPageLink( 'paniers-intermittents-page' ) . '#' . $dist->getSlug();
					$ret[]       = new Amapress_EventEntry( array(
						'ev_id'    => "intermittence-{$this->ID}-to-exchange",
						'date'     => $date,
						'date_end' => $date_end,
						'class'    => "agenda-intermittence",
						'type'     => 'intermittence',
						'category' => 'Paniers dispo',
						'priority' => 10,
						'lieu'     => $this->getRealLieu(),
						'label'    => '<span class="badge">' . $status_count['other_to_exchange'] . '</span> à échanger',
						'icon'     => Amapress::get_icon( Amapress::getOption( "agenda_intermittence_icon" ) ),
						'alt'      => $status_count['other_to_exchange'] . ' à échanger',
						'href'     => $paniers_url
					) );
				}
			}
		}

		return $ret;
	}
}