<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_FilterItemsFieldsOptions {

	public $table_html_id = 'avalon23_filter_options_table';
	public $action = 'avalon23_filter_items_fields_options_table';

	public function __construct() {
		add_action('admin_init', array($this, 'admin_init'), 9999);

		//fields options popup data 
		add_filter('avalon23_get_field_item_field_option', function( $data ) {
			if (is_array($data) && isset($data['filter_id'])) {
				$table_html_id = $this->table_html_id;
				$hide_text_search = true;
				include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php'));
				?>
				<div class='avalon23-data-table' data-table-id='<?php echo esc_attr($this->table_html_id); ?>' style='display: none;'>
				<?php				
				echo esc_textarea($this->get_items_options_esc(intval($data['filter_id']), intval($data['field_id'])));				
				?>
				</div>
				<?php				

			}
		});

		add_action('wp_ajax_avalon23_form_redraw', function() {
			$filter_id = 0;
			if (isset($_REQUEST['filter_id'])) {
				$filter_id = intval($_REQUEST['filter_id']);
			}
			$field_id = 0;
			if (isset($_REQUEST['field_id'])) {
				$field_id = intval($_REQUEST['field_id']);
			}
			echo esc_textarea(base64_encode($this->get_items_options_esc($filter_id, $field_id)));
			exit;
		});
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			add_action('wp_ajax_avalon23_save_filter_field_option', array($this, 'save'));
		}
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-filter-items-fields-options', AVALON23_ASSETS_LINK . 'js/admin/filter-items-fields-options.js', ['avalon23-generated-tables'], uniqid(), true);
		}
	}

	public function get_items_options( $filter_id, $field_id ) {
		return avalon23()->admin->draw_table_data([
					'mode' => 'json',
					'action' => $this->action,
					'per_page_position' => 'none',
					'per_page_sel_position' => 'none',
					'per_page' => -1,
					'use_flow_header' => 0,
					'table_data' => $this->get_options_data($filter_id, $field_id)
						], $this->table_html_id);
	}
	
	public function get_items_options_esc( $filter_id, $field_id) {

		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => $this->action,
			'per_page_position' => 'none',
			'per_page_sel_position' => 'none',
			'per_page' => -1,
			'use_flow_header' => 0,
			'table_data' => $this->get_options_data($filter_id, $field_id),
			'sanitize' => true
				], $this->table_html_id, '');

		return $json_data;
		
	}

	public function add_table_action() {
		add_action($this->action, function () {
			return [
				0 => [
				//'ajax_action' => ''
				],
				'title' => [
					'title' => esc_html__('Title', 'avalon23-products-filter')
				],
				'value' => [
					'title' => esc_html__('Value', 'avalon23-products-filter')
				],
				'notes' => [
					'title' => esc_html__('Info', 'avalon23-products-filter')
				]
			];
		});
	}

	private function get_options_data( $filter_id, $field_id ) {
		$data = apply_filters('avalon23_fields_options', [
			'filter_id' => $filter_id,
			'field_id' => $field_id,
			'rows' => []
		]);
		//print_r($data);
		return ['rows' => $data['rows'], 'count' => count($data['rows'])];
	}

	//ajax
	public function save() {

		$d = [];

		if (isset($_REQUEST['posted_id'])) {
			//for old versions of requests, should be remade in js's
			$d = explode('_', intval($_REQUEST['posted_id']));
		}

		if (count($d) === 2) {
			if (isset($_REQUEST['field']) && isset($_REQUEST['value'])) {
				$filter_id = intval($d[0]);
				$field_id = intval($d[1]);
				$key = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['field']));
				$value = intval($_REQUEST['value']);
			}
		} else {
			$filter_id = 0;
			if (isset($_REQUEST['filter_id'])) {
				$filter_id = intval($_REQUEST['filter_id']);
			}
			$field_id = 0;
			if (isset($_REQUEST['field_id'])) {
				$field_id = intval($_REQUEST['field_id']);
			}
			$key = '';
			if (isset($_REQUEST['key'])) {
				$key = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['key']));
			}
			$value = '';
			if (isset($_REQUEST['value'])) {
				
				if (stripos($key, 'custom_html') !== false) {
					$value = wp_kses_post($_REQUEST['value']);	

				} else {
					$value = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['value']));
					

				}


			}
		}

		//***

		$options = $this->get($field_id);
		$options[$key] = $value;
		$this->update($field_id, $options);

		die(json_encode(['value' => $value]));
	}

	public function get( $field_id ) {
		$options = avalon23()->filter_items->get($field_id, ['options'])['options'];

		if (!$options) {
			$options = [];
		} else {
			$options = json_decode($options, true);
		}


		return $options;
	}

	public function get_option( $filter_id, $field_key, $option_key ) {
		$res = avalon23()->filter_items->get_by_field_key($filter_id, $field_key);
		if ($res) {

			$res = $res['options'];

			if (!$res) {
				$res = [];
			} else {
				$res = json_decode($res, true);
			}
		}

		return isset($res[$option_key]) ? $res[$option_key] : '';
	}

	public function extract_from( $value, $key ) {
		if (!empty($value) && ! is_array($value)) {
			$value = json_decode($value, true);
		}

		if (isset($value[$key])) {
			return $value[$key];
		}

		return null;
	}

	public function update( $field_id, $options ) {
		avalon23()->filter_items->update_field('options', $field_id, json_encode($options));
	}

}
