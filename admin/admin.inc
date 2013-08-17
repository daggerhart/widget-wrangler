<?php
/*
 * Widget Wrangler Admin Panel and related functions
 */
function ww_display_admin_panel()
{
  $settings = ww_get_settings();
	$ww_pages = array(
    //'ww-defaults',
    //'ww-postspage',
    //'ww-new',
    'ww-debug',
    'ww-clone',
    'ww-corrals',
    'ww-presets',
	);
	
  // eventually I will handle access control this way
  $show_panel = true;

  if ($show_panel)
  {
    if(is_array($settings['post_types'])){
      // Add panels into the editing corral(s)
      foreach($settings['post_types'] as $post_type){
        add_meta_box('ww_admin_meta_box', __('<img src="'.WW_PLUGIN_URL.'/admin/images/wrangler_icon.png" />Widget Wrangler'), 'ww_widget_post_form', $post_type, 'normal', 'high');
      }
    }

    // Add some CSS to the admin header on the widget wrangler pages, and edit pages
    if( 
				// new page/post
				(isset($_GET['post_type']) && isset($settings['post_types'][$_GET['post_type']]))
				||
				// editing
			  (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')
				||
				// internal admin pages
				(isset($_GET['page']) && (in_array($_GET['page'], $ww_pages)))
			)
    {
      add_action( 'admin_head', 'ww_admin_css');
      add_action( 'admin_enqueue_scripts', 'ww_admin_js');
    }
  }
  add_action('admin_head', 'ww_adjust_css');
  //disable autosave
  //wp_deregister_script('autosave');
}

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
                  plugins_url('/js/sortable-widgets.js', WW_PLUGIN_DIR.'/admin/js' ),
                  array('jquery-ui-core', 'jquery-ui-sortable'),
                  false,
                  true);
	wp_localize_script( 'ww-admin-js', 'WidgetWrangler', array('l10n_print_after' => 'WidgetWrangler = {};') );	
}
/*
 * Javascript for drag and drop corral sorting
 */
function ww_corral_js(){
  wp_enqueue_script('ww-corral-js',
                    plugins_url('/js/corrals.js', WW_PLUGIN_DIR.'/admin/js' ),
                    array('jquery-ui-core', 'jquery-ui-sortable'),
                    false,
                    true);
}
/*
 * Javascript for drag and drop corral sorting
 */
function ww_widget_js(){
  wp_enqueue_script('ww-widget-js',
                    plugins_url('/js/single-widget.js', WW_PLUGIN_DIR.'/admin/js' ),
                    array('jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-tabs'),
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
  if (isset($_GET['post_type']) && $_GET['post_type'] == 'widget')
  {
    print "#wpbody-content #icon-edit {
             background: transparent url('".WW_PLUGIN_URL."/admin/images/wrangler_post_icon.png') no-repeat top left;
           }";
  }
  print  "</style>";
}
/*
 * Add css to admin interface
 */
function ww_admin_css(){
	print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/admin/css/admin.css" />';
}



/* ========================================== Page/post edit */


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

// http://www.wprecipes.com/how-to-show-an-urgent-message-in-the-wordpress-admin-area
function ww_set_message($message, $type = 'updated')
{
		 return '<div id="message" class="'.$type.'">
             <p>'.$message.'</p>
           </div>';
}

function ww_upgrade_message(){
  $msg = '<strong>Widget Wrangler requires a database update!</strong><br />
          Backup your database, and then visit <a href="">this page</a> to perform the update.';
  print ww_set_message($msg, 'error');
}