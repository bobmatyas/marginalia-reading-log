<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Custom post type registration.
 *
 * @package Marginalia
 */

/**
 * Class to register the book custom post type.
 */
class Marginalia_Post_Type {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register the book custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Books', 'Post type general name', 'marginalia-reading-log' ),
			'singular_name'         => _x( 'Book', 'Post type singular name', 'marginalia-reading-log' ),
			'menu_name'             => _x( 'Books', 'Admin Menu text', 'marginalia-reading-log' ),
			'name_admin_bar'        => _x( 'Book', 'Add New on Toolbar', 'marginalia-reading-log' ),
			'add_new'               => __( 'Add New', 'marginalia-reading-log' ),
			'add_new_item'          => __( 'Add New Book', 'marginalia-reading-log' ),
			'new_item'              => __( 'New Book', 'marginalia-reading-log' ),
			'edit_item'             => __( 'Edit Book', 'marginalia-reading-log' ),
			'view_item'             => __( 'View Book', 'marginalia-reading-log' ),
			'all_items'             => __( 'All Books', 'marginalia-reading-log' ),
			'search_items'          => __( 'Search Books', 'marginalia-reading-log' ),
			'parent_item_colon'     => __( 'Parent Books:', 'marginalia-reading-log' ),
			'not_found'             => __( 'No books found.', 'marginalia-reading-log' ),
			'not_found_in_trash'    => __( 'No books found in Trash.', 'marginalia-reading-log' ),
			'featured_image'        => _x( 'Book Cover', 'Overrides the "Featured Image" phrase', 'marginalia-reading-log' ),
			'set_featured_image'    => _x( 'Set book cover', 'Overrides the "Set featured image" phrase', 'marginalia-reading-log' ),
			'remove_featured_image' => _x( 'Remove book cover', 'Overrides the "Remove featured image" phrase', 'marginalia-reading-log' ),
			'use_featured_image'    => _x( 'Use as book cover', 'Overrides the "Use as featured image" phrase', 'marginalia-reading-log' ),
			'archives'              => _x( 'Book archives', 'The post type archive label', 'marginalia-reading-log' ),
			'insert_into_item'      => _x( 'Insert into book', 'Overrides the "Insert into post" phrase', 'marginalia-reading-log' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this book', 'Overrides the "Uploaded to this post" phrase', 'marginalia-reading-log' ),
			'filter_items_list'     => _x( 'Filter books list', 'Screen reader text', 'marginalia-reading-log' ),
			'items_list_navigation' => _x( 'Books list navigation', 'Screen reader text', 'marginalia-reading-log' ),
			'items_list'            => _x( 'Books list', 'Screen reader text', 'marginalia-reading-log' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'book' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-book',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'show_in_rest'       => true,
			'rest_base'          => 'books',
		);

		register_post_type( 'book', $args );
	}
}
