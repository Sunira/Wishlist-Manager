<?php
/**
 * Admin view for settings page
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Check user capabilities
if (!current_user_can('manage_wishlist_items')) {
	wp_die(__('You do not have sufficient permissions to access this page.', 'personal-wishlist-manager'));
}

// Get current tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'display';

// Handle form submission
if (isset($_POST['pwm_regenerate_quick_add_token'])) {
	check_admin_referer('pwm_settings_nonce');
	update_option('pwm_quick_add_token', wp_generate_password(40, false, false));
	pwm_admin_notice(__('Quick Add token regenerated.', 'personal-wishlist-manager'), 'success');
}

if (isset($_POST['pwm_save_settings'])) {
	check_admin_referer('pwm_settings_nonce');

	if ($active_tab === 'display') {
		update_option('pwm_default_columns', intval($_POST['pwm_default_columns']));
		update_option('pwm_items_per_page', intval($_POST['pwm_items_per_page']));
		update_option('pwm_default_sort', sanitize_text_field($_POST['pwm_default_sort']));
		update_option('pwm_currency_symbol', sanitize_text_field($_POST['pwm_currency_symbol']));
		update_option('pwm_currency_position', sanitize_text_field($_POST['pwm_currency_position']));
	} elseif ($active_tab === 'advanced') {
		update_option('pwm_custom_css', wp_strip_all_tags($_POST['pwm_custom_css']));
		update_option('pwm_delete_on_uninstall', isset($_POST['pwm_delete_on_uninstall']) ? true : false);
	}

	pwm_admin_notice(__('Settings saved successfully.', 'personal-wishlist-manager'), 'success');
}

// Get current settings
$default_columns = get_option('pwm_default_columns', 3);
$items_per_page = get_option('pwm_items_per_page', 20);
$default_sort = get_option('pwm_default_sort', 'alphabetical');
$currency_symbol = get_option('pwm_currency_symbol', '$');
$currency_position = get_option('pwm_currency_position', 'before');
$custom_css = get_option('pwm_custom_css', '');
$delete_on_uninstall = get_option('pwm_delete_on_uninstall', false);
$quick_add_token = pwm_get_quick_add_token();
$quick_add_endpoint = pwm_get_quick_add_endpoint_url();
$quick_add_bookmarklet = pwm_get_quick_add_bookmarklet();

$sort_options = pwm_get_sort_options();

// Get categories for shortcode generator
$db = PWM_Database::get_instance();
$categories = $db->get_categories();
?>

<div class="wrap">
	<h1><?php _e('Wishlist Settings', 'personal-wishlist-manager'); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="?page=wishlist-settings&tab=display" class="nav-tab <?php echo $active_tab === 'display' ? 'nav-tab-active' : ''; ?>">
			<?php _e('Display', 'personal-wishlist-manager'); ?>
		</a>
		<a href="?page=wishlist-settings&tab=shortcode" class="nav-tab <?php echo $active_tab === 'shortcode' ? 'nav-tab-active' : ''; ?>">
			<?php _e('Shortcode', 'personal-wishlist-manager'); ?>
		</a>
		<a href="?page=wishlist-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
			<?php _e('Advanced', 'personal-wishlist-manager'); ?>
		</a>
		<a href="?page=wishlist-settings&tab=import-export" class="nav-tab <?php echo $active_tab === 'import-export' ? 'nav-tab-active' : ''; ?>">
			<?php _e('Import/Export', 'personal-wishlist-manager'); ?>
		</a>
	</h2>

	<form method="post" action="">
		<?php wp_nonce_field('pwm_settings_nonce'); ?>

		<?php if ($active_tab === 'display') : ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pwm_default_columns"><?php _e('Default Columns', 'personal-wishlist-manager'); ?></label>
					</th>
					<td>
						<fieldset>
							<label><input type="radio" name="pwm_default_columns" value="1" <?php checked($default_columns, 1); ?>> 1</label><br>
							<label><input type="radio" name="pwm_default_columns" value="2" <?php checked($default_columns, 2); ?>> 2</label><br>
							<label><input type="radio" name="pwm_default_columns" value="3" <?php checked($default_columns, 3); ?>> 3</label><br>
							<label><input type="radio" name="pwm_default_columns" value="4" <?php checked($default_columns, 4); ?>> 4</label>
							<p class="description"><?php _e('Number of columns for the grid display', 'personal-wishlist-manager'); ?></p>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="pwm_items_per_page"><?php _e('Items Per Page', 'personal-wishlist-manager'); ?></label>
					</th>
					<td>
						<input type="number" name="pwm_items_per_page" id="pwm_items_per_page" value="<?php echo esc_attr($items_per_page); ?>" min="1" max="100" class="small-text">
						<p class="description"><?php _e('Number of items to display per page', 'personal-wishlist-manager'); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="pwm_default_sort"><?php _e('Default Sort Order', 'personal-wishlist-manager'); ?></label>
					</th>
					<td>
						<select name="pwm_default_sort" id="pwm_default_sort">
							<?php foreach ($sort_options as $value => $label) : ?>
								<option value="<?php echo esc_attr($value); ?>" <?php selected($default_sort, $value); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="pwm_currency_symbol"><?php _e('Currency Symbol', 'personal-wishlist-manager'); ?></label>
					</th>
					<td>
						<input type="text" name="pwm_currency_symbol" id="pwm_currency_symbol" value="<?php echo esc_attr($currency_symbol); ?>" class="small-text">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php _e('Currency Position', 'personal-wishlist-manager'); ?></label>
					</th>
					<td>
						<fieldset>
							<label><input type="radio" name="pwm_currency_position" value="before" <?php checked($currency_position, 'before'); ?>> <?php _e('Before price', 'personal-wishlist-manager'); ?> ($99.99)</label><br>
							<label><input type="radio" name="pwm_currency_position" value="after" <?php checked($currency_position, 'after'); ?>> <?php _e('After price', 'personal-wishlist-manager'); ?> (99.99$)</label>
						</fieldset>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="pwm_save_settings" class="button button-primary">
					<?php _e('Save Settings', 'personal-wishlist-manager'); ?>
				</button>
			</p>

		<?php elseif ($active_tab === 'shortcode') : ?>
			<div class="pwm-shortcode-docs">
				<h2><?php _e('Shortcode Generator', 'personal-wishlist-manager'); ?></h2>
				<p><?php _e('Use this tool to generate a customized shortcode for your wishlist display.', 'personal-wishlist-manager'); ?></p>

				<!-- Shortcode Generator -->
				<div class="pwm-shortcode-generator">
					<div class="pwm-generator-section">
						<h3><?php _e('Display Settings', 'personal-wishlist-manager'); ?></h3>

						<div class="pwm-generator-row">
							<label for="pwm-gen-columns"><?php _e('Number of Columns:', 'personal-wishlist-manager'); ?></label>
							<select id="pwm-gen-columns" class="pwm-gen-control">
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3" selected>3</option>
								<option value="4">4</option>
							</select>
						</div>

						<div class="pwm-generator-row">
							<label for="pwm-gen-category"><?php _e('Filter by Category:', 'personal-wishlist-manager'); ?></label>
							<select id="pwm-gen-category" class="pwm-gen-control">
								<option value=""><?php _e('All Categories', 'personal-wishlist-manager'); ?></option>
								<?php foreach ($categories as $cat) : ?>
									<option value="<?php echo esc_attr($cat->category); ?>">
										<?php echo esc_html($cat->category); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="pwm-generator-row">
							<label for="pwm-gen-sort"><?php _e('Sort Order:', 'personal-wishlist-manager'); ?></label>
							<select id="pwm-gen-sort" class="pwm-gen-control">
								<option value="alphabetical"><?php _e('Alphabetical A-Z', 'personal-wishlist-manager'); ?></option>
								<option value="price_asc"><?php _e('Price: Low to High', 'personal-wishlist-manager'); ?></option>
								<option value="price_desc"><?php _e('Price: High to Low', 'personal-wishlist-manager'); ?></option>
								<option value="date_desc"><?php _e('Date: Newest First', 'personal-wishlist-manager'); ?></option>
								<option value="date_asc"><?php _e('Date: Oldest First', 'personal-wishlist-manager'); ?></option>
							</select>
						</div>

						<div class="pwm-generator-row">
							<label for="pwm-gen-limit"><?php _e('Maximum Items:', 'personal-wishlist-manager'); ?></label>
							<input type="number" id="pwm-gen-limit" class="pwm-gen-control" placeholder="<?php esc_attr_e('Unlimited', 'personal-wishlist-manager'); ?>" min="1">
							<span class="description"><?php _e('Leave empty for unlimited', 'personal-wishlist-manager'); ?></span>
						</div>
					</div>

					<div class="pwm-generator-section">
						<h3><?php _e('Filter Visibility', 'personal-wishlist-manager'); ?></h3>
						<p class="description"><?php _e('Choose which filters to show to your visitors:', 'personal-wishlist-manager'); ?></p>

						<div class="pwm-generator-checkboxes">
							<label class="pwm-gen-checkbox">
								<input type="checkbox" id="pwm-gen-show-filters" class="pwm-gen-control" checked>
								<span><?php _e('Show Filters Panel', 'personal-wishlist-manager'); ?></span>
							</label>

							<div id="pwm-gen-filter-options" class="pwm-gen-sub-options">
								<label class="pwm-gen-checkbox">
									<input type="checkbox" id="pwm-gen-show-search" class="pwm-gen-control" checked>
									<span><?php _e('Show Search Filter', 'personal-wishlist-manager'); ?></span>
								</label>

								<label class="pwm-gen-checkbox">
									<input type="checkbox" id="pwm-gen-show-category" class="pwm-gen-control" checked>
									<span><?php _e('Show Category Filter', 'personal-wishlist-manager'); ?></span>
								</label>

								<label class="pwm-gen-checkbox">
									<input type="checkbox" id="pwm-gen-show-price" class="pwm-gen-control" checked>
									<span><?php _e('Show Price Range Filter', 'personal-wishlist-manager'); ?></span>
								</label>

								<label class="pwm-gen-checkbox">
									<input type="checkbox" id="pwm-gen-show-sort" class="pwm-gen-control" checked>
									<span><?php _e('Show Sort Dropdown', 'personal-wishlist-manager'); ?></span>
								</label>
							</div>
						</div>
					</div>

					<div class="pwm-generator-section pwm-generator-output">
						<h3><?php _e('Generated Shortcode', 'personal-wishlist-manager'); ?></h3>
						<div class="pwm-shortcode-output-wrapper">
							<pre id="pwm-generated-shortcode" class="pwm-shortcode-output"><code>[personal_wishlist]</code></pre>
							<button type="button" id="pwm-copy-shortcode" class="button button-primary">
								<span class="dashicons dashicons-clipboard"></span>
								<?php _e('Copy to Clipboard', 'personal-wishlist-manager'); ?>
							</button>
						</div>
						<p class="description"><?php _e('Copy this shortcode and paste it into any page or post where you want to display your wishlist.', 'personal-wishlist-manager'); ?></p>
					</div>
				</div>

				<hr style="margin: 40px 0;">

				<!-- Documentation -->
				<h2><?php _e('Shortcode Documentation', 'personal-wishlist-manager'); ?></h2>

				<h3><?php _e('Available Parameters', 'personal-wishlist-manager'); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php _e('Parameter', 'personal-wishlist-manager'); ?></th>
							<th><?php _e('Description', 'personal-wishlist-manager'); ?></th>
							<th><?php _e('Values', 'personal-wishlist-manager'); ?></th>
							<th><?php _e('Default', 'personal-wishlist-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>columns</code></td>
							<td><?php _e('Number of columns', 'personal-wishlist-manager'); ?></td>
							<td>1, 2, 3, 4</td>
							<td>3</td>
						</tr>
						<tr>
							<td><code>category</code></td>
							<td><?php _e('Filter by specific category', 'personal-wishlist-manager'); ?></td>
							<td><?php _e('Any category name', 'personal-wishlist-manager'); ?></td>
							<td><?php _e('All categories', 'personal-wishlist-manager'); ?></td>
						</tr>
						<tr>
							<td><code>sort</code></td>
							<td><?php _e('Sort order', 'personal-wishlist-manager'); ?></td>
							<td>alphabetical, price_asc, price_desc, date_desc, date_asc</td>
							<td>alphabetical</td>
						</tr>
						<tr>
							<td><code>limit</code></td>
							<td><?php _e('Maximum items to display', 'personal-wishlist-manager'); ?></td>
							<td><?php _e('Any positive number', 'personal-wishlist-manager'); ?></td>
							<td><?php _e('Unlimited', 'personal-wishlist-manager'); ?></td>
						</tr>
						<tr>
							<td><code>show_filters</code></td>
							<td><?php _e('Show/hide entire filter panel', 'personal-wishlist-manager'); ?></td>
							<td>true, false</td>
							<td>true</td>
						</tr>
						<tr>
							<td><code>show_search</code></td>
							<td><?php _e('Show/hide search filter', 'personal-wishlist-manager'); ?></td>
							<td>true, false</td>
							<td>true</td>
						</tr>
						<tr>
							<td><code>show_category</code></td>
							<td><?php _e('Show/hide category filter', 'personal-wishlist-manager'); ?></td>
							<td>true, false</td>
							<td>true</td>
						</tr>
							<tr>
								<td><code>show_price</code></td>
							<td><?php _e('Show/hide price range filter', 'personal-wishlist-manager'); ?></td>
							<td>true, false</td>
							<td>true</td>
						</tr>
						<tr>
							<td><code>show_sort</code></td>
							<td><?php _e('Show/hide sort dropdown', 'personal-wishlist-manager'); ?></td>
							<td>true, false</td>
							<td>true</td>
						</tr>
					</tbody>
				</table>

				<h3><?php _e('Example Shortcodes', 'personal-wishlist-manager'); ?></h3>
				<div class="pwm-example-shortcode">
					<h4><?php _e('Simple 4-column grid:', 'personal-wishlist-manager'); ?></h4>
					<pre><code>[personal_wishlist columns="4"]</code></pre>
				</div>

				<div class="pwm-example-shortcode">
					<h4><?php _e('No filters, just display items:', 'personal-wishlist-manager'); ?></h4>
					<pre><code>[personal_wishlist show_filters="false"]</code></pre>
				</div>

					<div class="pwm-example-shortcode">
						<h4><?php _e('Show only search and category filters:', 'personal-wishlist-manager'); ?></h4>
						<pre><code>[personal_wishlist show_price="false" show_sort="false"]</code></pre>
					</div>

				<div class="pwm-example-shortcode">
					<h4><?php _e('Electronics category only, sorted by price:', 'personal-wishlist-manager'); ?></h4>
					<pre><code>[personal_wishlist category="Electronics" sort="price_asc"]</code></pre>
				</div>
			</div>

		<?php elseif ($active_tab === 'advanced') : ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pwm_custom_css"><?php _e('Custom CSS', 'personal-wishlist-manager'); ?></label>
					</th>
					<td>
						<textarea name="pwm_custom_css" id="pwm_custom_css" rows="10" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
						<p class="description"><?php _e('Add custom CSS to style your wishlist', 'personal-wishlist-manager'); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php _e('Browser Quick Add', 'personal-wishlist-manager'); ?>
					</th>
					<td>
						<p class="description"><?php _e('Use this bookmarklet to capture the current page into your wishlist from any website.', 'personal-wishlist-manager'); ?></p>
						<p>
							<label for="pwm_quick_add_endpoint"><strong><?php _e('Quick Add Endpoint', 'personal-wishlist-manager'); ?></strong></label><br>
							<input type="text" id="pwm_quick_add_endpoint" value="<?php echo esc_attr($quick_add_endpoint); ?>" class="regular-text code" readonly>
						</p>
						<p>
							<label for="pwm_quick_add_token"><strong><?php _e('Quick Add Token', 'personal-wishlist-manager'); ?></strong></label><br>
							<input type="text" id="pwm_quick_add_token" value="<?php echo esc_attr($quick_add_token); ?>" class="regular-text code" readonly>
						</p>
						<p>
							<label for="pwm_quick_add_bookmarklet"><strong><?php _e('Bookmarklet', 'personal-wishlist-manager'); ?></strong></label><br>
							<textarea id="pwm_quick_add_bookmarklet" class="large-text code" rows="5" readonly><?php echo esc_textarea($quick_add_bookmarklet); ?></textarea>
						</p>
						<p class="description"><?php _e('Create a browser bookmark and paste the bookmarklet code as its URL. Clicking it on a product page opens a prefilled form where you can edit fields before saving.', 'personal-wishlist-manager'); ?></p>
						<p>
							<button type="submit" name="pwm_regenerate_quick_add_token" class="button">
								<?php _e('Regenerate Quick Add Token', 'personal-wishlist-manager'); ?>
							</button>
						</p>
						<p class="description" style="color: #d63638;"><?php _e('Regenerating the token invalidates previously saved bookmarklets.', 'personal-wishlist-manager'); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php _e('Delete Data on Uninstall', 'personal-wishlist-manager'); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="pwm_delete_on_uninstall" value="1" <?php checked($delete_on_uninstall, true); ?>>
							<?php _e('Delete all wishlist data when plugin is uninstalled', 'personal-wishlist-manager'); ?>
						</label>
						<p class="description" style="color: #d63638;">
							<?php _e('Warning: This will permanently delete all wishlist items and settings when you uninstall the plugin.', 'personal-wishlist-manager'); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="pwm_save_settings" class="button button-primary">
					<?php _e('Save Settings', 'personal-wishlist-manager'); ?>
				</button>
			</p>

		<?php elseif ($active_tab === 'import-export') : ?>
			<h2><?php _e('Export Data', 'personal-wishlist-manager'); ?></h2>
			<p><?php _e('Export your wishlist items to CSV or JSON format.', 'personal-wishlist-manager'); ?></p>
			<p>
				<a href="<?php echo esc_url(add_query_arg(array('pwm_export' => 'csv', 'nonce' => wp_create_nonce('pwm_export')), admin_url('admin-ajax.php'))); ?>" class="button button-secondary">
					<?php _e('Export to CSV', 'personal-wishlist-manager'); ?>
				</a>
				<a href="<?php echo esc_url(add_query_arg(array('pwm_export' => 'json', 'nonce' => wp_create_nonce('pwm_export')), admin_url('admin-ajax.php'))); ?>" class="button button-secondary">
					<?php _e('Export to JSON', 'personal-wishlist-manager'); ?>
				</a>
			</p>

			<hr>

			<h2><?php _e('Import Data', 'personal-wishlist-manager'); ?></h2>
			<p><?php _e('Import wishlist items from CSV or JSON file.', 'personal-wishlist-manager'); ?></p>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('pwm_import_nonce'); ?>
				<p>
					<input type="file" name="pwm_import_file" accept=".csv,.json">
				</p>
				<p>
					<button type="submit" name="pwm_import" class="button button-primary">
						<?php _e('Import File', 'personal-wishlist-manager'); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>
	</form>
</div>

<style>
.pwm-shortcode-docs {
	background: #fff;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

/* Shortcode Generator */
.pwm-shortcode-generator {
	background: #f9f9f9;
	padding: 30px;
	border: 2px solid #2271b1;
	border-radius: 8px;
	margin: 20px 0;
}

