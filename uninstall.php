<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Marginalia
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * Note: This only removes plugin options and transients.
 * Book posts and their meta are preserved to prevent data loss.
 * To completely remove all data, manually delete the 'book' posts
 * and the 'reading_status' taxonomy terms.
 */

// Delete transients.
delete_transient( 'marginalia_activation_notice' );

// Delete any duplicate transients.
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '_transient_marginalia_%'
	OR option_name LIKE '_transient_timeout_marginalia_%'"
);

// Optional: Uncomment the following to delete all book data on uninstall.
// WARNING: This will permanently delete all books and reading data!
/*
// Delete all book posts.
$books = get_posts(
	array(
		'post_type'      => 'book',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $books as $book_id ) {
	wp_delete_post( $book_id, true );
}

// Delete reading status terms.
$terms = get_terms(
	array(
		'taxonomy'   => 'reading_status',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

foreach ( $terms as $term_id ) {
	wp_delete_term( $term_id, 'reading_status' );
}
*/

// Flush rewrite rules.
flush_rewrite_rules();
