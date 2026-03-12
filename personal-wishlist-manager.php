<?php
/**
 * Plugin Name: Personal Wishlist Manager
 * Plugin URI: https://example.com/personal-wishlist-manager
 * Description: A personal wishlist manager with admin dashboard and frontend display
 * Version: 1.0.7
 * Author: Sunira
 * Author URI: https://example.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: personal-wishlist-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('PWM_VERSION', '1.0.7');
define('PWM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PWM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PWM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Personal_Wishlist_Manager {

	/**
	 * Instance of this class
	 *
	 * @var Personal_Wishlist_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the plugin
	 *
	 * @return Personal_Wishlist_Manager
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
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once PWM_PLUGIN_DIR . 'includes/class-database.php';
		require_once PWM_PLUGIN_DIR . 'includes/class-admin.php';
		require_once PWM_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once PWM_PLUGIN_DIR . 'includes/class-shortcode.php';
		require_once PWM_PLUGIN_DIR . 'includes/class-ajax.php';
		require_once PWM_PLUGIN_DIR . 'includes/class-blocks.php';
		require_once PWM_PLUGIN_DIR . 'includes/functions.php';
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		add_action('plugins_loaded', array($this, 'init'));
	}

	/**
	 * Initialize plugin components
	 */
	public function init() {
		// Initialize database
		PWM_Database::get_instance();

		// Initialize admin
		if (is_admin()) {
			PWM_Admin::get_instance();
		}

		// Initialize frontend
		PWM_Frontend::get_instance();

		// Initialize shortcode
		PWM_Shortcode::get_instance();

		// Initialize AJAX
		PWM_Ajax::get_instance();

		// Initialize Gutenberg blocks
		PWM_Blocks::get_instance();

		// Load text domain
		load_plugin_textdomain('personal-wishlist-manager', false, dirname(PWM_PLUGIN_BASENAME) . '/languages');
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create database table
		PWM_Database::create_table();

		// Set default options
		$defaults = array(
			'pwm_default_columns' => 3,
			'pwm_items_per_page' => 20,
			'pwm_show_filters' => true,
			'pwm_default_sort' => 'date_desc',
			'pwm_currency_symbol' => '$',
			'pwm_currency_position' => 'before',
			'pwm_enable_ajax' => true,
			'pwm_delete_on_uninstall' => false,
			'pwm_custom_css' => '',
			'pwm_quick_add_token' => wp_generate_password(40, false, false)
		);

		foreach ($defaults as $key => $value) {
			if (get_option($key) === false) {
				add_option($key, $value);
			}
		}

		// Add custom capability to administrator and editor roles
		$roles = array('administrator', 'editor');
		foreach ($roles as $role_name) {
			$role = get_role($role_name);
			if ($role) {
				$role->add_cap('manage_wishlist_items');
			}
		}

		// Set transient for welcome notice
		set_transient('pwm_activation_notice', true, 60);

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clear transients
		delete_transient('pwm_activation_notice');

		// Flush rewrite rules
		flush_rewrite_rules();
	}
}

/**
 * Initialize the plugin
 */
function pwm_init() {
	return Personal_Wishlist_Manager::get_instance();
}

// Start the plugin
pwm_init();
