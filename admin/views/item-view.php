<?php
/**
 * Admin view for viewing a single wishlist item
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Check user capabilities
if (!current_user_can('manage_wishlist_items')) {
	wp_die(__('You do not have sufficient permissions to access this page.', 'personal-wishlist-manager'));
}

$db = PWM_Database::get_instance();
$item_id = isset($_GET['item']) ? intval($_GET['item']) : 0;
$item = $item_id ? $db->get_item($item_id) : null;

if (!$item) {
	wp_die(__('Wishlist item not found.', 'personal-wishlist-manager'));
}

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html($item->title); ?></h1>
	<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 'action' => 'edit', 'item' => $item->id), admin_url('admin.php'))); ?>" class="page-title-action">
		<?php _e('Edit Item', 'personal-wishlist-manager'); ?>
	</a>
	<a href="<?php echo esc_url(add_query_arg('page', 'wishlist', admin_url('admin.php'))); ?>" class="page-title-action">
		<?php _e('View All Items', 'personal-wishlist-manager'); ?>
	</a>
	<hr class="wp-header-end">

	<div class="pwm-item-view">
		<div class="pwm-item-view-content">
			<div class="pwm-item-image-large">
				<img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->title); ?>">
			</div>

			<div class="pwm-item-details">
				<div class="pwm-detail-row">
					<span class="pwm-detail-label"><?php _e('Price:', 'personal-wishlist-manager'); ?></span>
					<span class="pwm-detail-value pwm-price"><?php echo pwm_format_price($item->price); ?></span>
				</div>

				<div class="pwm-detail-row">
					<span class="pwm-detail-label"><?php _e('Category:', 'personal-wishlist-manager'); ?></span>
					<span class="pwm-detail-value">
						<span class="pwm-category-badge"><?php echo esc_html($item->category); ?></span>
					</span>
				</div>

				<div class="pwm-detail-row">
					<span class="pwm-detail-label"><?php _e('Product URL:', 'personal-wishlist-manager'); ?></span>
					<span class="pwm-detail-value">
						<a href="<?php echo esc_url($item->product_url); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html($item->product_url); ?>
							<span class="dashicons dashicons-external"></span>
						</a>
					</span>
				</div>

				<?php if (!empty($item->reason)) : ?>
				<div class="pwm-detail-row pwm-detail-full">
					<span class="pwm-detail-label"><?php _e('Reason/Notes:', 'personal-wishlist-manager'); ?></span>
					<div class="pwm-detail-value pwm-reason-box">
						<?php echo wp_kses_post($item->reason); ?>
					</div>
				</div>
				<?php endif; ?>

				<div class="pwm-detail-row">
					<span class="pwm-detail-label"><?php _e('Added:', 'personal-wishlist-manager'); ?></span>
					<span class="pwm-detail-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at)); ?></span>
				</div>

				<div class="pwm-detail-row">
					<span class="pwm-detail-label"><?php _e('Last Updated:', 'personal-wishlist-manager'); ?></span>
					<span class="pwm-detail-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->updated_at)); ?></span>
				</div>
			</div>

			<div class="pwm-item-actions">
				<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 'action' => 'edit', 'item' => $item->id), admin_url('admin.php'))); ?>" class="button button-primary button-large">
					<span class="dashicons dashicons-edit"></span>
					<?php _e('Edit Item', 'personal-wishlist-manager'); ?>
				</a>
				<a href="<?php echo esc_url($item->product_url); ?>" class="button button-secondary button-large" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-external"></span>
					<?php _e('View Product', 'personal-wishlist-manager'); ?>
				</a>
				<a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'wishlist', 'action' => 'delete', 'item' => $item->id), admin_url('admin.php')), 'pwm_delete_item_' . $item->id)); ?>" class="button button-link-delete pwm-delete-item">
					<span class="dashicons dashicons-trash"></span>
					<?php _e('Delete Item', 'personal-wishlist-manager'); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<style>
.pwm-item-view {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 30px;
	margin-top: 20px;
	max-width: 900px;
}

.pwm-item-view-content {
	display: grid;
	gap: 30px;
}

.pwm-item-image-large {
	text-align: center;
	background: #f0f0f1;
	border-radius: 8px;
	padding: 20px;
	max-width: 600px;
	margin: 0 auto;
}

.pwm-item-image-large img {
	width: 100%;
	height: 400px;
	object-fit: cover;
	border-radius: 4px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pwm-item-details {
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.pwm-detail-row {
	display: grid;
	grid-template-columns: 150px 1fr;
	gap: 15px;
	align-items: start;
	padding: 12px 0;
	border-bottom: 1px solid #f0f0f1;
}

.pwm-detail-row.pwm-detail-full {
	grid-template-columns: 1fr;
}

.pwm-detail-label {
	font-weight: 600;
	color: #1d2327;
}

.pwm-detail-value {
	color: #50575e;
}

.pwm-detail-value a {
	text-decoration: none;
	color: #2271b1;
}

.pwm-detail-value a:hover {
	color: #135e96;
}

.pwm-detail-value .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
	vertical-align: middle;
}

.pwm-price {
	font-size: 24px;
	font-weight: 700;
	color: #2271b1;
}

.pwm-reason-box {
	background: #fff9e6;
	padding: 15px;
	border-left: 3px solid #ffcc00;
	border-radius: 4px;
	margin-top: 10px;
}

.pwm-item-actions {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	padding-top: 20px;
	border-top: 2px solid #f0f0f1;
}

.pwm-item-actions .button {
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.pwm-item-actions .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

@media (max-width: 782px) {
	.pwm-detail-row {
		grid-template-columns: 1fr;
		gap: 5px;
	}

	.pwm-item-actions {
		flex-direction: column;
	}

	.pwm-item-actions .button {
		width: 100%;
		justify-content: center;
	}
}
</style>
