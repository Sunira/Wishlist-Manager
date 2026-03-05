<?php
/**
 * Admin class for managing admin interface
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PWM_Admin class
 */
class PWM_Admin {

	/**
	 * Instance of this class
	 *
	 * @var PWM_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 *
	 * @return PWM_Admin
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
		add_action('admin_menu', array($this, 'register_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
		add_action('admin_notices', array($this, 'display_admin_notices'));
		add_action('admin_init', array($this, 'handle_actions'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu() {
		// Main menu
		add_menu_page(
			__('Wishlist', 'personal-wishlist-manager'),
			__('Wishlist', 'personal-wishlist-manager'),
			'manage_wishlist_items',
			'wishlist',
			array($this, 'display_items_list_page'),
			'dashicons-heart',
			30
		);

		// All Items submenu
		add_submenu_page(
			'wishlist',
			__('All Items', 'personal-wishlist-manager'),
			__('All Items', 'personal-wishlist-manager'),
			'manage_wishlist_items',
			'wishlist',
			array($this, 'display_items_list_page')
		);

		// Add New submenu
		add_submenu_page(
			'wishlist',
			__('Add New', 'personal-wishlist-manager'),
			__('Add New', 'personal-wishlist-manager'),
			'manage_wishlist_items',
			'wishlist-add-new',
			array($this, 'display_items_list_page')
		);

		// Categories submenu
		add_submenu_page(
			'wishlist',
			__('Categories', 'personal-wishlist-manager'),
			__('Categories', 'personal-wishlist-manager'),
			'manage_wishlist_items',
			'wishlist-categories',
			array($this, 'display_categories_page')
		);

		// Settings submenu
		add_submenu_page(
			'wishlist',
			__('Settings', 'personal-wishlist-manager'),
			__('Settings', 'personal-wishlist-manager'),
			'manage_wishlist_items',
			'wishlist-settings',
			array($this, 'display_settings_page')
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current page hook
	 */
	public function enqueue_admin_assets($hook) {
		// Only load on plugin pages
		if (strpos($hook, 'wishlist') === false) {
			return;
		}

		// Enqueue WordPress media library
		wp_enqueue_media();

		// Enqueue admin styles
		wp_enqueue_style(
			'pwm-admin-styles',
			PWM_PLUGIN_URL . 'admin/css/admin-styles.css',
			array(),
			PWM_VERSION
		);

		// Enqueue admin scripts
		wp_enqueue_script(
			'pwm-admin-scripts',
			PWM_PLUGIN_URL . 'admin/js/admin-scripts.js',
			array('jquery', 'jquery-ui-autocomplete'),
			PWM_VERSION,
			true
		);

		// Localize script
		wp_localize_script('pwm-admin-scripts', 'pwmAdmin', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pwm_nonce'),
			'confirmDelete' => __('Are you sure you want to delete this item?', 'personal-wishlist-manager'),
			'confirmBulkDelete' => __('Are you sure you want to delete the selected items?', 'personal-wishlist-manager')
		));
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		// Welcome notice on activation
		if (get_transient('pwm_activation_notice')) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e('Thank you for installing Personal Wishlist Manager! Get started by adding your first wishlist item.', 'personal-wishlist-manager'); ?></p>
			</div>
			<?php
			delete_transient('pwm_activation_notice');
		}

		// Display errors from transient
		if ($error = get_transient('pwm_admin_error')) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html($error); ?></p>
			</div>
			<?php
			delete_transient('pwm_admin_error');
		}

		// Display multiple errors from transient
		if ($errors = get_transient('pwm_admin_errors')) {
			foreach ($errors as $error) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html($error); ?></p>
				</div>
				<?php
			}
			delete_transient('pwm_admin_errors');
		}

		// Display any other notices
		settings_errors('pwm_messages');
	}

	/**
	 * Handle admin actions
	 */
	public function handle_actions() {
		if (!current_user_can('manage_wishlist_items')) {
			return;
		}

		// Handle save/update item action
		if (isset($_POST['pwm_save_item'])) {
			// Verify nonce
			if (!isset($_POST['pwm_nonce']) || !wp_verify_nonce($_POST['pwm_nonce'], 'pwm_save_item')) {
				wp_die(__('Security check failed', 'personal-wishlist-manager'));
			}

			$db = PWM_Database::get_instance();
			$item_id = isset($_GET['item']) ? intval($_GET['item']) : 0;
			$is_edit = $item_id > 0;

			// Get form data
			$data = array(
				'title' => isset($_POST['title']) ? $_POST['title'] : '',
				'category' => isset($_POST['category']) ? $_POST['category'] : '',
				'image_url' => isset($_POST['image_url']) ? $_POST['image_url'] : '',
				'product_url' => isset($_POST['product_url']) ? $_POST['product_url'] : '',
				'price' => isset($_POST['price']) ? $_POST['price'] : '',
				'reason' => isset($_POST['reason']) ? $_POST['reason'] : ''
			);

			// Validate data
			$errors = pwm_validate_item_data($data);

			if (empty($errors)) {
				// Sanitize data
				$data = pwm_sanitize_item_data($data);

				// Apply filter
				$data = apply_filters('pwm_item_data', $data);

				if ($is_edit) {
					// Update item
					do_action('pwm_before_save_item', $data);

					if ($db->update_item($item_id, $data)) {
						do_action('pwm_after_save_item', $item_id, $data);
						// Redirect to view page
						wp_redirect(add_query_arg(array('page' => 'wishlist', 'action' => 'view', 'item' => $item_id, 'message' => 'updated'), admin_url('admin.php')));
						exit;
					} else {
						// Store error in transient to display after redirect
						set_transient('pwm_admin_error', __('Error updating item.', 'personal-wishlist-manager'), 30);
					}
				} else {
					// Insert new item
					do_action('pwm_before_save_item', $data);

					$new_item_id = $db->insert_item($data);
					if ($new_item_id) {
						do_action('pwm_after_save_item', $new_item_id, $data);
						// Redirect to view page
						wp_redirect(add_query_arg(array('page' => 'wishlist', 'action' => 'view', 'item' => $new_item_id, 'message' => 'added'), admin_url('admin.php')));
						exit;
					} else {
						// Store error in transient to display after redirect
						set_transient('pwm_admin_error', __('Error adding item.', 'personal-wishlist-manager'), 30);
					}
				}
			} else {
				// Store errors in transient to display after redirect
				set_transient('pwm_admin_errors', $errors, 30);
			}
		}

		// Handle delete action
		if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['item'])) {
			check_admin_referer('pwm_delete_item_' . $_GET['item']);

			$item_id = intval($_GET['item']);
			$db = PWM_Database::get_instance();

			if ($db->delete_item($item_id)) {
				do_action('pwm_item_deleted', $item_id);
				wp_redirect(add_query_arg('message', 'deleted', pwm_get_admin_url('wishlist')));
				exit;
			}
		}

		// Handle bulk delete
		if (isset($_POST['action']) && $_POST['action'] === 'bulk-delete' && isset($_POST['items'])) {
			check_admin_referer('bulk-wishlist-items');

			$db = PWM_Database::get_instance();
			$deleted_count = 0;

			foreach ($_POST['items'] as $item_id) {
				if ($db->delete_item(intval($item_id))) {
					$deleted_count++;
					do_action('pwm_item_deleted', intval($item_id));
				}
			}

			if ($deleted_count > 0) {
				wp_redirect(add_query_arg('message', 'bulk_deleted', pwm_get_admin_url('wishlist')));
				exit;
			}
		}
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		// Display settings
		register_setting('pwm_display_settings', 'pwm_default_columns');
		register_setting('pwm_display_settings', 'pwm_items_per_page');
		register_setting('pwm_display_settings', 'pwm_default_sort');
		register_setting('pwm_display_settings', 'pwm_show_filters');
		register_setting('pwm_display_settings', 'pwm_currency_symbol');
		register_setting('pwm_display_settings', 'pwm_currency_position');

		// Advanced settings
		register_setting('pwm_advanced_settings', 'pwm_custom_css');
		register_setting('pwm_advanced_settings', 'pwm_enable_ajax');
		register_setting('pwm_advanced_settings', 'pwm_delete_on_uninstall');
	}

	/**
	 * Display items list page
	 */
	public function display_items_list_page() {
		$action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'wishlist';

		// Support both the canonical wishlist actions and the legacy submenu slug.
		if ($page === 'wishlist-add-new' || $action === 'add' || $action === 'edit') {
			require_once PWM_PLUGIN_DIR . 'admin/views/item-form.php';
		} elseif ($action === 'view' && isset($_GET['item'])) {
			require_once PWM_PLUGIN_DIR . 'admin/views/item-view.php';
		} else {
			require_once PWM_PLUGIN_DIR . 'admin/views/items-list.php';
		}
	}

	/**
	 * Display item form page (add/edit)
	 */
	public function display_item_form_page() {
		require_once PWM_PLUGIN_DIR . 'admin/views/item-form.php';
	}

	/**
	 * Display categories page
	 */
	public function display_categories_page() {
		require_once PWM_PLUGIN_DIR . 'admin/views/categories.php';
	}

	/**
	 * Display settings page
	 */
	public function display_settings_page() {
		require_once PWM_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
