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
		'show_tags' => true,
		'show_price' => true,
		'show_sort' => true
	);
}

// Get available categories, tags, and price range
$db = PWM_Database::get_instance();
$categories = $db->get_categories();
$tags = $db->get_tags();
$price_range = $db->get_price_range();
?>

<div class="wishlist-filters-wrapper">
	<div class="wishlist-filters">
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

		<!-- Tags Filter -->
		<?php if ($filter_options['show_tags'] && !empty($tags)) : ?>
			<div class="filter-group">
				<label for="pwm-tags-filter" class="filter-label">
					<?php _e('Tags', 'personal-wishlist-manager'); ?>
				</label>
				<div class="pwm-multiselect-wrapper">
					<button type="button" class="pwm-multiselect-button filter-select" id="pwm-tags-filter-btn">
						<span class="pwm-multiselect-text"><?php _e('Select tags...', 'personal-wishlist-manager'); ?></span>
						<span class="pwm-multiselect-arrow">▼</span>
					</button>
					<div class="pwm-multiselect-dropdown" id="pwm-tags-dropdown">
						<?php foreach ($tags as $tag => $count) : ?>
							<label class="pwm-multiselect-option">
								<input type="checkbox" name="pwm-tag-filter" value="<?php echo esc_attr($tag); ?>">
								<span><?php echo esc_html($tag); ?> (<?php echo esc_html($count); ?>)</span>
							</label>
						<?php endforeach; ?>
					</div>
					<select id="pwm-tags-filter" class="pwm-multiselect-hidden" multiple style="display: none;">
						<?php foreach ($tags as $tag => $count) : ?>
							<option value="<?php echo esc_attr($tag); ?>">
								<?php echo esc_html($tag); ?> (<?php echo esc_html($count); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		<?php endif; ?>

		<!-- Price Range Filter -->
		<?php if ($filter_options['show_price']) : ?>
			<div class="filter-group filter-group-price">
				<label class="filter-label">
					<?php _e('Price Range', 'personal-wishlist-manager'); ?>
				</label>
				<div class="price-inputs">
					<div class="price-input-wrapper">
						<span class="price-prefix">$</span>
						<input
							type="number"
							id="pwm-min-price-filter"
							class="filter-input price-input"
							placeholder="<?php esc_attr_e('Min', 'personal-wishlist-manager'); ?>"
							min="0"
							step="0.01"
							value="<?php echo esc_attr($price_range['min']); ?>"
						>
					</div>
					<span class="price-separator">-</span>
					<div class="price-input-wrapper">
						<span class="price-prefix">$</span>
						<input
							type="number"
							id="pwm-max-price-filter"
							class="filter-input price-input"
							placeholder="<?php esc_attr_e('Max', 'personal-wishlist-manager'); ?>"
							min="0"
							step="0.01"
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
					<option value="alphabetical"><?php _e('Alphabetical A-Z', 'personal-wishlist-manager'); ?></option>
					<option value="price_asc"><?php _e('Price: Low to High', 'personal-wishlist-manager'); ?></option>
					<option value="price_desc"><?php _e('Price: High to Low', 'personal-wishlist-manager'); ?></option>
					<option value="date_desc"><?php _e('Date: Newest First', 'personal-wishlist-manager'); ?></option>
					<option value="date_asc"><?php _e('Date: Oldest First', 'personal-wishlist-manager'); ?></option>
				</select>
			</div>
		<?php endif; ?>

	</div>

	<!-- Active Filters Display -->
	<div class="active-filters" id="pwm-active-filters" style="display: none;">
		<span class="active-filters-label"><?php _e('Active Filters:', 'personal-wishlist-manager'); ?></span>
		<div class="active-filters-list" id="pwm-active-filters-list"></div>
		<button type="button" id="pwm-clear-filters" class="filter-clear-btn">
			<?php _e('Clear All', 'personal-wishlist-manager'); ?>
		</button>
	</div>
</div>
