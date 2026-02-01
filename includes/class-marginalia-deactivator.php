<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Marginalia
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Marginalia_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Flushes rewrite rules on deactivation.
	 */
	public static function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
