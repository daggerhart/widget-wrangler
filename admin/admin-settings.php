<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_settings_admin_addon', 10, 2 );

//
function ww_settings_admin_addon($addons, $settings){
  $addons['Settings'] = WW_Settings_Admin::register($settings);
  return $addons;
}

/**
 * Class WW_Settings_Admin
 */
class WW_Settings_Admin extends WidgetWranglerAdminPage {
	public $urlbase = 'edit.php?post_type=widget&page=settings';

	/**
	 * @see WidgetWranglerAdminPage::title()
	 */
	function title() {
		return __('Settings');
	}

	/**
	 * @see WidgetWranglerAdminPage::menuTitle()
	 */
	function menuTitle() {
		return __('Settings');
	}

	/**
	 * @see WidgetWranglerAdminPage::slug()
	 */
	function slug() {
		return 'settings';
	}

	/**
	 * @see WidgetWranglerAdminPage::description()
	 */
	function description() {
		return array(
			__('Site settings for Widget Wrangler'),
		);
	}

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
	function actions() {
		return array(
			'save' => array( $this, 'actionSave' ),
			'reset' => array( $this, 'actionResetWidgets' ),
			'reset_settings' => array( $this, 'actionResetSettings' ),
			'theme_setup' => array( $this, 'actionThemeSetup' ),
		);
	}

	/**
	 * @see WidgetWranglerAdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
			wp_enqueue_style('ww-admin');
		}
	}

	/**
	 * @see \WidgetWranglerAdminPage::page()
	 */
	function page() {
		$sections = $this->processedSections();
		$form = array(
			'title' => '',
			'description' => '',
			'content' => '',
			'attributes' => array(
				'action' => $this->urlbase . '&ww_action=save&noheader=true',
			)
		);
		$form['content'] .= $this->templateSection($sections['settings']);
		$form['content'] .= $this->templateSection($sections['widget']);

		print WidgetWranglerAdminUi::form($form);
		print $this->templateSection($sections['tools']);
		//var_dump($this->settings);
    }

	/**
	 * Reset all pages to use the default widget settings
	 */
	function actionResetWidgets(){
		global $wpdb;
		$query = "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` = 'ww_post_widgets' OR `meta_key` = 'ww_post_preset_id'";
		$wpdb->query($query);
	}

	/**
	 * Reset Widget Wrangler settings values back to default.
	 */
	function actionResetSettings() {
		$settings = new WidgetWranglerSettings();
		$settings->values = $settings->default_settings;
		$settings->save();
    }

	/**
	 * Save the Widget Wrangler Settings page
	 */
	function actionSave() {
		$settings = new WidgetWranglerSettings();

		// loop through all settings_items looking for submitted values
		foreach ($this->processedFields() as $field_key => $field){
			// if these values were submitted with values, store them in the settings array
			foreach ($field['value_keys'] as $value_key){
				// default to empty
				$value = $field['empty_value'];

				if (isset($field['submitted_values'][$value_key])){
					$value = $field['submitted_values'][$value_key];
				}

				// override elements
				if ($field_key == "override_elements" && is_string($value)){
					$value = explode("\n", $value);
				}

				$settings->values[$value_key] = $value;
			}
		}

		$settings->save();
		$this->settings = $settings->values;
	}

	/**
	 * Empty wp sidebars,
	 *  - create a corral for each wp sidebar,
	 *  - place corral widget inside of each wp sidebar
	 */
	function actionThemeSetup(){
		global $wp_registered_sidebars;
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		$corrals = WidgetWranglerCorrals::all();

		// new options
		$new_sidebars_widgets = array(
			'wp_inactive_widgets' => $sidebars_widgets['wp_inactive_widgets'],
			'array_version' => $sidebars_widgets['array_version'],
		);
		$new_widget_ww_sidebar = array('_multiwidget' => 1);

		$i = 0;
		foreach ($wp_registered_sidebars as $sidebar_id => $sidebar_details){
			$corral_slug = WidgetWranglerUtils::makeSlug($sidebar_details['name']);

			// see if corral exists
			if (!isset($corrals[$corral_slug])){
				// make it
				$corrals[$corral_slug] = $sidebar_details['name'];
			}

			// assign a new corral widget instances
			$new_widget_ww_sidebar[$i] = array(
				'title' => '',
				'sidebar' => $corral_slug,
			);

			// assign new widget instance to sidebar
			$new_sidebars_widgets[$sidebar_id][$i] = 'widget-wrangler-sidebar-'.$i;

			$i++;
		}

		update_option('ww_sidebars', $corrals);
		update_option('sidebars_widgets', $new_sidebars_widgets);
		update_option('widget_widget-wrangler-sidebar', $new_widget_ww_sidebar);
	}

