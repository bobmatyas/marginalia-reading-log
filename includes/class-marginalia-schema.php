<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Schema.org structured data.
 *
 * @package Marginalia
 */

/**
 * Class to generate Schema.org JSON-LD structured data.
 */
class Marginalia_Schema {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output_schema' ), 99 );
	}

	/**
	 * Output Schema.org JSON-LD for single book pages.
	 */
	public function output_schema() {
		if ( ! is_singular( 'book' ) ) {
			return;
		}

		$post_id = get_the_ID();
		$schema  = $this->generate_book_schema( $post_id );

		if ( empty( $schema ) ) {
			return;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safely encoded.
		);
	}

	/**
	 * Generate Book schema for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return array Schema data.
	 */
	public function generate_book_schema( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || 'book' !== $post->post_type ) {
			return array();
		}

		// Get meta data.
		$author           = get_post_meta( $post_id, '_marginalia_author', true );
		$isbn_10          = get_post_meta( $post_id, '_marginalia_isbn_10', true );
		$isbn_13          = get_post_meta( $post_id, '_marginalia_isbn_13', true );
		$page_count       = get_post_meta( $post_id, '_marginalia_page_count', true );
		$publisher        = get_post_meta( $post_id, '_marginalia_publisher', true );
		$publication_date = get_post_meta( $post_id, '_marginalia_publication_date', true );
		$star_rating      = get_post_meta( $post_id, '_marginalia_star_rating', true );
		$date_finished    = get_post_meta( $post_id, '_marginalia_date_finished', true );

		// Build base Book schema.
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Book',
			'name'     => get_the_title( $post_id ),
			'url'      => get_permalink( $post_id ),
		);

		// Add author.
		if ( ! empty( $author ) ) {
			$schema['author'] = array(
				'@type' => 'Person',
				'name'  => $author,
			);
		}

		// Add ISBN (prefer ISBN-13).
		if ( ! empty( $isbn_13 ) ) {
			$schema['isbn'] = $isbn_13;
		} elseif ( ! empty( $isbn_10 ) ) {
			$schema['isbn'] = $isbn_10;
		}

		// Add page count.
		if ( ! empty( $page_count ) ) {
			$schema['numberOfPages'] = absint( $page_count );
		}

		// Add publisher.
		if ( ! empty( $publisher ) ) {
			$schema['publisher'] = array(
				'@type' => 'Organization',
				'name'  => $publisher,
			);
		}

		// Add publication date.
		if ( ! empty( $publication_date ) ) {
			$schema['datePublished'] = $publication_date;
		}

		// Add featured image.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
			if ( $image_url ) {
				$schema['image'] = $image_url;
			}
		}

		// Add review if review text exists.
		$review_text = $post->post_content;
		if ( ! empty( $review_text ) || ! empty( $star_rating ) ) {
			$review = array(
				'@type' => 'Review',
			);

			// Add review author (site name or admin user).
			$site_name = get_bloginfo( 'name' );
			if ( ! empty( $site_name ) ) {
				$review['author'] = array(
					'@type' => 'Person',
					'name'  => $site_name,
				);
			}

			// Add review body.
			if ( ! empty( $review_text ) ) {
				$review['reviewBody'] = wp_strip_all_tags( $review_text );
			}

			// Add review rating.
			if ( ! empty( $star_rating ) ) {
				$review['reviewRating'] = array(
					'@type'       => 'Rating',
					'ratingValue' => absint( $star_rating ),
					'bestRating'  => 5,
					'worstRating' => 0,
				);
			}

			// Add review date (date finished or post published date).
			if ( ! empty( $date_finished ) ) {
				$review['datePublished'] = $date_finished;
			} else {
				$review['datePublished'] = get_the_date( 'Y-m-d', $post_id );
			}

			$schema['review'] = $review;
		}

		/**
		 * Filter the book schema data.
		 *
		 * @param array $schema  The schema data.
		 * @param int   $post_id The post ID.
		 */
		return apply_filters( 'marginalia/book_schema', $schema, $post_id );
	}
}
