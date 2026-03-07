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
		$sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : get_option('pwm_default_sort', 'date_desc');
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
			$image_candidates = $this->parse_image_candidates(
				isset($_POST['image_candidates']) ? wp_unslash($_POST['image_candidates']) : ''
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

			$errors = array();
			$allowed_categories = pwm_get_wordpress_category_names();
			if (!empty($allowed_categories) && !in_array($item_data['category'], $allowed_categories, true)) {
				$errors[] = __('Please choose a valid WordPress category.', 'personal-wishlist-manager');
			}

			if (
				!empty($item_data['image_url']) &&
				!$this->is_local_media_url($item_data['image_url'])
			) {
				$sideload_result = $this->sideload_quick_add_image($item_data['image_url'], $item_data['title']);

				if (is_wp_error($sideload_result)) {
					$errors[] = sprintf(
						/* translators: %s: error message */
						__('Unable to import image into Media Library: %s', 'personal-wishlist-manager'),
						$sideload_result->get_error_message()
					);
				} else {
					$item_data['image_url'] = $sideload_result;
				}
			}

			$errors = array_merge($errors, pwm_validate_item_data($item_data));
			if (!empty($errors)) {
				$item_data['image_candidates'] = $image_candidates;
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

		$image_candidates = $this->sanitize_image_candidates(
			isset($decoded_data['image_candidates']) ? $decoded_data['image_candidates'] : array()
		);
		$image_url = isset($decoded_data['image_url']) ? esc_url_raw($decoded_data['image_url']) : '';
		if (empty($image_url)) {
			$image_url = !empty($image_candidates) ? $image_candidates[0] : PWM_PLUGIN_URL . 'public/images/quick-add-placeholder.svg';
		}
		if (!empty($image_url) && !in_array($image_url, $image_candidates, true)) {
			array_unshift($image_candidates, $image_url);
			$image_candidates = $this->sanitize_image_candidates($image_candidates);
		}

		$raw_price = isset($decoded_data['price']) ? wp_strip_all_tags((string) $decoded_data['price']) : '';
		$normalized_price = preg_replace('/[^0-9.]/', '', $raw_price);
		$price = is_numeric($normalized_price) ? (float) $normalized_price : 0;
		if ($price < 0) {
			$price = 0;
		}

		$form_data = array(
			'title' => $title,
			'category' => '',
			'image_url' => $image_url,
			'image_candidates' => $image_candidates,
			'product_url' => $product_url,
			'price' => $price,
			'reason' => sprintf(
				/* translators: %s: date/time */
				__('Added via browser quick add on %s', 'personal-wishlist-manager'),
				current_time('Y-m-d H:i')
			)
		);

		$wp_categories = pwm_get_wordpress_category_names();
		if (!empty($wp_categories)) {
			$form_data['category'] = $wp_categories[0];
		} else {
			$form_data['category'] = __('Uncategorized', 'personal-wishlist-manager');
		}

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
		$wp_categories = pwm_get_wordpress_category_names();
		$selected_image = isset($data['image_url']) ? esc_url_raw($data['image_url']) : '';
		$image_candidates = $this->sanitize_image_candidates(
			isset($data['image_candidates']) ? $data['image_candidates'] : array()
		);
		if (!empty($selected_image) && !in_array($selected_image, $image_candidates, true)) {
			array_unshift($image_candidates, $selected_image);
			$image_candidates = $this->sanitize_image_candidates($image_candidates);
		}
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
				input[type="text"], input[type="url"], input[type="number"], select, textarea { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px 12px; font-size:15px; box-sizing:border-box; background:#fff; }
				textarea { min-height:100px; resize:vertical; }
				.pwm-actions { display:flex; gap:10px; margin-top:18px; }
				.pwm-btn { border:none; border-radius:8px; padding:11px 16px; font-weight:600; cursor:pointer; }
				.pwm-btn-primary { background:#2563eb; color:#fff; }
				.pwm-btn-secondary { background:#e5e7eb; color:#111827; text-decoration:none; display:inline-flex; align-items:center; }
				.pwm-preview { width:100%; max-height:240px; object-fit:contain; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; }
				.pwm-image-picker { display:grid; grid-template-columns:repeat(auto-fill,minmax(88px,1fr)); gap:8px; margin-top:10px; }
				.pwm-image-choice { border:2px solid #d1d5db; border-radius:8px; padding:0; background:#fff; cursor:pointer; overflow:hidden; }
				.pwm-image-choice img { display:block; width:100%; aspect-ratio:1/1; object-fit:cover; }
				.pwm-image-choice.is-selected { border-color:#2563eb; box-shadow:0 0 0 1px #2563eb inset; }
				.pwm-image-help { margin:6px 0 0; font-size:13px; color:#6b7280; }
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
					<input type="hidden" name="image_candidates" value="<?php echo esc_attr(wp_json_encode($image_candidates)); ?>">
					<?php wp_nonce_field('pwm_quick_add_submit', 'pwm_quick_add_nonce'); ?>

					<div class="pwm-row">
						<label for="pwm-qa-title"><?php esc_html_e('Title', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-title" type="text" name="title" value="<?php echo esc_attr($data['title']); ?>" required>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-category"><?php esc_html_e('Category', 'personal-wishlist-manager'); ?></label>
						<select id="pwm-qa-category" name="category" required>
							<?php if (empty($wp_categories)) : ?>
								<option value="<?php echo esc_attr($data['category']); ?>"><?php echo esc_html($data['category']); ?></option>
							<?php else : ?>
								<?php foreach ($wp_categories as $category_name) : ?>
									<option value="<?php echo esc_attr($category_name); ?>" <?php selected($data['category'], $category_name); ?>>
										<?php echo esc_html($category_name); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-product-url"><?php esc_html_e('Product URL', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-product-url" type="url" name="product_url" value="<?php echo esc_url($data['product_url']); ?>" required>
					</div>
					<div class="pwm-row">
						<label for="pwm-qa-image-url"><?php esc_html_e('Image URL', 'personal-wishlist-manager'); ?></label>
						<input id="pwm-qa-image-url" type="url" name="image_url" value="<?php echo esc_url($selected_image); ?>">
						<?php if (!empty($image_candidates)) : ?>
							<div class="pwm-image-picker" id="pwm-image-picker">
								<?php foreach ($image_candidates as $candidate_url) : ?>
									<button type="button"
										class="pwm-image-choice <?php echo $candidate_url === $selected_image ? 'is-selected' : ''; ?>"
										data-image-url="<?php echo esc_url($candidate_url); ?>"
										aria-label="<?php esc_attr_e('Use this image', 'personal-wishlist-manager'); ?>">
										<img src="<?php echo esc_url($candidate_url); ?>" alt="">
									</button>
								<?php endforeach; ?>
							</div>
							<p class="pwm-image-help"><?php esc_html_e('Pick one of the detected images, or paste a different image URL.', 'personal-wishlist-manager'); ?></p>
						<?php endif; ?>
					</div>
					<div class="pwm-row">
						<img class="pwm-preview" id="pwm-qa-preview" src="<?php echo esc_url($selected_image); ?>" alt="">
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
			<script>
				(function () {
					var picker = document.getElementById('pwm-image-picker');
					var imageInput = document.getElementById('pwm-qa-image-url');
					var preview = document.getElementById('pwm-qa-preview');
					if (!imageInput || !preview) {
						return;
					}

					function updateSelection(selectedUrl) {
						if (!picker) {
							return;
						}

						var buttons = picker.querySelectorAll('.pwm-image-choice');
						for (var i = 0; i < buttons.length; i++) {
							var btn = buttons[i];
							var url = btn.getAttribute('data-image-url') || '';
							if (url === selectedUrl) {
								btn.classList.add('is-selected');
							} else {
								btn.classList.remove('is-selected');
							}
						}
					}

					if (picker) {
						picker.addEventListener('click', function (event) {
							var button = event.target.closest('.pwm-image-choice');
							if (!button) {
								return;
							}

							event.preventDefault();
							var url = button.getAttribute('data-image-url') || '';
							if (!url) {
								return;
							}

							imageInput.value = url;
							preview.src = url;
							updateSelection(url);
						});
					}

					imageInput.addEventListener('input', function () {
						var url = imageInput.value.trim();
						if (url) {
							preview.src = url;
						}
						updateSelection(url);
					});
				})();
			</script>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Parse and sanitize serialized image candidates.
	 *
	 * @param string $raw_json JSON-encoded candidate URL array.
	 * @return array
	 */
	private function parse_image_candidates($raw_json) {
		if (!is_string($raw_json) || $raw_json === '') {
			return array();
		}

		$decoded = json_decode($raw_json, true);
		if (!is_array($decoded)) {
			return array();
		}

		return $this->sanitize_image_candidates($decoded);
	}

	/**
	 * Sanitize candidate image URLs.
	 *
	 * @param array $candidates Candidate URL list.
	 * @return array
	 */
	private function sanitize_image_candidates($candidates) {
		if (!is_array($candidates)) {
			return array();
		}

		$clean = array();
		foreach ($candidates as $candidate) {
			$url = esc_url_raw((string) $candidate);
			if (empty($url)) {
				continue;
			}
			if (!filter_var($url, FILTER_VALIDATE_URL)) {
				continue;
			}
			if (in_array($url, $clean, true)) {
				continue;
			}

			$clean[] = $url;
			if (count($clean) >= 12) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Check whether URL is already a local media-library URL.
	 *
	 * @param string $url URL to inspect.
	 * @return bool
	 */
	private function is_local_media_url($url) {
		$uploads = wp_get_upload_dir();
		if (empty($uploads['baseurl'])) {
			return false;
		}

		return strpos($url, $uploads['baseurl']) === 0;
	}

	/**
	 * Download a remote image and save it to the WordPress Media Library.
	 *
	 * @param string $image_url Remote image URL.
	 * @param string $title     Optional title for attachment context.
	 * @return string|WP_Error Local media URL on success.
	 */
	private function sideload_quick_add_image($image_url, $title = '') {
		if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
			return new WP_Error('pwm_invalid_image_url', __('Invalid image URL.', 'personal-wishlist-manager'));
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$temp_file = download_url($image_url, 20);
		if (is_wp_error($temp_file)) {
			return $temp_file;
		}

		$path = wp_parse_url($image_url, PHP_URL_PATH);
		$filename = wp_basename($path);
		if (empty($filename) || strpos($filename, '.') === false) {
			$filename = 'quick-add-image.jpg';
		}

		$file_array = array(
			'name' => sanitize_file_name($filename),
			'tmp_name' => $temp_file,
		);

		$attachment_id = media_handle_sideload($file_array, 0, $title);
		if (is_wp_error($attachment_id)) {
			@unlink($temp_file);
			return $attachment_id;
		}

		$attachment_url = wp_get_attachment_url($attachment_id);
		if (empty($attachment_url)) {
			return new WP_Error('pwm_attachment_url_missing', __('Could not resolve uploaded image URL.', 'personal-wishlist-manager'));
		}

		return $attachment_url;
	}
}
