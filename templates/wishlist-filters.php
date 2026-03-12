<?php
/**
 * Template for wishlist filters
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Set default filter options if not provided
if (!isset($filter_options)) {
	$filter_options = array(
		'show_search' => true,
		'show_category' => true,
		'show_price' => true,
		'show_sort' => true
	);
}

// Get available categories and price range
$db = PWM_Database::get_instance();
$categories = $db->get_categories();
$price_range = $db->get_price_range();
$default_sort = get_option('pwm_default_sort', 'date_desc');
?>

<div class="wishlist-filters-wrapper">
	<div class="pwm-mobile-filter-bar">
		<button
			type="button"
			id="pwm-mobile-filter-toggle"
			class="pwm-mobile-filter-toggle"
			aria-expanded="false"
			aria-controls="pwm-filter-panel"
		>
			<span class="pwm-mobile-filter-toggle-label"><?php _e('Filters', 'personal-wishlist-manager'); ?></span>
			<span class="pwm-mobile-filter-toggle-meta">
				<span class="pwm-mobile-filter-count" id="pwm-mobile-filter-count" style="display: none;">0</span>
				<span class="pwm-mobile-filter-toggle-icon">▼</span>
			</span>
		</button>
	</div>

	<div class="wishlist-filters" id="pwm-filter-panel">
		<!-- Search Filter -->
		<?php if ($filter_options['show_search']) : ?>
			<div class="filter-group">
				<label for="pwm-search-filter" class="filter-label">
					<?php _e('Search', 'personal-wishlist-manager'); ?>
				</label>
				<input
					type="text"
					id="pwm-search-filter"
					class="filter-input"
					placeholder="<?php esc_attr_e('Search title, category, or reason...', 'personal-wishlist-manager'); ?>"
					autocomplete="off"
				>
			</div>
		<?php endif; ?>

		<!-- Category Filter -->
		<?php if ($filter_options['show_category']) : ?>
			<div class="filter-group">
				<label for="pwm-category-filter" class="filter-label">
					<?php _e('Category', 'personal-wishlist-manager'); ?>
				</label>
				<select id="pwm-category-filter" class="filter-select">
					<option value=""><?php _e('All Categories', 'personal-wishlist-manager'); ?></option>
					<?php foreach ($categories as $cat) : ?>
						<option value="<?php echo esc_attr($cat->category); ?>">
							<?php echo esc_html($cat->category); ?> (<?php echo esc_html($cat->count); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

		<!-- Price Range Filter -->
		<?php if ($filter_options['show_price']) : ?>
			<div class="filter-group filter-group-price">
				<label class="filter-label">
					<?php _e('Price Range', 'personal-wishlist-manager'); ?>
				</label>
					<div class="price-inputs">
							<div class="price-input-wrapper price-input-min">
								<label for="pwm-min-price-filter" class="price-input-label">
									<?php _e('Min', 'personal-wishlist-manager'); ?>
								</label>
								<input
									type="text"
									inputmode="decimal"
									id="pwm-min-price-filter"
									class="filter-input price-input"
								placeholder="0"
								pattern="[0-9]*[.]?[0-9]*"
								data-default-value="<?php echo esc_attr($price_range['min']); ?>"
								value="<?php echo esc_attr($price_range['min']); ?>"
							>
						</div>
						<span class="price-separator">-</span>
							<div class="price-input-wrapper price-input-max">
								<label for="pwm-max-price-filter" class="price-input-label">
									<?php _e('Max', 'personal-wishlist-manager'); ?>
								</label>
								<input
									type="text"
									inputmode="decimal"
									id="pwm-max-price-filter"
								class="filter-input price-input"
								placeholder="0"
								pattern="[0-9]*[.]?[0-9]*"
								data-default-value="<?php echo esc_attr($price_range['max']); ?>"
								value="<?php echo esc_attr($price_range['max']); ?>"
							>
						</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Sort Filter -->
		<?php if ($filter_options['show_sort']) : ?>
			<div class="filter-group">
				<label for="pwm-sort-filter" class="filter-label">
					<?php _e('Sort By', 'personal-wishlist-manager'); ?>
				</label>
				<select id="pwm-sort-filter" class="filter-select">
					<option value="alphabetical" <?php selected($default_sort, 'alphabetical'); ?>><?php _e('Alphabetical A-Z', 'personal-wishlist-manager'); ?></option>
					<option value="price_asc" <?php selected($default_sort, 'price_asc'); ?>><?php _e('Price: Low to High', 'personal-wishlist-manager'); ?></option>
					<option value="price_desc" <?php selected($default_sort, 'price_desc'); ?>><?php _e('Price: High to Low', 'personal-wishlist-manager'); ?></option>
					<option value="date_desc" <?php selected($default_sort, 'date_desc'); ?>><?php _e('Date: Newest First', 'personal-wishlist-manager'); ?></option>
					<option value="date_asc" <?php selected($default_sort, 'date_asc'); ?>><?php _e('Date: Oldest First', 'personal-wishlist-manager'); ?></option>
				</select>
			</div>
		<?php endif; ?>

		<div class="filter-group filter-group-reset" id="pwm-inline-reset-wrap" style="display: none;">
			<span class="filter-label filter-label-reset"><?php _e('Actions', 'personal-wishlist-manager'); ?></span>
			<button type="button" class="filter-clear-btn pwm-clear-filters-trigger">
				<?php _e('Reset', 'personal-wishlist-manager'); ?>
			</button>
		</div>

		<div class="pwm-mobile-filter-actions">
			<button type="button" id="pwm-mobile-filter-done" class="pwm-mobile-filter-done">
				<?php _e('Done', 'personal-wishlist-manager'); ?>
			</button>
		</div>
	</div>
</div>
