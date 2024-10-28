<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

add_action('avalon23_extend_options', function( $rows, $filter_id ) {
	return array_merge($rows, [
		[
			'id' => $filter_id,
			'title' => esc_html__('Filter mode', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_select([
				'style' => 'width: 100%'
					], [
				0 => esc_html__('redirect', 'avalon23-products-filter'),
				1 => esc_html__('ajax', 'avalon23-products-filter'),
					], avalon23()->filter_items->options->get($filter_id, 'ajax_mode', 'tb')),
			'value_custom_field_key' => 'ajax_mode',
			'notes' => esc_html__('This is the mode in which the current shortcode will run. Remember Ajax mode may not be compatible with third party plugins.', 'avalon23-products-filter'),
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Autosubmit mode', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_select([
				'style' => 'width: 100%'
					], [
				'no' => esc_html__('NO', 'avalon23-products-filter'),
				'yes' => esc_html__('YES', 'avalon23-products-filter'),
				'ajax_redraw' => esc_html__('Only form', 'avalon23-products-filter'),
					], avalon23()->filter_items->options->get($filter_id, 'autosubmit', 'no')),
			'value_custom_field_key' => 'autosubmit',
			'notes' => esc_html__('Setting the mode of operation of the form when choosing a filter. "Only form" - After each selection, the form will be redrawn, but for filtering, the user should click the filter button', 'avalon23-products-filter'),
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Show count', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_switcher('show_count', avalon23()->filter_items->options->get($filter_id, 'show_count', 1), $filter_id, 'avalon23_save_filter_item_option_field'),
			'value_custom_field_key' => 'show_count',
			'notes' => esc_html__('Shows how many products are there with the value of this filter', 'avalon23-products-filter')
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Dynamic recount', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_switcher('dynamic_recount', avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0), $filter_id, 'avalon23_save_filter_item_option_field'),
			'value_custom_field_key' => 'dynamic_recount',
			'notes' => esc_html__('Counter correction (actual value) in the context of the current search query', 'avalon23-products-filter')
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Hide empty terms', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::_draw_switcher('hide_empty_terms', avalon23()->filter_items->options->get($filter_id, 'hide_empty_terms', 0), $filter_id, 'avalon23_save_filter_item_option_field'),
			'value_custom_field_key' => 'hide_empty_terms',
			'notes' => '<span class="avalon23_free">' . esc_html__('Hides a filter element that has a count of zero(Only paid version)', 'avalon23-products-filter')  . '</span>'
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Filter/Reset button position', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_select([
				'style' => 'width: 100%',
				'disabled' => 'disabled'
					], [
				'b' => esc_html__('Bottom', 'avalon23-products-filter'),
				't' => esc_html__('Top', 'avalon23-products-filter'),
				'tb' => esc_html__('Top and Bottom', 'avalon23-products-filter'),
					], avalon23()->filter_items->options->get($filter_id, 'btn_position', 'b')),
			'value_custom_field_key' => 'btn_position',
			'notes' => '<span class="avalon23_free">' . esc_html__('Position of buttons in relation to the form filter', 'avalon23-products-filter')  . '</span>',
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Show filter Navigation', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_select([
				'style' => 'width: 100%'
					], [
				'n' => esc_html__('None', 'avalon23-products-filter'),
				'b' => esc_html__('Bottom', 'avalon23-products-filter'),
				't' => esc_html__('Top', 'avalon23-products-filter'),
					], avalon23()->filter_items->options->get($filter_id, 'filter_navigation', 'b')),
			'value_custom_field_key' => 'filter_navigation',
			'notes' => esc_html__('Position of filter navigation in relation to the form filter', 'avalon23-products-filter'),
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Filter custom text', 'avalon23-products-filter'),
			'value' => avalon23()->filter_items->options->get($filter_id, 'filter_text', ''),
			'value_custom_field_key' => 'filter_text',
			'notes' => esc_html__('You can use any text for the filter button', 'avalon23-products-filter'),
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Reset custom text', 'avalon23-products-filter'),
			'value' => avalon23()->filter_items->options->get($filter_id, 'reset_text', ''),
			'value_custom_field_key' => 'reset_text',
			'notes' => esc_html__('You can use any text for the reset button', 'avalon23-products-filter'),
		],
		[
			'id' => $filter_id,
			'title' => esc_html__('Toggle filter', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_select([
				'style' => 'width: 100%'
					], [
				'none' => esc_html__('None', 'avalon23-products-filter'),
				'hide' => esc_html__('Hide by default', 'avalon23-products-filter'),
				'show' => esc_html__('Show by default', 'avalon23-products-filter'),
				'hide_mobile' => esc_html__('Hide for mobile', 'avalon23-products-filter'),		
					], avalon23()->filter_items->options->get($filter_id, 'toggle_filter', 'none')),
			'value_custom_field_key' => 'toggle_filter',
			'notes' => esc_html__('Hide / Show filter form', 'avalon23-products-filter'),
		],		
		[
			'id' => $filter_id,
			'title' => esc_html__('Filter container custom HTML ID', 'avalon23-products-filter'),
			'value' => avalon23()->filter_items->options->get($filter_id, 'filter_html_id', ''),
			'value_custom_field_key' => 'filter_html_id',
			'notes' => esc_html__('Attach to filter container constant html id which you can use for targeted CSS customization. Remember that ID should be unique! If you not understand it - leave this field empty.', 'avalon23-products-filter'),
		],
	]);
}, 10, 2);
