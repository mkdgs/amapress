<?php
/**
 * @package Amapress
 */
/*
Plugin Name: Amapress
Plugin URI: http://amapress.fr/
Description: 
Version: 0.10.4
Author: ShareVB
Author URI: http://amapress.fr/
License: GPLv2 or later
Text Domain: amapress
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2015-2017 ShareVB
*/

// Make sure we don't expose any info if called directly

if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'AMAPRESS_VERSION', '0.9.4' );
define( 'AMAPRESS__MINIMUM_WP_VERSION', '4.2' );
define( 'AMAPRESS__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AMAPRESS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMAPRESS__PLUGIN_FILE', __FILE__ );
define( 'AMAPRESS_DELETE_LIMIT', 100000 );
define( 'AMAPRESS_DB_VERSION', 62 );
//remove_role('responable_amap');

function amapress_wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
//    add_filter( 'wp_mail_content_type', 'amapress_wpmail_content_type', 50);
	if ( empty( $headers ) ) {
		$headers = array();
	}
	if ( is_string( $headers ) ) {
		$headers = explode( "\n", $headers );
	}
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	wp_mail( $to, $subject, wpautop( $message ), $headers, $attachments );
//    remove_filter( 'wp_mail_content_type', 'amapress_wpmail_content_type', 50);
}

function amapress_dump( $v ) {
	echo '<pre>';
	var_dump( $v );
	echo '</pre>';
}

