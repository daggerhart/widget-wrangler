<?php

namespace WidgetWrangler;

/**
 * Class AdminPagePresets
 * @package WidgetWrangler
 */
class AdminPagePresets extends AdminPage {
    // @todo - fix this current preset id stuff
	public $current_preset_id = 0;

	public $new_preset_id = FALSE;

	/**
	 * @see AdminPage::title()
	 */
	function title() {
		return __('Presets');
	}

	/**
	 * @see AdminPage::menuTitle()
	 */
	function menuTitle() {
		return __('Presets');
	}

	/**
	 * @see AdminPage::slug()
	 */
	function slug() {
		return 'presets';
	}

	/**
	 * @see AdminPage::description()
	 */
	function description() {
		return array(
			__('Presets are pre-configured sets of Widgets assigned to Corrals. This makes a collection of widgets reusable on many pages without having to edit them individually.'),
            __('If you edit a preset here, the updates will apply to all pages using the preset.'),
		);
	}

	/**
	 * @see AdminPage::actions()
	 */
	function actions() {
		return array(
			'create' => array( $this, 'actionCreate' ),
            'update_widgets' => array( $this, 'actionUpdateWidgets' ),
            'update_data' => array( $this, 'actionUpdateWidgetsPresetData' ),
            'delete' => array( $this, 'actionDelete' ),
		);
	}

	/**
	 * @param $settings
	 *
	 * @return AdminPage
	 */
	public static function register( $settings ) {
		$plugin  = parent::register( $settings );

		add_action('admin_init', array( $plugin, 'init') );

		return $plugin;
	}

	/**
	 * Implements action 'admin_init'
	 */
	function init(){
		add_action( 'widget_wrangler_form_top', array( $this, 'ww_form_top' ));
		add_action( 'wp_ajax_ww_form_ajax', array( $this, 'ww_form_ajax' ) );
		add_action( 'widget_wrangler_save_widgets_alter', array( $this, 'ww_save_widgets_alter' ) );

	}

	/**
	 * @see AdminPage::actions()
	 */
	function enqueue() {
	    parent::enqueue();

	    if ($this->onPage()) {
		    wp_enqueue_style('ww-sortable-widgets');
		    SortableWidgetsUi::js();
        }
    }


	/**
	 * Update a Widget Preset
	 */
	function actionUpdateWidgets() {
		if ( empty( $_POST['preset-id'] ) ) {
			return $this->error( __('Error: No preset id.') );
		}

		if ( empty( $_POST['preset-variety'] ) ) {
			return $this->error( __('Error: No preset variety.') );
		}

        if ( empty( $_POST['ww-data'] ) || empty( $_POST['ww-data']['widgets'] ) ) {
	        return $this->error( __('Error: No widgets.') );
        }

        $active_widgets = Utils::serializeWidgets($_POST['ww-data']['widgets']);
        $preset_id = intval( $_POST['preset-id'] );
        $preset_variety = $_POST['preset-variety'];
        $preset_widgets = (!empty($active_widgets)) ? $active_widgets : serialize(array());

        // update the ww_presets db
        $data = array(
            'widgets' => $preset_widgets,
        );

        $where = array(
            'id' => $preset_id,
            'type' => 'preset',
            'variety' => $preset_variety,
        );

        // save the widgets
        Extras::update($data, $where);

        return $this->result(__('Preset updated.'));
	}

	/**
	 * Ajax form templates
	 */
	function ww_form_ajax() {
		if (isset($_POST['op']) && $_POST['op'] == 'replace_edit_page_widgets'){
			// legit post ids only
			if (isset($_POST['context_id']) && is_numeric($_POST['context_id']) && $_POST['context_id'] > 0)
			{
				$post_id = $_POST['context_id'];
				$preset_id = 0;

				if (isset($_POST['preset_id']) && is_numeric($_POST['preset_id'])){
					$preset_id = $_POST['preset_id'];
				}

				// if we changed to a preset, load those widgets
				if ($preset_id && $preset = Presets::get($preset_id)){
					$this->current_preset_id = $preset_id;
					$widgets = $preset->widgets;
				}
				// else, attempt to load page widgets
				else {
					$page_widgets = get_post_meta($post_id, 'ww_post_widgets', TRUE);
					$widgets = ($page_widgets != '') ? maybe_unserialize($page_widgets) : array();
				}

				ob_start();
				SortableWidgetsUi::metaBox( $widgets );
				$output = ob_get_clean();
				print $output;
			}
			exit;
		}
	}

