<?php
/*
 * Widget Wrangler Admin Panel and related functions
 */
/*
 * Display the plugin on admin screen
 */
function ww_admin_init()
{
  $settings = ww_get_settings();
  
  // eventually I will handle access control this way
  $show_panel = true;
  
  if ($show_panel)
  {
    // Add panels into the editing sidebar(s)
    foreach($settings['post_types'] as $post_type){
      add_meta_box('ww_admin_meta_box', __('<img src="'.WW_PLUGIN_URL.'/images/wrangler_icon.png" />Widget Wrangler'), 'ww_admin_sidebar_panel', $post_type, 'normal', 'high');
    }
    
    // Add some CSS to the admin header on the widget wrangler pages, and edit pages
    if((isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') ||
        (isset($_GET['page']) &&
          (
            $_GET['page'] == 'ww-defaults' ||
            $_GET['page'] == 'ww-debug'    ||
            $_GET['page'] == 'ww-new'      ||
            $_GET['page'] == 'ww-clone'    ||
            $_GET['page'] == 'ww-sidebars' ||
            $_GET['page'] == 'ww-spaces'   ||
            $_GET['page'] == 'ww-postspage'
          )
        )
      )
    {
      add_action('admin_enqueue_scripts', 'ww_admin_js');
      add_action('admin_head', 'ww_admin_css');
    }
  }
  add_action('admin_head', 'ww_adjust_css');
  //disable autosave
  //wp_deregister_script('autosave');
}
add_action( 'admin_init', 'ww_admin_init' );
/*
 * Provide Widget Wrangler selection when editing a page
 */
function ww_admin_sidebar_panel($pid)
{
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
      $output = array_merge(ww_create_widget_list($all_widgets, $active_array, $sidebars), $output);
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
  else{
    print "You must save this page before adjusting widgets.";
  }
}

/*
 * Hook into saving a page
 * Save the post meta for this post
 */
function ww_save_post($id)
{
  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( !wp_verify_nonce( $_POST['ww_noncename'], plugin_basename(__FILE__) )) {
    return $id;
  }
  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
  // to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
    return $id;
  }
  
  // Check permissions
  $settings = ww_get_settings();
  if (in_array($_POST['post_type'], $settings['post_types']) && !current_user_can('edit_page', $id)){
    return $id;
  }
  
  // OK, we're authenticated: we need to find and save the data
  $all_widgets = ww_get_all_widgets();
  
  $i = 1;
  // loop through all widgets looking for those submitted
  foreach($all_widgets as $key => $widget)
  {
    $name = $_POST["ww-".$widget->post_name];
    $weight = $_POST["ww-".$widget->post_name."-weight"];
    $sidebar = $_POST["ww-".$widget->post_name."-sidebar"];
    
    // if something was submitted without a weight, make it neutral
    if ($weight < 1){
      $weight = $i;
    }
    // add it to the active widgets list
    if (($sidebar && $name) &&
        ($sidebar != 'disabled'))
    {
      $active_widgets[$sidebar][] = array(
            'id' => $widget->ID,
            'name' => $widget->post_title,
            'weight' => $weight,
            );
    }
    $i++;
  }
  
  // if none are defined, save an empty array 
  if(count($active_widgets) == 0){
    $active_widgets = array();    
  }
  
  //save what we have
  $this_post_widgets = serialize($active_widgets);
  ww_update_post_widgets( $id, $this_post_widgets);
  
  // get defaults without- disabled for comparison
  $defaults = unserialize(get_option('ww_default_widgets'));
  unset($defaults['disabled']);
  $defaults = serialize($defaults);
  
  // last minute check for reset to defaults for this page
  if($_POST['ww-reset-widgets-to-default'] == "on" ||
     ($this_post_widgets == $defaults))
  {
    ww_delete_post_widgets( $id); 
  }
}
add_action( 'save_post', 'ww_save_post' );