	/**
     * Settings sections.
     *
	 * @return array
	 */
	function sections(){
		$sections['settings'] = array(
			'title' => __('General', 'widgetwrangler'),
			'description' => __('Setup how Widget Wrangler works with other WordPress content.', 'widgetwrangler'),
		);
		$sections['widget'] = array(
			'title' => __('Post Type', 'widgetwrangler'),
			'description' => __('Post type settings control the widget post_type registered by this plugin.', 'widgetwrangler'),
		);
		$sections['tools'] = array(
			'title' => __('Tools', 'widgetwrangler'),
			'description' => __('Actions that will modify Widget Wrangler data.', 'widgetwrangler'),
		);

		return $sections;
	}

	/**
	 * @return array
	 */
	function processedSections() {
		$sections = $this->sections();

		// normalize items
		foreach ($sections as $section_key => $section){
			$sections[$section_key]['key'] = $section_key;
		}


		// get the setting items associated with this tab
		foreach ($this->processedFields() as $field_key => $field){
			if ( isset( $sections[ $field['section'] ] ) ) {
				$sections[ $field['section'] ]['items'][$field_key] = $field;
			}
		}

		return $sections;
	}

	/**
     * Settings field definitions.
     *
	 * @param array $fields
	 *
	 * @return array
	 */
	function fields($fields = array()){
		$fields['post_types']   = array(
			'title' => __('Post Types', 'widgetwrangler'),
			'description' => __('Select which post types can control widgets individually.', 'widgetwrangler'),
			'empty_value' => array(),
		);
		$fields['taxonomies']   = array(
			'title' => __('Taxonomies', 'widgetwrangler'),
			'description' => __('Select which taxonomies can control widgets individually.', 'widgetwrangler'),
			'empty_value' => $this->settings['taxonomies'],
			'execute_action' => array( $this, '_default_settings_execute_action' ),
		);
		$fields['theme_compat'] = array(
			'title' => __('Theme Compatibility', 'widgetwrangler'),
			'empty_value' => 0,
		);

		if (get_option('ww_previous_main_version', '')){
			$extra_desc = __("This version of Widget Wrangler has been upgraded from 1.x. If you have created templates with the previous version, you should leave this checked.", 'widgetwrangler');
		} else {
			$extra_desc = __("This version of Widget Wrangler was not upgraded from 1.x, you should not need this setting.", 'widgetwrangler');
		}

		$fields['legacy_template_suggestions'] = array(
			'title' => __('Legacy Template Suggestions', 'widgetwrangler'),
			'description' => $extra_desc,
			'empty_value' => 0,
		);

		// deprecated
		// only allow people currently using this feature to see it.
		if ( $this->settings['previously_pro'] ||  $this->settings['override_elements_enabled']){
			$fields['override_elements_enabled'] = array(
				'title' => __('Deprecated: Enable overriding widget HTML from the Post UI.', 'widgetwrangler'),
				'description' => __( 'This is a deprecated feature that is only here for legacy systems.' ).
				                 '<h3><strong>'.__('Not Recommended, this feature is not available for new installs of Widget Wrangler. ').'</strong></h3>
          <p>Instead, create a reusable template in your theme and set that template in the Custom Template section for a widget.</p>',
				'empty_value' => 0,
			);
			$fields['override_elements']         = array(
				'title' => __('Deprecated: HTML Override Elements', 'widgetwrangler'),
				'description' => __('Allowed elements for override a widget\'s html output.  Place one element per line.', 'widgetwrangler'),
				'empty_value' => $this->settings['override_elements'],
			);
		}

		// deprecated
		// only allow people currently using shortcode tinymce to see this old feature
		if ( $this->settings['previously_pro'] || $this->settings['shortcode_tinymce'] ) {
			$fields['shortcode_tinymce'] = array(
				'title' => __('Deprecated: tinyMCE Shortcode Button', 'widgetwrangler'),
				'description' => __( 'This is a deprecated feature that is only here for legacy systems.' ).
				                 '<h3><strong>'.__('Not Recommended, this feature is not available for new installs of Widget Wrangler. ').'</strong></h3>
                           <p>Instead, try this plugin to insert shortcodes into an editor.
                           <a href="https://wordpress.org/plugins/shortcode-ui/">Shortcode UI</a></p>',
				'empty_value' => 0,
			);
		}

		// widget settings
		$fields['capabilities']        = array(
			'title' => __('Capabilities', 'widgetwrangler'),
			'section' => 'widget',
			'value_keys' => array('capabilities', 'advanced_capability'),'execute_action'
		);
		$fields['exclude_from_search'] = array(
			'title' => __('Exclude from search', 'widgetwrangler'),
			'section' => 'widget',
			'empty_value' => 0,
		);

		// tools
		$fields['theme_setup']    = array(
			'title' => __('Setup Theme', 'widgetwrangler'),
			'section' => 'tools',
			'description' => __('If you click this button, Widget Wrangler will create a Corral for each WordPress sidebar you have, and place a Widget Wrangler Corral Widget into each WordPress Sidebar.', 'widgetwrangler'),
		);
		$fields['mass_reset']     = array(
			'title' =>__('Mass Reset', 'widgetwrangler'),
			'section' => 'tools',
			'description' => __('If you click this button, all pages will lose their assigned widget settings and will fall back on the default preset.', 'widgetwrangler'),
		);
		$fields['settings_reset'] = array(
			'title' => __('Reset settings to default', 'widgetwrangler'),
			'section' => 'tools',
			'description' => __('If you click this button, Widget Wrangler settings will be reset to their default state.  This will not affect Corral or Widget data.', 'widgetwrangler'),
		);

		$fields = apply_filters('ww_settings_form_items', $fields);
		return $fields;
	}


