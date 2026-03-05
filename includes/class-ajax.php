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
		add_action('wp_ajax_pwm_export_csv', array($this, 'ajax_export_csv'));
		add_action('wp_ajax_pwm_export_json', array($this, 'ajax_export_json'));

		// Quick add endpoint (supports logged-in and logged-out requests)
		add_action('admin_post_pwm_quick_add', array($this, 'handle_quick_add'));
		add_action('admin_post_nopriv_pwm_quick_add', array($this, 'handle_quick_add'));

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
		fputcsv($output, array('Title', 'Category', 'Image URL', 'Product URL', 'Price', 'Reason', 'Created At'));

		// Add data rows
		foreach ($items as $item) {
			fputcsv($output, array(
				$item->title,
				$item->category,
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

	/**
	 * Handle browser bookmarklet quick add requests.
	 */
	public function handle_quick_add() {
		$token = isset($_REQUEST['token']) ? sanitize_text_field(wp_unslash($_REQUEST['token'])) : '';
		$expected_token = pwm_get_quick_add_token();

		if (empty($token) || !hash_equals($expected_token, $token)) {
			wp_die(__('Invalid quick add token.', 'personal-wishlist-manager'), 403);
		}

		$is_submit = isset($_POST['pwm_quick_add_submit']);

		if ($is_submit) {
			if (
				!isset($_POST['pwm_quick_add_nonce']) ||
				!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pwm_quick_add_nonce'])), 'pwm_quick_add_submit')
			) {
				wp_die(__('Security check failed.', 'personal-wishlist-manager'), 403);
			}

			$item_data = array(
				'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
				'category' => isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '',
				'image_url' => isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '',
				'product_url' => isset($_POST['product_url']) ? esc_url_raw(wp_unslash($_POST['product_url'])) : '',
				'price' => isset($_POST['price']) ? (float) wp_unslash($_POST['price']) : 0,
				'reason' => isset($_POST['reason']) ? wp_kses_post(wp_unslash($_POST['reason'])) : '',
				'user_id' => get_current_user_id() > 0 ? get_current_user_id() : 1
			);

			if (empty($item_data['image_url'])) {
				$item_data['image_url'] = PWM_PLUGIN_URL . 'public/images/quick-add-placeholder.svg';
			}

			if (empty($item_data['reason'])) {
				$item_data['reason'] = sprintf(
					/* translators: %s: date/time */
					__('Added via browser quick add on %s', 'personal-wishlist-manager'),
					current_time('Y-m-d H:i')
				);
			}

			$errors = pwm_validate_item_data($item_data);
			if (!empty($errors)) {
				$this->render_quick_add_form($token, $item_data, $errors);
				return;
			}

			$db = PWM_Database::get_instance();
			$item_id = $db->insert_item($item_data);

			if (!$item_id) {
				wp_die(__('Unable to save wishlist item.', 'personal-wishlist-manager'), 500);
			}

			if (is_user_logged_in() && current_user_can('manage_wishlist_items')) {
				$redirect_url = add_query_arg(
					array(
						'page' => 'wishlist',
						'message' => 'added'
					),
					admin_url('admin.php')
				);
				wp_safe_redirect($redirect_url);
				exit;
			}

			wp_die(__('Item added to wishlist successfully. You can close this tab.', 'personal-wishlist-manager'));
		}

		$raw_data = isset($_GET['data']) ? wp_unslash($_GET['data']) : '';
		if (empty($raw_data)) {
			wp_die(__('Missing quick add payload.', 'personal-wishlist-manager'), 400);
		}

		$decoded_data = json_decode(rawurldecode($raw_data), true);
		if (!is_array($decoded_data)) {
			wp_die(__('Invalid quick add payload.', 'personal-wishlist-manager'), 400);
		}

		$product_url = isset($decoded_data['product_url']) ? esc_url_raw($decoded_data['product_url']) : '';
		if (empty($product_url)) {
			wp_die(__('Product URL is required for quick add.', 'personal-wishlist-manager'), 400);
		}

		$title = isset($decoded_data['title']) ? sanitize_text_field($decoded_data['title']) : '';
		if (empty($title)) {
			$host = wp_parse_url($product_url, PHP_URL_HOST);
			$title = !empty($host) ? $host : __('Quick Add Item', 'personal-wishlist-manager');
		}

		$image_url = isset($decoded_data['image_url']) ? esc_url_raw($decoded_data['image_url']) : '';
		if (empty($image_url)) {
			$image_url = PWM_PLUGIN_URL . 'public/images/quick-add-placeholder.svg';
		}

		$raw_price = isset($decoded_data['price']) ? wp_strip_all_tags((string) $decoded_data['price']) : '';
		$normalized_price = preg_replace('/[^0-9.]/', '', $raw_price);
		$price = is_numeric($normalized_price) ? (float) $normalized_price : 0;
		if ($price < 0) {
			$price = 0;
		}

		$form_data = array(
			'title' => $title,
			'category' => __('Quick Add', 'personal-wishlist-manager'),
			'image_url' => $image_url,
			'product_url' => $product_url,
			'price' => $price,
			'reason' => sprintf(
				/* translators: %s: date/time */
				__('Added via browser quick add on %s', 'personal-wishlist-manager'),
				current_time('Y-m-d H:i')
			)
		);

		$this->render_quick_add_form($token, $form_data);
	}

	/**
	 * Render prefilled quick add confirmation form.
	 *
	 * @param string $token Quick add token.
	 * @param array  $data  Form data.
	 * @param array  $errors Validation errors.
	 */
	private function render_quick_add_form($token, $data, $errors = array()) {
		$form_action = admin_url('admin-post.php');
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo('charset'); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e('Quick Add Wishlist Item', 'personal-wishlist-manager'); ?></title>
			<style>
				body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:#f6f7f8; color:#1f2937; margin:0; padding:24px; }
				.pwm-quick-add-wrap { max-width:720px; margin:0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; }
				h1 { margin:0 0 16px; font-size:24px; }
				.pwm-note { margin:0 0 20px; color:#6b7280; }
				.pwm-errors { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 14px; margin-bottom:16px; }
				.pwm-errors ul { margin:0; padding-left:20px; }
				.pwm-row { margin-bottom:14px; }
				label { display:block; font-weight:600; margin-bottom:6px; }
				input[type="text"], input[type="url"], input[type="number"], textarea { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px 12px; font-size:15px; box-sizing:border-box; }
				textarea { min-height:100px; resize:vertical; }
				.pwm-actions { display:flex; gap:10px; margin-top:18px; }
				.pwm-btn { border:none; border-radius:8px; padding:11px 16px; font-weight:600; cursor:pointer; }
				.pwm-btn-primary { background:#2563eb; color:#fff; }
				.pwm-btn-secondary { background:#e5e7eb; color:#111827; text-decoration:none; display:inline-flex; align-items:center; }
				.pwm-preview { width:100%; max-height:240px; object-fit:contain; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; }
			</style>
		</head>
		<body>
			<div class="pwm-quick-add-wrap">
				<h1><?php esc_html_e('Quick Add to Wishlist', 'personal-wishlist-manager'); ?></h1>
				<p class="pwm-note"><?php esc_html_e('Review and edit the details below before saving.', 'personal-wishlist-manager'); ?></p>

				<?php if (!empty($errors)) : ?>
					<div class="pwm-errors">
						<ul>
							<?php foreach ($errors as $error) : ?>
								<li><?php echo esc_html($error); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url($form_action); ?>">
					<input type="hidden" name="action" value="pwm_quick_add">
					<input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
					<?php wp_nonce_field('pwm_quick_add_submit', 'pwm_quick_add_nonce'); ?>

					<div class="pwm-row">
						<label for="pwm-qa-title"><?php esc_html_e('Title', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-title" type="text" name="title" value="<?php echo esc_attr($data['title']); ?>" required>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-category"><?php esc_html_e('Category', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-category" type="text" name="category" value="<?php echo esc_attr($data['category']); ?>" required>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-product-url"><?php esc_html_e('Product URL', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-product-url" type="url" name="product_url" value="<?php echo esc_url($data['product_url']); ?>" required>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-image-url"><?php esc_html_e('Image URL', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-image-url" type="url" name="image_url" value="<?php echo esc_url($data['image_url']); ?>">
					</div>
					<div class="pwm-row">
						<img class="pwm-preview" src="<?php echo esc_url($data['image_url']); ?>" alt="">
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-price"><?php esc_html_e('Price', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-price" type="number" step="0.01" min="0.01" name="price" value="<?php echo esc_attr($data['price']); ?>" required>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-reason"><?php esc_html_e('Reason / Notes', 'personal-wishlist-manager'); ?></label>
						<textarea id="pwm-qa-reason" name="reason"><?php echo esc_textarea($data['reason']); ?></textarea>
					</div>

					<div class="pwm-actions">
						<button class="pwm-btn pwm-btn-primary" type="submit" name="pwm_quick_add_submit" value="1"><?php esc_html_e('Save to Wishlist', 'personal-wishlist-manager'); ?></button>
						<a class="pwm-btn pwm-btn-secondary" href="#" onclick="window.close();return false;"><?php esc_html_e('Cancel', 'personal-wishlist-manager'); ?></a>
					</div>
				</form>
			</div>
		</body>
		</html>
		<?php
		exit;
	}
}
