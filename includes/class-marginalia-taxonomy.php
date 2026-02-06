<?php
/**
 * Custom taxonomy registration.
 *
 * @package Marginalia
 */

/**
 * Class to register the reading status taxonomy.
 */
class Marginalia_Taxonomy {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the reading status taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Reading Status', 'taxonomy general name', 'marginalia-reading-log' ),
			'singular_name'              => _x( 'Reading Status', 'taxonomy singular name', 'marginalia-reading-log' ),
			'search_items'               => __( 'Search Reading Statuses', 'marginalia-reading-log' ),
			'popular_items'              => __( 'Popular Reading Statuses', 'marginalia-reading-log' ),
			'all_items'                  => __( 'All Reading Statuses', 'marginalia-reading-log' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Reading Status', 'marginalia-reading-log' ),
			'update_item'                => __( 'Update Reading Status', 'marginalia-reading-log' ),
			'add_new_item'               => __( 'Add New Reading Status', 'marginalia-reading-log' ),
			'new_item_name'              => __( 'New Reading Status Name', 'marginalia-reading-log' ),
			'separate_items_with_commas' => __( 'Separate reading statuses with commas', 'marginalia-reading-log' ),
			'add_or_remove_items'        => __( 'Add or remove reading statuses', 'marginalia-reading-log' ),
			'choose_from_most_used'      => __( 'Choose from the most used reading statuses', 'marginalia-reading-log' ),
			'not_found'                  => __( 'No reading statuses found.', 'marginalia-reading-log' ),
			'menu_name'                  => __( 'Reading Status', 'marginalia-reading-log' ),
			'back_to_items'              => __( '&larr; Back to Reading Statuses', 'marginalia-reading-log' ),
		);

		$args = array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'reading-status' ),
			'show_in_rest'          => true,
			'rest_base'             => 'reading-status',
			'meta_box_cb'           => array( $this, 'reading_status_meta_box' ),
		);

		register_taxonomy( 'reading_status', array( 'book' ), $args );
	}

	/**
	 * Custom meta box for reading status (radio buttons instead of checkboxes).
	 *
	 * @param WP_Post $post The post object.
	 */
	public function reading_status_meta_box( $post ) {
		$terms         = get_terms(
			array(
				'taxonomy'   => 'reading_status',
				'hide_empty' => false,
			)
		);
		$current_terms = wp_get_object_terms( $post->ID, 'reading_status', array( 'fields' => 'ids' ) );
		$current_term  = ! empty( $current_terms ) ? $current_terms[0] : 0;

		wp_nonce_field( 'marginalia_reading_status_nonce', 'marginalia_reading_status_nonce' );
		?>
		<div id="taxonomy-reading_status" class="categorydiv">
			<ul id="reading_status-checklist" class="categorychecklist form-no-clear">
				<?php foreach ( $terms as $term ) : ?>
					<li>
						<label>
							<input type="radio" name="tax_input[reading_status][]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( $current_term, $term->term_id ); ?> />
							<?php echo esc_html( $term->name ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
