<?php
/**
 * Template for wishlist grid
 *
 * @package Personal_Wishlist_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wishlist-results">
	<!-- Results header -->
	<div class="wishlist-results-header">
		<div class="wishlist-results-summary">
			<div class="wishlist-results-count">
				<?php
				if ($total_count === 0) {
					_e('No items found', 'personal-wishlist-manager');
				} elseif ($total_count === 1) {
					_e('Showing 1 item', 'personal-wishlist-manager');
				} else {
					printf(
						__('Showing %d items', 'personal-wishlist-manager'),
						intval($total_count)
					);
				}
				?>
			</div>
			<p class="wishlist-results-note"><?php _e('Use the filters above to quickly narrow the list.', 'personal-wishlist-manager'); ?></p>
		</div>
		<div class="wishlist-results-status"><?php _e('Live view', 'personal-wishlist-manager'); ?></div>
	</div>

	<?php if (empty($items)) : ?>
		<!-- Empty state -->
		<div class="wishlist-empty-state">
			<p><?php _e('Your wishlist is empty.', 'personal-wishlist-manager'); ?></p>
			<?php if (current_user_can('manage_wishlist_items')) : ?>
				<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 'action' => 'add'), admin_url('admin.php'))); ?>" class="button button-primary">
					<?php _e('Add your first item!', 'personal-wishlist-manager'); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<!-- Items grid -->
		<div class="personal-wishlist-grid" data-columns="<?php echo esc_attr($columns); ?>" id="pwm-grid">
			<?php foreach ($items as $item) : ?>
				<?php include PWM_PLUGIN_DIR . 'templates/wishlist-card.php'; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
