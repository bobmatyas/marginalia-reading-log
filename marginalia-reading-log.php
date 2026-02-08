<?php
/**
 * Plugin Name: Marginalia
 * Description: A personal reading log for WordPress. Track books you're reading with reviews, ratings, and reading progress.
 * Version: 1.0.0
 * Author: lastsplash
 * Author URI: https://bobmatyas.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: marginalia-reading-log
 * Domain Path: /languages
 *
 * @package Marginalia
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'MARGINALIA_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'MARGINALIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'MARGINALIA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'MARGINALIA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function marginalia_activate() {
	require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-activator.php';
	Marginalia_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function marginalia_deactivate() {
	require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-deactivator.php';
	Marginalia_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'marginalia_activate' );
register_deactivation_hook( __FILE__, 'marginalia_deactivate' );

/**
 * Include required files.
 */
require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-post-type.php';
require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-taxonomy.php';
require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-meta.php';
require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-openlibrary.php';
require_once MARGINALIA_PLUGIN_DIR . 'includes/class-marginalia-schema.php';

/**
 * Include admin files.
 */
if ( is_admin() ) {
	require_once MARGINALIA_PLUGIN_DIR . 'admin/class-marginalia-admin.php';
	require_once MARGINALIA_PLUGIN_DIR . 'admin/class-marginalia-meta-box.php';
	require_once MARGINALIA_PLUGIN_DIR . 'admin/class-marginalia-admin-columns.php';
	require_once MARGINALIA_PLUGIN_DIR . 'admin/class-marginalia-import.php';
}

/**
 * Include public files.
 */
require_once MARGINALIA_PLUGIN_DIR . 'public/class-marginalia-public.php';
require_once MARGINALIA_PLUGIN_DIR . 'public/class-marginalia-patterns.php';

/**
 * Initialize the plugin.
 */
function marginalia_init() {
	// Initialize post type.
	$post_type = new Marginalia_Post_Type();
	$post_type->init();

	// Initialize taxonomy.
	$taxonomy = new Marginalia_Taxonomy();
	$taxonomy->init();

	// Initialize meta fields.
	$meta = new Marginalia_Meta();
	$meta->init();

	// Initialize OpenLibrary integration.
	$openlibrary = new Marginalia_OpenLibrary();
	$openlibrary->init();

	// Initialize schema markup.
	$schema = new Marginalia_Schema();
	$schema->init();

	// Initialize admin.
	if ( is_admin() ) {
		$admin = new Marginalia_Admin();
		$admin->init();

		$meta_box = new Marginalia_Meta_Box();
		$meta_box->init();

		$admin_columns = new Marginalia_Admin_Columns();
		$admin_columns->init();

		$import = new Marginalia_Import();
		$import->init();
	}

	// Initialize public.
	$public = new Marginalia_Public();
	$public->init();

	// Initialize patterns.
	$patterns = new Marginalia_Patterns();
	$patterns->init();
}
add_action( 'plugins_loaded', 'marginalia_init' );

/**
 * Get a book's star rating.
 *
 * @param int $post_id The post ID.
 * @return int The star rating (0-5).
 */
function marginalia_get_book_rating( $post_id = null ) {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	$rating = get_post_meta( $post_id, '_marginalia_star_rating', true );

	return absint( $rating );
}

/**
 * Get a book's reading status.
 *
 * @param int $post_id The post ID.
 * @return string|false The reading status term name or false.
 */
function marginalia_get_reading_status( $post_id = null ) {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	$terms = wp_get_object_terms( $post_id, 'reading_status', array( 'fields' => 'names' ) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}

	return $terms[0];
}

/**
 * Get a book's author.
 *
 * @param int $post_id The post ID.
 * @return string The book author.
 */
function marginalia_get_book_author( $post_id = null ) {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	return get_post_meta( $post_id, '_marginalia_author', true );
}

/**
 * Display star rating HTML.
 *
 * @param int  $rating The rating value (0-5).
 * @param bool $echo   Whether to echo or return.
 * @return string|void The HTML output or void if echoing.
 */
function marginalia_display_stars( $rating = null, $echo = true ) {
	if ( null === $rating ) {
		$rating = marginalia_get_book_rating();
	}

	$rating = absint( $rating );
	$rating = min( 5, max( 0, $rating ) );

	$output = '<span class="marginalia-rating-stars" aria-label="' . sprintf(
		/* translators: %d: number of stars */
		esc_attr__( '%d out of 5 stars', 'marginalia-reading-log' ),
		$rating
	) . '">';

	for ( $i = 1; $i <= 5; $i++ ) {
		if ( $i <= $rating ) {
			$output .= '<span class="marginalia-star marginalia-star-filled" aria-hidden="true">★</span>';
		} else {
			$output .= '<span class="marginalia-star marginalia-star-empty" aria-hidden="true">☆</span>';
		}
	}

	$output .= '</span>';

	if ( $echo ) {
		echo wp_kses_post( $output );
	} else {
		return $output;
	}
}
