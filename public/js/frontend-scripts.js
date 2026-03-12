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
		var mobileFilterQuery = window.matchMedia('(max-width: 768px)');
		var defaultSortValue = $('#pwm-sort-filter').val() || 'date_desc';
		var priceInputSelector = '#pwm-min-price-filter, #pwm-max-price-filter';

		function sanitizePriceString(value) {
			if (typeof value !== 'string') {
				return '';
			}

			var sanitized = value.replace(/[^0-9.]/g, '');
			var firstDotIndex = sanitized.indexOf('.');
			if (firstDotIndex !== -1) {
				sanitized = sanitized.slice(0, firstDotIndex + 1) + sanitized.slice(firstDotIndex + 1).replace(/\./g, '');
			}

			return sanitized;
		}

		function normalizePriceInputValue(value) {
			var sanitized = sanitizePriceString(value);
			if (!sanitized) {
				return '';
			}

			var numeric = parseFloat(sanitized);
			if (isNaN(numeric) || numeric < 0) {
				return '';
			}

			return (Math.round(numeric * 100) / 100).toString();
		}

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
			var sortValue = $('#pwm-sort-filter').val() || defaultSortValue;
			var columns = $('.personal-wishlist-container').data('columns') || 3;

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
			var searchValue = $('#pwm-search-filter').val();
			var categoryValue = $('#pwm-category-filter').val();
			var minPrice = $('#pwm-min-price-filter').val();
			var maxPrice = $('#pwm-max-price-filter').val();
			var minPriceDefault = parseFloat($('#pwm-min-price-filter').data('default-value'));
			var maxPriceDefault = parseFloat($('#pwm-max-price-filter').data('default-value'));
			var sortValue = $('#pwm-sort-filter').val();
			var activeFilterCount = 0;

			if (searchValue) {
				activeFilterCount += 1;
			}

			if (categoryValue) {
				activeFilterCount += 1;
			}

			var hasCustomMin = minPrice !== '' && !isNaN(parseFloat(minPrice)) && parseFloat(minPrice) !== minPriceDefault;
			var hasCustomMax = maxPrice !== '' && !isNaN(parseFloat(maxPrice)) && parseFloat(maxPrice) !== maxPriceDefault;

			if (hasCustomMin || hasCustomMax) {
				activeFilterCount += 1;
			}

			// Add sort filter if not default
			if (sortValue && sortValue !== defaultSortValue) {
				activeFilterCount += 1;
			}

			var $inlineReset = $('#pwm-inline-reset-wrap');

			if (activeFilterCount > 0) {
				$inlineReset.show();
			} else {
				$inlineReset.hide();
			}

			updateMobileFilterCount(activeFilterCount);
		}

		function updateMobileFilterCount(count) {
			var $count = $('#pwm-mobile-filter-count');
			if (!$count.length) {
				return;
			}

			if (count > 0) {
				$count.text(count).show();
			} else {
				$count.hide();
			}
		}

		function setMobileFiltersOpen(isOpen) {
			var $wrapper = $('.wishlist-filters-wrapper');
			var $toggle = $('#pwm-mobile-filter-toggle');

			$wrapper.toggleClass('pwm-mobile-open', isOpen);
			$toggle.attr('aria-expanded', isOpen ? 'true' : 'false');
		}

		function syncMobileFiltersState() {
			if (!$('#pwm-mobile-filter-toggle').length) {
				return;
			}

			if (mobileFilterQuery.matches) {
				if (!$('.wishlist-filters-wrapper').hasClass('pwm-mobile-open')) {
					setMobileFiltersOpen(false);
				}
			} else {
				setMobileFiltersOpen(true);
			}
		}

		syncMobileFiltersState();

		$(document).on('click', '#pwm-mobile-filter-toggle', function() {
			setMobileFiltersOpen(!$('.wishlist-filters-wrapper').hasClass('pwm-mobile-open'));
		});

		$(document).on('click', '#pwm-mobile-filter-done', function() {
			setMobileFiltersOpen(false);
		});

		// Filter event listeners
		$('#pwm-search-filter').on('input', debounce(applyFilters, 300));
		$('#pwm-category-filter').on('change', applyFilters);
		$(document).on('input', priceInputSelector, function() {
			var sanitized = sanitizePriceString($(this).val() || '');
			if ($(this).val() !== sanitized) {
				$(this).val(sanitized);
			}
		});

		$(document).on('blur change', priceInputSelector, function() {
			$(this).val(normalizePriceInputValue($(this).val() || ''));
		});

		$(priceInputSelector).on('input', debounce(applyFilters, 500));
		$('#pwm-sort-filter').on('change', applyFilters);

		// Clear all filters
		$(document).on('click', '.pwm-clear-filters-trigger, #pwm-clear-filters-empty', function() {
			$('#pwm-search-filter').val('');
			$('#pwm-category-filter').val('');
			$('#pwm-min-price-filter').val('');
			$('#pwm-max-price-filter').val('');
			$('#pwm-sort-filter').val(defaultSortValue);
			currentSearchTerm = '';
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
				syncMobileFiltersState();
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
