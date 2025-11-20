<?php
/**
 * Main plugin class.
 *
 * @package JobsPlug
 */

namespace JobsPlug;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main plugin class.
 */
class Jobs_Plug {

	/**
	 * Single instance of the class.
	 *
	 * @var Jobs_Plug
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return Jobs_Plug
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Initialization hook.
		add_action( 'init', array( $this, 'init' ) );

		// Register custom post type.
		add_action( 'init', array( $this, 'register_job_post_type' ) );

		// Register custom taxonomies.
		add_action( 'init', array( $this, 'register_job_taxonomies' ) );

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_job_meta_boxes' ) );
			add_action( 'save_post_job', array( $this, 'save_job_meta_boxes' ), 10, 2 );
		}
	}

	/**
	 * Initialize plugin functionality.
	 */
	public function init() {
		// Load text domain for translations.
		load_plugin_textdomain(
			'jobs-plug',
			false,
			dirname( JOBS_PLUG_BASENAME ) . '/languages'
		);

		// Plugin initialization code here.
	}

	/**
	 * Register the Job custom post type.
	 */
	public function register_job_post_type() {
		$labels = array(
			'name'                  => _x( 'Jobs', 'Post type general name', 'jobs-plug' ),
			'singular_name'         => _x( 'Job', 'Post type singular name', 'jobs-plug' ),
			'menu_name'             => _x( 'Jobs', 'Admin Menu text', 'jobs-plug' ),
			'name_admin_bar'        => _x( 'Job', 'Add New on Toolbar', 'jobs-plug' ),
			'add_new'               => __( 'Add New', 'jobs-plug' ),
			'add_new_item'          => __( 'Add New Job', 'jobs-plug' ),
			'new_item'              => __( 'New Job', 'jobs-plug' ),
			'edit_item'             => __( 'Edit Job', 'jobs-plug' ),
			'view_item'             => __( 'View Job', 'jobs-plug' ),
			'all_items'             => __( 'All Jobs', 'jobs-plug' ),
			'search_items'          => __( 'Search Jobs', 'jobs-plug' ),
			'parent_item_colon'     => __( 'Parent Jobs:', 'jobs-plug' ),
			'not_found'             => __( 'No jobs found.', 'jobs-plug' ),
			'not_found_in_trash'    => __( 'No jobs found in Trash.', 'jobs-plug' ),
			'featured_image'        => _x( 'Job Featured Image', 'Overrides the "Featured Image" phrase', 'jobs-plug' ),
			'set_featured_image'    => _x( 'Set featured image', 'Overrides the "Set featured image" phrase', 'jobs-plug' ),
			'remove_featured_image' => _x( 'Remove featured image', 'Overrides the "Remove featured image" phrase', 'jobs-plug' ),
			'use_featured_image'    => _x( 'Use as featured image', 'Overrides the "Use as featured image" phrase', 'jobs-plug' ),
			'archives'              => _x( 'Job archives', 'The post type archive label used in nav menus', 'jobs-plug' ),
			'insert_into_item'      => _x( 'Insert into job', 'Overrides the "Insert into post" phrase', 'jobs-plug' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this job', 'Overrides the "Uploaded to this post" phrase', 'jobs-plug' ),
			'filter_items_list'     => _x( 'Filter jobs list', 'Screen reader text for the filter links', 'jobs-plug' ),
			'items_list_navigation' => _x( 'Jobs list navigation', 'Screen reader text for the pagination', 'jobs-plug' ),
			'items_list'            => _x( 'Jobs list', 'Screen reader text for the items list', 'jobs-plug' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'job' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-businessman',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'job', $args );
	}

	/**
	 * Register custom taxonomies for the Job post type.
	 */
	public function register_job_taxonomies() {
		$this->register_job_category_taxonomy();
		$this->register_employer_taxonomy();
		$this->register_location_taxonomy();
		$this->register_job_type_taxonomy();
	}

	/**
	 * Register Job Category taxonomy (hierarchical).
	 */
	private function register_job_category_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Job Categories', 'taxonomy general name', 'jobs-plug' ),
			'singular_name'              => _x( 'Job Category', 'taxonomy singular name', 'jobs-plug' ),
			'search_items'               => __( 'Search Job Categories', 'jobs-plug' ),
			'popular_items'              => __( 'Popular Job Categories', 'jobs-plug' ),
			'all_items'                  => __( 'All Job Categories', 'jobs-plug' ),
			'parent_item'                => __( 'Parent Job Category', 'jobs-plug' ),
			'parent_item_colon'          => __( 'Parent Job Category:', 'jobs-plug' ),
			'edit_item'                  => __( 'Edit Job Category', 'jobs-plug' ),
			'update_item'                => __( 'Update Job Category', 'jobs-plug' ),
			'add_new_item'               => __( 'Add New Job Category', 'jobs-plug' ),
			'new_item_name'              => __( 'New Job Category Name', 'jobs-plug' ),
			'separate_items_with_commas' => __( 'Separate job categories with commas', 'jobs-plug' ),
			'add_or_remove_items'        => __( 'Add or remove job categories', 'jobs-plug' ),
			'choose_from_most_used'      => __( 'Choose from the most used job categories', 'jobs-plug' ),
			'not_found'                  => __( 'No job categories found.', 'jobs-plug' ),
			'menu_name'                  => __( 'Job Categories', 'jobs-plug' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => array( 'slug' => 'job-category' ),
		);

		register_taxonomy( 'job_category', array( 'job' ), $args );
	}

	/**
	 * Register Employer taxonomy (non-hierarchical).
	 */
	private function register_employer_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Employers', 'taxonomy general name', 'jobs-plug' ),
			'singular_name'              => _x( 'Employer', 'taxonomy singular name', 'jobs-plug' ),
			'search_items'               => __( 'Search Employers', 'jobs-plug' ),
			'popular_items'              => __( 'Popular Employers', 'jobs-plug' ),
			'all_items'                  => __( 'All Employers', 'jobs-plug' ),
			'edit_item'                  => __( 'Edit Employer', 'jobs-plug' ),
			'update_item'                => __( 'Update Employer', 'jobs-plug' ),
			'add_new_item'               => __( 'Add New Employer', 'jobs-plug' ),
			'new_item_name'              => __( 'New Employer Name', 'jobs-plug' ),
			'separate_items_with_commas' => __( 'Separate employers with commas', 'jobs-plug' ),
			'add_or_remove_items'        => __( 'Add or remove employers', 'jobs-plug' ),
			'choose_from_most_used'      => __( 'Choose from the most used employers', 'jobs-plug' ),
			'not_found'                  => __( 'No employers found.', 'jobs-plug' ),
			'menu_name'                  => __( 'Employers', 'jobs-plug' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => array( 'slug' => 'employer' ),
		);

		register_taxonomy( 'employer', array( 'job' ), $args );
	}

	/**
	 * Register Location taxonomy (hierarchical).
	 */
	private function register_location_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Locations', 'taxonomy general name', 'jobs-plug' ),
			'singular_name'              => _x( 'Location', 'taxonomy singular name', 'jobs-plug' ),
			'search_items'               => __( 'Search Locations', 'jobs-plug' ),
			'popular_items'              => __( 'Popular Locations', 'jobs-plug' ),
			'all_items'                  => __( 'All Locations', 'jobs-plug' ),
			'parent_item'                => __( 'Parent Location', 'jobs-plug' ),
			'parent_item_colon'          => __( 'Parent Location:', 'jobs-plug' ),
			'edit_item'                  => __( 'Edit Location', 'jobs-plug' ),
			'update_item'                => __( 'Update Location', 'jobs-plug' ),
			'add_new_item'               => __( 'Add New Location', 'jobs-plug' ),
			'new_item_name'              => __( 'New Location Name', 'jobs-plug' ),
			'separate_items_with_commas' => __( 'Separate locations with commas', 'jobs-plug' ),
			'add_or_remove_items'        => __( 'Add or remove locations', 'jobs-plug' ),
			'choose_from_most_used'      => __( 'Choose from the most used locations', 'jobs-plug' ),
			'not_found'                  => __( 'No locations found.', 'jobs-plug' ),
			'menu_name'                  => __( 'Locations', 'jobs-plug' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => array( 'slug' => 'location' ),
		);

		register_taxonomy( 'location', array( 'job' ), $args );
	}

