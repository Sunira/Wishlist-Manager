<?php
/**
 * Uninstall script for Personal Wishlist Manager
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Check if user wants to delete data
$delete_data = get_option('pwm_delete_on_uninstall', false);

if ($delete_data) {
	global $wpdb;

	// Drop custom table
	$table_name = $wpdb->prefix . 'wishlist_items';
	$wpdb->query("DROP TABLE IF EXISTS $table_name");

	// Delete all plugin options
	delete_option('pwm_default_columns');
	delete_option('pwm_items_per_page');
	delete_option('pwm_default_sort');
	delete_option('pwm_show_filters');
	delete_option('pwm_currency_symbol');
	delete_option('pwm_currency_position');
	delete_option('pwm_custom_css');
	delete_option('pwm_enable_ajax');
	delete_option('pwm_delete_on_uninstall');

	// Delete transients
	delete_transient('pwm_activation_notice');

	// Remove custom capabilities
	$roles = array('administrator', 'editor');
	foreach ($roles as $role_name) {
		$role = get_role($role_name);
		if ($role) {
			$role->remove_cap('manage_wishlist_items');
		}
	}

	// Clear any cached data
	wp_cache_flush();
}
