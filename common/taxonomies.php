<?php

namespace WidgetWrangler;

/**
 * Class Taxonomies allows for setting widgets on taxonomy term routes.
 * @package WidgetWrangler
 */
class Taxonomies {

	/**
	 * Register WP hooks.
	 *
	 * @return Taxonomies
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
			$term_data = Extras::get($where);

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
				$tax_data = Extras::get($where);

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
			$preset = Presets::get($tax_data->data['preset_id']);

			$widgets = $preset->widgets;
		}

		return $widgets;
	}

	/**
	 * Save widgets from a wrangler form
	 *
	 * @param $variety
	 * @param $extra_key
	 * @param array $additional_data
	 */
	public static function saveWidgets($variety, $extra_key, $additional_data = array()){
		//
		$widgets = Utils::serializeWidgets($_POST['ww-data']['widgets']);

		// let presets addon do it's stuff
		$widgets = apply_filters('widget_wrangler_save_widgets_alter', $widgets);

		// get the new preset id, if set
		$new_preset_id = (isset($_POST['ww-post-preset-id-new'])) ? (int)$_POST['ww-post-preset-id-new'] : 0;

		$where = array(
			'type' => 'taxonomy',
			'variety' => $variety,
			'extra_key' => $extra_key,
		);

		$values = array(
			'type' => 'taxonomy',
			'variety' => $variety,
			'extra_key' => $extra_key,
			'data' => array('preset_id' => $new_preset_id),
			'widgets' => $widgets,
		);

		if (!empty($additional_data)){
			$values['data'] += $additional_data;
		}

		// doesn't exist, create it before update
		if (!Extras::get($where)){
			$values['data'] = serialize($values['data']);
			Extras::insert($values);
		}

		if ( $widgets ) {
			// no preset, save widgets
			$values['widgets'] = $widgets;

			// force the 'zero' preset because these widgets are custom
			$values['data']['preset_id'] = 0;
		}

		if ( $new_preset_id ) {
			// don't save widgets because they are preset widgets
			unset($values['widgets']);
		}

		$values['data'] = serialize($values['data']);
		Extras::update($values, $where);
	}

}
