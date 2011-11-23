<?php

/*
 * Output a sidebar
 */
function ww_dynamic_sidebar($sidebar_slug = 'default')
{
  include_once WW_PLUGIN_DIR.'/includes/spaces.inc';
  
  // get the post and sidebars
  global $post;
  $sidebars = ww_get_all_sidebars();
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
      
      // get the space
      $spaces_table = $wpdb->prefix.'ww_widget_spaces';
      $relation_table = $wpdb->prefix.'ww_space_term_relationships';
      
      // look for spaces for this category
      $sql = "SELECT r.space_id, s.widgets
              FROM ".$relation_table." as r
              LEFT JOIN ".$spaces_table." as s ON r.space_id = s.id
              WHERE r.term_id = ".$category->term_id."
              LIMIT 1";
      $row = $wpdb->get_row($sql);
      
      // make the array
      if($row->space_id){
        $widgets_array = unserialize($row->widgets);
      }
      break;
    
    case 'single':
    case 'page':
      $widgets = ww_get_post_widgets($post->ID);
      $widgets_array = unserialize($widgets);
      break;
    
    /*
     * Default widget spaces
     */
    case 'home':
      // see if this is the Posts (blog) page
      if(get_option('show_on_front') == 'posts'){
        // posts page is space 2
        $space = ww_get_space(2);
        $widgets_array = unserialize($space->widgets);
      }
      break;

    case 'search':
      // search page is default space 3
      $space = ww_get_space(3);
      $widgets_array = unserialize($space->widgets);
      break;
    
    case '404':
      // 404 page is default space 4
      $space = ww_get_space(4);
      $widgets_array = unserialize($space->widgets);
      break;
  }
  
  // if still no widgets, fall back to defaults
  if (!is_array($widgets_array))
  {
    // space 1 is the default widgets
    if($space = ww_get_space(1)){
      $widgets_array = unserialize($space->widgets);      
    }
    // no widgets in post and no defaults
    else {
      return;
    }
  }
  
  // handle widgets
  if (is_array($widgets_array[$sidebar_slug]))
  {
    $i = 0;
    $total = count($widgets_array[$sidebar_slug]);
    
    // custom sorting with callback
    usort($widgets_array[$sidebar_slug],'ww_cmp');
    $sorted_widgets = array_reverse($widgets_array[$sidebar_slug]);
    
    while($i < $total)
    {
      if($widget = ww_get_single_widget($widgets_array[$sidebar_slug][$i]['id'])){
        $output.= ww_theme_single_widget($widget);
      }
      $i++;
    }
    // output the widgets  
    print $output;
  }
}

/**
 * Taken from wp-includes/widgets.php, adjusted for my needs
 * 
 * @param array $widget data including the widget's PHP class name (see default-widgets.php)
 * 
 * @return void
 **/
function ww_the_widget($widget_data, $echo = false)
{
  $instance = $widget->widget_data['clone-instance'];
  // load widget from widget factory ?
  global $wp_widget_factory;
  $widget_obj = $wp_widget_factory->widgets[$widget_data['clone-class']];
 
  if ( !is_a($widget_obj, 'WP_Widget') )
   return;
 
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
  if($widget_data['hide_title']){
    unset($obj->post_title);
  }
  
  // template with WW template
  if ($echo){
    print ww_template_widget($obj);
  }
  else {
    return ww_template_widget($obj);
  }
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