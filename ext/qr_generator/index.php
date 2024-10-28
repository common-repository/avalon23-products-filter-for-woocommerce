<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
class Avalon23_QR_Generator {
	private $api_link = '';
	private $options = array();
	public function __construct() {
		//https://chart.apis.google.com/chart?cht=qr&chs=300x300&chl=https://pluginus.net/support/
		//https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://pluginus.net/support/
		$this->api_link = 'https://api.qrserver.com/v1/create-qr-code/?';
		add_shortcode('avalon23_qr', array($this, 'avalon23_qr'));
		add_action('wp_enqueue_scripts', array($this, 'add_js'));
		
		add_filter('avalon23_extend_filter_fields', array($this, 'add_custom_field'), 99, 2);
		add_filter('avalon23_fields_options_row_extend', array($this, 'settings_options'), 20, 2);
		
		$this->options = array('qr_title', 'qr_size');
	}
	public function add_js() {
		wp_enqueue_script('avalon23-qr-generator', $this->get_link() . 'js/av23-qr-generator.js', array('avalon23-filter'), AVALON23_VERSION);
		
	}
	public function add_filter_type( $filters ) {
		$filters['qr_generator'] = esc_html__('QR of current search', 'avalon23-products-filter');
		return $filters;
	}
	public function add_custom_field( $available_fields, $filter_id ) {
			$current_key = 'qr_generator';
			$available_fields[$current_key] = [
				'title' => esc_html__('QR of current search', 'avalon23-products-filter'),
				'view' => 'html',
				'optgroup' => esc_html__('Functions', 'avalon23-products-filter'),
				'options' => $this->options,
				'get_draw_data' => function( $filter_id ) use ( $current_key ) {
					$data['title'] = avalon23()->filter->options->get_option($filter_id, $current_key, "{$current_key}-qr_title");
					$data['size'] = avalon23()->filter->options->get_option($filter_id, $current_key, "{$current_key}-qr_size");
				
					$res['view']= 'html';
					$res['html']= base64_encode ($this->avalon23_qr($data));
					$res['width_sm'] = avalon23()->filter_items->get_by_field_key($filter_id, $current_key)['width_sm'];
					$res['width_md'] = avalon23()->filter_items->get_by_field_key($filter_id, $current_key)['width_md'];
					$res['width_lg'] = avalon23()->filter_items->get_by_field_key($filter_id, $current_key)['width_lg'];
					return $res;
				},                   
				'get_query_args' => function( $args, $value ) {     
					return $args;
				}
			];			
			
		return $available_fields;
		
	}	
	public function get_link() {
		return plugin_dir_url(__FILE__);
	}
	public function settings_options( $row, $args ) {
		if (isset($args['option']) && in_array($args['option'], $this->options)) {
			$col = avalon23()->filter_items->get($args['field_id'], ['field_key', 'options']);
			switch ($args['option']) {
				case 'qr_title':
				case 'qr_size':
				//case 'qr_width':	
					$key = $args['field_key'] . '-' . $args['option'];
					$val = avalon23()->filter_items->options->field_options->extract_from( $col['options'], $key);
					if (!$val && 'qr_size' == $args['option']) {
						$val = 100;
					}

					$input = AVALON23_HELPER::draw_html_item('input', [
								'class' => 'avalon23-filter-field-option avalon23-qr-input',
								'type' => 'text',
								'value' => $val,
								'data-table-id' => $args['filter_id'],
								'data-key' => $key,
								'data-field-id' => $args['field_id'],
					]);
					$title = esc_html__('Title', 'avalon23-products-filter');
					$notes = esc_html__('Title at the top of the QR code.', 'avalon23-products-filter');					
					if ('qr_size' == $args['option']) {
						$title = esc_html__('Size', 'avalon23-products-filter');
						$notes = esc_html__('Side size of QR code square in px', 'avalon23-products-filter');
					}
					
					$row = [
						'pid' => 0,
						'title' => $title,
						'value' => $input,
						'notes' => $notes,
					];					
					break;
			}
		}
		return $row;
	}	
	public function avalon23_qr( $args ) {

		$args = shortcode_atts( array(
			'size' => 100,
			'title' => esc_html__('Search link', 'avalon23-products-filter'),
			'fixed_link' => ''
		), $args );		
		$args['size'] = (int) ( $args['size'] )? $args['size']: 100;

		$data = array(
			'data-size' => $args['size'] . 'x' . $args['size'],
			'data-link' => $this->api_link,
			'class' => 'avalon23-qr-generator-item',
			'data-title' => $args['title'],
			'data-prev-link' => '',
			'data-fixed-link' => $args['fixed_link'],
		);
		return AVALON23_HELPER::draw_html_item('div', $data, ' ');	
	}
}

new Avalon23_QR_Generator();
