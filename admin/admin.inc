<?php

/* ================================ Page handling */
/*
 * Presets admin page handler
 */
function ww_presets_page_handler()
{
  include_once WW_PLUGIN_DIR.'/admin/preset.inc';
  
	if(isset($_GET['action'])) {
    switch ($_GET['action'])
    {
      // create new widget preset
      case 'create':
        $preset_id = ww_create_preset();
        break;

      // update an existing widget preset
      case 'update':

        // switch Save & Delete buttons
        // do not let them delete defaults or post pages
        if(isset($_POST['action-delete']) &&
           $_POST['preset-type'] != 'default')
        {
          ww_delete_preset();
          $preset_id = 1;
        }
        else if (isset($_POST['action-save'])){
          $preset_id = ww_update_preset();
        }
        break;
    }
    // send to the new preset
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-presets&preset_id='.$preset_id);
  }
  else {
    // presets edit form
    ww_preset_form();
  }
}

/*
 * Corrals admin page handler
 */
function ww_corrals_page_handler()
{
  // include the corrals form
  include_once WW_PLUGIN_DIR.'/admin/corral.inc';

  if(isset($_GET['ww-corral-action'])){
    switch($_GET['ww-corral-action']){
      case 'insert':
        $new_corral_id = ww_corral_insert($_POST);
        break;
      case 'delete':
        ww_corral_delete($_POST);
        break;
      case 'update':
        ww_corral_update($_POST);
        break;
      case 'sort':
        ww_corral_sort($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-corrals');
  }
  // show corrals page
  ww_corral_form();
}

/*
 * Handles creation of new cloned widgets, and displays clone new widget page
 */
function ww_clone_page_handler()
{
  include_once WW_PLUGIN_DIR.'/admin/clone.inc';

  if(isset($_GET['ww-clone-action'])){
    switch($_GET['ww-clone-action']){
      case 'insert':
        // create new cloned widget
        $new_post_id = ww_clone_insert($_POST);
        // goto new widget page
        wp_redirect(get_bloginfo('wpurl').'/wp-admin/post.php?post='.$new_post_id.'&action=edit');
        break;
    }
  }
  else{
    // show clone page
    ww_clone_form();
  }
}

/*
 * Handles settings page
 */
function ww_settings_page_handler()
{
  if (isset($_GET['ww-settings-action'])){
    switch($_GET['ww-settings-action']){
      case "save":
        ww_settings_save($_POST);
        break;
      case "reset":
        ww_settings_reset_widgets();
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-settings');
  }
  else{
    // settings form
    ww_settings_form();
  }
}
/*
 * for whatever.
 */
function ww_debug_page(){}
// */

/*==================================== JS & CSS */
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
 * Javascript for drag and drop corral sorting
 */
function ww_widget_js(){
  wp_enqueue_script('ww-widget-js',
                    plugins_url('/js/widget.js', WW_PLUGIN_DIR.'/js' ),
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
	print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/admin/admin.css" />';
}




/*
 * Serialize widgets from a saved form
 *
 * @param array $posted $_POST array for a saved widget
 * @return string serialized array of the widgets within the $_POST array
 */
function ww_serialize_widgets($posted)
{
  $active_widgets = array();
  $all_widgets = ww_get_all_widgets();

  // loop through post data and build widgets array for saving
  $i = 1;
  foreach($all_widgets as $key => $widget)
  {
    $name = $posted['ww-widgets'][$widget->post_name]['name'];
    $weight = $posted['ww-widgets'][$widget->post_name]['weight'];
    $corral = $posted['ww-widgets'][$widget->post_name]['corral'];

    // if something was submitted without a weight, make it neutral
    if ($weight < 1)
    {
      $weight = $i;
    }
    // add widgets to save array
    if (($corral && $name) &&
        ($corral != 'disabled'))
    {
      $active_widgets[$corral][] = array(
            'id' => $widget->ID,
            'name' => $widget->post_title,
            'weight' => $weight,
            );
    }
    $i++;
  }
  // serialize the results
  return serialize($active_widgets);
}

/* ========================================== Page/post edit */

/*
 * Save a post's widgets
 *
 * @param int $post_id The WP post ID for the page or post to assign widgets
 * @param string $widgets Serialized array of widgets on this post
 */
function ww_update_post_widgets($post_id, $post_widgets = ''){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";
  $set = array(
    'widgets' => $post_widgets,
  );
  $where = array(
    'post_id' => $post_id,
  );

  // If can't update, insert
  // $wpdb->update responds false on errors
  if(!$wpdb->update($table, $set, $where))
  {
    // send along the post_id
    $set['post_id'] = $post_id;

    $wpdb->insert($table, $set);
  }
}



/*
 * Delete a post's widgets
 *
 * @param int $post_id The WP post ID for the page or post to delete widgets from
 */
function ww_delete_post_widgets($post_id){
  if(is_numeric($post_id))
  {
    global $wpdb;
    $table = $wpdb->prefix."ww_post_widgets";

    $sql = "DELETE FROM ".$table." WHERE post_id = ".$post_id;
    $wpdb->query($sql);
  }
}


/*
 * Widget types
 *
 * @return array All widget types
 */
function ww_all_widget_types(){
  return array(
    'standard' => 'Standard',
    'clone' => 'Clone',
  );
}

/* ==================================== HELPER FUNCTIONS ===== */
/*
 * Helper function for making corral slugs
 */
function ww_make_slug($string){
  $search = array("!","@","#","$","%","^","&","*","(",")","-","+","=","{","}","[","]","\\","|",":",";","'","<",",",">",".","?","/","~","`");
  return str_replace(" ", "_", strtolower(str_replace($search, "", strip_tags($string))));
}