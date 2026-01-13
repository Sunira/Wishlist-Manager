<?php
/**
 * Helper functions for Personal Wishlist Manager
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get wishlist items
 *
 * @param array $args Query arguments
 * @return array Array of items
 */
function pwm_get_items($args = array()) {
	$db = PWM_Database::get_instance();
	return $db->get_items($args);
}

/**
 * Get single wishlist item
 *
 * @param int $item_id Item ID
 * @return object|null Item object or null
 */
function pwm_get_item($item_id) {
	$db = PWM_Database::get_instance();
	return $db->get_item($item_id);
}

/**
 * Format price with currency symbol
 *
 * @param float $price Price value
 * @return string Formatted price
 */
function pwm_format_price($price) {
	$symbol = get_option('pwm_currency_symbol', '$');
	$position = get_option('pwm_currency_position', 'before');
	$formatted_price = number_format($price, 2);

	if ($position === 'before') {
		return $symbol . $formatted_price;
	} else {
		return $formatted_price . $symbol;
	}
}

/**
 * Sanitize wishlist item data
 *
 * @param array $data Item data
 * @return array Sanitized data
 */
function pwm_sanitize_item_data($data) {
	return array(
		'title' => sanitize_text_field($data['title']),
		'category' => sanitize_text_field($data['category']),
		'tags' => sanitize_text_field($data['tags']),
		'image_url' => esc_url_raw($data['image_url']),
		'product_url' => esc_url_raw($data['product_url']),
		'price' => floatval($data['price']),
		'reason' => wp_kses_post($data['reason']),
		'user_id' => isset($data['user_id']) ? intval($data['user_id']) : get_current_user_id()
	);
}

/**
 * Validate wishlist item data
 *
 * @param array $data Item data
 * @return array Array of error messages (empty if valid)
 */
function pwm_validate_item_data($data) {
	$errors = array();

	if (empty($data['title'])) {
		$errors[] = __('Title is required', 'personal-wishlist-manager');
	}

	if (empty($data['category'])) {
		$errors[] = __('Category is required', 'personal-wishlist-manager');
	} elseif ($data['category'] === '__new__') {
		$errors[] = __('Please enter a category name or select an existing category', 'personal-wishlist-manager');
	}

	if (empty($data['image_url'])) {
		$errors[] = __('Image URL is required', 'personal-wishlist-manager');
	} elseif (!filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
		$errors[] = __('Invalid image URL', 'personal-wishlist-manager');
	}

	if (empty($data['product_url'])) {
		$errors[] = __('Product URL is required', 'personal-wishlist-manager');
	} elseif (!filter_var($data['product_url'], FILTER_VALIDATE_URL)) {
		$errors[] = __('Invalid product URL', 'personal-wishlist-manager');
	}

	if (empty($data['price']) || floatval($data['price']) <= 0) {
		$errors[] = __('Valid price is required', 'personal-wishlist-manager');
	}

	return $errors;
}

/**
 * Get all categories
 *
 * @return array Array of categories
 */
function pwm_get_categories() {
	$db = PWM_Database::get_instance();
	return $db->get_categories();
}

/**
 * Get all tags
 *
 * @return array Array of tags with counts
 */
function pwm_get_tags() {
	$db = PWM_Database::get_instance();
	return $db->get_tags();
}

/**
 * Parse tags string into array
 *
 * @param string $tags_string Comma-separated tags
 * @return array Array of tags
 */
function pwm_parse_tags($tags_string) {
	if (empty($tags_string)) {
		return array();
	}
	return array_filter(array_map('trim', explode(',', $tags_string)));
}

/**
 * Get admin page URL
 *
 * @param string $page Page slug
 * @param array  $args Additional query args
 * @return string Admin page URL
 */
function pwm_get_admin_url($page = 'wishlist', $args = array()) {
	$args['page'] = $page;
	return add_query_arg($args, admin_url('admin.php'));
}

/**
 * Display admin notice
 *
 * @param string $message Notice message
 * @param string $type    Notice type (success, error, warning, info)
 */
function pwm_admin_notice($message, $type = 'success') {
	add_settings_error(
		'pwm_messages',
		'pwm_message',
		$message,
		$type
	);
}

/**
 * Get sort options
 *
 * @return array Sort options
 */
function pwm_get_sort_options() {
	return array(
		'alphabetical' => __('Alphabetical (A-Z)', 'personal-wishlist-manager'),
		'price_asc' => __('Price: Low to High', 'personal-wishlist-manager'),
		'price_desc' => __('Price: High to Low', 'personal-wishlist-manager'),
		'date_desc' => __('Date Added (Newest First)', 'personal-wishlist-manager'),
		'date_asc' => __('Date Added (Oldest First)', 'personal-wishlist-manager')
	);
}

/**
 * Convert sort option to database parameters
 *
 * @param string $sort Sort option
 * @return array Array with orderby and order keys
 */
function pwm_get_sort_params($sort) {
	$params = array(
		'orderby' => 'created_at',
		'order' => 'DESC'
	);

	switch ($sort) {
		case 'alphabetical':
			$params['orderby'] = 'title';
			$params['order'] = 'ASC';
			break;
		case 'price_asc':
			$params['orderby'] = 'price';
			$params['order'] = 'ASC';
			break;
		case 'price_desc':
			$params['orderby'] = 'price';
			$params['order'] = 'DESC';
			break;
		case 'date_desc':
			$params['orderby'] = 'created_at';
			$params['order'] = 'DESC';
			break;
		case 'date_asc':
			$params['orderby'] = 'created_at';
			$params['order'] = 'ASC';
			break;
	}

	return $params;
}
