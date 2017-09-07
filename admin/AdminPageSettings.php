<?php
namespace WidgetWrangler;

/**
 * Class AdminPageSettings
 * @package WidgetWrangler
 */
class AdminPageSettings extends AdminPage {

	/**
	 * @see AdminPage::title()
	 */
	function title() {
		return __('Settings');
	}

	/**
	 * @see AdminPage::menuTitle()
	 */
	function menuTitle() {
		return __('Settings');
	}

	/**
	 * @see AdminPage::slug()
	 */
	function slug() {
		return 'settings';
	}

	/**
	 * @see AdminPage::description()
	 */
	function description() {
		return array(
			__('Site settings for Widget Wrangler'),
		);
	}

	/**
	 * @see AdminPage::actions()
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
	 * @see AdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
			wp_enqueue_style('ww-admin');
		}
	}

	/**
	 * @see \AdminPage::page()
	 */
	function page() {
		$settings_form = new Form(array(
			'action' => $this->pageUrl().'&ww_action=save&noheader=true',
			'field_prefix' => 'settings',
			'style' => 'box',
			'fields' => array(
				'submit' =>array(
					'type' => 'submit',
					'value' => __('Save settings'),
                    'class' => 'button button-large button-primary',
				),
				'post_types' => array(
					'type' => 'checkboxes',
					'title' => __('Post Types'),
					'description' => __('Select which post types can control widgets individually.'),
					'options' => $this->postTypeOptions(),
					'value' => $this->settings['post_types'],
				),
				'taxonomies' => array(
					'type' => 'checkboxes',
					'title' => __('Taxonomies'),
					'description' => __('Select which taxonomies can control widgets individually.'),
					'options' => $this->taxonomyOptions(),
					'value' => $this->settings['taxonomies'],
				),
				'theme_compat' => array(
					'type' => 'checkbox',
					'title' => __('Theme Compatibility'),
					'help' => __('If checked, widgets will include WordPress sidebar settings for the registered sidebar.  ie, $before_widget, $before_title, $after_title, $after_widget. -- Additionally, enabling theme compatibility provides an administration page for managing the current theme\'s registered sidebar html.'),
					'value' => $this->settings['theme_compat'],
				),
				// Widget Post Type
				'exclude_from_search' => array(
					'type' => 'checkbox',
					'title' => __('Exclude from search'),
					'help' => __('If checked, widgets will be excluded from search results.'),
					'value' => $this->settings['exclude_from_search'],
				),
				'advanced_capability' => array(
					'type' => 'text',
					'title' => __('Widget post type capability'),
					'description' => __('This is primarily for incorporating third party permission systems.'),
					'help' => __('Leave blank to use default post capabilities.'),
					'value' => $this->settings['advanced_capability'],
				),
				// Old & Deprecated stuff
				'legacy_template_suggestions' => array(
					'type' => 'checkbox',
					'title' => __('Legacy Template Suggestions'),
					'help' => __('This version of Widget Wrangler has been upgraded from 1.x. If you have created templates with the previous version, you should leave this checked.'),
					'value' => $this->settings['legacy_template_suggestions'],
					'access' => get_option('ww_previous_main_version', false),
				),
				'override_elements_enabled' => array(
					'type' => 'checkbox',
					'title' => __('Deprecated: Enable overriding widget HTML from the Post UI.'),
					'help' => __('This is a deprecated feature that is only here for legacy systems.'),
					'value' => $this->settings['override_elements_enabled'],
					'access' => ( $this->settings['override_elements_enabled'] ),
				),
				'override_elements' => array(
					'type' => 'textarea',
					'title' => __('Deprecated: HTML Override Elements'),
					'help' => __('Allowed elements for override a widget\'s html output.  Place one element per line.'),
					'value' => $this->settings['override_elements'],
					'access' => ( $this->settings['override_elements_enabled'] ),
				),
				'shortcode_tinymce' => array(
					'type' => 'checkbox',
					'title' => __('Deprecated: tinyMCE Shortcode Button'),
					'help' => __('Do not use this feature. Instead, try this plugin: ').'<a href="https://wordpress.org/plugins/shortcode-ui/">Shortcode UI</a>',
					'value' => $this->settings['shortcode_tinymce'],
					'access' => ( $this->settings['shortcode_tinymce'] ),
				),
			)
		));

		$setup_theme_form = new Form(array(
            'style' => 'box',
            'action' => $this->pageUrl().'&ww_action=theme_setup&noheader=true',
            'fields' => array(
                'submit' => array(
                    'type' => 'submit',
                    'title' => __('Setup Theme'),
                    'description' => __('If you click this button, Widget Wrangler will create a Corral for each WordPress sidebar you have, and place a Widget Wrangler Corral Widget into each WordPress Sidebar.'),
                    'value' => __('Reset All Widgets to Default'),
                    'class' => 'button disabled',
                    'attributes' => array(
                        'data-confirm' => __('Are you sure?'),
                    ),
                )
            )
        ));
		$reset_widgets_form = new Form(array(
            'style' => 'box',
            'action' => $this->pageUrl().'&ww_action=reset&noheader=true',
            'fields' => array(
                'submit' => array(
                    'type' => 'submit',
                    'title' => __('Mass Reset'),
                    'description' => __('If you click this button, all pages will lose their assigned widget settings and will fall back on the default preset.'),
                    'value' => __('Reset All Widgets to Default'),
                    'class' => 'button disabled',
                    'attributes' => array(
	                    'data-confirm' => __('Are you sure?'),
                    ),
                )
            )
        ));
		$reset_settings_form = new Form(array(
            'style' => 'box',
            'action' => $this->pageUrl().'&ww_action=reset_settings&noheader=true',
            'fields' => array(
                'submit' => array(
                    'type' => 'submit',
                    'title' => __('Reset Settings'),
                    'description' => __('If you click this button, Widget Wrangler settings will be reset to their default state.  This will not affect Corral or Widget data.'),
                    'value' => __('Reset Settings'),
                    'class' => 'button disabled',
                    'attributes' => array(
	                    'data-confirm' => __('Are you sure?'),
                    ),
                )
            )
        ));
        ?>
        <div class="ww-columns">
            <div class="ww-column col-25">
                <h2><?php _e('General'); ?></h2>
                <p><?php _e('Setup how Widget Wrangler works with other WordPress content.'); ?></p>
            </div>
            <div class="ww-column col-75">
				<?php print $settings_form->render(); ?>
            </div>
        </div>
        <div class="ww-columns">
            <div class="ww-column col-25">
                <h2><?php _e('Tools'); ?></h2>
                <p><?php _e('Actions that will modify Widget Wrangler data.'); ?></p>
            </div>
            <div class="ww-column col-75">
				<?php
                    print $setup_theme_form->render();
                    print $reset_widgets_form->render();
                    print $reset_settings_form->render();
				?>
            </div>
        </div>
        <?php
    }

