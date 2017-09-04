<?php
/*
Plugin Name: Widget Wrangler
Plugin URI: http://www.wranglerplugins.com
Description: Widget Wrangler gives the WordPress admin a clean interface for managing widgets on a page by page basis. It also provides widgets as a post type, the ability to clone existing WordPress widgets, and granular control over widget templates.
Author: Jonathan Daggerhart
Version: 2.2.4
Author URI: http://daggerhart.com
Text Domain: widgetwrangler
Domain Path: /languages
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

// versioning for now
define('WW_VERSION', '2.2.4');
define('WW_SCRIPT_VERSION', '2.2.4');

define('WW_PLUGIN_FILE', __FILE__);
define('WW_PLUGIN_DIR', dirname(WW_PLUGIN_FILE));
define('WW_PLUGIN_URL', plugin_dir_url(WW_PLUGIN_FILE));

// leave this in the global space so anything can use it
$widget_wrangler = Widget_Wrangler::register();

/*
 * The Widget_Wrangler object is the master of all things widget wrangler.
 *   It handles database interactions, loading addons, and execution of output.
 *   Does NOT build the output.  See Widget_Wrangler_Display (common/display.php)
 *   
 * New WordPress filters included
 *  - Widget_Wrangler_Addons
 *  - widget_wrangler_find_all_page_widgets
 *  - widget-wrangler-set-page-context
 */
class Widget_Wrangler {
  // addons
  var $addons = array();
    
  // context for current page being viewed on front end
  var $page_context = NULL;

  // theme compatiblity is a global option
  var $theme_compat = 0;
  
  // ww
  var $settings = array();
  var $default_settings = array(
        'exclude_from_search' => 1,
        'theme_compat' => 1,
        'capabilities' => 'simple',
        'advanced_capability' => '',
        'post_types' => array(
          'page' => 'page',
          'post' => 'post',
        ),
        'taxonomies' => array(),
        'override_elements' => array(
          'div', 'h2', 'h3', 'aside', 'strong', 'span',
        ),
        'legacy_template_suggestions' => 0,

        // begin the weaning of features that will be removed
        // dead end features:  override html elements, shortcode tinymce
        'previously_pro' => 0,
        'override_elements_enabled' => 0,
        'shortcode_tinymce' => 0,
      );

  /**
   * Construct the widget wrangler object.
   *  - add dependencies
   */
  function __construct(){
	  // core
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerSettings.php';
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerDB.php';
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerExtras.php';
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerCorrals.php';
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerWidgets.php';
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerUpdate.php';
	  include_once WW_PLUGIN_DIR.'/common/WidgetWranglerUtils.php';

	  include_once WW_PLUGIN_DIR.'/common/template-wrangler.inc';
	  include_once WW_PLUGIN_DIR.'/common/presets.php';
	  include_once WW_PLUGIN_DIR.'/common/display.php';
	  include_once WW_PLUGIN_DIR.'/common/wp-posttype-widget.php';

	  // addons
	  include_once WW_PLUGIN_DIR.'/common/taxonomies.php';
  }

  /**
   * Instantiate and register WP hooks
   *
   * @return \Widget_Wrangler
   */
  public static function register() {
    $plugin = new self();
    
	add_action('wp_loaded', array($plugin, 'wp_loaded'), 999);

	// early wp hooks
	register_activation_hook(WW_PLUGIN_FILE, 'WidgetWranglerUpdate::install');
	add_action( 'widgets_init', array( $plugin, 'wp_widgets_init' ) );

	// let all plugins load before gathering addons
	add_action( 'plugins_loaded' , array( $plugin, 'wp_plugins_loaded' ) );

	// singular page widget detection
	add_filter( 'widget_wrangler_find_all_page_widgets', array( $plugin, '_find_singular_page_widgets' ), 10 );


	return $plugin;
  }

  /*
   * WordPress hook widgets_init
   * 
   *  - Register the corral and widget WP_Widget(s) 
   */
  function wp_widgets_init(){
    include_once WW_PLUGIN_DIR.'/common/wp-widget-ww-corral.php';
    include_once WW_PLUGIN_DIR.'/common/wp-widget-ww-widget.php';
    register_widget( 'WidgetWrangler_Corral_Widget' );
    register_widget( 'WidgetWrangler_Widget_Widget' );
  }

  /*
   * WordPress hook plugins_loaded
   * 
   *  - load all Widget Wrangler Addons
   *  - load core WW display and preset functionality
   */
  function wp_plugins_loaded(){
    load_plugin_textdomain( 'widgetwrangler', FALSE, basename( WW_PLUGIN_DIR ) . '/languages/' );


	// initialize core
	  $this->settings = new WidgetWranglerSettings();
    $this->addons = apply_filters( 'Widget_Wrangler_Addons', array() );
    $this->display = Widget_Wrangler_Display::register( $this->settings->values );
    $this->presets = WW_Presets::register();

    // init the post type
    WW_Widget_PostType::register($this->settings->values);

    // initialize admin stuff
    if (is_admin()){
      include_once WW_PLUGIN_DIR.'/admin/widget-wrangler-admin.php';
      $this->admin = Widget_Wrangler_Admin::register($this->settings->values);

      // make sure we're updated
      WidgetWranglerUpdate::update();
    }
  }

	/**
	 * WordPress wp_loaded hook
	 *
	 *  - Handle altered sidebar definitions
	 */
	function wp_loaded(){
		if ( !is_admin()) {
			global $wp_registered_sidebars;
			$wp_registered_sidebars = WidgetWranglerUtils::alteredSidebars();
		}
	}
  
  /*
   * Detect if the current page being viewed is wrangling own widgets
   *
   * @param (array) - $widgets found by the system
   *
   * @return (array) - widgets array
   */
  function _find_singular_page_widgets($widgets){
    // don't replace any widgets already found
    if (is_null($widgets) && (is_singular() || is_admin())) {
      global $post;
      // single page widgets wrangling on their own
      if (isset($post) && $widgets_string = get_post_meta($post->ID,'ww_post_widgets', TRUE)) {
        $widgets = unserialize( $widgets_string );
      }
    }
    return $widgets;
  }

	/**
	 * Returns all published widgets
	 * @deprecated
	 * @see \WidgetWranglerWidgets::all()
	 */
	function get_all_widgets($post_status = array('publish')) {
		return WidgetWranglerWidgets::all($post_status);
	}

	/**
	 * Retrieve and return a single widget by its ID
	 * @deprecated
	 * @see WidgetWranglerWidgets::get()
	 */
	function get_single_widget($post_id, $widget_status = false) {
		return WidgetWranglerWidgets::get($post_id, $widget_status);
	}
}
