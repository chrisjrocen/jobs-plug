<?php
/**
 * Single template for Job post type.
 *
 * @package JobsPlug
 */

get_header();
?>

<div class="jobs-plug-single">
	<?php
	while ( have_posts() ) :
		the_post();

		// Get meta data.
		$application_method = get_post_meta( get_the_ID(), '_job_application_method', true );
		$expiry_date        = get_post_meta( get_the_ID(), '_job_expiry_date', true );
		$salary             = get_post_meta( get_the_ID(), '_job_salary', true );
		$is_featured        = get_post_meta( get_the_ID(), '_job_is_featured', true );
		$is_remote          = get_post_meta( get_the_ID(), '_job_is_remote', true );

		// Get taxonomy terms.
		$employers  = get_the_terms( get_the_ID(), 'employer' );
		$locations  = get_the_terms( get_the_ID(), 'location' );
		$job_types  = get_the_terms( get_the_ID(), 'job_type' );
		$categories = get_the_terms( get_the_ID(), 'job_category' );
		$job_tags   = get_the_terms( get_the_ID(), 'job_tags' );
		?>

		<div class="jobs-plug-container">

			<!-- Job Header -->
			<div class="jobs-plug-single-header">
				<?php if ( $is_featured ) : ?>
					<div class="jobs-plug-badge-featured">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Featured Job', 'jobs-plug' ); ?>
					</div>
				<?php endif; ?>

				<h1 class="jobs-plug-single-title"><?php the_title(); ?></h1>

				<div class="jobs-plug-single-meta">
					<?php if ( ! empty( $employers ) && ! is_wp_error( $employers ) ) : ?>
						<div class="jobs-plug-meta-item">
							<span class="dashicons dashicons-building"></span>
							<strong><?php esc_html_e( 'Employer:', 'jobs-plug' ); ?></strong>
							<span><?php echo esc_html( $employers[0]->name ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
						<div class="jobs-plug-meta-item">
							<span class="dashicons dashicons-location"></span>
							<strong><?php esc_html_e( 'Location:', 'jobs-plug' ); ?></strong>
							<span><?php echo esc_html( $locations[0]->name ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $job_types ) && ! is_wp_error( $job_types ) ) : ?>
						<div class="jobs-plug-meta-item">
							<span class="dashicons dashicons-businessman"></span>
							<strong><?php esc_html_e( 'Job Type:', 'jobs-plug' ); ?></strong>
							<span><?php echo esc_html( $job_types[0]->name ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $salary ) ) : ?>
						<div class="jobs-plug-meta-item">
							<span class="dashicons dashicons-money-alt"></span>
							<strong><?php esc_html_e( 'Salary:', 'jobs-plug' ); ?></strong>
							<span><?php echo esc_html( number_format_i18n( $salary ) ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $expiry_date ) ) : ?>
						<div class="jobs-plug-meta-item">
							<span class="dashicons dashicons-calendar-alt"></span>
							<strong><?php esc_html_e( 'Application Deadline:', 'jobs-plug' ); ?></strong>
							<span>
								<?php
								$expiry_timestamp = strtotime( $expiry_date );
								echo esc_html( date_i18n( get_option( 'date_format' ), $expiry_timestamp ) );

								// Check if expired.
								if ( $expiry_timestamp < current_time( 'timestamp' ) ) {
									echo ' <span class="jobs-plug-expired">' . esc_html__( '(Expired)', 'jobs-plug' ) . '</span>';
								}
								?>
							</span>
						</div>
					<?php endif; ?>

					<div class="jobs-plug-meta-item">
						<span class="dashicons dashicons-admin-site"></span>
						<strong><?php esc_html_e( 'Remote:', 'jobs-plug' ); ?></strong>
						<span><?php echo $is_remote ? esc_html__( 'Yes', 'jobs-plug' ) : esc_html__( 'No', 'jobs-plug' ); ?></span>
					</div>
				</div>
			</div>

			<div class="jobs-plug-single-content-wrapper">

				<!-- Main Content -->
				<div class="jobs-plug-single-main">

					<!-- Job Thumbnail -->
					<?php
					// Display employer logo if available.
					$employer_logo = \JobsPlug\Jobs_Plug::get_instance()->get_employer_logo( get_the_ID(), 'large' );
					if ( ! empty( $employer_logo ) ) :
						?>
						<div class="jobs-plug-single-thumbnail">
							<?php echo $employer_logo; ?>
						</div>
					<?php endif; ?>

					<!-- Expired Notification -->
					<?php
					if ( ! empty( $expiry_date ) ) {
						$expiry_timestamp = strtotime( $expiry_date );
						if ( $expiry_timestamp && $expiry_timestamp < current_time( 'timestamp' ) ) :
							?>
							<div class="jobs-plug-expired-banner">
								<span class="dashicons dashicons-warning"></span>
								<div class="jobs-plug-expired-banner-content">
									<strong><?php esc_html_e( 'This job has expired', 'jobs-plug' ); ?></strong>
									<p>
										<?php
										printf(
											/* translators: %s: expiry date */
											esc_html__( 'This position expired on %s and is no longer accepting applications.', 'jobs-plug' ),
											'<strong>' . esc_html( date_i18n( get_option( 'date_format' ), $expiry_timestamp ) ) . '</strong>'
										);
										?>
									</p>
								</div>
							</div>
							<?php
						endif;
					}
					?>

					<!-- Job Description -->
					<div class="jobs-plug-single-content">
						<h2><?php esc_html_e( 'Job Description', 'jobs-plug' ); ?></h2>
						<?php the_content(); ?>
					</div>

					<!-- Application Method -->
					<?php if ( ! empty( $application_method ) ) : ?>
						<div class="jobs-plug-application-method" id="how-to-apply">
							<h2><?php esc_html_e( 'How to Apply', 'jobs-plug' ); ?></h2>
							<div class="jobs-plug-application-content">
								<?php echo wp_kses_post( wpautop( $application_method ) ); ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Job Categories -->
					<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div class="jobs-plug-single-categories">
							<strong><?php esc_html_e( 'Categories:', 'jobs-plug' ); ?></strong>
							<?php
							$category_links = array();
							foreach ( $categories as $category ) {
								$category_links[] = '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
							}
							echo implode( ', ', $category_links );
							?>
						</div>
					<?php endif; ?>

					<!-- Job Tags -->
					<?php if ( ! empty( $job_tags ) && ! is_wp_error( $job_tags ) ) : ?>
						<div class="jobs-plug-single-tags">
							<?php
							$tag_links = array();
							foreach ( $job_tags as $tag ) {
								$tag_links[] = '<a href="' . esc_url( get_term_link( $tag ) ) . '">' . esc_html( $tag->name ) . '</a>';
							}
							echo implode( ', ', $tag_links );
							?>
						</div>
					<?php endif; ?>

				</div>

				<!-- Sidebar -->
				<div class="jobs-plug-single-sidebar">

					<!-- Quick Apply Box -->
					<div class="jobs-plug-sidebar-box jobs-plug-apply-box">
						<h3><?php esc_html_e( 'Interested in this job?', 'jobs-plug' ); ?></h3>
						<?php if ( ! empty( $expiry_date ) && strtotime( $expiry_date ) >= current_time( 'timestamp' ) ) : ?>
							<p><?php esc_html_e( 'This position is currently open for applications.', 'jobs-plug' ); ?></p>
							<a href="#how-to-apply" class="jobs-plug-apply-button">
								<?php esc_html_e( 'Apply Now', 'jobs-plug' ); ?>
							</a>
						<?php else : ?>
							<p class="jobs-plug-expired-notice">
								<?php esc_html_e( 'This job posting has expired.', 'jobs-plug' ); ?>
							</p>
						<?php endif; ?>
					</div>

					<!-- Share Box -->
					<div class="jobs-plug-sidebar-box jobs-plug-share-box">
						<h3><?php esc_html_e( 'Share this job', 'jobs-plug' ); ?></h3>
						<div class="jobs-plug-share-links">
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url( get_permalink() ); ?>" target="_blank" rel="noopener" class="jobs-plug-share-link">
								<span class="dashicons dashicons-facebook-alt"></span>
								<?php esc_html_e( 'Facebook', 'jobs-plug' ); ?>
							</a>
							<a href="https://twitter.com/intent/tweet?url=<?php echo esc_url( get_permalink() ); ?>&text=<?php echo esc_attr( get_the_title() ); ?>" target="_blank" rel="noopener" class="jobs-plug-share-link">
								<span class="dashicons dashicons-twitter"></span>
								<?php esc_html_e( 'Twitter', 'jobs-plug' ); ?>
							</a>
							<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_url( get_permalink() ); ?>" target="_blank" rel="noopener" class="jobs-plug-share-link">
								<span class="dashicons dashicons-linkedin"></span>
								<?php esc_html_e( 'LinkedIn', 'jobs-plug' ); ?>
							</a>
							<a href="mailto:?subject=<?php echo esc_attr( get_the_title() ); ?>&body=<?php echo esc_url( get_permalink() ); ?>" class="jobs-plug-share-link">
								<span class="dashicons dashicons-email-alt"></span>
								<?php esc_html_e( 'Email', 'jobs-plug' ); ?>
							</a>
						</div>
					</div>

				</div>

			</div>

			<!-- Related Jobs -->
			<?php
			$related_args = array(
				'post_type'      => 'job',
				'posts_per_page' => 3,
				'post__not_in'   => array( get_the_ID() ),
				'orderby'        => 'rand',
			);

			// Try to get jobs from same category.
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$related_args['tax_query'] = array(
					array(
						'taxonomy' => 'job_category',
						'field'    => 'term_id',
						'terms'    => $categories[0]->term_id,
					),
				);
			}

			$related_query = new WP_Query( $related_args );

			if ( $related_query->have_posts() ) :
				?>
				<div class="jobs-plug-related">
					<h2><?php esc_html_e( 'Related Jobs', 'jobs-plug' ); ?></h2>

					<div class="jobs-plug-grid">
						<?php
						while ( $related_query->have_posts() ) :
							$related_query->the_post();

							// Get meta data for related job.
							$related_salary      = get_post_meta( get_the_ID(), '_job_salary', true );
							$related_is_featured = get_post_meta( get_the_ID(), '_job_is_featured', true );
							$related_expiry      = get_post_meta( get_the_ID(), '_job_expiry_date', true );

							// Get taxonomy terms.
							$related_employers = get_the_terms( get_the_ID(), 'employer' );
							$related_locations = get_the_terms( get_the_ID(), 'location' );
							$related_types     = get_the_terms( get_the_ID(), 'job_type' );
							?>

							<article class="jobs-plug-card <?php echo $related_is_featured ? 'jobs-plug-featured' : ''; ?>">

								<?php if ( $related_is_featured ) : ?>
									<div class="jobs-plug-badge-featured">
										<span class="dashicons dashicons-star-filled"></span>
										<?php esc_html_e( 'Featured', 'jobs-plug' ); ?>
									</div>
								<?php endif; ?>

								<?php
								// Display employer logo if available.
								$related_employer_logo = \JobsPlug\Jobs_Plug::get_instance()->get_employer_logo( get_the_ID(), 'full' );
								if ( ! empty( $related_employer_logo ) ) :
									?>
									<div class="jobs-plug-card-thumbnail">
										<?php echo $related_employer_logo; ?>
									</div>
								<?php endif; ?>

								<div class="jobs-plug-card-content">
									<h3 class="jobs-plug-card-title">
										<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									</h3>

									<div class="jobs-plug-card-meta">
										<?php if ( ! empty( $related_employers ) && ! is_wp_error( $related_employers ) ) : ?>
											<div class="jobs-plug-meta-item">
												<span class="dashicons dashicons-building"></span>
												<span><?php echo esc_html( $related_employers[0]->name ); ?></span>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $related_locations ) && ! is_wp_error( $related_locations ) ) : ?>
											<div class="jobs-plug-meta-item">
												<span class="dashicons dashicons-location"></span>
												<span><?php echo esc_html( $related_locations[0]->name ); ?></span>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $related_types ) && ! is_wp_error( $related_types ) ) : ?>
											<div class="jobs-plug-meta-item jobs-plug-badge-type">
												<?php echo esc_html( $related_types[0]->name ); ?>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $related_salary ) ) : ?>
											<div class="jobs-plug-meta-item jobs-plug-salary">
												<span class="dashicons dashicons-money-alt"></span>
												<span><?php echo esc_html( number_format_i18n( $related_salary ) ); ?></span>
											</div>
										<?php endif; ?>
									</div>

									<div class="jobs-plug-card-footer">
										<a href="<?php the_permalink(); ?>" class="jobs-plug-card-button">
											<?php esc_html_e( 'View Job', 'jobs-plug' ); ?>
											<span class="dashicons dashicons-arrow-right-alt2"></span>
										</a>
									</div>
								</div>
							</article>

						<?php endwhile; ?>
					</div>
				</div>
				<?php
				wp_reset_postdata();
			endif;
			?>

		</div>

	<?php endwhile; ?>
</div>

<?php
get_footer();
