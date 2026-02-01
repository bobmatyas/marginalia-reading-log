<?php
/**
 * Public-facing functionality.
 *
 * @package Marginalia
 */

/**
 * Class to handle public-facing functionality.
 */
class Marginalia_Public {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue public styles.
	 */
	public function enqueue_styles() {
		// Only enqueue on book pages or when displaying book patterns.
		if ( is_singular( 'book' ) || is_post_type_archive( 'book' ) || is_tax( 'reading_status' ) ) {
			wp_enqueue_style(
				'marginalia-public-css',
				MARGINALIA_PLUGIN_URL . 'public/css/marginalia-public.css',
				array(),
				MARGINALIA_VERSION
			);
		}
	}
}
