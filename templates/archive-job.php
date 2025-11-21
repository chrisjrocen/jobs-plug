<?php
/**
 * Archive template for Job post type.
 *
 * @package JobsPlug
 */

get_header();
?>

<div class="jobs-plug-archive">
	<div class="jobs-plug-container">

		<!-- Search and Filter Section -->
		<div class="jobs-plug-search-section">
			<?php
			// Generate appropriate heading based on archive type.
			$archive_title = '';
			if ( is_tax( 'location' ) ) {
				$term          = get_queried_object();
				$archive_title = sprintf(
					/* translators: %s: location name */
					__( 'Jobs in %s', 'jobs-plug' ),
					$term->name
				);
			} elseif ( is_tax( 'employer' ) ) {
				$term          = get_queried_object();
				$archive_title = sprintf(
					/* translators: %s: employer name */
					__( 'Jobs at %s', 'jobs-plug' ),
					$term->name
				);
			} elseif ( is_tax( 'job_category' ) ) {
				$term          = get_queried_object();
				$archive_title = sprintf(
					/* translators: %s: category name */
					__( '%s Jobs', 'jobs-plug' ),
					$term->name
				);
			} elseif ( is_tax( 'job_type' ) ) {
				$term          = get_queried_object();
				$archive_title = sprintf(
					/* translators: %s: job type name */
					__( '%s Jobs', 'jobs-plug' ),
					$term->name
				);
			} else {
				$archive_title = __( 'All Jobs', 'jobs-plug' );
			}
			?>
			<h1 class="jobs-plug-archive-title"><?php echo esc_html( $archive_title ); ?></h1>

			<?php
			// Get current filter values from GET parameters.
			$current_search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$current_category      = isset( $_GET['job_category'] ) ? sanitize_text_field( wp_unslash( $_GET['job_category'] ) ) : '';
			$current_employer      = isset( $_GET['employer'] ) ? sanitize_text_field( wp_unslash( $_GET['employer'] ) ) : '';
			$current_location      = isset( $_GET['location'] ) ? sanitize_text_field( wp_unslash( $_GET['location'] ) ) : '';
			$current_job_type      = isset( $_GET['job_type'] ) ? sanitize_text_field( wp_unslash( $_GET['job_type'] ) ) : '';
			$current_salary_min    = isset( $_GET['salary_min'] ) ? absint( $_GET['salary_min'] ) : '';
			$current_salary_max    = isset( $_GET['salary_max'] ) ? absint( $_GET['salary_max'] ) : '';
			$current_featured_only = isset( $_GET['featured_only'] ) && '1' === $_GET['featured_only'];

			// Get taxonomies for filtering.
			$job_categories = get_terms(
				array(
					'taxonomy'   => 'job_category',
					'hide_empty' => true,
				)
			);
			$employers      = get_terms(
				array(
					'taxonomy'   => 'employer',
					'hide_empty' => true,
				)
			);
			$locations      = get_terms(
				array(
					'taxonomy'   => 'location',
					'hide_empty' => true,
				)
			);
			$job_types      = get_terms(
				array(
					'taxonomy'   => 'job_type',
					'hide_empty' => true,
				)
			);
			?>

			<form method="get" action="<?php echo esc_url( get_post_type_archive_link( 'job' ) ); ?>" class="jobs-plug-search-form" role="search">
				<!-- Search Input -->
				<div class="jobs-plug-search-wrapper">
					<input
						type="text"
						name="s"
						class="jobs-plug-search-input"
						placeholder="<?php esc_attr_e( 'Search jobs by title or keyword...', 'jobs-plug' ); ?>"
						value="<?php echo esc_attr( $current_search ); ?>"
					/>
					<button type="submit" class="jobs-plug-search-button">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Search Jobs', 'jobs-plug' ); ?>
					</button>
				</div>

				<!-- Filters -->
				<div class="jobs-plug-filters">
					<!-- Category Filter -->
					<?php if ( ! empty( $job_categories ) && ! is_wp_error( $job_categories ) ) : ?>
						<div class="jobs-plug-filter">
							<label for="job-category-filter"><?php esc_html_e( 'Category:', 'jobs-plug' ); ?></label>
							<select name="job_category" id="job-category-filter">
								<option value=""><?php esc_html_e( 'All Categories', 'jobs-plug' ); ?></option>
								<?php foreach ( $job_categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $current_category, $category->slug ); ?>>
										<?php echo esc_html( $category->name ); ?> (<?php echo esc_html( $category->count ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- Employer Filter -->
					<?php if ( ! empty( $employers ) && ! is_wp_error( $employers ) ) : ?>
						<div class="jobs-plug-filter">
							<label for="employer-filter"><?php esc_html_e( 'Employer:', 'jobs-plug' ); ?></label>
							<select name="employer" id="employer-filter">
								<option value=""><?php esc_html_e( 'All Employers', 'jobs-plug' ); ?></option>
								<?php foreach ( $employers as $employer ) : ?>
									<option value="<?php echo esc_attr( $employer->slug ); ?>" <?php selected( $current_employer, $employer->slug ); ?>>
										<?php echo esc_html( $employer->name ); ?> (<?php echo esc_html( $employer->count ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- Location Filter -->
					<?php if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
						<div class="jobs-plug-filter">
							<label for="location-filter"><?php esc_html_e( 'Location:', 'jobs-plug' ); ?></label>
							<select name="location" id="location-filter">
								<option value=""><?php esc_html_e( 'All Locations', 'jobs-plug' ); ?></option>
								<?php foreach ( $locations as $loc ) : ?>
									<option value="<?php echo esc_attr( $loc->slug ); ?>" <?php selected( $current_location, $loc->slug ); ?>>
										<?php echo esc_html( $loc->name ); ?> (<?php echo esc_html( $loc->count ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- Job Type Filter -->
					<?php if ( ! empty( $job_types ) && ! is_wp_error( $job_types ) ) : ?>
						<div class="jobs-plug-filter">
							<label for="job-type-filter"><?php esc_html_e( 'Job Type:', 'jobs-plug' ); ?></label>
							<select name="job_type" id="job-type-filter">
								<option value=""><?php esc_html_e( 'All Types', 'jobs-plug' ); ?></option>
								<?php foreach ( $job_types as $type ) : ?>
									<option value="<?php echo esc_attr( $type->slug ); ?>" <?php selected( $current_job_type, $type->slug ); ?>>
										<?php echo esc_html( $type->name ); ?> (<?php echo esc_html( $type->count ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- Salary Range Filters -->
					<div class="jobs-plug-filter jobs-plug-filter-salary">
						<label for="salary-min-filter"><?php esc_html_e( 'Salary Range:', 'jobs-plug' ); ?></label>
						<div class="jobs-plug-salary-inputs">
							<input
								type="number"
								name="salary_min"
								id="salary-min-filter"
								placeholder="<?php esc_attr_e( 'Min', 'jobs-plug' ); ?>"
								value="<?php echo esc_attr( $current_salary_min ); ?>"
								min="0"
								step="1000000"
							/>
							<span class="jobs-plug-salary-separator">-</span>
							<input
								type="number"
								name="salary_max"
								id="salary-max-filter"
								placeholder="<?php esc_attr_e( 'Max', 'jobs-plug' ); ?>"
								value="<?php echo esc_attr( $current_salary_max ); ?>"
								min="0"
								step="1000000"
							/>
						</div>
					</div>

					<!-- Featured Only Checkbox -->
					<div class="jobs-plug-filter jobs-plug-filter-checkbox">
						<label for="featured-only-filter">
							<input
								type="checkbox"
								name="featured_only"
								id="featured-only-filter"
								value="1"
								<?php checked( $current_featured_only, true ); ?>
							/>
							<?php esc_html_e( 'Featured jobs only', 'jobs-plug' ); ?>
						</label>
					</div>

					<!-- Reset Filters Button -->
					<div class="jobs-plug-filter jobs-plug-filter-actions">
						<a href="<?php echo esc_url( get_post_type_archive_link( 'job' ) ); ?>" class="jobs-plug-reset-button">
							<?php esc_html_e( 'Reset Filters', 'jobs-plug' ); ?>
						</a>
					</div>
				</div>
			</form>
		</div>

		<!-- Jobs Listing -->
		<div class="jobs-plug-listings">
			<?php if ( have_posts() ) : ?>

				<div class="jobs-plug-results-info">
					<?php
					global $wp_query;
					printf(
						/* translators: %s: number of jobs found */
						esc_html__( 'Showing %s jobs', 'jobs-plug' ),
						'<strong>' . esc_html( $wp_query->found_posts ) . '</strong>'
					);
					?>
				</div>

				<div class="jobs-plug-grid">
					<?php
					while ( have_posts() ) :
						the_post();

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

							<?php
							// Display employer logo if available.
							$employer_logo = \JobsPlug\Jobs_Plug::get_instance()->get_employer_logo( get_the_ID(), 'full' );
							if ( ! empty( $employer_logo ) ) :
								?>
								<div class="jobs-plug-card-thumbnail">
									<?php echo $employer_logo; ?>
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

				<!-- Pagination -->
				<div class="jobs-plug-pagination">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 2,
							'prev_text' => __( '&laquo; Previous', 'jobs-plug' ),
							'next_text' => __( 'Next &raquo;', 'jobs-plug' ),
						)
					);
					?>
				</div>

			<?php else : ?>

				<div class="jobs-plug-no-results">
					<span class="dashicons dashicons-search"></span>
					<h2><?php esc_html_e( 'No jobs found', 'jobs-plug' ); ?></h2>
					<p><?php esc_html_e( 'Try adjusting your search or filters to find what you\'re looking for.', 'jobs-plug' ); ?></p>
				</div>

			<?php endif; ?>
		</div>

	</div>
</div>

<?php
get_footer();
