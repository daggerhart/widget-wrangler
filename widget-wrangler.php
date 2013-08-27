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

if (!defined('WW_PLUGIN_NAME'))
    define('WW_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (!defined('WW_PLUGIN_DIR'))
    define('WW_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WW_PLUGIN_NAME);

if (!defined('WW_PLUGIN_URL'))
    define('WW_PLUGIN_URL', WP_PLUGIN_URL . '/' . WW_PLUGIN_NAME);

// template wrangler
if(!function_exists('theme')){
  include_once WW_PLUGIN_DIR.'/template-wrangler.inc';
}

// widget post type
$ww = NULL;

// details about the current widgets loaded
$ww_page = NULL;

// common functions for front and back ends
include_once WW_PLUGIN_DIR.'/common/functions.inc';

// new hooks provided to wordpress for widgetwrangler
include_once WW_PLUGIN_DIR.'/common/preset_types.inc';

// functions that control display of widgets and corrals
include_once WW_PLUGIN_DIR.'/common/display.inc';

// add the widget post type class and initiate it
include_once WW_PLUGIN_DIR.'/common/post_type-widget.inc';

// the widget for the post_type-widget allows the use widget wrangler widgets within normal WP sidebars
include_once WW_PLUGIN_DIR.'/common/post_type-widget.widget.inc';

// the corrals widget allows the use of corrals within normal WP sidebars
include_once WW_PLUGIN_DIR.'/common/corral.widget.inc';

/*
 * Make the loaded page's widgets into a global property
 */ 
function ww_set_page_widgets() {
	global $ww_page;
	//print '<pre>';
	$ww_page['widgets'] = ww_find_page_widgets();
	
	//global $wp_query;
	//print_r($ww_page);
	//print_r($wp_query);print '</pre>';
}
add_action( 'wp', 'ww_set_page_widgets');

/*
 * have to conditionally load a widget earlier than init
 */
//function ww_plugins_loaded(){}
//add_action( 'plugins_loaded', 'ww_plugins_loaded');

/*
 * Initialize the post type
 */
function widget_wrangler_init() {
  global $ww;
	
	// admin has way more code.  only load it when necessary
	if (is_admin()){
		include_once WW_PLUGIN_DIR.'/admin/post_type-widget.admin.inc';
		$ww = new WidgetWrangler_WidgetAdmin();
	}
	else {
		$ww = new WidgetWrangler_Widget();
	}

}
add_action( 'init', 'widget_wrangler_init');

/*
 * Admin initialize
 */
function ww_admin_init() {
  // include admin panel and helper functions such as sortable widgets
	include_once WW_PLUGIN_DIR.'/admin/admin.inc';
	include_once WW_PLUGIN_DIR.'/admin/clone.inc';
	include_once WW_PLUGIN_DIR.'/admin/settings.inc';
	include_once WW_PLUGIN_DIR.'/admin/sortable-widgets.inc';
  include_once WW_PLUGIN_DIR.'/admin/sortable-widgets.admin_panel.inc';
  include_once WW_PLUGIN_DIR.'/admin/upgrade.inc';
  
	// handle upgrades
  ww_check_version();
 
  // determine whether to display the admin panel
  // handles adding css and js
  ww_display_admin_panel();
}
add_action( 'admin_init', 'ww_admin_init' );

/*
 * All my hook_menu implementations
 */
function ww_menu() {
  $clone    = add_submenu_page( 'edit.php?post_type=widget', 'Copy Widget', 	 'Copy Widget',    'manage_options', 'ww-clone',    'ww_clone_page_handler'   );
  $corrals 	= add_submenu_page( 'edit.php?post_type=widget', 'Corrals (Sidebars)', 'Corrals (Sidebars)', 'manage_options', 'ww-corrals',  'ww_corrals_page_handler' );
  $presets  = add_submenu_page( 'edit.php?post_type=widget', 'Page Presets', 'Page Presets', 'manage_options', 'ww-presets',  'ww_presets_page_handler' );
  $settings = add_submenu_page( 'edit.php?post_type=widget', 'Settings',       'Settings',       'manage_options', 'ww-settings', 'ww_settings_page_handler');
  //$debug    = add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets',  'Debug',          'manage_options', 'ww-debug',    'ww_debug_page');
}
add_action( 'admin_menu', 'ww_menu');

/*
 * Make sure to show our plugin on the admin screen
 */
function ww_hec_show_dbx( $to_show ){
  array_push( $to_show, 'widget-wrangler' );
  return $to_show;
}

/*
 * Activation/install hooks
 */
function ww_plugin_activation(){
	include_once WW_PLUGIN_DIR.'/admin/install.inc'; 
	
	// create tables
	ww_post_widgets_table();
	ww_widget_data_table();
	ww_presets_table();
	ww_preset_term_relationships_table();
	// create default presets
	ww_default_presets();
	
	// set version
	update_option('ww_version', WW_VERSION);
}
register_activation_hook(__FILE__, 'ww_plugin_activation');
