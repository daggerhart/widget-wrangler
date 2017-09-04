<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_sidebars_admin_addon', 10, 2 );

//
function ww_sidebars_admin_addon($addons, $settings){
  $addons['Sidebars'] = WW_Sidebars_Admin::register($settings);
  return $addons;
}

/**
 * Class WW_Sidebars_Admin
 */
class WW_Sidebars_Admin extends WidgetWranglerAdminPage {

	/**
	 * @see WidgetWranglerAdminPage::title()
	 */
	function title() {
		return __('Modify the WordPress theme sidebar templates');
	}

	/**
	 * @see WidgetWranglerAdminPage::menuTitle()
	 */
	function menuTitle() {
		return __('Sidebars');
	}

	/**
	 * @see WidgetWranglerAdminPage::slug()
	 */
	function slug() {
		return 'sidebars';
	}

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
	function actions() {
		return array(
			'update' => array( $this, 'actionUpdate' ),
		);
	}

	function menu() {
		parent::menu();

		if ( !$this->settings['theme_compat']) {
			remove_submenu_page($this->parent(), $this->slug());
		}
	}

	/**
	 * @return array
	 */
	function actionUpdate() {
		if ( isset($_POST['ww-data']['sidebars'] ) ) {
			// clean up a little
			$sidebars = array();
			foreach ($_POST['ww-data']['sidebars'] as $slug => $sidebar){
				$sidebars[$slug] = array();
				foreach ($sidebar as $k => $v){
					$sidebars[$slug][$k] = str_replace("\\", '', $v);
				}
			}
			update_option('ww_alter_sidebars', $sidebars);
			return $this->result(__('Sidebars modified.'));
		}

		return $this->error(__('Something went wrong...'));
	}

	/**
	 *
	 */
  function page()
  {

    //delete_option('ww_alter_sidebars');
    global $wp_registered_sidebars;

	  add_action( "admin_head", 'WidgetWranglerAdminUi::css' );
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