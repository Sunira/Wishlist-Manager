<?php
/**
 * Blocks class for managing Gutenberg blocks
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PWM_Blocks class
 */
class PWM_Blocks {

	/**
	 * Instance of this class
	 *
	 * @var PWM_Blocks
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 *
	 * @return PWM_Blocks
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
		add_action('init', array($this, 'register_blocks'));
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
	}

	/**
	 * Register Gutenberg blocks
	 */
	public function register_blocks() {
		// Register wishlist block
		register_block_type('personal-wishlist-manager/wishlist', array(
			'attributes' => array(
				'categories' => array(
					'type' => 'array',
					'default' => array()
				),
				'columns' => array(
					'type' => 'number',
					'default' => get_option('pwm_default_columns', 3)
				),
				'sort' => array(
					'type' => 'string',
					'default' => get_option('pwm_default_sort', 'date_desc')
				),
				'limit' => array(
					'type' => 'number',
					'default' => -1
				),
				'showFilters' => array(
					'type' => 'boolean',
					'default' => get_option('pwm_show_filters', true)
				),
				'userId' => array(
					'type' => 'number',
					'default' => 0
				)
			),
			'render_callback' => array($this, 'render_wishlist_block')
		));
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_style(
			'pwm-frontend-styles',
			PWM_PLUGIN_URL . 'public/css/frontend-styles.css',
			array(),
			PWM_VERSION
		);

		// Enqueue block JavaScript
		wp_enqueue_script(
			'pwm-wishlist-block',
			PWM_PLUGIN_URL . 'blocks/wishlist/block.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render'
			),
			PWM_VERSION,
			true
		);

		// Enqueue block editor styles
		wp_enqueue_style(
			'pwm-wishlist-block-editor',
			PWM_PLUGIN_URL . 'blocks/wishlist/editor.css',
			array(),
			PWM_VERSION
		);

		// Localize block data
		wp_localize_script('pwm-wishlist-block', 'pwmBlockData', array(
			'categories' => $this->get_block_categories_data(),
			'sortOptions' => $this->get_block_sort_options()
		));
	}

	/**
	 * Get categories data for block sidebar
	 *
	 * @return array Array of categories with name and count
	 */
	private function get_block_categories_data() {
		$categories = pwm_get_categories();
		$data = array();

		foreach ($categories as $category) {
			$data[] = array(
				'name' => $category->category,
				'count' => intval($category->count)
			);
		}

		return $data;
	}

	/**
	 * Get sort options for block sidebar
	 *
	 * @return array Array of sort options with label and value
	 */
	private function get_block_sort_options() {
		$sort_options = pwm_get_sort_options();
		$data = array();

		foreach ($sort_options as $value => $label) {
			$data[] = array(
				'label' => $label,
				'value' => $value
			);
		}

		return $data;
	}

	/**
	 * Render wishlist block
	 *
	 * @param array $attributes Block attributes
	 * @return string Rendered HTML
	 */
	public function render_wishlist_block($attributes) {
		// Parse defaults
		$attributes = wp_parse_args($attributes, array(
			'categories' => array(),
			'columns' => get_option('pwm_default_columns', 3),
			'sort' => get_option('pwm_default_sort', 'date_desc'),
			'limit' => -1,
			'showFilters' => get_option('pwm_show_filters', true),
			'userId' => 0
		));

		// Sanitize and convert to shortcode format
		$shortcode_atts = array(
			'columns' => intval($attributes['columns']),
			'sort' => sanitize_text_field($attributes['sort']),
			'limit' => intval($attributes['limit']),
			'show_filters' => $attributes['showFilters'] ? 'true' : 'false',
			'user_id' => intval($attributes['userId']),
			'preview_mode' => (defined('REST_REQUEST') && REST_REQUEST) ? 'true' : 'false'
		);

		// Handle categories (convert array to proper format)
		if (!empty($attributes['categories']) && is_array($attributes['categories'])) {
			$shortcode_atts['categories'] = array_map('sanitize_text_field', $attributes['categories']);
		}

		// Get shortcode instance and render
		$shortcode = PWM_Shortcode::get_instance();

		// Apply filter to allow modification of block attributes
		$shortcode_atts = apply_filters('pwm_block_attributes', $shortcode_atts, $attributes);

		// Render using shortcode logic
		$html = $shortcode->render_shortcode($shortcode_atts);

		// Apply filter to allow modification of block output
		$html = apply_filters('pwm_block_render', $html, $attributes);

		return $html;
	}
}
