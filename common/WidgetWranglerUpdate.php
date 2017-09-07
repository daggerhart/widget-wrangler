<?php

class WidgetWranglerUpdate extends WidgetWranglerDb {

	/**
	 * Check for updates and perform as necessary
	 */
	public static function update() {
		// upgrade, if an old version exists
		if ( $old_version = get_option( 'ww_version', FALSE ) ){
			if ( version_compare( $old_version, WW_VERSION, '<') ){

				// upgrade from 1x to 2x
				if ( version_compare( $old_version, 2, '<' ) ){
					self::update2000();
				}

				// abandonment of pro
				if ( get_option('ww_pro_license_status', FALSE ) ){
					self::update2001();
				}

				update_option('ww_version', WW_VERSION);
			}
		}
	}

	/**
	 * Installation.
	 */
	public static function install() {
		$settings = new WidgetWranglerSettings();
		add_option('ww_settings', $settings->default_settings);
		add_option('ww_version', WW_VERSION);
		WidgetWranglerExtras::ensureTable();
		WW_Presets::installCore();
	}

	/**
	 * Simple check whether or not WW was previously_pro
	 *
	 * @return bool
	 */
	public static function previouslyPro() {
		$status = get_option('ww_pro_license_status', FALSE );
		if ( ! $status ){
			return FALSE;
		}

		return (isset($status->license) && $status->license === "valid") ? TRUE : FALSE;
	}
	
	/**
	 * Big upgrade with a lot of 1-time changes
	 */
	protected static function update2000() {
		// check to make sure array options aren't over serialized
		$options = array('ww_default_widgets', 'ww_postspage_widgets', 'ww_settings', 'ww_sidebars');
		foreach ($options as $option){
			if ($v = get_option($option)){
				$v = maybe_unserialize($v);
				update_option($option, $v);
			}
		}

		$settings = new WidgetWranglerSettings();

		// add new default settings
		foreach ( $settings->default_settings as $key => $value ) {
			if  ($key != "theme_compat" && !isset( $settings->{$key} ) ) {
				$settings->{$key} = $value;
			}
		}

		if ( isset( $settings->advanced ) ) {
			$settings->advanced_capability = $settings['advanced'];
			unset( $settings->advanced );
		}

		// enable legacy template suggestions
		$settings->legacy_template_suggestions = 1;
		$settings->save();

		// save the previous main version number for later use
		if ( !get_option( 'ww_previous_main_version', '' ) ) {
			add_option('ww_previous_main_version', 1, '', 'no' );
		}
		else {
			update_option('ww_previous_main_version', 1 );
		}
	}

	/**
	 * Migration after abandonment of "Pro" version
	 */
	protected static function update2001() {
		// only modifications are for versions that used to be PRO
		if ( self::previouslyPro() ){
			$settings = new WidgetWranglerSettings();

			// this site used to be a paid-for WW Pro version
			$settings->previously_pro = 1;

			// enable the override html legacy feature
			$settings->override_elements_enabled = 1;

			$settings->save();
		}

		delete_option('ww_pro_license_status');
	}
}
