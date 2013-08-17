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
		
// common functions for front and back ends
include_once WW_PLUGIN_DIR.'/common.inc';

// functions that control display logic of widgets and output
include_once WW_PLUGIN_DIR.'/display.inc';

// add the widget post type class and initiate it
include_once WW_PLUGIN_DIR.'/post_type-widget.inc';

// the widget for the post_type-widget allows the use widget wrangler widgets within normal WP sidebars
include_once WW_PLUGIN_DIR.'/post_type-widget.widget.inc';

// the corrals widget allows the use of corrals within normal WP sidebars
include_once WW_PLUGIN_DIR.'/corral.widget.inc';

$ww = NULL;
$ww_page_widgets = NULL;

/*
 * Initialize the post type
 */
function Widget_Wrangler_Init() {
  global $ww;
	// admin has way more code.  only load it when necessary
	if (is_admin()){
		include_once WW_PLUGIN_DIR.'/admin/post_type-widget.admin.inc';
		$ww = new Widget_Wrangler_Admin();
	}
	else {
		$ww = new Widget_Wrangler();
	}
}
add_action( 'init', 'Widget_Wrangler_Init');

/*
 * Admin initialize
 */
function ww_admin_init() {
	
  // include admin panel and helper functions such as sortable widgets
	include_once WW_PLUGIN_DIR.'/admin/admin.inc';
	include_once WW_PLUGIN_DIR.'/admin/settings.inc';
  include_once WW_PLUGIN_DIR.'/admin/single-post-widgets.admin_panel.inc';
	include_once WW_PLUGIN_DIR.'/admin/sortable-widgets.inc';
  include_once WW_PLUGIN_DIR.'/admin/upgrade.inc';
  
	// handle upgrades
  ww_check_version();
 
  // determine whether to display the admin panel
  // handles adding some css and the js
  ww_display_admin_panel();

  // add admin css
  add_action( 'admin_head', 'ww_admin_css');
	add_action( 'save_post', 'ww_save_post' );
}
add_action( 'admin_init', 'ww_admin_init' );

/*
 * All my hook_menu implementations
 */
function ww_menu() {
  $clone    = add_submenu_page( 'edit.php?post_type=widget', 'Copy Widget', 	 'Copy Widget',    'manage_options', 'ww-clone',    'ww_clone_page_handler'   );
  $corrals 	= add_submenu_page( 'edit.php?post_type=widget', 'Corrals (Sidebars)', 'Corrals (Sidebars)', 'manage_options', 'ww-corrals',  'ww_corrals_page_handler' );
  $presets  = add_submenu_page( 'edit.php?post_type=widget', 'Widget Presets', 'Widget Presets', 'manage_options', 'ww-presets',  'ww_presets_page_handler' );
  $settings = add_submenu_page( 'edit.php?post_type=widget', 'Settings',       'Settings',       'manage_options', 'ww-settings', 'ww_settings_page_handler');
  //$debug    = add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets',  'Debug',          'manage_options', 'ww-debug',    'ww_debug_page');
  add_action( "admin_print_scripts-$corrals", 'ww_corral_js' );
}
add_action( 'admin_menu', 'ww_menu');

/*
 * Shortcode support for all widgets
 *
 * @param array $atts Attributes within the executed shortcode.  'id' => widget->ID
 * @return string HTML for a single themed widget
 */
function ww_single_widget_shortcode($atts) {
  $short_array = shortcode_atts(array('id' => ''), $atts);
  extract($short_array);
  return ww_theme_single_widget(ww_get_single_widget($id));
}
add_shortcode('ww_widget','ww_single_widget_shortcode');

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