.pwm-generator-section {
	background: #fff;
	padding: 20px;
	margin-bottom: 20px;
	border: 1px solid #ddd;
	border-radius: 6px;
}

.pwm-generator-section:last-child {
	margin-bottom: 0;
}

.pwm-generator-section h3 {
	margin-top: 0;
	margin-bottom: 20px;
	color: #2271b1;
	font-size: 16px;
	font-weight: 600;
}

.pwm-generator-row {
	display: flex;
	align-items: center;
	gap: 15px;
	margin-bottom: 15px;
}

.pwm-generator-row label {
	font-weight: 600;
	min-width: 150px;
	margin: 0;
}

.pwm-generator-row .pwm-gen-control {
	flex: 1;
	max-width: 300px;
}

.pwm-generator-row .description {
	font-style: italic;
	color: #666;
	font-size: 13px;
}

.pwm-generator-checkboxes {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.pwm-gen-checkbox {
	display: flex;
	align-items: center;
	gap: 10px;
	cursor: pointer;
	padding: 8px;
	border-radius: 4px;
	transition: background 0.2s ease;
}

.pwm-gen-checkbox:hover {
	background: #f9f9f9;
}

.pwm-gen-checkbox input[type="checkbox"] {
	width: 18px;
	height: 18px;
	margin: 0;
	cursor: pointer;
}

.pwm-gen-checkbox span {
	font-size: 14px;
	font-weight: 500;
}

.pwm-gen-sub-options {
	margin-left: 30px;
	padding-left: 20px;
	border-left: 3px solid #e0e0e0;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.pwm-gen-sub-options.disabled {
	opacity: 0.5;
	pointer-events: none;
}

.pwm-generator-output {
	background: #fffef7 !important;
	border-color: #2271b1 !important;
	border-width: 2px !important;
}

.pwm-shortcode-output-wrapper {
	display: flex;
	align-items: flex-start;
	gap: 15px;
	margin-bottom: 15px;
}

.pwm-shortcode-output {
	flex: 1;
	background: #f0f0f0;
	border: 1px solid #ccc;
	padding: 15px;
	border-radius: 4px;
	font-family: 'Courier New', monospace;
	font-size: 14px;
	line-height: 1.6;
	margin: 0;
	word-wrap: break-word;
	white-space: pre-wrap;
}

.pwm-shortcode-output code {
	background: none;
	padding: 0;
	font-size: inherit;
}

#pwm-copy-shortcode {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	white-space: nowrap;
}

#pwm-copy-shortcode .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

#pwm-copy-shortcode.copied {
	background: #00a32a !important;
	border-color: #00a32a !important;
}

/* Documentation */
.pwm-example-shortcode {
	background: #f9f9f9;
	padding: 15px;
	margin: 15px 0;
	border-left: 4px solid #2271b1;
}

.pwm-example-shortcode h4 {
	margin-top: 0;
}

.pwm-example-shortcode pre {
	background: #fff;
	padding: 10px;
	border: 1px solid #ddd;
	border-radius: 3px;
}

/* Responsive */
@media (max-width: 782px) {
	.pwm-generator-row {
		flex-direction: column;
		align-items: flex-start;
	}

	.pwm-generator-row label {
		min-width: auto;
	}

	.pwm-generator-row .pwm-gen-control {
		max-width: 100%;
		width: 100%;
	}

	.pwm-shortcode-output-wrapper {
		flex-direction: column;
	}
}
</style>