	/**
     * Widget Wrangler hook on widgets save.
     *
	 * @param $widgets
	 *
	 * @return bool
	 */
	function ww_save_widgets_alter($widgets){
		// '0' handles itself
		$new_preset_id = (isset($_POST['ww-post-preset-id-new'])) ? (int)$_POST['ww-post-preset-id-new'] : 1;
		$new_preset_widgets = NULL;

		// attempt to load that new preset
		if ($new_preset_id && $new_preset = Presets::get($new_preset_id)){
			$new_preset_widgets = serialize($new_preset->widgets);
		}

		// if the widgets to be saved are not the same as the selected preset's widgets
		//   then the user loaded a preset, and changed some widgets
		if ($widgets != $new_preset_widgets){
			// force the 'zero' preset because these widgets are custom
			$new_preset_id = 0;
		}

		// if new_preset_id is not zero, then a preset was selected and we don't want to save widgets
		if ($new_preset_id !== 0){
			$widgets = false;
		}
		// set the new preset id for other plugins or addons to use
		Presets::$new_preset_id = $new_preset_id;

		return $widgets;
	}

	/**
	 * Preset wrangler meta box form.
	 */
	function ww_form_top() {
		$preset_ajax_op = 'replace_edit_page_widgets';
		// allow other addons to manage their own ajax
		$preset_ajax_op = apply_filters('widget_wrangler_preset_ajax_op', $preset_ajax_op);

		$all = Presets::all();
		$current_preset_id = $this->current_preset_id;
		$current_preset = NULL;
		$current_preset_message = "No preset selected. This page is wrangling widgets on its own.";

		// we have a preset to load
		if ($current_preset_id && $current_preset = Presets::get($current_preset_id)){
			$current_preset_message = "This page is currently using the <a href='".$this->pagePath() . "&preset_id=" . $current_preset->id . "'>" . $current_preset->data['name'] . "</a>  Widgets.";
		}

		$preset_options = array(0 => __('- No Preset'));
		foreach( $all as $preset ) {
			$preset_options[ $preset->id ] = $preset->data['name'];
		}

		?>
        <div id='ww-post-preset'>
            <span id="ww-post-preset-message"><?php print $current_preset_message; ?></span>
            <?php
				$form = new Form();
				print $form->render_field(array(
                    'type' => 'hidden',
                    'name' => 'widget_wrangler_preset_ajax_op',
                    'value' => $preset_ajax_op,
                ));
				print $form->render_field(array(
					'type' => 'select',
					'name' => 'ww-post-preset-id-new',
					'title' => __('Preset'),
					'description' => '<span class="ajax-working spinner"></span>',
					'help' => __("Select the Widget Preset you would like to control widgets on this page. To allow this page to control its own widgets, select '- No Preset -'. If you select a preset and then rearrange widgets, this page will convert itself to '- No Preset -' on save."),
					'options' => $preset_options,
					'value' => $current_preset_id,
				));
            ?>
        </div>
		<?php
	}

	/**
     * Form to create a new preset.
     *
	 * @return string
	 */
    function formCreate(){
	    $varieties  = Presets::varieties();
	    unset($varieties['core']);
	    $options = array();

	    foreach ($varieties as $key => $variety) {
	        $options[$key] = $variety['title'];
        }

	    $form = new Form(array(
            'field_prefix' => 'create',
            'action' => 'edit.php?post_type=widget&page=presets&ww_action=create&noheader=true',
            'fields' => array(
	            'variety' => array(
		            'type' => 'select',
		            'title' => __('Variety'),
		            'options' => $options,
	            ),
	            'save' => array(
		            'type' => 'submit',
		            'value' => __('Create New Preset'),
		            'class' => 'button button-primary button-large',
	            )
            ),
        ));

	    return $form->render();
    }

