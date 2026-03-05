<?php
/**
 * Database class for managing wishlist items table
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PWM_Database class
 */
class PWM_Database {

	/**
	 * Instance of this class
	 *
	 * @var PWM_Database
	 */
	private static $instance = null;

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Get instance of the class
	 *
	 * @return PWM_Database
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
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'wishlist_items';
		$this->maybe_migrate_schema();
	}

	/**
	 * Remove legacy schema columns no longer used by the plugin.
	 */
	private function maybe_migrate_schema() {
		global $wpdb;

		$table_exists = $wpdb->get_var(
			$wpdb->prepare('SHOW TABLES LIKE %s', $this->table_name)
		);

		if ($table_exists !== $this->table_name) {
			return;
		}

		$has_tags_column = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$this->table_name} LIKE %s",
				'tags'
			)
		);

		if (!empty($has_tags_column)) {
			$wpdb->query("ALTER TABLE {$this->table_name} DROP COLUMN tags");
		}
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Create the wishlist items table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wishlist_items';
		$charset_collate = $wpdb->get_charset_collate();

		// Note: dbDelta requires specific formatting - no IF NOT EXISTS, two spaces after PRIMARY KEY
		$sql = "CREATE TABLE $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			category VARCHAR(100) NOT NULL,
			image_url TEXT NOT NULL,
			product_url TEXT NOT NULL,
			price DECIMAL(10,2) NOT NULL,
			reason TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			user_id BIGINT UNSIGNED DEFAULT 1,
			PRIMARY KEY  (id),
			KEY idx_category (category),
			KEY idx_price (price),
			KEY idx_user_id (user_id),
			KEY idx_created_at (created_at)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Insert a new wishlist item
	 *
	 * @param array $data Item data
	 * @return int|false Item ID on success, false on failure
	 */
	public function insert_item($data) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'title' => $data['title'],
				'category' => $data['category'],
				'image_url' => $data['image_url'],
				'product_url' => $data['product_url'],
				'price' => $data['price'],
				'reason' => isset($data['reason']) ? $data['reason'] : '',
				'user_id' => isset($data['user_id']) ? $data['user_id'] : get_current_user_id()
			),
			array('%s', '%s', '%s', '%s', '%f', '%s', '%d')
		);

		if ($result === false) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update a wishlist item
	 *
	 * @param int   $item_id Item ID
	 * @param array $data    Item data
	 * @return bool True on success, false on failure
	 */
	public function update_item($item_id, $data) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array(
				'title' => $data['title'],
				'category' => $data['category'],
				'image_url' => $data['image_url'],
				'product_url' => $data['product_url'],
				'price' => $data['price'],
				'reason' => isset($data['reason']) ? $data['reason'] : ''
			),
			array('id' => $item_id),
			array('%s', '%s', '%s', '%s', '%f', '%s'),
			array('%d')
		);

		return $result !== false;
	}

	/**
	 * Delete a wishlist item
	 *
	 * @param int $item_id Item ID
	 * @return bool True on success, false on failure
	 */
	public function delete_item($item_id) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array('id' => $item_id),
			array('%d')
		);

		return $result !== false;
	}

	/**
	 * Get a single wishlist item
	 *
	 * @param int $item_id Item ID
	 * @return object|null Item object or null
	 */
	public function get_item($item_id) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$item_id
			)
		);
	}

	/**
	 * Get wishlist items with filters
	 *
	 * @param array $args Query arguments
	 * @return array Array of items
	 */
	public function get_items($args = array()) {
		global $wpdb;

		$defaults = array(
			'search' => '',
			'category' => '',
			'categories' => array(),
			'min_price' => 0,
			'max_price' => 999999,
			'user_id' => 0,
			'orderby' => 'created_at',
			'order' => 'DESC',
			'limit' => -1,
			'offset' => 0
		);

		$args = wp_parse_args($args, $defaults);

		$where = array('1=1');
		$prepare_values = array();

		// Search across title, category, and reason
		if (!empty($args['search'])) {
			$where[] = '(title LIKE %s OR category LIKE %s OR reason LIKE %s)';
			$search_term = '%' . $wpdb->esc_like($args['search']) . '%';
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
		}

		// Single category filter
		if (!empty($args['category'])) {
			$where[] = 'category = %s';
			$prepare_values[] = $args['category'];
		}

		// Multiple categories filter
		if (!empty($args['categories']) && is_array($args['categories'])) {
			$category_placeholders = implode(',', array_fill(0, count($args['categories']), '%s'));
			$where[] = "category IN ($category_placeholders)";
			$prepare_values = array_merge($prepare_values, $args['categories']);
		}

		// Price range
		if ($args['min_price'] > 0 || $args['max_price'] < 999999) {
			$where[] = 'price BETWEEN %f AND %f';
			$prepare_values[] = $args['min_price'];
			$prepare_values[] = $args['max_price'];
		}

		// User ID
		if ($args['user_id'] > 0) {
			$where[] = 'user_id = %d';
			$prepare_values[] = $args['user_id'];
		}

		// Build WHERE clause
		$where_sql = implode(' AND ', $where);

		// Sanitize ORDER BY
		$allowed_orderby = array('title', 'category', 'price', 'created_at', 'updated_at');
		$orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';

		// Sanitize ORDER
		$order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

		// Build query
		$sql = "SELECT * FROM {$this->table_name} WHERE $where_sql ORDER BY $orderby $order";

		// Add LIMIT
		if ($args['limit'] > 0) {
			$sql .= ' LIMIT %d';
			$prepare_values[] = $args['limit'];

			if ($args['offset'] > 0) {
				$sql .= ' OFFSET %d';
				$prepare_values[] = $args['offset'];
			}
		}

		// Prepare and execute query
		if (!empty($prepare_values)) {
			$sql = $wpdb->prepare($sql, $prepare_values);
		}

		return $wpdb->get_results($sql);
	}

	/**
	 * Get total count of items with filters
	 *
	 * @param array $args Query arguments
	 * @return int Total count
	 */
	public function get_items_count($args = array()) {
		global $wpdb;

		$defaults = array(
			'search' => '',
			'category' => '',
			'categories' => array(),
			'min_price' => 0,
			'max_price' => 999999,
			'user_id' => 0
		);

		$args = wp_parse_args($args, $defaults);

		$where = array('1=1');
		$prepare_values = array();

		// Search across title, category, and reason
		if (!empty($args['search'])) {
			$where[] = '(title LIKE %s OR category LIKE %s OR reason LIKE %s)';
			$search_term = '%' . $wpdb->esc_like($args['search']) . '%';
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
		}

		// Single category
		if (!empty($args['category'])) {
			$where[] = 'category = %s';
			$prepare_values[] = $args['category'];
		}

		// Multiple categories
		if (!empty($args['categories']) && is_array($args['categories'])) {
			$category_placeholders = implode(',', array_fill(0, count($args['categories']), '%s'));
			$where[] = "category IN ($category_placeholders)";
			$prepare_values = array_merge($prepare_values, $args['categories']);
		}

		// Price range
		if ($args['min_price'] > 0 || $args['max_price'] < 999999) {
			$where[] = 'price BETWEEN %f AND %f';
			$prepare_values[] = $args['min_price'];
			$prepare_values[] = $args['max_price'];
		}

		// User ID
		if ($args['user_id'] > 0) {
			$where[] = 'user_id = %d';
			$prepare_values[] = $args['user_id'];
		}

		$where_sql = implode(' AND ', $where);
		$sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE $where_sql";

		if (!empty($prepare_values)) {
			$sql = $wpdb->prepare($sql, $prepare_values);
		}

		return (int) $wpdb->get_var($sql);
	}

	/**
	 * Get all unique categories
	 *
	 * @return array Array of categories
	 */
	public function get_categories() {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT category, COUNT(*) as count
			FROM {$this->table_name}
			GROUP BY category
			ORDER BY category ASC"
		);

		return $results;
	}

	/**
	 * Get price range
	 *
	 * @return array Array with min and max prices
	 */
	public function get_price_range() {
		global $wpdb;

		$result = $wpdb->get_row(
			"SELECT MIN(price) as min_price, MAX(price) as max_price FROM {$this->table_name}"
		);

		return array(
			'min' => $result ? floatval($result->min_price) : 0,
			'max' => $result ? floatval($result->max_price) : 0
		);
	}
}
