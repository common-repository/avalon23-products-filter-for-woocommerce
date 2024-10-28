<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Custom_Html {

	private $field_key = 'custom_html_';
	public $field_count = 5;

	public function __construct() {
		add_filter('avalon23_extend_filter_fields', array($this, 'add_custom_field'), 99, 2);
		add_action('avalon23_extend_settings', array($this, 'add_settings'), 99);
		$this->field_count = ( Avalon23_Settings::get('custom_text_count') != -1 ) ? (int) Avalon23_Settings::get('custom_text_count') : 5;
	}

	public function get_link() {
		return plugin_dir_url(__FILE__);
	}

	public function get_path() {
		return plugin_dir_path(__FILE__);
	}
	public function add_custom_field( $available_fields, $filter_id ) {
		$current_key = '';
		for ($i = 0; $i < $this->field_count; $i++ ) {
			$current_key = $this->field_key . $i;
			
			$txt = $i + 1;
			$available_fields[$current_key] = [
				'title' => esc_html__('Text field #', 'woocommerce-filter') . $txt,
				'view' => 'html',
				'optgroup' => esc_html__('Custom text', 'woocommerce-filter'),
				'options' => ['text_field'],
				'get_draw_data' => function( $filter_id ) use ( $current_key ) {
					$val = avalon23()->filter->options->get_option($filter_id, $current_key, "{$current_key}-text_field");
				
					$res['view']= 'html';
					$res['html']= base64_encode ($this->get_html($val));
					$res['width_sm'] = avalon23()->filter_items->get_by_field_key($filter_id, $current_key)['width_sm'];
					$res['width_md'] = avalon23()->filter_items->get_by_field_key($filter_id, $current_key)['width_md'];
					$res['width_lg'] = avalon23()->filter_items->get_by_field_key($filter_id, $current_key)['width_lg'];
					return $res;
				},                   
				'get_query_args' => function( $args, $value ) {     
					return $args;
				}
			];			
			
			
		}
		return $available_fields;
		
	}
	public function add_settings( $rows ) {
		$custom_text_settings = array();

		$text_count = ( Avalon23_Settings::get('custom_text_count') != -1 ) ? ( int ) Avalon23_Settings::get('custom_text_count') : '';
		$custom_text_settings = [
			[
				'title' => esc_html__('Number of custom text', 'avalon23-products-filter'),
				'value' => [
						'value' => $text_count,
						'custom_field_key' => 'custom_text_count'
					],
				'notes' => esc_html__('The number of custom text fields that will be displayed for each filter. By default 5', 'avalon23-products-filter')
			]
		];		
		return array_merge($rows, $custom_text_settings);
	}	
	public function get_html( $value = '' ) {

		$allowedpost = wp_kses_allowed_html('post');
		$allowedpost['iframe'] = array(
			'align' => true,
			'frameborder' => true,
			'height' => true,
			'width' => true,
			'sandbox' => true,
			'seamless' => true,
			'scrolling' => true,
			'srcdoc' => true,
			'src' => true,
			'class' => true,
			'id' => true,
			'style' => true,
			'border' => true,
		);

		$value = wp_kses($value, $allowedpost);		
		
		return do_shortcode($value);
		
	}
	

}

new Avalon23_Custom_Html();