	/**
	 * Create a new Widget Preset
	 */
	function actionCreate() {
		if ( empty( $_POST['create'] ) || empty( $_POST['create']['variety'] ) ) {
			return $this->error( __('Error: Missing data.') );
		}

		switch( $_POST['create']['variety'] ) {
			case 'standard':
				$data = array(
					'type' => 'preset',
					'variety' => Utils::makeSlug( $_POST['create']['variety'] ),
					'data' => serialize(array('name' => ucfirst($_POST['create']['variety']))),
					'widgets' => serialize(array()),
				);

				$new_preset_id = Extras::insert($data);

				return $this->result(
					__('New Preset Created.'),
					get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=presets&preset_id='.$new_preset_id
				);
				break;
		}

		return $this->error();
	}

	/**
     * Form to edit a preset's data.
     *
	 * @param $preset
	 *
	 * @return string
	 */
    function formEdit( $preset ) {
        $form = new Form(array(
            'style' => 'table',
            'action' => 'edit.php?post_type=widget&page=presets&ww_action=update_data&noheader=true',
            'fields' => array(
                'submit' => array(
                    'type' => 'submit',
                    'value' => __('Update'),
                    'class' => 'ww-pull-right button button-primary button-large',
                ),
                'preset-id' => array(
                    'type' => 'hidden',
                    'value' => $preset->id,
                ),
                'preset-variety' => array(
                    'type' => 'hidden',
                    'value' => $preset->variety,
                ),
                'name' => array(
	                'type' => 'text',
	                'title' => __('Name'),
	                'name_prefix' => 'data',
	                'value' => $preset->data['name'],
                    'class' => 'regular-text',
                ),
                'slug' => array(
	                'type' => 'markup',
	                'title' => __('Type'),
	                'value' => $preset->variety,
                ),
            )
        ));

        return $form->render();
    }

	/**
	 * Update the preset name.
	 *
	 * @return array
	 */
	function actionUpdateWidgetsPresetData() {
		if ( empty( $_POST['preset-id'] ) ) {
			return $this->error( __('Error: No preset id.') );
		}

		if ( empty( $_POST['preset-variety'] ) ) {
			return $this->error( __('Error: No preset variety.') );
		}

		if ( empty( $_POST['data'] ) || empty( $_POST['data']['name'] ) ) {
			return $this->error( __('Error: Missing data.') );
		}

		$where = array(
			'id' => $_POST['preset-id'],
			'variety' => $_POST['preset-variety']
		);

		$preset = Extras::get($where);

		if ( $preset ) {
			$preset->data['name'] = sanitize_text_field($_POST['data']['name']);
			Extras::update(array( 'data' => $preset->data ), $where);

			return $this->result(sprintf(__('Updated preset name to "%s".'), $preset->data['name']));
		}

		return $this->error( __('Error: Preset not found') );
	}

	/**
     * Form to delete a preset.
     *
	 * @param $preset
	 *
	 * @return string
	 */
    function formDelete($preset) {

	    $form = new Form(array(
		    'style' => 'table',
		    'action' => 'edit.php?post_type=widget&page=presets&ww_action=delete&noheader=true',
		    'fields' => array(
			    'submit' => array(
				    'type' => 'submit',
				    'value' => __('Delete'),
				    'class' => 'button button-small disabled',
				    'attributes' => array(
					    'data-confirm' => __('Are you sure you want to delete this preset?'),
				    ),
			    ),
			    'preset-id' => array(
				    'type' => 'hidden',
				    'value' => $preset->id,
			    ),
			    'preset-variety' => array(
				    'type' => 'hidden',
				    'value' => $preset->variety,
			    ),
		    )
	    ));

	    return $form->render();
    }

