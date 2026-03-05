<?php
/**
 * Template for individual wishlist card - Modern Design
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Check if user can edit
$can_edit = current_user_can('manage_wishlist_items');

// Allow filtering of card HTML
$card_html = '';
ob_start();
?>

<div class="wishlist-card" data-item-id="<?php echo esc_attr($item->id); ?>">
	<?php if ($can_edit) : ?>
		<div class="wishlist-card-actions">
			<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 'action' => 'edit', 'item' => $item->id), admin_url('admin.php'))); ?>"
			   class="card-action-btn edit-btn"
			   title="<?php _e('Edit item', 'personal-wishlist-manager'); ?>">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
					<path d="M11.333 2A1.886 1.886 0 0 1 14 4.667l-9 9-3.667 1L2.667 11l9-9z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
			<a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'wishlist', 'action' => 'delete', 'item' => $item->id), admin_url('admin.php')), 'pwm_delete_item_' . $item->id)); ?>"
			   class="card-action-btn delete-btn pwm-delete-item"
			   title="<?php _e('Delete item', 'personal-wishlist-manager'); ?>">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
					<path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 0 1 1.334-1.334h2.666a1.333 1.333 0 0 1 1.334 1.334V4m2 0v9.333a1.333 1.333 0 0 1-1.334 1.334H4.667a1.333 1.333 0 0 1-1.334-1.334V4h9.334z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</div>
	<?php endif; ?>

	<div class="wishlist-card-image">
		<a href="<?php echo esc_url($item->product_url); ?>"
		   class="wishlist-card-image-link"
		   target="_blank"
		   rel="noopener noreferrer"
		   title="<?php _e('View Product', 'personal-wishlist-manager'); ?>">
			<img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->title); ?>" loading="lazy">
		</a>
		<span class="wishlist-card-price"><?php echo pwm_format_price($item->price); ?></span>
		<a href="<?php echo esc_url($item->product_url); ?>"
		   class="wishlist-card-shop-btn"
		   target="_blank"
		   rel="noopener noreferrer"
		   title="<?php _e('View Product', 'personal-wishlist-manager'); ?>">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
				<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M3 6h18M16 10a4 4 0 0 1-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</a>
	</div>

	<div class="wishlist-card-content">
		<div class="wishlist-card-header">
			<h3 class="wishlist-card-title"><?php echo esc_html($item->title); ?></h3>
			<span class="wishlist-card-category"><?php echo esc_html($item->category); ?></span>
		</div>

		<?php if (!empty($item->reason)) : ?>
			<div class="wishlist-card-reason">
				<span><?php echo wp_kses_post($item->reason); ?></span>
			</div>
		<?php endif; ?>

	</div>
</div>

<?php
$card_html = ob_get_clean();
echo apply_filters('pwm_card_html', $card_html, $item);
?>
