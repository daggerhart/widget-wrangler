<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_sidebars_admin_addon', 10, 2 );

//
function ww_sidebars_admin_addon($addons, $settings){
  $addons['Sidebars'] = WW_Sidebars_Admin::register($settings);
  return $addons;
}

/*
 *
 */
class WW_Sidebars_Admin  {
  public $page_hook;


	public $settings = array();

	function __construct($settings){
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @param $settings
	 *
	 * @return \WW_Sidebars_Admin
	 */
	public static function register( $settings ) {
		$plugin = new self($settings);

		add_action( 'admin_menu', array( $plugin, 'wp_admin_menu' ) );

		return $plugin;
	}

  /**
   * Implements action 'admin_menu'
   */
  function wp_admin_menu(){
    if ($this->settings['theme_compat']) {
      $page_title = 'Sidebars';

      $this->page_hook = add_submenu_page(Widget_Wrangler_Admin::$page_slug, $page_title, $page_title, Widget_Wrangler_Admin::$capability, 'sidebars', array( $this, '_menu_router' ));
      add_action( "admin_head", 'WidgetWranglerAdminUi::css' );
    }
  }

  /*
   * Handles settings pages
   *   - all settings pages submit to this execute_action
   */
  function _menu_router(){
    if (isset($_GET['ww_action'])){
      switch($_GET['ww_action']){
        case 'update':
          if (isset($_POST['ww-data']['sidebars'])) {
            // clean up a little
            $sidebars = array();
            foreach ($_POST['ww-data']['sidebars'] as $slug => $sidebar){
              $sidebars[$slug] = array();
              foreach ($sidebar as $k => $v){
                $sidebars[$slug][$k] = str_replace("\\", '', $v);
              }
            }
            update_option('ww_alter_sidebars', $sidebars);
          }
          break;
      }
      
      wp_redirect($_SERVER['HTTP_REFERER']);
      exit;
    }
    else {
      $this->_manage_sidebars_form();
    }
  }
  
  /*
   * Build the form 
   */
  function _manage_sidebars_form()
  {
    //delete_option('ww_alter_sidebars');
    global $wp_registered_sidebars;
    
    $altered_sidebars = WidgetWranglerUtils::alteredSidebars(true);

    ob_start();
    ?>
      <div id='ww-alter-sidebar-page'>
      <?php
        foreach ($altered_sidebars as $slug => $sidebar)
        {
          $original = (isset($wp_registered_sidebars[$slug])) ? $wp_registered_sidebars[$slug] : FALSE;
          ?>
          <div class="postbox">
            <h2 class="ww-setting-title"><?php print $sidebar['name']; ?> <sup><em>(<?php print $slug; ?>)</em></sup></h2>
            <div class="ww-setting-content">
              <p class="description"><?php print $sidebar['description']; ?></p>
              <hr />
              <input type="hidden" name="ww-data[sidebars][<?php print $slug; ?>][slug]" value="<?php print $slug; ?>" />
              <div class="ww-alter-sidebar-field">
                <label><?php _e('Alter Sidebar', 'widgetwrangler'); ?></label>
                <input type="checkbox" name="ww-data[sidebars][<?php print $slug; ?>][ww_alter]" <?php if (isset($sidebar['ww_alter'])) { print "checked='checked'"; } ?> />
              </div>
              <div class="ww-alter-sidebar-field">
                <label><?php _e('Before Widget', 'widgetwrangler'); ?></label>
                <input type="text" name="ww-data[sidebars][<?php print $slug; ?>][before_widget]" value="<?php print htmlentities($sidebar['before_widget']); ?>" />
              </div>
              <div class="ww-alter-sidebar-field">
                <label><?php _e('Before Title', 'widgetwrangler'); ?></label>
                <input type="text" name="ww-data[sidebars][<?php print $slug; ?>][before_title]" value="<?php print htmlentities($sidebar['before_title']); ?>" />
              </div>
              <div class="ww-alter-sidebar-field">
                <label><?php _e('After Title', 'widgetwrangler'); ?></label>
                <input type="text" name="ww-data[sidebars][<?php print $slug; ?>][after_title]" value="<?php print htmlentities($sidebar['after_title']); ?>" />
              </div>
              <div class="ww-alter-sidebar-field">
                <label><?php _e('After Widget', 'widgetwrangler'); ?></label>
                <input type="text" name="ww-data[sidebars][<?php print $slug; ?>][after_widget]" value="<?php print htmlentities($sidebar['after_widget']); ?>" />
              </div>
              
              <div class="ww-alter-sidebar-original">
                <a class="toggle"><?php _e('View Original', 'widgetwrangler'); ?></a>
                <div class="content"><pre><?php print htmlentities(print_r($original,1)); ?></pre></div>
              </div>
            </div>
          </div>
          <?php
        }
      ?>
      <script type="text/javascript">
        (function ($) {
          $(document).ready(function(){
            $('.ww-alter-sidebar-original .content').hide();
            $('.ww-alter-sidebar-original .toggle').click(function(){
              $(this).next('.content').toggle();
            });
          });
        })(jQuery);
      </script>
    <?php
    $form_content = ob_get_clean();
    
    
    $form = array(
      'title' => __('Wordpress Sidebars', 'widgetwrangler'),
      'description' => __('Alter existing sidebars registered by the current theme.', 'widgetwrangler'),
      'content' => $form_content,
      'attributes' => array(
        'action' => 'edit.php?post_type=widget&page=sidebars&ww_action=update&noheader=true',
        ),
      'submit_button' => array(
          'attributes' => array(
            'value' => __('Update Sidebars', 'widgetwrangler'),
          ),
        ),
      );
    print WidgetWranglerAdminUi::form($form);
  }
}