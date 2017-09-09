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

		return $plugin;
	}

	/**
	 * Save widgets from a wrangler form
	 *
	 * @param $variety
	 * @param $extra_key
	 * @param array $additional_data
	 */
	public static function saveWidgets($variety, $extra_key, $additional_data = array()){
		$widgets = ( !empty( $_POST['ww-data'] ) && !empty( $_POST['ww-data']['widgets'] ) ) ? $_POST['ww-data']['widgets'] : array();
		$widgets = Utils::serializeWidgets($widgets);

		// let presets addon do it's stuff
		$widgets = apply_filters('widget_wrangler_save_widgets_alter', $widgets);

		// get the new preset id, if set
		$new_preset_id = (isset($_POST['ww-preset-id-new'])) ? (int)$_POST['ww-preset-id-new'] : 0;

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

		if ( $widgets ) {
			// no preset, save widgets
			$values['widgets'] = $widgets;

			// force the 'zero' preset because these widgets are custom
			$values['data']['preset_id'] = 0;
		}

		// doesn't exist, create it before update
		if (!Extras::get($where)){
			$values['data'] = serialize($values['data']);
			Extras::insert($values);
		}

		if ( $new_preset_id ) {
			// don't save widgets because they are preset widgets
			unset($values['widgets']);
		}

		$values['data'] = serialize($values['data']);
		Extras::update($values, $where);
	}

}
