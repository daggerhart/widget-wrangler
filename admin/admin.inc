<?php
/*
 * Widget Wrangler Admin Panel and related functions
 */
function ww_display_admin_panel()
{
  $settings = ww_get_settings();
	$ww_pages = array(
    'ww-debug',
    'ww-clone',
    'ww-corrals',
    'ww-presets',
		'ww-settings',
	);
	
  // add admin css
  add_action( 'admin_head', 'ww_admin_css');
	add_action( 'admin_head', 'ww_adjust_css');
	add_action( 'save_post', 'ww_save_post' );
	
	// add meta boxes to all enabled post types
	if(is_array($settings['post_types'])){
		foreach($settings['post_types'] as $post_type){
			add_meta_box('ww_admin_meta_box', __('<img src="'.WW_PLUGIN_URL.'/admin/images/wrangler_icon.png" />Widget Wrangler'), 'ww_widget_post_form', $post_type, 'normal', 'high');
		}
	}
  
	// ww internal admin pages
	if (isset($_GET['page']) && (in_array($_GET['page'], $ww_pages))) {
		add_action( 'admin_enqueue_scripts', 'ww_admin_js');
		add_action( 'admin_enqueue_scripts', 'ww_post_type_widget_js');
		add_action( 'admin_enqueue_scripts', 'ww_sortable_widgets_js');
		if (isset($settings['disable_autosave']) && $settings['disable_autosave'] == 1) {
			add_action( 'admin_enqueue_scripts', 'ww_dequeue_autosave' );
		}
	}
}

/* ================================ Page handling */
/*
 * Presets admin page handler
 */
function ww_presets_page_handler()
{
  include_once WW_PLUGIN_DIR.'/admin/preset.inc';
	$actions = array("create", "update");
  
	if (isset($_POST['action'])){
		$action = array_pop(array_keys($_POST['action']));
		
		if (in_array($action, $actions)) {
			switch ($action)
			{
				// create new widget preset
				case 'create':
					$preset_id = ww_create_preset();
					break;
	
				// update an existing widget preset
				case 'update':
					$preset_id = ww_update_preset();
					break;
				
				case 'delete':
					ww_delete_preset();
					// send back to default
					$preset_id = 1;
					break;
			}
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

	$actions = array("insert", "delete", "update", "sort");
  
	if (isset($_POST['action'])){
		$action = array_pop(array_keys($_POST['action']));
		$tab = "";
	  if (in_array($action, $actions)) {
			switch($action){
				case 'insert':
					ww_corral_insert();
					break;
				case 'delete':
					ww_corral_delete();
					break;
				case 'update':
					ww_corral_update();
					break;
				case 'sort':
					ww_corral_sort();
					break;
			}
		}
		
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-corrals');
  }
  // show corrals page
  ww_corral_form();
}

/*
 * Handles settings page
 */
function ww_settings_page_handler()
{
	$actions = array("save", "reset");
  
	if (isset($_POST['action'])){
		$action = array_pop(array_keys($_POST['action']));
		
	  if (in_array($action, $actions)) {
			switch($action){
				case "save":
					ww_settings_save($_POST);
					break;
				case "reset":
					ww_settings_reset_widgets();
					break;
			}
		}
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-settings');
  }
  else{
    // settings form
    ww_settings_form();
  }
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
 * for whatever.
 */
function ww_debug_page(){}
// */

/*==================================== JS & CSS */
/*
 * Javascript for general admin stuff
 */
function ww_admin_js(){
  wp_enqueue_script('ww-admin-js',
                    plugins_url('/js/admin.js', WW_PLUGIN_DIR.'/admin/js' ),
                    array('jquery-ui-core', 'jquery-ui-sortable'),
                    false,
                    true);
}
/*
 * Javascript for sortable widgets
 */
function ww_sortable_widgets_js(){
  wp_enqueue_script('ww-sortable-widgets-js',
                  plugins_url('/js/sortable-widgets.js', WW_PLUGIN_DIR.'/admin/js' ),
                  array('jquery-ui-core', 'jquery-ui-sortable'),
                  false,
                  true);
	wp_localize_script( 'ww-sortable-widgets-js', 'WidgetWrangler', array('l10n_print_after' => 'WidgetWrangler = {};') );	
}

/*
 * Javascript for the widget post type edit page
 */
function ww_post_type_widget_js(){
  wp_enqueue_script('ww-post-type-widget-js',
                    plugins_url('/js/post_type-widget.js', WW_PLUGIN_DIR.'/admin/js' ),
                    array('jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-tabs'),
                    false,
                    true);
}

/*
 * Probably don't want to autosave widgets
 */
function ww_dequeue_autosave() {
	if ( 'widget' == get_post_type() ){
		wp_dequeue_script( 'autosave' );
	}
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