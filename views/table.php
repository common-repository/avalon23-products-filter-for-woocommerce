<?php
if (!defined('ABSPATH')) {
	wp_die('No direct access allowed');
}	
if (!isset($action)) {
	$action_table = '';
} else {
	$action_table = $action;
}

if (!isset($filter_id)) {
	$filter_id = 0;
}

if (!isset($classes)) {
	$classes = '';
}

if (!isset($search_data_key)) {
	$search_data_key = 'text_search';
}

if (!isset($text_search_min_symbols)) {
	$text_search_min_symbols = '';
}

if (!isset($placeholder)) {
	$placeholder = '';
}

if (!isset($hide_filter_form)) {
	$hide_filter_form = false;
}

if (!isset($has_filter)) {
	$has_filter = false;
}

if (!isset($cart_position)) {
	$cart_position = 0;
} else {
	if ('left' === $cart_position) {
		$cart_position = 1;
	}
}

if (!isset($orderby_select)) {
	$orderby_select = '';
}

if (!isset($published)) {
	$published = true;
}
?>

<?php if (boolval($published) ) : ?>

	<div class='avalon23-data-table <?php echo esc_attr($action_table); ?> <?php echo esc_attr($classes); ?>' id='<?php echo esc_attr($table_html_id); ?>'>
		<input type="search" data-key="<?php echo esc_attr($search_data_key); ?>" value="" minlength="<?php echo esc_attr($text_search_min_symbols); ?>" class="avalon23-text-search" <?php echo ( $hide_text_search ) ? "style='display: none;'" : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>" />


		<?php if (isset($filter) && ! empty($filter) ) : ?>
			<div class="avalon23-filter-data" style="display: none;"><?php echo esc_attr($filter); ?></div>

			<?php
			if ($has_filter) :
				?>
				<?php
				if ($hide_filter_form) {
					?>
			<a class="avalon23-btn avalon23-filter-show-btn" onclick="<?php echo esc_js('javascript: avalon23_show_filter(this);void(0);'); ?>" href="<?php echo esc_js('javascript: void(0);'); ?>" >
				<span class="dashicons-before dashicons-filter"></span>
				<?php esc_html_e('show', 'avalon23-products-tables'); ?>
			</a>
				<?php
				}
				?>
				<div class="avalon23-filter-list  <?php echo ( $hide_filter_form ) ? esc_attr('avalon23-hidden') : ''; ?>"></div>
			<?php endif; ?>

			<div class="avalon23-clearfix"></div>
		<?php endif; ?>

		<div class="avalon23-order-select-zone" <?php if (1 === $cart_position) { ?>
			 style="float:right;"
				<?php } ?> 
			 >
			<?php
			if (!empty($orderby_select) ) :
				$first_option = [0 => esc_html__('Sorted by table', 'avalon23-products-tables')];
				$orderby_select = array_merge($first_option, $orderby_select);
				?>
				<div class="avalon23-order-select" style="display: none;"><?php echo json_encode($orderby_select); ?></div>
			<?php endif; ?>
		</div>  

		<div class="avalon23-woocommerce-cart-zone" <?php echo ( 1 === $cart_position ) ? "style='float:left;'" : ''; ?>></div>

		<div class="avalon23-clearfix"></div>

		<div class="avalon23-place-loader"><?php echo esc_html(Avalon23_Vocabulary::get(esc_html__('Loading ...', 'avalon23-products-tables'))); ?></div>
		<table class="avalon23-table"></table>

	</div>

<?php else : ?>

	<div class="avalon23-notice">
		<strong>
			<?php		
			esc_html_e('Table is not active!', 'avalon23-products-tables'); 
			?>
		</strong>
	</div>

<?php endif; ?>
