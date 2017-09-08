<?php

namespace WidgetWrangler;

/**
 * Class Admin
 * @package WidgetWrangler
 */
class Admin {
	/**
	 * @var array
	 */
	public $settings = array();

	/**
	 * Admin constructor.
	 *
	 * @param $settings
	 */
	function __construct($settings){

		include_once WW_PLUGIN_DIR.'/admin/AdminMessages.php';
		include_once WW_PLUGIN_DIR.'/admin/Form.php';

		include_once WW_PLUGIN_DIR.'/admin/AdminPage.php';
		include_once WW_PLUGIN_DIR.'/admin/AdminPageClones.php';
		include_once WW_PLUGIN_DIR.'/admin/AdminPageCorrals.php';
		include_once WW_PLUGIN_DIR.'/admin/AdminPagePresets.php';
		include_once WW_PLUGIN_DIR.'/admin/AdminPageSettings.php';
		include_once WW_PLUGIN_DIR.'/admin/AdminPageSidebars.php';

		include_once WW_PLUGIN_DIR.'/admin/SortableWidgetsUi.php';
		include_once WW_PLUGIN_DIR.'/admin/TaxonomiesUi.php';
		include_once WW_PLUGIN_DIR.'/admin/TinymceShortcode.php';
		include_once WW_PLUGIN_DIR.'/admin/WidgetPostType.php';

		$this->settings = $settings;
	}

	/**
	 * @param $settings
	 *
	 * @return \WidgetWrangler\Admin
	 */
	public static function register($settings) {
		$plugin = new self($settings);

		add_action( 'wp_loaded', array( $plugin, 'loaded' ) );
		add_action( 'admin_init', array( $plugin, 'admin_init' ) );

		AdminPageClones::register($settings);
		AdminPageCorrals::register($settings);
		AdminPagePresets::register($settings);
		AdminPageSettings::register($settings);
		AdminPageSidebars::register($settings);
		TaxonomiesUi::register($settings);

		return $plugin;
	}

	/**
	 * WP hook wp_loaded
	 */
	function loaded() {
		TinymceShortcode::register($this->settings);
		WidgetPostType::register($this->settings);
    }

	/**
	 * WP hook admin_init
	 */
    function admin_init() {

        add_action( 'widget_wrangler_form_meta' , array( $this, 'ww_form_meta' ) );

        wp_register_style('ww-admin', plugins_url('css/admin.css', __FILE__), array(), WW_SCRIPT_VERSION );
        wp_register_style('ww-sortable-widgets', plugins_url('css/sortable-widgets.css', __FILE__),array(), WW_SCRIPT_VERSION );

	    wp_register_script('ww-admin',
		    plugins_url('js/admin.js', __FILE__),
		    array('jquery', 'jquery-ui-sortable', 'wp-util'),
		    WW_SCRIPT_VERSION,
		    true
	    );
        wp_register_script('ww-sortable-widgets',
            plugins_url('js/sortable-widgets.js', __FILE__),
            array('jquery-ui-core', 'jquery-ui-sortable', 'wp-util'),
            WW_SCRIPT_VERSION,
            true );
        wp_register_script('ww-widget-posttype',
            plugins_url('js/widget-posttype.js', __FILE__),
            array('jquery'),
            WW_SCRIPT_VERSION,
            true
        );

        // Add metabox to enabled post_types
        if (!empty($this->settings['post_types'])){
            foreach($this->settings['post_types'] as $enabled_post_type){
                add_meta_box('ww_admin_meta_box',
                    '<img src="'.WW_PLUGIN_URL.'/admin/images/lasso-small-black.png" />' . __('Widget Wrangler'),
                    'WW_Admin_Sortable::postMetaBox',
                    $enabled_post_type,
                    'normal',
                    'high');

                // Add some CSS to the admin header on the widget wrangler pages, and edit pages
                if (Utils::editingEnabledPostType()){
                    SortableWidgetsUi::js();
                }

                if (isset($_POST['post_type']) && $_POST['post_type'] == $enabled_post_type){
                    add_action( 'save_post', array( $this, '_save_post_widgets' ) );
                }
            }
        }
    }

	/* -------------------------------------------- Sortable Widgets --------------------------*/



	//
  function ww_form_meta(){
    // add the post_id hidden input when editing an enabled post
    // for ajax handling
    if (Utils::editingEnabledPostType())
    { ?>
      <input value="<?php print get_the_ID(); ?>" type="hidden" id="ww_ajax_context_id" />
      <?php
    }
  }

  /*
   * Hook into saving a page
   * Save the post meta for this post
   */
  function _save_post_widgets($post_id)
  {
    // skip quick edit
    if (isset($_REQUEST['_inline_edit'])) { return $post_id; }
    
    // don't know what is being saved if not a post_type, so we do nothing
    if (!isset($_POST['post_type'])){
      return $post_id;
    }
    
    // Ensure this is an enabled post_type and user can edit it
    $settings = $this->settings;  
    if (!in_array($_POST['post_type'], $settings['post_types']) || !current_user_can('edit_post', $post_id)){
      return $post_id;
    }
    
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( ! isset( $_POST['ww-sortable-list-box'] ) || ! wp_verify_nonce( $_POST['ww-sortable-list-box'], 'widget-wrangler-sortable-list-box-save' ) ) {
      return $post_id;
    }
    
    // If this is an auto save routine, our form has not been submitted, so we dont want to do anything
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
      return $post_id;
    }

    // OK, we're authenticated:
    // we need to find and save the data
    $widgets = Utils::serializeWidgets($_POST['ww-data']['widgets']);

    // allow other plugins to modify the widgets or save other things
    $widgets = apply_filters('widget_wrangler_save_widgets_alter', $widgets);

	  if ($widgets){
		  update_post_meta( $post_id, 'ww_post_widgets', $widgets);
	  }
    
    $new_preset_id = Presets::$new_preset_id;
    
    if ($new_preset_id !== FALSE){
      update_post_meta( $post_id, 'ww_post_preset_id', (int) $new_preset_id);
    }
  }

}
