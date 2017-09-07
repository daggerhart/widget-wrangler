<?php

namespace WidgetWrangler;

/**
 * Class Settings
 * @package WidgetWrangler
 */
class Settings {

	// wp option name/key
	public $option_name = 'ww_settings';

	// stored option values array
	public $values = [];

	// default plugin settings values
	public $default_settings = array(
		'exclude_from_search' => 1,
		'theme_compat' => 1,
		'capabilities' => 'simple',
		'advanced_capability' => '',
		'post_types' => array(
			'page' => 'page',
			'post' => 'post',
		),
		'taxonomies' => array(),
		'override_elements' => array(
			'div', 'h2', 'h3', 'aside', 'strong', 'span',
		),
		'legacy_template_suggestions' => 0,

		// begin the weaning of features that will be removed
		// dead end features:  override html elements, shortcode tinymce
		'previously_pro' => 0,
		'override_elements_enabled' => 0,
		'shortcode_tinymce' => 0,
	);

	/**
	 * WidgetWranglerSettings constructor.
	 */
	 function __construct(){
		 $this->values = get_option( $this->option_name, $this->default_settings );
		 // help with over serialization in previous versions
		 $this->values = maybe_unserialize($this->values);
		 $this->values = array_replace( $this->default_settings, $this->values );
		 return $this;
	}

	/**
	 * Save the stored values to the option row
	 */
	function save(){
		update_option( $this->option_name, $this->values );
	}

	/**
	 * Magic getter
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	function __get( $key ){
		if ( isset( $this->values[ $key ] ) ) {
			return $this->values[ $key ];
		}

		return null;
	}

	/**
	 * Magic setter
	 *
	 * @param $key
	 * @param $value
	 */
	function __set( $key, $value ){
		$this->values[ $key ] = $value;
	}

	/**
	 * Magic isset check
	 * @param $key
	 *
	 * @return bool
	 */
	function __isset( $key ){
		return isset( $this->values[ $key ] );
	}

	/**
	 * Magic unset
	 * @param $key
	 */
	function __unset( $key ){
		unset( $this->values[ $key ]);
	}
}
