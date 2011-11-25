<?php
/*
 * Get the Widget Wrangler Settings
 * 
 * @return settings array
 */
function ww_get_settings()
{
  if ($settings = get_option("ww_settings")){
    $settings = unserialize($settings);
  }
  else{
    ww_settings_set_default();
    $settings = ww_get_settings();
  }
  
  return $settings;
}
/*
 * Reset all pages to use the default widget settings
 */
function ww_settings_reset_widgets()
{
  global $wpdb;
  $query = "TRUNCATE TABLE `".$wpdb->prefix."ww_post_widgets`";
  $wpdb->query($query);
}
/*
 * Save the Widget Wrangler Settings page
 */
function ww_settings_save($post)
{
  $settings = serialize($post['settings']);
  
  // save to wordpress options
  update_option("ww_settings", $settings);
}
/*
 * Default settings
 */
function ww_settings_set_default()
{
  $settings["capabilities"] = "simple";
  $settings["post_types"]['page'] = "page";
  $settings["post_types"]['post'] = "post";
  update_option("ww_settings", serialize($settings));
}