	/**
     * Gather, preprocess and process settings_form_items
	 * @return array
	 */
	function processedFields(){

		$fields = $this->fields();

		foreach ($fields as $field_key => $field){
			// default setting values
			$field = array_replace(array(
                'key' => $field_key,
                'section' => 'settings',
                'execute_key' => 'save',
                'value_keys' => array($field_key),
                'empty_value' => '',
            ), $field);


			// get submitted values
			if (is_array($field['value_keys'])){
				foreach ($field['value_keys'] as $key)
				{
					// get submitted values from $_POST if they exist
					if (isset($_POST['settings'][$key])){
						$field['submitted_values'][$key] = $_POST['settings'][$key];
					}

					// get default form values from settings array
					if (isset($this->settings[$key])){
						$field['form_values'][$key] = $this->settings[$key];
					}
					// fall back to empty value if missing (or disabled)
					else {
						$field['form_values'][$key] = $field['empty_value'];
					}
				}
			}

			$fields[$field_key] = $field;
		}

		return $fields;
	}

	/**
	 * @param $section
	 *
	 * @return string
	 */
	function templateSection($section) {
		ob_start();
		?>
        <div class="ww-columns">
            <div class="ww-column col-25">
                <h2><?php print $section['title']; ?></h2>
                <p><?php print $section['description']; ?></p>
            </div>
            <div class="ww-column col-75">
			<?php
                foreach( $section['items'] as $field) {
                    print $this->templateField($field);
                }
			?>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
     * Template a field and wrap it
     *
	 * @param $field
	 *
	 * @return string
	 */
	function templateField($field){
		$setting_key = $field['key'];

		ob_start();
		switch($field['key'])
		{
			case 'post_types':
				$post_types = get_post_types(array('public' => true, '_builtin' => false), 'names', 'and');
				$post_types['post'] = 'post';
				$post_types['page'] = 'page';
				unset($post_types['widget']);
				ksort($post_types);
				?>
                <div>
					<?php
					// loop through post types
					foreach ($post_types as $post_type )
					{
						$checked = (in_array($post_type, $field['form_values']['post_types'])) ? 'checked="checked"' : '';
						?>
                        <label><input type="checkbox" name="settings[<?php print $setting_key; ?>][<?php print $post_type; ?>]" value="<?php print $post_type; ?>" <?php print $checked; ?> /> - <?php print ucfirst($post_type); ?> </label>
						<?php
					}
					?>
                </div>
				<?php
				break;

			// Taxonomies
			case 'taxonomies':
				$taxonomies = get_taxonomies(array(), 'objects');

				if (!isset($field['form_values']['taxonomies'])){
					$field['form_values']['taxonomies'] = array();
				}
				?>
                <div>
					<?php
					// loop through taxonomies
					foreach ($taxonomies as $tax_name => $tax ){
						if ($tax->show_ui){
							// taken from get_edit_term_link
							// https://core.trac.wordpress.org/browser/tags/3.9.1/src/wp-includes/link-template.php#L894
							$args = array(
								'taxonomy' => $tax_name,
							);

							$edit_link = add_query_arg( $args, admin_url( 'edit-tags.php' ) );

							$checked = (in_array($tax_name, $field['form_values']['taxonomies'])) ? 'checked="checked"' : '';
							?>
                            <label><input type="checkbox" name="settings[<?php print $setting_key; ?>][<?php print $tax_name; ?>]" value="<?php print $tax_name; ?>" <?php print $checked; ?> /> - <?php print $tax->label; ?>
								<?php if ($checked) { ?>- <a href="<?php print $edit_link;?>#widget-wrangler">edit widgets</a><?php } ?>
                            </label>
							<?php
						}
					}
					?>
                </div>
				<?php
				break;

			case 'theme_compat':
				$checked = (!empty($field['form_values']['theme_compat'])) ? "checked='checked'" : "";
				?>
                <label>
                    <input name="settings[<?php print $setting_key; ?>]" type="checkbox" <?php print $checked; ?> value="1" /> - <?php _e('If checked, widgets will include WordPress sidebar settings for the registered sidebar.  ie, $before_widget, $before_title, $after_title, $after_widget. -- Additionally, enabling theme compatibility provides an administration page for managing the current theme\'s registered sidebar html.', 'widgetwrangler'); ?>
                </label>
				<?php
				break;

			case 'shortcode_tinymce':
				$checked = (!empty($field['form_values']['shortcode_tinymce'])) ? "checked='checked'" : "";
				?>
                <label>
                    <input name="settings[<?php print $setting_key; ?>]" type="checkbox" <?php print $checked; ?> value="1" /> - <?php _e('Enable tinyMCE shortcode button', 'widgetwrangler'); ?>
                </label>
				<?php
				break;

			case 'legacy_template_suggestions':
				$checked = (!empty($field['form_values']['legacy_template_suggestions'])) ? "checked='checked'" : "";
				?>
                <label>
                    <input name="settings[<?php print $setting_key; ?>]" type="checkbox" <?php print $checked; ?> value="1" /> - <?php _e('Enable template suggestions from Widget Wrangler 1.x', 'widgetwrangler'); ?>
                </label>
				<?php
				break;

			case 'override_elements_enabled':
				$checked = (!empty($field['form_values']['override_elements_enabled'])) ? "checked='checked'" : "";
				?>
                <label>
                    <input name="settings[<?php print $setting_key; ?>]" type="checkbox" <?php print $checked; ?> value="1" /> - <?php _e('Enable Overriding Widget HTML from the Post UI. Not recommended.', 'widgetwrangler'); ?>
                </label>
				<?php
				break;

			// widget settings
			case 'capabilities':
				$simple_checked = ( $field['form_values']['capabilities'] == 'simple') ? "checked" : "";
				$adv_checked = ( $field['form_values']['capabilities'] == 'advanced') ? "checked" : "";
				$advanced_capability = (!empty($field['form_values']['advanced_capability'])) ? $field['form_values']['advanced_capability'] : "";
				?>
                <p>
                    <label>
                        <input name="settings[capabilities]" type="radio" value="simple" <?php print $simple_checked; ?> />
                        <strong><?php _e('Simple', 'widgetwrangler'); ?></strong>:  <?php _e('Widgets can be Created and Edited by anyone who can edit Posts.  Anyone who can edit a Page can change the Widgets displayed on that Page.', 'widgetwrangler'); ?>
                    </label>
                </p>
                <hr />
                <p>
                    <label>
                        <input name="settings[capabilities]" type="radio" value="advanced" <?php print $adv_checked; ?> />
                        <strong><?php _e('Advanced', 'widgetwrangler'); ?></strong>:  <?php _e('Change the capability_type for this post_type.', 'widgetwrangler'); ?>
                    </label>
					<?php _e('This is primarily for incorporating third party permission systems. A simple use of this setting would be to change the Capability Type to \'page\'.  This would make it so that only users who can create and edit pages may create and edit widgets.', 'widgetwrangler'); ?>
                </p>
                <p>
                    <label><input name="settings[advanced_capability]" type="text" size="20" value="<?php print $advanced_capability; ?>"/> <?php _e('Capability Type', 'widgetwrangler'); ?></label>
                </p>
				<?php
				break;

			case 'exclude_from_search':
				$checked = (!empty($field['form_values']['exclude_from_search'])) ? "checked='checked'" : "";
				?>
                <label>
                    <input name="settings[<?php print $setting_key; ?>]" type="checkbox" <?php print $checked; ?> value="1" /> - <?php _e('If checked, widgets will be excluded from search results.', 'widgetwrangler'); ?>
                </label>
				<?php
				break;

			case 'widget_advanced':
				?>
                <p>
                    <label>
						<?php _e('Rewrite slug', 'widgetwrangler'); ?>: <input name="settings[rewrite_slug]" type="text" value="<?php print $field['form_values']['rewrite_slug']; ?>" />
                    </label>
                </p>
                <p>
                    <label>
						<?php _e('Query var', 'widgetwrangler'); ?>: <input name="settings[query_var]" type="text" value="<?php print $field['form_values']['query_var']; ?>" />
                    </label>
                </p>
				<?php
				break;

			// tools
			case 'theme_setup':
				?>
                <form action="<?php print $this->urlbase; ?>&ww_action=theme_setup&noheader=true" method="post">
                    <input class="button ww-setting-button-bad" type="submit" value="<?php _e('Setup Theme', 'widgetwrangler'); ?>" onclick="return confirm( '<?php _e('Are you sure you want to reset your WordPress sidebars and widget wrangler corrals?', 'widgetwrangler'); ?>' );" />
                </form>
				<?php
				break;

			case 'mass_reset':
				?>
                <form action="<?php print $this->urlbase; ?>&ww_action=reset&noheader=true" method="post">
                    <input class="button ww-setting-button-bad" type="submit" value="<?php _e('Reset All Widgets to Default', 'widgetwrangler'); ?>" onclick="return confirm( '<?php _e('Are you sure you want to Reset widget settings on all pages?', 'widgetwrangler'); ?>' );" />
                </form>
				<?php
				break;

			case 'settings_reset':
				?>
                <form action="<?php print $this->urlbase; ?>&ww_action=reset_settings&noheader=true" method="post">
                    <input class="button ww-setting-button-bad" type="submit" value="<?php _e('Reset Settings', 'widgetwrangler'); ?>" onclick="return confirm( '<?php _e('Are you sure you want to Reset Settings?', 'widgetwrangler'); ?>' );" />
                </form>
				<?php
				break;

			case 'override_elements':
				$rows = count($field['form_values']['override_elements']) + 1;
				if ($rows < 5){
					$rows = 5;
				}
				?>
                <textarea name="settings[override_elements]" cols="16" rows="<?php print $rows; ?>"><?php print implode("\n", $field['form_values']['override_elements']); ?></textarea>
				<?php
				break;
		}

		$field['form'] = ob_get_clean();

		return $this->templateFieldWrapper($field);
	}

	/**
     * Individual settings form item html template
     *
	 * @param $field
     *
     * @return string
	 */
	function templateFieldWrapper($field) {
	    ob_start();
		?>
        <div class="ww-box">
            <h3><?php print $field['title']; ?></h3>
            <div>
				<?php if (isset($field['description'])) { ?>
                    <p class="description"><?php print $field['description']; ?></p>
				<?php } ?>
                <div class='ww-setting-form-item'>
					<?php print $field['form']; ?>
                </div>
                <div class="ww-clear-gone">&nbsp;</div>
            </div>
        </div>
		<?php
        return ob_get_clean();
	}

}
