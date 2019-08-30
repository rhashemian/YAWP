<?php

// the plugin's engine - buffer  page and alter content before rendering.
// do not ever alter for this page itself
// do not alter if feature not active
// do not alter if in admin and feature not active for admin area
if (get_option('yawp_utils_yawpchecked2') && (!is_admin() || (false===strpos($_GET['page'],"yawp-utils/yawp-utils.php.replace") && get_option('yawp_utils_yawpadminyes')))) {
  $srArray = get_option('yawp_utils_SearchReplace',[]);
  // filter out if search term is empty or search and replace terms are equal. 
  $srArray = array_filter($srArray, function($row) { return !empty($row["yawpsearch"]) && $row["yawpsearch"]!=$row["yawpreplace"]; });
  if (!empty($srArray)) {
    // wordpress plugin review team wants the plugin to use wp_kses_post on the content. 
    // but wp_kses_post strips tags fanatically including those form frameworks such as Angular or Vue.js.
    // since only an admin can activate search/replace, we let him/her be in total control here.
    switch (get_option('yawp_utils_sr_type')) {
      case "2": // experimental, when there's caching plugin active such as supercache or totalcache
        add_filter('wp_cache_ob_callback_filter',function($buffer) use ($srArray) {return (yawp_transFunc($buffer, $srArray));});
        add_filter('w3tc_pagecache_set',function($buffer,$this_page_key) use ($srArray) {return (yawp_transFunc($buffer, $srArray));});
        break;
      case "1": // safe replace for a few filters
        foreach(['the_content','the_title','comment_text'] as $filterThis)
          add_filter($filterThis, function($buffer) use ($srArray) {return (yawp_transFunc($buffer, $srArray));});
        break;
      default:  //0 or nothing - nuclear option. full replace everything in raw page source before rendering.
        add_action('init', function() use ($srArray) { 
          ob_start(function($buffer) use ($srArray) {return yawp_transFunc($buffer, $srArray);});
        });
    }
  }
}

// select replace function depending on case (in)sensitivity and do the work.
function yawp_transFunc($buffer, $srArray) {
  foreach ($srArray as $row) {
    $yawpcase = empty($row["yawpcase"])?'str_ireplace':'str_replace';
    $buffer=$yawpcase($row["yawpsearch"], $row["yawpreplace"], $buffer);
  }
  return $buffer;
};

