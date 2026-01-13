<?php
/**
 * Admin view for tags management
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

$tags = pwm_get_tags();
arsort($tags); // Sort by count descending

// Calculate max count for tag cloud sizing
$max_count = !empty($tags) ? max($tags) : 1;
$min_count = !empty($tags) ? min($tags) : 1;
?>

<div class="wrap">
	<h1><?php _e('Wishlist Tags', 'personal-wishlist-manager'); ?></h1>

	<div class="pwm-tags-view-toggle" style="margin: 20px 0;">
		<button type="button" class="button" id="pwm-view-cloud" style="margin-right: 10px;">
			<?php _e('Cloud View', 'personal-wishlist-manager'); ?>
		</button>
		<button type="button" class="button" id="pwm-view-list">
			<?php _e('List View', 'personal-wishlist-manager'); ?>
		</button>
	</div>

	<?php if (empty($tags)) : ?>
		<div class="notice notice-info">
			<p><?php _e('No tags found. Tags will appear here once you add them to your wishlist items.', 'personal-wishlist-manager'); ?></p>
		</div>
	<?php else : ?>

		<!-- Cloud View -->
		<div id="pwm-cloud-view" class="pwm-tag-cloud" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
			<?php foreach ($tags as $tag => $count) : ?>
				<?php
				// Calculate font size based on count
				$font_size = 12;
				if ($max_count > $min_count) {
					$font_size = 12 + (($count - $min_count) / ($max_count - $min_count)) * 20;
				}
				?>
				<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 's' => urlencode($tag)), admin_url('admin.php'))); ?>"
				   class="tag-cloud-link"
				   style="font-size: <?php echo $font_size; ?>px; margin: 5px; display: inline-block;">
					<?php echo esc_html($tag); ?>
					<span class="tag-link-count">(<?php echo intval($count); ?>)</span>
				</a>
			<?php endforeach; ?>
		</div>

		<!-- List View -->
		<div id="pwm-list-view" style="display: none;">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-name column-primary">
							<?php _e('Tag Name', 'personal-wishlist-manager'); ?>
						</th>
						<th scope="col" class="manage-column column-count">
							<?php _e('Item Count', 'personal-wishlist-manager'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($tags as $tag => $count) : ?>
						<tr>
							<td class="name column-name column-primary" data-colname="<?php _e('Tag Name', 'personal-wishlist-manager'); ?>">
								<strong><?php echo esc_html($tag); ?></strong>
								<div class="row-actions">
									<span class="view">
										<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 's' => urlencode($tag)), admin_url('admin.php'))); ?>">
											<?php _e('View Items', 'personal-wishlist-manager'); ?>
										</a>
									</span>
								</div>
							</td>
							<td class="count column-count" data-colname="<?php _e('Item Count', 'personal-wishlist-manager'); ?>">
								<?php echo intval($count); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-name column-primary">
							<?php _e('Tag Name', 'personal-wishlist-manager'); ?>
						</th>
						<th scope="col" class="manage-column column-count">
							<?php _e('Item Count', 'personal-wishlist-manager'); ?>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#pwm-view-cloud').on('click', function() {
		$('#pwm-cloud-view').show();
		$('#pwm-list-view').hide();
		$(this).addClass('button-primary');
		$('#pwm-view-list').removeClass('button-primary');
	});

	$('#pwm-view-list').on('click', function() {
		$('#pwm-cloud-view').hide();
		$('#pwm-list-view').show();
		$(this).addClass('button-primary');
		$('#pwm-view-cloud').removeClass('button-primary');
	});

	// Set default view
	$('#pwm-view-cloud').addClass('button-primary');
});
</script>
