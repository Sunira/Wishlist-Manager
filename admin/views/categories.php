<?php
/**
 * Admin view for categories management
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

$categories = pwm_get_categories();
?>

<div class="wrap">
	<h1><?php _e('Wishlist Categories', 'personal-wishlist-manager'); ?></h1>

	<div id="col-container" class="wp-clearfix">
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h2><?php _e('Add New Category', 'personal-wishlist-manager'); ?></h2>
					<form id="addcat" method="post" action="" class="validate">
						<?php wp_nonce_field('pwm_add_category', 'pwm_category_nonce'); ?>
						<div class="form-field form-required">
							<label for="category-name"><?php _e('Name', 'personal-wishlist-manager'); ?></label>
							<input name="category_name" id="category-name" type="text" value="" size="40" aria-required="true">
							<p><?php _e('The name is how it appears on your site.', 'personal-wishlist-manager'); ?></p>
						</div>
						<p class="submit">
							<input type="submit" name="add_category" id="submit" class="button button-primary" value="<?php _e('Add New Category', 'personal-wishlist-manager'); ?>">
						</p>
					</form>
				</div>
			</div>
		</div>

		<div id="col-right">
			<div class="col-wrap">
				<div class="tablenav top">
					<div class="alignleft actions">
						<h2 class="screen-reader-text"><?php _e('Filter categories list', 'personal-wishlist-manager'); ?></h2>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-name column-primary">
								<?php _e('Name', 'personal-wishlist-manager'); ?>
							</th>
							<th scope="col" class="manage-column column-count">
								<?php _e('Count', 'personal-wishlist-manager'); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($categories)) : ?>
							<tr class="no-items">
								<td class="colspanchange" colspan="2">
									<?php _e('No categories found.', 'personal-wishlist-manager'); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ($categories as $category) : ?>
								<tr>
									<td class="name column-name column-primary" data-colname="<?php _e('Name', 'personal-wishlist-manager'); ?>">
										<strong><?php echo esc_html($category->category); ?></strong>
										<div class="row-actions">
											<span class="view">
												<a href="<?php echo esc_url(add_query_arg(array('page' => 'wishlist', 'category' => urlencode($category->category)), admin_url('admin.php'))); ?>">
													<?php _e('View Items', 'personal-wishlist-manager'); ?>
												</a>
											</span>
										</div>
									</td>
									<td class="count column-count" data-colname="<?php _e('Count', 'personal-wishlist-manager'); ?>">
										<?php echo intval($category->count); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-name column-primary">
								<?php _e('Name', 'personal-wishlist-manager'); ?>
							</th>
							<th scope="col" class="manage-column column-count">
								<?php _e('Count', 'personal-wishlist-manager'); ?>
							</th>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</div>
