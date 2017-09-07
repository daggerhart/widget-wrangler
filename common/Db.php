<?php

namespace WidgetWrangler;

/**
 * Class Db
 * @package WidgetWrangler
 */
abstract class Db {

	/**
	 * Simple wrapper to retrieve global $wpdb object with local modifications
	 *
	 * @return \wpdb
	 */
	public static function db() {
		global $wpdb;
		$wpdb->ww_extras_table = $wpdb->prefix."ww_extras";

		return $wpdb;
	}
}
