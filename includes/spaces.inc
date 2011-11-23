<?php
/*
 * Get all Widget Spaces
 */
function ww_get_all_spaces(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  
  $sql = "SELECT id,type,data,widgets FROM ".$table;
  $results = $wpdb->get_results($sql);
  
  $spaces = array();
  
  return $results;
}
/*
 * Get a single Widget Space
 */
function ww_get_space($space_id){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  
  $sql = "SELECT type,data,widgets FROM ".$table." WHERE id = ".$space_id." LIMIT 1";
  $results = $wpdb->get_row($sql);
  return $results;
}
/*
 * All Widget Space types
 */
function ww_space_types(){
  return array(
    'category',
  );
}
/*
 * Delete a Widget Space
 */
function ww_delete_space(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  
  $space_id = $_POST['space-id'];
  $space_type = $_POST['space-type'];
  
  $sql = "DELETE FROM ".$table." WHERE id = ".$space_id." AND type = '".$space_type."' LIMIT 1";
  $wpdb->query($sql);
}
/*
 * Update a Widget Space
 */
function ww_update_space(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  
  $space_id = $_POST['space-id'];
  $space_type = $_POST['space-type'];
  $space_data = serialize($_POST['data']);
  $space_widgets = ww_serialize_widgets($_POST);
  
  // update the ww_widget_spaces db
  $data = array(
    'data' => $space_data,
    'widgets' => $space_widgets,
  );
  $where = array(
    'id' => $space_id,
    'type' => $space_type,
  );
  
  $wpdb->update($table, $data, $where);
  
  // update the category reference table
  if($space_type == 'category' && is_array($_POST['data']['categories']))
  {
    // new table
    $table = $wpdb->prefix."ww_space_term_relationships";
    
    // get category info
    $space_categories = array_keys($_POST['data']['categories']);
    
    // delete all existing relationships
    $sql = "DELETE FROM ".$table." WHERE space_id = ".$space_id;
    $wpdb->query($sql);
          
    // loop through and insert all relationships
    foreach($_POST['data']['categories'] as $term_id => $term_name)
    {
      // insert all relationships
      $data = array(
        'space_id' => $space_id,
        'term_id' => $term_id,
      );
      $wpdb->insert($table, $data);
    }
  }
  
  return $space_id;
}
/*
 * Create a new Widget Space
 */
function ww_create_space()
{
  //print_r($_POST);exit();
  
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
      
  switch($_POST['type'])
  {
    case 'category':
      $data = array(
        'type' => $_POST['type'],
        'data' => serialize(array('name' => ucfirst($_POST['type']))),
        'widgets' => serialize(array()),
      );
      
      $wpdb->insert($table, $data);
      
      return $wpdb->insert_id;
      break;
  }  
}
/*
 * Show edit page for a space
 */
function ww_spaces_edit_page()
{
  // prepare space data  
  if(!$_GET['space_id'] || !is_numeric($_GET['space_id'])){
    $space_id = 1;
  } else {
    $space_id = $_GET['space_id'];
  }
  $this_space          = ww_get_space($space_id);
  $space_type     = $this_space->type;
  $space_data     = unserialize($this_space->data);
  $space_widgets  = unserialize($this_space->widgets);
  $all_spaces     = ww_get_all_spaces();
  
  $sortable_widgets = array();
  $all_widgets = ww_get_all_widgets();
  $all_sidebars = ww_get_all_sidebars();  

  // add additional sidebars to output
  if (is_array($all_widgets) && count($all_sidebars) > 0){
    $sortable_widgets = ww_create_sortable_widgets($all_widgets, $space_widgets, $all_sidebars);
  }
  
  // sort each sidebar
  if(is_array($sortable_widgets['active'])){
    foreach($sortable_widgets['active'] as $sidebar => $unsorted_widgets){
      ksort($sortable_widgets['active'][$sidebar]);  
    }
  }
  
  include WW_PLUGIN_DIR.'/forms/spaces.inc';
}