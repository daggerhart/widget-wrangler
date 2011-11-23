<?php
  // dirty hack to get post id, prob a better way.
  $pid = $_GET['post'];
  
  if (is_numeric($pid))
  {
    // put into array
    $all_widgets = ww_get_all_widgets(); 
    $sidebars = ww_get_all_sidebars();
    $sorted_widgets = array();
    $output = array();
    $active_array = array();
    $default_message = "Defaults are Not Defined, click <a href='/edit.php?post_type=widget&page=ww-defaults'>here</a> to select your default widgets.";
    
    // get post meta for this post
    // array of chosen widgets
    if ($active = ww_get_post_widgets($pid))
    {
      $active_array = unserialize($active);
      $default_message = "Defaults are Disabled. This page is wrangling widgets on its own.";
    }  
    elseif($default_widgets = get_option('ww_default_widgets'))
    {
      // pull default widgets 
      $active_array = unserialize($default_widgets);
      $default_message = "This page is using the <a href='/edit.php?post_type=widget&page=ww-defaults'>Defaults Widgets</a>.";
    }
    
    $output['open'] = "
              <div id='widget-wrangler-form' class='new-admin-panel'>
                  <div class='outer'>
                    <div id='ww-defaults'>
                      <span>".$default_message."</span>
                    </div>
                    <input value='true' type='hidden' name='widget-wrangler-edit' />
                    <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";
  
    $output['close'] = " <!-- .inner -->
                    <hr />
                    <label><input type='checkbox' name='ww-reset-widgets-to-default' value='on' /> Reset this page to the default widgets.</label>
                 </div><!-- .outer -->
               </div>";
               
    // merge the widget arrays into the output array
    if (count($all_widgets) > 0){
      $output = array_merge(ww_create_sortable_widgets($all_widgets, $active_array, $sidebars), $output);
    }
    
    // sort the sidebar's widgets
    if ($output['active']){
      foreach($output['active'] as $sidebar => $unsorted_widgets){
        if ($output['active'][$sidebar]){
          ksort($output['active'][$sidebar]);
        }
      }
    }
    
    // theme it out
    ww_theme_sortable_widgets($output);
  }
  else
  {
    print "You must save this page before adjusting widgets.";
  }