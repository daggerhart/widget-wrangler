<?php

/*
 * Serialize widgets from a saved form
 */
function ww_serialize_widgets($posted)
{
  $active_widgets = array();
  $all_widgets = ww_get_all_widgets();
  
  // loop through post data and build widgets array for saving
  $i = 1;
  foreach($all_widgets as $key => $widget)
  {
    $name = $posted['ww-widgets'][$widget->post_name]['name'];
    $weight = $posted['ww-widgets'][$widget->post_name]['weight'];
    $sidebar = $posted['ww-widgets'][$widget->post_name]['sidebar'];
    
    // if something was submitted without a weight, make it neutral
    if ($weight < 1)
    {
      $weight = $i;
    }
    // add widgets to save array
    if ($sidebar && $name)
    {
      $active_widgets[$sidebar][] = array(
            'id' => $widget->ID,
            'name' => $widget->post_title,
            'weight' => $weight,
            );
    }
    $i++;
  }
  // serialize the results
  return serialize($active_widgets);
}
/*
 * Save a post's widgets
 */
function ww_update_post_widgets($post_id, $post_widgets = ''){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";
  $set = array(
    'widgets' => $post_widgets,
  );
  $where = array(
    'post_id' => $post_id,
  );
  
  // If can't update, insert
  // $wpdb->update responds false on errors
  if(!$wpdb->update($table, $set, $where))
  {
    // send along the post_id
    $set['post_id'] = $post_id;
    
    $wpdb->insert($table, $set);
  }
}
/*
 * Delete a post's widgets
 */
function ww_delete_post_widgets($post_id){
  if(is_numeric($post_id))
  {
    global $wpdb;
    $table = $wpdb->prefix."ww_post_widgets";
    
    $sql = "DELETE FROM ".$table." WHERE post_id = ".$post_id;
    $wpdb->query($sql);
  }
}
/*
 * Get a post's widgets
 */
function ww_get_post_widgets($post_id){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";
  
  $sql = "SELECT widgets FROM ".$table." WHERE post_id = ".$post_id;
  if($results = $wpdb->get_row($sql)){
    //print_r($results);
    return $results->widgets;
  } else {
    return false;
  }
}
/*
 * Get data for a single widget
 */
function ww_get_widget_data($post_id){
  global $wpdb;
  $sql = "SELECT type,data FROM ".$wpdb->prefix."ww_widget_data WHERE post_id = ".$post_id;
  return $wpdb->get_row($sql);
}
/*
 * Widget types
 */
function ww_all_widget_types(){
  return array(
    'standard' => 'Standard',
    'clone' => 'Clone',
    'query' => 'Query',
  );
}
/*
 * Returns all published widgets
 * @return array of all widget objects
 */
function ww_get_all_widgets()
{
  global $wpdb;
  $query = "SELECT
              ID,post_name,post_title,post_content
            FROM
              ".$wpdb->prefix."posts
            WHERE
              post_type = 'widget' AND
              post_status = 'publish'";
  $widgets = $wpdb->get_results($query);
  
  $i=0;
  $total = count($widgets);
  while($i < $total)
  {
    $widgets[$i]->adv_enabled = get_post_meta($widgets[$i]->ID,'ww-adv-enabled',TRUE);
    $widgets[$i]->parse       = get_post_meta($widgets[$i]->ID,'ww-parse', TRUE);
    $widgets[$i]->wpautop     = get_post_meta($widgets[$i]->ID,'ww-wpautop', TRUE);
    $i++;
  }
  return $widgets;
}
/*
 * Retrieve and return a single widget by its ID
 * 
 * @return widget object
 */
function ww_get_single_widget($post_id){
  global $wpdb;
  $query = "SELECT
              posts.ID, posts.post_name, posts.post_title, posts.post_content,
              data.type as widget_type, data.data as widget_data
            FROM
              ".$wpdb->prefix."posts as posts
              LEFT JOIN ".$wpdb->prefix."ww_widget_data as data on data.post_id = posts.ID
            WHERE
              posts.post_type = 'widget' AND
              posts.post_status = 'publish' AND
              posts.ID = ".$post_id;
  $widget = $wpdb->get_row($query);
  
  // unserialize widget data and clone instance
  $widget->widget_data = unserialize($widget->widget_data);
  if($widget->widget_type == 'clone'){
    $widget->widget_data['clone-instance'] = unserialize($widget->widget_data['clone-instance']);
  }
  
  return $widget;
}

/*
 * Apply templating and parsing to a single widget
 * 
 * @return themed widget for output or templating
 */
