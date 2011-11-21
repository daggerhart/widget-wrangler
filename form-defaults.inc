<?php
/*
 * Default widgets page
 */
function ww_theme_defaults_page()
{
  $defaults = get_option('ww_default_widgets');
  $defaults_array = array('');
  if(is_string($defaults))
  {
    $defaults_array = unserialize($defaults);
  }  
  
  print "<div class='wrap'>
          <h2>Default Widgets</h2>
          <p>Set the default widgets for your sidebar.</p>
            <div id='widget-wrangler-form' class='new-admin-panel' style='width: 50%;'>
              <form action='edit.php?post_type=widget&page=ww-defaults&ww-defaults-action=update&noheader=true' method='post' name='widget-wrangler-form'>";
              
  print  ww_default_page_widgets($defaults_array);
  print "</div></form></div>";
}
/*
 * Save widgts on the default page.  Stored as wp_option
 */
function ww_save_default_widgets($posted)
{
  $all_widgets = ww_get_all_widgets();
  
  // loop through post data and build widgets array for saving
  $i = 1;
  foreach($all_widgets as $key => $widget)
  {
    $name = $posted["ww-".$widget->post_name];
    $weight = $posted["ww-".$widget->post_name."-weight"];
    $sidebar = $posted["ww-".$widget->post_name."-sidebar"];
    
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
  
  /*
   * Assign true weights to avoid conflicts
   */
  if(is_array($active_widgets))
  {
    $defaults = serialize($active_widgets);
    update_option('ww_default_widgets', $defaults);
    return $active_array;
  }
  else
  {
    update_option('ww_default_widgets', 'N;');
    return array();
  }
  
}
/*
 * Retrieve and theme the widgets on the Default Widgets Page
 */
function ww_default_page_widgets($defaults_array)
{
  //global $defaults_array;
  $temp = array();
  $widgets = ww_get_all_widgets();
  $sidebars = ww_get_all_sidebars();
 
  // start output
  $output = array();
  $output['open'] = "
              <div class='outer'>
                <input value='true' type='hidden' name='widget-wrangler-edit' />
                <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";
  
  $i = 1;
  
  // add additional sidebars to output
  if (is_array($widgets) && count($sidebars) > 0)
  {
    $temp = ww_create_widget_list($widgets, $defaults_array, $sidebars);
    if(is_array($temp))
    {
      $output = array_merge($temp, $output);
    }
  }
  
  $output['close'] = " <!-- .inner -->
               </div><!-- .outer -->
               <input class='button' name='ww-save-default' type='submit' value='Save' />";
               
  if(is_array($output['active']))
  {
    foreach($output['active'] as $sidebar => $unsorted_widgets)
    {
      // sort each sidebar
      ksort($output['active'][$sidebar]);  
    }
  }
  // theme it out
  ww_theme_admin_panel($output);
}