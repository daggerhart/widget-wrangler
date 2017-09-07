<?php

/**
 * WW_Taxonomies allows for setting widgets on taxonomy term routes
 */
class WW_Taxonomies {

	/**
	 * Register WP hooks.
	 *
	 * @return \WW_Taxonomies
	 */
	public static function register() {
		$plugin = new self();

		add_filter( 'widget_wrangler_find_all_page_widgets', array( $plugin, 'ww_find_taxonomy_term_widgets' ) );

		return $plugin;
	}

	/**
	 * Find the widgets for a taxonomy term page
	 *
	 * @param array|null - array if widgets previously found, null if not
	 *
	 * @return array|null
	 */
	function ww_find_taxonomy_term_widgets($widgets){
		if (is_null($widgets) &&
		    (is_tax() || is_category() || is_tag()) &&
		    $term = get_queried_object())
		{
			$where = array(
				'type' => 'taxonomy',
				'variety' => 'term',
				'extra_key' => $term->term_id,
			);
			$term_data = WidgetWranglerExtras::get($where);

			if ( $term_data ) {
				$widgets = $this->getTaxonomyWidgets($term_data);
			}
			else {
				// see if the taxonomy is overriding
				$where = array(
					'type' => 'taxonomy',
					'variety' => 'taxonomy',
					'extra_key' => $term->taxonomy,
				);
				$tax_data = WidgetWranglerExtras::get($where);

				if ( $tax_data && isset( $tax_data->data['override_default'] ) ) {
					$widgets = $this->getTaxonomyWidgets($tax_data);
				}
			}
		}

		return $widgets;
	}

	/**
	 * Find the widgets for a taxonomy page.  Terms can use the parent taxonomy's
	 *  widget configuration as their default.
	 *
	 * @param array|null - array if widgets previously found, null if not
	 *
	 * @return array|null
	 */
	function getTaxonomyWidgets($tax_data){

		$widgets = $tax_data->widgets;

		if (isset($tax_data->data['preset_id']) && $tax_data->data['preset_id'] != 0){
			$preset = WW_Presets::get($tax_data->data['preset_id']);

			WW_Presets::$current_preset_id = $preset->id;
			$widgets = $preset->widgets;
		}

		return $widgets;
	}
}
