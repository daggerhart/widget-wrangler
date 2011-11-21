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
include WW_PLUGIN_DIR.'/functions.inc';

// add the widget post type class
include WW_PLUGIN_DIR.'/post_type.widget.inc';

// include admin panel and helper functions
include WW_PLUGIN_DIR.'/form-admin.inc';

// include WP standard widgets for sidebars
include WW_PLUGIN_DIR.'/widget.sidebar.inc';

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
register_activation_hook(__FILE__,'ww_post_widgets_table');
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
register_activation_hook(__FILE__,'ww_widget_data_table');


/*
 * All my hook_menu implementations
 */
function ww_menu()
{
  $clone    = add_submenu_page( 'edit.php?post_type=widget', 'Clone WP Widget', 'Clone WP Widget',  'manage_options', 'ww-clone',    'ww_clone_page_handler');
  $defaults = add_submenu_page( 'edit.php?post_type=widget', 'Default Widgets', 'Default WIdgets',     'manage_options', 'ww-defaults', 'ww_defaults_page_handler');
  // only show postspage widget setting if post page is the front page
  if(get_option('show_on_front') == 'posts'){
    $postspage= add_submenu_page( 'edit.php?post_type=widget', 'Posts Page',      'Posts Page Widgets',       'manage_options', 'ww-postspage','ww_postspage_page_handler');
  }
  $sidebars = add_submenu_page( 'edit.php?post_type=widget', 'Widget Sidebars', 'Sidebars',         'manage_options', 'ww-sidebars', 'ww_sidebars_page_handler');
  $settings = add_submenu_page( 'edit.php?post_type=widget', 'Settings',        'Settings',         'manage_options', 'ww-settings', 'ww_settings_page_handler');
  //$debug    = add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets', 'Debug', 'manage_options', 'ww-debug', 'ww_debug_page');
  add_action( "admin_print_scripts-$sidebars", 'ww_sidebar_js' );
}
add_action( 'admin_menu', 'ww_menu');

/*
 * for whatever.
 */
function ww_debug_page(){
  /*/global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates, $_wp_deprecated_widgets_callbacks;
  //global $wp_widget_factory,$wp_registered_widgets, $wpdb;
  global $wp_filter;
  //print_r($wp_filter);
  foreach($wp_filter as $k => $v){
    print $k."<br>";
  }
  // */
}
/* * * * * * * *
 * Page handling
 */
function ww_postspage_page_handler()
{
  include WW_PLUGIN_DIR.'/form-postspage.inc';
  // save Posts page widgets if posted
  if ($_GET['ww-postspage-action']){
    switch($_GET['ww-postspage-action']){
      case 'update':
        $defaults_array = ww_postspage_save_widgets($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-postspage');
  }
  else{
    ww_postspage_form();
  }
}
/*
 * Sidebar page handler
 */
function ww_sidebars_page_handler()
{
  // include the sidebars form
  include WW_PLUGIN_DIR.'/form-sidebars.inc';
  if($_GET['ww-sidebar-action']){
    switch($_GET['ww-sidebar-action']){
      case 'insert':
        $new_sidebar_id = ww_sidebar_insert($_POST);
        break;
      case 'delete':
        ww_sidebar_delete($_POST);
        break;
      case 'update':
        ww_sidebar_update($_POST);
        break;
      case 'sort':
        ww_sidebar_sort($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-sidebars');
  }
  // show sidebars page
  ww_sidebars_form();
}
/*
 * Handles creation of new cloned widgets, and displays clone new widget page
 */
function ww_clone_page_handler()
{
  include WW_PLUGIN_DIR.'/form-clone.inc';
  if($_GET['ww-clone-action']){
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
  include WW_PLUGIN_DIR.'/form-settings.inc';
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
    ww_settings_form();    
  }
}
/*
 * Produce the Default Widgets Page
 */
function ww_defaults_page_handler()
{
  include WW_PLUGIN_DIR."/form-defaults.inc";
  // save defaults if posted
  if ($_GET['ww-defaults-action']){
    switch($_GET['ww-defaults-action']){
      case 'update':
        $defaults_array = ww_save_default_widgets($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-defaults');
  }
  else{
    include WW_PLUGIN_DIR."/form-sidebars.inc";
    ww_theme_defaults_page();
  }
}
/* end page handling */

/*
 * Shortcode support for all widgets
 */
function ww_single_widget_shortcode($atts) {
  $short_array = shortcode_atts(array('id' => ''), $atts);
  extract($short_array);
  return ww_theme_single_widget(ww_get_single_widget($id));
}
add_shortcode('ww_widget','ww_single_widget_shortcode');
/*
 * Javascript drag and drop for sorting
 */ 
function ww_admin_js(){
  wp_enqueue_script('ww-admin-js',
                  plugins_url('/js/ww-admin.js', __FILE__ ),
                  array('jquery-ui-core', 'jquery-ui-sortable'),
                  false,
                  true);
}
/*
 * Javascript for drag and drop sidebar sorting
 */
function ww_sidebar_js(){
  wp_enqueue_script('ww-sidebar-js',
                    plugins_url('/js/ww-sidebars.js', __FILE__ ),
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
	print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/css/widget-wrangler.css" />';
}
add_action( 'admin_head', 'ww_admin_css');
/*
 * Make sure to show our plugin on the admin screen
 */
function ww_hec_show_dbx( $to_show )
{
  array_push( $to_show, 'widget-wrangler' );
  return $to_show;
}