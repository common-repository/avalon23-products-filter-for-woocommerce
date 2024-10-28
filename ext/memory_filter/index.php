<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Memory_Filter {
	protected $memory_key = 'avalon23_memory_filter_';
	protected $memory_filter_ids = array();


	public function __construct() {

		add_action('avalon23_extend_options', array($this, 'add_options'), 999, 2);

		add_filter('avalon23_request_get_data', array($this, 'check_request'), 99);
		add_filter('avalon23_after_parse_query_args', array($this, 'add_query'), 99, 3);
		add_filter('avalon23_filter_redraw_data', array($this, 'add_filter_data'), 99);
		//ajax
		add_action('wp_ajax_avalon23_filter_reset', array($this, 'reset_filter'));
		add_action('wp_ajax_nopriv_avalon23_filter_reset', array($this, 'reset_filter'));	
	}
	public function get_memory_filter_ids() {
		
		if ( !count($this->memory_filter_ids) ) {
			$ids = avalon23()->filters->get_ids();
			foreach ($ids as $item) {
				if (isset($item['id']) && $item['id']) {
					$is = avalon23()->filter_items->options->get($item['id'], 'is_memory_filter', 0);
					if ($is && -1 != $is) {
						$this->memory_filter_ids[] = (int) $item['id'];
					}

				}
			}
		}
		return $this->memory_filter_ids;
	}

	public function get_link() {
		return plugin_dir_url(__FILE__);
	}
	public function reset_filter() {
		if (isset($_POST['filter_id']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'avalon23-nonce') ) {
			$id = (int) $_POST['filter_id'];

			if ($this->is_memory_enabled( $id )) {
				$this->set_filter_data( $id );
			}
			
		}
		
	}
	public function check_request( $requests ) {
		if (!is_array($requests)) {
			return $requests;
		}

		foreach ($requests as $id => $request) {
			if (isset($request['filter_id']) && $this->is_memory_enabled( $request['filter_id'] ) ) {
				$this->set_filter_data( $request['filter_id'], $request );
			} 			
		}

		if (empty($request) && count($this->get_memory_filter_ids()) ) {
			$requests[0]['filter_id'] = -1;
		}
		
		return $requests;
	}
	public function is_memory_enabled( $id ) {
		$is_enabled = in_array($id, $this->get_memory_filter_ids());
		return $is_enabled;
	}
	public function set_filter_data( $id, $data = null ) {
		if (!WC()->session->has_session()) {
			WC()->session->set_customer_session_cookie( true );
		}
		if ($data) {
			WC()->session->set( $this->memory_key . $id , $data );
		} else {
			WC()->session->__unset( $this->memory_key . $id );
		}
	}
	public function get_filter_data( $id ) {
		if ( is_object(WC()->session) ) {
			$data = WC()->session->get( $this->memory_key . $id );
		} else {
			$data = array();
		}
		
		return $data;
	}
	public function add_query( $query_args, $requests, $loop_name) {
		if ('avalon23_memory' != $loop_name) {
			$loop_name = 'avalon23_memory';
			foreach ($this->get_memory_filter_ids() as $id) {
				$requests[$id] = $this->get_filter_data($id);
				if ( $requests[$id] ) {
					$query_args = avalon23()->filter->get_query( $query_args, $requests, $loop_name );
				}
			}
		}
		return $query_args;
	}
	public function add_filter_data( $data ) {
		
		if ( ( isset($data['filter_options']) && is_array($data['filter_options']) ) && isset($data['filter_options']['filter_id']) &&  $this->is_memory_enabled( $data['filter_options']['filter_id'] )) {
			$data['filter_options']['special_reset'] = 1;
			$filter_data = $this->get_filter_data($data['filter_options']['filter_id']);
			if ( $filter_data ) {
				$data['filter_options']['filter_data'] = $filter_data;
			}
		}
		return $data;
	}
	public  function add_options ( $rows, $filter_id ) {
		
		return array_merge($rows, [
			[
				'id' => $filter_id,
				'title' => esc_html__('Save filter', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher('is_memory_filter', avalon23()->filter_items->options->get($filter_id, 'is_memory_filter', 0), $filter_id, 'avalon23_save_filter_item_option_field'),
				'value_custom_field_key' => 'is_memory_filter',
				'notes' => esc_html__('when a user makes a filter, this search query is saved across all pages and lasts for several days. Until the user resets the filter', 'avalon23-products-filter'),
			]			
		]);
		
	}
}

new Avalon23_Memory_Filter();