	/**
	 * Delete a Widget Preset
	 */
	function actionDelete(){
		if ( empty( $_POST['preset-id'] ) ) {
			return $this->error( __('Error: No preset id.') );
		}

		if ( empty( $_POST['preset-variety'] ) ) {
			return $this->error( __('Error: No preset variety.') );
		}

		if ( $_POST['preset-variety'] == 'core' ) {
			return $this->error( __('Error: You cannot delete a plugin provided preset.') );
		}

		Extras::delete(array(
			'type' => 'preset',
			'variety' => $_POST['preset-variety'],
			'id' => $_POST['preset-id'],
		));

		return $this->result(
			__('Preset deleted.'),
			get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=presets'
		);
	}

	/**
	 * Admin Manage presets form
	 */
	function page() {
		// prepare preset data
		$preset_id = 1;

		if(isset($_GET['preset_id'])) {
			if (is_numeric($_GET['preset_id']) && $_GET['preset_id'] > 1){
				$preset_id = $_GET['preset_id'];
			}
		}

		$all = Presets::all();
		$this_preset = Presets::get($preset_id);
		$varieties  = Presets::varieties();
		$preset_variety  = $varieties[$this_preset->variety];

		// themes draggable widgets
		$sortable = new SortableWidgetsUi();

		// need to remove the normal preset form_top. can't select preset for preset
		remove_action( 'widget_wrangler_form_top', array( $this, 'ww_form_top' ));

		ob_start();
		?>
        <div class="ww-columns">
            <div class="ww-column col-25">
                <div class="ww-box">
                    <h3>Presets</h3>
                    <ul class="ww-list-links">
                        <?php
                        foreach($all as $preset)
                        {
                            $classes = ($preset_id == $preset->id) ? 'active' : '';
                            ?>
                            <li class="<?php print $classes; ?>">
                                <a href="edit.php?post_type=widget&page=presets&preset_id=<?php print $preset->id; ?>"><?php print $preset->data['name']; ?><?php print ($preset->variety == 'core') ? ' <small>('.$preset->variety.')</small>' : ''; ?></a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
                <div class="ww-box">
                    <h3><?php _e('Create New'); ?></h3>
                    <?php print $this->formCreate(); ?>
                </div>
            </div>
            <div class="ww-column col-75">
                <?php
                // can't change the names of core presets
                if($preset_variety['slug'] == 'core')
                { ?>
                    <div class="ww-box">
                        <h3><?php print $this_preset->data['name']; ?></h3>
                        <input type="hidden" name="data[name]" value="<?php print $this_preset->data['name']; ?>" />
                    </div>
                    <?php
                }
                else
                { ?>
                    <div class="ww-box ww-box-toggle">
                        <h3><?php print $this_preset->data['name']; ?></h3>
                        <div class="ww-box-toggle-content">
                            <?php
                            print $this->formEdit($this_preset);
                            print $this->formDelete($this_preset);
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="ww-box">
                    <h3><?php _e('Widgets'); ?></h3>

                    <form action='edit.php?post_type=widget&page=presets&ww_action=update_widgets&noheader=true' method='post' name='widget-wrangler-form'>

                        <input class='ww-pull-right-box-out button button-primary button-large' type='submit' value='Save Preset'>
                        <input type='hidden' name='widget-wrangler-edit' value='true' />
                        <input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
                        <input type="hidden" name="preset-id" value="<?php print $preset_id; ?>" />
                        <input type="hidden" name="preset-variety" value="<?php print $preset_variety['slug']; ?>" />

                        <div id="widget_wrangler_form_top">
                            <?php
                            // TODO, dry this action up
                            do_action('widget_wrangler_form_top');
                            ?>
                        </div>
                        <div id='ww-edited-message'>
                            <p><em>* <?php _e("Widget changes will not be updated until you save.", 'widgetwrangler'); ?></em></p>
                        </div>

                        <div>
                            <?php print $sortable->theme_sortable_corrals( $this_preset->widgets ); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
		<?php
	}

}
