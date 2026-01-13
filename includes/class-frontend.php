<?php
/**
 * Frontend class for managing frontend functionality
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PWM_Frontend class
 */
class PWM_Frontend {

	/**
	 * Instance of this class
	 *
	 * @var PWM_Frontend
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 *
	 * @return PWM_Frontend
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
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
		add_action('wp_head', array($this, 'output_custom_css'));
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue on pages with the shortcode or block
		global $post;
		if (!is_a($post, 'WP_Post')) {
			return;
		}

		// Check for shortcode or block
		$has_wishlist = has_shortcode($post->post_content, 'personal_wishlist') ||
		                has_block('personal-wishlist-manager/wishlist', $post);

		if (!$has_wishlist) {
			return;
		}

		// Enqueue frontend styles
		wp_enqueue_style(
			'pwm-frontend-styles',
			PWM_PLUGIN_URL . 'public/css/frontend-styles.css',
			array(),
			PWM_VERSION
		);

		// Enqueue frontend scripts
		wp_enqueue_script(
			'pwm-frontend-scripts',
			PWM_PLUGIN_URL . 'public/js/frontend-scripts.js',
			array('jquery'),
			PWM_VERSION,
			true
		);

		// Localize script
		wp_localize_script('pwm-frontend-scripts', 'pwmFrontend', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pwm_frontend_nonce'),
			'enableAjax' => get_option('pwm_enable_ajax', true)
		));
	}

	/**
	 * Output custom CSS in head
	 */
	public function output_custom_css() {
		$custom_css = get_option('pwm_custom_css', '');
		if (!empty($custom_css)) {
			echo '<style type="text/css">' . wp_strip_all_tags($custom_css) . '</style>';
		}
	}
}