	/**
	 * Register Job Type taxonomy (non-hierarchical).
	 */
	private function register_job_type_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Job Types', 'taxonomy general name', 'jobs-plug' ),
			'singular_name'              => _x( 'Job Type', 'taxonomy singular name', 'jobs-plug' ),
			'search_items'               => __( 'Search Job Types', 'jobs-plug' ),
			'popular_items'              => __( 'Popular Job Types', 'jobs-plug' ),
			'all_items'                  => __( 'All Job Types', 'jobs-plug' ),
			'edit_item'                  => __( 'Edit Job Type', 'jobs-plug' ),
			'update_item'                => __( 'Update Job Type', 'jobs-plug' ),
			'add_new_item'               => __( 'Add New Job Type', 'jobs-plug' ),
			'new_item_name'              => __( 'New Job Type Name', 'jobs-plug' ),
			'separate_items_with_commas' => __( 'Separate job types with commas', 'jobs-plug' ),
			'add_or_remove_items'        => __( 'Add or remove job types', 'jobs-plug' ),
			'choose_from_most_used'      => __( 'Choose from the most used job types', 'jobs-plug' ),
			'not_found'                  => __( 'No job types found.', 'jobs-plug' ),
			'menu_name'                  => __( 'Job Types', 'jobs-plug' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => array( 'slug' => 'job-type' ),
		);

