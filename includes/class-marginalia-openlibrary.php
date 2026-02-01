<?php
/**
 * OpenLibrary API integration.
 *
 * @package Marginalia
 */

/**
 * Class to handle OpenLibrary API interactions.
 */
class Marginalia_OpenLibrary {

	/**
	 * OpenLibrary API base URL.
	 *
	 * @var string
	 */
	const API_BASE_URL = 'https://openlibrary.org';

	/**
	 * OpenLibrary Covers API base URL.
	 *
	 * @var string
	 */
	const COVERS_BASE_URL = 'https://covers.openlibrary.org';

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'wp_ajax_marginalia_search_books', array( $this, 'ajax_search_books' ) );
		add_action( 'wp_ajax_marginalia_get_book_details', array( $this, 'ajax_get_book_details' ) );
		add_action( 'wp_ajax_marginalia_import_cover', array( $this, 'ajax_import_cover' ) );
	}

	/**
	 * Search books via AJAX.
	 */
	public function ajax_search_books() {
		check_ajax_referer( 'marginalia_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'marginalia-reading-log' ) ) );
		}

		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'title';

		if ( empty( $query ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a search query.', 'marginalia-reading-log' ) ) );
		}

		$results = $this->search_books( $query, $type );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
		}

		wp_send_json_success( $results );
	}

	/**
	 * Search books on OpenLibrary.
	 *
	 * @param string $query Search query.
	 * @param string $type  Search type (title, isbn, author).
	 * @return array|WP_Error Search results or error.
	 */
	public function search_books( $query, $type = 'title' ) {
		$search_param = 'q';
		if ( 'isbn' === $type ) {
			$search_param = 'isbn';
		} elseif ( 'author' === $type ) {
			$search_param = 'author';
		} elseif ( 'title' === $type ) {
			$search_param = 'title';
		}

		$url = add_query_arg(
			array(
				$search_param => rawurlencode( $query ),
				'limit'       => 20,
			),
			self::API_BASE_URL . '/search.json'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent' => 'Marginalia WordPress Plugin/' . MARGINALIA_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error(
				'api_error',
				/* translators: %d: HTTP response code */
				sprintf( __( 'OpenLibrary API returned error code: %d', 'marginalia-reading-log' ), $code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['docs'] ) ) {
			return array();
		}

		$results = array();
		foreach ( $data['docs'] as $doc ) {
			$cover_id = isset( $doc['cover_i'] ) ? absint( $doc['cover_i'] ) : 0;
			$results[] = array(
				'key'              => isset( $doc['key'] ) ? sanitize_text_field( $doc['key'] ) : '',
				'title'            => isset( $doc['title'] ) ? sanitize_text_field( $doc['title'] ) : '',
				'author'           => isset( $doc['author_name'][0] ) ? sanitize_text_field( $doc['author_name'][0] ) : '',
				'authors'          => isset( $doc['author_name'] ) ? array_map( 'sanitize_text_field', $doc['author_name'] ) : array(),
				'first_publish_year' => isset( $doc['first_publish_year'] ) ? absint( $doc['first_publish_year'] ) : '',
				'isbn'             => isset( $doc['isbn'][0] ) ? sanitize_text_field( $doc['isbn'][0] ) : '',
				'isbns'            => isset( $doc['isbn'] ) ? array_map( 'sanitize_text_field', array_slice( $doc['isbn'], 0, 5 ) ) : array(),
				'publisher'        => isset( $doc['publisher'][0] ) ? sanitize_text_field( $doc['publisher'][0] ) : '',
				'cover_id'         => $cover_id,
				'cover_url'        => $cover_id ? $this->get_cover_url( $cover_id, 'M' ) : '',
				'edition_count'    => isset( $doc['edition_count'] ) ? absint( $doc['edition_count'] ) : 0,
			);
		}

		return $results;
	}

	/**
	 * Get book details via AJAX.
	 */
	public function ajax_get_book_details() {
		check_ajax_referer( 'marginalia_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'marginalia-reading-log' ) ) );
		}

		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

		if ( empty( $key ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid book key.', 'marginalia-reading-log' ) ) );
		}

		$details = $this->get_book_details( $key );

		if ( is_wp_error( $details ) ) {
			wp_send_json_error( array( 'message' => $details->get_error_message() ) );
		}

		wp_send_json_success( $details );
	}

	/**
	 * Get detailed book information from OpenLibrary.
	 *
	 * @param string $key OpenLibrary work key (e.g., /works/OL123W).
	 * @return array|WP_Error Book details or error.
	 */
	public function get_book_details( $key ) {
		// Get work details.
		$work_url  = self::API_BASE_URL . $key . '.json';
		$work_response = wp_remote_get(
			$work_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent' => 'Marginalia WordPress Plugin/' . MARGINALIA_VERSION,
				),
			)
		);

		if ( is_wp_error( $work_response ) ) {
			return $work_response;
		}

		$work_body = wp_remote_retrieve_body( $work_response );
		$work_data = json_decode( $work_body, true );

		// Get editions for this work to get ISBN and other edition-specific data.
		$editions_url = self::API_BASE_URL . $key . '/editions.json?limit=5';
		$editions_response = wp_remote_get(
			$editions_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent' => 'Marginalia WordPress Plugin/' . MARGINALIA_VERSION,
				),
			)
		);

		$edition_data = array();
		if ( ! is_wp_error( $editions_response ) ) {
			$editions_body = wp_remote_retrieve_body( $editions_response );
			$editions      = json_decode( $editions_body, true );
			if ( ! empty( $editions['entries'][0] ) ) {
				$edition_data = $editions['entries'][0];
			}
		}

		// Extract author names.
		$authors = array();
		if ( ! empty( $work_data['authors'] ) ) {
			foreach ( $work_data['authors'] as $author_ref ) {
				$author_key = isset( $author_ref['author']['key'] ) ? $author_ref['author']['key'] : '';
				if ( $author_key ) {
					$author_name = $this->get_author_name( $author_key );
					if ( $author_name ) {
						$authors[] = $author_name;
					}
				}
			}
		}

		// Extract cover ID.
		$cover_id = 0;
		if ( ! empty( $work_data['covers'][0] ) ) {
			$cover_id = absint( $work_data['covers'][0] );
		} elseif ( ! empty( $edition_data['covers'][0] ) ) {
			$cover_id = absint( $edition_data['covers'][0] );
		}

		// Extract ISBN.
		$isbn_10 = '';
		$isbn_13 = '';
		if ( ! empty( $edition_data['isbn_10'][0] ) ) {
			$isbn_10 = sanitize_text_field( $edition_data['isbn_10'][0] );
		}
		if ( ! empty( $edition_data['isbn_13'][0] ) ) {
			$isbn_13 = sanitize_text_field( $edition_data['isbn_13'][0] );
		}

		// Extract OCLC.
		$oclc = '';
		if ( ! empty( $edition_data['oclc_numbers'][0] ) ) {
			$oclc = sanitize_text_field( $edition_data['oclc_numbers'][0] );
		}

		$details = array(
			'key'              => sanitize_text_field( $key ),
			'title'            => isset( $work_data['title'] ) ? sanitize_text_field( $work_data['title'] ) : '',
			'author'           => ! empty( $authors ) ? implode( ', ', $authors ) : '',
			'authors'          => $authors,
			'isbn_10'          => $isbn_10,
			'isbn_13'          => $isbn_13,
			'oclc'             => $oclc,
			'publisher'        => isset( $edition_data['publishers'][0] ) ? sanitize_text_field( $edition_data['publishers'][0] ) : '',
			'publication_date' => isset( $edition_data['publish_date'] ) ? sanitize_text_field( $edition_data['publish_date'] ) : '',
			'page_count'       => isset( $edition_data['number_of_pages'] ) ? absint( $edition_data['number_of_pages'] ) : 0,
			'cover_id'         => $cover_id,
			'cover_url_small'  => $cover_id ? $this->get_cover_url( $cover_id, 'S' ) : '',
			'cover_url_medium' => $cover_id ? $this->get_cover_url( $cover_id, 'M' ) : '',
			'cover_url_large'  => $cover_id ? $this->get_cover_url( $cover_id, 'L' ) : '',
			'description'      => $this->extract_description( $work_data ),
		);

		return $details;
	}

	/**
	 * Get author name from OpenLibrary.
	 *
	 * @param string $author_key Author key (e.g., /authors/OL123A).
	 * @return string|false Author name or false on failure.
	 */
	private function get_author_name( $author_key ) {
		$url = self::API_BASE_URL . $author_key . '.json';

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'Marginalia WordPress Plugin/' . MARGINALIA_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : false;
	}

	/**
	 * Extract description from work data.
	 *
	 * @param array $work_data Work data from API.
	 * @return string Description text.
	 */
	private function extract_description( $work_data ) {
		if ( empty( $work_data['description'] ) ) {
			return '';
		}

		$description = $work_data['description'];

		// Description can be a string or an object with 'value' key.
		if ( is_array( $description ) && isset( $description['value'] ) ) {
			$description = $description['value'];
		}

		return wp_kses_post( $description );
	}

	/**
	 * Get cover image URL.
	 *
	 * @param int    $cover_id Cover ID.
	 * @param string $size     Size (S, M, L).
	 * @return string Cover URL.
	 */
	public function get_cover_url( $cover_id, $size = 'M' ) {
		$size = in_array( $size, array( 'S', 'M', 'L' ), true ) ? $size : 'M';
		return self::COVERS_BASE_URL . '/b/id/' . absint( $cover_id ) . '-' . $size . '.jpg';
	}

	/**
	 * Get cover URL by ISBN.
	 *
	 * @param string $isbn ISBN.
	 * @param string $size Size (S, M, L).
	 * @return string Cover URL.
	 */
	public function get_cover_url_by_isbn( $isbn, $size = 'L' ) {
		$size = in_array( $size, array( 'S', 'M', 'L' ), true ) ? $size : 'L';
		return self::COVERS_BASE_URL . '/b/isbn/' . sanitize_text_field( $isbn ) . '-' . $size . '.jpg';
	}

	/**
	 * Get cover URL by OpenLibrary key.
	 *
	 * @param string $key  OpenLibrary key.
	 * @param string $size Size (S, M, L).
	 * @return string Cover URL.
	 */
	public function get_cover_url_by_key( $key, $size = 'L' ) {
		$size = in_array( $size, array( 'S', 'M', 'L' ), true ) ? $size : 'L';
		// Extract the OLID from the key (e.g., /works/OL123W -> OL123W).
		$olid = basename( $key );
		return self::COVERS_BASE_URL . '/b/olid/' . sanitize_text_field( $olid ) . '-' . $size . '.jpg';
	}

	/**
	 * Import cover image via AJAX.
	 */
	public function ajax_import_cover() {
		check_ajax_referer( 'marginalia_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'marginalia-reading-log' ) ) );
		}

		$cover_url = isset( $_POST['cover_url'] ) ? esc_url_raw( wp_unslash( $_POST['cover_url'] ) ) : '';
		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		if ( empty( $cover_url ) ) {
			wp_send_json_error( array( 'message' => __( 'No cover URL provided.', 'marginalia-reading-log' ) ) );
		}

		$attachment_id = $this->import_cover_image( $cover_url, $post_id, $title );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_url( $attachment_id ),
			)
		);
	}

	/**
	 * Import cover image to WordPress Media Library.
	 *
	 * @param string $cover_url Remote cover URL.
	 * @param int    $post_id   Post ID to attach to.
	 * @param string $title     Book title for filename.
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function import_cover_image( $cover_url, $post_id = 0, $title = '' ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download the file.
		$tmp_file = download_url( $cover_url, 30 );

		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		// Check if the file is a valid image.
		$file_type = wp_check_filetype( $tmp_file );
		if ( empty( $file_type['type'] ) || ! str_starts_with( $file_type['type'], 'image/' ) ) {
			// Try to get the file type from the URL or check the actual file.
			$file_info = wp_check_filetype( basename( $cover_url ) );
			if ( empty( $file_info['type'] ) ) {
				// Force JPG since OpenLibrary returns JPG.
				$file_type = array(
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				);
			} else {
				$file_type = $file_info;
			}
		}

		// Prepare file array.
		$filename = ! empty( $title ) ? sanitize_file_name( $title . '-cover' ) : 'book-cover';
		$filename = $filename . '.' . ( $file_type['ext'] ? $file_type['ext'] : 'jpg' );

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp_file,
		);

		// Upload to media library.
		$attachment_id = media_handle_sideload( $file_array, $post_id );

		// Clean up temp file if sideload failed.
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp_file );
			return $attachment_id;
		}

		// Set as featured image if post_id is provided.
		if ( $post_id > 0 ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}

		return $attachment_id;
	}
}