	/**
	 * Reset all pages to use the default widget settings
	 */
	function actionResetWidgets(){
		global $wpdb;
		$query = "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` = 'ww_post_widgets' OR `meta_key` = 'ww_post_preset_id'";
		$wpdb->query($query);

		return $this->result( __('Widget data on all posts have been deleted.') );
	}

	/**
	 * Reset Widget Wrangler settings values back to default.
	 */
	function actionResetSettings() {
		$settings = new Settings();
		$settings->values = $settings->default_settings;
		$settings->save();
		$this->settings = $settings->values;

		return $this->result( __('Settings have been reset to default.') );
    }

	/**
	 * Save the Widget Wrangler Settings page
	 */
	function actionSave() {
	    if ( empty( $_POST['settings'] ) ) {
	        return $this->error();
        }

		$settings = new Settings();

		// copy submitted fields to the settings object
	    foreach ($settings->values as $key => $value) {
	        if ( isset( $_POST['settings'][ $key ] ) ) {
                $settings->values[ $key ] = $_POST['settings'][ $key ];
            }
        }

		$settings->save();
		$this->settings = $settings->values;

		return $this->result( __('Settings saved.') );
	}

	/**
	 * Empty wp sidebars,
	 *  - create a corral for each wp sidebar,
	 *  - place corral widget inside of each wp sidebar
	 */
	function actionThemeSetup(){
		global $wp_registered_sidebars;
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		$corrals = Corrals::all();
		$new_corrals = array();

		// new options
		$new_sidebars_widgets = array(
			'wp_inactive_widgets' => $sidebars_widgets['wp_inactive_widgets'],
			'array_version' => $sidebars_widgets['array_version'],
		);
		$new_widget_ww_sidebar = array('_multiwidget' => 1);

		$i = 0;
		foreach ($wp_registered_sidebars as $sidebar_id => $sidebar_details){
			$corral_slug = Utils::makeSlug($sidebar_details['name']);

			// see if corral exists
			if (!isset($corrals[$corral_slug])){
				// make it
				$corrals[$corral_slug] = $sidebar_details['name'];
				$new_corrals[] = $sidebar_details['name'];
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

		if ( !empty( $new_corrals ) ) {
			return $this->result( sprintf( __('New corrals created: %s'), implode(', ', $new_corrals) ) );
        }

        return $this->error( __('All sidebars already have corrals assigned.') );
	}

	/**
     * Get all post types widgets can be assigned to as an options array.
     *
	 * @return array
	 */
	function postTypeOptions() {
		$post_types = get_post_types(array('public' => true, '_builtin' => false), 'names', 'and');
		$post_types['post'] = 'post';
		$post_types['page'] = 'page';
		unset($post_types['widget']);
		ksort($post_types);
		return $post_types;
	}

	/**
     * Get all taxonomies widgets can be assigned to as an options array.
     *
	 * @return array
	 */
	function taxonomyOptions() {
		$options = array();
		$taxonomies = get_taxonomies(array(), 'objects');

		foreach( $taxonomies as $slug => $taxonomy ){
			if ($taxonomy->show_ui) {
				// taken from get_edit_term_link
				// https://core.trac.wordpress.org/browser/tags/3.9.1/src/wp-includes/link-template.php#L894
				$args = array( 'taxonomy' => $slug );

				$edit_link= add_query_arg( $args, admin_url( 'edit-tags.php' ) );
				$options[ $slug ] = $taxonomy->label;
			}
		}

		return $options;
	}
}