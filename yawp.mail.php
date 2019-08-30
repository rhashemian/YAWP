<?php
// run it in all pages if activated.
if (get_option('yawp_utils_yawpchecked')) {
  // grab saved data from wp_options and run the filters
  $yawpname = get_option('yawp_utils_yawpname', '');
  $yawpemail = get_option('yawp_utils_yawpemail', '');
  if (!empty($yawpname))
    add_filter( 'wp_mail_from_name', function() use($yawpname) { return $yawpname; } );
  if (!empty($yawpemail))
    add_filter( 'wp_mail_from', function() use($yawpemail) { return $yawpemail; } );
}

// admin page
function yawp_utils_admin_page_mail() {
  wp_enqueue_script("yawp_ajaxcommon_script",plugin_dir_url( __FILE__ )."js/yawp.ajaxcommon.js",array(),YAWP_VERSION);
  // pass param to yawp.ajaxcommon.js to indicate which parameter to update in wp_options
  wp_localize_script("yawp_ajaxcommon_script", 'oparam', ["ikey" => "","ajax_nonce" => wp_create_nonce("yawp.mail")]);
  wp_enqueue_style("yawp_style",plugin_dir_url( __FILE__ )."css/yawp.mail.css",array(),YAWP_VERSION);
  // grab values from wp_options if we're not post'ing them back
  if (!isset($_POST['yawpupdate'])) {
    $yawpemail = get_option('yawp_utils_yawpemail', '');
    $yawpname = get_option('yawp_utils_yawpname', '');
    $yawpchecked = get_option('yawp_utils_yawpchecked', false);
  }
  else if (isset($_POST['yawpupdate'])) {
    $yawpemail=is_email($_POST['yawpemail']);
    $yawpname=sanitize_text_field($_POST['yawpname']);
    $yawpchecked=($_POST['yawpchecked']?true:false); // only boolean valid
    if ($yawpemail) {
      update_option('yawp_utils_yawpemail', sanitize_email($yawpemail));
      update_option('yawp_utils_yawpname', $yawpname);
      update_option('yawp_utils_yawpchecked', $yawpchecked);
      $yawp_utils_message= "Updated.";
    }
    else {
      $yawp_utils_message= "Please specify a valid email address.";
    }
  }
  // send test email. hopefully a server is there or configured to send it along.
  if (isset($_POST['yawptest'])) {
    if (!is_email($_POST['yawptestemail'])) {
      $yawp_utils_message= "Please specify a valid email address.";
    }
    else {
      wp_mail(sanitize_email($_POST['yawptestemail']),"Test Subject From YAWP Utils", "Test Message From YAWP Utils @ ".get_site_url());
      $yawp_utils_message= "Test email sent.";
    }
  }
  
?>
<div class="yawp_utils">
  <form method="post">
  <h1 style="float:left">YAWP Utils - Set Email Address</h1>
  <div style="padding-top:20px;">&nbsp; &nbsp; &nbsp;
  Activate? <input type="checkbox" name="yawpchecked" <?= $yawpchecked ? "checked": "" ?>></div>
  <div style="clear:both">
  <div id="yawp_message" class="yawp_utils_message"><?= $yawp_utils_message ?></div>
  <p>Set the address for emails sent from this blog:</p>
  <div style="height:10px;"></div>
  <table><tr>
  <td>Email address:</td><td><input type="email" value="<?= $yawpemail ?>" name="yawpemail" placeholder="email@example.com"></td>
  </tr><tr>
  <td>Email name:</td><td><input type="text" value="<?= $yawpname ?>" name="yawpname" placeholder="FirstName LastName"></td>
  </tr></table>
  <input type="submit" name="yawpupdate" value="Update" />
  <hr>
  <span style="font-weight:bold">Send a test email to:</span>
  <input type="email" name="yawptestemail"  value="<?= $_POST['yawptestemail'] ?>" placeholder="email@example.com">
  <input type="submit" name="yawptest" value="Send Email" />
<br />
  </form>
</div>

<?php
}

