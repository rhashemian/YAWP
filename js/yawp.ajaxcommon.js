// capture click on checkbox to activate/deactivate feature
jQuery('input[name=yawpchecked]').change(function(){
  var data = {
    'action': 'yawp_action', // call endpoint function in yawp-utils.php
    'active_checkbox': jQuery(this).is(':checked'), //was it checked or cleared?
    'ajax_nonce': oparam.ajax_nonce, // passed in nonce value
    'option_key': oparam.ikey // key for wp_options table appended to yawp_utils_yawpchecked
  };
  // show returned status message to user
  jQuery.post(ajaxurl, data, function(response) {
    jQuery('#yawp_message').html(response);
  });    
});
