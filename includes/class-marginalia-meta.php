<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Post meta registration.
 *
 * @package Marginalia
 */

/**
 * Class to register and manage book post meta.
 */
class Marginalia_Meta {

	/**
	 * Meta fields configuration.
	 *
	 * @var array
	 */
	private $meta_fields = array();

	/**
	 * Initialize the class.
	 */
	public function init() {
		$this->setup_meta_fields();
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Setup meta fields configuration.
	 */
	private function setup_meta_fields() {
		$this->meta_fields = array(
			'_marginalia_author'           => array(
				'type'              => 'string',
				'description'       => __( 'Book author', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_marginalia_isbn_10'          => array(
				'type'              => 'string',
				'description'       => __( 'ISBN-10', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_isbn' ),
				'show_in_rest'      => true,
			),
			'_marginalia_isbn_13'          => array(
				'type'              => 'string',
				'description'       => __( 'ISBN-13', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_isbn' ),
				'show_in_rest'      => true,
			),
			'_marginalia_oclc'             => array(
				'type'              => 'string',
				'description'       => __( 'OCLC Number', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_marginalia_publication_date' => array(
				'type'              => 'string',
				'description'       => __( 'Publication date', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_marginalia_publisher'        => array(
				'type'              => 'string',
				'description'       => __( 'Publisher', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_marginalia_openlibrary_key'  => array(
				'type'              => 'string',
				'description'       => __( 'OpenLibrary Key', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_marginalia_page_count'       => array(
				'type'              => 'integer',
				'description'       => __( 'Number of pages', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			),
			'_marginalia_date_started'     => array(
				'type'              => 'string',
				'description'       => __( 'Date started reading', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_date' ),
				'show_in_rest'      => true,
			),
			'_marginalia_date_finished'    => array(
				'type'              => 'string',
				'description'       => __( 'Date finished reading', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_date' ),
				'show_in_rest'      => true,
			),
			'_marginalia_star_rating'      => array(
				'type'              => 'integer',
				'description'       => __( 'Star rating (0-5)', 'marginalia-reading-log' ),
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_rating' ),
				'show_in_rest'      => true,
			),
		);
	}

	/**
	 * Register post meta fields.
	 */
	public function register_meta() {
		foreach ( $this->meta_fields as $meta_key => $args ) {
			register_post_meta( 'book', $meta_key, $args );
		}
	}

	/**
	 * Sanitize ISBN field.
	 *
	 * @param string $value The ISBN value.
	 * @return string Sanitized ISBN.
	 */
	public function sanitize_isbn( $value ) {
		// Remove any non-alphanumeric characters except hyphens.
		$value = preg_replace( '/[^0-9Xx-]/', '', $value );
		// Remove hyphens for storage.
		$value = str_replace( '-', '', $value );
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize date field.
	 *
	 * @param string $value The date value.
	 * @return string Sanitized date in Y-m-d format or empty string.
	 */
	public function sanitize_date( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$timestamp = strtotime( $value );
		if ( false === $timestamp ) {
			return '';
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Sanitize rating field.
	 *
	 * @param mixed $value The rating value.
	 * @return int Sanitized rating (0-5).
	 */
	public function sanitize_rating( $value ) {
		$rating = absint( $value );
		return min( 5, max( 0, $rating ) );
	}

	/**
	 * Get all meta fields configuration.
	 *
	 * @return array Meta fields configuration.
	 */
	public function get_meta_fields() {
		return $this->meta_fields;
	}

	/**
	 * Check if a book with the given identifier already exists.
	 *
	 * @param string $isbn_10         ISBN-10.
	 * @param string $isbn_13         ISBN-13.
	 * @param string $openlibrary_key OpenLibrary key.
	 * @param int    $exclude_post_id Post ID to exclude from check.
	 * @return int|false Post ID if duplicate found, false otherwise.
	 */
	public static function check_duplicate( $isbn_10 = '', $isbn_13 = '', $openlibrary_key = '', $exclude_post_id = 0 ) {
		global $wpdb;

		$meta_conditions = array();
		$prepare_args   = array();

		if ( ! empty( $isbn_10 ) ) {
			$meta_conditions[] = '(pm.meta_key = %s AND pm.meta_value = %s)';
			$prepare_args[]    = '_marginalia_isbn_10';
			$prepare_args[]    = $isbn_10;
		}

		if ( ! empty( $isbn_13 ) ) {
			$meta_conditions[] = '(pm.meta_key = %s AND pm.meta_value = %s)';
			$prepare_args[]    = '_marginalia_isbn_13';
			$prepare_args[]    = $isbn_13;
		}

		if ( ! empty( $openlibrary_key ) ) {
			$meta_conditions[] = '(pm.meta_key = %s AND pm.meta_value = %s)';
			$prepare_args[]    = '_marginalia_openlibrary_key';
			$prepare_args[]    = $openlibrary_key;
		}

		if ( empty( $meta_conditions ) ) {
			return false;
		}

		$meta_where = implode( ' OR ', $meta_conditions );

		$sql = "SELECT p.ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'book'
			AND p.post_status != 'trash'
			AND ({$meta_where})";

		if ( $exclude_post_id > 0 ) {
			$sql           .= ' AND p.ID != %d';
			$prepare_args[] = $exclude_post_id;
		}

		$sql .= ' LIMIT 1';

		$post_id = $wpdb->get_var(
			$wpdb->prepare( $sql, $prepare_args ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders are used throughout.
		);

		return $post_id ? absint( $post_id ) : false;
	}
}
