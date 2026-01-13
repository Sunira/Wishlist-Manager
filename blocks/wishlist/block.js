/**
 * Personal Wishlist Block - Vanilla JavaScript
 *
 * @package Personal_Wishlist_Manager
 */

(function(wp) {
	'use strict';

	// Extract WordPress components
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var CheckboxControl = wp.components.CheckboxControl;
	var ServerSideRender = wp.serverSideRender;

	/**
	 * Register Personal Wishlist Block
	 */
	registerBlockType('personal-wishlist-manager/wishlist', {
		title: 'Personal Wishlist',
		description: 'Display wishlist items filtered by categories',
		icon: 'heart',
		category: 'widgets',
		keywords: ['wishlist', 'products', 'shopping'],
		supports: {
			html: false,
			align: ['wide', 'full']
		},

		attributes: {
			categories: {
				type: 'array',
				default: []
			},
			columns: {
				type: 'number',
				default: 3
			},
			sort: {
				type: 'string',
				default: 'alphabetical'
			},
			limit: {
				type: 'number',
				default: -1
			},
			showFilters: {
				type: 'boolean',
				default: true
			},
			userId: {
				type: 'number',
				default: 0
			}
		},

		/**
		 * Edit function - renders the block in the editor
		 */
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			// Helper function to check if category is selected
			function isCategorySelected(categoryName) {
				return attributes.categories.indexOf(categoryName) !== -1;
			}

			// Helper function to toggle category selection
			function toggleCategory(categoryName, checked) {
				var newCategories = attributes.categories.slice();

				if (checked) {
					if (newCategories.indexOf(categoryName) === -1) {
						newCategories.push(categoryName);
					}
				} else {
					var index = newCategories.indexOf(categoryName);
					if (index > -1) {
						newCategories.splice(index, 1);
					}
				}

				setAttributes({ categories: newCategories });
			}

			// Build category checkboxes
			var categoryControls = [];

			if (pwmBlockData.categories && pwmBlockData.categories.length > 0) {
				categoryControls.push(
					el('p', {
						style: {
							fontWeight: 'bold',
							marginBottom: '8px',
							marginTop: '0'
						}
					}, 'Categories')
				);

				pwmBlockData.categories.forEach(function(category) {
					categoryControls.push(
						el(CheckboxControl, {
							key: 'category-' + category.name,
							label: category.name + ' (' + category.count + ')',
							checked: isCategorySelected(category.name),
							onChange: function(checked) {
								toggleCategory(category.name, checked);
							}
						})
					);
				});
			} else {
				categoryControls.push(
					el('p', {
						style: {
							fontStyle: 'italic',
							color: '#757575'
						}
					}, 'No categories available. Add wishlist items first.')
				);
			}

			// Build inspector controls (sidebar)
			var inspectorControls = el(InspectorControls, {},
				// Display Settings Panel
				el(PanelBody, {
					title: 'Display Settings',
					initialOpen: true
				},
					// Categories section
					el('div', { style: { marginBottom: '16px' } },
						categoryControls
					),

					// Columns control
					el(RangeControl, {
						label: 'Columns',
						value: attributes.columns,
						onChange: function(value) {
							setAttributes({ columns: value });
						},
						min: 1,
						max: 4,
						help: 'Number of columns in the grid layout'
					}),

					// Sort control
					el(SelectControl, {
						label: 'Sort Order',
						value: attributes.sort,
						options: pwmBlockData.sortOptions,
						onChange: function(value) {
							setAttributes({ sort: value });
						},
						help: 'How items are sorted in the display'
					}),

					// Limit control
					el(RangeControl, {
						label: 'Limit Items',
						value: attributes.limit,
						onChange: function(value) {
							setAttributes({ limit: value });
						},
						min: -1,
						max: 100,
						help: 'Maximum items to display (-1 for unlimited)'
					}),

					// Show filters toggle
					el(ToggleControl, {
						label: 'Show Filters Panel',
						checked: attributes.showFilters,
						onChange: function(value) {
							setAttributes({ showFilters: value });
						},
						help: 'Display filter panel for visitors'
					})
				)
			);

			// Build block preview using ServerSideRender
			var blockPreview = el(ServerSideRender, {
				block: 'personal-wishlist-manager/wishlist',
				attributes: attributes,
				EmptyResponsePlaceholder: function() {
					return el('div', {
						className: 'pwm-block-placeholder',
						style: {
							padding: '40px',
							textAlign: 'center',
							border: '2px dashed #ddd',
							borderRadius: '4px',
							background: '#fafafa'
						}
					},
						el('span', {
							className: 'dashicons dashicons-heart',
							style: {
								fontSize: '48px',
								width: '48px',
								height: '48px',
								color: '#aaa'
							}
						}),
						el('p', {
							style: {
								margin: '16px 0 0 0',
								color: '#757575'
							}
						}, 'No wishlist items match your selection.')
					);
				},
				ErrorResponsePlaceholder: function() {
					return el('div', {
						className: 'pwm-block-error',
						style: {
							padding: '40px',
							textAlign: 'center',
							border: '2px solid #d63638',
							borderRadius: '4px',
							background: '#fff'
						}
					},
						el('span', {
							className: 'dashicons dashicons-warning',
							style: {
								fontSize: '48px',
								width: '48px',
								height: '48px',
								color: '#d63638'
							}
						}),
						el('p', {
							style: {
								margin: '16px 0 0 0',
								color: '#d63638'
							}
						}, 'Error loading wishlist. Please check your settings.')
					);
				},
				LoadingResponsePlaceholder: function() {
					return el('div', {
						className: 'pwm-block-loading',
						style: {
							padding: '40px',
							textAlign: 'center',
							border: '2px dashed #ddd',
							borderRadius: '4px',
							background: '#fafafa'
						}
					},
						el('div', {
							className: 'components-spinner',
							style: {
								float: 'none'
							}
						})
					);
				}
			});

			// Return the complete editor UI
			return el(Fragment, {},
				inspectorControls,
				el('div', {
					className: 'wp-block-personal-wishlist-manager-wishlist'
				},
					blockPreview
				)
			);
		},

		/**
		 * Save function - return null for server-side rendering
		 */
		save: function() {
			return null;
		}
	});

})(window.wp);
