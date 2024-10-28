<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Mobile_Behavior {

	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'add_js'));
		add_action('avalon23_extend_options', array($this, 'add_options'), 999, 2);

		add_filter('avalon23_filter_redraw_data', array($this, 'add_filter_data'), 99);
		//admin scripts
		add_action('admin_enqueue_scripts', array($this, 'js_css_enqueue'));
	}

	public function add_js() {
		wp_enqueue_script('avalon23-mobile-behavior', $this->get_link() . 'js/av23-mobile-behavior.js', array('avalon23-filter'), AVALON23_VERSION);
	}

	public function get_link() {
		return plugin_dir_url(__FILE__);
	}

	public function js_css_enqueue() {
		wp_enqueue_script('av23-mobile-behavior-admin', $this->get_link() . '/js/admin.js', array(), AVALON23_VERSION);
	}

	public function add_options( $rows, $filter_id) {


		$uploader_data = array(
			'href' => 'javasctipt: void(0);',
			'onclick' => 'return avalon23_change_side_filter_image(this);',
			'class' => 'avalon23_override_field_type',
			'data-field-type-override' => 'image',
			'data-name' => 'side_img',
			'data-post_id' => $filter_id
		);
		$uploader_delete_data = array(
			'href' => 'javasctipt: void(0);',
			'onclick' => 'return avalon23_delete_side_filter_image(this);',
			'data-name' => 'side_img',
			'data-post_id' => $filter_id
		);
		$img_id = avalon23()->filter_items->options->get($filter_id, 'side_img', 0);
		$img_settings = [
			'id' => $filter_id,
			'title' => esc_html__('Side image(Mobile behavior)', 'avalon23-products-filter'),
			'value' => AVALON23_HELPER::draw_image_uploader($img_id, $uploader_data, $uploader_delete_data),
			'value_custom_field_key' => 'side_img',
			'notes' => esc_html__('Filter icon, when clicked, a sidebar appears', 'avalon23-products-filter')
		];

		return array_merge($rows, [
			[
				'id' => $filter_id,
				'title' => esc_html__('Display type(Mobile behavior)', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_select([
					'style' => 'width: 100%'
						],
						[
					'left_sidebar' => esc_html__('Left sidebar', 'avalon23-products-filter'),
					'right_sidebar' => esc_html__('Right sidebar', 'avalon23-products-filter'),
					'content' => esc_html__('Content(selector)', 'avalon23-products-filter'),
						], avalon23()->filter_items->options->get($filter_id, 'mobile_display_type', 'sidebar')),
				'value_custom_field_key' => 'mobile_display_type',
				'notes' => esc_html__('How the filter should be displayed for mobile devices.Sidebar - the filter is displayed in the left/right popup sidebar. Content - the filter is wrapped in the selector container (text box below)', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Min client width(Mobile behavior)', 'avalon23-products-filter'),
				'value' => avalon23()->filter_items->options->get($filter_id, 'min_client_width', ''),
				'value_custom_field_key' => 'min_client_width',
				'notes' => esc_html__('If the user′s screen width is smaller then this mobile behavior is triggered', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Selector(Mobile behavior)', 'avalon23-products-filter'),
				'value' => avalon23()->filter_items->options->get($filter_id, 'mob_behavior_selector', ''),
				'value_custom_field_key' => 'mob_behavior_selector',
				'notes' => esc_html__('Container selector in which the filter or sidebar open button(depends on the display type) is displayed.', 'avalon23-products-filter'),
			],
			$img_settings
		]);
	}

	public function add_filter_data( $data) {

		if (( isset($data['filter_options']) && is_array($data['filter_options']) ) && isset($data['filter_options']['filter_id'])) {
			$data['filter_options']['side_img_url'] = AVALON23_ASSETS_LINK . 'img/side_filter.png';
			if (isset($data['filter_options']['side_img'])) {
				$img_src = wp_get_attachment_image_src($data['filter_options']['side_img'], 'thumbnail');
				if (is_array($img_src) && !empty($img_src[0])) {
					$data['filter_options']['side_img_url'] = $img_src[0];
				}
			}
		}
		return $data;
	}

}

new Avalon23_Mobile_Behavior();

