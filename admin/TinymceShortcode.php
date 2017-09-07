<?php
namespace WidgetWrangler;

/**
 * Class TinymceShortcode
 * @package WidgetWrangler
 */
class TinymceShortcode  {

	public $settings = array();

	function __construct($settings){
		$this->settings = $settings;
	}

	/**
	 * @param $settings
	 *
	 * @return \WidgetWrangler\TinymceShortcode
	 */
	public static function register( $settings ) {
		$plugin = new self($settings);

		if ( $settings['shortcode_tinymce'] && Utils::editingEnabledPostType() ) {
			add_action( 'admin_head', array( $plugin, 'admin_head' ) );
			add_filter( 'admin_body_class', array( $plugin, 'admin_body_class' ) );
		}

		return $plugin;
	}
	/**
	 * Implements action 'admin_body_class'
	 */
	function admin_body_class( $classes ){
		$classes.= ' widget-wrangler-tinymce-button ';
		return $classes;
	}

	/**
	 * Implements action 'admin_head'
	 */
	function admin_head(){
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ){
			return;
		}

		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', array($this, 'mce_external_plugins_widgets'));
			//you can use the filters mce_buttons_2, mce_buttons_3 and mce_buttons_4
			//to add your button to other toolbars of your tinymce
			add_filter('mce_buttons', array($this, 'mce_buttons_widgets_listbox'));
		}
	}

	/**
	 * @param $buttons
	 *
	 * @return mixed
	 */
	function mce_buttons_widgets_listbox($buttons){
		array_push($buttons, "ww_insert_widget");
		return $buttons;
	}

	/**
	 * @param $plugin_array
	 *
	 * @return mixed
	 */
	function mce_external_plugins_widgets($plugin_array){
		$plugin_array['ww_insert_widget'] = WW_PLUGIN_URL.'/admin/js/shortcode-tinymce.js';
		return $plugin_array;
	}
}
