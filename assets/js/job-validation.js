/**
 * Jobs Plug - Job Post Taxonomy Validation
 *
 * @package JobsPlug
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Only run on job post edit screen.
		if ($('body').hasClass('post-type-job')) {
			initValidation();
		}
	});

	function initValidation() {
		const publishButton = $('#publish');
		const requiredTaxonomies = {
			'job_category': {
				name: 'Job Category',
				type: 'hierarchical',
				selector: '#job_categorychecklist input[type="checkbox"]'
			},
			'employer': {
				name: 'Employer',
				type: 'non-hierarchical',
				selector: '#employer .tagchecklist',
				input: '#new-tag-employer'
			},
			'location': {
				name: 'Location',
				type: 'hierarchical',
				selector: '#locationchecklist input[type="checkbox"]'
			},
			'job_type': {
				name: 'Job Type',
				type: 'non-hierarchical',
				selector: '#job_type .tagchecklist',
				input: '#new-tag-job_type'
			}
		};

		// Validate on publish button click.
		publishButton.on('click', function(e) {
			const errors = validateTaxonomies();

			if (errors.length > 0) {
				e.preventDefault();
				e.stopImmediatePropagation();

				// Show error message.
				let errorMessage = 'Please select at least one term for the following required fields:\n\n';
				errorMessage += errors.join('\n');

				alert(errorMessage);

				// Highlight first error.
				highlightError(errors[0]);

				return false;
			}
		});

		// Live validation - show/hide warnings.
		function setupLiveValidation() {
			// For hierarchical taxonomies (checkboxes).
			$('#job_categorychecklist input[type="checkbox"], #locationchecklist input[type="checkbox"]').on('change', function() {
				updatePublishButtonState();
			});

			// For non-hierarchical taxonomies (tags).
			$(document).on('DOMNodeInserted', '#employer .tagchecklist, #job_type .tagchecklist', function() {
				updatePublishButtonState();
			});

			$(document).on('DOMNodeRemoved', '#employer .tagchecklist, #job_type .tagchecklist', function() {
				updatePublishButtonState();
			});
		}

		function validateTaxonomies() {
			const errors = [];

			$.each(requiredTaxonomies, function(key, taxonomy) {
				if (taxonomy.type === 'hierarchical') {
					// Check if at least one checkbox is checked.
					const checked = $(taxonomy.selector + ':checked').length;
					if (checked === 0) {
						errors.push('• ' + taxonomy.name);
					}
				} else {
					// Check if there are any tags in the tagchecklist.
					const tags = $(taxonomy.selector + ' .ntdelbutton').length;
					if (tags === 0) {
						errors.push('• ' + taxonomy.name);
					}
				}
			});

			return errors;
		}

		function updatePublishButtonState() {
			const errors = validateTaxonomies();

			if (errors.length > 0) {
				// Add visual indicator to publish button.
				if (!$('#taxonomy-validation-warning').length) {
					publishButton.before('<div id="taxonomy-validation-warning" style="background: #f0ad4e; color: #fff; padding: 8px 12px; margin-bottom: 10px; border-radius: 3px; font-size: 13px;">⚠ Required taxonomies missing</div>');
				}
			} else {
				// Remove warning if all taxonomies are filled.
				$('#taxonomy-validation-warning').remove();
			}
		}

		function highlightError(errorField) {
			// Remove '• ' prefix from error field.
			const fieldName = errorField.replace('• ', '');

			// Find and highlight the corresponding taxonomy box.
			$.each(requiredTaxonomies, function(key, taxonomy) {
				if (taxonomy.name === fieldName) {
					const $taxonomyBox = $(taxonomy.selector).closest('.postbox');

					if ($taxonomyBox.length) {
						// Scroll to the taxonomy box.
						$('html, body').animate({
							scrollTop: $taxonomyBox.offset().top - 100
						}, 500);

						// Add temporary highlight.
						$taxonomyBox.css('border', '2px solid #dc3232');
						setTimeout(function() {
							$taxonomyBox.css('border', '');
						}, 3000);
					}
				}
			});
		}

		// Initialize live validation.
		setupLiveValidation();

		// Initial state check.
		updatePublishButtonState();
	}

})(jQuery);
