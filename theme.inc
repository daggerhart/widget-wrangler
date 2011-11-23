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
  
  // See if this is a category page
  if(is_category())
  {
    global $wpdb;
    // get first category
    $category = array_pop(get_the_category());  
    
    $spaces_table = $wpdb->prefix.'ww_widget_spaces';
    $relation_table = $wpdb->prefix.'ww_space_term_relationships';
    
    // look for spaces for this category
    $sql = "SELECT r.space_id, s.widgets
            FROM ".$relation_table." as r
            LEFT JOIN ".$spaces_table." as s ON r.space_id = s.id
            WHERE r.term_id = ".$category->term_id."
            LIMIT 1";
    $row = $wpdb->get_row($sql);
    if($row->space_id){
      $widgets_array = unserialize($row->widgets);
    }
  }
  
  // see if this is the Posts (blog) page
  else if(is_home() && (get_option('show_on_front') == 'posts'))
  {
    // potss page is space 2
    $space = ww_get_space(2);
    $widgets_array = unserialize($space->widgets);
  }
  // look for post meta data
  else if ($widgets_string = ww_get_post_widgets($post->ID))
  {
    $widgets_array = unserialize($widgets_string);
    //print_r($widgets_array);
  }
  // try defaults instead
  else if ($space = ww_get_space(1))
  {
    // space 1 is the default widgets
    $widgets_array = unserialize($space->widgets);
  }
  // no widgets in post and no defaults
  else{
    return;
  }
  // handle widgets
  if (is_array($widgets_array[$sidebar_slug]))
  {
    $i = 0;
    $total = count($widgets_array[$sidebar_slug]);
    
    // custom sorting with callback
    usort($widgets_array[$sidebar_slug],'ww_cmp');
    $sorted_widgets = array_reverse($widgets_array[$sidebar_slug]);
    //print_r($sorted_widgets);
    while($i < $total)
    {
      if($widget = ww_get_single_widget($widgets_array[$sidebar_slug][$i]['id'])){
        $output.= ww_theme_single_widget($widget);
      }
      $i++;
    }
  }
  
  print $output;
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
 * Put all widgets into a list for output
 */
function ww_create_sortable_widgets($widgets, $ref_array, $sidebars)
{
  $i = 0;
  foreach($widgets as $widget)
  {
    $temp = array();
    $keys = ww_array_searchRecursive($widget->ID, $ref_array);
    // fix widgets with no title
    if ($widget->post_title == ""){
      $widget->post_title = "(no title) - Widget ID: ".$widget->ID;
    }
    
    // look for appropriate sidebar, default to disabled
    if ($keys[0] == '' || (!array_key_exists($keys[0], $sidebars))){
      $keys[0] = "disabled";
    }
    
    // setup initial info
    $sidebar_slug = $keys[0];
   
    // get weight
    $weight = $ref_array[$sidebar_slug][$keys[1]]['weight'];
    
    // build select box
    $sidebars_options = "<option value='disabled'>Disabled</option>";
    foreach($sidebars as $slug => $sidebar){
      ($slug == $sidebar_slug) ? $selected = "selected='selected'" : $selected = '';
      $sidebars_options.= "<option name='".$slug."' value='".$slug."' ".$selected.">".$sidebar."</option>";   
    }
    
    // add item to our temp array
    $temp[$weight] = "<li class='ww-item ".$sidebar_slug." nojs' width='100%'>
                        <input class='ww-widget-weight' name='ww-widgets[".$widget->post_name."][weight] type='text' size='2' value='$weight' />
                        <select name='ww-widgets[".$widget->post_name."][sidebar]'>
                        ".$sidebars_options."
                        </select>
                        <input class='ww-widget-name' name='ww-widgets[".$widget->post_name."][name]' type='hidden' value='".$widget->post_name."' />
                        <input class='ww-widget-id' name='ww-widgets[".$widget->post_name."][id]' type='hidden' value='".$widget->ID."' />
                        ".$widget->post_title."
                      </li>";
                      
    // place into output array
    if ($sidebar_slug == 'disabled'){
      $output['disabled'][] = $temp[$weight];
    }
    else{
      $output['active'][$sidebar_slug][$weight] = $temp[$weight];
    }
    
    $i++;
  }
  return $output;
}
/*
 * Theme the output for editing widgets on a page
 */
function ww_theme_sortable_widgets($panel_array)
{
  $sidebars = ww_get_all_sidebars();
  $output = $panel_array['open'];
  
  // loop through sidebars and add active widgets to list
  if (is_array($sidebars))
  {
    foreach($sidebars as $slug => $sidebar)
    {
      // open the list
      $output.= "<h4>".$sidebar."</h4>";
      $output.= "<ul name='".$slug."' id='ww-sidebar-".$slug."-items' class='inner ww-sortable' width='100%'>";
      
      if (is_array($panel_array['active'][$slug])) {
        // loop through sidebar array and add items to list
        foreach($panel_array['active'][$slug] as $item){
          $output.= $item;
        }
        $style = "style='display: none;'";
      }
      else {
        $style = '';
      }
      // close the list
      $output.= "<li class='ww-no-widgets' ".$style.">No Widgets in this sidebar.</li>";
      $output.= "</ul>";
    }
  }
  
  // disabled list
  $output.= "<h4>Disabled</h4><ul name='disabled' id='ww-disabled-items' class='inner ww-sortable' width='100%'>";
  
  // loop through and add disabled widgets to list
  if (is_array($panel_array['disabled'])){
    foreach ($panel_array['disabled'] as $disabled){
      $output.= $disabled;
    }
    $style = "style='display: none;'";
  }
  else{
    $style = '';
  }
  // close disabled list
  $output.= "<li class='ww-no-widgets' ".$style.">No disabled Widgets</li>";
  $output.= "</ul>";
  
  $output.= $panel_array['close'];
  
  print $output;
}