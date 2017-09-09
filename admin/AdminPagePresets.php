<?php

namespace WidgetWrangler;

/**
 * Class AdminPagePresets
 * @package WidgetWrangler
 */
class AdminPagePresets extends AdminPage {

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
		add_action( 'widget_wrangler_save_widgets_alter', array( $this, 'ww_save_widgets_alter' ) );
	}

	/**
	 * @see AdminPage::enqueue()
	 */
	function enqueue() {
	    parent::enqueue();

	    if ($this->onPage()) {
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

        $widgets = ( !empty( $_POST['ww-data'] ) && !empty( $_POST['ww-data']['widgets'] ) ) ? $_POST['ww-data']['widgets'] : array();
        $active_widgets = Utils::serializeWidgets( $widgets );
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
     * Widget Wrangler hook on widgets save.
     *
	 * @param $widgets
	 *
	 * @return bool
	 */
	function ww_save_widgets_alter($widgets){
		// '0' handles itself
		$new_preset_id = (isset($_POST['ww-preset-id-new'])) ? (int)$_POST['ww-preset-id-new'] : 1;
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

		return $widgets;
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
            'action' => $this->actionPath('create'),
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

				return $this->result( __('New Preset Created.'), $this->pageUrl().'&preset_id='.$new_preset_id );
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
            'action' => $this->actionPath('update_data'),
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
		    'action' => $this->actionPath('delete'),
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

		return $this->result( __('Preset deleted.'), $this->pageUrl() );
	}

	/**
	 * Admin Manage presets form
	 */
	function page() {
		$all = Presets::all();
		$preset_id = 1;
		$preset = Presets::getCore('default');
        $context = Utils::context();
		$sortable = new SortableWidgetsUi();

		if ( $context['preset'] ) {
			$preset = $context['preset'];
			$preset_id = $preset->id;
		}
		ob_start();
		?>
        <div class="ww-columns">
            <div class="ww-column col-25">
                <div class="ww-box">
                    <h3><?php _e('Presets'); ?></h3>
                    <ul class="ww-list-links">
                        <?php foreach($all as $item) {
                            $classes = ($preset_id == $item->id) ? 'active' : '';
                            ?>
                            <li class="<?php print $classes; ?>">
                                <a href="<?php print $this->pagePath(); ?>&preset_id=<?php print $item->id; ?>"><?php print $item->data['name']; ?><?php print ($item->variety == 'core') ? ' <small>('.$item->variety.')</small>' : ''; ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="ww-box">
                    <h3><?php _e('Create New'); ?></h3>
                    <?php print $this->formCreate(); ?>
                </div>
            </div>
            <div class="ww-column col-75">
                <?php if ( $preset->variety == 'core') { ?>
                    <div class="ww-box">
                        <h3><?php print $preset->data['name']; ?></h3>
                    </div>
                <?php } else { ?>
                    <div class="ww-box ww-box-toggle">
                        <h3><?php print $preset->data['name']; ?></h3>
                        <div class="ww-box-toggle-content">
                            <?php
                            print $this->formEdit($preset);
                            print $this->formDelete($preset);
                            ?>
                        </div>
                    </div>
                <?php } ?>

                <div class="ww-box">
                    <h3><?php _e('Widgets'); ?></h3>
                    <?php
                    $form = new Form(array(
	                    'action' => $this->actionPath('update_widgets'),
	                    'fields' => array(
		                    'submit' => array(
			                    'type' => 'submit',
			                    'value' => __('Save Preset'),
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
		                    'wrangler' => array(
			                    'type' => 'markup',
			                    'value' => $sortable->form( $preset->widgets ),
                            ),
	                    )
                    ));

                    print $form->render();
                    ?>
                </div>
            </div>
        </div>
		<?php
	}

}
