<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_FilterItems {

	private $db_table = 'avalon23_filters_fields';
	private $db = null;
	public $options = null;
	public $meta = null;
	public $by_field_key = array();
	//public $filter = null;

	public function __construct() {
		global $wpdb;
		$this->db = &$wpdb; //pointer
		$this->db_table = $this->db->prefix . $this->db_table;
		$this->options = new Avalon23_FilterOptions();
		$this->meta = new Avalon23_FilterItemsMeta();
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-filter-items', AVALON23_ASSETS_LINK . 'js/admin/filter-items.js', [], uniqid(), true);
		}
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('wp_ajax_avalon23_get_filter_item_data', array($this, 'get_item_data'));
			add_action('wp_ajax_avalon23_save_filter_item_field', array($this, 'save_item_field'));
			add_action('wp_ajax_avalon23_create_filter_field', array($this, 'create_item'));
			add_action('wp_ajax_avalon23_refresh_filter_items_table', array($this, 'refresh'));
			add_action('wp_ajax_avalon23_delete_filter_field', array($this, 'delete'));
		}
	}

	public function add_table_action() {
		add_action('avalon23_filter_items_table', function () {
			return [
				0 => [
				//'ajax_action' => ''
				],
				'move' => [
					'title' => esc_html__('Move', 'avalon23-products-filter')
				],
				'title' => [
					'title' => esc_html__('Title', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'order' => 'asc'
				],
				'is_active' => [
					'title' => esc_html__('Active', 'avalon23-products-filter'),
					'order' => 'desc'
				],
				'field_key' => [
					'title' => esc_html__('Field', 'avalon23-products-filter'),
					'editable' => 'select'
				],
				'width_sm' => [
					'title' => esc_html__('Width for Mobile', 'avalon23-products-filter'),
					'editable' => 'select'
				],
				'width_md' => [
					'title' => esc_html__('Width for Tablets', 'avalon23-products-filter'),
					'editable' => 'select'
				],
				'width_lg' => [
					'title' => esc_html__('Width for Desktop', 'avalon23-products-filter'),
					'editable' => 'select'
				],
				'notes' => [
					'title' => esc_html__('Notes', 'avalon23-products-filter'),
					'editable' => 'textinput'
				],
				'actions' => [
					'title' => esc_html__('Actions', 'avalon23-products-filter')
				]
			];
		});
	}

	public function get_items( $filter_id, $args = [], $where = [] ) {
		$fields = '*';
		if (isset($args['fields'])) {
			$fields = $args['fields']; //string
		}

		$orderby = 'pos_num';
		if (isset($args['orderby'])) {
			$orderby = $args['orderby'];
		}

		$order = 'ASC';
		if (isset($args['order'])) {
			$order = $args['order'];
		}

		$and_where = '';
		if (!empty($where)) {
			foreach ($where as $key => $value) {
				$and_where .= " AND {$key}={$value}";
			}
		}

		$sql = "SELECT {$fields} FROM {$this->db_table} WHERE filter_id={$filter_id} {$and_where} ORDER BY {$orderby} {$order}";

		return $this->db->get_results($sql, ARRAY_A);
	}

	public function get_count( $filter_id, $args = [], $where = [] ) {
		$args['fields'] = 'COUNT(*) as count';
		return $this->get_items($filter_id, $args, $where)[0]['count'];
	}

	private function get_data( $filter_id, $order_by_active = true ) {
		$rows = [];
		$fields = array_keys(apply_filters('avalon23_filter_items_table', null));

		//***

		$found_items = $this->get_items($filter_id);
		$found_count = count($found_items); //no pagination here
		//+++
		//let active items always will be on the top
		if (!empty($found_items) && $order_by_active) {

			$tmp = [];
			foreach ($found_items as $key => $value) {
				if ($value['is_active']) {
					$tmp[] = $value;
					unset($found_items[$key]);
				}
			}

			$found_items = array_merge($tmp, $found_items);
			unset($tmp);
		}

		//+++

		$available_fields = avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);

		if (!empty($fields) && ! empty($found_items)) {

			foreach ($found_items as $c) {
				$tmp = [];
				$tmp['pid'] = $c['id']; //VERY IMPORTANT AS IT POST ID IN THE TABLES CELLS ACTIONS

				foreach ($fields as $field) {
					switch ($field) {
						case 'id':
							$tmp[$field] = $c['id'];
							break;

						case 'move':
							$tmp[$field] = AVALON23_HELPER::draw_html_item('img', [
										'src' => AVALON23_ASSETS_LINK . 'img/move.png',
										'width' => 20,
										'alt' => esc_html__('drag and drope', 'avalon23-products-filter'),
										'class' => 'avalon23-tr-drag-and-drope'
							]);
							break;

						case 'title':
							$tmp[$field] = $c['title'];
							break;

						case 'is_active':
							$tmp[$field] = AVALON23_HELPER::draw_switcher('is_active', $c['is_active'], $c['id'], 'avalon23_save_filter_item_field');
							break;

						case 'notes':
							$tmp[$field] = $c['notes'];
							break;
						case 'width_sm':
							$options = array(
								'1@sm' => '1/12',
								'2@sm' => '2/12',
								'3@sm' => '3/12',
								'4@sm' => '4/12',
								'5@sm' => '5/12',
								'6@sm' => '6/12',
								'7@sm' => '7/12',
								'8@sm' => '8/12',
								'9@sm' => '9/12',
								'10@sm' => '10/12',
								'11@sm' => '11/12',
								'12@sm' => '12/12',
								'hide show@sm' => esc_html__('Hide on mobile', 'avalon23-products-filter')
							);
							$options = apply_filters('avalon23_grid_options', $options, $filter_id, $field);
							if (!$c[$field]) {
								$c[$field] = '12@sm';
							}
							$tmp[$field] = AVALON23_HELPER::draw_select([], $options, $c[$field]);
							if (!isset($tmp[$field])) {
								$tmp[$field] = esc_html__('no fields found', 'avalon23-products-filter');
							}
							break;
						case 'width_md':
							$options = array(
								'1@md' => '1/12',
								'2@md' => '2/12',
								'3@md' => '3/12',
								'4@md' => '4/12',
								'5@md' => '5/12',
								'6@md' => '6/12',
								'7@md' => '7/12',
								'8@md' => '8/12',
								'9@md' => '9/12',
								'10@md' => '10/12',
								'11@md' => '11/12',
								'12@md' => '12/12',
								'hide show@md' => esc_html__('Hide on Tablets and mob ', 'avalon23-products-filter')
							);
							$options = apply_filters('avalon23_grid_options', $options, $filter_id, $field);
							if (!$c[$field]) {
								$c[$field] = '6@md';
							}
							$tmp[$field] = AVALON23_HELPER::draw_select([], $options, $c[$field]);
							if (!isset($tmp[$field])) {
								$tmp[$field] = esc_html__('no fields found', 'avalon23-products-filter');
							}
							break;
						case 'width_lg':
							$options = array(
								'1@lg' => '1/12',
								'2@lg' => '2/12',
								'3@lg' => '3/12',
								'4@lg' => '4/12',
								'5@lg' => '5/12',
								'6@lg' => '6/12',
								'7@lg' => '7/12',
								'8@lg' => '8/12',
								'9@lg' => '9/12',
								'10@lg' => '10/12',
								'11@lg' => '11/12',
								'12@lg' => '12/12',
									//'hide hide@lg'=>esc_html__('Hide on Desktop', 'avalon23-products-filter') 
							);
							$options = apply_filters('avalon23_grid_options', $options, $filter_id, $field);
							if (!$c[$field]) {
								$c[$field] = '4@lg';
							}
							$tmp[$field] = AVALON23_HELPER::draw_select([], $options, $c[$field]);
							if (!isset($tmp[$field])) {
								$tmp[$field] = esc_html__('no fields found', 'avalon23-products-filter');
							}
							break;

						case 'created':
							$tmp[$field] = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $c['created']);
							break;

						case 'field_key':
							$options = array();
							if (!empty($available_fields)) {
								$options[''] = [
									0 => esc_html__('not selected', 'avalon23-products-filter')
								];

								if (!empty($available_fields) && is_array($available_fields)) {
									foreach ($available_fields as $key => $f) {

										//as an example of manipulation
										if (isset($f['display']) && ! $f['display']) {
											continue;
										}
										$group_mame = esc_html__('Others', 'avalon23-products-filter');
										if (isset($f['optgroup']) && $f['optgroup']) {
											$group_mame = $f['optgroup'];
										}
										
										$options[$group_mame][$key] = $f['title'];
									}
								}
								//set disabled optiona
								$options_attribute = array();
								foreach ($found_items as $filter_item) {
									if (isset($available_fields[$filter_item['field_key']])) {
										$options_attribute[$filter_item['field_key']] = array('disabled' => 'disabled');
									}
								}
								
								//asort($options);
                                $free_fields=[
                                    'on_sale'=>['disabled'=>'disabled','style'=>'color:red;'],
                                    'post_author'=>['disabled'=>'disabled','style'=>'color:red;'],
                                    '_length'=>['disabled'=>'disabled','style'=>'color:red;'],
                                    'featured'=>['disabled'=>'disabled','style'=>'color:red;'],
                                    'height'=>['disabled'=>'disabled','style'=>'color:red;']
                                      ];
								$options_attribute = array_merge($options_attribute, $free_fields);
								$tmp[$field] = AVALON23_HELPER::draw_select_group([], $options, $c[$field], $options_attribute);
								$filter_type_array = AVALON23_HELPER::get_filter_name($filter_id, $c['field_key']);
								$filter_type = '<div class="av23_admin_filter_type av23_admin_filter_type_' . $filter_id . '_' . $c['field_key'] . '">' . array_shift($filter_type_array) . '</div>';
								$tmp[$field] .= $filter_type; 
							}

							if (!isset($tmp[$field])) {
								$tmp[$field] = esc_html__('no fields found', 'avalon23-products-filter');
							}
							break;

						case 'actions':
							/* translators: %s is replaced with "string" */
							$edit_popup_title = sprintf(esc_html__('Field: %s', 'avalon23-products-filter'), sanitize_text_field($c['title']));

							//***

							$options = apply_filters('avalon23_fields_options', [
								'filter_id' => $filter_id,
								'field_id' => $c['id'],
								'rows' => []
							]);

							$edit_button_classes = 'button avalon23-field-edit-btn avalon23-dash-btn-single';
							if (empty($options['rows'])) {
								$edit_button_classes .= ' avalon23-hidden';
							}

							$help = esc_html__('Help', 'avalon23-products-filter');
							$edit_button = AVALON23_HELPER::draw_html_item('a', [
										'href' => "javascript: avalon23_helper.call_popup(\"avalon23_get_field_item_field_option\",{field_id:{$c['id']}, filter_id:{$filter_id}, not_paste:1},\"avalon23_filter_options_table\",\"{$edit_popup_title}\", {left:15, right:15}, `<a href=\"https://avalon23.dev/document/filter-item/\" target=\"_blank\">{$help}</a>`); void(0);",
										'title' => esc_html__('edit', 'avalon23-products-filter'),
										'class' => $edit_button_classes
											], '<span class="dashicons-before dashicons-edit"></span>');

							//***

							$tmp[$field] = $edit_button . AVALON23_HELPER::draw_html_item('a', [
										'href' => "javascript: avalon23_filter_items_table.delete({$c['id']});void(0);",
										'title' => esc_html__('delete', 'avalon23-products-filter'),
										'class' => 'button avalon23-dash-btn-single'
											], '<span class="dashicons-before dashicons-no"></span>');										

							break;

						default:
							$tmp[$field] = esc_html__('Wrong type', 'avalon23-products-filter');

							break;
					}
				}

				$rows[] = $tmp;
			}
		}


		return ['rows' => $rows, 'count' => $found_count];
	}

	//*******************************************************************************
	//ajax
	public function get_item_data() {
		$table_html_id = '';
		if (isset($_REQUEST['table_html_id'])) {
			$table_html_id = sanitize_text_field($_REQUEST['table_html_id']);
		}
		$hide_text_search = false;
		?>
			<div class='avalon23-table-json-data' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
		<?php
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => 'avalon23_filter_items_table',
			'filter_id' => ( isset($_REQUEST['filter_id']) ) ? intval($_REQUEST['filter_id']) : 0,
			'per_page_position' => 'none',
			'per_page_sel_position' => 'none',
			'per_page' => -1,
			'sanitize' => true,
			'table_data' => $this->get_data( ( isset($_REQUEST['filter_id']) ) ? intval($_REQUEST['filter_id']) : 0)
				], $table_html_id, '');

		echo esc_textarea($json_data);
		?>
			</div>
		<?php
		$text_search_min_symbols = 2;
		$placeholder = esc_html__('search by title', 'avalon23-products-filter') . ' ...';
		$classes = 'avalon23-filter-items-table';
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php'));
		exit(0);
	}

	//ajax
	public function save_item_field() {
		
		if (!isset($_REQUEST['field'])) {
			$_REQUEST['field'] = '';
		}
		$value = '';
		if (isset($_REQUEST['value'])) {
			$value = sanitize_text_field($_REQUEST['value']);
		}	
		switch ($_REQUEST['field']) {
			case 'pos_num':
				$ids = explode(',', $value);

				if (!empty($ids) && is_array($ids)) {
					$ids = array_map(function( $id ) {
						return intval($id); //sanitize
					}, $ids);

					//***

					$pos_num = 0;
					foreach ($ids as $id) {
						if ($id > 0) {
							$this->update_field('pos_num', $id, $pos_num);
							++$pos_num;
						}
					}
				}

				break;

			default:
				$field = '';
				if (isset($_REQUEST['field'])) {
					$field = sanitize_text_field($_REQUEST['field']);
				}
				$posted_id = 0;
				if (isset($_REQUEST['posted_id'])) {
					$posted_id = intval($_REQUEST['posted_id']);
				}	
			
				//posted_id here is item id
				$this->update_field($field, $posted_id, AVALON23_HELPER::sanitize_text($value));
				break;
		}


		die(json_encode([
			'value' => AVALON23_HELPER::sanitize_text($value)
		]));
	}

	public function update_field( $field, $id, $value ) {
		$this->db->update($this->db_table, array(sanitize_key($field) => $value), array('id' => intval($id)));
	}

	public function create( $filter_id, $prepend = false, $title = '', $field_key = null ) {

		if ($prepend) {
			$pos_num = 0 - $this->get_count($filter_id);
		} else {
			$pos_num = $this->get_count($filter_id) + 1;
		}

		if (empty($title)) {
			$title = esc_html__('New Column', 'avalon23-products-filter');
		}

		if ($filter_id > 0) {
			$this->insert(array(
				'title' => $title,
				'filter_id' => $filter_id,
				'pos_num' => $pos_num,
				'field_key' => $field_key,
				'is_active' => intval(boolval($field_key)),
				'created' => current_time('U', get_option('timezone_string'))
			));
		}
	}

	//ajax
	public function create_item() {
		if (!isset($_REQUEST['posted_id'])) {
			$_REQUEST['posted_id'] = 0;
		}
		if (intval($_REQUEST['posted_id'])) {
			$this->create(intval($_REQUEST['posted_id']), ( isset($_REQUEST['prepend']) ) ? boolval($_REQUEST['prepend']) : true);
			die(json_encode($this->get_data(intval($_REQUEST['posted_id']), false)));
		}

		die('failed');
	}

	//ajax
	public function refresh() {
		if (!isset($_REQUEST['posted_id'])) {
			$_REQUEST['posted_id'] = 0;
		}		
		die(json_encode($this->get_data(intval($_REQUEST['posted_id']), false)));
	}

	public function insert( $args ) {
		$this->db->insert($this->db_table, $args);
	}

	//ajax
	public function delete( $id = 0 ) {

		if (!$id) {
			$id = ( isset($_REQUEST['id']) ) ? intval($_REQUEST['id']) : 0;
		} else {
			$id = intval($id);
		}

		if ($id > 0) {
			$this->db->delete($this->db_table, array('id' => $id));
		}
	}

	public function get( $id, $fields = [] ) {
		if (empty($fields)) {
			$fields = '*';
		} else {
			$fields = implode(',', $fields);
		}


		$sql = "SELECT {$fields} FROM {$this->db_table} WHERE id={$id}";
		return $this->db->get_row($sql, ARRAY_A);
	}

	public function get_by_field_key( $filter_id, $field_key ) {

		static $res = []; //cache

		if (!isset($res[$filter_id])) {
			$res[$filter_id] = [];
		}

		if (!isset($res[$filter_id][$field_key])) {
			$sql = "SELECT * FROM {$this->db_table} WHERE filter_id={$filter_id} AND field_key='{$field_key}'";
			$res[$filter_id][$field_key] = $this->db->get_row($sql, ARRAY_A);
		}
		
		return $res[$filter_id][$field_key];

	}

	public function get_acceptor_keys( $filter_id ) {
		$res = $this->get_items(intval($filter_id));

		if (!empty($res)) {
			$res = array_map(function( $item ) {
				return $item['field_key'];
			}, $res);
		}

		return $res;
	}

	public function gets() {
		return $this->db->get_results("SELECT * FROM {$this->db_table} ORDER BY id DESC", ARRAY_A);
	}

	public function import( $data ) {
		AVALON23_HELPER::import_mysql_table($this->db_table, $data);
	}

}
