<?php
/**
 * Meta box functionality.
 *
 * @package Marginalia
 */

/**
 * Class to handle meta boxes for the book post type.
 */
class Marginalia_Meta_Box {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_book', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Add meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'marginalia-openlibrary-search',
			__( 'OpenLibrary Search', 'marginalia-reading-log' ),
			array( $this, 'render_openlibrary_search_box' ),
			'book',
			'normal',
			'high'
		);

		add_meta_box(
			'marginalia-book-details',
			__( 'Book Details', 'marginalia-reading-log' ),
			array( $this, 'render_book_details_box' ),
			'book',
			'normal',
			'high'
		);

		add_meta_box(
			'marginalia-reading-tracking',
			__( 'Reading Tracking', 'marginalia-reading-log' ),
			array( $this, 'render_reading_tracking_box' ),
			'book',
			'side',
			'default'
		);
	}

	/**
	 * Render OpenLibrary search meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_openlibrary_search_box( $post ) {
		?>
		<div class="marginalia-openlibrary-search">
			<div class="marginalia-search-form">
				<label for="marginalia-search-query" class="screen-reader-text">
					<?php esc_html_e( 'Search OpenLibrary', 'marginalia-reading-log' ); ?>
				</label>
				<input type="text"
					id="marginalia-search-query"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'Enter ISBN, title, or author...', 'marginalia-reading-log' ); ?>"
				/>
				<select id="marginalia-search-type">
					<option value="q"><?php esc_html_e( 'All Fields', 'marginalia-reading-log' ); ?></option>
					<option value="isbn"><?php esc_html_e( 'ISBN', 'marginalia-reading-log' ); ?></option>
					<option value="title"><?php esc_html_e( 'Title', 'marginalia-reading-log' ); ?></option>
					<option value="author"><?php esc_html_e( 'Author', 'marginalia-reading-log' ); ?></option>
				</select>
				<button type="button" id="marginalia-search-btn" class="button button-primary">
					<?php esc_html_e( 'Search', 'marginalia-reading-log' ); ?>
				</button>
			</div>
			<div id="marginalia-search-results" class="marginalia-search-results"></div>
			<div id="marginalia-search-status" class="marginalia-search-status"></div>
		</div>
		<?php
	}

	/**
	 * Render book details meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_book_details_box( $post ) {
		wp_nonce_field( 'marginalia_save_book_meta', 'marginalia_book_meta_nonce' );

		$author           = get_post_meta( $post->ID, '_marginalia_author', true );
		$isbn_10          = get_post_meta( $post->ID, '_marginalia_isbn_10', true );
		$isbn_13          = get_post_meta( $post->ID, '_marginalia_isbn_13', true );
		$oclc             = get_post_meta( $post->ID, '_marginalia_oclc', true );
		$publication_date = get_post_meta( $post->ID, '_marginalia_publication_date', true );
		$publisher        = get_post_meta( $post->ID, '_marginalia_publisher', true );
		$openlibrary_key  = get_post_meta( $post->ID, '_marginalia_openlibrary_key', true );
		$page_count       = get_post_meta( $post->ID, '_marginalia_page_count', true );
		?>
		<table class="form-table marginalia-meta-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="marginalia-author"><?php esc_html_e( 'Author', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-author"
							name="marginalia_author"
							value="<?php echo esc_attr( $author ); ?>"
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-isbn-10"><?php esc_html_e( 'ISBN-10', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-isbn-10"
							name="marginalia_isbn_10"
							value="<?php echo esc_attr( $isbn_10 ); ?>"
							class="regular-text"
							maxlength="13"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-isbn-13"><?php esc_html_e( 'ISBN-13', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-isbn-13"
							name="marginalia_isbn_13"
							value="<?php echo esc_attr( $isbn_13 ); ?>"
							class="regular-text"
							maxlength="17"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-oclc"><?php esc_html_e( 'OCLC Number', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-oclc"
							name="marginalia_oclc"
							value="<?php echo esc_attr( $oclc ); ?>"
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-publisher"><?php esc_html_e( 'Publisher', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-publisher"
							name="marginalia_publisher"
							value="<?php echo esc_attr( $publisher ); ?>"
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-publication-date"><?php esc_html_e( 'Publication Date', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-publication-date"
							name="marginalia_publication_date"
							value="<?php echo esc_attr( $publication_date ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'YYYY-MM-DD or text', 'marginalia-reading-log' ); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-page-count"><?php esc_html_e( 'Number of Pages', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="number"
							id="marginalia-page-count"
							name="marginalia_page_count"
							value="<?php echo esc_attr( $page_count ); ?>"
							class="small-text"
							min="0"
							step="1"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="marginalia-openlibrary-key"><?php esc_html_e( 'OpenLibrary Key', 'marginalia-reading-log' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="marginalia-openlibrary-key"
							name="marginalia_openlibrary_key"
							value="<?php echo esc_attr( $openlibrary_key ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( '/works/OL123W', 'marginalia-reading-log' ); ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'At least one of ISBN-10, ISBN-13, or OpenLibrary Key is required.', 'marginalia-reading-log' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render reading tracking meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_reading_tracking_box( $post ) {
		$date_started  = get_post_meta( $post->ID, '_marginalia_date_started', true );
		$date_finished = get_post_meta( $post->ID, '_marginalia_date_finished', true );
		$star_rating   = get_post_meta( $post->ID, '_marginalia_star_rating', true );
		?>
		<div class="marginalia-reading-tracking">
			<p>
				<label for="marginalia-date-started"><?php esc_html_e( 'Date Started', 'marginalia-reading-log' ); ?></label>
				<input type="date"
					id="marginalia-date-started"
					name="marginalia_date_started"
					value="<?php echo esc_attr( $date_started ); ?>"
					class="widefat"
				/>
			</p>
			<p>
				<label for="marginalia-date-finished"><?php esc_html_e( 'Date Finished', 'marginalia-reading-log' ); ?></label>
				<input type="date"
					id="marginalia-date-finished"
					name="marginalia_date_finished"
					value="<?php echo esc_attr( $date_finished ); ?>"
					class="widefat"
				/>
			</p>
			<p>
				<label for="marginalia-star-rating"><?php esc_html_e( 'Star Rating', 'marginalia-reading-log' ); ?></label>
			</p>
			<div class="marginalia-rating-input">
				<?php for ( $i = 0; $i <= 5; $i++ ) : ?>
					<label class="marginalia-rating-label">
						<input type="radio"
							name="marginalia_star_rating"
							value="<?php echo esc_attr( $i ); ?>"
							<?php checked( (int) $star_rating, $i ); ?>
						/>
						<?php if ( 0 === $i ) : ?>
							<span class="marginalia-rating-text"><?php esc_html_e( 'Unrated', 'marginalia-reading-log' ); ?></span>
						<?php else : ?>
							<span class="marginalia-rating-stars" aria-hidden="true">
								<?php echo esc_html( str_repeat( '★', $i ) . str_repeat( '☆', 5 - $i ) ); ?>
							</span>
							<span class="screen-reader-text">
								<?php
								printf(
									/* translators: %d: number of stars */
									esc_html( _n( '%d star', '%d stars', $i, 'marginalia-reading-log' ) ),
									$i
								);
								?>
							</span>
						<?php endif; ?>
					</label>
				<?php endfor; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Verify nonce.
		if ( ! isset( $_POST['marginalia_book_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['marginalia_book_meta_nonce'] ) ), 'marginalia_save_book_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Define fields to save.
		$text_fields = array(
			'marginalia_author'           => '_marginalia_author',
			'marginalia_isbn_10'          => '_marginalia_isbn_10',
			'marginalia_isbn_13'          => '_marginalia_isbn_13',
			'marginalia_oclc'             => '_marginalia_oclc',
			'marginalia_publication_date' => '_marginalia_publication_date',
			'marginalia_publisher'        => '_marginalia_publisher',
			'marginalia_openlibrary_key'  => '_marginalia_openlibrary_key',
		);

		$int_fields = array(
			'marginalia_page_count'  => '_marginalia_page_count',
			'marginalia_star_rating' => '_marginalia_star_rating',
		);

		$date_fields = array(
			'marginalia_date_started'  => '_marginalia_date_started',
			'marginalia_date_finished' => '_marginalia_date_finished',
		);

		// Save text fields.
		foreach ( $text_fields as $field_name => $meta_key ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		// Save integer fields.
		foreach ( $int_fields as $field_name => $meta_key ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = absint( $_POST[ $field_name ] );
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		// Save date fields.
		foreach ( $date_fields as $field_name => $meta_key ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
				// Validate date format.
				if ( ! empty( $value ) ) {
					$timestamp = strtotime( $value );
					if ( false !== $timestamp ) {
						$value = gmdate( 'Y-m-d', $timestamp );
					} else {
						$value = '';
					}
				}
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		// Check for duplicates before publishing.
		if ( 'publish' === $post->post_status ) {
			$isbn_10         = get_post_meta( $post_id, '_marginalia_isbn_10', true );
			$isbn_13         = get_post_meta( $post_id, '_marginalia_isbn_13', true );
			$openlibrary_key = get_post_meta( $post_id, '_marginalia_openlibrary_key', true );

			$duplicate_id = Marginalia_Meta::check_duplicate( $isbn_10, $isbn_13, $openlibrary_key, $post_id );

			if ( $duplicate_id ) {
				// Add admin notice about duplicate.
				set_transient( 'marginalia_duplicate_' . $post_id, $duplicate_id, 60 );

				// Redirect with duplicate notice.
				add_filter(
					'redirect_post_location',
					function ( $location ) use ( $duplicate_id ) {
						return add_query_arg( 'marginalia_duplicate', $duplicate_id, $location );
					}
				);
			}
		}
	}
}
