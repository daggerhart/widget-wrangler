<?php

/*
 * Get which widgets belong on the current page/route loaded
 */
function ww_find_page_widgets() {
  // get the post and corrals
	global $post;
	$widgets_array = array();
  $presets = ww_get_all_presets();
  $preset_types = ww_get_all_preset_types();
	$settings = ww_get_settings();
	$op = ww_get_page_op();
	
	//print $op;
	
	// give single-page widgets a chance first
  switch ($op) {
    case 'single':
    case 'page':
			if (isset($settings['post_types'][$post->post_type])){
				// see if we have widgets for this page
				if ($post_widgets = ww_get_post_widgets($post->ID)){
					// individual widgets or a preset ?
					if (!isset($post_widgets->preset_id) || $post_widgets->preset_id == 0){
						$widgets_array = $post_widgets->widgets;
					}
				}
			}
      break;
  }
	
	// then let presets handle what's left
	foreach ($preset_types as $type => $preset_type){
		if (isset($preset_type['execution_ops']) && in_array($op, $preset_type['execution_ops'])){
			if (isset($preset_type['execution_callback']) && function_exists($preset_type['execution_callback'])){
				$preset_type['execution_callback']($op, $widgets_array);
			}
		}
	}
	
	// return if found results	
	if (count($widgets_array)){
		return $widgets_array;
	}
}

/*
 * Get the Widget Wrangler Settings
 *
 * @return settings array
 */
function ww_get_settings(){
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
 * Default settings
 */
function ww_settings_set_default(){
  $settings["capabilities"] = "simple";
  $settings["post_types"]['page'] = "page";
  $settings["post_types"]['post'] = "post";
	$settings["exclude_from_search"] = 1;
	$settings["disable_autosave"] = 1;
	$settings["enable_shortcode_tinymce"] = 1;
	
  update_option("ww_settings", serialize($settings));
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

  $sql = "SELECT widgets,preset_id FROM ".$table." WHERE post_id = %d";
  if($results = $wpdb->get_row($wpdb->prepare($sql, $post_id))){
    $results->widgets = unserialize($results->widgets);
    return $results;
  } 
  return false;
}


/*
 * Returns all published widgets
 *
 * @return array of all widget objects
 */
function ww_get_all_widgets()
{
  global $wpdb;
	$widget_data_table = $wpdb->prefix."ww_widget_data";
	$posts_table = $wpdb->prefix."posts";
  $query = "SELECT
              posts.ID, posts.post_name, posts.post_title, posts.post_content,
              data.type as widget_type, data.data as widget_data
            FROM
              ".$posts_table." as posts
              LEFT JOIN ".$widget_data_table." as data on data.post_id = posts.ID
            WHERE
              posts.post_type = 'widget' AND
              posts.post_status = 'publish'";
  $results = $wpdb->get_results($query);

	$widgets = array();
	foreach ($results as $i => $widget)
  {
		$widget->widget_data = (isset($widget->widget_data)) ? unserialize($widget->widget_data) : array();
		$widget->widget_type = (isset($widget->widget_type)) ? $widget->widget_type : "standard";
		$widgets[$widget->ID] = $widget;
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
	$widget_data_table = $wpdb->prefix."ww_widget_data";
	$posts_table = $wpdb->prefix."posts";
  $query = "SELECT
              posts.ID, posts.post_name, posts.post_title, posts.post_content,
              data.type as widget_type, data.data as widget_data
            FROM
              ".$posts_table." as posts
              LEFT JOIN ".$widget_data_table." as data on data.post_id = posts.ID
            WHERE
              posts.post_type = 'widget' AND
              posts.post_status = 'publish' AND
              posts.ID = %d
						LIMIT 1";
  $widget = $wpdb->get_row($wpdb->prepare($query, $post_id));

	if ($widget){
		// unserialize widget data and clone instance
		$widget->widget_data = (isset($widget->widget_data)) ? unserialize($widget->widget_data) : array();
		
		if($widget->widget_type == 'clone'){
			$widget->widget_data['clone-instance'] = isset($widget->widget_data['clone-instance']) ? unserialize($widget->widget_data['clone-instance']) : array();
		}
	
		return $widget;
	}
	return false;
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
  
  $sql = "SELECT id,type,data,widgets FROM ".$table." WHERE id = %d LIMIT 1";
  $results = $wpdb->get_row($wpdb->prepare($sql,$preset_id));
  if (isset($results->data) && is_string($results->data)){
    $results->data = unserialize($results->data);
    $results->widgets = unserialize($results->widgets);
		return $results;
  }
	return false;
}
/* ================================= corrals */
/*
 * Retrieve list of corrals
 *
 * @return array of corrals
 */
function ww_get_all_corrals(){
  if ($corrals_string = get_option('ww_corrals')){
    $corrals_array = unserialize($corrals_string);
  }
  else{
    $corrals_array = array();
  }
  return $corrals_array;
}

/*
 * Compat with old sidebars
 */
function ww_get_corral_id_by_slug($slug){
	$all_corrals = ww_get_all_corrals();
	
	// find the corral for this old sidebar slug
	$found_corral = false;
	$found_corral_id = 0;
	foreach ($all_corrals as $id => $corral){
		if ($corral['slug'] == $slug) {
			$found_corral_id = $id;
			$found_corral = true;
			break;
		}
	}
	
	// pass to new function
	if ($found_corral){
		return $found_corral_id;
	}
	return false;
}

/*
 * http://docs.appthemes.com/tutorials/wordpress-check-user-role-function/
 * recommended by codex: http://codex.wordpress.org/Function_Reference/current_user_can
 */
function ww_check_user_role( $role, $user_id = null ) {
	if ( is_numeric( $user_id ) ){
		$user = get_userdata( $user_id );
	}
	else{
		$user = wp_get_current_user();
	}

	if ( empty( $user ) ){
		return false;
	}
  return in_array( $role, (array) $user->roles );
}


/*
 * Shortcode support for all widgets
 *
 * @param array $atts Attributes within the executed shortcode.  'id' => widget->ID
 * @return string HTML for a single themed widget
 */
function ww_single_widget_shortcode($atts) {
  $short_array = shortcode_atts(array('id' => ''), $atts);
  extract($short_array);
  return ww_theme_single_widget(ww_get_single_widget($id));
}
add_shortcode('ww_widget','ww_single_widget_shortcode');

/*
 * Shortcode support for all widgets
 *
 * @param array $atts Attributes within the executed shortcode.  'id' => corral id
 * @return string HTML for a single themed corral
 */
function ww_single_corral_shortcode($atts) {
  $short_array = shortcode_atts(array('id' => ''), $atts);
  extract($short_array);
	ob_start();
		ww_dynamic_corral($id);
  return ob_get_clean();
}
add_shortcode('ww_corral','ww_single_corral_shortcode');


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
	else if (is_front_page()){
		$op = 'front_page';
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
