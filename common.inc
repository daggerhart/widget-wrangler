<?php
// http://codex.wordpress.org/Conditional_Tags
/*
 * Determine what type of page we're on using WP conditional tags
 *
 * @return string Based on conditional tags
 */
function ww_get_page_op()
{
  $op = '';
  if (is_category()){
    $op = 'category';
  }
  else if (is_tag()){
    $op = 'tag';
  }
  else if (is_home()){
    $op = 'home';
  }
  else if (is_single()){
    // single is not pages
    $op = 'single';
  }
  else if (is_page()){
    $op = 'page';
  }
  else if (is_author()){
    $op = 'author';
  }
  else if (is_search()){
    $op = 'search';
  }
  else if (is_404()){
    $op = '404';
  }
  else if (is_paged()){
    $op = 'paged';
  }
  else {
    $op = 'default';
  }
  return $op;
}
/*
 * Serialize widgets from a saved form
 *
 * @param array $posted $_POST array for a saved widget
 * @return string serialized array of the widgets within the $_POST array
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
    $corral = $posted['ww-widgets'][$widget->post_name]['corral'];

    // if something was submitted without a weight, make it neutral
    if ($weight < 1)
    {
      $weight = $i;
    }
    // add widgets to save array
    if (($corral && $name) &&
        ($corral != 'disabled'))
    {
      $active_widgets[$corral][] = array(
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
 *
 * @param int $post_id The WP post ID for the page or post to assign widgets
 * @param string $widgets Serialized array of widgets on this post
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
 *
 * @param int $post_id The WP post ID for the page or post to delete widgets from
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
 *
 * @param int $post_id The WP post ID for the page or post to get
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
 *
 * @param int $post_id The WP post ID for the WW widget
 */
function ww_get_widget_data($post_id){
  global $wpdb;
  $sql = "SELECT type,data FROM ".$wpdb->prefix."ww_widget_data WHERE post_id = ".$post_id;
  return $wpdb->get_row($sql);
}
/*
 * Widget types
 *
 * @return array All widget types
 */
function ww_all_widget_types(){
  return array(
    'standard' => 'Standard',
    'clone' => 'Clone',
  );
}
/*
 * Returns all published widgets
 *
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
  $results = $wpdb->get_results($query);

	$widgets = array();
	foreach ($results as $i => $widget)
  {
		$widgets[$widget->ID] = $widget;
    $widgets[$widget->ID]->adv_enabled = get_post_meta($widget->ID,'ww-adv-enabled',TRUE);
    $widgets[$widget->ID]->parse       = get_post_meta($widget->ID,'ww-parse', TRUE);
    $widgets[$widget->ID]->wpautop     = get_post_meta($widget->ID,'ww-wpautop', TRUE);
  }
	unset($results);
  return $widgets;
}
/*
 * Retrieve and return a single widget by its ID
 *
 * @param int $post_id The WP post ID for the WW widget
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
 * Retrieve list of corrals
 *
 * @return array of corrals
 */
function ww_get_all_corrals()
{
  if ($corrals_string = get_option('ww_corrals')){
    $corrals_array = unserialize($corrals_string);
  }
  else{
    $corrals_array = array('No Corrals Defined');
  }
  return $corrals_array;
}

/* ==================================== HELPER FUNCTIONS ===== */
/*
 * Helper function for making corral slugs
 */
function ww_make_slug($string){
  $search = array("!","@","#","$","%","^","&","*","(",")","-","+","=","{","}","[","]","\\","|",":",";","'","<",",",">",".","?","/","~","`");
  return str_replace(" ", "_", strtolower(str_replace($search, "", strip_tags($string))));
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
