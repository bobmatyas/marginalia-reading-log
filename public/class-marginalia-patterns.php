<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Block patterns registration.
 *
 * @package Marginalia
 */

/**
 * Class to register block patterns for book displays.
 */
class Marginalia_Patterns {

	/**
	 * Reading status term IDs cache.
	 *
	 * @var array
	 */
	private $term_ids = array();

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_pattern_category' ) );
		add_action( 'init', array( $this, 'register_patterns' ), 20 );
	}

	/**
	 * Register pattern category.
	 */
	public function register_pattern_category() {
		register_block_pattern_category(
			'marginalia-book-lists',
			array(
				'label'       => __( 'Book Lists', 'marginalia-reading-log' ),
				'description' => __( 'Patterns for displaying book collections and reading lists.', 'marginalia-reading-log' ),
			)
		);
	}

	/**
	 * Get term ID by slug.
	 *
	 * @param string $slug Term slug.
	 * @return int Term ID or 0 if not found.
	 */
	private function get_term_id( $slug ) {
		if ( empty( $this->term_ids ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'reading_status',
					'hide_empty' => false,
				)
			);

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$this->term_ids[ $term->slug ] = $term->term_id;
				}
			}
		}

		return isset( $this->term_ids[ $slug ] ) ? $this->term_ids[ $slug ] : 0;
	}

	/**
	 * Register block patterns.
	 */
	public function register_patterns() {
		$this->register_currently_reading_pattern();
		$this->register_to_read_pattern();
		$this->register_read_books_pattern();
		$this->register_single_book_pattern();
		$this->register_book_grid_pattern();
	}

	/**
	 * Register "Currently Reading Books" pattern.
	 */
	private function register_currently_reading_pattern() {
		$term_id = $this->get_term_id( 'currently-reading' );

		$content = '<!-- wp:group {"className":"marginalia-book-list-currently-reading"} -->
<div class="wp-block-group marginalia-book-list-currently-reading">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'Currently Reading', 'marginalia-reading-log' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":' . wp_unique_id() . ',"query":{"perPage":10,"pages":0,"offset":0,"postType":"book","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"reading_status":[' . $term_id . ']}},"namespace":"marginalia"} -->
<div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"className":"marginalia-book-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group marginalia-book-card">
<!-- wp:post-featured-image {"isLink":true,"width":"100px","height":"150px","className":"marginalia-book-cover"} /-->

<!-- wp:group {"className":"marginalia-book-info","layout":{"type":"constrained"}} -->
<div class="wp-block-group marginalia-book-info">
<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'No books currently being read.', 'marginalia-reading-log' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'marginalia/currently-reading-books',
			array(
				'title'       => __( 'Currently Reading Books', 'marginalia-reading-log' ),
				'description' => __( 'Display books with "Currently Reading" status.', 'marginalia-reading-log' ),
				'content'     => $content,
				'categories'  => array( 'marginalia-book-lists' ),
				'keywords'    => array( 'books', 'reading', 'current', 'list' ),
			)
		);
	}

	/**
	 * Register "Books to Read" pattern.
	 */
	private function register_to_read_pattern() {
		$term_id = $this->get_term_id( 'to-read' );

		$content = '<!-- wp:group {"className":"marginalia-book-list-to-read"} -->
<div class="wp-block-group marginalia-book-list-to-read">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'To Read', 'marginalia-reading-log' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":' . wp_unique_id() . ',"query":{"perPage":10,"pages":0,"offset":0,"postType":"book","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"reading_status":[' . $term_id . ']}},"namespace":"marginalia"} -->
<div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"className":"marginalia-book-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group marginalia-book-card">
<!-- wp:post-featured-image {"isLink":true,"width":"100px","height":"150px","className":"marginalia-book-cover"} /-->

