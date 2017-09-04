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
		return __('Theme Sidebars');
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
	 * @see WidgetWranglerAdminPage::description()
	 */
	function description() {
		return array(
            __('Modify the sidebar output registered by the current theme.'),
		);
	}

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
	function actions() {
		return array(
			'update' => array( $this, 'actionUpdate' ),
		);
	}

	/**
	 * @see WidgetWranglerAdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
			wp_enqueue_style('ww-admin');
			wp_enqueue_script('ww-sidebars');
		}
	}

	/**
	 * @see WidgetWranglerAdminPage::menu()
	 */
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
    $altered_sidebars = WidgetWranglerUtils::alteredSidebars(true);

    ob_start();
    ?>
      <div>
      <?php
        foreach ($altered_sidebars as $slug => $sidebar)
        {
          $original = (isset($wp_registered_sidebars[$slug])) ? $wp_registered_sidebars[$slug] : FALSE;
          ?>
          <div class="ww-box">
            <h3><?php print $sidebar['name']; ?> <sup><em>(<?php print $slug; ?>)</em></sup></h3>
              <p class="description"><?php print $sidebar['description']; ?></p>
              <input type="hidden" name="ww-data[sidebars][<?php print $slug; ?>][slug]" value="<?php print $slug; ?>" />

            <div>
            <table class="form-table">
            <tbody>
              <tr>
                <th scope="row"><label><?php _e('Alter Sidebar', 'widgetwrangler'); ?></label></th>
                <td><input type="checkbox" name="ww-data[sidebars][<?php print $slug; ?>][ww_alter]" <?php if (isset($sidebar['ww_alter'])) { print "checked='checked'"; } ?> /></td>
              </tr>
              <tr>
                <th scope="row"><label><?php _e('Before Widget', 'widgetwrangler'); ?></label></th>
                <td><input type="text" class="regular-text code" name="ww-data[sidebars][<?php print $slug; ?>][before_widget]" value="<?php print htmlentities($sidebar['before_widget']); ?>" /></td>
              </tr>
              <tr>
                <th scope="row"><label><?php _e('Before Title', 'widgetwrangler'); ?></label></th>
                <td><input type="text" class="regular-text code" name="ww-data[sidebars][<?php print $slug; ?>][before_title]" value="<?php print htmlentities($sidebar['before_title']); ?>" /></td>
              </tr>
              <tr>
                <th scope="row"><label><?php _e('After Title', 'widgetwrangler'); ?></label></th>
                <td><input type="text" class="regular-text code" name="ww-data[sidebars][<?php print $slug; ?>][after_title]" value="<?php print htmlentities($sidebar['after_title']); ?>" /></td>
              </tr>
              <tr>
                <th scope="row"><label><?php _e('After Widget', 'widgetwrangler'); ?></label></th>
                <td><input type="text" class="regular-text code" name="ww-data[sidebars][<?php print $slug; ?>][after_widget]" value="<?php print htmlentities($sidebar['after_widget']); ?>" /></td>
              </tr>
              </tbody>
            </table>
            </div>

          <div class="ww-alter-sidebar-original">
            <a class="toggle"><?php _e('View Original', 'widgetwrangler'); ?></a>
            <div class="content"><pre><?php print htmlentities(print_r($original,1)); ?></pre></div>
          </div>
          </div>
          <?php
        }

    $form_content = ob_get_clean();
    
    
    $form = array(
      'title' => '',
      'description' => '',
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