global $amapress_notices;
$amapress_notices = array();
function amapress_add_admin_notice( $message, $type, $is_dismissible ) {
	global $amapress_notices;

	$class = $is_dismissible ? "notice-$type is-dismissible" : "notice-$type";

	$amapress_notices[] = sprintf( '<div class="notice %1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

add_action( 'admin_notices', 'amapress_output_admin_notices' );
function amapress_output_admin_notices() {
	global $amapress_notices;

	foreach ( $amapress_notices as $notice ) {
		echo $notice;
	}
}

function amapress_exception_error_handler( $errno, $errstr, $errfile, $errline, $errcontext ) {
	// handle @
	if ( 0 === error_reporting() ) {
		return false;
	}
	$message = $errstr . ' in ' . $errfile . ' on line ' . $errline . ', backtrace: ' . wp_debug_backtrace_summary( null, 1 );

	if ( strpos( $message, 'Load_Resend_Welcome_Email' ) !== false ) {
		return true;
	}

	if ( WP_DEBUG_DISPLAY || ini_get( 'display_errors' ) ) {
		echo '<br />' . $message . '<br />';
	}
	if ( WP_DEBUG_LOG || ini_get( 'log_errors' ) ) {
		error_log( $message );
	}

	return true;
}

//function amapress_exception_fatal_error_handler()
//{
//    $error = error_get_last();
//    if ( $error["type"] == E_ERROR ) {
//        $message = $error["message"] . ' in ' . $error["file"] . ' on line ' . $error["line"] . ', backtrace: ' . wp_debug_backtrace_summary( null, 1 );
//
//        if( WP_DEBUG_DISPLAY || ini_get( 'display_errors' ) )
//            echo '<br />' . $message . '<br />';
//        if( WP_DEBUG_LOG || ini_get( 'log_errors' ) )
//            error_log( $message );
//    }
//}
set_error_handler( 'amapress_exception_error_handler' );
//register_shutdown_function('amapress_exception_fatal_error_handler');
function amapress_wpmail_content_type() {
	return 'text/html';
}

//add_filter( 'wp_mail_content_type', 'amapress_wpmail_content_type' );

function amapress_mail_from( $old ) {
	$new = Amapress::getOption( 'email_from_mail' );

	return empty( $new ) ? $old : $new;
}

function amapress_mail_from_name( $old ) {
	$new = Amapress::getOption( 'email_from_name' );

	return empty( $new ) ? $old : $new;
}

add_filter( 'wp_mail_from', 'amapress_mail_from' );
add_filter( 'wp_mail_from_name', 'amapress_mail_from_name' );


function amapress_get_default_wordpress_from_email() {
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}

	return 'wordpress@' . $sitename;
}

//function amapress_add_replyto($args) {
//    if(!empty($args['headers'])) {
//        $headers = $args['headers'];
//        if (is_string($headers)) {
//            $headers = str_replace("\r", '', $headers);
//            $headers = explode("\n", $headers);
//        }
//        $reply_to_name = Amapress::getOption('email_replyto_name');
//        $reply_to_mail = Amapress::getOption('email_replyto_mail');
//        $has_replyto = false;
//        foreach ($headers as $h) {
//            if (strpos(strtolower($h), 'reply-to:') === 0)
//                $has_replyto=true;
//        }
//        if (!$has_replyto && !empty($reply_to_mail)) {
//            if (!empty($reply_to_name))
//                $headers[] = "Reply-to: $reply_to_name <$reply_to_mail>";
//            else
//                $headers[] = "Reply-to: $reply_to_mail";
//        }
//
//        $args['headers'] = $headers;
//    }
//    return $args;
//}
//add_filter('wp_mail', 'amapress_add_replyto');

if ( ! defined( 'AMAPRESS_CSV_DELIMITER' ) ) {
	define( 'AMAPRESS_CSV_DELIMITER', "," );
}
//define( 'WP_DEBUG', true );

register_activation_hook( __FILE__, array( 'Amapress', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Amapress', 'plugin_deactivation' ) );

function amapress__( $s ) {
	return __( $s, 'amapress' );
}

require_once( 'titan-framework/titan-framework.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'state.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/options.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapressentities.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'impersonation.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapressmeseventswidget.php' );
//require_once(AMAPRESS__PLUGIN_DIR . 'class.amapressmenuwidget.php');
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.users.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.paniers.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.events.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.distributions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.commandes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.contrats.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/class-amapress-event-base.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/class-amapress-calendar.php' );
//require_once(AMAPRESS__PLUGIN_DIR . 'class.amapressadmin.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'class.amapressmembership.php');
//require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress-widget.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/include.entities.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'options/customizer.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'options/tabs.models.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'options/options.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/utils.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/register.php' );


require_once( AMAPRESS__PLUGIN_DIR . 'utils/Encoding.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/export-posts-to-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/export-users-to-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/import-posts-from-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/import-users-from-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/class.amapress-taxonomy.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/row.actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/bulk.actions.php' );


new AmapressUserTaxonomy();

function amapress_unsimplify_post_type( $post_type ) {
	$pts = AmapressEntities::getPostTypes();
	if ( array_key_exists( $post_type, $pts ) ) {
		return ( ! empty( $pts[ $post_type ]['internal_name'] ) ? $pts[ $post_type ]['internal_name'] : 'amps_' . $post_type );
	}

	return $post_type;
}

function amapress_simplify_post_type( $post_type ) {
	if ( is_array( $post_type ) ) {
		return array_map( 'amapress_simplify_post_type', $post_type );
	}

	switch ( $post_type ) {
		case 'page':
		case 'post':
		case 'user':
			return $post_type;
		case 'amps_lieu':
			return 'lieu_distribution';
		case 'amps_assemblee':
			return 'assemblee_generale';
		case 'amps_contrat_inst':
			return 'contrat_instance';
		case 'amps_contrat_quant':
			return 'contrat_quantite';
		case 'amps_cont_pmt':
			return 'contrat_paiement';
		case 'amps_adh_pmt':
			return 'adhesion_paiement';
		case 'amps_user_plike':
			return 'user_produit_like';
		case 'amps_inter_panier':
			return 'intermittence_panier';
//        case 'amps_inter_adhe':
//            return 'adhesion_intermittence';
		case 'amps_adh_req';
			return 'adhesion_request';
		case 'amps_adh_per';
			return 'adhesion_period';
		case 'amps_mailing':
			return 'mailinglist';
	}
	if ( strpos( $post_type, 'amps_' ) === 0 ) {
		return substr( $post_type, 5 );
	} else {
		return $post_type;
	}
}

if ( ! function_exists( 'array_group_by' ) ) :
	/**
	 * Groups an array by a given key.
	 *
	 * Groups an array into arrays by a given key, or set of keys, shared between all array members.
	 *
	 * Based on {@author Jake Zatecky}'s {@link https://github.com/jakezatecky/array_group_by array_group_by()} function.
	 * This variant allows $key to be closures.
	 *
	 * @param array $array The array to have grouping performed on.
	 * @param mixed $key,... The key to group or split by. Can be a _string_,
	 *                       an _integer_, a _float_, or a _callable_.
	 *
	 *                       If the key is a callback, it must return
	 *                       a valid key from the array.
	 *
	 *                       ```
	 *                       string|int callback ( mixed $item )
	 *                       ```
	 *
	 * @return array|null Returns a multidimensional array or `null` if `$key` is invalid.
	 */

	function array_group_by( array $array, $key ) {
		if ( ! is_string( $key ) && ! is_int( $key ) && ! is_float( $key ) && ! is_callable( $key ) ) {
			trigger_error( 'array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR );

			return null;
		}

		$func = ( is_callable( $key ) ? $key : null );
		$_key = $key;

		// Load the new array, splitting by the target key
		$grouped = [];
		foreach ( $array as $value ) {
			if ( is_callable( $func ) ) {
				$key = call_user_func( $func, $value );
			} else {
				$key = $value[ $_key ];
			}

			$grouped[ $key ][] = $value;
		}

		// Recursively build a nested grouping if more parameters are supplied
		// Each grouped array value is grouped according to the next sequential key
		if ( func_num_args() > 2 ) {
			$args = func_get_args();

			foreach ( $grouped as $key => $value ) {
				$params          = array_merge( array( $value ), array_slice( $args, 2, func_num_args() ) );
				$grouped[ $key ] = call_user_func_array( 'array_group_by', $params );
			}
		}

		return $grouped;
	}
endif;

if ( ! function_exists( 'get_posts_count' ) ) {
	/**
	 * Retrieve count of latest posts or posts matching criteria.
	 *
	 * The defaults are as follows:
	 *
	 * @since 1.2.0
	 *
	 * @see WP_Query::parse_query()
	 *
	 * @param array $args {
	 *     Optional. Arguments to retrieve posts. See WP_Query::parse_query() for all
	 *     available arguments.
	 *
	 * @type int $numberposts Total number of posts to retrieve. Is an alias of $posts_per_page
	 *                                        in WP_Query. Accepts -1 for all. Default 5.
	 * @type int|string $category Category ID or comma-separated list of IDs (this or any children).
	 *                                        Is an alias of $cat in WP_Query. Default 0.
	 * @type array $include An array of post IDs to retrieve, sticky posts will be included.
	 *                                        Is an alias of $post__in in WP_Query. Default empty array.
	 * @type array $exclude An array of post IDs not to retrieve. Default empty array.
	 * @type bool $suppress_filters Whether to suppress filters. Default true.
	 * }
	 * @return array List of posts.
	 */
	function get_posts_count( $args = null ) {
		$args                   = wp_parse_args( $args );
		$args['fields']         = 'ids';
		$args['no_found_rows']  = true;
		$args['posts_per_page'] = - 1;
		$posts                  = get_posts( $args );

		return count( $posts );
	}
}

if ( ! function_exists( 'get_users_count' ) ) {
	/**
	 * Retrieve count of users matching criteria.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_User_Query
	 *
	 * @param array $args Optional. Arguments to retrieve users. See {@see WP_User_Query::prepare_query()}
	 *                    for more information on accepted arguments.
	 *
	 * @return array List of users.
	 */
	function get_users_count( $args = array() ) {
		$args                = wp_parse_args( $args );
		$args['count_total'] = false;

		$user_search = new WP_User_Query();
		$user_search->prepare_query( $args );

		global $wpdb;

		return intval( $wpdb->get_var( "SELECT COUNT(DISTINCT $wpdb->users.ID) $user_search->query_from $user_search->query_where" ) );
	}
}

function array_mode( $arr ) {
	$values = array_count_values( $arr );

	return array_search( max( $values ), $values );
}

// register Foo_Widget widget

function amapress_register_widgets() {
	register_widget( 'Amapress_Next_Events_Widget' );
//    register_widget('Amapress_Menu_Widget');
}

add_action( 'widgets_init', 'amapress_register_widgets' );
add_action( 'init', array( 'Amapress', 'init' ) );
//add_action('init', array('AmapressLoginAdmin', 'init'));
//add_action('init', array('AmapressMembership', 'init'));
add_action( 'init', array( 'AmapressPaniers', 'init' ) );
add_action( 'init', array( 'AmapressDistributions', 'init' ) );
add_action( 'init', array( 'Amapress_Calendar', 'init' ) );
add_action( 'init', array( 'AmapressUsers', 'init' ) );
add_action( 'init', array( 'AmapressContrats', 'init' ) );
add_action( 'tf_create_options', array( 'Amapress', 'init_post_metaboxes' ) );
add_action( 'init', 'amapress_global_init', 15 );

//add_action('admin_page_access_denied', function() {
//    var_dump(amp_user_can_access_admin_page());
//    die('xdfsfsd');
//});

function amapress_global_init() {
	$key = Amapress::getOption( 'google_map_key' );
	if ( ! empty( $key ) ) {
		TitanFrameworkOptionAddress::$google_map_api_key = $key;
	}
//    global $typenow;
//    var_dump(get_post_types( array( 'show_ui' => true ) ));
//    var_dump($typenow);
//    var_dump(amp_user_can_access_admin_page());
	do_action( 'amapress_init' );

	global $amapress_smtpMailingQueue;
	require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueue.php' );
	$amapress_smtpMailingQueue = new AmapressSMTPMailingQueue();
//    $users = get_users(
//        array('amapress_contrat' => 'no')
//    );
	//amapress_update_all_posts();
}

function amapress_get_avatar_meta_name() {
	global $wpdb, $blog_id;

	return $wpdb->get_blog_prefix( $blog_id ) . 'user_avatar';
}

add_action( 'updated_postmeta', 'amapress_updated_postmeta', 10, 4 );
function amapress_updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {
	if ( $meta_key == '_thumbnail_id' && $meta_value ) {
		$att = get_post( $meta_value );
		if ( $att && ! $att->post_parent ) {
			wp_update_post( array(
				'ID'          => $att->ID,
				'post_parent' => $object_id,
			) );
		}
	}
}

function amapress_get_avatar_url( $id, $meta_name, $size, $default_image, $user = 0 ) {
	if ( empty( $meta_name ) ) {
		$meta_name = '_thumbnail_id';
	}
	if ( $user ) {
		$avatar = get_user_meta( $id, $meta_name, true );
	} else {
		$avatar = get_post_meta( $id, $meta_name, true );
	}
	if ( ! $avatar ) {
		$meta_name = 'amapress_icon_id';
		if ( $user ) {
			$avatar = get_user_meta( $id, $meta_name, true );
		} else {
			$avatar = get_post_meta( $id, $meta_name, true );
		}
	}

	$url = null;
	if ( $avatar ) {
		if ( is_array( $avatar ) ) {
			$avatar = $avatar[0];
		}
		$url = wp_get_attachment_image_src( $avatar, $size );
		if ( $url ) {
			$url = $url[0];
		}
	}

	if ( empty( $url ) ) {
		$url = AMAPRESS__PLUGIN_URL . 'images/' . $default_image;
	}

	return $url;
}

function amapress_get_font_icon( $icon_name ) {
	if ( strpos( $icon_name, 'fa-' ) === 0 ) {
		return '<i class="fa ' . $icon_name . '" aria-hidden="true"></i>';
	} else if ( strpos( $icon_name, 'dashicons-' ) === 0 ) {
		return '<span class="dashicons-before ' . $icon_name . '" aria-hidden="true"></span>';
	} else {
		return $icon_name;
	}
}

function amapress_get_icon_html( $option_name, $default ) {
	$optVal = Amapress::getOption( $option_name );
	if ( empty( $optVal ) ) {
		$optVal = $default;
	}
	if ( empty( $optVal ) ) {
		return '';
	}

	return amapress_get_font_icon( $optVal );
}

function amapress_echo_img_thumb( $filename, $alt, $size ) {
	$src = AMAPRESS__PLUGIN_URL . 'images/' . $filename;
	echo "<img src='$src' alt='$alt' class='img_thumb_$size' />";
}

function amapress_get_post_field_as_text( $post_id, $post_type, $field_name ) {
	return esc_html( get_post_meta( $post_id, "amapress_{$post_type}_{$field_name}", true ) );
}

function amapress_get_post_field_as_html( $post_id, $post_type, $field_name ) {
	return get_post_meta( $post_id, "amapress_{$post_type}_{$field_name}", true );
}

function mc_admin_users_caps( $caps, $cap, $user_id, $args ) {

//    var_dump($cap);
	foreach ( $caps as $key => $capability ) {

		if ( $capability != 'do_not_allow' ) {
			continue;
		}

		switch ( $cap ) {
			case 'edit_user':
			case 'edit_users':
				$caps[ $key ] = 'edit_users';
				break;
			case 'delete_user':
			case 'delete_users':
				$caps[ $key ] = 'delete_users';
				break;
			case 'create_users':
				$caps[ $key ] = $cap;
				break;
		}
	}

	return $caps;
}

add_filter( 'map_meta_cap', 'mc_admin_users_caps', 1, 4 );
remove_all_filters( 'enable_edit_any_user_configuration' );
add_filter( 'enable_edit_any_user_configuration', '__return_true' );

/**
 * Checks that both the editing user and the user being edited are
 * members of the blog and prevents the super admin being edited.
 */
function mc_edit_permission_check() {
	global $current_user, $profileuser;

	$screen = get_current_screen();

//    die($screen->id);

	wp_get_current_user();

	if ( ! is_super_admin( $current_user->ID ) && in_array( $screen->base, array(
			'user-edit',
			'user-edit-network'
		) ) ) { // editing a user profile
		if ( is_super_admin( $profileuser->ID ) ) { // trying to edit a superadmin while less than a superadmin
			wp_die( __( 'You do not have permission to edit this user.' ) );
		} elseif ( ! ( is_user_member_of_blog( $profileuser->ID, get_current_blog_id() ) && is_user_member_of_blog( $current_user->ID, get_current_blog_id() ) ) ) { // editing user and edited user aren't members of the same blog
			wp_die( __( 'You do not have permission to edit this user.' ) );
		}
	}
}

add_filter( 'admin_head', 'mc_edit_permission_check', 1, 4 );


add_filter( 'user_has_cap', 'amapress_check_permissions', 10, 3 );
function amapress_check_permissions( $allcaps, $cap, $args ) {
//    var_dump($cap);
//    die();
//    var_dump($allcaps);
//    die();
	return $allcaps;
}

function amapress_unconfirmed_do_network_admin( $do_network_admin ) {
	return false;
}

add_filter( 'unconfirmed_do_network_admin', 'amapress_unconfirmed_do_network_admin' );

function amapress_check_info_visibility( $value, $name, AmapressUser $user ) {
	if ( 'force' == $value ) {
		return true;
	}
	if ( $value == 'default' || Amapress::toBool( $value ) ) {
		if ( $name == 'roles' ) {
			return true;
		}
		$user_right = $user->getDisplayRight( $name );
		if ( ! empty( $user_right ) && $user_right != 'default' ) {
			return Amapress::toBool( $user_right );
		}
		if ( $name == 'avatar' ) {
			return get_option( 'show_avatars' );
		}
		if ( $name == 'tel_mobile' ) {
			if ( Amapress::toBool( Amapress::getOption( "allow_show_resp_distrib_tel" ) )
			     && AmapressDistributions::isCurrentUserResponsableThisWeek( $user->ID )
			) {
				return true;
			}
		}

		$def = Amapress::getOption( "allow_show_$name" );
		if ( empty( $def ) || ! Amapress::toBool( $def ) ) {
			return amapress_can_access_admin() || AmapressDistributions::isCurrentUserResponsableThisWeek();
		} else {
			return true;
		}
	} else {
		return false;
	}
}


function amapress_bbp_enable_visual_editor( $args = array() ) {
	$args['tinymce']   = true;
	$args['quicktags'] = false;
	$args['teeny']     = false;

	return $args;
}

add_filter( 'bbp_after_get_the_content_parse_args', 'amapress_bbp_enable_visual_editor' );

// Sets the display name to first name and last name
add_filter( 'pre_user_display_name', 'amapress_default_display_name' );
function amapress_default_display_name( $name ) {
	$firstname = null;
	if ( isset( $_POST['first_name'] ) ) {
		$firstname = sanitize_text_field( $_POST['first_name'] );
	}
	$lastname = null;
	if ( isset( $_POST['last_name'] ) ) {
		$lastname = sanitize_text_field( $_POST['last_name'] );
	}
	if ( ! empty( $firstname ) && ! empty( $lastname ) ) {
		$name = $firstname . ' ' . $lastname;
	}

	return $name;
}

add_action( 'user_register', 'amapress_multisite_save_names', 10, 1 );
function amapress_multisite_save_names( $user_id ) {
	if ( is_multisite() ) {
		if ( isset( $_POST['first_name'] ) ) {
			update_user_meta( $user_id, 'first_name', $_POST['first_name'] );
		}
		if ( isset( $_POST['last_name'] ) ) {
			update_user_meta( $user_id, 'last_name', $_POST['last_name'] );
		}
	}
}

function amapress_multisite_createuser_noconfirm( $type ) {
	if ( is_multisite() ) {
// Load up the passed data, else set to a default.
		$creating = isset( $_POST['createuser'] );
//        $new_user_login = $creating && isset( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : '';
		$new_user_firstname = $creating && isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '';
		$new_user_lastname  = $creating && isset( $_POST['last_name'] ) ? wp_unslash( $_POST['last_name'] ) : '';
//        $new_user_email = $creating && isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
		$new_user_uri = $creating && isset( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
//        $new_user_role = $creating && isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : '';
		$new_user_send_notification = $creating && ! isset( $_POST['send_user_notification'] ) ? false : true;
//        $new_user_ignore_pass = $creating && isset( $_POST['noconfirmation'] ) ? wp_unslash( $_POST['noconfirmation'] ) : '';

		?>
        <table class="form-table">
            <tr class="form-field">
                <th scope="row"><label for="first_name"><?php _e( 'First Name' ) ?> </label></th>
                <td><input name="first_name" type="text" id="first_name"
                           value="<?php echo esc_attr( $new_user_firstname ); ?>"/></td>
            </tr>
            <tr class="form-field">
                <th scope="row"><label for="last_name"><?php _e( 'Last Name' ) ?> </label></th>
                <td><input name="last_name" type="text" id="last_name"
                           value="<?php echo esc_attr( $new_user_lastname ); ?>"/></td>
            </tr>
            <tr class="form-field">
                <th scope="row"><label for="url"><?php _e( 'Website' ) ?></label></th>
                <td><input name="url" type="url" id="url" class="code"
                           value="<?php echo esc_attr( $new_user_uri ); ?>"/>
                </td>
            </tr>
            <tr class="form-field form-required user-pass1-wrap">
                <th scope="row">
                    <label for="pass1">
						<?php _e( 'Password' ); ?>
                        <span class="description hide-if-js"><?php _e( '(required)' ); ?></span>
                    </label>
                </th>
                <td>
                    <input class="hidden" value=" "/><!-- #24364 workaround -->
                    <button type="button"
                            class="button wp-generate-pw hide-if-no-js"><?php _e( 'Show password' ); ?></button>
                    <div class="wp-pwd hide-if-js">
						<?php $initial_password = wp_generate_password( 24 ); ?>
                        <span class="password-input-wrapper">
                        <input type="password" name="pass1" id="pass1" class="regular-text" autocomplete="off"
                               data-reveal="1" data-pw="<?php echo esc_attr( $initial_password ); ?>"
                               aria-describedby="pass-strength-result"/>
                    </span>
                        <button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0"
                                aria-label="<?php esc_attr_e( 'Hide password' ); ?>">
                            <span class="dashicons dashicons-hidden"></span>
                            <span class="text"><?php _e( 'Hide' ); ?></span>
                        </button>
                        <button type="button" class="button wp-cancel-pw hide-if-no-js" data-toggle="0"
                                aria-label="<?php esc_attr_e( 'Cancel password change' ); ?>">
                            <span class="text"><?php _e( 'Cancel' ); ?></span>
                        </button>
                        <div style="display:none" id="pass-strength-result" aria-live="polite"></div>
                    </div>
                </td>
            </tr>
            <tr class="form-field form-required user-pass2-wrap hide-if-js">
                <th scope="row"><label for="pass2"><?php _e( 'Repeat Password' ); ?> <span
                                class="description"><?php _e( '(required)' ); ?></span></label></th>
                <td>
                    <input name="pass2" type="password" id="pass2" autocomplete="off"/>
                </td>
            </tr>
            <tr class="pw-weak">
                <th><?php _e( 'Confirm Password' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="pw_weak" class="pw-checkbox"/>
						<?php _e( 'Confirm use of weak password' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Send User Notification' ) ?></th>
                <td>
                    <input type="checkbox" name="send_user_notification" id="send_user_notification"
                           value="1" <?php checked( $new_user_send_notification ); ?> />
                    <label
                            for="send_user_notification"><?php _e( 'Send the new user an email about their account.' ); ?></label>
                </td>
            </tr>
        </table>
		<?php
	}
	if ( ! is_super_admin() ) {
		?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Skip Confirmation Email' ); ?></th>
                <td>
                    <input type="checkbox" name="noconfirmation" id="adduser-noconfirmation"
                           value="1" <?php checked( isset( $_POST['noconfirmation'] ) ? $_POST['noconfirmation'] : 1, 1 ); ?> />
                    <label
                            for="adduser-noconfirmation"><?php _e( 'Add the user without sending an email that requires their confirmation.' ); ?></label>
                </td>
            </tr>
        </table>
		<?php
	}
}

add_action( "user_new_form", "amapress_multisite_createuser_noconfirm" );

add_filter( 'wpmu_signup_user_notification', 'amapress_auto_activate_users', 10, 4 );
function amapress_auto_activate_users( $user, $user_email, $key, $meta ) {
	if ( ! current_user_can( 'create_users' ) ) {
		return false;
	}

	if ( ! empty( $_POST['noconfirmation'] ) && $_POST['noconfirmation'] == 1 ) {
		wpmu_activate_signup( $key );

		return false;
	}
}

/*
if ( is_admin() ) {
	require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress-admin.php' );
	add_action( 'init', array( 'Amapress_Admin', 'init' ) );
}
*/

//add wrapper class around deprecated akismet functions that are referenced elsewhere
//require_once( AMAPRESS__PLUGIN_DIR . 'wrapper.php' );

if ( ! function_exists( 'get_user_by' ) ) :
	function get_user_by( $field, $value ) {
		$userdata = WP_User::get_data_by( $field, $value );

		if ( ! $userdata ) {
			if ( 'email' == $field ) {
				global $wpdb;
				if ( ! $user_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT user_id FROM $wpdb->usermeta WHERE meta_key IN ('email2','email3','email4') AND meta_value = %s", $value
				) )
				) {
					return false;
				}

				$userdata = WP_User::get_data_by( 'ID', intval( $user_id ) );
				if ( ! $userdata ) {
					return false;
				}
			} else {
				return false;
			}
		}

		$user = new WP_User();
		$user->init( $userdata );

		return $user;
	}
endif;

function amapress_user_contact_methods( $user_contact ) {
	// Add user contact methods
	$user_contact['email2'] = __( 'Email 2' );
	$user_contact['email3'] = __( 'Email 3' );
	$user_contact['email4'] = __( 'Email 4' );

	return $user_contact;
}

add_filter( 'user_contactmethods', 'amapress_user_contact_methods' );

function amapress_user_has_cap( $allcaps, $caps, $args ) {
	if ( ! isset( $args[0] ) ) {
		return $allcaps;
	}

	$cap = $args[0];
//    var_dump($cap);
	if ( isset( $args[2] ) && ( 'delete_users' == $cap || 'remove_users' == $cap || 'delete_user' == $cap || 'remove_user' == $cap ) ) {
		$allcaps[ $caps[0] ] = apply_filters( 'amapress_can_delete_user', true, $args[2] );
//        var_dump($allcaps[$cap[0]]);
	} else if ( isset( $args[2] ) && strpos( $cap, 'delete_' ) === 0 ) {
		if ( ! isset( $allcaps[ $cap ] ) || ! $allcaps[ $cap ] ) {
			return $allcaps;
		}

		$post_id = isset( $args[2] ) ? $args[2] : 0;

		$post_type = amapress_simplify_post_type( get_post_type( $post_id ) );

//        die($post_type);
		$allcaps[ $caps[0] ] = apply_filters( "amapress_can_delete_$post_type", true, $post_id );
//        $post_types = AmapressEntities::getPostTypes();
//        if (isset($post))
	} else if ( isset( $args[1] ) ) {
		$user_id = isset( $args[1] ) ? $args[1] : amapress_current_user_id();
		$user    = AmapressUser::getBy( $user_id );
		if ( $user ) {
			$user_role_caps = $user->getAmapRoleCapabilities();
			if ( ! empty( $user_role_caps ) && $cap = "access_admin" ) {
				$allcaps[ $caps[0] ] = true;
			} else if ( in_array( $cap, $user_role_caps ) ) {
				$allcaps[ $caps[0] ] = true;
			}
		}
	}

//    var_dump($cap);
//    var_dump($args);
	return $allcaps;
}

add_filter( 'user_has_cap', 'amapress_user_has_cap', 10, 3 );

if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		/** @var AmapressSMTPMailingQueue $amapress_smtpMailingQueue */
		global $amapress_smtpMailingQueue;

		if ( $amapress_smtpMailingQueue ) {
			return $amapress_smtpMailingQueue->wp_mail( $to, $subject, $message, $headers, $attachments );
		} else {
//            wp_mail($to, $subject, $message, $headers, $attachments);
			die( "Uh, no wp_mail ???" );
		}
	}
}

//add_filter('pre_user_query', function($query) {
//    var_dump("SELECT $query->query_fields $query->query_from $query->query_where $query->query_orderby $query->query_limit");
//}, 50);
//
//    add_filter('posts_request', function($query) {
//        var_dump($query);
//        return $query;
//    }, 20);