<?php
/*
Plugin Name: Alternate OpenID for WordPress
Plugin URI: 
Description: Allow OpenID based commenters to add their thoughts on your WordPress blog.
Author: Jerry Yeager
Version: 0.04
Author URI: http://www.scene-naturally.dyndns.org
Email: jerry@scene-naturally.dyndns.org
*/

define('OPENID_ICON', get_settings('siteurl') . '/wp-content/plugins/alternate-openid/openid.gif');
require ('SimpleOpenID.class.php');

class altopenid {

	/**** First part of authentication. ****/
	function start_openid_authenticate() {
		if( empty($_POST['openid_url']) ) {
			require_once( ABSPATH . WPINC . '/pluggable.php');
			wp_safe_redirect($_POST['come-back_to']);
		} else {
			$openid = new SimpleOpenID;
			$openid->SetIdentity($_POST['openid_url']);
			$openid->SetApprovedURL($_POST['come-back_to']);
			$openid->SetTrustRoot(get_bloginfo('url'));
			$openid->SetOptionalFields(array('nickname','fullname','email','dob','gender','postcode','country','language','timezone'));
			if ( ($server = $openid->GetOpenIDServer()) ) {
				setcookie('wp_openid_comment_page_'. COOKIEHASH, $_POST['come-back_to'], time()+33100, COOKIEPATH, $_SERVER['server_name']);
				setcookie('wp_openid_comment_p2_'. COOKIEHASH, $_POST['openid_url'], time()+33100, COOKIEPATH, $_SERVER['server_name']);
			} else {
				wp_die( __('Error: I could not create the openid connection. Is the OpenID URL you provided active/valid?') );
			}
			$openid->Redirect();
		}
	}

	/**** Second part of authentication ****/
	function finish_openid_authenticate() {
		$openid = new SimpleOpenID;

		if ($_GET['openid_identity']) 
			$openid->SetIdentity($_GET['openid_identity']);
		else
			wp_die( __('<p>Authorisation failed: Please re&ndash;check the credentials you entered.</p>') );

		if ( $openid->ValidateWithServer() ) {

			require_once( ABSPATH . WPINC . '/formatting.php');
			require_once( ABSPATH . WPINC . '/pluggable.php');
			require_once( ABSPATH . WPINC . '/registration.php');
			require_once( ABSPATH . WPINC . '/capabilities.php');

			global $wpdb;
			global $error;
			$this_user = $wpdb->escape( sanitize_user( $openid->OpenID_Standarize($_COOKIE['wp_openid_comment_p2_'.COOKIEHASH]), true ) );
			$this_user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_login = '$this_user'");

			if ( $this_user_id ) {
				$user = new WP_User($this_user_id);
				$new_pass = substr( uniqid( microtime() ), 0, 7);
				wp_set_password( $new_pass, $this_user_id );

				if( wp_login( $user->user_login, $new_pass ) ) {
					wp_setcookie($user->user_login, md5($user->user_pass), true, '', '', true);
					setcookie('wp_openid_comment_page_'. COOKIEHASH, '', time()-33100, COOKIEPATH, $_SERVER['server_name']);
					setcookie('wp_openid_comment_p2_'. COOKIEHASH, '', time()-33100, COOKIEPATH, $_SERVER['server_name']);
					do_action('wp_login', $user->ID);
					wp_safe_redirect($_GET['openid_return_to']);
					exit();
				} else {
					wp_die( __('<p>Your OpenID authentication attempt validated, but the WordPress login failed. Please notify the administrator.</p>') );
				}
			} else {
				if (get_option('users_can_register')) {
					$user_options = array(
						'user_login'		=> $this_user,
						'user_pass'			=> substr( uniqid( microtime() ), 0, 7),
						'user_url'			=> $_COOKIE['wp_openid_comment_p2_'.COOKIEHASH],
						'user_registered'	=> date('Y-m-d H:i:s'),
						'first_name'		=> '',
						'last_name'			=> '',
					);
					if ($_GET['openid_sreg_email']) $user_options['user_email'] = $_GET['openid_sreg_email'];
					if ($_GET['openid_sreg_fullname']) $user_options['user_nicename'] = $_GET['openid_sreg_fullname'];
					if ($_GET['openid_sreg_nickname']) {
						$user_options['nickname'] = $_GET['openid_sreg_nickname'];
						$user_options['display_name'] = $_GET['openid_sreg_nickname'];
					}
					if ($_GET['openid_sreg_language']) $user_options['description'] = 'Language: '.$_GET['openid_sreg_language'].'&#13;';
					if ($_GET['openid_sreg_dob']) $user_options['description'] .= 'Birth Date: '.$_GET['openid_sreg_dob'].'&#13;';
					if ($_GET['openid_sreg_gender']) $user_options['description'] .= 'Gender: '.$_GET['openid_sreg_gender'].'&#13;';
					if ($_GET['openid_sreg_country']) $user_options['description'] .= 'Country: '.$_GET['openid_sreg_country'].'&#13;';
					if ($_GET['openid_sreg_postcode']) $user_options['description'] .= 'Postal Code: '.$_GET['openid_sreg_postcode'].'&#13;';
					if ($_GET['openid_sreg_timezone']) $user_options['description'] .= 'Time Zone: '.$_GET['openid_sreg_timezone'].'&#13;';

					$user = new WP_User( wp_insert_user($user_options) );
					wp_setcookie($user->user_login, md5($user->user_pass), true, '', '', true);
					setcookie('wp_openid_comment_page_'. COOKIEHASH, '', time()-33100, COOKIEPATH, $_SERVER['server_name']);
					setcookie('wp_openid_comment_p2_'. COOKIEHASH, '', time()-33100, COOKIEPATH, $_SERVER['server_name']);
					do_action('wp_login', $user->ID);
					wp_safe_redirect($_GET['openid_return_to']);
					exit();
				} else {
					wp_die( __('<p>Your OpenID authentication attempt validated, but this blog has new user registrations disabled. Please notify the administrator.</p>') );
				}
			}
		} else if ($openid->IsError() == true) {
			$error = $openid->GetError();
			wp_die( __('<p>OpenID auth problem</p><p>Code: {'.$error['code'].'}</p><p>Description: {'.$error['description'].'}</p><p>OpenID: {'.$identity.'}</p>') );
		} else {
			wp_die( __('<p>Authorisation failed: Please check the entered credentials and double check the caps locks key.</p>') );
		}
	}
}

/**** Part of this function goes in your comments template See the README example****/
function openid_url_input_text() {
	$user = wp_get_current_user();
	if (!$user->id) {
		printf('<p style="font-size: 1.2em;">Or, enter your <a href="http://openid.net/">OpenID</a> URL: (make sure your browser has cookies turned on)');
		printf('<br /><input type="text" name="openid_url" id="openid_url" size="25" tabindex="4" style="background: url('.OPENID_ICON.') center left no-repeat; padding-left: 19px;" />');
		printf('<input type="hidden" name="come-back_to" value="' .get_permalink().'" />');
		printf('<br />Your comments may need to be approved by the moderator before they appear.</p>');
	}
}

/**** initialize plugin logic when someone wants to login, ****/
if( eregi('wp-comments-post.php$', $_SERVER['PHP_SELF']) && $_POST ) {
	$begin = new altopenid();
	$begin->start_openid_authenticate();
}
if($_GET['openid_mode'] === 'id_res') {
	$begin = new altopenid();
	$begin->finish_openid_authenticate();
}

?>
