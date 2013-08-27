<?php


// example template hook
function ww_admin_templates($templates)
{
  $templates['ww_admin_page'] = array(
    'files' => 'admin-page.php',
    'default_path' => dirname(__FILE__).'/templates',
    'arguments' => array(
        'title' => '',
        'content' => '',
    ),
  );
  $templates['ww_sortable_widgets'] = array(
    'arguments' => array(
      'active_widgets' => array(),
    ),
  );
  return $templates;
}
add_filter('tw_templates', 'ww_admin_templates');

/*
 * Widget Wrangler Admin Panel and related functions
 */
function ww_display_admin_panel()
{
	global $ww_page;

	// gather info for admin meta data
  $settings = ww_get_settings();
	$ww_admin_pages = array(
    'ww-debug',
    'ww-clone',
    'ww-corrals',
    'ww-presets',
		'ww-settings',
	);
	if (isset($_GET['post'])){
		$post_id = $_GET['post'];
	}
	else if (isset($_POST['post_ID'])){
		$post_id = $_POST['post_ID'];
	}
	
	// record some info for easy use later
	$ww_page['admin'] = array(
		'post_id' => (isset($post_id)) ? $post_id : NULL,
		'post_id_is_type_allowed' => (isset($post_id) && in_array(get_post_type($post_id), $settings['post_types'])),
		'post_type_is_type_allowed' => (isset($_GET['post_type']) && in_array($_GET['post_type'], $settings['post_types'])),
		'is_admin_page' => (isset($_GET['page']) && (in_array($_GET['page'], $ww_admin_pages))),
		'is_widget_edit' => ((isset($post_id) && get_post_type($post_id) == "widget") || (isset($_GET['post_type']) && $_GET['post_type'] == "widget")),
	);
	$ww_page['admin']['is_post_edit'] = ($ww_page['admin']['post_id_is_type_allowed'] || $ww_page['admin']['post_type_is_type_allowed']);
	//$ww_page['admin']['is_post_edit'] = ((isset($post_id) && in_array(get_post_type($post_id), $settings['post_types'])) || (isset($_GET['post_type']) && in_array($_GET['post_type'], $settings['post_types'])));
	
	
	
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
	if ($ww_page['admin']['is_admin_page']) {
		add_action( 'admin_enqueue_scripts', 'ww_admin_js');
		add_action( 'admin_enqueue_scripts', 'ww_post_type_widget_js');
		add_action( 'admin_enqueue_scripts', 'ww_sortable_widgets_js');
		if (isset($settings['disable_autosave']) && $settings['disable_autosave'] == 1) {
			add_action( 'admin_enqueue_scripts', 'ww_dequeue_autosave' );
		}
	}

	if ($ww_page['admin']['is_post_edit']) {
		// shortcode listbox tinymce button
		if (isset($settings['enable_shortcode_tinymce']) &&
				$settings['enable_shortcode_tinymce'] == 1)
		{
			include_once WW_PLUGIN_DIR.'/admin/shortcode.tinymce.inc';
			$ww_shortcode_tinymce = new WidgetWrangler_ShortcodeTinymce();
			add_action('admin_head', array($ww_shortcode_tinymce, 'addWidgetListbox'));
		}
	}
	
	// admin ajax
	add_action( 'wp_ajax_nopriv_ww_form_ajax', 'ww_form_ajax' );
	add_action( 'wp_ajax_ww_form_ajax', 'ww_form_ajax' );
}

/*
 * Ajax form templates
 */
function ww_form_ajax() {
	if (isset($_POST['op'])){
		switch ($_POST['op']){
			case 'replace_edit_page_widgets':
				
				// legit post ids only
				if (isset($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0)
				{
					$ajax['post_id'] = $_POST['post_id'];
					
					if (isset($_POST['preset_id']) && is_numeric($_POST['preset_id']) && $_POST['preset_id'] > 0){
						$ajax['preset_id'] = $_POST['preset_id'];
					}

					ob_start();
						ww_widget_post_form(NULL, $ajax);
					$output = ob_get_clean();

					print $output;
				}
				break;
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
	$actions = array("create", "update", "delete");
  
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
  
	$preset_id = (isset($_GET['preset_id']) && !empty($_GET['preset_id'])) ? $_GET['preset_id'] : 1;
	$this_preset = ww_get_preset($preset_id);
		
	// presets edit form
	$args = array(
		'title' => 'Edit Page Preset <em>'. $this_preset->data['name'] .'</em>',
		'content' => ww_preset_form(),
	);
	print theme('ww_admin_page', $args);
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
	$args = array(
		'title' => 'Widget Corrals',
		'content' => ww_corral_form(),
	);
	print theme('ww_admin_page', $args);
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

	// settings form
	$args = array(
		'title' => 'Widget Wrangler Settings',
		'content' => ww_settings_form(),
	);
	print theme('ww_admin_page', $args);
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
	// show clone page
	$args = array(
		'title' => 'Copy a Wordpress Widget',
		'content' => ww_clone_form(),
	);
	print theme('ww_admin_page', $args);
}



/*
 * for whatever.
 */
function ww_debug_page(){
	global $wpdb;
	//$all_widgets = ww_get_all_widgets();

	
	//print theme('ww_admin_page', $a);
}
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
  wp_enqueue_script('ww-post-ajax-js',
                  plugins_url('/js/ajax.js', WW_PLUGIN_DIR.'/admin/js' ),
                  array('jquery-ui-core', 'jquery-ui-sortable'),
                  false,
                  true);
	$data = ww_json_data();
	wp_localize_script( 'ww-sortable-widgets-js', 'WidgetWrangler', array('l10n_print_after' => 'WidgetWrangler = '.$data.';') );	
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

function ww_json_data() {
	$WidgetWrangler = array();
	$WidgetWrangler['data'] = array(
		'ajaxURL' => admin_url( 'admin-ajax.php' ),
		'allWidgets' => array_values(ww_get_all_widgets()),
	);
	return json_encode($WidgetWrangler);
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