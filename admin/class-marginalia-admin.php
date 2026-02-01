<?php
/**
 * Admin functionality.
 *
 * @package Marginalia
 */

/**
 * Class to handle admin functionality.
 */
class Marginalia_Admin {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
		add_action( 'admin_notices', array( $this, 'duplicate_book_notice' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'render_quick_add_modal' ) );
		add_action( 'wp_ajax_marginalia_quick_add_book', array( $this, 'ajax_quick_add_book' ) );
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_styles( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || 'book' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'marginalia-admin-css',
			MARGINALIA_PLUGIN_URL . 'admin/css/marginalia-admin.css',
			array(),
			MARGINALIA_VERSION
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || 'book' !== $screen->post_type ) {
			return;
		}

		// Enqueue media uploader for cover images.
		wp_enqueue_media();

		wp_enqueue_script(
			'marginalia-admin-js',
			MARGINALIA_PLUGIN_URL . 'admin/js/marginalia-admin.js',
			array( 'jquery', 'wp-util' ),
			MARGINALIA_VERSION,
			true
		);

		wp_localize_script(
			'marginalia-admin-js',
			'marginalia',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'marginalia_admin_nonce' ),
				'strings'            => array(
					'searching'          => __( 'Searching...', 'marginalia-reading-log' ),
					'no_results'         => __( 'No results found.', 'marginalia-reading-log' ),
					'select_book'        => __( 'Select', 'marginalia-reading-log' ),
					'loading_details'    => __( 'Loading book details...', 'marginalia-reading-log' ),
					'importing_cover'    => __( 'Importing cover image...', 'marginalia-reading-log' ),
					'cover_imported'     => __( 'Cover image imported successfully.', 'marginalia-reading-log' ),
					'cover_import_error' => __( 'Failed to import cover image.', 'marginalia-reading-log' ),
					'fields_populated'   => __( 'Fields populated from OpenLibrary.', 'marginalia-reading-log' ),
					'error'              => __( 'An error occurred.', 'marginalia-reading-log' ),
					'confirm_overwrite'  => __( 'This will overwrite existing field values. Continue?', 'marginalia-reading-log' ),
					'creating_book'      => __( 'Creating book...', 'marginalia-reading-log' ),
					'create_book'        => __( 'Create Book', 'marginalia-reading-log' ),
					'edit_book'          => __( 'Edit Book', 'marginalia-reading-log' ),
					'edit_existing'      => __( 'Edit existing book', 'marginalia-reading-log' ),
				),
				'placeholder_cover'  => MARGINALIA_PLUGIN_URL . 'assets/images/placeholder-cover.svg',
			)
		);

		// Enqueue block editor script for reading status dropdown.
		if ( $screen && 'post' === $screen->base && 'book' === $screen->post_type ) {
			$this->enqueue_block_editor_assets();
		}
	}

	/**
	 * Enqueue block editor assets.
	 */
	private function enqueue_block_editor_assets() {
		// Get reading status terms.
		$terms = get_terms(
			array(
				'taxonomy'   => 'reading_status',
				'hide_empty' => false,
			)
		);

		$reading_statuses = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$reading_statuses[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		wp_enqueue_script(
			'marginalia-block-editor-js',
			MARGINALIA_PLUGIN_URL . 'admin/js/marginalia-block-editor.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-dom-ready' ),
			MARGINALIA_VERSION,
			true
		);

		wp_localize_script(
			'marginalia-block-editor-js',
			'marginaliaBlockEditor',
			array(
				'readingStatuses' => $reading_statuses,
				'strings'         => array(
					'readingStatus' => __( 'Reading Status', 'marginalia-reading-log' ),
					'statusLabel'   => __( 'Select reading status', 'marginalia-reading-log' ),
					'selectStatus'  => __( '— Select Status —', 'marginalia-reading-log' ),
				),
			)
		);
	}

	/**
	 * Display activation notice.
	 */
	public function activation_notice() {
		if ( ! get_transient( 'marginalia_activation_notice' ) ) {
			return;
		}

		delete_transient( 'marginalia_activation_notice' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: URL to Books admin page */
					esc_html__( 'Thank you for installing Marginalia! Start by %s.', 'marginalia-reading-log' ),
					'<a href="' . esc_url( admin_url( 'post-new.php?post_type=book' ) ) . '">' . esc_html__( 'adding your first book', 'marginalia-reading-log' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display duplicate book notice.
	 */
	public function duplicate_book_notice() {
		$screen = get_current_screen();

		if ( ! $screen || 'book' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		// Check for duplicate notice in URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only, no action taken.
		$duplicate_id = isset( $_GET['marginalia_duplicate'] ) ? absint( $_GET['marginalia_duplicate'] ) : 0;

		if ( ! $duplicate_id ) {
			return;
		}

		$duplicate_post = get_post( $duplicate_id );

		if ( ! $duplicate_post ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: book title, 2: edit link */
					esc_html__( 'A book with this identifier already exists: "%1$s". %2$s', 'marginalia-reading-log' ),
					esc_html( $duplicate_post->post_title ),
					'<a href="' . esc_url( get_edit_post_link( $duplicate_id ) ) . '">' . esc_html__( 'Edit existing book', 'marginalia-reading-log' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the quick add modal on the books list page.
	 */
	public function render_quick_add_modal() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-book' !== $screen->id ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Get reading status terms for the dropdown.
		$reading_statuses = get_terms(
			array(
				'taxonomy'   => 'reading_status',
				'hide_empty' => false,
			)
		);
		?>
		<!-- Quick Add Button (injected via JS) -->
		<script type="text/html" id="tmpl-marginalia-quick-add-button">
			<a href="#" class="page-title-action marginalia-quick-add-btn">
				<?php esc_html_e( 'Quick Add from OpenLibrary', 'marginalia-reading-log' ); ?>
			</a>
		</script>

		<!-- Quick Add Modal -->
		<div id="marginalia-quick-add-modal" class="marginalia-modal" style="display: none;">
			<div class="marginalia-modal-overlay"></div>
			<div class="marginalia-modal-container">
				<div class="marginalia-modal-header">
					<h2><?php esc_html_e( 'Add Book from OpenLibrary', 'marginalia-reading-log' ); ?></h2>
					<button type="button" class="marginalia-modal-close" aria-label="<?php esc_attr_e( 'Close', 'marginalia-reading-log' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="marginalia-modal-body">
					<div class="marginalia-modal-search">
						<div class="marginalia-search-form">
							<input type="text"
								id="marginalia-modal-search-query"
								class="regular-text"
								placeholder="<?php esc_attr_e( 'Enter ISBN, title, or author...', 'marginalia-reading-log' ); ?>"
							/>
							<select id="marginalia-modal-search-type">
								<option value="q"><?php esc_html_e( 'All Fields', 'marginalia-reading-log' ); ?></option>
								<option value="isbn"><?php esc_html_e( 'ISBN', 'marginalia-reading-log' ); ?></option>
								<option value="title"><?php esc_html_e( 'Title', 'marginalia-reading-log' ); ?></option>
								<option value="author"><?php esc_html_e( 'Author', 'marginalia-reading-log' ); ?></option>
							</select>
							<button type="button" id="marginalia-modal-search-btn" class="button button-primary">
								<?php esc_html_e( 'Search', 'marginalia-reading-log' ); ?>
							</button>
						</div>
						<div id="marginalia-modal-search-status" class="marginalia-search-status"></div>
						<div id="marginalia-modal-search-results" class="marginalia-search-results"></div>
					</div>

					<div class="marginalia-modal-book-details" style="display: none;">
						<div class="marginalia-modal-book-preview">
							<img id="marginalia-modal-book-cover" src="" alt="" class="marginalia-preview-cover" />
							<div class="marginalia-preview-info">
								<h3 id="marginalia-modal-book-title"></h3>
								<p id="marginalia-modal-book-author" class="marginalia-preview-author"></p>
								<p id="marginalia-modal-book-meta" class="marginalia-preview-meta"></p>
							</div>
						</div>

						<div class="marginalia-modal-options">
							<p>
								<label for="marginalia-modal-reading-status">
									<?php esc_html_e( 'Reading Status', 'marginalia-reading-log' ); ?>
								</label>
								<select id="marginalia-modal-reading-status" class="widefat">
									<option value=""><?php esc_html_e( '— Select —', 'marginalia-reading-log' ); ?></option>
									<?php foreach ( $reading_statuses as $status ) : ?>
										<option value="<?php echo esc_attr( $status->slug ); ?>">
											<?php echo esc_html( $status->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
							<p>
								<label for="marginalia-modal-date-started">
									<?php esc_html_e( 'Date Started', 'marginalia-reading-log' ); ?>
								</label>
								<input type="date" id="marginalia-modal-date-started" class="widefat" />
							</p>
							<p>
								<label for="marginalia-modal-date-finished">
									<?php esc_html_e( 'Date Finished', 'marginalia-reading-log' ); ?>
								</label>
								<input type="date" id="marginalia-modal-date-finished" class="widefat" />
							</p>
							<p>
								<label><?php esc_html_e( 'Star Rating', 'marginalia-reading-log' ); ?></label>
								<span class="marginalia-modal-rating-input">
									<?php for ( $i = 0; $i <= 5; $i++ ) : ?>
										<label class="marginalia-rating-option">
											<input type="radio" name="marginalia_modal_rating" value="<?php echo esc_attr( $i ); ?>" <?php checked( 0, $i ); ?> />
											<?php if ( 0 === $i ) : ?>
												<span class="marginalia-rating-text"><?php esc_html_e( 'None', 'marginalia-reading-log' ); ?></span>
											<?php else : ?>
												<span class="marginalia-rating-stars"><?php echo esc_html( str_repeat( '★', $i ) ); ?></span>
											<?php endif; ?>
										</label>
									<?php endfor; ?>
								</span>
							</p>
						</div>
					</div>
				</div>
				<div class="marginalia-modal-footer">
					<button type="button" class="button marginalia-modal-back" style="display: none;">
						<?php esc_html_e( '← Back to Search', 'marginalia-reading-log' ); ?>
					</button>
					<div class="marginalia-modal-footer-right">
						<button type="button" class="button marginalia-modal-cancel">
							<?php esc_html_e( 'Cancel', 'marginalia-reading-log' ); ?>
						</button>
						<button type="button" class="button button-primary marginalia-modal-create" style="display: none;">
							<?php esc_html_e( 'Create Book', 'marginalia-reading-log' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to quick add a book.
	 */
	public function ajax_quick_add_book() {
		check_ajax_referer( 'marginalia_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'marginalia-reading-log' ) ) );
		}

		// Get and sanitize book data.
		$book_data = array(
			'title'            => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'author'           => isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '',
			'isbn_10'          => isset( $_POST['isbn_10'] ) ? sanitize_text_field( wp_unslash( $_POST['isbn_10'] ) ) : '',
			'isbn_13'          => isset( $_POST['isbn_13'] ) ? sanitize_text_field( wp_unslash( $_POST['isbn_13'] ) ) : '',
			'oclc'             => isset( $_POST['oclc'] ) ? sanitize_text_field( wp_unslash( $_POST['oclc'] ) ) : '',
			'publisher'        => isset( $_POST['publisher'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher'] ) ) : '',
			'publication_date' => isset( $_POST['publication_date'] ) ? sanitize_text_field( wp_unslash( $_POST['publication_date'] ) ) : '',
			'page_count'       => isset( $_POST['page_count'] ) ? absint( $_POST['page_count'] ) : 0,
			'openlibrary_key'  => isset( $_POST['openlibrary_key'] ) ? sanitize_text_field( wp_unslash( $_POST['openlibrary_key'] ) ) : '',
			'cover_url'        => isset( $_POST['cover_url'] ) ? esc_url_raw( wp_unslash( $_POST['cover_url'] ) ) : '',
			'reading_status'   => isset( $_POST['reading_status'] ) ? sanitize_text_field( wp_unslash( $_POST['reading_status'] ) ) : '',
			'date_started'     => isset( $_POST['date_started'] ) ? sanitize_text_field( wp_unslash( $_POST['date_started'] ) ) : '',
			'date_finished'    => isset( $_POST['date_finished'] ) ? sanitize_text_field( wp_unslash( $_POST['date_finished'] ) ) : '',
			'star_rating'      => isset( $_POST['star_rating'] ) ? absint( $_POST['star_rating'] ) : 0,
		);

		// Validate required fields.
		if ( empty( $book_data['title'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Book title is required.', 'marginalia-reading-log' ) ) );
		}

		// Check for duplicates.
		$duplicate_id = Marginalia_Meta::check_duplicate(
			$book_data['isbn_10'],
			$book_data['isbn_13'],
			$book_data['openlibrary_key']
		);

		if ( $duplicate_id ) {
			$duplicate_post = get_post( $duplicate_id );
			wp_send_json_error(
				array(
					'message'      => sprintf(
						/* translators: %s: book title */
						__( 'A book with this identifier already exists: "%s"', 'marginalia-reading-log' ),
						$duplicate_post->post_title
					),
					'duplicate_id' => $duplicate_id,
					'edit_url'     => get_edit_post_link( $duplicate_id, 'raw' ),
				)
			);
		}

		// Create the post.
		$post_data = array(
			'post_title'  => $book_data['title'],
			'post_type'   => 'book',
			'post_status' => 'publish',
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		// Save meta fields.
		$meta_mappings = array(
			'author'           => '_marginalia_author',
			'isbn_10'          => '_marginalia_isbn_10',
			'isbn_13'          => '_marginalia_isbn_13',
			'oclc'             => '_marginalia_oclc',
			'publisher'        => '_marginalia_publisher',
			'publication_date' => '_marginalia_publication_date',
			'page_count'       => '_marginalia_page_count',
			'openlibrary_key'  => '_marginalia_openlibrary_key',
			'date_started'     => '_marginalia_date_started',
			'date_finished'    => '_marginalia_date_finished',
			'star_rating'      => '_marginalia_star_rating',
		);

		foreach ( $meta_mappings as $data_key => $meta_key ) {
			if ( ! empty( $book_data[ $data_key ] ) || '0' === (string) $book_data[ $data_key ] ) {
				update_post_meta( $post_id, $meta_key, $book_data[ $data_key ] );
			}
		}

		// Set reading status.
		if ( ! empty( $book_data['reading_status'] ) ) {
			wp_set_object_terms( $post_id, $book_data['reading_status'], 'reading_status' );
		}

		// Import cover image.
		if ( ! empty( $book_data['cover_url'] ) ) {
			$openlibrary = new Marginalia_OpenLibrary();
			$attachment_id = $openlibrary->import_cover_image(
				$book_data['cover_url'],
				$post_id,
				$book_data['title']
			);

			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		wp_send_json_success(
			array(
				'post_id'  => $post_id,
				'edit_url' => get_edit_post_link( $post_id, 'raw' ),
				'view_url' => get_permalink( $post_id ),
				'message'  => sprintf(
					/* translators: %s: book title */
					__( '"%s" has been added to your library.', 'marginalia-reading-log' ),
					$book_data['title']
				),
			)
		);
	}
}
