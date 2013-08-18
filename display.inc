<?php


// example template hook
function ww_display_templates($templates)
{
  // template applied by files
  $templates['ww_widget'] = array(
    // string of single template suggestion
    // or array of multiple template suggestions
    'files' => array(
        // use argument keys as replacable patterns with the argument's value
        // order is important as the order here is the order inwhich templates are searched for
        'widget-[widget_type]-[widget_id].php',
        'widget-[widget_id].php',
        'widget-[widget_type].php',
        // a default fall back template name
        'widget.php'
    ),

    // location of the default template if nothing is found
    'default_path' => dirname(__FILE__).'/templates',

    // optional arguments to be passed to the themeing function
    'arguments' => array(
        // must be key => value pairs
				'widget' => NULL,
        'widget_id' => 0,
        'widget_type' => 'standard',
    ),
  );

  return $templates;
}
// hook your function into the wordpress filter
add_filter('tw_templates', 'ww_display_templates');

/*
 * Output a corral
 *
 * @param string $corral_slug The slug for the corral to be displayed
 */
function ww_dynamic_corral($corral_slug = 'default')
{
	global $ww_page_widgets;
  
  // if still no widgets, fall back to defaults
  if (!is_array($ww_page_widgets))
  {
    // preset 1 is the default widgets
    if($preset = ww_get_preset(1)){
      $ww_page_widgets = $preset->widgets;
    }
    // no widgets in post and no defaults
    else {
      return;
    }
  }
  
  // handle widgets
  if (isset($ww_page_widgets[$corral_slug]) && is_array($ww_page_widgets[$corral_slug]))
  {
    // custom sorting with callback
    usort($ww_page_widgets[$corral_slug],'ww_cmp');
    $sorted_widgets = array_reverse($ww_page_widgets[$corral_slug]);
    
    $i = 0;
    $total = count($ww_page_widgets[$corral_slug]);
    $output = array();
    while($i < $total)
    {
      // make sure we have a widget
      if($widget = ww_get_single_widget($ww_page_widgets[$corral_slug][$i]['id']))
      {
        // check the widget's display logic
        if(ww_widget_display_logic($widget))
        {
          // output the widget
          $output[] = ww_theme_single_widget($widget);  
        }
      }
      $i++;
    }
    // output the widgets if not empty
		if (count($output)){
			print implode("", $output);
		}
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
		$post_type_show = false;
    if(isset($logic['post_types']) && is_array($logic['post_types']))
    {
      global $post;
      // check current post_type
      if(in_array($post->post_type, $logic['post_types'])){
        $post_type_show = true;
      }
    }
    
    // check php
		$php_show = false;
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
    if(!isset($logic['post_types']) && !isset($logic['php']))
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
	// clone creation seems to happen early
	if (!isset($widget->widget_type)){
		$ww_widget = ww_get_single_widget($widget->ID);
		$widget->widget_type = $ww_widget->widget_type;
		$widget->widget_data = $ww_widget->widget_data;
	}
	
	$args = array(
		'widget' => $widget,
		'widget_id' => $widget->ID,
		'widget_type' => $widget->widget_type,
	);

	return theme('ww_widget', $args);
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
  $args = array('before_widget'=>'','after_widget'=>'','before_title'=>'','after_title'=>'[eXpl0de]');
 
  // output to variable for replacements
  ob_start();
     $widget_obj->widget($args, $instance);
  $temp = ob_get_clean();
  
  // get title and content separate
  $array = explode("[eXpl0de]", $temp);
  
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