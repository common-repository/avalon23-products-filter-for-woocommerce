<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Predefinition {

	public $action = 'avalon23_predefinition_table';

	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-predefinition', AVALON23_ASSETS_LINK . 'js/admin/predefinition.js', ['data-table-23'], uniqid(), true);
		}
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('wp_ajax_avalon23_get_predefinition_table', array($this, 'get_table'));
			add_action('wp_ajax_avalon23_save_table_predefinition_field', array($this, 'save'));
		}
	}

	//ajax
	public function get_table() {

		$filter_id = 0;
		if (isset($_REQUEST['filter_id'])) {
			$filter_id = intval($_REQUEST['filter_id']);
		}
		$table_html_id = 'avalon23-predefinition-table';
		$hide_text_search = true;
		?>
			<div class='avalon23-table-json-data' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
		<?php
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => $this->action,
			'per_page_position' => 'none',
			'per_page_sel_position' => 'none',
			'per_page' => -1,
			'table_data' => $this->get_data($filter_id),
			'sanitize' => true
				], $table_html_id, '');

		echo esc_textarea($json_data);
		?>
			</div>
		<?php
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php'));

		exit(0);
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

	private function get_data( $filter_id ) {
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

	private function get_rows( $filter_id ) {
		$rows = [
			[
				'id' => $filter_id,
				'title' => esc_html__('Products ids', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'ids'),
				'value_custom_field_key' => 'ids',
				'notes' => esc_html__('Using comma, set products ids you want to show in the table. Example: 23,99,777. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Exclude products ids', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'ids_exclude'),
				'value_custom_field_key' => 'ids_exclude',
				'notes' => esc_html__('Using comma, set products ids you want to hide in the table. Example: 24,101,888. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Products SKU', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'sku'),
				'value_custom_field_key' => 'sku',
				'notes' => esc_html__('Using comma, set products SKU you want to show in the table. Example: aa1,bb2,cc3. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Exclude products SKU', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'sku_exclude'),
				'value_custom_field_key' => 'sku_exclude',
				'notes' => esc_html__('Using comma, set products SKU you want to hide in the table. Example: aa1,bb2,cc3. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('On sale only', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher('on_sale_only', intval($this->get($filter_id, 'on_sale_only')), $filter_id, 'avalon23_save_table_predefinition_field'),
				'value_custom_field_key' => 'on_sale_only',
				'notes' => esc_html__('Show products which are on sale only', 'avalon23-products-filter')
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('In stock only', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher('in_stock_only', intval($this->get($filter_id, 'in_stock_only')), $filter_id, 'avalon23_save_table_predefinition_field'),
				'value_custom_field_key' => 'in_stock_only',
				'notes' => esc_html__('Show products which are in stock only', 'avalon23-products-filter')
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Featured only', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher('featured_only', intval($this->get($filter_id, 'featured_only')), $filter_id, 'avalon23_save_table_predefinition_field'),
				'value_custom_field_key' => 'featured_only',
				'notes' => esc_html__('Show featured products only', 'avalon23-products-filter')
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Authors', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'authors'),
				'value_custom_field_key' => 'authors',
				'notes' => esc_html__('Products by authors ids. Example: 1,2,3. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Included by taxonomy', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'by_taxonomy'),
				'value_custom_field_key' => 'by_taxonomy',
				'notes' => esc_html__('Display products which relevant to the rule. Example: product_cat:25,26|pa_color:19|rel:AND. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
			[
				'id' => $filter_id,
				'title' => esc_html__('Excluded by taxonomy', 'avalon23-products-filter'),
				'value' => $this->get($filter_id, 'not_by_taxonomy'),
				'value_custom_field_key' => 'not_by_taxonomy',
				'notes' => esc_html__('Exclude products which relevant to the rule. Example: pa_color:19|pa_size:21|rel:OR. Set -1 if you do not want to use it.', 'avalon23-products-filter'),
			],
		];


		return $rows;
	}

	public function get( $filter_id, $key = null ) {
		$predefinition = [];

		if (avalon23()->filters->get($filter_id)) {
			$predefinition = avalon23()->filters->get($filter_id)['predefinition'];
		}

		if (!$predefinition) {
			$predefinition = [];
		} else {
			$predefinition = json_decode($predefinition, true);
		}


		//***

		if ($key) {
			return isset($predefinition[$key]) ? $predefinition[$key] : -1;
		}

		return $predefinition;
	}

	//*******************************************************************************
	//ajax
	public function save() {
		$value = '';
		if (isset($_REQUEST['value'])) {
			$value = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['value']));
		}

		$filter_id = 0;
		if (isset($_REQUEST['posted_id'])) {
			$filter_id = intval($_REQUEST['posted_id']);
		}
		$predefinition = $this->get($filter_id);
		$field = '';
		if (isset($_REQUEST['field'])) {
			$field = sanitize_text_field($_REQUEST['field']);
		}
		$predefinition[AVALON23_HELPER::sanitize_text($field)] = $value;

		avalon23()->filters->update_field($filter_id, 'predefinition', json_encode($predefinition));

		die(json_encode([
			'value' => $value
		]));
	}

}