// admin page
function yawp_utils_admin_page_replace() {
  // only let admins in.
  current_user_can('administrator') || wp_die("Sorry, this area is restricted to administrators.");

  wp_enqueue_script("yawp_script",plugin_dir_url( __FILE__ )."js/yawp.searchreplace.js",array(),YAWP_VERSION);
  wp_enqueue_script("yawp_ajaxcommon_script",plugin_dir_url( __FILE__ )."js/yawp.ajaxcommon.js",array(),YAWP_VERSION);
  // pass param to yawp.ajaxcommon.js to indicate which parameter to update in wp_options
  wp_localize_script("yawp_ajaxcommon_script", 'oparam', ["ikey" => "2","ajax_nonce" => wp_create_nonce("yawp.searchreplace")]);
  wp_enqueue_style("fa_style","https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css",array(),YAWP_VERSION);
  wp_enqueue_style("yawp_style",plugin_dir_url( __FILE__ )."css/yawp.searchreplace.css",array(),YAWP_VERSION);

  $srArray = [];
  $yawp_sr_type =$yawpchecked2=$yawpadminyes=null;
  $script="";
  // check nonce field to make sure the post is legit and within admin area.
  if (isset($_POST['yawpupdate']) && check_admin_referer('yawp_replace_action','yawp_replace_nonce_field')) {
    foreach ($_POST as $key => $value) {
      // go thru post'ed rows, filter out nonsense and create a php array of the rows with automatically starts with index 0.
      if (strpos($key, "yawpsearch_")===0 && !empty($value)) {
        $kindex=str_replace("yawpsearch_","",$key); // row number on the page. may not be consecutive due to add/delete
        // grab the 3 fields and stuff into array
        // no validation or sanitazing for search or replace terms. user can choose any value for them.
        $srArray[]=["yawpsearch" => $value,
                    "yawpreplace" => $_POST["yawpreplace_".$kindex],
                    "yawpcase" => ($_POST['yawpcase_'.$kindex]?true:false)  // only boolean valid
                   ];
      }
    }
    // serialize final array into wp_options.
    update_option('yawp_utils_SearchReplace', stripslashes_deep($srArray));
    // save other fields in wp_options table
    update_option('yawp_utils_yawpchecked2', $yawpchecked2 = ($_POST['yawpchecked']?true:false)); // only boolean valid
    update_option('yawp_utils_yawpadminyes', $yawpadminyes = ($_POST['yawpadminyes']?true:false)); // only boolean valid
    update_option('yawp_utils_sr_type', $yawp_sr_type = sanitize_text_field($_POST['yawp_sr_type']));
    $yawp_utils_message= "Updated.";
  }
  else {
    // we're not in POST, so pull data if any from wp_options.
    $srArray = get_option('yawp_utils_SearchReplace',[]);
    $yawpchecked2 = get_option('yawp_utils_yawpchecked2');
    $yawpadminyes = get_option('yawp_utils_yawpadminyes');
    $yawp_sr_type = get_option('yawp_utils_sr_type');
  }
  
  // append an inline script to choose a default type from option dropdown if none is found above.
  $script="document.querySelector('#yawp_sr_type').selectedIndex=parseInt(". ($yawp_sr_type===false?'1':$yawp_sr_type) .");";
  // fill rows with post'ed or wp_options data
  foreach ($srArray as $row) {
    $script .= "addRow('{$row["yawpsearch"]}','{$row["yawpreplace"]}','{$row["yawpcase"]}');";
  }
  // add an extra empty row at end
  wp_add_inline_script("yawp_script","$script addRow();");
    
?>
<div class="yawp_utils">
  <form method="post">
  <h1 style="float:left">YAWP Utils - Find/Replace</h1>  
  <div style="padding-top:20px;">&nbsp; &nbsp; &nbsp; 
  Activate? <input type="checkbox" name="yawpchecked" <?= $yawpchecked2 ? "checked": ""; ?>></div>
  <div style="clear:both">
  <div id="yawp_message" class="yawp_utils_message"><?= $yawp_utils_message ?></div>
  <p>Terms are found and replaced in the order that they appear here:</p>
  
  Include Admin Area? <input type="checkbox" name="yawpadminyes" <?= $yawpadminyes ? "checked": ""; ?>>
  (Safer to keep this box clear)
  <div style="height:10px;"></div>
  Replace Option: <select name="yawp_sr_type" id="yawp_sr_type">
    <option value="0">Full Page (less safe)</option>
    <option value="1">Title/Content/Comments (safe)</option>
    <option value="2">WP Super Cache/W3 Total Cache (experimental)</option>
  </select>
  <br /><br />
  * Keep these rows to a minimum as every row adds additional workload to your server.
  <table id="ttable">
    <tr>
    <th>Search For / Case-sensitive?</th>
    <th>Replace With</th>
    <th><button type="button" onclick="addRow()" id="addrow"><i class="fa fa-bars"></i> Add Row</button></th>
    </tr>
  </table>
  <?php wp_nonce_field('yawp_replace_action','yawp_replace_nonce_field'); ?>
  <input type="submit" name="yawpupdate" value="Update" />
  </form>
</div>

<!-- row template. used by js file -->
<template id="tpl">
  <tr id="row_{yawpindex}">
  <td><input type="text" placeholder="[this field is empty]" value="" id="yawpsearch_{yawpindex}"> <input type="checkbox" id="yawpcase_{yawpindex}"></td>
  <td><input type="text" placeholder="[this field is empty]" value="" id="yawpreplace_{yawpindex}"></td>
  <td><i class="fa fa-window-close" aria-hidden="true" id="yawpremove_row_{yawpindex}" onclick="removeRow(this)" title="Delete Row" ></i></td>
  </tr>
</template>

<?php
}

