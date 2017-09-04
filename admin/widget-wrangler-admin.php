<?php
/*
 * Widget Wrangler Admin Panel and related functions
 *

filters
  - Widget_Wrangler_Admin_Addons

actions
  - widget_wrangler_form_meta  (doesn't get replaced on ajax)
  - widget_wrangler_form_top
  - widget_wrangler_form_bottom
  
*/

/**
 * Class Widget_Wrangler_Admin
 */
class Widget_Wrangler_Admin {
	public $addons = array();
	public static $page_slug = 'edit.php?post_type=widget';
	public static $capability = 'manage_options';

	public $settings = array();

	function __construct($settings){

		include_once WW_PLUGIN_DIR.'/admin/WidgetWranglerAdminMessages.php';
		include_once WW_PLUGIN_DIR.'/admin/WidgetWranglerAdminUi.php';
		include_once WW_PLUGIN_DIR.'/admin/WidgetWranglerAdminPage.php';

		include_once WW_PLUGIN_DIR.'/admin/admin-clone.php';
		include_once WW_PLUGIN_DIR.'/admin/admin-presets.php';
		include_once WW_PLUGIN_DIR.'/admin/admin-corrals.php';
		include_once WW_PLUGIN_DIR.'/admin/admin-sidebars.php';
		include_once WW_PLUGIN_DIR.'/admin/admin-settings.php';
		include_once WW_PLUGIN_DIR.'/admin/admin-shortcode-tinymce.php';
		include_once WW_PLUGIN_DIR.'/admin/admin-taxonomies.php';
		include_once WW_PLUGIN_DIR.'/admin/sortable.php';

		$this->settings = $settings;

		// get all our admin addons
		$this->addons = apply_filters( 'Widget_Wrangler_Admin_Addons', array(), $this->settings );
	}

	public static function register($settings) {
		$plugin = new self($settings);

		add_action( 'admin_init', array( $plugin, 'wp_admin_init' ) );

		return $plugin;
	}


    // WordPress hook 'admin_init'
    function wp_admin_init() {
        add_action( 'widget_wrangler_form_meta' , array( $this, 'ww_form_meta' ) );

        // Add metabox to enabled post_types
        if (!empty($this->settings['post_types'])){
            foreach($this->settings['post_types'] as $enabled_post_type){
                add_meta_box('ww_admin_meta_box',
                    '<img src="'.WW_PLUGIN_URL.'/admin/images/lasso-small-black.png" />' . __('Widget Wrangler'),
                    'WW_Admin_Sortable::metaBox',
                    $enabled_post_type,
                    'normal',
                    'high');

                // Add some CSS to the admin header on the widget wrangler pages, and edit pages
                if (WidgetWranglerUtils::editingEnabledPostType()){
                    WW_Admin_Sortable::init();
                }

                if (isset($_POST['post_type']) && $_POST['post_type'] == $enabled_post_type){
                    add_action( 'save_post', array( $this, '_save_post_widgets' ) );   // admin/sortable-widgets-metabox.inc
                }
            }
        }
    }

	/* -------------------------------------------- Sortable Widgets --------------------------*/



	//
  function ww_form_meta(){
    // add the post_id hidden input when editing an enabled post
    // for ajax handling
    if (WidgetWranglerUtils::editingEnabledPostType())
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
    if (isset($_REQUEST['_inline_edit'])) { return; }
    
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
    $widgets = WidgetWranglerUtils::serializeWidgets($_POST['ww-data']['widgets']);

    // allow other plugins to modify the widgets or save other things
    $widgets = apply_filters('widget_wrangler_save_widgets_alter', $widgets);

	  if ($widgets){
		  update_post_meta( $post_id, 'ww_post_widgets', $widgets);
	  }
    
    $new_preset_id = WW_Presets::$new_preset_id;
    
    if ($new_preset_id !== FALSE){
      update_post_meta( $post_id, 'ww_post_preset_id', (int) $new_preset_id);
    }
  }

}
