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
		$context = self::context();
		$widgets = apply_filters('widget_wrangler_find_all_page_widgets', null, $context);

		if ( empty( $widgets ) ) {
			$widgets = $context['widgets'];
		}

		return $widgets;
	}

	/**
	 * Get the widget data assigned to a single post.
	 *
	 * @param $post_id
	 *
	 * @return array|mixed
	 */
	public static function getPostWidgets( $post_id ) {
		$widgets = maybe_unserialize( get_post_meta( $post_id, 'ww_post_widgets', TRUE ) );

		if ( empty( $widgets ) ) {
			$widgets = array();
		}

		return $widgets;
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

	/**
	 * Provide some useful information to help determine the current page and
	 * its Widget Wrangler data.
	 *
	 * @return array
	 */
	public static function context() {
		$context = array(
			'id' => null,
			'post' => null,
			'preset' => null,
			'term' => null,
			'taxonomy' => null,
			'widgets' => null,
		);

		// Admin page for presets
		if ( is_admin() && isset( $_REQUEST['preset_id'] ) ) {
			$context['preset'] = Presets::get( $_REQUEST['preset_id'] );
			$context['widgets'] = $context['preset']->widgets;
			return $context;
		}

		// Single page/post: admin & frontend
		$post = get_post();
		if ( $post ) {
			$context = array_replace( $context, self::getSingleContext( $post->ID ) );
		}

		// Taxonomy &/ term: admin
		if ( is_admin() && ( !empty( $_GET['taxonomy'] ) ) ) {
			$context = array_replace( $context, self::getTaxonomyContext( $_GET['taxonomy'] ) );

			// term
			if ( !empty( $_GET['tag_ID'] ) ) {
				$context = array_replace( $context, self::getTermContext( $_GET['tag_ID'] ) );
			}
		}

		// Taxonomy &/ term: frontend
		if ((is_tax() || is_category() || is_tag()) && $term = get_queried_object() ) {
			$context = array_replace( $context, self::getTermContext( $term->term_id ) );
		}

		// Postspage: blog home, not "is_front()"
		if ( is_home() ) {
			$context['preset'] = Presets::getCore('postspage');
			$context['widgets'] = $context['preset']->widgets;
		}

		// Default core widgets
		if ( empty( $context['widgets'] ) ) {
			$context['preset'] = Presets::getCore('default');
			$context['widgets'] = $context['preset']->widgets;
		}

		return $context;
	}

	/**
	 * Get the preset and widgets for a single post.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function getSingleContext( $post_id ) {
		$context = array();

		if ( Settings::instance()->isEnabledPostType( get_post_type( $post_id ) ) ) {
			$context['post'] = get_post( $post_id );
			$context['id'] = $context['post']->ID;
			$context['widgets'] = self::getPostWidgets( $post_id );

			$preset_id = get_post_meta( $post_id, 'ww_post_preset_id', TRUE );

			if ( !empty( $preset_id ) ) {
				$context['preset'] = Presets::get( $preset_id );
				$context['widgets'] = $context['preset']->widgets;
			}
		}

		return $context;
	}

	/**
	 * Get the preset and widgets for a taxonomy term page.
	 *
	 * @param $term_id
	 *
	 * @return array
	 */
	public static function getTermContext( $term_id ) {
		$context = array();
		$term = get_term( $term_id );

		if ( $term && Settings::instance()->isEnabledTaxonomy( $term->taxonomy ) ) {
			$context['term'] = $term;
			$context['id'] = $term->term_id;

			$extra = Extras::get(array(
				'type' => 'taxonomy',
				'variety' => 'term',
				'extra_key' => $term->term_id,
			));

			// see if this term has widgets set
			if ( $extra ) {
				$context['widgets'] = $extra->widgets;

				if ( !empty( $extra->data['preset_id'] ) ) {
					$context['preset'] = Presets::get( $extra->data['preset_id'] );
					$context['widgets'] = $context['preset']->widgets;
				}
			}
			// otherwise see if the taxonomy is overriding
			else {
				$context = self::getTaxonomyContext( $term->taxonomy );
			}
		}

		return $context;
	}

	/**
	 * Get the preset and widgets about a given taxonomy.
	 *
	 * @param $taxonomy
	 *
	 * @return array
	 */
	public static function getTaxonomyContext( $taxonomy ) {
		$context = array();

		if ( Settings::instance()->isEnabledTaxonomy( $taxonomy ) ) {
			$context['taxonomy'] = get_taxonomy( $taxonomy );
			$context['id'] = $taxonomy;

			$extra = Extras::get(array(
				'type' => 'taxonomy',
				'variety' => 'taxonomy',
				'extra_key' => $taxonomy,
			));

			if ( $extra && isset( $extra->data['override_default'] ) ) {
				$context['widgets'] = $extra->widgets;

				if ( !empty( $extra->data['preset_id'] ) ) {
					$context['preset'] = Presets::get( $extra->data['preset_id'] );
					$context['widgets'] = $context['preset']->widgets;
				}
			}
		}

		return $context;
	}

}
