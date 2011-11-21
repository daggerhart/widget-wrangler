<?php
/*
 * Default widgets page
 */
function ww_postspage_form()
{
  include WW_PLUGIN_DIR."/form-defaults.inc";
  if($widgets_string = get_option('ww_postspage_widgets')){
    $widgets = unserialize($widgets_string);
  }
  else if($defaults = get_option('ww_default_widgets')){
    $widgets = unserialize($defaults);
  }
  else {
    $widgets = array('');
  }
  
  print "<div class='wrap'>
          <h2>Posts page Widgets</h2>
          <p>Set the widgets for your Posts page.</p>
          
            <div id='widget-wrangler-form' class='new-admin-panel' style='width: 50%;'>
              <form action='edit.php?post_type=widget&page=ww-postspage&ww-postspage-action=update&noheader=true' method='post' name='widget-wrangler-form'>";
              
  print  ww_default_page_widgets($widgets);
  print "</div></form></div>";
}
/*
 * Save widgts on the default page.  Stored as wp_option
 */
function ww_postspage_save_widgets($posted)
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
    $widgets = serialize($active_widgets);
    update_option('ww_postspage_widgets', $widgets);
    return $active_array;
  }
  else
  {
    update_option('ww_postspage_widgets', 'N;');
    return array();
  }
  
}