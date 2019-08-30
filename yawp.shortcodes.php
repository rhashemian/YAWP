<?php
// display active plugins on regular pages but only for admin
add_shortcode('yawp_show_plugins', function(){
  if (current_user_can('administrator')) {
    $active_plugins = get_option( 'active_plugins' );
    $plugins = "";
    if( count( $active_plugins ) > 0 ){
      $plugins = "<ul>";
      foreach ( $active_plugins as $plugin ) {
        $plugins .= "<li>" . $plugin . "</li>";
      }
      $plugins .= "</ul>";
    }
    return $plugins; //. $_SERVER['REQUEST_URI'];
  }
});
