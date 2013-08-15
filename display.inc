<?php
/*
 * Output a corral
 *
 * @param string $corral_slug The slug for the corral to be displayed
 */
function ww_dynamic_corral($corral_slug = 'default')
{
  //include_once WW_PLUGIN_DIR.'/admin/corral.inc';
  
  // get the post and corrals
  global $post;
  $corrals = ww_get_all_corrals();
  $output = '';
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
      
      // get the corral
      $corrals_table = $wpdb->prefix.'ww_widget_corrals';
      $relation_table = $wpdb->prefix.'ww_corral_term_relationships';
      
      // look for corrals for this category
      $sql = "SELECT r.corral_id, s.widgets
              FROM ".$relation_table." as r
              LEFT JOIN ".$corrals_table." as s ON r.corral_id = s.id
              WHERE r.term_id = ".$category->term_id."
              LIMIT 1";
      $row = $wpdb->get_row($sql);
      
      // make the array
      if($row->corral_id){
        $widgets_array = unserialize($row->widgets);
      }
      break;
    
    case 'single':
    case 'page':
      $widgets = ww_get_post_widgets($post->ID);
      $widgets_array = unserialize($widgets);
      break;
    
    /*
     * Default widget corrals
     */
    case 'home':
      // see if this is the Posts (blog) page
      if(get_option('show_on_front') == 'posts'){
        // posts page is corral 2
        $corral = ww_get_corral(2);
        $widgets_array = unserialize($corral->widgets);
      }
      break;

    case 'search':
      // search page is default corral 3
      $corral = ww_get_corral(3);
      $widgets_array = unserialize($corral->widgets);
      break;
    
    case '404':
      // 404 page is default corral 4
      $corral = ww_get_corral(4);
      $widgets_array = unserialize($corral->widgets);
      break;
  }
  
  // if still no widgets, fall back to defaults
  if (!is_array($widgets_array))
  {
    // corral 1 is the default widgets
    if($corral = ww_get_corral(1)){
      $widgets_array = unserialize($corral->widgets);      
    }
    // no widgets in post and no defaults
    else {
      return;
    }
  }
  
  // handle widgets
  if (is_array($widgets_array[$corral_slug]))
  {
    // custom sorting with callback
    usort($widgets_array[$corral_slug],'ww_cmp');
    $sorted_widgets = array_reverse($widgets_array[$corral_slug]);
    
    $i = 0;
    $total = count($widgets_array[$corral_slug]);
    
    while($i < $total)
    {
      // make sure we have a widget
      if($widget = ww_get_single_widget($widgets_array[$corral_slug][$i]['id']))
      {
        // check the widget's display logic
        if(ww_widget_display_logic($widget))
        {
          // output the widget
          $output.= ww_theme_single_widget($widget);  
        }
      }
      $i++;
    }
    // output the widgets  
    print $output;
  }
}
/*
 * Complex boolean function to determine if the widget should be displayed
 */
function ww_widget_display_logic($widget)
{
  // default to false
  $final_show = false;
  
  //print '<pre>'.print_r($logic,1).'</pre>';
  
  // see if any display logic is set
  if(isset($widget->widget_data['logic']))
  {
		// logic array
		$logic = $widget->widget_data['logic'];
    
		// check conditionals
    /* feature on hold for reconsideration
    if(is_array($logic['conditionals']))
    {
      $conditional_show = false;

      foreach($logic['conditionals'] as $conditional){
        $function = 'is_'.$conditional;
        // check WP conditional tag
        if($function()){
          $conditional_show = true;
        }
      }
    }
    // */
    
    // check post_types
    if(is_array($logic['post_types']))
    {
      global $post;
      $post_type_show = false;

      // check current post_type
      if(in_array($post->post_type, $logic['post_types'])){
        $post_type_show = true;
      }
    }
    
    // check php
    if(isset($logic['php']))
    {      
      // execute the php and get returned value
      $returned_value = eval($logic['php']);
      if(is_bool($returned_value)){
        $php_show = $returned_value;
      }
    }
    
    // if any are true, show the widget
    //if($conditional_show || $post_type_show || $php_show)
    if($post_type_show || $php_show)
    {
      $final_show = true;
    }

    // if all are empty, show the widget
    //if(!is_array($logic['conditionals']) && !is_array($logic['post_types']) && !isset($logic['php']))
    if(!is_array($logic['post_types']) && !isset($logic['php']))
    {
      $final_show = true;
    }
    
  }
  // no logic? default to showing the widget
  else
  {
    $final_show = true;    
  }
  return $final_show;
}
/*
 * Apply templating and parsing to a single widget
 *
 * @param object $widget The widget to be themed
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
      if (isset($widget->widget_data['filter-content']) && $widget->widget_data['filter-content'] == "on"){
        $widget->post_content = apply_filters('the_content', $widget->post_content);
      }
      
      // apply shortcode
      // $widget->post_content = do_shortcode($widget->post_content);  
      
      // see if this should use advanced parsing
      if(isset($widget->widget_data['rewrite-output'])){
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
 * @param object $widget The widget to be rewritten
 * @return advanced parsed widget
 */
function ww_rewrite_output_widget($widget)
{
  // make $post and $page available
  global $post;
  $page = $post;

  // find and replace title and content tokens
  $pattern = array('/{{title}}/','/{{content}}/');
  $replace = array($widget->post_title, $widget->post_content);  
  $widget->widget_data['custom-output'] = preg_replace($pattern,$replace,$widget->widget_data['custom-output']);
   
  // handle advanced templating
  if(isset($widget->widget_data['adv-template']))
  {  
    $returned_array = eval('?>'.$widget->widget_data['custom-output']);
    if (is_array($returned_array)){
      $widget->post_title = $returned_array['title'];
      $widget->post_content = $returned_array['content'];
      $output = ww_template_widget($widget);
    }
    else {
      $output = "<!-- Error:  This widget's rewrite-output did not return an array. -->";
    }
  }
  // execute adv parsing area
  else
  {  
    ob_start();
      eval('?>'.$widget->widget_data['custom-output']);
      $output = ob_get_clean();
      // fix for recent post widget not resetting the query
      $post = $page;
  }
  
  return $output;
}
/**
 * Taken from wp-includes/widgets.php, adjusted for my needs
 * 
 * @param array $widget data including the widget's PHP class name (see default-widgets.php)
 * @return void
 **/
function ww_the_widget($widget_data)
{
  // defined instance in widget
  $instance = $widget_data['clone-instance'];
  
  // load widget from widget factory 
  global $wp_widget_factory;
  $widget_obj = $wp_widget_factory->widgets[$widget_data['clone-class']];
 
  if ( !is_a($widget_obj, 'WP_Widget') ){
   return;
  }
 
  // args for spliting title from content
  $args = array('before_widget'=>'','after_widget'=>'','before_title'=>'','after_title'=>'[explode]');
 
  // output to variable for replacements
  ob_start();
     $widget_obj->widget($args, $instance);
  $temp = ob_get_clean();
  
  // get title and content separate
  $array = explode("[explode]", $temp);
  
  // prep object for template
  $obj                = new stdClass();
  $obj->ID            = $instance['ID'];
  $obj->post_name     = $instance['post_name'];
  $obj->post_title    = ($array[0]) ? $array[0]: $instance['title'];
  $obj->post_content  = $array[1];
  
  // hide title if desired
  if(isset($widget_data['hide_title'])){
    unset($obj->post_title);
  }
  
  // template with WW template
  return ww_template_widget($obj);  
}