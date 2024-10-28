<?php
if (!defined('ABSPATH')) {
	die('No direct access allowed');
}

if (!isset($filter_id)) {
	$filter_id = 0;
}

if (!isset($classes)) {
	$classes = '';
}

if (!isset($hide_filter_form)) {
	$hide_filter_form = false;
}

if (!isset($published)) {
	$published = true;
}

?>

<?php if (boolval($published) ) : ?>

	<?php do_action('avalon23_before_filter_draw', $shortcode_args); ?>

	<div class='avalon23-filter <?php echo esc_attr($classes); ?>' id='<?php echo esc_attr($filter_html_id); ?>' data-version="<?php echo esc_attr(AVALON23_VERSION); ?>" >
		<?php if (isset($filter) && !empty($filter)) : ?>
		<div class="avalon23-filter-data" data-filter_id="<?php echo esc_attr($filter_id); ?>" style="display: none;"><?php echo esc_textarea($filter); ?></div>
			<div class="avalon23-filter-list  <?php echo ( $hide_filter_form ) ? esc_attr('avalon23-hidden') : ''; ?>" bp="grid" ></div>
		<?php endif; ?>

		<div class="avalon23-clearfix"></div>

	</div>

	<?php do_action('avalon23_after_filter_draw', $shortcode_args); ?>

<?php else : ?>

	<div class="avalon23-notice">
		<strong>
		<?php echo esc_html(apply_filters('avalon23_filter_text_not_active', esc_html__('Filter is not active! id:', 'avalon23-products-filter') . esc_attr($filter_id), $filter_id) ); ?>
		</strong>
	</div>

<?php endif; ?>
