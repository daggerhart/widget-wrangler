<?php
/*
 * Widget Wrangler Admin Panel and related functions
 */
function ww_display_admin_panel()
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
/*
 * Provide Widget Wrangler selection when editing a page
 */
function ww_admin_sidebar_panel($pid)
{
  include_once WW_PLUGIN_DIR.'/forms/admin-panel.inc';
}
/*
 * Javascript drag and drop for sorting
 */ 
function ww_admin_js(){
  wp_enqueue_script('ww-admin-js',
                  plugins_url('/js/admin-panel.js', WW_PLUGIN_DIR.'/js' ),
                  array('jquery-ui-core', 'jquery-ui-sortable'),
                  false,
                  true);
}
/*
 * Javascript for drag and drop sidebar sorting
 */
function ww_sidebar_js(){
  wp_enqueue_script('ww-sidebar-js',
                    plugins_url('/js/sidebars.js', WW_PLUGIN_DIR.'/js' ),
                    array('jquery-ui-core', 'jquery-ui-sortable'),
                    false,
                    true);
}
/*
 * Handle CSS necessary for Admin Menu on left
 */
function ww_adjust_css(){
  print "<style type='text/css'>
         li#menu-posts-widget a.wp-has-submenu {
          letter-spacing: -1px;
         }";
  if ($_GET['post_type'] == 'widget')
  {
    print "#wpbody-content #icon-edit {
             background: transparent url('".WW_PLUGIN_URL."/images/wrangler_post_icon.png') no-repeat top left; 
           }";
  }
  print  "</style>";
}
/*
 * Add css to admin interface
 */
function ww_admin_css(){
	print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/widget-wrangler.css" />';
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
