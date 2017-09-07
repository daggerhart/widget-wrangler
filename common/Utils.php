<?php

namespace WidgetWrangler;

/**
 * Class Utils
 * @package WidgetWrangler
 */
class Utils {

	/**
	 * Apply filter so all addons can help find the appropriate page_widgets
	 *
	 * @return array|null
	 */
	public static function pageWidgets() {
		return apply_filters('widget_wrangler_find_all_page_widgets', null);
	}

	/**
	 * Alter WP Sidebars
	 *
	 * @param bool $force_alter
	 *
	 * @return array
	 */
	public static function alteredSidebars($force_alter = false) {
		global $wp_registered_sidebars;
		$ww_alter_sidebars = get_option('ww_alter_sidebars', array());
		$combined = array();

		// altered sidebars
		foreach ($wp_registered_sidebars as $slug => $sidebar){
			// use original
			if ($force_alter || isset($ww_alter_sidebars[$slug]['ww_alter'])){
				$combined[$slug] = $wp_registered_sidebars[$slug];
				if ( isset($ww_alter_sidebars[$slug]) && is_array($ww_alter_sidebars[$slug]) ){
					foreach ($ww_alter_sidebars[$slug] as $k => $v){
						if (isset($v)) {
							$combined[$slug][$k] = $v;
						}
					}
				}
			}
			else {
				$combined[$slug] = $wp_registered_sidebars[$slug];
			}

			$combined[$slug]['ww_created'] = FALSE;
		}

		return $combined;
	}

	/**
	 * Cleanup stored widget data in case of over serialization
	 *
	 * @param $corrals
	 *
	 * @return mixed
	 */
	public static function unserializeWidgets($corrals) {
		// problem with over serialized options
		$corrals = maybe_unserialize( $corrals );
		$corrals = maybe_unserialize( $corrals );

		if (isset( $corrals['disabled'] ) ) {
			unset( $corrals['disabled'] );
		}

		foreach ($corrals as $corral_slug => $corral_widgets) {
			foreach ($corral_widgets as $i => $widget) {
				if (isset( $widget['name'] ) ) {
					unset( $corrals[ $corral_slug ][ $i ]['name'] );
				}
			}
		}

		return $corrals;
	}

	/**
	 * Take data from $_POST submit and convert in to serialized array as string
	 *
	 * @param $submitted_widget_data
	 *
	 * @return string
	 */
	public static function serializeWidgets($submitted_widget_data) {

		$all_widgets = Widgets::all(array('publish', 'draft'));
		$active_widgets = array();

		if ( ! empty( $submitted_widget_data ) ) {
			foreach ( $submitted_widget_data as $key => $details ) {
				// get rid of any hashes
				if ( isset( $all_widgets[ $details['id'] ] ) && isset( $details['weight'] ) && isset( $details['sidebar'] ) ) {
					// if something was submitted without a weight, make it neutral
					if ( $details['weight'] < 1 ) {
						$details['weight'] = $key;
					}

					$active_widgets[ $details['sidebar'] ][] = array(
						'id'     => $details['id'],
						'weight' => $details['weight'],
					);
				}
			}
		}

		return serialize($active_widgets);
	}

	/**
	 * Callable for usort - Sort an array by a property named "weight"
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function sortByWeight($a, $b) {
		$a = (object) $a;
		$b = (object) $b;
		if ($a->weight == $b->weight) return 0;
		return ($a->weight > $b->weight) ? 1 : -1;
	}

	/**
	 * Create a machine safe name from a given string
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function makeSlug($string) {
		return stripcslashes(preg_replace('/[\s_\'\"]/','_', strtolower(strip_tags($string))));
	}

	public static function currentPostId() {

	}

	/**
	 * Determine if we are editing a post type with widget wrangler enabled.
	 *
	 * @return bool
	 */
	public static function editingEnabledPostType() {
		$settings = new Settings();

		if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') &&
		     isset($_REQUEST['post']))
		{
			$current_post_type = get_post_type($_REQUEST['post']);

			if (in_array($current_post_type, $settings->post_types)){
				return TRUE;
			}
		}

		return FALSE;
	}

//	/**
//	 * Provide some useful information to help determine the current screen
//	 *
//	 * @return array
//	 */
//	public static function pageContext() {
//		static $context = null;
//
//		if ( $context ) {
//			return $context;
//		}
//
//		if (is_singular()){
//			global $post;
//
//			if (isset($post->ID)){
//				$context['id'] = $post->ID;
//				$context['context'] = 'post';
//				$context['object'] = $post;
//			}
//		}
//		else if ((is_tax() || is_category() || is_tag()) &&
//		         $term = get_queried_object())
//		{
//			$context['id'] = $term->term_id;
//			$context['context'] = 'term';
//			$context['object'] = $term;
//		}
//
//		$context = apply_filters('widget-wrangler-set-page-context', $context);
//
//		return $context;
//	}
}