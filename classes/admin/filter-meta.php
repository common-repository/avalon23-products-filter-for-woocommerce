<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_FilterItemsMeta {

	private $db_table = 'avalon23_filters_meta';
	private $db = null;

	public function __construct() {
		global $wpdb;
		$this->db = &$wpdb; //pointer
		$this->db_table = $this->db->prefix . $this->db_table;
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);

		//***

		add_filter('avalon23_table_orderby_select_args', function( $args, $filter_id = 0 ) {

			if ($filter_id > 0) {
				$found_rows = $this->get_rows($filter_id);

				if (!empty($found_rows)) {
					$filter_items = avalon23()->filter_items->get_items(intval($filter_id), ['fields' => 'title,field_key']);
					foreach ($found_rows as $r) {
						$title = '';
						$meta_key = $r['meta_key'];

						array_map(function( $item )use( $meta_key, &$title ) {
							if ($item['field_key'] === $meta_key) {
								$title = $item['title'];
							}
						}, $filter_items);

						if (empty($title)) {
							$title = $r['title'];
						}

						$args[$r['meta_key']] = $title . ': ' . esc_html__('Ascending', 'avalon23-products-filter');
						$args[$r['meta_key'] . '-desc'] = $title . ': ' . esc_html__('Descending', 'avalon23-products-filter');
					}
				}
			}

			return $args;
		}, 10, 2);
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-filter-meta', AVALON23_ASSETS_LINK . 'js/admin/filter-meta.js', ['data-table-23'], uniqid(), true);
		}
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('wp_ajax_avalon23_get_filter_meta', array($this, 'draw_table'));
			add_action('wp_ajax_avalon23_save_filter_meta_field', array($this, 'save'));
			add_action('wp_ajax_avalon23_create_meta', array($this, 'create'));
			add_action('wp_ajax_avalon23_delete_filter_meta', array($this, 'delete'));
		}
	}

	public function extend_filter_fields( $filter_id ) {
		$meta = $this->get_rows($filter_id);
		$meta = apply_filters('avalon23_get_all_meta', $meta, $filter_id);
		if (!empty($meta)) {
			$add_profile = [];
			foreach ($meta as $m) {
				$meta_key = $m['meta_key'];

				$tmp = [
					'title' => $m['title'],
					'meta_key' => $meta_key,
					'meta_type' => $m['meta_type'],
					'meta_options' => $m['notes'],
					'optgroup' => esc_html__('Meta Fields', 'avalon23-products-filter')	
				];

				switch ($m['meta_type']) {
					case 'text':
						$view_type = avalon23()->filter->options->get_option($filter_id, $meta_key, "{$meta_key}-meta-front-view");
						$tmp['view'] = $view_type;
						$tmp['options'] = ['meta-front-view'];
						switch ($view_type) {
							case 'select':
								$tmp['options'] = array_merge($tmp['options'], ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'meta-logic']);
								break;
							case 'labels':
								$tmp['options'] = array_merge($tmp['options'], ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'toggle', 'meta-logic']);
								break;
							case 'checkbox_radio':
								$tmp['options'] = array_merge($tmp['options'], ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'checkbox_template', 'toggle', 'meta-logic']);
								break;	
							case 'image':
								$tmp['options'] = array_merge($tmp['options'], ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'meta-image', 'show_title', 'toggle', 'meta-logic']);
								break;			
							case 'color':
								$tmp['options'] = array_merge($tmp['options'], ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'meta-color', 'show_title', 'toggle', 'meta-logic']);
								break;								
							default:
								$tmp['options'] = array_merge($tmp['options'], ['placeholder', 'minlength']);

						}
						
						if ('textinput' == $view_type) {
							$tmp['get_draw_data'] = function() use( $filter_id, $meta_key ) {
								$data = avalon23()->filter->get_field_drawing_data($filter_id, $meta_key);
								$data['view'] = 'textinput';
								return $data;
							};
							$tmp['get_query_args'] = function( $args, $value ) use( $meta_key ) {
								$args['meta_query'][] = array(
									'key' => $meta_key,
									'value' => $value,
									'compare' => 'LIKE'
								);

								return $args;
							};							
						} else {

							$tmp['get_draw_data'] = function() use( $filter_id, $meta_key ) {
								return avalon23()->filter->get_meta_drawing_data($filter_id, $meta_key);
							};							
							$tmp['get_count'] = function( $filter_id, $value = '', $dynamic_recount = 0 )use( $meta_key ) {
								$dynamic_recount = avalon23()->filter->options->get_option($filter_id, $meta_key, "$meta_key-dynamic_recount");

								if (-1 == $dynamic_recount || null == $dynamic_recount) {
									$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
								}
								if (-1 == $dynamic_recount || null == $dynamic_recount) {
									return -1;
								}
								return avalon23()->filter->get_meta_count($meta_key, $value, $filter_id);
							};	
							$tmp['get_query_args'] = function( $args, $value ) use( $meta_key, $filter_id ) {
								
								if (!is_array($value)) {
									$value = explode(',', $value);
								}
								$logic = 'IN';

								if ($filter_id > 0) {
									$logic = avalon23()->filter->options->get_option($filter_id, $meta_key, "{$meta_key}-meta-logic");
									if (!in_array($logic, ['IN', 'NOT IN'])) {
										$logic = 'IN';
									}
								}
								if ('NOT IN' == $logic) {
									$args['meta_query'][] = array(
										'relation' => 'OR',
										array(
											'key' => $meta_key,
											'value' => (array) $value,
											'compare' => $logic
										),
										array(
											'key' => $meta_key,
											'compare' => 'NOT EXISTS'
										)									
									);
								} else {
									$args['meta_query'][] = array(
											'key' => $meta_key,
											'value' => (array) $value,
											'compare' => $logic
										);									
								}

								return $args;
							};								
						}
						
						break;

					case 'number':
						$tmp['view'] = 'range_slider';
						$tmp['counted'] = 1;
						$tmp['options'] = ['min', 'max', 'dynamic_recount', 'show_count', 'hide_empty_terms'];
						$tmp['get_draw_data'] = function() use( $filter_id, $meta_key ) {
							
							return avalon23()->filter->get_field_drawing_data($filter_id, $meta_key);
						};
						$tmp['get_count'] = function( $filter_id, $value = '', $dynamic_recount = 0 )use( $meta_key ) {
							$dynamic_recount = avalon23()->filter->options->get_option($filter_id, $meta_key, $meta_key . '-dynamic_recount');
							if (!$dynamic_recount || ( $dynamic_recount && empty($value) )) {
								$value = avalon23()->filter->get_min_max_field($meta_key, 'min', $filter_id);
								$value .= ':' . avalon23()->filter->get_min_max_field($meta_key, 'max', $filter_id);
							}

							return avalon23()->filter->get_field_count($meta_key, $value, $filter_id);
						};
						$tmp['get_query_args'] = function( $args, $value ) use( $meta_key ) {
							$value = explode(':', $value);
							if (!isset($value[1])) {
								return $args;
							}
							$args['meta_query'][] = array(
								'key' => $meta_key,
								'value' => array(intval($value[0]), intval($value[1])),
								'type' => 'numeric',
								'compare' => 'BETWEEN'
							);

							return $args;
						};
						break;

					case 'calendar':
						$tmp['view'] = 'calendar';
						$tmp['options'] = ['calendar-data-type'];

						$tmp['get_draw_data'] = function() use( $filter_id, $meta_key ) {
							return avalon23()->filter->get_field_drawing_data($filter_id, $meta_key);
						};

						$tmp['get_query_args'] = function( $args, $value ) use( $filter_id, $meta_key ) {

							$is_calendar_dir_to = false;

							if (strlen($meta_key) > 5) {
								if (substr_count(strrev($meta_key), 'ot---', 0, 5)) {
									$is_calendar_dir_to = true;
									$meta_key = str_replace('---to', '', $meta_key);
								}
							}
																
								

							$calendar_type = avalon23()->filter->options->get_option($filter_id, $meta_key, $meta_key . '-calendar-data-type');
							if (!$calendar_type) {
								$calendar_type = 'unixtimestamp';
							}
							switch ($calendar_type) {
								case 'unixtimestamp':
									$args['meta_query'][] = array(
										'key' => $meta_key,
										'value' => intval($value),
										'type' => 'numeric',
										'compare' => $is_calendar_dir_to ? '<=' : '>='
									);

									break;

								//for ACF calendars https://support.advancedcustomfields.com/forums/topic/filtering-by-date/
								case 'datetime':
									$args['meta_query'][] = array(
										'key' => $meta_key,
										'value' => gmdate('Y-m-d H:i:s', $value),
										'type' => 'DATETIME',
										'compare' => $is_calendar_dir_to ? '<=' : '>='
									);

									break;
							}

							return $args;
						};
						break;
				}
				
				$add_profile[$m['meta_key']] = $tmp;
			}

			//***

			add_filter('avalon23_extend_filter_fields', function( $profile ) use( $add_profile ) {
				return array_merge($profile, $add_profile);
			}, 10, 2);
		}
	}

	//ajax
	public function draw_table() {
		$table_html_id = 'avalon23_meta_fields_table';
		$filter_id = 0;
		if (isset($_REQUEST['filter_id'])) {
			$filter_id = intval($_REQUEST['filter_id']);
		}
		
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => 'avalon23_meta_fields_table',
			'orderby' => 'meta_key',
			'order' => 'asc',
			'per_page_position' => 'none',
			'per_page_sel_position' => 'none',
			'per_page' => -1,
			'table_data' => $this->get_prepared_data($filter_id),
			'sanitize' => true
				], $table_html_id, false);

		echo esc_textarea(base64_encode($json_data));		

		exit(0);
	}

	public function add_table_action() {
		add_action('avalon23_meta_fields_table', function () {
			return [
				0 => [
				//'ajax_action' => ''
				],
				'title' => [
					'title' => esc_html__('Title', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'order' => 'asc'
				],
				'meta_key' => [
					'title' => esc_html__('Meta key', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'order' => 'asc'
				],
				'meta_type' => [
					'title' => esc_html__('Type', 'avalon23-products-filter'),
					'editable' => 'select',
					'order' => 'asc'
				],
				'notes' => [
					'title' => esc_html__('Meta items', 'avalon23-products-filter'),
					'editable' => 'textinput'
				],
				'actions' => [
					'title' => esc_html__('Actions', 'avalon23-products-filter')
				]
			];
		});
	}

	public function get_rows( $filter_id, $args = [], $where = [], $where_logic = 'AND' ) {
		static $cache = [];


		$fields = '*';
		if (isset($args['fields'])) {
			$fields = $args['fields']; //string
		}

		$orderby = 'meta_key';
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
				$and_where .= " {$where_logic} {$key}='{$value}'";
			}
		}

		//***

		$sql = "SELECT {$fields} FROM {$this->db_table} WHERE filter_id={$filter_id} {$and_where} ORDER BY {$orderby} {$order}";

		if (!isset($cache[md5($sql)])) {
			$cache[md5($sql)] = $this->db->get_results($sql, ARRAY_A);
		}

		return $cache[md5($sql)];
	}
	public  function get_meta_terms( $meta_key, $filter_id ) {				
		$args = array(
			'fields' => 'notes',
		);
		
		$meta_optios = array();
		
		$meta_optios = apply_filters('avalon23_get_meta_options', $meta_optios, $meta_key, $filter_id);
		
		if (!empty($meta_optios)) {
			return $meta_optios;
		}
		
		$_optios = $this->get_rows( $filter_id, $args, array('meta_key' => $meta_key) );

		if (count($_optios)) {
			$_optios = explode(',', $_optios[0]['notes']);
			foreach ($_optios as $option) {
				$option_tmp = explode('^', $option);
				if ( $option_tmp[0] ) {
					$mtitle = Avalon23_Vocabulary::get( trim(isset($option_tmp[1]) ? $option_tmp[1] : $option_tmp[0]) );
					$meta_optios[trim( $option_tmp[0] )] = $mtitle;
				}
			}
		}
				
		return $meta_optios;
	}
	private function get_prepared_data( $filter_id ) {
		$meta_items = [];
		$fields = array_keys(apply_filters('avalon23_meta_fields_table', null));

		$found_rows = $this->get_rows($filter_id);
		$found_rows_count = count($found_rows); //no pagination here
		//***

		if (!empty($fields) && ! empty($found_rows)) {
			$found_rows = array_slice($found_rows, 0, 2);
			foreach ($found_rows as $r) {
				$tmp = [];
				$tmp['pid'] = $r['id']; //VERY IMPORTANT AS IT POST ID IN THE TABLES CELLS ACTIONS

				foreach ($fields as $field) {
					switch ($field) {

						case 'meta_key':
							$tmp[$field] = $r['meta_key'];
							break;

						case 'meta_type':
							$tmp[$field] = AVALON23_HELPER::draw_select([
										'style' => 'width: 100%;',
										'data-redraw' => 1
											], [
										'not_defined' => esc_html__('not defined', 'avalon23-products-filter'),
										'text' => esc_html__('text', 'avalon23-products-filter'),
										'number' => esc_html__('number', 'avalon23-products-filter'),
										'calendar' => esc_html__('calendar', 'avalon23-products-filter')
											], $r['meta_type']);

							break;

						case 'title':
							$tmp[$field] = $r[$field];
							
							break;							
						case 'notes':
							$tmp[$field] = '<div class="avalon23_override_field_type  avalon23_meta_options"><span class=" dashicons dashicons-dismiss"></span>' .
									esc_html__('Only available for text type meta field', 'avalon23-products-filter') . '</div>';
							if ('text' == $r['meta_type']) {
								$tmp[$field] = $r[$field];
							}
							break;

						case 'actions':
							$tmp[$field] = AVALON23_HELPER::draw_html_item('a', [
										'href' => "javascript: avalon23_meta_fields_table.delete({$r['id']});void(0);",
										'title' => esc_html__('delete', 'avalon23-products-filter'),
										'class' => 'button avalon23-dash-btn-single'
											], '<span class="dashicons-before dashicons-no"></span>');

							break;

						default:
							$tmp[$field] = esc_html__('Wrong type', 'avalon23-products-filter');

							break;
					}
				}

				$meta_items[] = $tmp;
			}
		}


		return ['rows' => $meta_items, 'count' => $found_rows_count];
	}

	//*******************************************************************************
	//ajax
	public function save() {

		$field = '';
		if (isset($_REQUEST['field'])) {
			$field = sanitize_text_field($_REQUEST['field']);
		}
		$posted_id = 0;
		if (isset($_REQUEST['posted_id'])) {
			$posted_id = intval($_REQUEST['posted_id']);
		}
		$value = '';
		if (isset($_REQUEST['value'])) {
			$value = sanitize_text_field($_REQUEST['value']);
		}

		$this->update_field($field, $posted_id, AVALON23_HELPER::sanitize_text($value));

		die(esc_textarea(base64_encode(json_encode(['value' => sanitize_text_field($_REQUEST['value'])]))));
	}

	private function update_field( $field, $id, $value ) {
		$this->db->update($this->db_table, array(sanitize_key($field) => $value), array('id' => intval($id)));
	}

	//ajax
	public function create() {
		$filter_id = 0;
		if (isset($_REQUEST['filter_id'])) {
			$filter_id = intval($_REQUEST['filter_id']);
		}

		if ($filter_id > 0) {
			$this->insert(array(
				'filter_id' => $filter_id,
				'meta_key' => esc_html__('0 write key here', 'avalon23-products-filter')
			));
		}

		die(json_encode($this->get_prepared_data($filter_id)));
	}

	public function insert( $args ) {
		$this->db->insert($this->db_table, $args);
	}

	public function delete( $filter_item_id = 0 ) {

		if (!$filter_item_id) {
			if (!isset($_REQUEST['id'])) {
				$_REQUEST['id'] = 0;
			}
			$filter_item_id = intval($_REQUEST['id']);
		} else {
			$filter_item_id = intval($filter_item_id);
		}

		if ($filter_item_id > 0) {
			$this->db->delete($this->db_table, array('id' => $filter_item_id));
		}
	}

	public function gets() {
		return $this->db->get_results("SELECT * FROM {$this->db_table} ORDER BY id DESC", ARRAY_A);
	}

	public function import( $data ) {
		AVALON23_HELPER::import_mysql_table($this->db_table, $data);
	}

}
