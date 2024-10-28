<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Compatibility_ACF {
	
	public $meta_fields = array();
	public $possible_types = array();
	public $possible_terms = array();
	public function __construct() {
		
		$this->possible_types = array(
			'select' => 'text',
			'text' => 'text',
			'number' => 'number',
			'button_group' => 'text',
			'range' => 'number',
			'true_false' => 'text',
			'radio' => 'text',
			'textarea' => 'text',
			'date_time_picker' => 'calendar',
		);

		$this->meta_fields  = $this->get_all_meta();

		add_filter('avalon23_get_all_meta', array( $this, 'add_meta'), 10, 2);
		
		add_filter('avalon23_get_meta_options', array( $this, 'add_meta_terms'), 10, 3);
		
		
	}
	public function add_meta( $metas, $filter_id ) {
		
		foreach ($this->meta_fields as $acf_meta) {
			$add_meta = true;
			foreach ($metas as $meta) {
				if ($meta['meta_key'] == $acf_meta['meta_key']) {
					$add_meta = false;
					continue;
				}
			}
			if ($add_meta) {
				$title = $acf_meta['title'];
				if (is_admin()) {
					$title .= '(ACF:' . $acf_meta['group_name'] . ')';
				}

				$metas[] = array(
					'title' => $title ,
					'meta_key' => $acf_meta['meta_key'],
					'meta_type' => $acf_meta['meta_type'],
					'notes' => $acf_meta['meta_options']
				);
				$this->possible_terms[$filter_id][$acf_meta['meta_key']] = $acf_meta['meta_options'];
			}
			
		}	
	
				
		return $metas;
	}
	public function add_meta_terms( $meta_optios, $meta_key, $filter_id ) {
		
		if ( isset($this->possible_terms[$filter_id]) && isset($this->possible_terms[$filter_id][$meta_key]) ) {
			$meta_optios = $this->possible_terms[$filter_id][$meta_key];
		}
		
		return $meta_optios;		
	}
	public function product_droup( $group ) {
		$show = false;
		if (!isset($group['location'])) {
			return true;
		}
		foreach ($group['location'] as $rule) {
			if ('product' == $rule[0]['value'] && '==' == $rule[0]['operator']) {
				$show = true;
			}
		}

		return $show;
	}

	public function get_all_meta() {

		$acf = acf_get_field_groups();

		$fields = array();
		$meta = array();

		foreach ($acf as $item) {
			if (!$this->product_droup($item)) {
				continue;
			}
			//$fields = acf_get_fields($item);
			$fields = acf_get_raw_fields( $item['ID'] );
			$group_name = $item['title'];
			foreach ($fields as $field) {
				$type = '';
				if (isset($this->possible_types[$field['type']])) {
					$type = $this->possible_types[$field['type']];
				} else {
					continue;
				}

				$options = array();
				if (isset($field['choices'])) {
					$options = $field['choices'];
					foreach ($options as $o_key => $o_val) {
						$options[$o_key] = Avalon23_Vocabulary::get($o_val);
						$options[$o_key] = str_replace("'", '&prime;', $options[$o_key]);
						$options[$o_key] = str_replace('"', '&Prime;', $options[$o_key]);
						
					}
				}
				if ('true_false' == $field['type']) {
					$options = array(
						1 => Avalon23_Vocabulary::get(esc_html__('Yes', 'avalon23-products-filter'))
					);
				}
				$t_m = Avalon23_Vocabulary::get($field['label']);
				$t_m = str_replace("'", '&prime;', $t_m);
				$t_m = str_replace('"', '&Prime;', $t_m);				
				
				$meta[$field['name']] = array(
					'meta_key' => $field['name'],
					'title' => $t_m,
					'meta_type' => $type,
					'meta_options' => $options,
					'group_name' => $group_name
				);
			}
		}

		return $meta;
	}

}

