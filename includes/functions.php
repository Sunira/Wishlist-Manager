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
 * Get WordPress post category names for select controls.
 *
 * @return array
 */
function pwm_get_wordpress_category_names() {
	$terms = get_terms(
		array(
			'taxonomy' => 'category',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
			'fields' => 'names',
		)
	);

	if (is_wp_error($terms) || !is_array($terms)) {
		return array();
	}

	$names = array_map('sanitize_text_field', $terms);
	$names = array_filter($names);
	$names = array_values(array_unique($names));

	return $names;
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

/**
 * Get or initialize the quick add token.
 *
 * @return string
 */
function pwm_get_quick_add_token() {
	$token = get_option('pwm_quick_add_token', '');

	if (empty($token)) {
		$token = wp_generate_password(40, false, false);
		update_option('pwm_quick_add_token', $token);
	}

	return $token;
}

/**
 * Build the admin-post endpoint URL for quick add.
 *
 * @return string
 */
function pwm_get_quick_add_endpoint_url() {
	return add_query_arg(
		array('action' => 'pwm_quick_add'),
		admin_url('admin-post.php')
	);
}

/**
 * Build bookmarklet script for quick add.
 *
 * @return string
 */
function pwm_get_quick_add_bookmarklet() {
	$base_url = add_query_arg(
		array(
			'action' => 'pwm_quick_add',
			'token' => pwm_get_quick_add_token()
		),
		admin_url('admin-post.php')
	);

	$base_url_js = wp_json_encode($base_url);
	$script = "(function(){function getPrice(){var m=document.querySelector('meta[property=\"product:price:amount\"],meta[property=\"og:price:amount\"],meta[name=\"price\"],meta[itemprop=\"price\"]');if(m&&m.content){return m.content;}var t=document.body?document.body.innerText:'';var r=t.match(/(?:\\$|USD\\s?)(\\d{1,3}(?:,\\d{3})*(?:\\.\\d{1,2})?)/i);return r?r[1].replace(/,/g,''):'';}function addImage(list,url){if(!url||typeof url!=='string'){return;}var clean=url.trim();if(!/^https?:\\/\\//i.test(clean)){return;}if(list.indexOf(clean)===-1){list.push(clean);}}var images=[];var meta=document.querySelector('meta[property=\"og:image\"],meta[name=\"twitter:image\"],meta[property=\"twitter:image\"],link[rel=\"image_src\"]');if(meta){addImage(images,meta.content||meta.href||'');}var all=document.images||[];for(var i=0;i<all.length&&images.length<8;i++){var img=all[i];if(!img){continue;}var src=img.currentSrc||img.src||'';if(!src){continue;}if((img.naturalWidth||0)<220||(img.naturalHeight||0)<220){continue;}addImage(images,src);}var d={title:document.title||'',product_url:location.href||'',image_url:images[0]||'',image_candidates:images,price:getPrice()};var u=BASE_URL+'&data='+encodeURIComponent(JSON.stringify(d));window.open(u,'_blank','noopener');})();";

	return 'javascript:' . str_replace('BASE_URL', $base_url_js, $script);
}
