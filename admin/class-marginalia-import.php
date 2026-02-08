<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * CSV Import functionality.
 *
 * @package Marginalia
 */

/**
 * Class to handle CSV import of books.
 */
class Marginalia_Import {

	/**
	 * Target fields available for mapping.
	 *
	 * @var array
	 */
	private $target_fields = array();

	/**
	 * Reading status mapping.
	 *
	 * @var array
	 */
	private $status_map = array();

	/**
	 * Auto-detection column mapping.
	 *
	 * @var array
	 */
	private $auto_detect_map = array();

	/**
	 * Initialize the class.
	 */
	public function init() {
		$this->setup_fields();
		add_action( 'admin_menu', array( $this, 'add_import_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_marginalia_upload_csv', array( $this, 'ajax_upload_csv' ) );
		add_action( 'wp_ajax_marginalia_import_batch', array( $this, 'ajax_import_batch' ) );
	}

	/**
	 * Setup field configurations.
	 */
	private function setup_fields() {
		$this->target_fields = array(
			''                 => __( '— Do Not Import —', 'marginalia-reading-log' ),
			'post_title'       => __( 'Title', 'marginalia-reading-log' ),
			'author'           => __( 'Author', 'marginalia-reading-log' ),
			'isbn_10'          => __( 'ISBN-10', 'marginalia-reading-log' ),
			'isbn_13'          => __( 'ISBN-13', 'marginalia-reading-log' ),
			'oclc'             => __( 'OCLC', 'marginalia-reading-log' ),
			'openlibrary_key'  => __( 'OpenLibrary Key', 'marginalia-reading-log' ),
			'publisher'        => __( 'Publisher', 'marginalia-reading-log' ),
			'publication_date' => __( 'Publication Date', 'marginalia-reading-log' ),
			'page_count'       => __( 'Page Count', 'marginalia-reading-log' ),
			'star_rating'      => __( 'Star Rating', 'marginalia-reading-log' ),
			'date_started'     => __( 'Date Started', 'marginalia-reading-log' ),
			'date_finished'    => __( 'Date Finished', 'marginalia-reading-log' ),
			'reading_status'   => __( 'Reading Status', 'marginalia-reading-log' ),
			'post_content'     => __( 'Review', 'marginalia-reading-log' ),
		);

		$this->status_map = array(
			'to-read'            => 'to-read',
			'to read'            => 'to-read',
			'want to read'       => 'to-read',
			'currently-reading'  => 'currently-reading',
			'currently reading'  => 'currently-reading',
			'reading'            => 'currently-reading',
			'read'               => 'read',
			'finished'           => 'read',
			'did-not-finish'     => 'did-not-finish',
			'did not finish'     => 'did-not-finish',
			'dnf'                => 'did-not-finish',
			'stopped'            => 'did-not-finish',
		);

		$this->auto_detect_map = array(
			'title'           => 'post_title',
			'book_title'      => 'post_title',
			'author'          => 'author',
			'author_text'     => 'author',
			'authors'         => 'author',
			'isbn_10'         => 'isbn_10',
			'isbn10'          => 'isbn_10',
			'isbn_13'         => 'isbn_13',
			'isbn13'          => 'isbn_13',
			'isbn'            => 'isbn_13',
			'pages'           => 'page_count',
			'num_pages'       => 'page_count',
			'page_count'      => 'page_count',
			'number of pages' => 'page_count',
			'rating'          => 'star_rating',
			'star_rating'     => 'star_rating',
			'my_rating'       => 'star_rating',
			'my rating'       => 'star_rating',
			'start_date'      => 'date_started',
			'date_started'    => 'date_started',
			'date started'    => 'date_started',
			'finish_date'     => 'date_finished',
			'date_finished'   => 'date_finished',
			'date finished'   => 'date_finished',
			'date_read'       => 'date_finished',
			'date read'       => 'date_finished',
			'shelf'           => 'reading_status',
			'shelf_name'      => 'reading_status',
			'exclusive_shelf' => 'reading_status',
			'reading_status'  => 'reading_status',
			'review'          => 'post_content',
			'review_content'  => 'post_content',
			'my_review'       => 'post_content',
			'my review'       => 'post_content',
			'publisher'       => 'publisher',
			'openlibrary_key' => 'openlibrary_key',
			'ol_key'          => 'openlibrary_key',
			'oclc'            => 'oclc',
			'publication_date' => 'publication_date',
			'publish_date'    => 'publication_date',
		);
	}

	/**
	 * Add import page under Books menu.
	 */
	public function add_import_page() {
		add_submenu_page(
			'edit.php?post_type=book',
			__( 'Import Books', 'marginalia-reading-log' ),
			__( 'Import', 'marginalia-reading-log' ),
			'edit_posts',
			'marginalia-import',
			array( $this, 'render_import_page' )
		);
	}

	/**
	 * Enqueue import page assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'book_page_marginalia-import' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'marginalia-import-css',
			MARGINALIA_PLUGIN_URL . 'admin/css/marginalia-import.css',
			array(),
			MARGINALIA_VERSION
		);

		wp_enqueue_script(
			'marginalia-import-js',
			MARGINALIA_PLUGIN_URL . 'admin/js/marginalia-import.js',
			array(),
			MARGINALIA_VERSION,
			true
		);

		wp_localize_script(
			'marginalia-import-js',
			'marginaliaImport',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'marginalia_import_nonce' ),
				'target_fields'   => $this->target_fields,
				'auto_detect_map' => $this->auto_detect_map,
				'strings'         => array(
					'uploading'        => __( 'Uploading and parsing CSV...', 'marginalia-reading-log' ),
					'upload_error'     => __( 'Upload failed. Please try again.', 'marginalia-reading-log' ),
					'no_file'          => __( 'Please select a CSV file.', 'marginalia-reading-log' ),
					'no_title'         => __( 'You must map at least one column to Title.', 'marginalia-reading-log' ),
					'importing'        => __( 'Importing books...', 'marginalia-reading-log' ),
					'import_complete'  => __( 'Import complete!', 'marginalia-reading-log' ),
					'imported'         => __( 'Imported', 'marginalia-reading-log' ),
					'skipped'          => __( 'Skipped (duplicate)', 'marginalia-reading-log' ),
					'error'            => __( 'Error', 'marginalia-reading-log' ),
					'missing_title'    => __( 'Missing title', 'marginalia-reading-log' ),
					'transient_expired' => __( 'Session expired. Please re-upload your CSV file.', 'marginalia-reading-log' ),
					'row'              => __( 'Row', 'marginalia-reading-log' ),
				),
			)
		);
	}

	/**
	 * Render the import page.
	 */
	public function render_import_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'marginalia-reading-log' ) );
		}
		?>
		<div class="wrap marginalia-import-wrap">
			<h1><?php esc_html_e( 'Import Books', 'marginalia-reading-log' ); ?></h1>

			<div class="marginalia-import-steps">
				<div class="marginalia-import-step active" data-step="1">
					<span class="step-number">1</span>
					<span class="step-label"><?php esc_html_e( 'Upload', 'marginalia-reading-log' ); ?></span>
				</div>
				<div class="marginalia-import-step" data-step="2">
					<span class="step-number">2</span>
					<span class="step-label"><?php esc_html_e( 'Map Fields', 'marginalia-reading-log' ); ?></span>
				</div>
				<div class="marginalia-import-step" data-step="3">
					<span class="step-number">3</span>
					<span class="step-label"><?php esc_html_e( 'Confirm', 'marginalia-reading-log' ); ?></span>
				</div>
				<div class="marginalia-import-step" data-step="4">
					<span class="step-number">4</span>
					<span class="step-label"><?php esc_html_e( 'Import', 'marginalia-reading-log' ); ?></span>
				</div>
			</div>

			<!-- Step 1: Upload -->
			<div class="marginalia-import-panel" id="marginalia-step-1">
				<h2><?php esc_html_e( 'Upload CSV File', 'marginalia-reading-log' ); ?></h2>
				<p><?php esc_html_e( 'Select a CSV file exported from Goodreads, BookWyrm, or another reading tracker.', 'marginalia-reading-log' ); ?></p>
				<div class="marginalia-import-upload">
					<input type="file" id="marginalia-csv-file" accept=".csv" />
					<button type="button" id="marginalia-upload-btn" class="button button-primary">
						<?php esc_html_e( 'Upload & Parse', 'marginalia-reading-log' ); ?>
					</button>
				</div>
				<div id="marginalia-upload-status" class="marginalia-import-status"></div>
			</div>

			<!-- Step 2: Map Fields -->
			<div class="marginalia-import-panel" id="marginalia-step-2" style="display: none;">
				<h2><?php esc_html_e( 'Map CSV Columns', 'marginalia-reading-log' ); ?></h2>
				<p><?php esc_html_e( 'Map each CSV column to a Marginalia field. Preview values from the first row are shown to help you identify each column.', 'marginalia-reading-log' ); ?></p>
				<table class="widefat marginalia-mapping-table" id="marginalia-mapping-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'CSV Column', 'marginalia-reading-log' ); ?></th>
							<th><?php esc_html_e( 'Preview Value', 'marginalia-reading-log' ); ?></th>
							<th><?php esc_html_e( 'Map To', 'marginalia-reading-log' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>

				<div class="marginalia-import-options">
					<h3><?php esc_html_e( 'Import Options', 'marginalia-reading-log' ); ?></h3>
					<p>
						<label>
							<input type="checkbox" id="marginalia-skip-duplicates" checked />
							<?php esc_html_e( 'Skip duplicate books (matched by ISBN or OpenLibrary key)', 'marginalia-reading-log' ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" id="marginalia-fetch-covers" />
							<?php esc_html_e( 'Fetch cover images from OpenLibrary (slower import)', 'marginalia-reading-log' ); ?>
						</label>
					</p>
					<p>
						<label for="marginalia-post-status"><?php esc_html_e( 'Post Status:', 'marginalia-reading-log' ); ?></label>
						<select id="marginalia-post-status">
							<option value="publish"><?php esc_html_e( 'Published', 'marginalia-reading-log' ); ?></option>
							<option value="draft"><?php esc_html_e( 'Draft', 'marginalia-reading-log' ); ?></option>
							<option value="private"><?php esc_html_e( 'Private', 'marginalia-reading-log' ); ?></option>
						</select>
					</p>
				</div>

				<div class="marginalia-import-actions">
					<button type="button" class="button marginalia-back-btn" data-target="1">
						<?php esc_html_e( '← Back', 'marginalia-reading-log' ); ?>
					</button>
					<button type="button" id="marginalia-confirm-mapping-btn" class="button button-primary">
						<?php esc_html_e( 'Continue →', 'marginalia-reading-log' ); ?>
					</button>
				</div>
			</div>

			<!-- Step 3: Confirm -->
			<div class="marginalia-import-panel" id="marginalia-step-3" style="display: none;">
				<h2><?php esc_html_e( 'Confirm Import', 'marginalia-reading-log' ); ?></h2>
				<div id="marginalia-confirm-summary"></div>
				<div class="marginalia-import-actions">
					<button type="button" class="button marginalia-back-btn" data-target="2">
						<?php esc_html_e( '← Back', 'marginalia-reading-log' ); ?>
					</button>
					<button type="button" id="marginalia-start-import-btn" class="button button-primary">
						<?php esc_html_e( 'Start Import', 'marginalia-reading-log' ); ?>
					</button>
				</div>
			</div>

			<!-- Step 4: Import Progress -->
			<div class="marginalia-import-panel" id="marginalia-step-4" style="display: none;">
				<h2><?php esc_html_e( 'Importing Books', 'marginalia-reading-log' ); ?></h2>
				<div class="marginalia-progress-wrap">
					<div class="marginalia-progress-bar">
						<div class="marginalia-progress-fill" id="marginalia-progress-fill"></div>
					</div>
					<p id="marginalia-progress-text"></p>
				</div>
				<div id="marginalia-import-log" class="marginalia-import-log"></div>
				<div id="marginalia-import-summary" class="marginalia-import-summary" style="display: none;"></div>
				<div class="marginalia-import-actions" id="marginalia-import-done-actions" style="display: none;">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=book' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'View All Books', 'marginalia-reading-log' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler: upload and parse CSV.
	 */
	public function ajax_upload_csv() {
		check_ajax_referer( 'marginalia_import_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'marginalia-reading-log' ) ) );
		}

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'marginalia-reading-log' ) ) );
		}

		$file = $_FILES['csv_file'];

		// Validate file type.
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			wp_send_json_error( array( 'message' => __( 'Please upload a CSV file.', 'marginalia-reading-log' ) ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $file['tmp_name'], 'r' );
		if ( false === $handle ) {
			wp_send_json_error( array( 'message' => __( 'Could not read the uploaded file.', 'marginalia-reading-log' ) ) );
		}

		$rows    = array();
		$headers = array();
		$row_num = 0;

		while ( false !== ( $row = fgetcsv( $handle ) ) ) {
			if ( 0 === $row_num ) {
				$headers = array_map( 'sanitize_text_field', $row );
			} else {
				$rows[] = array_map( 'sanitize_text_field', $row );
			}
			$row_num++;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		if ( empty( $headers ) || empty( $rows ) ) {
			wp_send_json_error( array( 'message' => __( 'The CSV file is empty or has no data rows.', 'marginalia-reading-log' ) ) );
		}

		// Store parsed data in transient.
		$user_id       = get_current_user_id();
		$transient_key = 'marginalia_import_' . $user_id;

		set_transient(
			$transient_key,
			array(
				'headers' => $headers,
				'rows'    => $rows,
			),
			HOUR_IN_SECONDS
		);

		// Return headers and first 3 rows for preview.
		$preview_rows = array_slice( $rows, 0, 3 );

		wp_send_json_success(
			array(
				'headers'      => $headers,
				'preview_rows' => $preview_rows,
				'total_rows'   => count( $rows ),
			)
		);
	}

	/**
	 * AJAX handler: import a batch of books.
	 */
	public function ajax_import_batch() {
		check_ajax_referer( 'marginalia_import_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'marginalia-reading-log' ) ) );
		}

		$offset          = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$mapping         = isset( $_POST['mapping'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mapping'] ) ) : array();
		$skip_duplicates = isset( $_POST['skip_duplicates'] ) && 'true' === $_POST['skip_duplicates'];
		$fetch_covers    = isset( $_POST['fetch_covers'] ) && 'true' === $_POST['fetch_covers'];
		$post_status     = isset( $_POST['post_status'] ) ? sanitize_text_field( wp_unslash( $_POST['post_status'] ) ) : 'publish';

		if ( ! in_array( $post_status, array( 'publish', 'draft', 'private' ), true ) ) {
			$post_status = 'publish';
		}

		// Retrieve stored CSV data.
		$user_id       = get_current_user_id();
		$transient_key = 'marginalia_import_' . $user_id;
		$csv_data      = get_transient( $transient_key );

		if ( false === $csv_data ) {
			wp_send_json_error( array( 'message' => 'transient_expired' ) );
		}

		$headers    = $csv_data['headers'];
		$rows       = $csv_data['rows'];
		$total_rows = count( $rows );
		$batch_size = $fetch_covers ? 2 : 5;
		$batch      = array_slice( $rows, $offset, $batch_size );
		$results    = array();

		foreach ( $batch as $index => $row ) {
			$row_number = $offset + $index + 1;
			$result     = $this->import_row( $row, $headers, $mapping, $skip_duplicates, $fetch_covers, $post_status, $row_number );
			$results[]  = $result;
		}

		$next_offset = $offset + count( $batch );
		$done        = $next_offset >= $total_rows;

		// Clean up transient when done.
		if ( $done ) {
			delete_transient( $transient_key );
		}

		wp_send_json_success(
			array(
				'results'     => $results,
				'next_offset' => $next_offset,
				'total_rows'  => $total_rows,
				'done'        => $done,
			)
		);
	}

	/**
	 * Import a single row from CSV data.
	 *
	 * @param array  $row             CSV row data.
	 * @param array  $headers         CSV headers.
	 * @param array  $mapping         Column-to-field mapping.
	 * @param bool   $skip_duplicates Whether to skip duplicates.
	 * @param bool   $fetch_covers    Whether to fetch cover images.
	 * @param string $post_status     Post status to use.
	 * @param int    $row_number      Row number for logging.
	 * @return array Result of the import.
	 */
	private function import_row( $row, $headers, $mapping, $skip_duplicates, $fetch_covers, $post_status, $row_number ) {
		// Map CSV values to fields.
		$book_data = array();
		foreach ( $headers as $col_index => $header ) {
			$col_key = strval( $col_index );
			if ( ! isset( $mapping[ $col_key ] ) || empty( $mapping[ $col_key ] ) ) {
				continue;
			}
			$field = $mapping[ $col_key ];
			$value = isset( $row[ $col_index ] ) ? $row[ $col_index ] : '';
			$book_data[ $field ] = $value;
		}

		// Validate title.
		if ( empty( $book_data['post_title'] ) ) {
			return array(
				'row'     => $row_number,
				'status'  => 'error',
				'title'   => '',
				'message' => __( 'Missing title', 'marginalia-reading-log' ),
			);
		}

		$title = sanitize_text_field( $book_data['post_title'] );

		// Sanitize identifiers.
		$isbn_10         = isset( $book_data['isbn_10'] ) ? sanitize_text_field( $book_data['isbn_10'] ) : '';
		$isbn_13         = isset( $book_data['isbn_13'] ) ? sanitize_text_field( $book_data['isbn_13'] ) : '';
		$openlibrary_key = isset( $book_data['openlibrary_key'] ) ? sanitize_text_field( $book_data['openlibrary_key'] ) : '';

		// Check duplicates.
		if ( $skip_duplicates ) {
			$duplicate_id = Marginalia_Meta::check_duplicate( $isbn_10, $isbn_13, $openlibrary_key );
			if ( $duplicate_id ) {
				return array(
					'row'     => $row_number,
					'status'  => 'skipped',
					'title'   => $title,
					'message' => __( 'Duplicate found', 'marginalia-reading-log' ),
				);
			}
		}

		// Create the post.
		$post_data = array(
			'post_title'   => $title,
			'post_type'    => 'book',
			'post_status'  => $post_status,
			'post_content' => isset( $book_data['post_content'] ) ? wp_kses_post( $book_data['post_content'] ) : '',
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return array(
				'row'     => $row_number,
				'status'  => 'error',
				'title'   => $title,
				'message' => $post_id->get_error_message(),
			);
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
			if ( isset( $book_data[ $data_key ] ) && '' !== $book_data[ $data_key ] ) {
				$value = sanitize_text_field( $book_data[ $data_key ] );

				// Additional sanitization for specific fields.
				if ( 'star_rating' === $data_key ) {
					$value = min( 5, max( 0, absint( $value ) ) );
				} elseif ( 'page_count' === $data_key ) {
					$value = absint( $value );
				}

				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		// Set reading status.
		if ( ! empty( $book_data['reading_status'] ) ) {
			$status_value = strtolower( trim( $book_data['reading_status'] ) );
			$term_slug    = isset( $this->status_map[ $status_value ] ) ? $this->status_map[ $status_value ] : '';
			if ( $term_slug ) {
				wp_set_object_terms( $post_id, $term_slug, 'reading_status' );
			}
		}

		// Fetch cover image.
		$cover_result = '';
		if ( $fetch_covers ) {
			$cover_url = $this->resolve_cover_url( $isbn_13, $isbn_10, $openlibrary_key );
			if ( $cover_url ) {
				sleep( 1 ); // Rate limit delay.
				$openlibrary   = new Marginalia_OpenLibrary();
				$attachment_id = $openlibrary->import_cover_image( $cover_url, $post_id, $title );
				if ( is_wp_error( $attachment_id ) ) {
					$cover_result = __( 'cover fetch failed', 'marginalia-reading-log' );
				} else {
					set_post_thumbnail( $post_id, $attachment_id );
					$cover_result = __( 'cover imported', 'marginalia-reading-log' );
				}
			}
		}

		$message = __( 'Imported successfully', 'marginalia-reading-log' );
		if ( $cover_result ) {
			$message .= ' (' . $cover_result . ')';
		}

		return array(
			'row'     => $row_number,
			'status'  => 'imported',
			'title'   => $title,
			'message' => $message,
			'post_id' => $post_id,
		);
	}

	/**
	 * Resolve cover URL using available identifiers.
	 *
	 * @param string $isbn_13         ISBN-13.
	 * @param string $isbn_10         ISBN-10.
	 * @param string $openlibrary_key OpenLibrary key.
	 * @return string Cover URL or empty string.
	 */
	private function resolve_cover_url( $isbn_13, $isbn_10, $openlibrary_key ) {
		$openlibrary = new Marginalia_OpenLibrary();

		if ( ! empty( $isbn_13 ) ) {
			return $openlibrary->get_cover_url_by_isbn( $isbn_13, 'L' );
		}

		if ( ! empty( $isbn_10 ) ) {
			return $openlibrary->get_cover_url_by_isbn( $isbn_10, 'L' );
		}

		if ( ! empty( $openlibrary_key ) ) {
			return $openlibrary->get_cover_url_by_key( $openlibrary_key, 'L' );
		}

		return '';
	}
}
