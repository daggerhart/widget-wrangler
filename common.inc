<?php

// gonna make widgets global
function ww_set_page_widgets() {
	global $ww_page_widgets;
	$ww_page_widgets = ww_find_page_widgets();
}
add_action( 'wp', 'ww_set_page_widgets');

/*
 *
 */
function ww_find_page_widgets() {
  // get the post and corrals
	global $post;
  $presets = ww_get_all_presets();
	$settings = ww_get_settings();
  $op = ww_get_page_op();
	
  switch ($op)
  {
    // tags and categories are controlled the same way
    case 'tag':
    case 'category':
      global $wpdb;
      // get first category
      if($op == 'category') {
        $category = array_pop(get_the_category());  
      }
      else if ($op == 'tag'){
        $category = array_pop(get_the_tags());
      }
      
      // get the preset
      $presets_table = $wpdb->prefix.'ww_presets';
      $relation_table = $wpdb->prefix.'ww_preset_term_relationships';
      
      // look for presets for this category
      $sql = "SELECT r.preset_id, s.widgets
              FROM ".$relation_table." as r
              LEFT JOIN ".$presets_table." as s ON r.preset_id = s.id
              WHERE r.term_id = ".$category->term_id."
              LIMIT 1";
      $row = $wpdb->get_row($sql);
      
      // make the array
      if(isset($row->preset_id)){
        $widgets_array = $row->widgets;
      }
      break;
    
    case 'single':
    case 'page':
			if (isset($settings['post_types'][$post->post_type])){
				// see if we have widgets for this page
				if ($post_widgets = ww_get_post_widgets($post->ID)){
					// individual widgets or a preset ?
					if (!isset($post_widgets->preset_id) || $post_widgets->preset_id == 0){
						$widgets_array = $post_widgets->widgets;
					}
					else {
						// preset
						$preset = ww_get_preset($post_widgets->preset_id);
						$widgets_array = $preset->widgets;
					}
				}
				else {
					// widget wrnagler enabled for type, server default widgets
					$preset = ww_get_preset(1);
					$widgets_array = $preset->widgets;
				}
			}
      break;
    
    case 'home':
      // see if this is the Posts (blog) page
      if(get_option('show_on_front') == 'posts'){
        // posts page is preset 2
        $preset = ww_get_preset(2);
        $widgets_array = $preset->widgets;
      }
      break;

    case 'search':
      // search page is default preset 3
      $preset = ww_get_preset(3);
      $widgets_array = $preset->widgets;
      break;
    
    case '404':
      // 404 page is default preset 4
      $preset = ww_get_preset(4);
      $widgets_array = $preset->widgets;
      break;
  }
	
	if (isset($widgets_array)){
		return $widgets_array;
	}
}

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
/* ================================= widgets */
/*
 * Get a post's widgets
 *
 * @param int $post_id The WP post ID for the page or post to get
 */
function ww_get_post_widgets($post_id){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";

  $sql = "SELECT widgets,preset_id FROM ".$table." WHERE post_id = ".$post_id;
  if($results = $wpdb->get_row($sql)){
    $results->widgets = unserialize($results->widgets);
    return $results;
  } 
  return false;
}

/*
 * Get data for a single widget
 *
 * @param int $post_id The WP post ID for the WW widget
 */
function ww_get_widget_data($post_id){
  global $wpdb;
  $sql = "SELECT type,data FROM ".$wpdb->prefix."ww_widget_data WHERE post_id = ".$post_id;
  $widget = $wpdb->get_row($sql);
	$widget->data = unserialize($widget->data);
	d($widget);d(debug_backtrace());
	return $widget;
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

/* ================================= presets */
/*
 * Get all Corral Presets
 *
 * @return array All widget presets found in the DB
 */
function ww_get_all_presets(){
  global $wpdb;
  $table = $wpdb->prefix."ww_presets";
  
  $sql = "SELECT id,type,data,widgets FROM ".$table." ORDER BY id ASC";
  $results = $wpdb->get_results($sql);
  
  $presets = array();
	foreach ($results as $i => $preset){
		$preset->data = unserialize($preset->data);
		$preset->widgets = unserialize($preset->widgets);
		$presets[$preset->id] = $preset;
	}
  unset($results);
	
  return $presets;
}
/*
 * Get a single Corral Preset
 *
 * @param int $preset_id The id for the Corral Preset
 * @return array The Corral Preset details
 */
function ww_get_preset($preset_id){
  global $wpdb;
  $table = $wpdb->prefix."ww_presets";
  
  $sql = "SELECT id,type,data,widgets FROM ".$table." WHERE id = ".$preset_id." LIMIT 1";
  $results = $wpdb->get_row($sql);
  if (isset($results->data) && is_string($results->data)){
    $results->data = unserialize($results->data);
    $results->widgets = unserialize($results->widgets);
  }
  return $results;
}
/* ================================= corrals */
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