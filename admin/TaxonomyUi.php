<?php

namespace WidgetWrangler;

/**
 * Class TaxonomiesUi
 * @package WidgetWrangler
 */
class TaxonomyUi extends AdminPage {

	/**
	 * Register hooks.
	 *
	 * @param $settings
	 *
	 * @return \WidgetWrangler\AdminPage
	 */
	public static function register( $settings ) {
		$plugin  = parent::register( $settings );

		// enabled taxonomy, but not term pages
		if ( empty( $_GET['tag_ID'] ) &&
		     !empty( $_GET['taxonomy'] ) &&
		     !empty( $settings['taxonomies'] ) &&
		     !empty( $settings['taxonomies'][ $_GET['taxonomy'] ] ) )
		{
			// alter the form and add some ajax functionality
			add_filter( 'widget_wrangler_preset_ajax_op', array( $plugin, 'ww_preset_ajax_op') );
			add_action( 'wp_ajax_ww_form_ajax', array( $plugin, 'ww_form_ajax' ) );
			add_action( 'widget_wrangler_form_meta' , array( $plugin, 'ww_form_meta' ) );

			add_action( 'admin_enqueue_scripts', array( $plugin, 'enqueue' ));
			add_action( "after-{$_GET['taxonomy']}-table", array( $plugin, 'formTaxonomy' ) );
		}

		return $plugin;
	}

	/**
	 * @see AdminPage::slug()
	 */
	function slug() {
		return 'taxonomies';
	}

	/**
	 * @see AdminPage::actions()
	 */
	function actions() {
		return array(
			'save_taxonomy' => array( $this, 'actionSaveTaxonomy' ),
		);
	}

	/**
	 * @see AdminPage::enqueue()
	 */
	function enqueue() {
		parent::enqueue();
		SortableWidgetsUi::js();
	}

	/**
	 * Need a page route for saving taxonomy defaults that doesn't exist in the
	 * admin menu.
	 */
	function menu(){
		$this->page_hook = add_submenu_page(null, $this->title(), $this->menuTitle(), $this->capability(), $this->slug(), array( $this, 'route' ) );
	}

	/**
     * Widget form on taxonomy (term list) screen. On the taxonomy screen we
     * need a complete form w/ button any route -> action.
     *
	 * @param $taxonomy
	 */
	function formTaxonomy( $taxonomy ) {

		if (isset($this->settings['taxonomies'][$taxonomy]) &&
		    $taxonomies = get_taxonomies(array('name' => $taxonomy), 'objects'))
		{
			$where = array(
				'type' => 'taxonomy',
				'variety' => 'taxonomy',
				'extra_key' => $taxonomy,
			);

			// allow for presets
			if ($tax_data = Extras::get($where))
			{
				if (isset($tax_data->data['preset_id']) && $tax_data->data['preset_id'] != 0) {
					$preset = Presets::get($tax_data->data['preset_id']);
					$widgets = $preset->widgets;
				}
				else {
					$widgets = $tax_data->widgets;
				}
			}
			else {
				$preset = Presets::getCore('default');
				$widgets = $preset->widgets;
			}

			$page_widgets = $widgets;

			ob_start();
			    print $this->templateMessages();
			    SortableWidgetsUi::metaBox( $page_widgets );
			$sortable_widgets = ob_get_clean();

			$form = new Form(array(
				'action' => $this->actionPath('save_taxonomy'),
				'style' => 'box',
				'fields' => array(
					'opening' => array(
						'type' => 'markup',
						'title' => __('Widget Wrangler'),
						'description' => __('Here you can override the default widgets for all terms in this taxonomy.', 'widgetwrangler'),
					),
					'taxonomy' => array(
						'type' => 'hidden',
						'value' => $taxonomy,
					),
					'override_default' => array(
						'type' => 'checkbox',
						'title' => __('Set as taxonomy default widgets'),
						'help' => __('Enable these widgets as the default widgets for terms in this taxonomy.'),
                        'value' => !empty( $tax_data->data['override_default'] ),
					),
					'save_widgets' => array(
						'type' => 'submit',
						'value' => __('Save Widgets', 'widgetwrangler'),
						'class' => 'button button-large button-primary',
					),
					'widgets' => array(
						'type' => 'markup',
						'value' => $sortable_widgets,
						'title' => __('Widgets'),
					),
				)
			));

			print $form->render();
		}
	}

	/**
	 * Save taxonomy widget data
	 *
	 * @return array
	 */
	function actionSaveTaxonomy() {
		if ( empty( $_POST['taxonomy'] ) ) {
			return $this->error( __('Missing taxonomy data.') );
		}

		$data = array(
			'override_default' => (int) !empty($_POST['override_default']),
		);

		Taxonomies::saveWidgets('taxonomy', $_POST['taxonomy'], $data);

		return $this->result( __('Success.') );
	}

	/**
	 * Provide the contextual ID to the wrangler form.
	 */
	function ww_form_meta() {
		?>
        <input value="<?php print $_GET['taxonomy']; ?>" type="hidden" id="ww_ajax_context_id" />
		<?php
	}

	/**
	 * Override the preset ajax op
	 *
	 * @param $op
	 *
	 * @return string
	 */
	function ww_preset_ajax_op($op){
		if ( isset($_POST['op']) && $_POST['op'] == 'replace_edit_taxonomy_widgets' ) {
			$op = 'replace_edit_taxonomy_widgets';
		}

		return $op;
	}


	/**
	 * Answer the ajax request for preset changes on a term form.
	 */
	function ww_form_ajax(){
		if ( isset( $_POST['op'] ) && $_POST['op'] == 'replace_edit_taxonomy_widgets'){
			if (isset($_POST['context_id'])) {
				$taxonomy = $_POST['context_id'];
				$preset_id = 0;

				if (isset($_POST['preset_id']) && is_numeric($_POST['preset_id'])){
					$preset_id = $_POST['preset_id'];
				}

				// if we changed to a preset, load those widgets
				if ($preset_id && $preset = Presets::get($preset_id)){
					$widgets = $preset->widgets;
				}
				// else, attempt to load tag widgets
				else {
					$where = array(
						'type' => 'taxonomy',
						'variety' => 'taxonomy',
						'extra_key' => $taxonomy,
					);

					if ($term_data = Extras::get($where)){
						$widgets = $term_data->widgets;
					}
					else {
						$widgets = Presets::getCore('default')->widgets;
					}
				}
				ob_start();
				SortableWidgetsUi::metaBox( $widgets );
				$output = ob_get_clean();

				print $output;
			}
			exit;
		}
	}
    
}
