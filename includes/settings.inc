<?php 
/*
 * Reset all pages to use the default widget settings
 */
function ww_settings_reset_widgets()
{
  global $wpdb;
  $query = "DELETE FROM `".$wpdb->prefix."postmeta` WHERE `meta_key` = 'ww_post_widgets'";
  $wpdb->query($query);
}
/*
 * Save the Widget Wrangler Settings page
 */
function ww_settings_save($post)
{
  // make into array
  $post_types = explode(",", $post['settings']['post_types']);
  // remove white space
  for($i=0;$i<count($post_types);$i++){
    $post_types[$i] = trim($post_types[$i]);
    // don't allow widgets on widget pages
    if($post_types[$i] == "widget"){
      unset($post_types[$i]);
    }
  }
  $post['settings']['post_types'] = $post_types;
  $settings = serialize($post['settings']);
  
  // save to wordpress options
  update_option("ww_settings", $settings);
}
