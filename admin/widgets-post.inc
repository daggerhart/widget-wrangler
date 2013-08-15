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
    if(is_array($settings['post_types'])){
      // Add panels into the editing corral(s)
      foreach($settings['post_types'] as $post_type){
        add_meta_box('ww_admin_meta_box', __('<img src="'.WW_PLUGIN_URL.'/images/wrangler_icon.png" />Widget Wrangler'), 'ww_widget_post_form', $post_type, 'normal', 'high');
      }
    }

    // Add some CSS to the admin header on the widget wrangler pages, and edit pages
    if((isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') ||
        (isset($_GET['page']) &&
          (
            $_GET['page'] == 'ww-defaults' ||
            $_GET['page'] == 'ww-debug'    ||
            $_GET['page'] == 'ww-new'      ||
            $_GET['page'] == 'ww-clone'    ||
            $_GET['page'] == 'ww-corrals' ||
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
 * Javascript for drag and drop corral sorting
 */
function ww_corral_js(){
  wp_enqueue_script('ww-corral-js',
                    plugins_url('/js/corrals.js', WW_PLUGIN_DIR.'/js' ),
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
function ww_create_sortable_widgets($widgets, $ref_array, $corrals)
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

    // look for appropriate corral, default to disabled
    if ($keys[0] == '' || (!array_key_exists($keys[0], $corrals))){
      $keys[0] = "disabled";
    }

    // setup initial info
    $corral_slug = $keys[0];

    // get weight
    if (isset($keys[1]) && isset($ref_array[$corral_slug][$keys[1]]['weight'])) {
      $weight = $ref_array[$corral_slug][$keys[1]]['weight'];
    } else {
      $weight = count($temp); // 'next' row
    }

    // build select box
    $corrals_options = "<option value='disabled'>Disabled</option>";
    foreach($corrals as $slug => $corral){
      ($slug == $corral_slug) ? $selected = "selected='selected'" : $selected = '';
      $corrals_options.= "<option name='".$slug."' value='".$slug."' ".$selected.">".$corral."</option>";
    }

    // add item to our temp array
    $temp[$weight] = "<li class='ww-item ".$corral_slug." nojs' width='100%'>
                        <input class='ww-widget-weight' name='ww-widgets[".$widget->post_name."][weight] type='text' size='2' value='$weight' />
                        <select name='ww-widgets[".$widget->post_name."][corral]'>
                        ".$corrals_options."
                        </select>
                        <input class='ww-widget-name' name='ww-widgets[".$widget->post_name."][name]' type='hidden' value='".$widget->post_name."' />
                        <input class='ww-widget-id' name='ww-widgets[".$widget->post_name."][id]' type='hidden' value='".$widget->ID."' />
                        ".$widget->post_title."
                      </li>";

    // place into output array
    if ($corral_slug == 'disabled'){
      $output['disabled'][] = $temp[$weight];
    }
    else{
      $output['active'][$corral_slug][$weight] = $temp[$weight];
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
  $corrals = ww_get_all_corrals();
  $output = isset($panel_array['open']) ? $panel_array['open']: "";

  // loop through corrals and add active widgets to list
  if (is_array($corrals))
  {
    foreach($corrals as $slug => $corral)
    {
      // open the list
      $output.= "<h4>".$corral."</h4>";
      $output.= "<ul name='".$slug."' id='ww-corral-".$slug."-items' class='inner ww-sortable' width='100%'>";

      if (isset($panel_array['active']) && isset($panel_array['active'][$slug]) && is_array($panel_array['active'][$slug])) {
        // loop through corral array and add items to list
        foreach($panel_array['active'][$slug] as $item){
          $output.= $item;
        }
        $style = "style='display: none;'";
      }
      else {
        $style = '';
      }
      // close the list
      $output.= "<li class='ww-no-widgets' ".$style.">No Widgets in this corral.</li>";
      $output.= "</ul>";
    }
  }

  // disabled list
  $output.= "<h4>Disabled</h4><ul name='disabled' id='ww-disabled-items' class='inner ww-sortable' width='100%'>";

  // loop through and add disabled widgets to list
  if (isset($panel_array['disabled']) && is_array($panel_array['disabled'])){
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

  $output.= isset($panel_array['close']) ? $panel_array['close']: "";

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
  if (isset($_POST['ww_noncename']) && !wp_verify_nonce( $_POST['ww_noncename'], plugin_basename(WW_PLUGIN_DIR.'/forms/admin-panel.inc') )) {
    return $id;
  }

  // verify if this is an auto save routine. If it is, our form has not been submitted, so we dont want
  // to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
    return $id;
  }

  // Check permissions
  $settings = ww_get_settings();
  if (isset($_POST['post_type']) && in_array($_POST['post_type'], $settings['post_types']) && !current_user_can('edit_page', $id)){
    return $id;
  }

  // OK, we're authenticated: we need to find and save the data
  $this_post_widgets = ww_serialize_widgets($_POST);

  //save what we have
  ww_update_post_widgets( $id, $this_post_widgets);

  // get defaults without- disabled for comparison
  $defaults = unserialize(get_option('ww_default_widgets'));
  unset($defaults['disabled']);
  $defaults = serialize($defaults);

  // last minute check for reset to defaults for this page
  if(isset($_POST['ww-reset-widgets-to-default']) &&
     $_POST['ww-reset-widgets-to-default'] == "on" ||
     ($this_post_widgets == $defaults))
  {
    ww_delete_post_widgets( $id);
  }
}


function ww_widget_post_form($pid){
  // dirty hack to get post id, prob a better way.
  $pid = $_GET['post'];
  
  if (is_numeric($pid))
  {
    // put into array
    $all_widgets = ww_get_all_widgets(); 
    $corrals = ww_get_all_corrals();
    $sorted_widgets = array();
    $output = array();
    $active_array = array();
    $default_message = "Defaults are Not Defined, click <a href='/edit.php?post_type=widget&page=ww-defaults'>here</a> to select your default widgets.";
    $default_space = NULL;
    
    // get post meta for this post
    // array of chosen widgets
    if ($active = ww_get_post_widgets($pid))
    {
      $active_array = unserialize($active);
      $default_message = "Defaults are Disabled. This page is wrangling widgets on its own.";
    }
    // pull default widgets 
    else
    {
      // include spaces functions
      include_once WW_PLUGIN_DIR.'/includes/spaces.inc';
      $default_space = ww_get_space(1);
      $active_array = unserialize($default_space->widgets);
      $default_message = "This page is using the <a href='edit.php?post_type=widget&page=ww-spaces&space_id=".$default_space->id."'>".$default_space->data['name']." Widgets</a>.";
    }
    
    // spaces select
    $all_spaces = ww_get_all_spaces();
    
    $spaces_select = "<div class=''>
                        <select id='ww_spaces_select' name='ww-spaces-select'>
                          <option value='0'>-none-</option>";
                          
    foreach($all_spaces as $space){
      $space->data = unserialize($space->data);
      $selected = (!is_null($default_space) && $space->id == $default_space->id) ? "selected='selected'" : '';
      $spaces_select.= "<option value='".$space->id."' ".$selected.">".$space->data['name']."</option>";
    }
    
    $spaces_select.=    "</select>
                      </div>";
    
    $output['open'] = "
              <div id='widget-wrangler-form' class='new-admin-panel'>
                  <div class='outer'>
                    <div id='ww-defaults'>
                      <span>".$default_message."</span>
                    </div>
                    $spaces_select
                    <input value='true' type='hidden' name='widget-wrangler-edit' />
                    <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";
  
    $output['close'] = " <!-- .inner -->
                    <hr />
                    <label><input type='checkbox' name='ww-reset-widgets-to-default' /> Reset this page to the default widgets.</label>
                 </div><!-- .outer -->
               </div>";
               
    // merge the widget arrays into the output array
    if (count($all_widgets) > 0){
      $output = array_merge(ww_create_sortable_widgets($all_widgets, $active_array, $corrals), $output);
    }
    
    // sort the corral's widgets
    if ($output['active']){
      foreach($output['active'] as $corral => $unsorted_widgets){
        if ($output['active'][$corral]){
          ksort($output['active'][$corral]);
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
}
