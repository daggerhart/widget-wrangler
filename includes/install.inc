<?php
function ww_activation()
{
  // table updates
  ww_post_widgets_table();
  ww_widget_data_table();
  ww_widget_spaces_table();
  ww_widget_space_term_relationships_table();
}

/*
 * Create post - widgets table
 */
function ww_post_widgets_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";
  
  $sql = "CREATE TABLE " . $table . " (
	  post_id mediumint(11) NOT NULL,
	  widgets text NOT NULL,
	  UNIQUE KEY id (post_id)
  );";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
/*
 * Create widget data table
 */
function ww_widget_data_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_data";
  
  $sql = "CREATE TABLE " . $table . " (
	  post_id mediumint(11) NOT NULL,
	  type varchar(32) NOT NULL,
   data text NOT NULL,
   UNIQUE KEY id (post_id)
  );";
  
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');  
  dbDelta($sql);
}
/*
 * Create widget spaces table
 */
function ww_widget_spaces_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  
  $sql = "CREATE TABLE " . $table . " (
	  id mediumint(11) NOT NULL AUTO_INCREMENT,
	  type varchar(32) NOT NULL,
   data text NOT NULL,
   widgets text NOT NULL,
   UNIQUE KEY id (id)
  );";
  
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');  
  dbDelta($sql);
}
/*
 * Create widget spaces table
 */
function ww_widget_space_term_relationships_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_space_term_relationships";  
  
  $sql = "CREATE TABLE " . $table . " (
	  space_id mediumint(11) NOT NULL,
	  term_id mediumint(11) NOT NULL
  );";
  
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');  
  dbDelta($sql);
}


