<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
//works all except of example fields
add_action('avalon23_extend_settings', function( $rows ) {
	return array_merge($rows, [
		[
			'title' => esc_html__('Show button in admin bar', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_switcher('show_btn_in_admin_bar', Avalon23_Settings::get('show_btn_in_admin_bar'), 0, 'avalon23_save_settings_field'),
			'notes' => esc_html__('Enable/Disable button in top admin bar. Button will appear/disappear after the page next loading!', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Filter prefix', 'avalon23-products-filter'),
			'value' => [
				'value' => Avalon23_Settings::get('filter_prefix'),
				'custom_field_key' => 'filter_prefix'
			],
			'notes' => esc_html__('Filter prefix,. By default: -1 is  av', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Product container', 'avalon23-products-filter'),
			'value' => [
				'value' => ( Avalon23_Settings::get('product_container') != -1 ) ? Avalon23_Settings::get('product_container') : '.products',
				'custom_field_key' => 'product_container'
			],
			'notes' => esc_html__('Product container  for ajax mode', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Pagination container', 'avalon23-products-filter'),
			'value' => [
				'value' => ( Avalon23_Settings::get('pagination_container') != -1 ) ? Avalon23_Settings::get('pagination_container') : 'nav.woocommerce-pagination',
				'custom_field_key' => 'pagination_container'
			],
			'notes' => esc_html__('Pagination container  for ajax mode', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Count container', 'avalon23-products-filter'),
			'value' => [
				'value' => ( Avalon23_Settings::get('count_container') != -1 ) ? Avalon23_Settings::get('count_container') : '.woocommerce-result-count',
				'custom_field_key' => 'count_container'
			],
			'notes' => esc_html__('Count container  for ajax mode', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('No products found container', 'avalon23-products-filter'),
			'value' => [
				'value' => ( Avalon23_Settings::get('no_products_found_container') != -1 ) ? Avalon23_Settings::get('no_products_found_container') : '.woocommerce-info',
				'custom_field_key' => 'no_products_found_container'
			],
			'notes' => esc_html__('No products found container for ajax mode', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Filter navigation container', 'avalon23-products-filter'),
			'value' => [
				'value' => ( Avalon23_Settings::get('filter_navigation_container') != -1 ) ? Avalon23_Settings::get('filter_navigation_container') : '',
				'custom_field_key' => 'filter_navigation_container'
			],
			'notes' => esc_html__('Add filter  navigation  after this  container', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Languages', 'avalon23-products-filter'),
			'value' => [
				'value' => Avalon23_Settings::get('languages'),
				'custom_field_key' => 'languages'
			],
			'notes' => esc_html__('Languages for vocabulary. Using comma add languages you want to use on the site front. By default: -1. Example: en_US,fr_FR,es_ES,de_DE,ru_RU. After changing this field vocabulary settings appears will be after the page reloading.', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Wipe all data while uninstall', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_switcher('delete_db_tables', Avalon23_Settings::get('delete_db_tables'), 0, 'avalon23_save_settings_field'),
			'notes' => esc_html__('Enable this ONLY if you not going to update or reinstall in future as all tables will be removed if you will apply operation of the plugin uninstalling!', 'avalon23-products-filter')
		],
		[
			'title' => esc_html__('Do after Ajax', 'avalon23-products-filter'),
			'value' => [
				'value' => ( Avalon23_Settings::get('do_after_ajax') != -1 ) ? stripcslashes(Avalon23_Settings::get('do_after_ajax')) : '',
				'custom_field_key' => 'do_after_ajax'
			],
			'notes' => esc_html__('JS code will be executed after ajax page redrawing. This is useful for adapting third party templates', 'avalon23-products-filter')
		],
	]);
});