<!-- wp:group {"className":"marginalia-book-info","layout":{"type":"constrained"}} -->
<div class="wp-block-group marginalia-book-info">
<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'No books in the reading list.', 'marginalia-reading-log' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'marginalia/to-read-books',
			array(
				'title'       => __( 'Books to Read', 'marginalia-reading-log' ),
				'description' => __( 'Display books with "To Read" status.', 'marginalia-reading-log' ),
				'content'     => $content,
				'categories'  => array( 'marginalia-book-lists' ),
				'keywords'    => array( 'books', 'reading', 'tbr', 'list', 'want' ),
			)
		);
	}

	/**
	 * Register "Read Books" pattern.
	 */
	private function register_read_books_pattern() {
		$term_id = $this->get_term_id( 'read' );

		$content = '<!-- wp:group {"className":"marginalia-book-list-read"} -->
<div class="wp-block-group marginalia-book-list-read">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'Books I\'ve Read', 'marginalia-reading-log' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":' . wp_unique_id() . ',"query":{"perPage":10,"pages":0,"offset":0,"postType":"book","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"reading_status":[' . $term_id . ']}},"namespace":"marginalia"} -->
<div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"className":"marginalia-book-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group marginalia-book-card">
<!-- wp:post-featured-image {"isLink":true,"width":"100px","height":"150px","className":"marginalia-book-cover"} /-->

<!-- wp:group {"className":"marginalia-book-info","layout":{"type":"constrained"}} -->
<div class="wp-block-group marginalia-book-info">
<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->

<!-- wp:marginalia/star-rating /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'No books read yet.', 'marginalia-reading-log' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'marginalia/read-books',
			array(
				'title'       => __( 'Read Books', 'marginalia-reading-log' ),
				'description' => __( 'Display books with "Read" status.', 'marginalia-reading-log' ),
				'content'     => $content,
				'categories'  => array( 'marginalia-book-lists' ),
				'keywords'    => array( 'books', 'reading', 'finished', 'completed', 'list' ),
			)
		);
	}

	/**
	 * Register "Book Grid" pattern (no taxonomy filter).
	 */
	private function register_book_grid_pattern() {
		$content = '<!-- wp:group {"className":"marginalia-book-grid-container"} -->
<div class="wp-block-group marginalia-book-grid-container">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'My Books', 'marginalia-reading-log' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":' . wp_unique_id() . ',"query":{"perPage":12,"pages":0,"offset":0,"postType":"book","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"namespace":"marginalia"} -->
<div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"grid","columnCount":4}} -->
<!-- wp:group {"className":"marginalia-book-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group marginalia-book-card">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"2/3","className":"marginalia-book-cover"} /-->
<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"small"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'No books found.', 'marginalia-reading-log' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'marginalia/book-grid',
			array(
				'title'       => __( 'Book Grid', 'marginalia-reading-log' ),
				'description' => __( 'Display all books in a grid layout. Use the Query Loop filters to narrow by reading status.', 'marginalia-reading-log' ),
				'content'     => $content,
				'categories'  => array( 'marginalia-book-lists' ),
				'keywords'    => array( 'books', 'grid', 'library', 'collection' ),
			)
		);
	}

	/**
	 * Register "Single Book Display" pattern.
	 */
	private function register_single_book_pattern() {
		$content = '<!-- wp:group {"className":"marginalia-single-book"} -->
<div class="wp-block-group marginalia-single-book">
<!-- wp:group {"className":"marginalia-single-book-header","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group marginalia-single-book-header">

<!-- wp:group {"className":"marginalia-single-book-cover"} -->
<div class="wp-block-group marginalia-single-book-cover">
<!-- wp:post-featured-image {"width":"250px"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"marginalia-single-book-details","layout":{"type":"constrained"}} -->
<div class="wp-block-group marginalia-single-book-details">
<!-- wp:post-title {"level":1,"className":"marginalia-single-book-title"} /-->

<!-- wp:post-terms {"term":"reading_status","className":"marginalia-reading-status-badge"} /-->

<!-- wp:marginalia/star-rating /-->
</div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->

<!-- wp:group {"className":"marginalia-review"} -->
<div class="wp-block-group marginalia-review">
<!-- wp:heading {"level":2,"className":"marginalia-review-heading"} -->
<h2 class="wp-block-heading marginalia-review-heading">' . esc_html__( 'My Review', 'marginalia-reading-log' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:post-content /-->
</div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->';

		register_block_pattern(
			'marginalia/single-book-display',
			array(
				'title'       => __( 'Single Book Display', 'marginalia-reading-log' ),
				'description' => __( 'Full display for a single book with cover, details, and review.', 'marginalia-reading-log' ),
				'content'     => $content,
				'categories'  => array( 'marginalia-book-lists' ),
				'keywords'    => array( 'book', 'single', 'review', 'display' ),
			)
		);
	}
}
