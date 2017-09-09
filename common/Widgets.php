<?php

namespace WidgetWrangler;

/**
 * Class WidgetWranglerWidgets
 * @package WidgetWrangler
 */
class Widgets extends Db {

	/**
	 * Returns all published widgets
	 *
	 * @param array $post_status
	 *
	 * @return array
	 */
	public static function all($post_status = array('publish')) {

		if (!is_array($post_status)) { $post_status = array($post_status); }
		$status = implode("','", $post_status);

		$db = self::db();

		$query = "
			SELECT `ID`
			FROM {$db->posts}
			WHERE `post_type` = 'widget' AND `post_status` IN ('$status')";
		$results = $db->get_results($query);

		$widgets = array();
		$i=0;
		$total = count($results);

		while($i < $total){
			$widgets[$results[$i]->ID] = self::get($results[$i]->ID);
			$i++;
		}

		return $widgets;
	}

	/**
	 * Retrieve and return a single widget by its ID
	 *
	 * @param $widget_id
	 * @param bool $widget_status
	 *
	 * @return object|false
	 */
	public static function get( $widget_id, $widget_status = false ) {
		// make sure method called with parameter
		if ( empty( $widget_id ) ) { return false; }

		$db = self::db();
		$status = $widget_status ? "`post_status` = '".$widget_status."' AND" : "";

		$query = "
			SELECT `ID`,`post_name`,`post_title`,`post_content`,`post_status`
            FROM `{$db->posts}`
            WHERE `post_type` = 'widget' AND {$status} `ID` = {$widget_id}
            LIMIT 1";

		if ($widget = $db->get_row($query)) {

			// do this first so the following get_post_meta queries are pulled from cache
			$widget->widget_meta  = get_post_meta($widget->ID);

			$widget->adv_enabled  = get_post_meta($widget->ID,'ww-adv-enabled',TRUE);
			$widget->adv_template = get_post_meta($widget->ID,'ww-adv-template',TRUE);
			$widget->parse        = get_post_meta($widget->ID,'ww-parse', TRUE);
			$widget->wpautop      = get_post_meta($widget->ID,'ww-wpautop', TRUE);
			$widget->widget_type  = get_post_meta($widget->ID,'ww-widget-type', TRUE);
			if (empty($widget->widget_type)){
				$widget->widget_type = "standard";
			}

			// output related variables
			$widget->display_logic_enabled  = get_post_meta($widget->ID,'ww-display-logic-enabled',TRUE);
			$widget->display_logic  = get_post_meta($widget->ID,'ww-display-logic',TRUE);
			$widget->wp_widget_args = array('before_widget' => '', 'before_title' => '', 'after_title' => '', 'after_widget' => '');
			$widget->hide_title = get_post_meta($widget->ID,'ww-hide-title', TRUE);
			$widget->hide_from_wrangler = get_post_meta($widget->ID,'ww-hide-from-wrangler', TRUE);
			$widget->override_output_html = get_post_meta($widget->ID,'ww-override-output-html', TRUE);
			$widget->html = array(
				'wrapper_element' => get_post_meta($widget->ID,'ww-html-wrapper-element', TRUE),
				'wrapper_id'      => get_post_meta($widget->ID,'ww-html-wrapper-id', TRUE),
				'wrapper_classes' => get_post_meta($widget->ID,'ww-html-wrapper-classes', TRUE),
				'title_element'   => get_post_meta($widget->ID,'ww-html-title-element', TRUE),
				'title_classes'   => get_post_meta($widget->ID,'ww-html-title-classes', TRUE),
				'content_element' => get_post_meta($widget->ID,'ww-html-content-element', TRUE),
				'content_classes' => get_post_meta($widget->ID,'ww-html-content-classes', TRUE),
			);

			$widget->custom_template_suggestion = get_post_meta($widget->ID,'ww-custom-template-suggestion', TRUE);

			// clones
			$widget->clone_classname = get_post_meta($widget->ID,'ww-clone-classname', TRUE);
			$widget->clone_instance = get_post_meta($widget->ID,'ww-clone-instance', TRUE);

			$widget->in_preview = FALSE;

			return $widget;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public static function asOptions( $widgets = array() ) {
		$options = array();

		if ( empty( $widgets ) ) {
			$widgets = self::all();
		}

		foreach( $widgets as $id => $widget ) {
			if ( ! $widget->hide_from_wrangler ) {
				$options[ $id ] = $widget->post_title;
			}
		}

		return $options;
	}

}