function ww_theme_single_widget($widget)
{
  // widget type determines templating
  switch($widget->widget_type)
  { 
    case 'clone':
      $widget->widget_data['clone-instance']['ID'] = $widget->ID;
      $widget->widget_data['clone-instance']['post_name'] = $widget->post_name;
      $widget->widget_data['clone-instance']['post_title'] = $widget->post_title;

      $themed = ww_the_widget($widget->widget_data);
      break;
    
    case 'standard':
    default:
      // maybe they don't want auto p ?
      if ($widget->widget_data['wpautop'] == "on"){
        $widget->post_content = wpautop($widget->post_content);
      }
      
      // apply shortcode
      $widget->post_content = do_shortcode($widget->post_content);  
      
      // see if this should use advanced parsing
      if($widget->widget_data['rewrite-output']){
        $themed = ww_rewrite_output_widget($widget);
      }
      else{
        $themed = ww_template_widget($widget);
      }
      break;
  }
  return $themed;
}
/*
 * Look for possible custom templates, then default to widget-template.php
 * 
 * @param object $widget Widget as object from db
 * @return templated widget
 */ 
function ww_template_widget($widget)
{
  ob_start();
  
  // look for template in theme folder w/ widget ID first
  if (file_exists(TEMPLATEPATH . "/widget-".$widget->ID.".php")){
    include TEMPLATEPATH . "/widget-".$widget->ID.".php";
  }
  // fallback to standard widget template in theme
  else if (file_exists(TEMPLATEPATH . "/widget.php")){
    include TEMPLATEPATH . "/widget.php";
  }
  // fallback on default template
  else{
    include WW_PLUGIN_DIR. '/templates/widget.php';
  }
  $templated = ob_get_clean();
  
  return $templated;
}
/*
 * Handle the advanced parsing for a widget
 * 
 * @return advanced parsed widget
 */
function ww_rewrite_output_widget($widget)
{
  // make $post and $page available
  global $post;
  $page = $post;
  
  // handle advanced templating
  if($widget->widget_data['adv-template'])
  {  
    $returned_array = eval('?>'.$widget->widget_data['custom-output']);
    if (is_array($returned_array)){
      $widget->post_title = $returned_array['title'];
      $widget->post_content = $returned_array['content'];
      $output = ww_template_widget($widget);
    }
    else {
      $output = "<!-- Error:  This widget did not return an array. -->";
    }
  }
  else
  {
    $pattern = array('/{{title}}/','/{{content}}/');
    $replace = array($widget->post_title, $widget->post_content);
    
    // find and replace title and content tokens
    $parsed = preg_replace($pattern,$replace,$widget->widget_data['custom-output']);
    
    // execute adv parsing area
    ob_start();
      eval('?>'.$parsed);
      $output = ob_get_clean();
      // fix for recent post widget not resetting the query
      $post = $page;
  }
  
  return $output;
}
/*
 * Retrieve list of sidebars
 * 
 * @return array of sidebars
 */
function ww_get_all_sidebars()
{
  if ($sidebars_string = get_option('ww_sidebars')){
    $sidebars_array = unserialize($sidebars_string);
  }
  else{
    $sidebars_array = array('No Sidebars Defined');
  }
  return $sidebars_array;
}

/*
 * Get the Widget Wrangler Settings
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
  
  // update 1.3 & 1.3.1 fix force a post_types setting
  if($settings['post_types'][0] == "" || empty($settings['post_types'])){
    $settings['post_types'] = array('page');
    update_option("ww_settings", serialize($settings));
  }
  
  return $settings;
}
/*
 * Default settings
 */
function ww_settings_set_default()
{
  $settings["capabilities"] = "simple";
  $settings["post_types"][] = "page";
  $settings["post_types"][] = "post";
  update_option("ww_settings", serialize($settings));
}

/* ==================================== HELPER FUNCTIONS ===== */
/*
 * Helper function for making sidebar slugs
 */
function ww_make_slug($string){
  return stripcslashes(preg_replace('/[\s_\'\"]/','_', strtolower(strip_tags($string))));
}
/*
 * usort callback. I likely stole this from somewhere.. like php.net
 */
function ww_cmp($a,$b) {
  if ($a['weight'] == $b['weight']) return 0;
  return ($a['weight'] < $b['weight'])? -1 : 1;
}
// recursive array search
function ww_array_searchRecursive( $needle, $haystack, $strict=false, $path=array() )
{
  if( !is_array($haystack) ) {
    return false;
  }
  foreach( $haystack as $key => $val ) {
    if( is_array($val) && $subPath = ww_array_searchRecursive($needle, $val, $strict, $path) ) {
        $path = array_merge($path, array($key), $subPath);
        return $path;
    } elseif( (!$strict && $val == $needle) || ($strict && $val === $needle) ) {
        $path[] = $key;
        return $path;
    }
  }
  return false;
}
