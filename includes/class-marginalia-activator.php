<?php
/**
 * Fired during plugin activation.
 *
 * @package Marginalia
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Marginalia_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates default reading status terms and flushes rewrite rules.
	 */
	public static function activate() {
		// Register post type and taxonomy first.
		require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-post-type.php';
		require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-taxonomy.php';

		$post_type = new Marginalia_Post_Type();
		$post_type->register_post_type();

		$taxonomy = new Marginalia_Taxonomy();
		$taxonomy->register_taxonomy();

		// Create default reading status terms.
		$default_terms = array(
			'to-read'           => __( 'To Read', 'marginalia-reading-log' ),
			'currently-reading' => __( 'Currently Reading', 'marginalia-reading-log' ),
			'read'              => __( 'Read', 'marginalia-reading-log' ),
			'did-not-finish'    => __( 'Did Not Finish', 'marginalia-reading-log' ),
		);

		foreach ( $default_terms as $slug => $name ) {
			if ( ! term_exists( $slug, 'reading_status' ) ) {
				wp_insert_term(
					$name,
					'reading_status',
					array( 'slug' => $slug )
				);
			}
		}

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set activation flag for admin notice.
		set_transient( 'marginalia_activation_notice', true, 30 );
	}
}
