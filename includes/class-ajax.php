<?php
/**
 * AJAX class for handling AJAX requests
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PWM_Ajax class
 */
class PWM_Ajax {

	/**
	 * Instance of this class
	 *
	 * @var PWM_Ajax
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 *
	 * @return PWM_Ajax
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
		// Admin AJAX actions
		add_action('wp_ajax_pwm_delete_item', array($this, 'ajax_delete_item'));
		add_action('wp_ajax_pwm_get_tags', array($this, 'ajax_get_tags'));
		add_action('wp_ajax_pwm_export_csv', array($this, 'ajax_export_csv'));
		add_action('wp_ajax_pwm_export_json', array($this, 'ajax_export_json'));

		// Frontend AJAX actions (nopriv for public access)
		add_action('wp_ajax_nopriv_pwm_filter_items', array($this, 'ajax_filter_items'));
		add_action('wp_ajax_pwm_filter_items', array($this, 'ajax_filter_items'));
	}

	/**
	 * AJAX delete item
	 */
	public function ajax_delete_item() {
		check_ajax_referer('pwm_nonce', 'nonce');

		if (!current_user_can('manage_wishlist_items')) {
			wp_send_json_error(__('Insufficient permissions', 'personal-wishlist-manager'));
		}

		$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

		if (!$item_id) {
			wp_send_json_error(__('Invalid item ID', 'personal-wishlist-manager'));
		}

		$db = PWM_Database::get_instance();

		if ($db->delete_item($item_id)) {
			do_action('pwm_item_deleted', $item_id);
			wp_send_json_success(__('Item deleted successfully', 'personal-wishlist-manager'));
		} else {
			wp_send_json_error(__('Error deleting item', 'personal-wishlist-manager'));
		}
	}

	/**
	 * AJAX get tags for autocomplete
	 */
	public function ajax_get_tags() {
		check_ajax_referer('pwm_nonce', 'nonce');

		if (!current_user_can('manage_wishlist_items')) {
			wp_send_json_error(__('Insufficient permissions', 'personal-wishlist-manager'));
		}

		$term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
		$all_tags = pwm_get_tags();
		$matching_tags = array();

		foreach ($all_tags as $tag => $count) {
			if (empty($term) || stripos($tag, $term) !== false) {
				$matching_tags[] = array(
					'label' => $tag . ' (' . $count . ')',
					'value' => $tag
				);
			}
		}

		wp_send_json($matching_tags);
	}

	/**
	 * AJAX export to CSV
	 */
	public function ajax_export_csv() {
		if (!isset($_GET['pwm_export']) || $_GET['pwm_export'] !== 'csv') {
			return;
		}

		if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'pwm_export')) {
			wp_die(__('Security check failed', 'personal-wishlist-manager'));
		}

		if (!current_user_can('manage_wishlist_items')) {
			wp_die(__('Insufficient permissions', 'personal-wishlist-manager'));
		}

		$db = PWM_Database::get_instance();
		$items = $db->get_items();

		// Set headers for CSV download
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="wishlist-export-' . date('Y-m-d') . '.csv"');

		$output = fopen('php://output', 'w');

		// Add CSV headers
		fputcsv($output, array('Title', 'Category', 'Tags', 'Image URL', 'Product URL', 'Price', 'Reason', 'Created At'));

		// Add data rows
		foreach ($items as $item) {
			fputcsv($output, array(
				$item->title,
				$item->category,
				$item->tags,
				$item->image_url,
				$item->product_url,
				$item->price,
				wp_strip_all_tags($item->reason),
				$item->created_at
			));
		}

		fclose($output);
		exit;
	}

	/**
	 * AJAX export to JSON
	 */
	public function ajax_export_json() {
		if (!isset($_GET['pwm_export']) || $_GET['pwm_export'] !== 'json') {
			return;
		}

		if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'pwm_export')) {
			wp_die(__('Security check failed', 'personal-wishlist-manager'));
		}

		if (!current_user_can('manage_wishlist_items')) {
			wp_die(__('Insufficient permissions', 'personal-wishlist-manager'));
		}

		$db = PWM_Database::get_instance();
		$items = $db->get_items();

		// Set headers for JSON download
		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="wishlist-export-' . date('Y-m-d') . '.json"');

		echo json_encode($items, JSON_PRETTY_PRINT);
		exit;
	}

	/**
	 * AJAX filter items for frontend
	 */
	public function ajax_filter_items() {
		check_ajax_referer('pwm_frontend_nonce', 'nonce');

		// Get filter parameters
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
		$tags = isset($_POST['tags']) && is_array($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : array();
		$min_price = isset($_POST['min_price']) ? floatval($_POST['min_price']) : 0;
		$max_price = isset($_POST['max_price']) ? floatval($_POST['max_price']) : 999999;
		$sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'alphabetical';
		$columns = isset($_POST['columns']) ? intval($_POST['columns']) : 3;

		// Get sort parameters
		$sort_params = pwm_get_sort_params($sort);

		// Build query args
		$query_args = array(
			'orderby' => $sort_params['orderby'],
			'order' => $sort_params['order']
		);

		// Add search filter - search in title, category, and reason
		if (!empty($search)) {
			$query_args['search'] = $search;
		}

		// Add category filter
		if (!empty($category)) {
			$query_args['category'] = $category;
		}

		// Add tags filter
		if (!empty($tags)) {
			$query_args['tags'] = $tags;
		}

		// Add price range filter
		if ($min_price > 0 || $max_price < 999999) {
			$query_args['min_price'] = $min_price;
			$query_args['max_price'] = $max_price;
		}

		// Get filtered items
		$db = PWM_Database::get_instance();
		$items = $db->get_items($query_args);
		$total_count = count($items);

		// Generate HTML for items
		ob_start();
		if (empty($items)) {
			echo '<div class="wishlist-empty-state">';
			echo '<p>' . esc_html__('No items match your filters.', 'personal-wishlist-manager') . '</p>';
			echo '<button type="button" id="pwm-clear-filters-empty" class="button">' . esc_html__('Clear filters', 'personal-wishlist-manager') . '</button>';
			echo '</div>';
		} else {
			foreach ($items as $item) {
				include PWM_PLUGIN_DIR . 'templates/wishlist-card.php';
			}
		}
		$html = ob_get_clean();

		wp_send_json_success(array(
			'html' => $html,
			'count' => $total_count,
			'search_term' => $search,
			'sort' => $sort
		));
	}
}
