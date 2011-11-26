<?php
/*
 *
 */
function ww_check_version(){
  $last_version = get_option('ww_version');
  
  // if no last version found
  if(!$last_version)
  {
    //set_option('ww_version', WW_VERSION);
  }
  // if last version is lower than current version
  else if($last_version < WW_VERISON)
  {
    // run version related upgrade
    //$function = 'ww_upgrade_'
  }
}
/*
 * Look for an insert default spaces
 */
function ww_default_spaces(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  $data = array(
    'id' => 0,
    'type' => 'default',
    'data' => '',
    'widgets' => serialize(array()),
  );
  
  // defaults
  $sql_select = "SELECT id FROM ".$table." WHERE id = 1";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 1;
    $data['data'] = serialize(array('name' => 'Defaults'));
    $wpdb->insert($table, $data);
  }
  
  // posts page
  $sql_select = "SELECT id FROM ".$table." WHERE id = 2";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 2;
    $data['data'] = serialize(array('name' => 'Posts Page'));
    $wpdb->insert($table, $data);
  }
  
  // search page
  $sql_select = "SELECT id FROM ".$table." WHERE id = 3";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 3;
    $data['data'] = serialize(array('name' => 'Search Page'));
    $wpdb->insert($table, $data);
  }

  // 404 page
  $sql_select = "SELECT id FROM ".$table." WHERE id = 4";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 4;
    $data['data'] = serialize(array('name' => '404 Page'));
    $wpdb->insert($table, $data);
  }
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


