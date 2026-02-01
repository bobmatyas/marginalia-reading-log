<?php
/**
 * Admin columns functionality.
 *
 * @package Marginalia
 */

/**
 * Class to handle custom admin columns for the book post type.
 */
class Marginalia_Admin_Columns {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_filter( 'manage_book_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_book_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-book_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_by_reading_status' ) );
	}

	/**
	 * Add custom columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			// Add our columns after title.
			if ( 'title' === $key ) {
				$new_columns[ $key ] = $value;
				$new_columns['marginalia_author'] = __( 'Author', 'marginalia-reading-log' );
				$new_columns['marginalia_rating'] = __( 'Rating', 'marginalia-reading-log' );
				$new_columns['marginalia_date_started'] = __( 'Date Started', 'marginalia-reading-log' );
				continue;
			}

			// Skip the default date column, we'll add our own.
			if ( 'date' === $key ) {
				continue;
			}

			$new_columns[ $key ] = $value;
		}

		// Add date column at the end.
		$new_columns['date'] = __( 'Date Added', 'marginalia-reading-log' );

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'marginalia_author':
				$author = get_post_meta( $post_id, '_marginalia_author', true );
				echo esc_html( $author ? $author : '—' );
				break;

			case 'marginalia_rating':
				$rating = get_post_meta( $post_id, '_marginalia_star_rating', true );
				if ( $rating ) {
					marginalia_display_stars( $rating );
				} else {
					echo '<span class="marginalia-unrated">' . esc_html__( 'Unrated', 'marginalia-reading-log' ) . '</span>';
				}
				break;

			case 'marginalia_date_started':
				$date_started = get_post_meta( $post_id, '_marginalia_date_started', true );
				if ( $date_started ) {
					$timestamp = strtotime( $date_started );
					echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Define sortable columns.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['marginalia_author']       = 'marginalia_author';
		$columns['marginalia_rating']       = 'marginalia_rating';
		$columns['marginalia_date_started'] = 'marginalia_date_started';
		return $columns;
	}

	/**
	 * Handle column sorting.
	 *
	 * @param WP_Query $query The query object.
	 */
	public function sort_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit-book' !== $screen->id ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'marginalia_author':
				$query->set( 'meta_key', '_marginalia_author' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'marginalia_rating':
				$query->set( 'meta_key', '_marginalia_star_rating' );
				$query->set( 'orderby', 'meta_value_num' );
				break;

			case 'marginalia_date_started':
				$query->set( 'meta_key', '_marginalia_date_started' );
				$query->set( 'orderby', 'meta_value' );
				break;
		}
	}

	/**
	 * Add filter dropdowns to admin.
	 *
	 * @param string $post_type The post type.
	 */
	public function add_filters( $post_type ) {
		if ( 'book' !== $post_type ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'reading_status',
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$selected = isset( $_GET['reading_status'] ) ? sanitize_text_field( wp_unslash( $_GET['reading_status'] ) ) : '';
		?>
		<select name="reading_status" id="filter-by-reading-status">
			<option value=""><?php esc_html_e( 'All Reading Statuses', 'marginalia-reading-log' ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected, $term->slug ); ?>>
					<?php echo esc_html( $term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter posts by reading status.
	 *
	 * @param WP_Query $query The query object.
	 */
	public function filter_by_reading_status( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

		if ( 'book' !== $post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$reading_status = isset( $_GET['reading_status'] ) ? sanitize_text_field( wp_unslash( $_GET['reading_status'] ) ) : '';

		if ( ! empty( $reading_status ) ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'reading_status',
						'field'    => 'slug',
						'terms'    => $reading_status,
					),
				)
			);
		}
	}
}
