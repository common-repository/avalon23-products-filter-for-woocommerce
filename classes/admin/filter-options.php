<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_FilterOptions {

	public $field_options = null;

	public function __construct() {
		$this->field_options = new Avalon23_FilterItemsFieldsOptions();
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-filter-options', AVALON23_ASSETS_LINK . 'js/admin/filter-options.js', ['data-table-23'], uniqid(), true);
		}
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('wp_ajax_avalon23_get_filter_item_options', array($this, 'get_items_options'));
			add_action('wp_ajax_avalon23_save_filter_item_option_field', array($this, 'save'));
		}
	}

	//ajax
	public function get_items_options() {
		$filter_id = 'avalon23_filter_options_table';
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => 'avalon23_filter_options_table',
			'per_page_position' => 'none',
			'per_page_sel_position' => 'none',
			'per_page' => -1,
			'table_data' => $this->get_options_data( ( isset($_REQUEST['filter_id']) ) ? intval($_REQUEST['filter_id']) : 0),
			'sanitize' => true
				], $filter_id, false);

		echo esc_textarea(base64_encode($json_data));

		exit(0);
	}

	public function add_table_action() {
		add_action('avalon23_filter_options_table', function () {
			return [
				0 => [
				//'ajax_action' => ''
				],
				'title' => [
					'title' => esc_html__('Title', 'avalon23-products-filter')
				],
				'value' => [
					'title' => esc_html__('Value', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'custom_field_key' => true
				],
				'notes' => [
					'title' => esc_html__('Info', 'avalon23-products-filter')
				]
			];
		});
	}

	public function get_per_page_sel_pp( $filter_id, $as_array = false ) {
		$per_page_values = $this->get($filter_id, 'per_page_values', '10,20,30,40,50,60,70,80,90,100');

		if ($per_page_values) {
			if ($as_array) {
				$per_page_values = explode(',', $per_page_values);
			} else {
				$per_page_values = $per_page_values;
			}
		} else {
			if ($as_array) {
				$per_page_values = range(10, 100, 10);
			} else {
				$per_page_values = implode(',', range(10, 100, 10));
			}
		}

		return $per_page_values;
	}

	public function get_order_disabled( $filter_id ) {
		$res = $this->get($filter_id, 'order_disabled');
		if ($res) {
			$res = explode(',', $res);
		} else {
			$res = [];
		}

		return $res;
	}

	public function get_rows( $filter_id ) {
		return apply_filters('avalon23_extend_options', [], $filter_id);
	}

	private function get_options_data( $filter_id ) {
		$rows = [];
		$fields = 'title,value,notes';

		//***

		$found_options = $this->get_rows($filter_id);

		//***

		if (!empty($fields) && ! empty($found_options)) {
			$fields = explode(',', $fields);

			foreach ($found_options as $c) {
				$tmp = [];
				$tmp['pid'] = $c['id']; //VERY IMPORTANT AS IT POST ID IN THE TABLES CELLS ACTIONS

				foreach ($fields as $field) {
					switch ($field) {
						case 'title':
							$tmp[$field] = $c['title'];
							break;

						case 'value':
							if (isset($c['value_custom_field_key']) && ! empty($c['value_custom_field_key'])) {
								$tmp[$field] = [
									'value' => $c['value'],
									'custom_field_key' => $c['value_custom_field_key']
								];
							} else {
								$tmp[$field] = $c['value'];
							}

							break;

						case 'notes':
							$tmp[$field] = $c['notes'];
							break;

						default:
							$tmp[$field] = Avalon23_Vocabulary::get(esc_html__('Wrong type', 'avalon23-products-filter'));
							break;
					}
				}

				$rows[] = $tmp;
			}
		}


		return ['rows' => $rows, 'count' => count($found_options)];
	}

	public function get( $filter_id, $key, $default = null ) {
		static $options = [];
		$res = $default;

		//+++

		if (!isset($options[$filter_id])) {
			if (avalon23()->filters->get($filter_id)) {
				$options[$filter_id] = avalon23()->filters->get($filter_id)['options'];
				if ($options[$filter_id]) {
					$options[$filter_id] = json_decode($options[$filter_id], true);
				} else {
					$options[$filter_id] = [];
				}
			}
		}

		//+++

		if (isset($options[$filter_id][$key])) {
			$res = $options[$filter_id][$key];
		}

		if (is_numeric($res)) {
			$res = intval($res);
		}

		return $res;
	}

	//*******************************************************************************
	//ajax
	public function save() {

		$filter_id = 0;
		if (isset($_REQUEST['posted_id'])) {
			$filter_id = intval($_REQUEST['posted_id']);
		}
		if (!isset($_REQUEST['value'])) {
			$_REQUEST['value'] = 0;
		}
		if (is_int($_REQUEST['value'])) {
			$value = intval($value);
		} else {
			$value = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['value']));
		}

		//***
		$options = avalon23()->filters->get($filter_id)['options'];
		if ($options) {
			$options = json_decode($options, true);
		} else {
			$options = [];
		}

		if (!isset($_REQUEST['field'])) {
			$_REQUEST['field'] = '';
		}
		$options[AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['field']))] = $value;
		avalon23()->filters->update_field($filter_id, 'options', json_encode($options));

		die(json_encode([
			'value' => $value
		]));
	}

}
