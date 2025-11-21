/**
 * Jobs Plug - Live Filter and Search
 *
 * @package JobsPlug
 */

(function() {
	'use strict';

	// Wait for DOM to be ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	function init() {
		const form = document.querySelector('.jobs-plug-search-form');
		const resultsContainer = document.querySelector('.jobs-plug-listings');

		if (!form || !resultsContainer) {
			return;
		}

		// Get all filter inputs.
		const searchInput = form.querySelector('input[name="s"]');
		const categorySelect = form.querySelector('select[name="job_category"]');
		const employerSelect = form.querySelector('select[name="employer"]');
		const locationSelect = form.querySelector('select[name="location"]');
		const jobTypeSelect = form.querySelector('select[name="job_type"]');
		const salaryMinInput = form.querySelector('input[name="salary_min"]');
		const salaryMaxInput = form.querySelector('input[name="salary_max"]');
		const featuredCheckbox = form.querySelector('input[name="featured_only"]');
		const remoteCheckbox = form.querySelector('input[name="remote_only"]');

		let searchTimeout = null;

		// Prevent form submission.
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			performFilter();
		});

		// Live search with debounce.
		if (searchInput) {
			searchInput.addEventListener('input', function() {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(performFilter, 500);
			});
		}

		// Immediate filter on dropdown change.
		const selectInputs = [categorySelect, employerSelect, locationSelect, jobTypeSelect];
		selectInputs.forEach(function(input) {
			if (input) {
				input.addEventListener('change', performFilter);
			}
		});

		// Filter on salary change with debounce.
		[salaryMinInput, salaryMaxInput].forEach(function(input) {
			if (input) {
				input.addEventListener('input', function() {
					clearTimeout(searchTimeout);
					searchTimeout = setTimeout(performFilter, 800);
				});
			}
		});

		// Immediate filter on checkbox change.
		if (featuredCheckbox) {
			featuredCheckbox.addEventListener('change', performFilter);
		}
		if (remoteCheckbox) {
			remoteCheckbox.addEventListener('change', performFilter);
		}

		// Perform the actual filtering.
		function performFilter() {
			// Show loading state.
			resultsContainer.style.opacity = '0.5';
			resultsContainer.style.pointerEvents = 'none';

			// Build query parameters.
			const params = new URLSearchParams();

			if (searchInput && searchInput.value) {
				params.append('s', searchInput.value);
			}
			if (categorySelect && categorySelect.value) {
				params.append('job_category', categorySelect.value);
			}
			if (employerSelect && employerSelect.value) {
				params.append('employer', employerSelect.value);
			}
			if (locationSelect && locationSelect.value) {
				params.append('location', locationSelect.value);
			}
			if (jobTypeSelect && jobTypeSelect.value) {
				params.append('job_type', jobTypeSelect.value);
			}
			if (salaryMinInput && salaryMinInput.value) {
				params.append('salary_min', salaryMinInput.value);
			}
			if (salaryMaxInput && salaryMaxInput.value) {
				params.append('salary_max', salaryMaxInput.value);
			}
			if (featuredCheckbox && featuredCheckbox.checked) {
				params.append('featured_only', '1');
			}
			if (remoteCheckbox && remoteCheckbox.checked) {
				params.append('remote_only', '1');
			}

			// Add AJAX action.
			params.append('action', 'jobs_plug_filter');

			// Perform AJAX request.
			fetch(jobsPlugAjax.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: params.toString()
			})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				if (data.success) {
					// Update the results.
					resultsContainer.innerHTML = data.data.html;

					// Update URL without reloading.
					const newUrl = new URL(window.location.href);
					newUrl.search = '';
					params.forEach(function(value, key) {
						if (key !== 'action') {
							newUrl.searchParams.set(key, value);
						}
					});
					window.history.pushState({}, '', newUrl);
				} else {
					console.error('Filter error:', data.data);
				}
			})
			.catch(function(error) {
				console.error('AJAX error:', error);
			})
			.finally(function() {
				// Remove loading state.
				resultsContainer.style.opacity = '1';
				resultsContainer.style.pointerEvents = 'auto';
			});
		}
	}
})();
