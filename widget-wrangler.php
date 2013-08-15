<?php
/*
Plugin Name: Widget Wrangler
Plugin URI: http://www.widgetwrangler.com
Description: Widget Wrangler gives the wordpress admin a clean interface for managing widgets on a page by page basis. It also provides widgets as a post type, the ability to clone existing wordpress widgets, and granular control over widgets' templates.
Author: Jonathan Daggerhart
Version: 2.0
Author URI: http://www.daggerhart.com
License: GPL2
*/
define('WW_VERSION', 2.0);
/*  Copyright 2010  Jonathan Daggerhart  (email : jonathan@daggerhart.com)
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('WW_PLUGIN_DIR', dirname(__FILE__));
define('WW_PLUGIN_URL', get_bloginfo('wpurl')."/wp-content/plugins/widget-wrangler");

// functions
include_once WW_PLUGIN_DIR.'/common.inc';

// theme functions
include_once WW_PLUGIN_DIR.'/front.inc';

// settings functions
include_once WW_PLUGIN_DIR.'/admin/settings.inc';

// add the widget post type class
include_once WW_PLUGIN_DIR.'/post_type-widget.inc';

// include WP standard widgets for corrals
include_once WW_PLUGIN_DIR.'/corral.widget.inc';

/*
 * checkout version number and perform an upgrade if necessary
 */
function ww_check_version(){
  if($last_version = get_option('ww_version'))
  {
    // compare versions
    if ($last_version < WW_VERSION)
    {
      add_action('admin_notices', 'ww_upgrade_message');
      //// include upgrade inc
      //include_once WW_PLUGIN_DIR.'/includes/upgrade.inc';
      //$upgrade_function = 'ww_upgrade_'.ww_make_slug($last_version).'_to_'.ww_make_slug(WW_VERSION);
      //if(function_exists($upgrade_function)){
      //  $upgrade_function();
      //}
      //update_option('ww_version', WW_VERSION);
    }
  }
  // if last version doesn't exist, then its an old 1.x version
  else
  {
    add_action('admin_notices', 'ww_upgrade_message');
    //// include upgrade inc
    //include_once WW_PLUGIN_DIR.'/includes/upgrade.inc';
    //// run version related upgrade
    //ww_upgrade_1x_to_2x();
    //update_option('ww_version', WW_VERSION);
  }
}

/*
 * Admin initialize
 */
function ww_admin_init()
{
  // handle upgrades
  ww_check_version();

  // include admin panel and helper functions such as sortable widgets
  include_once WW_PLUGIN_DIR.'/admin/widgets-post.inc';

  // determine whether to display the admin panel
  // handles adding some css and the js
  ww_display_admin_panel();

  // add admin css
  add_action( 'admin_head', 'ww_admin_css');

}
add_action( 'admin_init', 'ww_admin_init' );
add_action( 'save_post', 'ww_save_post' );

/*
 * All my hook_menu implementations
 */
function ww_menu()
{
  $clone    = add_submenu_page( 'edit.php?post_type=widget', 'Clone Widget', 	 'Clone Widget',   'manage_options', 'ww-clone',    'ww_clone_page_handler'   );
  $corrals 	= add_submenu_page( 'edit.php?post_type=widget', 'Widget Corrals', 'Widget Corrals', 'manage_options', 'ww-corrals',  'ww_corrals_page_handler' );
  $presets  = add_submenu_page( 'edit.php?post_type=widget', 'Corral Presets', 'Corral Presets', 'manage_options', 'ww-presets',  'ww_presets_page_handler' );
  $settings = add_submenu_page( 'edit.php?post_type=widget', 'Settings',       'Settings',       'manage_options', 'ww-settings', 'ww_settings_page_handler');
  //$debug    = add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets',  'Debug',          'manage_options', 'ww-debug',    'ww_debug_page');
  add_action( "admin_print_scripts-$corrals", 'ww_corral_js' );
}
add_action( 'admin_menu', 'ww_menu');


/************************************************************
 * Page handling
 */
/*
 * Produce the Widget Presets
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
 * Corral page handler
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
  if ($_GET['ww-settings-action']){
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
/*********************************************** end page handling */

/*
 * Shortcode support for all widgets
 *
 * @param array $atts Attributes within the executed shortcode.  'id' => widget->ID
 * @return string HTML for a single themed widget
 */
function ww_single_widget_shortcode($atts)
{
  $short_array = shortcode_atts(array('id' => ''), $atts);
  extract($short_array);
  return ww_theme_single_widget(ww_get_single_widget($id));
}
add_shortcode('ww_widget','ww_single_widget_shortcode');

/*
 * Make sure to show our plugin on the admin screen
 */
function ww_hec_show_dbx( $to_show )
{
  array_push( $to_show, 'widget-wrangler' );
  return $to_show;
}


/*******************************************************************************
 * Activation hooks
 *
 * These are required to be in this file
 */

// activation hooks
register_activation_hook(__FILE__, 'ww_post_widgets_table');
register_activation_hook(__FILE__, 'ww_widget_data_table');
register_activation_hook(__FILE__, 'ww_widget_presets_table');
register_activation_hook(__FILE__, 'ww_widget_preset_term_relationships_table');
register_activation_hook(__FILE__, 'ww_default_presets');

/*
 * Create post - widgets table
 */
function ww_post_widgets_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";

  $sql = "CREATE TABLE " . $table . " (
	  post_id mediumint(11) NOT NULL,
	  widgets text NOT NULL,
	  UNIQUE KEY id (post_id)
  );";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
/*
 * Create widget data table
 */
function ww_widget_data_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_data";

  $sql = "CREATE TABLE " . $table . " (
	  post_id mediumint(11) NOT NULL,
	  type varchar(32) NOT NULL,
   data text NOT NULL,
   UNIQUE KEY id (post_id)
  );";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
/*
 * Create widget presets table
 */
function ww_widget_presets_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";

  $sql = "CREATE TABLE " . $table . " (
	  id mediumint(11) NOT NULL AUTO_INCREMENT,
	  type varchar(32) NOT NULL,
   data text NOT NULL,
   widgets text NOT NULL,
   UNIQUE KEY id (id)
  );";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
/*
 * Create widget presets table
 */
function ww_widget_preset_term_relationships_table(){
  global $wpdb;
  $table = $wpdb->prefix."ww_preset_term_relationships";

  $sql = "CREATE TABLE " . $table . " (
	  preset_id mediumint(11) NOT NULL,
	  term_id mediumint(11) NOT NULL
  );";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
/*
 * Look for an insert default presets
 */
function ww_default_presets(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";
  $data = array(
    'id' => 0,
    'type' => 'default',
    'data' => '',
    'widgets' => serialize(array()),
  );

  // defaults
  $sql_select = "SELECT id FROM ".$table." WHERE id = 1";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 1;
    $data['data'] = serialize(array('name' => 'Defaults'));
    $wpdb->insert($table, $data);
  }

  // posts page
  $sql_select = "SELECT id FROM ".$table." WHERE id = 2";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 2;
    $data['data'] = serialize(array('name' => 'Posts Page'));
    $wpdb->insert($table, $data);
  }

  // search page
  $sql_select = "SELECT id FROM ".$table." WHERE id = 3";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 3;
    $data['data'] = serialize(array('name' => 'Search Page'));
    $wpdb->insert($table, $data);
  }

  // 404 page
  $sql_select = "SELECT id FROM ".$table." WHERE id = 4";
  $row = $wpdb->get_row($sql_select);
  if(!$row){
    $data['id'] = 4;
    $data['data'] = serialize(array('name' => '404 Page'));
    $wpdb->insert($table, $data);
  }
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