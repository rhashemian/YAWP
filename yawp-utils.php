<?php
/*
Plugin Name: YAWP Utils 
Plugin URI: 
Description: Yet Another WordPress Plugin. Set blog's email address, do real-time find and replace. After activating, look under the 'Settings' menu for configuration.
Author: Robert Hashemian
Version: 1.0
Author URI: https://www.hashemian.com/
License: GPLv2 or later
*/

// Make sure we don't expose any info if called directly
function_exists('add_action') or die("YAWP! It's Yet Another WordPress Plugin.");

define('YAWP_VERSION', '1.0');
define('YAWP__PLUGIN_DIR', plugin_dir_path( __FILE__ ));

require_once(YAWP__PLUGIN_DIR . 'yawp.mail.php');
require_once(YAWP__PLUGIN_DIR . 'yawp.searchreplace.php');
require_once(YAWP__PLUGIN_DIR . 'yawp.shortcodes.php');

// add to Settings menu
add_action('admin_menu', function() {
  add_options_page('YAWP Set Email', 'YAWP Set Email', 'manage_options', __FILE__ . '.mail', 'yawp_utils_admin_page_mail');
  add_options_page('YAWP Find/Replace', 'YAWP Find/Replace', 'manage_options', __FILE__ . '.replace', 'yawp_utils_admin_page_replace');
});

// endpoint called in all yawp settings pages when clicked on 'activate' checkbox. called from yawp.ajaxcommon.js
add_action('wp_ajax_yawp_action', function() {
  $option_key = sanitize_text_field($_POST['option_key']);
  switch ($option_key) {
    case '':
      check_ajax_referer("yawp.mail", "ajax_nonce");
      break;
    case '2':
      check_ajax_referer("yawp.searchreplace", "ajax_nonce");
      break;
    default:  // invalid input, bail.
      wp_die();
  }
  // extract status as boolean
  $option_value = filter_var($_POST['active_checkbox'], FILTER_VALIDATE_BOOLEAN);
  update_option("yawp_utils_yawpchecked" . $option_key , $option_value);
  // retuned as data to client to show resulting status to user
  echo ($option_value?"Activated.":"Deactivated.");
  wp_die(); // required. to end AJAX request.
});
