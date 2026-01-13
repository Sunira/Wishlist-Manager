<?php
/**
 * Shortcode class for displaying wishlist on frontend
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PWM_Shortcode class
 */
class PWM_Shortcode {

	/**
	 * Instance of this class
	 *
	 * @var PWM_Shortcode
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 *
	 * @return PWM_Shortcode
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_shortcode('personal_wishlist', array($this, 'render_shortcode'));
	}

	/**
	 * Render the wishlist shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_shortcode($atts) {
		// Parse shortcode attributes
		$atts = shortcode_atts(array(
			'columns' => get_option('pwm_default_columns', 3),
			'category' => '',
			'categories' => array(),
			'sort' => get_option('pwm_default_sort', 'alphabetical'),
			'limit' => -1,
			'user_id' => 0,
			'show_filters' => 'true',
			'show_search' => 'true',
			'show_category' => 'true',
			'show_tags' => 'true',
			'show_price' => 'true',
			'show_sort' => 'true'
		), $atts, 'personal_wishlist');

		// Sanitize attributes
		$columns = intval($atts['columns']);
		$columns = max(1, min(4, $columns)); // Ensure 1-4 range

		$category = sanitize_text_field($atts['category']);
		$shortcode_categories = is_array($atts['categories']) ? array_map('sanitize_text_field', $atts['categories']) : array();
		$sort = sanitize_text_field($atts['sort']);
		$limit = intval($atts['limit']);
		$user_id = intval($atts['user_id']);

		// Convert string booleans to actual booleans
		$show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
		$show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
		$show_category = filter_var($atts['show_category'], FILTER_VALIDATE_BOOLEAN);
		$show_tags = filter_var($atts['show_tags'], FILTER_VALIDATE_BOOLEAN);
		$show_price = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
		$show_sort = filter_var($atts['show_sort'], FILTER_VALIDATE_BOOLEAN);

		// Store filter visibility options for use in templates
		$filter_options = array(
			'show_filters' => $show_filters,
			'show_search' => $show_search,
			'show_category' => $show_category,
			'show_tags' => $show_tags,
			'show_price' => $show_price,
			'show_sort' => $show_sort
		);

		// Build query args
		$sort_params = pwm_get_sort_params($sort);
		$query_args = array(
			'orderby' => $sort_params['orderby'],
			'order' => $sort_params['order']
		);

		// Add category filter
		if (!empty($category)) {
			$query_args['category'] = $category;
		} elseif (!empty($shortcode_categories)) {
			// Categories from block attributes or shortcode
			$query_args['categories'] = $shortcode_categories;
		}

		// Add user filter
		if ($user_id > 0) {
			$query_args['user_id'] = $user_id;
		}

		// Add limit
		if ($limit > 0) {
			$query_args['limit'] = $limit;
		}

		// Apply filter to allow modification of query args
		$query_args = apply_filters('pwm_query_args', $query_args);

		// Get items
		$db = PWM_Database::get_instance();
		$items = $db->get_items($query_args);
		$total_count = $db->get_items_count($query_args);

		// Start output buffering
		ob_start();

		// Apply action before grid
		do_action('pwm_before_grid', $items, $atts);

		echo '<div class="personal-wishlist-container" data-columns="' . esc_attr($columns) . '">';

		// Render filters (only if show_filters is true)
		if ($show_filters) {
			$this->render_filters($filter_options);
		}

		// Render grid
		$this->render_grid($items, $columns, $total_count);

		echo '</div>';

		// Apply action after grid
		do_action('pwm_after_grid', $items, $atts);

		return ob_get_clean();
	}

	/**
	 * Render wishlist filters
	 *
	 * @param array $filter_options Filter visibility options
	 */
	private function render_filters($filter_options = array()) {
		include PWM_PLUGIN_DIR . 'templates/wishlist-filters.php';
	}

	/**
	 * Render wishlist grid
	 *
	 * @param array $items       Wishlist items
	 * @param int   $columns     Number of columns
	 * @param int   $total_count Total items count
	 */
	private function render_grid($items, $columns, $total_count) {
		include PWM_PLUGIN_DIR . 'templates/wishlist-grid.php';
	}
}
