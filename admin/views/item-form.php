<?php
/**
 * Admin view for add/edit item form - Redesigned
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
$is_edit = !empty($item);

// Get existing categories for dropdown
$categories = pwm_get_categories();

// Set default values
$title = $item ? $item->title : '';
$category = $item ? $item->category : '';
$image_url = $item ? $item->image_url : '';
$product_url = $item ? $item->product_url : '';
$price = $item ? $item->price : '';
$reason = $item ? $item->reason : '';
?>

<div class="wrap pwm-form-wrap">
	<h1><?php echo $is_edit ? __('Edit Wishlist Item', 'personal-wishlist-manager') : __('Add New Wishlist Item', 'personal-wishlist-manager'); ?></h1>

	<form method="post" action="" id="pwm-item-form" class="pwm-modern-form">
		<?php wp_nonce_field('pwm_save_item', 'pwm_nonce'); ?>

		<div class="pwm-form-container">
			<!-- Main Form Fields -->
			<div class="pwm-form-main">
				<div class="pwm-form-card">
					<h2><span class="dashicons dashicons-edit"></span> <?php _e('Item Details', 'personal-wishlist-manager'); ?></h2>

					<div class="pwm-form-row">
						<label for="title" class="pwm-label">
							<?php _e('Item Title', 'personal-wishlist-manager'); ?>
							<span class="required">*</span>
						</label>
						<input type="text"
							   id="title"
							   name="title"
							   value="<?php echo esc_attr($title); ?>"
							   class="pwm-input pwm-input-large"
							   placeholder="<?php _e('e.g., Wireless Noise-Cancelling Headphones', 'personal-wishlist-manager'); ?>"
							   required>
					</div>

					<div class="pwm-form-row pwm-form-row-split">
						<div class="pwm-form-col">
							<label for="category" class="pwm-label">
								<?php _e('Category', 'personal-wishlist-manager'); ?>
								<span class="required">*</span>
							</label>
							<select id="category" name="category" class="pwm-select" required>
								<option value=""><?php _e('Select a category...', 'personal-wishlist-manager'); ?></option>
								<?php foreach ($categories as $cat) : ?>
									<option value="<?php echo esc_attr($cat->category); ?>" <?php selected($category, $cat->category); ?>>
										<?php echo esc_html($cat->category); ?> (<?php echo intval($cat->count); ?>)
									</option>
								<?php endforeach; ?>
								<option value="__new__" class="pwm-new-option"><?php _e('+ Add New Category', 'personal-wishlist-manager'); ?></option>
							</select>
							<div id="new-category-field" class="pwm-new-field" style="display: none;">
								<input type="text"
									   id="new-category"
									   placeholder="<?php _e('Enter new category name', 'personal-wishlist-manager'); ?>"
									   class="pwm-input">
							</div>
						</div>

						<div class="pwm-form-col">
							<label for="price" class="pwm-label">
								<?php _e('Price', 'personal-wishlist-manager'); ?>
								<span class="required">*</span>
							</label>
							<div class="pwm-price-input">
								<span class="pwm-currency"><?php echo esc_html(get_option('pwm_currency_symbol', '$')); ?></span>
								<input type="number"
									   id="price"
									   name="price"
									   value="<?php echo esc_attr($price); ?>"
									   class="pwm-input"
									   step="0.01"
									   min="0.01"
									   placeholder="0.00"
									   required>
							</div>
						</div>
					</div>

				</div>

				<div class="pwm-form-card">
					<h2><span class="dashicons dashicons-format-image"></span> <?php _e('Product Image', 'personal-wishlist-manager'); ?></h2>

					<div class="pwm-form-row">
						<label for="image_url" class="pwm-label">
							<?php _e('Image URL', 'personal-wishlist-manager'); ?>
							<span class="required">*</span>
						</label>
						<div class="pwm-image-upload-wrapper">
							<input type="url"
								   id="image_url"
								   name="image_url"
								   value="<?php echo esc_url($image_url); ?>"
								   class="pwm-input pwm-input-large"
								   placeholder="https://example.com/image.jpg"
								   required>
							<button type="button" class="pwm-button pwm-button-secondary pwm-upload-image">
								<span class="dashicons dashicons-format-image"></span>
								<?php _e('Choose Image', 'personal-wishlist-manager'); ?>
							</button>
						</div>
						<input type="hidden" id="image_id" name="image_id" value="">
					</div>

					<div id="pwm-image-preview" class="pwm-image-preview">
						<?php if ($image_url) : ?>
							<img src="<?php echo esc_url($image_url); ?>" alt="<?php _e('Preview', 'personal-wishlist-manager'); ?>">
							<button type="button" class="pwm-remove-image" title="<?php _e('Remove image', 'personal-wishlist-manager'); ?>">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						<?php else : ?>
							<div class="pwm-image-placeholder">
								<span class="dashicons dashicons-format-image"></span>
								<p><?php _e('Image preview will appear here', 'personal-wishlist-manager'); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="pwm-form-card">
					<h2><span class="dashicons dashicons-admin-links"></span> <?php _e('Product Link', 'personal-wishlist-manager'); ?></h2>

					<div class="pwm-form-row">
						<label for="product_url" class="pwm-label">
							<?php _e('Product URL', 'personal-wishlist-manager'); ?>
							<span class="required">*</span>
						</label>
						<input type="url"
							   id="product_url"
							   name="product_url"
							   value="<?php echo esc_url($product_url); ?>"
							   class="pwm-input pwm-input-large"
							   placeholder="https://example.com/product"
							   required>
						<p class="pwm-field-description"><?php _e('Link to where this item can be purchased', 'personal-wishlist-manager'); ?></p>
					</div>
				</div>

				<div class="pwm-form-card">
					<h2><span class="dashicons dashicons-text-page"></span> <?php _e('Reason & Notes', 'personal-wishlist-manager'); ?></h2>

					<div class="pwm-form-row">
						<label for="reason" class="pwm-label">
							<?php _e('Why do you want this item?', 'personal-wishlist-manager'); ?>
							<span class="pwm-label-hint"><?php _e('(Optional)', 'personal-wishlist-manager'); ?></span>
						</label>
						<textarea id="reason"
								  name="reason"
								  rows="6"
								  class="pwm-textarea"
								  placeholder="<?php _e('Add any notes, reasons, or specifications...', 'personal-wishlist-manager'); ?>"><?php echo esc_textarea($reason); ?></textarea>
					</div>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="pwm-form-sidebar">
				<div class="pwm-form-card pwm-sticky-card">
					<h2><?php echo $is_edit ? __('Update Item', 'personal-wishlist-manager') : __('Publish Item', 'personal-wishlist-manager'); ?></h2>

					<div class="pwm-form-actions">
						<button type="submit" name="pwm_save_item" class="pwm-button pwm-button-primary pwm-button-large">
							<span class="dashicons dashicons-yes"></span>
							<?php echo $is_edit ? __('Update Item', 'personal-wishlist-manager') : __('Add to Wishlist', 'personal-wishlist-manager'); ?>
						</button>

						<a href="<?php echo esc_url(add_query_arg('page', 'wishlist', admin_url('admin.php'))); ?>" class="pwm-button pwm-button-secondary pwm-button-large">
							<span class="dashicons dashicons-no-alt"></span>
							<?php _e('Cancel', 'personal-wishlist-manager'); ?>
						</a>

						<?php if ($is_edit) : ?>
							<hr class="pwm-divider">
							<a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'wishlist', 'action' => 'delete', 'item' => $item_id), admin_url('admin.php')), 'pwm_delete_item_' . $item_id)); ?>"
							   class="pwm-button pwm-button-danger pwm-button-large pwm-delete-item">
								<span class="dashicons dashicons-trash"></span>
								<?php _e('Delete Item', 'personal-wishlist-manager'); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php if ($is_edit && $item) : ?>
					<div class="pwm-form-meta">
						<div class="pwm-meta-item">
							<span class="pwm-meta-label"><?php _e('Created:', 'personal-wishlist-manager'); ?></span>
							<span class="pwm-meta-value"><?php echo date_i18n(get_option('date_format'), strtotime($item->created_at)); ?></span>
						</div>
						<div class="pwm-meta-item">
							<span class="pwm-meta-label"><?php _e('Last Updated:', 'personal-wishlist-manager'); ?></span>
							<span class="pwm-meta-value"><?php echo date_i18n(get_option('date_format'), strtotime($item->updated_at)); ?></span>
						</div>
					</div>
					<?php endif; ?>

					<div class="pwm-form-help">
						<h3><?php _e('Quick Tips', 'personal-wishlist-manager'); ?></h3>
						<ul>
							<li><?php _e('Use descriptive titles for easy searching', 'personal-wishlist-manager'); ?></li>
							<li><?php _e('Include product link for quick access', 'personal-wishlist-manager'); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<style>
.pwm-form-wrap {
	background: #f0f0f1;
	margin: 20px 20px 20px 0;
	padding: 20px;
}

.pwm-modern-form {
	background: transparent;
}

.pwm-form-container {
	display: grid;
	grid-template-columns: 1fr 340px;
	gap: 20px;
	margin-top: 20px;
}

.pwm-form-main {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.pwm-form-card {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	padding: 24px;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.pwm-form-card h2 {
	margin: 0 0 20px 0;
	font-size: 16px;
	font-weight: 600;
	color: #1d2327;
	display: flex;
	align-items: center;
	gap: 8px;
}

.pwm-form-card h2 .dashicons {
	color: #2271b1;
	font-size: 20px;
	width: 20px;
	height: 20px;
}

.pwm-form-row {
	margin-bottom: 20px;
}

.pwm-form-row:last-child {
	margin-bottom: 0;
}

.pwm-form-row-split {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
}

.pwm-label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: #1d2327;
	font-size: 14px;
}

.pwm-label .required {
	color: #d63638;
}

.pwm-label-hint {
	font-weight: 400;
	color: #646970;
	font-size: 13px;
}

.pwm-input,
.pwm-select,
.pwm-textarea {
	width: 100%;
	padding: 10px 14px;
	border: 1px solid #8c8f94;
	border-radius: 4px;
	font-size: 14px;
	line-height: 1.5;
	transition: border-color 0.2s, box-shadow 0.2s;
}

.pwm-input:focus,
.pwm-select:focus,
.pwm-textarea:focus {
	border-color: #2271b1;
	box-shadow: 0 0 0 1px #2271b1;
	outline: none;
}

.pwm-input-large {
	font-size: 15px;
}

.pwm-textarea {
	resize: vertical;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.pwm-field-description {
	margin: 6px 0 0 0;
	font-size: 13px;
	color: #646970;
	font-style: italic;
}

.pwm-price-input {
	position: relative;
	display: flex;
	align-items: center;
}

.pwm-currency {
	position: absolute;
	left: 14px;
	font-weight: 600;
	color: #50575e;
	pointer-events: none;
}

.pwm-price-input .pwm-input {
	padding-left: 32px;
}

.pwm-new-field {
	margin-top: 10px;
}

.pwm-new-option {
	color: #2271b1;
	font-weight: 600;
}

.pwm-image-upload-wrapper {
	display: flex;
	gap: 10px;
}

.pwm-image-upload-wrapper .pwm-input {
	flex: 1;
}

.pwm-image-preview {
	margin-top: 16px;
	border: 2px dashed #c3c4c7;
	border-radius: 8px;
	padding: 20px;
	text-align: center;
	position: relative;
	background: #f6f7f7;
	max-width: 500px;
}

.pwm-image-preview img {
	width: 100%;
	height: 300px;
	object-fit: cover;
	border-radius: 4px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pwm-remove-image {
	position: absolute;
	top: 10px;
	right: 10px;
	background: #d63638;
	color: white;
	border: none;
	border-radius: 50%;
	width: 32px;
	height: 32px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: background 0.2s;
}

.pwm-remove-image:hover {
	background: #b32d2e;
}

.pwm-remove-image .dashicons {
	font-size: 20px;
	width: 20px;
	height: 20px;
}

.pwm-image-placeholder {
	padding: 40px 20px;
	color: #646970;
}

.pwm-image-placeholder .dashicons {
	font-size: 48px;
	width: 48px;
	height: 48px;
	color: #c3c4c7;
	margin-bottom: 10px;
}

.pwm-image-placeholder p {
	margin: 0;
	font-size: 14px;
}

.pwm-form-sidebar {
	position: relative;
}

.pwm-sticky-card {
	position: sticky;
	top: 32px;
}

.pwm-form-actions {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.pwm-button {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	padding: 10px 16px;
	border: none;
	border-radius: 4px;
	font-size: 14px;
	font-weight: 500;
	cursor: pointer;
	transition: all 0.2s;
	text-decoration: none;
	text-align: center;
}

.pwm-button .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

.pwm-button-large {
	padding: 12px 20px;
	font-size: 15px;
}

.pwm-button-primary {
	background: #2271b1;
	color: white;
	border: 1px solid #2271b1;
}

.pwm-button-primary:hover {
	background: #135e96;
	border-color: #135e96;
	color: white;
}

.pwm-button-secondary {
	background: white;
	color: #2271b1;
	border: 1px solid #2271b1;
}

.pwm-button-secondary:hover {
	background: #f0f0f1;
	color: #135e96;
	border-color: #135e96;
}

.pwm-button-danger {
	background: white;
	color: #d63638;
	border: 1px solid #d63638;
}

.pwm-button-danger:hover {
	background: #d63638;
	color: white;
	border-color: #b32d2e;
}

.pwm-divider {
	margin: 16px 0;
	border: none;
	border-top: 1px solid #dcdcde;
}

.pwm-form-meta {
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid #dcdcde;
}

.pwm-meta-item {
	display: flex;
	justify-content: space-between;
	padding: 8px 0;
	font-size: 13px;
}

.pwm-meta-label {
	font-weight: 600;
	color: #1d2327;
}

.pwm-meta-value {
	color: #646970;
}

.pwm-form-help {
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid #dcdcde;
}

.pwm-form-help h3 {
	margin: 0 0 12px 0;
	font-size: 13px;
	font-weight: 600;
	color: #1d2327;
}

.pwm-form-help ul {
	margin: 0;
	padding-left: 20px;
	font-size: 13px;
	color: #646970;
}

.pwm-form-help li {
	margin-bottom: 6px;
	line-height: 1.5;
}

@media (max-width: 1200px) {
	.pwm-form-container {
		grid-template-columns: 1fr;
	}

	.pwm-sticky-card {
		position: static;
	}
}

@media (max-width: 782px) {
	.pwm-form-row-split {
		grid-template-columns: 1fr;
	}

	.pwm-image-upload-wrapper {
		flex-direction: column;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Handle new category
	$('#category').on('change', function() {
		if ($(this).val() === '__new__') {
			$('#new-category-field').slideDown(200);
			$('#new-category').focus();
		} else {
			$('#new-category-field').slideUp(200);
		}
	});

	// Function to set new category value
	function setNewCategory() {
		var newCat = $('#new-category').val().trim();
		if (newCat) {
			$('#category option[value="__new__"]').prop('selected', false);

			var exists = false;
			$('#category option').each(function() {
				if ($(this).val() === newCat) {
					exists = true;
					$(this).prop('selected', true);
					return false;
				}
			});

			if (!exists) {
				$('#category option[value="__new__"]').before('<option value="' + newCat + '" selected>' + newCat + '</option>');
			}

			$('#new-category-field').slideUp(200);
			$('#new-category').val('');
		}
	}

	$('#new-category').on('blur', setNewCategory);
	$('#new-category').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			setNewCategory();
		}
	});

	// Handle form submission
	$('#pwm-item-form').on('submit', function(e) {
		if ($('#new-category-field').is(':visible') && $('#new-category').val().trim()) {
			e.preventDefault();
			setNewCategory();
			setTimeout(function() {
				$('#pwm-item-form').off('submit').submit();
			}, 100);
			return false;
		}

		if ($('#category').val() === '__new__') {
			e.preventDefault();
			alert('Please enter a category name or select an existing category.');
			$('#new-category').focus();
			return false;
		}
	});

	// Image preview handling
	function updateImagePreview(url) {
		if (url) {
			$('#pwm-image-preview').html(
				'<img src="' + url + '" alt="Preview">' +
				'<button type="button" class="pwm-remove-image" title="Remove image">' +
				'<span class="dashicons dashicons-no-alt"></span>' +
				'</button>'
			);
		} else {
			$('#pwm-image-preview').html(
				'<div class="pwm-image-placeholder">' +
				'<span class="dashicons dashicons-format-image"></span>' +
				'<p>Image preview will appear here</p>' +
				'</div>'
			);
		}
	}

	var imageTimeout;
	$('#image_url').on('input paste', function() {
		clearTimeout(imageTimeout);
		var url = $(this).val().trim();
		imageTimeout = setTimeout(function() {
			updateImagePreview(url);
		}, 500);
	});

	$(document).on('click', '.pwm-remove-image', function(e) {
		e.preventDefault();
		if (confirm('Remove this image?')) {
			$('#image_url').val('');
			$('#image_id').val('');
			updateImagePreview('');
		}
	});
});
</script>
