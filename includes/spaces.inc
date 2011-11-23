<?php
/*
 * Get all Widget Spaces
 */
function ww_get_all_spaces(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_spaces";
  
  $sql = "SELECT id,type,data,widgets FROM ".$table." ORDER BY id ASC";
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
    'term',
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
  if($space_type == 'term' && is_array($_POST['data']['categories']))
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
    case 'term':
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