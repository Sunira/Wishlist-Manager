/**
 * Frontend scripts for Personal Wishlist Manager - Modern Design
 *
 * @package Personal_Wishlist_Manager
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		/**
		 * Real-time filtering with debounce
		 */
		var filterTimeout;
		var currentSearchTerm = '';

		function debounce(func, wait) {
			return function() {
				var context = this;
				var args = arguments;
				clearTimeout(filterTimeout);
				filterTimeout = setTimeout(function() {
					func.apply(context, args);
				}, wait);
			};
		}

		function applyFilters() {
			var searchValue = $('#pwm-search-filter').val();
			var categoryValue = $('#pwm-category-filter').val();
			var minPrice = $('#pwm-min-price-filter').val();
			var maxPrice = $('#pwm-max-price-filter').val();
			var sortValue = $('#pwm-sort-filter').val() || 'alphabetical';
			var columns = $('.personal-wishlist-container').data('columns') || 3;

			// Get selected tags
			var selectedTags = $('#pwm-tags-filter').val() || [];

			currentSearchTerm = searchValue;

			// Show loading state
			$('#pwm-grid').addClass('pwm-loading');

			// Make AJAX request
			$.ajax({
				url: pwmFrontend.ajaxurl,
				type: 'POST',
				data: {
					action: 'pwm_filter_items',
					nonce: pwmFrontend.nonce,
					search: searchValue,
					category: categoryValue,
					tags: selectedTags,
					min_price: minPrice || 0,
					max_price: maxPrice || 999999,
					sort: sortValue,
					columns: columns
				},
				success: function(response) {
					if (response.success) {
						// Update grid HTML
						$('#pwm-grid').html(response.data.html);

						// Update count
						if (response.data.count === 0) {
							$('.wishlist-results-count').text('No items found');
						} else if (response.data.count === 1) {
							$('.wishlist-results-count').text('Showing 1 item');
						} else {
							$('.wishlist-results-count').text('Showing ' + response.data.count + ' items');
						}

						// Highlight search terms if present
						if (response.data.search_term) {
							highlightSearchTerms(response.data.search_term);
						}

						// Update active filters display
						updateActiveFilters();

						// Re-initialize lazy loading for new images
						initLazyLoading();
					}
				},
				complete: function() {
					// Remove loading state
					$('#pwm-grid').removeClass('pwm-loading');
				},
				error: function() {
					$('#pwm-grid').removeClass('pwm-loading');
					alert('An error occurred while filtering items. Please try again.');
				}
			});
		}

		function highlightSearchTerms(searchTerm) {
			if (!searchTerm || searchTerm.trim() === '') {
				return;
			}

			var $grid = $('#pwm-grid');
			var regex = new RegExp('(' + escapeRegex(searchTerm) + ')', 'gi');

			// Highlight in titles
			$grid.find('.wishlist-card-title').each(function() {
				var $this = $(this);
				var text = $this.text();
				var highlightedText = text.replace(regex, '<mark class="pwm-highlight">$1</mark>');
				$this.html(highlightedText);
			});

			// Highlight in categories
			$grid.find('.wishlist-card-category').each(function() {
				var $this = $(this);
				var text = $this.text();
				var highlightedText = text.replace(regex, '<mark class="pwm-highlight">$1</mark>');
				$this.html(highlightedText);
			});

			// Highlight in reasons
			$grid.find('.wishlist-card-reason').each(function() {
				var $this = $(this);
				var html = $this.html();
				// Only highlight text nodes, not HTML tags
				var tempDiv = $('<div>').html(html);
				highlightTextNodes(tempDiv[0], regex);
				$this.html(tempDiv.html());
			});
		}

		function highlightTextNodes(node, regex) {
			if (node.nodeType === 3) {
				// Text node
				var text = node.nodeValue;
				if (regex.test(text)) {
					var highlightedText = text.replace(regex, '<mark class="pwm-highlight">$1</mark>');
					var span = document.createElement('span');
					span.innerHTML = highlightedText;
					node.parentNode.replaceChild(span, node);
				}
			} else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE') {
				// Element node
				for (var i = 0; i < node.childNodes.length; i++) {
					highlightTextNodes(node.childNodes[i], regex);
				}
			}
		}

		function escapeRegex(string) {
			return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		}

		function updateActiveFilters() {
			var filters = [];
			var searchValue = $('#pwm-search-filter').val();
			var categoryValue = $('#pwm-category-filter').val();
			var minPrice = $('#pwm-min-price-filter').val();
			var maxPrice = $('#pwm-max-price-filter').val();
			var sortValue = $('#pwm-sort-filter').val();

			// Get selected tags
			var selectedTags = $('#pwm-tags-filter').val() || [];

			if (searchValue) {
				filters.push({
					type: 'search',
					label: 'Search: ' + searchValue
				});
			}

			if (categoryValue) {
				filters.push({
					type: 'category',
					label: 'Category: ' + categoryValue
				});
			}

			// Add tag filters
			if (selectedTags.length > 0) {
				selectedTags.forEach(function(tag) {
					filters.push({
						type: 'tag',
						label: 'Tag: ' + tag,
						value: tag
					});
				});
			}

			if (minPrice || maxPrice) {
				var priceLabel = 'Price: ';
				if (minPrice && maxPrice) {
					priceLabel += '$' + minPrice + ' - $' + maxPrice;
				} else if (minPrice) {
					priceLabel += 'Min $' + minPrice;
				} else {
					priceLabel += 'Max $' + maxPrice;
				}
				filters.push({
					type: 'price',
					label: priceLabel
				});
			}

			// Add sort filter if not default
			if (sortValue && sortValue !== 'alphabetical') {
				var sortLabel = 'Sort: ';
				switch(sortValue) {
					case 'price_asc':
						sortLabel += 'Price: Low to High';
						break;
					case 'price_desc':
						sortLabel += 'Price: High to Low';
						break;
					case 'date_desc':
						sortLabel += 'Newest First';
						break;
					case 'date_asc':
						sortLabel += 'Oldest First';
						break;
				}
				filters.push({
					type: 'sort',
					label: sortLabel
				});
			}

			var $activeFilters = $('#pwm-active-filters');
			var $activeFiltersList = $('#pwm-active-filters-list');

			if (filters.length > 0) {
				$activeFiltersList.empty();
				filters.forEach(function(filter) {
					var badge = $('<span class="active-filter-badge" data-filter-type="' + filter.type + '"' +
						(filter.value ? ' data-filter-value="' + filter.value + '"' : '') + '>' +
						filter.label +
						'<button type="button" class="remove-filter" aria-label="Remove filter">&times;</button>' +
						'</span>');
					$activeFiltersList.append(badge);
				});
				$activeFilters.show();
			} else {
				$activeFilters.hide();
			}
		}

		function initLazyLoading() {
			if ('loading' in HTMLImageElement.prototype) {
				$('#pwm-grid .wishlist-card-image img').each(function() {
					$(this).attr('loading', 'lazy');
				});
			}
		}

		// Custom multiselect dropdown functionality
		function initMultiselect() {
			var $button = $('#pwm-tags-filter-btn');
			var $dropdown = $('#pwm-tags-dropdown');
			var $hiddenSelect = $('#pwm-tags-filter');
			var $checkboxes = $dropdown.find('input[type="checkbox"]');

			// Toggle dropdown
			$button.on('click', function(e) {
				e.stopPropagation();
				$button.toggleClass('active');
				$dropdown.toggleClass('active');
			});

			// Handle checkbox changes
			$checkboxes.on('change', function() {
				syncMultiselectValues();
				updateTagsDisplay();
				applyFilters();
			});

			// Sync checkboxes with hidden select
			function syncMultiselectValues() {
				var selectedValues = [];
				$checkboxes.filter(':checked').each(function() {
					selectedValues.push($(this).val());
				});
				$hiddenSelect.val(selectedValues);
			}

			// Close dropdown when clicking outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.pwm-multiselect-wrapper').length) {
					$button.removeClass('active');
					$dropdown.removeClass('active');
				}
			});

			// Prevent dropdown from closing when clicking inside
			$dropdown.on('click', function(e) {
				e.stopPropagation();
			});
		}

		// Update tags display to show count and selected tags
		function updateTagsDisplay() {
			var selectedValues = [];
			$('#pwm-tags-dropdown input[type="checkbox"]:checked').each(function() {
				selectedValues.push($(this).next('span').text().split(' (')[0]);
			});

			var $button = $('#pwm-tags-filter-btn');
			var $text = $button.find('.pwm-multiselect-text');
			var $label = $('.filter-label[for="pwm-tags-filter"]');
			var baseText = $label.text().replace(/\s*\(\d+\)$/, '');

			if (selectedValues.length > 0) {
				// Update button text
				if (selectedValues.length === 1) {
					$text.text(selectedValues[0]);
				} else {
					$text.text(selectedValues.length + ' tags selected');
				}
				// Update label count
				$label.html(baseText + ' <span class="filter-count">(' + selectedValues.length + ')</span>');
			} else {
				$text.text('Select tags...');
				$label.text(baseText);
			}
		}

		// Initialize custom multiselect
		initMultiselect();

		// Filter event listeners
		$('#pwm-search-filter').on('input', debounce(applyFilters, 300));
		$('#pwm-category-filter').on('change', applyFilters);
		$('#pwm-min-price-filter, #pwm-max-price-filter').on('input', debounce(applyFilters, 500));
		$('#pwm-sort-filter').on('change', applyFilters);

		// Clear all filters
		$(document).on('click', '#pwm-clear-filters, #pwm-clear-filters-empty', function() {
			$('#pwm-search-filter').val('');
			$('#pwm-category-filter').val('');
			$('#pwm-tags-dropdown input[type="checkbox"]').prop('checked', false);
			$('#pwm-tags-filter').val(null);
			$('#pwm-min-price-filter').val('');
			$('#pwm-max-price-filter').val('');
			$('#pwm-sort-filter').val('alphabetical');
			currentSearchTerm = '';
			updateTagsDisplay();
			applyFilters();
		});

		// Remove individual filter
		$(document).on('click', '.remove-filter', function(e) {
			e.stopPropagation();
			var $badge = $(this).closest('.active-filter-badge');
			var filterType = $badge.data('filter-type');
			var filterValue = $badge.data('filter-value');

			switch(filterType) {
				case 'search':
					$('#pwm-search-filter').val('');
					break;
				case 'category':
					$('#pwm-category-filter').val('');
					break;
				case 'tag':
					// Uncheck the specific tag checkbox
					$('#pwm-tags-dropdown input[type="checkbox"][value="' + filterValue + '"]').prop('checked', false);
					var selectedValues = [];
					$('#pwm-tags-dropdown input[type="checkbox"]:checked').each(function() {
						selectedValues.push($(this).val());
					});
					$('#pwm-tags-filter').val(selectedValues);
					updateTagsDisplay();
					break;
				case 'price':
					$('#pwm-min-price-filter').val('');
					$('#pwm-max-price-filter').val('');
					break;
				case 'sort':
					$('#pwm-sort-filter').val('alphabetical');
					break;
			}

			applyFilters();
		});

		/**
		 * Delete confirmation for wishlist items
		 */
		$('.pwm-delete-item').on('click', function(e) {
			if (!confirm('Are you sure you want to delete this item?')) {
				e.preventDefault();
				return false;
			}
		});

		/**
		 * Lazy load images for better performance
		 */
		if ('loading' in HTMLImageElement.prototype) {
			// Native lazy loading supported
			$('.wishlist-card-image img').each(function() {
				$(this).attr('loading', 'lazy');
			});
		} else if ('IntersectionObserver' in window) {
			// Fallback to IntersectionObserver
			var imageObserver = new IntersectionObserver(function(entries, observer) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						var img = entry.target;
						if (img.dataset.src) {
							img.src = img.dataset.src;
							img.removeAttribute('data-src');
							observer.unobserve(img);
						}
					}
				});
			});

			$('.wishlist-card-image img').each(function() {
				var currentSrc = $(this).attr('src');
				if (currentSrc) {
					$(this).attr('data-src', currentSrc);
					$(this).attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
					imageObserver.observe(this);
				}
			});
		}

		/**
		 * Keyboard navigation for cards
		 */
		$('.wishlist-card-button').on('keydown', function(e) {
			// Allow Space and Enter to activate
			if (e.which === 32 || e.which === 13) {
				e.preventDefault();
				window.open($(this).attr('href'), '_blank');
			}
		});


		/**
		 * Handle window resize for responsive behavior
		 */
		var resizeTimeout;
		$(window).on('resize', function() {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function() {
				// Re-initialize any responsive features if needed
			}, 250);
		});

		/**
		 * Initialize tooltips (if using a tooltip library)
		 */
		if (typeof $.fn.tooltip === 'function') {
			$('[title]').tooltip({
				position: {
					my: 'center bottom-10',
					at: 'center top'
				}
			});
		}

		/**
		 * Add animation classes on scroll (optional enhancement)
		 */
		if ('IntersectionObserver' in window) {
			var fadeObserver = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						$(entry.target).addClass('fade-in');
						fadeObserver.unobserve(entry.target);
					}
				});
			}, {
				threshold: 0.1
			});

			$('.wishlist-card').each(function() {
				fadeObserver.observe(this);
			});
		}

	});

})(jQuery);
