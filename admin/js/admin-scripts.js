/**
 * Admin scripts for Personal Wishlist Manager
 *
 * @package Personal_Wishlist_Manager
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		/**
		 * Shortcode Generator
		 */
		function generateShortcode() {
			var params = [];
			var baseShortcode = 'personal_wishlist';

			// Get all generator controls
			var columns = $('#pwm-gen-columns').val();
			var category = $('#pwm-gen-category').val();
			var sort = $('#pwm-gen-sort').val();
			var limit = $('#pwm-gen-limit').val();
			var showFilters = $('#pwm-gen-show-filters').is(':checked');
			var showSearch = $('#pwm-gen-show-search').is(':checked');
			var showCategory = $('#pwm-gen-show-category').is(':checked');
			var showPrice = $('#pwm-gen-show-price').is(':checked');
			var showSort = $('#pwm-gen-show-sort').is(':checked');

			// Add columns if not default (3)
			if (columns && columns !== '3') {
				params.push('columns="' + columns + '"');
			}

			// Add category if selected
			if (category) {
				params.push('category="' + category + '"');
			}

			// Add sort if not default (alphabetical)
			if (sort && sort !== 'alphabetical') {
				params.push('sort="' + sort + '"');
			}

			// Add limit if specified
			if (limit) {
				params.push('limit="' + limit + '"');
			}

			// Add filter visibility params (only if not true/default)
			if (!showFilters) {
				params.push('show_filters="false"');
			} else {
				// Only add individual filter params if main filter is enabled
				if (!showSearch) {
					params.push('show_search="false"');
				}
				if (!showCategory) {
					params.push('show_category="false"');
				}
				if (!showPrice) {
					params.push('show_price="false"');
				}
				if (!showSort) {
					params.push('show_sort="false"');
				}
			}

			// Build the shortcode
			var shortcode = '[' + baseShortcode;
			if (params.length > 0) {
				shortcode += ' ' + params.join(' ');
			}
			shortcode += ']';

			// Update the display
			$('#pwm-generated-shortcode code').text(shortcode);
		}

		// Update shortcode on any control change
		$('.pwm-gen-control').on('change input', generateShortcode);

		// Toggle sub-options when main filter checkbox is changed
		$('#pwm-gen-show-filters').on('change', function() {
			var $subOptions = $('#pwm-gen-filter-options');
			if ($(this).is(':checked')) {
				$subOptions.removeClass('disabled');
				$subOptions.find('input[type="checkbox"]').prop('disabled', false);
			} else {
				$subOptions.addClass('disabled');
				$subOptions.find('input[type="checkbox"]').prop('disabled', true);
			}
			generateShortcode();
		});

		// Copy to clipboard functionality
		$('#pwm-copy-shortcode').on('click', function() {
			var $button = $(this);
			var shortcode = $('#pwm-generated-shortcode code').text();

			// Try to use the Clipboard API
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(shortcode).then(function() {
					// Success
					showCopySuccess($button);
				}).catch(function() {
					// Fallback
					fallbackCopyToClipboard(shortcode, $button);
				});
			} else {
				// Fallback for older browsers
				fallbackCopyToClipboard(shortcode, $button);
			}
		});

		function fallbackCopyToClipboard(text, $button) {
			// Create temporary textarea
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(text).select();

			try {
				document.execCommand('copy');
				showCopySuccess($button);
			} catch (err) {
				alert('Failed to copy. Please copy manually: ' + text);
			}

			$temp.remove();
		}

		function showCopySuccess($button) {
			var originalText = $button.html();
			$button.addClass('copied');
			$button.html('<span class="dashicons dashicons-yes"></span> Copied!');

			setTimeout(function() {
				$button.removeClass('copied');
				$button.html(originalText);
			}, 2000);
		}

		// Initialize shortcode generator
		if ($('#pwm-generated-shortcode').length > 0) {
			generateShortcode();
		}

		/**
		 * Media Library Integration
		 */
		if ($('.pwm-upload-image').length > 0) {
			var mediaUploader;

			$('.pwm-upload-image').on('click', function(e) {
				e.preventDefault();

				// If the uploader object has already been created, reopen the dialog
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}

				// Create the media uploader
				mediaUploader = wp.media({
					title: 'Select or Upload Product Image',
					button: {
						text: 'Use this image'
					},
					library: {
						type: 'image'
					},
					multiple: false
				});

				// When an image is selected, run a callback
				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();

					// Store the attachment ID
					$('#image_id').val(attachment.id);

					// Use the full-size image URL, or fall back to the largest available
					var imageUrl = attachment.url;
					if (attachment.sizes && attachment.sizes.large) {
						imageUrl = attachment.sizes.large.url;
					}

					// Update the URL field
					$('#image_url').val(imageUrl);

					// Update the preview with animation
					$('#pwm-image-preview').fadeOut(200, function() {
						$(this).html('<img src="' + imageUrl + '" alt="Preview" style="max-width: 300px; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">').fadeIn(200);
					});

					// Show remove button if it doesn't exist
					if ($('.pwm-remove-image').length === 0) {
						$('.pwm-upload-image').after('<button type="button" class="button button-link-delete pwm-remove-image">Remove</button>');
					}
				});

				// Open the uploader
				mediaUploader.open();
			});
		}

		/**
		 * Remove Image
		 */
		$(document).on('click', '.pwm-remove-image', function(e) {
			e.preventDefault();

			if (confirm('Are you sure you want to remove this image?')) {
				$('#image_url').val('');
				$('#image_id').val('');
				$('#pwm-image-preview').fadeOut(200, function() {
					$(this).html('');
				});
				$(this).remove();
			}
		});

		/**
		 * Manual Image URL Preview
		 */
		var imagePreviewTimeout;
		$('#image_url').on('input paste', function() {
			var $input = $(this);

			clearTimeout(imagePreviewTimeout);
			imagePreviewTimeout = setTimeout(function() {
				var url = $input.val().trim();

				if (url && isValidUrl(url)) {
					// Update preview
					$('#pwm-image-preview').fadeOut(200, function() {
						$(this).html('<img src="' + url + '" alt="Preview" style="max-width: 300px; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">').fadeIn(200);
					});

					// Show remove button if it doesn't exist
					if ($('.pwm-remove-image').length === 0) {
						$('.pwm-upload-image').after('<button type="button" class="button button-link-delete pwm-remove-image">Remove</button>');
					}
				} else if (url === '') {
					$('#pwm-image-preview').fadeOut(200, function() {
						$(this).html('');
					});
					$('.pwm-remove-image').remove();
				}
			}, 500);
		});

		/**
		 * Delete Item Confirmation
		 */
		$('.pwm-delete-item').on('click', function(e) {
			if (!confirm(pwmAdmin.confirmDelete)) {
				e.preventDefault();
				return false;
			}
		});

		/**
		 * Bulk Delete Confirmation
		 */
		$('#doaction, #doaction2').on('click', function(e) {
			var action = $(this).siblings('select').val();
			if (action === 'bulk-delete') {
				if (!confirm(pwmAdmin.confirmBulkDelete)) {
					e.preventDefault();
					return false;
				}
			}
		});

		/**
		 * URL Validation Helper
		 */
		function isValidUrl(url) {
			try {
				new URL(url);
				return true;
			} catch (e) {
				return false;
			}
		}

		/**
		 * Live Search in Items List
		 */
		var searchTimeout;
		$('input[name="s"]').on('keyup', function() {
			clearTimeout(searchTimeout);
			var $form = $(this).closest('form');
			var $table = $('.wp-list-table tbody');

			searchTimeout = setTimeout(function() {
				// Could implement AJAX search here if needed
				// For now, the default WordPress search works
			}, 500);
		});

		/**
		 * Quick Edit (if implemented)
		 */
		$('.editinline').on('click', function() {
			// WordPress quick edit functionality
			// Can be extended for custom fields if needed
		});

	});

})(jQuery);
