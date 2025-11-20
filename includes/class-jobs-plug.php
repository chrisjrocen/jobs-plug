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

		// Template loading.
		add_filter( 'template_include', array( $this, 'load_job_templates' ) );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Add schema markup.
		add_action( 'wp_head', array( $this, 'output_job_schema_markup' ), 10 );

		// Set job archive as homepage.
		add_action( 'pre_get_posts', array( $this, 'set_job_archive_as_homepage' ), 5 );

		// Filter job archives based on GET parameters.
		add_action( 'pre_get_posts', array( $this, 'filter_job_archives' ) );

		// AJAX handlers for live filtering.
		add_action( 'wp_ajax_jobs_plug_filter', array( $this, 'ajax_filter_jobs' ) );
		add_action( 'wp_ajax_nopriv_jobs_plug_filter', array( $this, 'ajax_filter_jobs' ) );

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
	 * Load custom templates for Job post type.
	 *
	 * @param string $template The path to the template file.
	 * @return string The modified template path.
	 */
	public function load_job_templates( $template ) {
		// Check if it's a single job post.
		if ( is_singular( 'job' ) ) {
			$plugin_template = JOBS_PLUG_PATH . 'templates/single-job.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		// Check if it's a job archive or taxonomy.
		if ( is_post_type_archive( 'job' ) || is_tax( array( 'job_category', 'employer', 'location', 'job_type' ) ) ) {
			$plugin_template = JOBS_PLUG_PATH . 'templates/archive-job.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		// Only load on job-related pages.
		if ( is_singular( 'job' ) || is_post_type_archive( 'job' ) || is_tax( array( 'job_category', 'employer', 'location', 'job_type' ) ) ) {
			wp_enqueue_style(
				'jobs-plug-frontend',
				JOBS_PLUG_URL . 'assets/css/jobs-plug-frontend.css',
				array(),
				JOBS_PLUG_VERSION
			);

			// Enqueue dashicons for icons.
			wp_enqueue_style( 'dashicons' );

			// Enqueue filter JavaScript on archive pages only.
			if ( is_post_type_archive( 'job' ) || is_tax( array( 'job_category', 'employer', 'location', 'job_type' ) ) ) {
				wp_enqueue_script(
					'jobs-plug-filter',
					JOBS_PLUG_URL . 'assets/js/jobs-filter.js',
					array(),
					JOBS_PLUG_VERSION,
					true
				);

				// Localize script with AJAX URL.
				wp_localize_script(
					'jobs-plug-filter',
					'jobsPlugAjax',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					)
				);
			}
		}
	}

	/**
	 * Filter job archives based on GET parameters.
	 *
	 * @param WP_Query $query The WordPress query object.
	 */
	public function filter_job_archives( $query ) {
		// Only modify main query on job archives.
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Don't filter single job posts - they should always be accessible.
		if ( $query->is_singular() || $query->get( 'name' ) || $query->get( 'p' ) ) {
			return;
		}

		// Check if this is a job archive by checking the query object.
		$post_type = $query->get( 'post_type' );
		$taxonomy  = $query->get( 'taxonomy' );

		$is_job_archive = false;

		// Check if it's the job post type archive.
		if ( 'job' === $post_type ) {
			$is_job_archive = true;
		}

		// Check if it's a job taxonomy archive.
		$job_taxonomies = array( 'job_category', 'employer', 'location', 'job_type' );
		if ( in_array( $taxonomy, $job_taxonomies, true ) ) {
			$is_job_archive = true;
			// Ensure post type is set for taxonomy archives.
			$query->set( 'post_type', 'job' );
		}

		// Return if not a job archive.
		if ( ! $is_job_archive ) {
			return;
		}

		// Get existing tax query if any.
		$tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		// Filter by category.
		if ( ! empty( $_GET['job_category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'job_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_GET['job_category'] ) ),
			);
		}

		// Filter by employer.
		if ( ! empty( $_GET['employer'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'employer',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_GET['employer'] ) ),
			);
		}

		// Filter by location.
		if ( ! empty( $_GET['location'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'location',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_GET['location'] ) ),
			);
		}

		// Filter by job type.
		if ( ! empty( $_GET['job_type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'job_type',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_GET['job_type'] ) ),
			);
		}

		// Apply tax query if we added any filters.
		if ( count( $tax_query ) > 0 ) {
			// Set relation to AND if not already set.
			if ( ! isset( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			$query->set( 'tax_query', $tax_query );
		}

		// Get existing meta query if any.
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		// Filter by salary range.
		if ( ! empty( $_GET['salary_min'] ) && ! empty( $_GET['salary_max'] ) ) {
			// Both min and max set - use BETWEEN.
			$meta_query[] = array(
				'key'     => '_job_salary',
				'value'   => array( absint( $_GET['salary_min'] ), absint( $_GET['salary_max'] ) ),
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			);
		} elseif ( ! empty( $_GET['salary_min'] ) ) {
			// Only min set - use >=.
			$meta_query[] = array(
				'key'     => '_job_salary',
				'value'   => absint( $_GET['salary_min'] ),
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		} elseif ( ! empty( $_GET['salary_max'] ) ) {
			// Only max set - use <=.
			$meta_query[] = array(
				'key'     => '_job_salary',
				'value'   => absint( $_GET['salary_max'] ),
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		// Filter by featured status.
		if ( ! empty( $_GET['featured_only'] ) && '1' === $_GET['featured_only'] ) {
			$meta_query[] = array(
				'key'   => '_job_is_featured',
				'value' => '1',
			);
		}

		// Exclude expired jobs.
		$today        = current_time( 'Y-m-d' );
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => '_job_expiry_date',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_job_expiry_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);

		// Apply meta query if we added any filters.
		if ( count( $meta_query ) > 0 ) {
			// Set relation to AND if not already set.
			if ( ! isset( $meta_query['relation'] ) ) {
				$meta_query['relation'] = 'AND';
			}
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * AJAX handler for live job filtering.
	 */
	public function ajax_filter_jobs() {
		// Build query args from POST parameters.
		$args = array(
			'post_type'      => 'job',
			'posts_per_page' => get_option( 'posts_per_page' ),
			'post_status'    => 'publish',
		);

		// Build tax query.
		$tax_query = array();

		if ( ! empty( $_POST['job_category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'job_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_POST['job_category'] ) ),
			);
		}

		if ( ! empty( $_POST['employer'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'employer',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_POST['employer'] ) ),
			);
		}

		if ( ! empty( $_POST['location'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'location',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_POST['location'] ) ),
			);
		}

		if ( ! empty( $_POST['job_type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'job_type',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_POST['job_type'] ) ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		// Build meta query.
		$meta_query = array();

		// Salary range filtering.
		if ( ! empty( $_POST['salary_min'] ) && ! empty( $_POST['salary_max'] ) ) {
			$meta_query[] = array(
				'key'     => '_job_salary',
				'value'   => array( absint( $_POST['salary_min'] ), absint( $_POST['salary_max'] ) ),
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			);
		} elseif ( ! empty( $_POST['salary_min'] ) ) {
			$meta_query[] = array(
				'key'     => '_job_salary',
				'value'   => absint( $_POST['salary_min'] ),
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		} elseif ( ! empty( $_POST['salary_max'] ) ) {
			$meta_query[] = array(
				'key'     => '_job_salary',
				'value'   => absint( $_POST['salary_max'] ),
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		// Featured only filtering.
		if ( ! empty( $_POST['featured_only'] ) && '1' === $_POST['featured_only'] ) {
			$meta_query[] = array(
				'key'   => '_job_is_featured',
				'value' => '1',
			);
		}

		// Exclude expired jobs.
		$today        = current_time( 'Y-m-d' );
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => '_job_expiry_date',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_job_expiry_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query;
		}

		// Search query.
		if ( ! empty( $_POST['s'] ) ) {
			$args['s'] = sanitize_text_field( wp_unslash( $_POST['s'] ) );
		}

		// Execute query.
		$query = new \WP_Query( $args );

		// Start output buffering.
		ob_start();

		if ( $query->have_posts() ) :
			?>
			<div class="jobs-plug-results-info">
				<?php
				printf(
					/* translators: %s: number of jobs found */
					esc_html__( 'Showing %s jobs', 'jobs-plug' ),
					'<strong>' . esc_html( $query->found_posts ) . '</strong>'
				);
				?>
			</div>

			<div class="jobs-plug-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					// Get meta data.
					$salary      = get_post_meta( get_the_ID(), '_job_salary', true );
					$is_featured = get_post_meta( get_the_ID(), '_job_is_featured', true );
					$expiry_date = get_post_meta( get_the_ID(), '_job_expiry_date', true );

					// Check if job is expired.
					$is_expired = false;
					if ( ! empty( $expiry_date ) ) {
						$expiry_timestamp = strtotime( $expiry_date );
						$is_expired       = ( $expiry_timestamp && $expiry_timestamp < current_time( 'timestamp' ) );
					}

					// Get taxonomy terms.
					$employers = get_the_terms( get_the_ID(), 'employer' );
					$locations = get_the_terms( get_the_ID(), 'location' );
					$job_types = get_the_terms( get_the_ID(), 'job_type' );

					// Add expired class to card.
					$card_classes = array( 'jobs-plug-card' );
					if ( $is_featured ) {
						$card_classes[] = 'jobs-plug-featured';
					}
					if ( $is_expired ) {
						$card_classes[] = 'jobs-plug-card-expired';
					}
					?>

					<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">

						<?php if ( $is_expired ) : ?>
							<div class="jobs-plug-badge-expired">
								<span class="dashicons dashicons-warning"></span>
								<?php esc_html_e( 'Expired', 'jobs-plug' ); ?>
							</div>
						<?php elseif ( $is_featured ) : ?>
							<div class="jobs-plug-badge-featured">
								<span class="dashicons dashicons-star-filled"></span>
								<?php esc_html_e( 'Featured', 'jobs-plug' ); ?>
							</div>
						<?php endif; ?>

						<?php if ( has_post_thumbnail() ) : ?>
							<div class="jobs-plug-card-thumbnail">
								<?php the_post_thumbnail( 'full' ); ?>
							</div>
						<?php endif; ?>

						<div class="jobs-plug-card-content">
							<h2 class="jobs-plug-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

							<div class="jobs-plug-card-meta">
								<?php if ( ! empty( $employers ) && ! is_wp_error( $employers ) ) : ?>
									<div class="jobs-plug-meta-item">
										<span class="dashicons dashicons-building"></span>
										<span><?php echo esc_html( $employers[0]->name ); ?></span>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
									<div class="jobs-plug-meta-item">
										<span class="dashicons dashicons-location"></span>
										<span><?php echo esc_html( $locations[0]->name ); ?></span>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $job_types ) && ! is_wp_error( $job_types ) ) : ?>
									<div class="jobs-plug-meta-item jobs-plug-badge-type">
										<?php echo esc_html( $job_types[0]->name ); ?>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $salary ) ) : ?>
									<div class="jobs-plug-meta-item jobs-plug-salary">
										<span class="dashicons dashicons-money-alt"></span>
										<span><?php echo esc_html( number_format_i18n( $salary ) ); ?></span>
									</div>
								<?php endif; ?>
							</div>

							<div class="jobs-plug-card-excerpt">
								<?php echo wp_trim_words( get_the_excerpt(), 20, '...' ); ?>
							</div>

							<div class="jobs-plug-card-footer">
								<?php if ( ! empty( $expiry_date ) ) : ?>
									<div class="jobs-plug-expiry <?php echo $is_expired ? 'jobs-plug-expiry-expired' : ''; ?>">
										<span class="dashicons dashicons-calendar-alt"></span>
										<?php
										if ( $is_expired ) {
											printf(
												/* translators: %s: expiry date */
												esc_html__( 'Expired on: %s', 'jobs-plug' ),
												esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expiry_date ) ) )
											);
										} else {
											printf(
												/* translators: %s: expiry date */
												esc_html__( 'Expires: %s', 'jobs-plug' ),
												esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expiry_date ) ) )
											);
										}
										?>
									</div>
								<?php endif; ?>

								<a href="<?php the_permalink(); ?>" class="jobs-plug-card-button">
									<?php esc_html_e( 'View Job', 'jobs-plug' ); ?>
									<span class="dashicons dashicons-arrow-right-alt2"></span>
								</a>
							</div>
						</div>
					</article>

				<?php endwhile; ?>
			</div>

			<div class="jobs-plug-pagination">
				<?php
				$pagination = paginate_links(
					array(
						'total'     => $query->max_num_pages,
						'current'   => max( 1, get_query_var( 'paged' ) ),
						'format'    => '?paged=%#%',
						'mid_size'  => 2,
						'prev_text' => __( '&laquo; Previous', 'jobs-plug' ),
						'next_text' => __( 'Next &raquo;', 'jobs-plug' ),
						'type'      => 'plain',
					)
				);
				echo $pagination ? '<nav class="nav-links">' . $pagination . '</nav>' : '';
				?>
			</div>

		<?php else : ?>

			<div class="jobs-plug-no-results">
				<span class="dashicons dashicons-search"></span>
				<h2><?php esc_html_e( 'No jobs found', 'jobs-plug' ); ?></h2>
				<p><?php esc_html_e( 'Try adjusting your search or filters to find what you\'re looking for.', 'jobs-plug' ); ?></p>
			</div>

			<?php
		endif;

		wp_reset_postdata();

		// Get the buffered output.
		$html = ob_get_clean();

		// Send JSON response.
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Output JobPosting schema markup in JSON-LD format.
	 */
	public function output_job_schema_markup() {
		// Only output on single job pages.
		if ( ! is_singular( 'job' ) ) {
			return;
		}

		global $post;

		// Get meta data.
		$application_method = get_post_meta( $post->ID, '_job_application_method', true );
		$expiry_date        = get_post_meta( $post->ID, '_job_expiry_date', true );
		$salary             = get_post_meta( $post->ID, '_job_salary', true );

		// Get taxonomy terms.
		$employers = get_the_terms( $post->ID, 'employer' );
		$locations = get_the_terms( $post->ID, 'location' );
		$job_types = get_the_terms( $post->ID, 'job_type' );

		// Build schema data.
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'JobPosting',
			'title'       => get_the_title( $post->ID ),
			'description' => wp_strip_all_tags( get_post_field( 'post_content', $post->ID ) ),
			'datePosted'  => get_the_date( 'c', $post->ID ),
			'url'         => get_permalink( $post->ID ),
		);

		// Add hiring organization.
		if ( ! empty( $employers ) && ! is_wp_error( $employers ) ) {
			$schema['hiringOrganization'] = array(
				'@type' => 'Organization',
				'name'  => $employers[0]->name,
			);
		} else {
			// Fallback to site name.
			$schema['hiringOrganization'] = array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			);
		}

		// Add job location.
		if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) {
			$schema['jobLocation'] = array(
				'@type'   => 'Place',
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => $locations[0]->name,
				),
			);
		}

		// Add expiry date (validThrough).
		if ( ! empty( $expiry_date ) ) {
			// Convert to ISO 8601 format.
			$expiry_timestamp = strtotime( $expiry_date );
			if ( $expiry_timestamp ) {
				$schema['validThrough'] = date( 'c', $expiry_timestamp );
			}
		}

		// Add employment type.
		if ( ! empty( $job_types ) && ! is_wp_error( $job_types ) ) {
			// Map common job types to schema.org employment types.
			$employment_type_map = array(
				'full-time'  => 'FULL_TIME',
				'full time'  => 'FULL_TIME',
				'fulltime'   => 'FULL_TIME',
				'part-time'  => 'PART_TIME',
				'part time'  => 'PART_TIME',
				'parttime'   => 'PART_TIME',
				'contract'   => 'CONTRACTOR',
				'contractor' => 'CONTRACTOR',
				'temporary'  => 'TEMPORARY',
				'temp'       => 'TEMPORARY',
				'intern'     => 'INTERN',
				'internship' => 'INTERN',
				'volunteer'  => 'VOLUNTEER',
				'per diem'   => 'PER_DIEM',
				'other'      => 'OTHER',
			);

			$job_type_slug = strtolower( $job_types[0]->slug );
			if ( isset( $employment_type_map[ $job_type_slug ] ) ) {
				$schema['employmentType'] = $employment_type_map[ $job_type_slug ];
			}
		}

		// Add salary (baseSalary).
		if ( ! empty( $salary ) && is_numeric( $salary ) ) {
			$schema['baseSalary'] = array(
				'@type'    => 'MonetaryAmount',
				'currency' => 'UGX', // Uganda Shillings - adjust as needed.
				'value'    => array(
					'@type'    => 'QuantitativeValue',
					'value'    => $salary,
					'unitText' => 'YEAR',
				),
			);
		}

		// Add identifier.
		$schema['identifier'] = array(
			'@type' => 'PropertyValue',
			'name'  => get_bloginfo( 'name' ),
			'value' => $post->ID,
		);

		/**
		 * Filter the JobPosting schema markup.
		 *
		 * @param array   $schema The schema data array.
		 * @param WP_Post $post   The current post object.
		 */
		$schema = apply_filters( 'jobs_plug_schema_markup', $schema, $post );

		// Output the JSON-LD script.
		echo "\n<!-- JobPosting Schema Markup by Jobs Plug -->\n";
		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		echo "\n</script>\n";
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
					<?php
					wp_editor(
						$application_method,
						'job_application_method',
						array(
							'textarea_name' => 'job_application_method',
							'textarea_rows' => 8,
							'media_buttons' => false,
							'teeny'         => false,
							'quicktags'     => true,
							'tinymce'       => array(
								'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
								'toolbar2' => '',
							),
						)
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Provide instructions for how candidates can apply for this job. You can use formatting, lists, and links.', 'jobs-plug' ); ?>
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
			$application_method = wp_kses_post( wp_unslash( $_POST['job_application_method'] ) );
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
	 * Set job archive as homepage.
	 *
	 * @param WP_Query $query The WordPress query object.
	 */
	public function set_job_archive_as_homepage( $query ) {
		// Only proceed if this is the main query and the home page.
		if ( ! $query->is_main_query() || ! $query->is_home() ) {
			return;
		}

		// Check if setting is enabled.
		$set_as_homepage = get_option( 'jobs_plug_set_as_homepage', true );
		if ( ! $set_as_homepage ) {
			return;
		}

		// Set query to show job post type.
		$query->set( 'post_type', 'job' );
		$query->is_home     = false;
		$query->is_archive  = true;
		$query->is_post_type_archive = true;
	}

	/**
	 * Admin initialization.
	 */
	public function admin_init() {
		// Register settings.
		register_setting(
			'jobs_plug_settings',
			'jobs_plug_set_as_homepage',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		// Add settings section.
		add_settings_section(
			'jobs_plug_general_section',
			__( 'General Settings', 'jobs-plug' ),
			array( $this, 'render_settings_section' ),
			'jobs-plug-settings'
		);

		// Add setting field.
		add_settings_field(
			'jobs_plug_set_as_homepage',
			__( 'Set as Homepage', 'jobs-plug' ),
			array( $this, 'render_homepage_setting_field' ),
			'jobs-plug-settings',
			'jobs_plug_general_section'
		);
	}

	/**
	 * Register admin menu.
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Jobs Plug Settings', 'jobs-plug' ),
			__( 'Jobs Plug', 'jobs-plug' ),
			'manage_options',
			'jobs-plug-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Display success message if settings were saved.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'jobs_plug_messages',
				'jobs_plug_message',
				__( 'Settings Saved', 'jobs-plug' ),
				'updated'
			);
		}

		settings_errors( 'jobs_plug_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'jobs_plug_settings' );
				do_settings_sections( 'jobs-plug-settings' );
				submit_button( __( 'Save Settings', 'jobs-plug' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render settings section description.
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__( 'Configure the general settings for Jobs Plug.', 'jobs-plug' ) . '</p>';
	}

	/**
	 * Render homepage setting field.
	 */
	public function render_homepage_setting_field() {
		$value = get_option( 'jobs_plug_set_as_homepage', true );
		?>
		<label>
			<input
				type="checkbox"
				name="jobs_plug_set_as_homepage"
				value="1"
				<?php checked( $value, true ); ?>
			/>
			<?php esc_html_e( 'Display the job archive as the site homepage', 'jobs-plug' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, the job listings will be shown on your homepage instead of your regular posts or pages.', 'jobs-plug' ); ?>
		</p>
		<?php
	}
}