		register_taxonomy( 'job_type', array( 'job' ), $args );
	}

	/**
	 * Add custom meta boxes for the Job post type.
	 */
	public function add_job_meta_boxes() {
		add_meta_box(
			'job_details',
			__( 'Job Details', 'jobs-plug' ),
			array( $this, 'render_job_details_meta_box' ),
			'job',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Job Details meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render_job_details_meta_box( $post ) {
		// Add nonce for security.
		wp_nonce_field( 'job_details_meta_box', 'job_details_nonce' );

		// Get existing values.
		$application_method = get_post_meta( $post->ID, '_job_application_method', true );
		$expiry_date        = get_post_meta( $post->ID, '_job_expiry_date', true );
		$salary             = get_post_meta( $post->ID, '_job_salary', true );
		$is_featured        = get_post_meta( $post->ID, '_job_is_featured', true );

		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="job_application_method"><?php esc_html_e( 'Application Method', 'jobs-plug' ); ?></label>
				</th>
				<td>
					<textarea
						id="job_application_method"
						name="job_application_method"
						rows="4"
						class="large-text"
						placeholder="<?php esc_attr_e( 'Enter how applicants can apply (email, URL, instructions, etc.)', 'jobs-plug' ); ?>"
					><?php echo esc_textarea( $application_method ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Provide instructions for how candidates can apply for this job.', 'jobs-plug' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="job_expiry_date"><?php esc_html_e( 'Expiry Date', 'jobs-plug' ); ?></label>
				</th>
				<td>
					<input
						type="date"
						id="job_expiry_date"
						name="job_expiry_date"
						value="<?php echo esc_attr( $expiry_date ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'The date when this job posting expires.', 'jobs-plug' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="job_salary"><?php esc_html_e( 'Salary', 'jobs-plug' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="job_salary"
						name="job_salary"
						value="<?php echo esc_attr( $salary ); ?>"
						class="regular-text"
						min="0"
						step="0.01"
						placeholder="<?php esc_attr_e( 'Enter salary amount', 'jobs-plug' ); ?>"
					/>
					<p class="description">
						<?php esc_html_e( 'Annual salary or compensation for this position.', 'jobs-plug' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="job_is_featured"><?php esc_html_e( 'Featured Job', 'jobs-plug' ); ?></label>
				</th>
				<td>
					<label for="job_is_featured">
						<input
							type="checkbox"
							id="job_is_featured"
							name="job_is_featured"
							value="1"
							<?php checked( $is_featured, '1' ); ?>
						/>
						<?php esc_html_e( 'Mark this job as featured', 'jobs-plug' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Featured jobs may be highlighted on your site.', 'jobs-plug' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the Job Details meta box data.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function save_job_meta_boxes( $post_id, $post ) {
		// Verify nonce.
		if ( ! isset( $_POST['job_details_nonce'] ) || ! wp_verify_nonce( $_POST['job_details_nonce'], 'job_details_meta_box' ) ) {
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

		// Save Application Method.
		if ( isset( $_POST['job_application_method'] ) ) {
			$application_method = sanitize_textarea_field( wp_unslash( $_POST['job_application_method'] ) );
			update_post_meta( $post_id, '_job_application_method', $application_method );
		} else {
			delete_post_meta( $post_id, '_job_application_method' );
		}

		// Save Expiry Date.
		if ( isset( $_POST['job_expiry_date'] ) && ! empty( $_POST['job_expiry_date'] ) ) {
			$expiry_date = sanitize_text_field( wp_unslash( $_POST['job_expiry_date'] ) );
			// Validate date format.
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expiry_date ) ) {
				update_post_meta( $post_id, '_job_expiry_date', $expiry_date );
			}
		} else {
			delete_post_meta( $post_id, '_job_expiry_date' );
		}

		// Save Salary.
		if ( isset( $_POST['job_salary'] ) && ! empty( $_POST['job_salary'] ) ) {
			$salary = sanitize_text_field( wp_unslash( $_POST['job_salary'] ) );
			// Validate numeric value.
			if ( is_numeric( $salary ) && $salary >= 0 ) {
				update_post_meta( $post_id, '_job_salary', $salary );
			}
		} else {
			delete_post_meta( $post_id, '_job_salary' );
		}

		// Save Featured checkbox.
		if ( isset( $_POST['job_is_featured'] ) && '1' === $_POST['job_is_featured'] ) {
			update_post_meta( $post_id, '_job_is_featured', '1' );
		} else {
			delete_post_meta( $post_id, '_job_is_featured' );
		}
	}

	/**
	 * Admin initialization.
	 */
	public function admin_init() {
		// Admin-specific initialization code here.
	}

	/**
	 * Register admin menu.
	 */
	public function admin_menu() {
		// Admin menu registration code here.
	}
}
