<?php 
/*
Plugin Name: PromoLiveChat
Plugin URI: http://promoheads.com/software.php
Description: This plugin install chat on your site. After activation go to Options page and insert your jabber accounts.
Version: 1.0
Author: Promoheads
Author URI: promoheads.com
*/

/*  Copyright 2015  Promoheads  (email : master@promoheads.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


	// Stop direct call
if ( ! defined( 'ABSPATH' ) ) { die("You are not allowed to call this page directly."); }

class promolivechat {

	static function install() {

  	add_option('promolivechat_info', '');
  	add_option('promolivechat_token', '');
   	add_option('promolivechat_uuid', '');
		add_option('promolivechat_admin_email', '');
		add_option('promolivechat_active', 0);
		add_option('promolivechat_jabber_acc', '');
  }

  static function uninstall() {
    delete_option('promolivechat_info');
    delete_option('promolivechat_token');
    delete_option('promolivechat_uuid');
    delete_option('promolivechat_admin_email');
    delete_option('promolivechat_active');
    delete_option('promolivechat_jabber_acc');
  }


  function promolivechat() {
    if (get_option('promolivechat_active')) {
        add_action('wp_footer', function () {
            $uuid = get_option('promolivechat_uuid');
            echo '<script>window.newChatMembers = "' . $uuid . '";</script>';
            echo '<script src="https://chat.cepbep.org:3001/simple/index.js?ver=1.0"></script>';
            //echo '<script src="http://chat:3000/simple/index.js?ver=1.0"></script>';
        });
    }
		add_action('admin_menu',  array (&$this, 'admin') );
	}

	function admin () {
		if ( function_exists('add_options_page') ) {
			add_options_page( 'PromoLiveChat Options', 'PromoLiveChat', 8, basename(__FILE__), array (&$this, 'admin_form') );
		}
	}
	
	function admin_form() {
		$admin_email = get_bloginfo('admin_email');
		update_option('promolivechat_admin_email', $admin_email);
		$domain = parse_url(get_bloginfo('url'));

		$jabber_acc = get_option('promolivechat_jabber_acc');
		$active = get_option('promolivechat_active');
		$info = get_option('promolivechat_info');
		$token = get_option('promolivechat_token');
		$error = [];
		$err_message = '';

    if ( isset($_POST['submit']) && ('register' == $_POST['action'] )) {

      if ( function_exists('current_user_can') && !current_user_can('manage_options') )
        die ( _e('Hacker?', 'promolivechat') );

      if (function_exists ('check_admin_referer') && !check_admin_referer('promolivechat_form') )
        die ( _e('Hacker?', 'promolivechat') );

      if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'promolivechat_form' ))
        die ( _e('Hacker?', 'promolivechat') );

      $args = array(
        'sslverify' => false
      );
	  
      $response = wp_remote_get('https://chat.cepbep.org:3001/register?email=' . $admin_email . '&domain=' . $domain['host'], $args);  //  request for Registration
      //$response = wp_remote_get('http://chat:3000/register?email=' . $admin_email . '&domain=' . $domain['host'], $args);  //  request for Registration

      if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response); // use the content
        $resp = json_decode($body);

        if ('new' == $resp->status) {
          update_option('promolivechat_active', 1);
          update_option('promolivechat_token', $resp->token);
          update_option('promolivechat_uuid', $resp->uuid);
        } else if ('registered' == $resp->status) {
          update_option('promolivechat_info', $resp->info);
          update_option('promolivechat_active', 0);
        }
      }
		$active = get_option('promolivechat_active');
		$info = get_option('promolivechat_info');
		$token = get_option('promolivechat_token');
	  
    } else if ( isset($_POST['submit']) ) {
		    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
          die ( _e('Hacker?', 'promolivechat') );

        if (function_exists ('check_admin_referer') && !check_admin_referer('promolivechat_form') )
          die ( _e('Hacker?', 'promolivechat') );

      if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'promolivechat_form' ))
        die ( _e('Hacker?', 'promolivechat') );

        $args = array(
			'sslverify' => false,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token
			)
        );

        $new_email = '';
        $list = '';
        $matches = array();
        $form_email_field = sanitize_email($_POST['promolivechat_admin_email']);
        if (($admin_email != $form_email_field)&&('' != $form_email_field)) {
          if (is_email($form_email_field)) {
            $new_email = $form_email_field;
          } else {
            $error[] = 'Enter valid email address.';
          }
        }
        $form_jabber_field = sanitize_text_field($_POST['promolivechat_jabber_acc']);
        if ($jabber_acc != $form_jabber_field) {
          $list = array_map('trim', array_filter( array_unique(explode("\r\n", $form_jabber_field))));
          $list = array_slice($list, 0, 5);
        }

        if (empty($error)) {
          $response = wp_remote_get('https://chat.cepbep.org:3001/update?email=' . $new_email . '&acc=' . json_encode($list), $args);  //  request for Admin Email Update
          //$response = wp_remote_get('http://chat:3000/update?email=' . $new_email . '&acc=' . json_encode($list), $args);  //  request for Admin Email Update

          if (is_array($response) && !is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response); // use the content
            $resp = json_decode($body);
		
            if ('OK' == $resp->status) {
              if ('' != $new_email) update_option('promolivechat_admin_email', $new_email);
              if ('' != $list) update_option('promolivechat_jabber_acc', implode("\r\n", $list));
            } else {
              $error[] = 'Error occured. Try again later.';
            }
          } else {
            $error[] = 'Error occured. Try again later.';
          }
        }
        if (count($error)) {
          $err_message = implode("<br>", $error);
        }

            $jabber_acc = get_option('promolivechat_jabber_acc');
            $admin_email = get_option('promolivechat_admin_email');
	}

    if ((0 == $active)&&('' == $info)) {
      ?>
      <div class='wrap'>
        <h2><?php _e('PromoLiveChat Settings', 'promolivechat'); ?></h2>

        <form name="promolivechat" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=promolivechat.php">

          <!-- Имя mychat_form используется в check_admin_referer -->
          <?php
          if (function_exists('wp_nonce_field')) {
            wp_nonce_field('promolivechat_form');
          }
          ?>

          <input type="hidden" name="action" value="register"/>

          <p class="submit">
            <input type="submit" name="submit" value="<?php _e('Register my domain at PromoLiveChat server ') ?>"/>
          </p>
        </form>
      </div>
    <?
    } else {
      ?>
      <div class='wrap'>
        <h2><?php _e('PromoLiveChat Settings', 'promolivechat'); ?></h2>
        <div style='color: green;'><?php echo $info ?></div>
        <div style='color: red;'><?php echo $err_message ?></div>

        <form name="promolivechat" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=promolivechat.php">

          <!-- Имя mychat_form используется в check_admin_referer -->
          <?php
          if (function_exists('wp_nonce_field')) {
            wp_nonce_field('promolivechat_form');
          }
          ?>

          <table class="form-table">
            <tr valign="top">
              <th scope="row"><?php _e('Domain status:', 'promolivechat'); ?></th>

              <td>
                <?php if ($active) echo 'Active'; else echo 'NOT Active'; ?>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row"><?php _e('Email:', 'promolivechat'); ?></th>

              <td>
                <input type="text" name="promolivechat_admin_email" size="78"
                       value="<?php echo esc_attr($admin_email); ?>"/>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row"><?php _e('Jabber accounts:', 'promolivechat'); ?></th>

              <td>
                <textarea name="promolivechat_jabber_acc" rows="5"
                          cols="75"><?php echo esc_textarea($jabber_acc); ?></textarea>
              </td>
            </tr>

          </table>

          <input type="hidden" name="action" value="update"/>

          <p class="submit">
            <input type="submit" name="submit" value="<?php _e('Save Changes') ?>"/>
          </p>
        </form>
      </div>
      <?
    }
	}
}
register_activation_hook( __FILE__, array( 'promolivechat', 'install' ) );
register_deactivation_hook( __FILE__, array( 'promolivechat', 'uninstall' ) );
$promolivechat = new promolivechat();
?>
