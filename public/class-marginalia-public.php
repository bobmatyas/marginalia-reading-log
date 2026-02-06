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
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register the star rating dynamic block.
	 */
	public function register_block() {
		wp_register_script(
			'marginalia-star-rating-block',
			MARGINALIA_PLUGIN_URL . 'public/js/marginalia-star-rating-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render' ),
			MARGINALIA_VERSION,
			true
		);

		wp_register_style(
			'marginalia-public-css',
			MARGINALIA_PLUGIN_URL . 'public/css/marginalia-public.css',
			array(),
			MARGINALIA_VERSION
		);

		register_block_type(
			'marginalia/star-rating',
			array(
				'editor_script'   => 'marginalia-star-rating-block',
				'editor_style'    => 'marginalia-public-css',
				'style'           => 'marginalia-public-css',
				'render_callback' => array( $this, 'render_star_rating_block' ),
				'uses_context'    => array( 'postId' ),
			)
		);
	}

	/**
	 * Render callback for the star rating block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block HTML.
	 */
	public function render_star_rating_block( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID();
		$rating  = marginalia_get_book_rating( $post_id );

		if ( $rating < 1 ) {
			return '';
		}

		$stars = marginalia_display_stars( $rating, false );

		return '<div class="marginalia-book-rating">' . $stars . '</div>';
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
