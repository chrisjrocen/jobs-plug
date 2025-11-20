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
