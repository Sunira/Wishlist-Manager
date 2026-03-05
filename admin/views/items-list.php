<?php
/**
 * Admin view for items list
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Wishlist Items List Table class
 */
class PWM_Items_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => 'item',
			'plural' => 'items',
			'ajax' => false
		));
	}

	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'image' => __('Image', 'personal-wishlist-manager'),
			'title' => __('Title', 'personal-wishlist-manager'),
			'category' => __('Category', 'personal-wishlist-manager'),
			'price' => __('Price', 'personal-wishlist-manager'),
			'created_at' => __('Date Added', 'personal-wishlist-manager')
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'title' => array('title', false),
			'category' => array('category', false),
			'price' => array('price', false),
			'created_at' => array('created_at', true)
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'bulk-delete' => __('Delete', 'personal-wishlist-manager')
		);
	}

	/**
	 * Column default
	 *
	 * @param object $item        Item object
	 * @param string $column_name Column name
	 * @return string
	 */
	public function column_default($item, $column_name) {
		switch ($column_name) {
			case 'image':
				return sprintf(
					'<img src="%s" alt="%s" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">',
					esc_url($item->image_url),
					esc_attr($item->title)
				);
			case 'category':
				return '<span class="pwm-category-badge">' . esc_html($item->category) . '</span>';
			case 'price':
				return '<strong>' . pwm_format_price($item->price) . '</strong>';
			case 'created_at':
				return date_i18n(get_option('date_format'), strtotime($item->created_at));
			default:
				return '';
		}
	}

	/**
	 * Column checkbox
	 *
	 * @param object $item Item object
	 * @return string
	 */
	public function column_cb($item) {
		return sprintf('<input type="checkbox" name="items[]" value="%d" />', $item->id);
	}

	/**
	 * Column title
	 *
	 * @param object $item Item object
	 * @return string
	 */
	public function column_title($item) {
		$edit_url = add_query_arg(
			array('page' => 'wishlist', 'action' => 'edit', 'item' => $item->id),
			admin_url('admin.php')
		);

		$delete_url = wp_nonce_url(
			add_query_arg(
				array('page' => 'wishlist', 'action' => 'delete', 'item' => $item->id),
				admin_url('admin.php')
			),
			'pwm_delete_item_' . $item->id
		);

		$actions = array(
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				esc_url($edit_url),
				__('Edit', 'personal-wishlist-manager')
			),
			'delete' => sprintf(
				'<a href="%s" class="pwm-delete-item" style="color: #b32d2e;">%s</a>',
				esc_url($delete_url),
				__('Delete', 'personal-wishlist-manager')
			),
			'view' => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url($item->product_url),
				__('View Product', 'personal-wishlist-manager')
			)
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url($edit_url),
			esc_html($item->title),
			$this->row_actions($actions)
		);
	}

	/**
	 * Prepare items
	 */
	public function prepare_items() {
		$db = PWM_Database::get_instance();

		// Get current page
		$current_page = $this->get_pagenum();
		$per_page = 20;

		// Get search query
		$search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

		// Get sort parameters
		$orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'created_at';
		$order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';

		// Build query args
		$args = array(
			'search' => $search,
			'orderby' => $orderby,
			'order' => $order,
			'limit' => $per_page,
			'offset' => ($current_page - 1) * $per_page
		);

		// Get items
		$items = $db->get_items($args);
		$total_items = $db->get_items_count(array('search' => $search));

		// Set pagination
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));

		// Set columns
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		// Set items
		$this->items = $items;
	}

	/**
	 * Display when no items found
	 */
	public function no_items() {
		$add_new_url = add_query_arg(
			array('page' => 'wishlist', 'action' => 'add'),
			admin_url('admin.php')
		);
		printf(
			__('No wishlist items found. <a href="%s">Add your first item!</a>', 'personal-wishlist-manager'),
			esc_url($add_new_url)
		);
	}
}

// Handle messages
if (isset($_GET['message'])) {
	switch ($_GET['message']) {
		case 'added':
			pwm_admin_notice(__('Item added successfully.', 'personal-wishlist-manager'), 'success');
			break;
		case 'updated':
			pwm_admin_notice(__('Item updated successfully.', 'personal-wishlist-manager'), 'success');
			break;
		case 'deleted':
			pwm_admin_notice(__('Item deleted successfully.', 'personal-wishlist-manager'), 'success');
			break;
		case 'bulk_deleted':
			pwm_admin_notice(__('Items deleted successfully.', 'personal-wishlist-manager'), 'success');
			break;
	}
}

// Create list table instance
$list_table = new PWM_Items_List_Table();
$list_table->prepare_items();
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('Wishlist Items', 'personal-wishlist-manager'); ?></h1>
	<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 'action' => 'add'), admin_url('admin.php'))); ?>" class="page-title-action">
		<?php _e('Add New', 'personal-wishlist-manager'); ?>
	</a>
	<hr class="wp-header-end">

	<form method="get">
		<input type="hidden" name="page" value="wishlist">
		<?php
		$list_table->search_box(__('Search Items', 'personal-wishlist-manager'), 'pwm-search');
		$list_table->display();
		?>
	</form>
</div>
