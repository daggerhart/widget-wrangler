<?php

namespace WidgetWrangler;

/**
 * Class TaxonomyTermUi
 * @package WidgetWrangler
 */
class TaxonomyTermUi {

	public $settings;

	function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param $settings
	 *
	 * @return \WidgetWrangler\TaxonomyTermUi
	 */
	public static function register( $settings ) {
		$plugin = new self( $settings );

		add_action( 'edited_term', array( $plugin, 'actionSaveTerm' ) );
		add_filter( 'widget_wrangler_preset_ajax_op', array( $plugin, 'ww_preset_ajax_op') );
		add_action( 'wp_ajax_ww_form_ajax', array( $plugin, 'ww_form_ajax' ) );

		// add our form to term edit form if this taxonomy is enabled
		if ( !empty( $_GET['taxonomy'] ) &&
		     !empty( $_GET['tag_ID'] ) &&
		     !empty( $settings['taxonomies'] ) &&
		     !empty( $settings['taxonomies'][ $_GET['taxonomy'] ] ) )
		{
			add_action ( $_GET['taxonomy'] . '_edit_form_fields', array( $plugin, 'formTerm'), 10, 2);
		}

		return $plugin;
	}

	/**
	 * Widget form on taxonomy_term edit screen. For the term form we hook into
	 * the edit_term action, so we do not need a complete form. Nor do we need
	 * a route -> action.
	 *
	 * @param $tag
	 * @param $taxonomy
	 */
	function formTerm( $tag, $taxonomy ) {
		$settings = $this->settings;

		if (isset($settings['taxonomies'][$tag->taxonomy])){
			$where = array(
				'type' => 'taxonomy',
				'variety' => 'term',
				'extra_key' => $tag->term_id,
			);
			// allow for presets
			if ($term_data = Extras::get($where)){
				if (isset($term_data->data['preset_id']) && $term_data->data['preset_id'] != 0) {
					$preset = Presets::get($term_data->data['preset_id']);
					$widgets = $preset->widgets;
				}
				else {
					$widgets = $term_data->widgets;
				}
			}
			else {
				$preset = Presets::getCore('default');
				$widgets = $preset->widgets;
			}
			?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label><?php _e('Widget Wrangler', 'widgetwrangler'); ?></label>
					<p class="description">(<?php _e('for this term only', 'widgetwrangler'); ?>)</p>
				</th>
				<td>
					<div class="ww-box">
						<h3><?php _e('Widgets'); ?></h3>
						<div>
							<?php
							SortableWidgetsUi::metaBox( $widgets );
							?>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Save taxonomy_term widget data
	 *
	 * @param $term_id
	 */
	function actionSaveTerm( $term_id ) {
		if ( empty( $_POST['taxonomy'] ) || empty( $_POST['tag_ID'] ) ) {
			return;
		}

		Taxonomies::saveWidgets('term', $_POST['tag_ID']);
	}

	/**
	 * Override the preset ajax op
	 *
	 * @param $op
	 *
	 * @return string
	 */
	function ww_preset_ajax_op($op){
		if ( isset( $_POST['op'] ) && $_POST['op'] == 'replace_edit_taxonomy_term_widgets' ) {
			$op = 'replace_edit_taxonomy_term_widgets';
		}

		return $op;
	}

	/**
	 * Answer the ajax request for preset changes on a term form.
	 */
	function ww_form_ajax(){
		if ( isset($_POST['op'] ) && $_POST['op'] == 'replace_edit_taxonomy_term_widgets'){
			// legit post ids only
			if (isset($_POST['context_id']) && is_numeric($_POST['context_id']) && $_POST['context_id'] > 0)
			{
				$tag_id = $_POST['context_id'];
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
						'variety' => 'term',
						'extra_key' => $tag_id,
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
