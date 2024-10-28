<?php

if (!defined('ABSPATH')) {
	die('No direct access allowed');
}

class Avalon23_Admin {

	public function __construct() {
		//+++
	}

	//это нужно только чтобы таблички в админке печатались, оставь как есть
	public function draw_table_data( $args, $table_html_id, $as_script = 'avalon23-table-json-data' ) {

		$current_page = 0;
		if (isset($args['current_page'])) {
			$current_page = intval($args['current_page']);
			if ($current_page < 0) {
				$current_page = 0;
			}
		}


		if (isset($_GET['current_page'])) {
			$current_page = intval($_GET['current_page']);
			if ($current_page < 0) {
				$current_page = 0;
			}
		}

		//***

		if (isset($args['id'])) {
			if (intval($args['id']) <= 0) {
				unset($args['id']); //fix, such tables can exists
			}
		}

		//***

		if (isset($args['action'])) {

			$profile = apply_filters($args['action'], ( isset($args['id']) ? intval($args['id']) : 0 ), $args);

			//***

			if (isset($args['columns']) && ! empty($args['columns'])) {
				$cols = explode(',', $args['columns']);

				//***

				$new_profile = [];
				$new_profile[0] = $profile[0];

				if (!empty($cols)) {
					foreach ($cols as $fk) {
						if (isset($profile[$fk])) {
							$new_profile[$fk] = $profile[$fk];
						}
					}

					$profile = $new_profile;
				}
			}
		}


		//***

		$add_data = [
			'post_type' => 'product'
		];

		if (isset($profile[0])) {
			$add_data = $profile[0];
			unset($profile[0]);
		}

		//***

		$columns = [];
		$orders = [];
		$editable = [];
		$custom_field_keys = []; //we need to save fields with the key another than data-field

		if (!empty($profile) && is_array($profile)) {
			foreach ($profile as $key => $c) {
				$columns[$key] = $c['title'];

				if (isset($c['order']) && $c['order']) {
					$orders[$key] = $c['order'];
				}

				if (isset($c['editable']) && $c['editable']) {
					$editable[$key] = $c['editable'];
				}

				if (isset($c['custom_field_key']) && $c['custom_field_key']) {
					$custom_field_keys[$key] = $c['custom_field_key'];
				}
			}
		}


		//***

		$filter_data = '';
		if (isset($add_data['filter_data'])) {
			$filter_data = json_encode($add_data['filter_data']); //connect for filter plugins
		}

		//shortcode $args has more prioritet
		if (isset($args['filter_data'])) {
			//$filter_data = json_encode($args['filter_data']);
			$filter_data = $args['filter_data'];
		}

		$filter_provider = '';
		if (isset($add_data['filter_provider'])) {
			$filter_provider = $add_data['filter_provider'];
		}

		//$args has more prioritet
		if (isset($args['filter_provider'])) {
			$filter_provider = $args['filter_provider'];
		}

		//***

		$ajax_action = 'avalon23_get_table_data';
		if (isset($add_data['ajax_action'])) {
			$ajax_action = $add_data['ajax_action'];
		}


		if (isset($args['ajax_action'])) {
			$ajax_action = $args['ajax_action'];
		}


		//for json mode
		$table_data = [];
		if (isset($args['table_data'])) {
			$table_data = $args['table_data'];
		}

		//***

		$per_page = 10;
		$per_page_position = 'tb';
		$per_page_sel_pp = range(10, 100, 10);
		$per_page_sel_position = 'tb';
		$orderby = 'id';
		$order = 'desc';
		$use_load_more = 0;
		$cells_width = [];
		$show_print_button = 0;
		$compact_view_width = -1;

		//constant filtr-independent choice
		$predefinition = [];

		if (isset($args['id'])) {
			$per_page = $this->filter_items->options->get(intval($args['id']), 'per_page_default', 10);
			$per_page_sel_pp = $this->filter_items->options->get_per_page_sel_pp(intval($args['id']), true);
			$per_page_position = $this->filter_items->options->get(intval($args['id']), 'pagination_position', 'tb');
			$per_page_sel_position = $this->filter_items->options->get(intval($args['id']), 'per_page_sel_position', 'tb');
			$orderby = $this->filter_items->options->get(intval($args['id']), 'default_orderby', 'id');
			$order = $this->filter_items->options->get(intval($args['id']), 'default_order', 'desc');
			$use_load_more = $this->filter_items->options->get(intval($args['id']), 'use_load_more_button', false);
			$show_print_button = $this->filter_items->options->get(intval($args['id']), 'show_print_button', 0);

			if (!isset($args['compact_view_width'])) {
				$compact_view_width = $this->filter_items->options->get(intval($args['id']), 'compact_view_width', -1);
			}

			$predefinition['rules'] = $this->predefinition->get(intval($args['id']));

			if (!isset($args['columns'])) {//columns can be set in shortcode
				$table_columns = $this->filter_items->get_items(intval($args['id']), [], ['is_active' => 1]);

				if (!empty($table_columns)) {
					$cells_width = array_map(function( $f ) {
						return $f['width'];
					}, $table_columns);

					$columns = [];

					foreach ($table_columns as $c) {
						if ($c['is_active']) {
							$columns[$c['field_key']] = $c['title'];
						}
					}
				}
			}

			//***

			$disable_orders = $this->filter_items->options->get_order_disabled(intval($args['id']));
			if (!empty($disable_orders)) {
				foreach ($disable_orders as $k) {
					if (isset($orders[$k])) {
						unset($orders[$k]);
					}
				}
			}
		}

		//***		

		if (isset($args['predefinition'])) {
			$predefinition['rules'] = json_decode($args['predefinition'], true);
		}


		if (isset($args['per_page'])) {
			if (intval($args['per_page']) === -1) {
				$per_page = -1;
			} else {
				$per_page = intval($args['per_page']);
			}
		}

		//***


		if (isset($args['per_page_sel_pp'])) {
			if (intval($args['per_page_sel_pp']) === -1) {
				$per_page_sel_pp = -1;
			} else {
				$per_page_sel_pp = explode(',', $args['per_page_sel_pp']);
			}
		}

		//***

		if (isset($args['per_page_position'])) {
			$per_page_position = trim($args['per_page_position']);
		}

		//***


		if (isset($args['per_page_sel_position'])) {
			$per_page_sel_position = trim($args['per_page_sel_position']);
		}

		//***


		if (isset($args['orderby'])) {
			$orderby = trim($args['orderby']);
			if (!$orderby) {
				$orderby = 'id';
			}
		}


		if (isset($args['order'])) {
			$order = trim($args['order']);

			if (!in_array($order, ['asc', 'desc'])) {
				$order = 'desc';
			}
		}

		if (isset($args['use_load_more'])) {
			$use_load_more = intval($args['use_load_more']);
		}

		if (isset($args['cells_width']) && ! empty($args['cells_width'])) {
			$cells_width = explode(',', $args['cells_width']);
		}

		if (isset($args['author']) && ! empty($args['author'])) {
			$predefinition['author'] = intval($args['author']);
		}

		if (isset($args['show_print_btn'])) {
			$show_print_button = intval($args['show_print_btn']);
		}

		//***

		if (!empty($columns)) {
			foreach ($columns as $key => $value) {
				$columns[$key] = Avalon23_Vocabulary::get($value);
			}
		}

		//***

		if (isset($args['compact_view_width'])) {
			$compact_view_width = intval($args['compact_view_width']);
		}

		//***

		$js_script_data = [
			'mode' => isset($args['mode']) ? $args['mode'] : 'ajax',
			'table_data' => $table_data, //for json mode
			'heads' => $columns,
			'hide_on_mobile' => '',
			'cells_width' => $cells_width,
			'orders' => $orders,
			'editable' => $editable,
			'custom_field_keys' => $custom_field_keys,
			'total_rows_count' => $per_page,
			'use_load_more' => $use_load_more,
			'css_classes' => isset($args['css_classes']) ? $args['css_classes'] : '',
			'no_found_text' => isset($args['no_found_text']) ? $args['no_found_text'] : '',
			'show_print_btn' => $show_print_button,
			'posted_id' => isset($args['filter_id']) ? intval($args['filter_id']) : 0, //for some program cases
			'compact_view_width' => $compact_view_width,
			'stop_notice' => isset($args['stop_notice']) ? $args['stop_notice'] : '',
			'pagination' => [
				'position' => $per_page_position, //t,b,tb,none
				'next' => [
					'class' => 'avalon23-btn',
					'content' => '&gt;'
				],
				'prev' => [
					'class' => 'avalon23-btn',
					'content' => '&lt;'
				],
				'input' => [
					'class' => 'form-control'
				],
			],
			'per_page_sel_position' => $per_page_sel_position, //t,b,tb,none
			'per_page_sel_pp' => $per_page_sel_pp,
			'request_data' => [
				'action' => $ajax_action,
				'fields' => array_keys($columns),
				'post_type' => isset($add_data['post_type']) ? $add_data['post_type'] : '',
				'wp_columns_actions' => $args['action'],
				'filter_id' => isset($args['id']) ? intval($args['id']) : 0,
				'predefinition' => serialize($predefinition),
				'filter_data' => $filter_data,
				'filter_provider' => $filter_provider,
				'orderby' => $orderby,
				'order' => $order,
				'per_page' => $per_page,
				'current_page' => $current_page,
				'shortcode_args_set' => serialize($args)
			]
		];

		//special param, not for customers
		if (isset($args['not_load_on_init'])) {
			$js_script_data['not_load_on_init'] = 1;
		}

		if ($as_script) {
			return "<div class='{$as_script}' data-table-id='{$table_html_id}' style='display: none;'>" . json_encode($js_script_data, JSON_HEX_QUOT | JSON_HEX_TAG) . '</div>';
		}

		if (isset($args['sanitize']) && $args['sanitize']) {
			return json_encode($js_script_data, JSON_HEX_QUOT | JSON_HEX_TAG);
		}
		return json_encode($js_script_data);
	}
	public function draw_table_data_san( $args, $table_html_id, $as_script = 'avalon23-table-json-data' ) {
		$current_page = 0;
		if (isset($args['current_page'])) {
			$current_page = intval($args['current_page']);
			if ($current_page < 0) {
				$current_page = 0;
			}
		}


		if (isset($_GET['current_page'])) {
			$current_page = intval($_GET['current_page']);
			if ($current_page < 0) {
				$current_page = 0;
			}
		}

		//***

		if (isset($args['id'])) {
			if (intval($args['id']) <= 0) {
				unset($args['id']); //fix, such tables can exists
			}
		}

		//***

		if (isset($args['action'])) {

			$profile = apply_filters($args['action'], ( isset($args['id']) ? intval($args['id']) : 0 ), $args);

			//***

			if (isset($args['columns']) && ! empty($args['columns'])) {
				$cols = explode(',', $args['columns']);

				//***

				$new_profile = [];
				$new_profile[0] = $profile[0];

				if (!empty($cols)) {
					foreach ($cols as $fk) {
						if (isset($profile[$fk])) {
							$new_profile[$fk] = $profile[$fk];
						}
					}

					$profile = $new_profile;
				}
			}
		}


		//***

		$add_data = [
			'post_type' => 'product'
		];

		if (isset($profile[0])) {
			$add_data = $profile[0];
			unset($profile[0]);
		}

		//***

		$columns = [];
		$orders = [];
		$editable = [];
		$custom_field_keys = []; //we need to save fields with the key another than data-field

		if (!empty($profile) && is_array($profile)) {
			foreach ($profile as $key => $c) {
				$columns[$key] = $c['title'];

				if (isset($c['order']) && $c['order']) {
					$orders[$key] = $c['order'];
				}

				if (isset($c['editable']) && $c['editable']) {
					$editable[$key] = $c['editable'];
				}

				if (isset($c['custom_field_key']) && $c['custom_field_key']) {
					$custom_field_keys[$key] = $c['custom_field_key'];
				}
			}
		}


		//***

		$filter_data = '';
		if (isset($add_data['filter_data'])) {
			$filter_data = json_encode($add_data['filter_data']); //connect for filter plugins
		}

		//shortcode $args has more prioritet
		if (isset($args['filter_data'])) {
			//$filter_data = json_encode($args['filter_data']);
			$filter_data = $args['filter_data'];
		}

		$filter_provider = '';
		if (isset($add_data['filter_provider'])) {
			$filter_provider = $add_data['filter_provider'];
		}

		//$args has more prioritet
		if (isset($args['filter_provider'])) {
			$filter_provider = $args['filter_provider'];
		}

		//***

		$ajax_action = 'avalon23_get_table_data';
		if (isset($add_data['ajax_action'])) {
			$ajax_action = $add_data['ajax_action'];
		}


		if (isset($args['ajax_action'])) {
			$ajax_action = $args['ajax_action'];
		}


		//for json mode
		$table_data = [];
		if (isset($args['table_data'])) {
			$table_data = $args['table_data'];
		}

		//***

		$per_page = 10;
		$per_page_position = 'tb';
		$per_page_sel_pp = range(10, 100, 10);
		$per_page_sel_position = 'tb';
		$orderby = 'id';
		$order = 'desc';
		$use_load_more = 0;
		$cells_width = [];
		$show_print_button = 0;
		$compact_view_width = -1;

		//constant filtr-independent choice
		$predefinition = [];

		if (isset($args['id'])) {
			$per_page = $this->filter_items->options->get(intval($args['id']), 'per_page_default', 10);
			$per_page_sel_pp = $this->filter_items->options->get_per_page_sel_pp(intval($args['id']), true);
			$per_page_position = $this->filter_items->options->get(intval($args['id']), 'pagination_position', 'tb');
			$per_page_sel_position = $this->filter_items->options->get(intval($args['id']), 'per_page_sel_position', 'tb');
			$orderby = $this->filter_items->options->get(intval($args['id']), 'default_orderby', 'id');
			$order = $this->filter_items->options->get(intval($args['id']), 'default_order', 'desc');
			$use_load_more = $this->filter_items->options->get(intval($args['id']), 'use_load_more_button', false);
			$show_print_button = $this->filter_items->options->get(intval($args['id']), 'show_print_button', 0);

			if (!isset($args['compact_view_width'])) {
				$compact_view_width = $this->filter_items->options->get(intval($args['id']), 'compact_view_width', -1);
			}

			$predefinition['rules'] = $this->predefinition->get(intval($args['id']));

			if (!isset($args['columns'])) {//columns can be set in shortcode
				$table_columns = $this->filter_items->get_items(intval($args['id']), [], ['is_active' => 1]);

				if (!empty($table_columns)) {
					$cells_width = array_map(function( $f ) {
						return $f['width'];
					}, $table_columns);

					$columns = [];

					foreach ($table_columns as $c) {
						if ($c['is_active']) {
							$columns[$c['field_key']] = $c['title'];
						}
					}
				}
			}

			//***

			$disable_orders = $this->filter_items->options->get_order_disabled(intval($args['id']));
			if (!empty($disable_orders)) {
				foreach ($disable_orders as $k) {
					if (isset($orders[$k])) {
						unset($orders[$k]);
					}
				}
			}
		}

		//***		

		if (isset($args['predefinition'])) {
			$predefinition['rules'] = json_decode($args['predefinition'], true);
		}


		if (isset($args['per_page'])) {
			if (intval($args['per_page']) === -1) {
				$per_page = -1;
			} else {
				$per_page = intval($args['per_page']);
			}
		}

		//***


		if (isset($args['per_page_sel_pp'])) {
			if (intval($args['per_page_sel_pp']) === -1) {
				$per_page_sel_pp = -1;
			} else {
				$per_page_sel_pp = explode(',', $args['per_page_sel_pp']);
			}
		}

		//***

		if (isset($args['per_page_position'])) {
			$per_page_position = trim($args['per_page_position']);
		}

		//***


		if (isset($args['per_page_sel_position'])) {
			$per_page_sel_position = trim($args['per_page_sel_position']);
		}

		//***


		if (isset($args['orderby'])) {
			$orderby = trim($args['orderby']);
			if (!$orderby) {
				$orderby = 'id';
			}
		}


		if (isset($args['order'])) {
			$order = trim($args['order']);

			if (!in_array($order, ['asc', 'desc'])) {
				$order = 'desc';
			}
		}

		if (isset($args['use_load_more'])) {
			$use_load_more = intval($args['use_load_more']);
		}

		if (isset($args['cells_width']) && ! empty($args['cells_width'])) {
			$cells_width = explode(',', $args['cells_width']);
		}

		if (isset($args['author']) && ! empty($args['author'])) {
			$predefinition['author'] = intval($args['author']);
		}

		if (isset($args['show_print_btn'])) {
			$show_print_button = intval($args['show_print_btn']);
		}

		//***

		if (!empty($columns)) {
			foreach ($columns as $key => $value) {
				$columns[$key] = Avalon23_Vocabulary::get($value);
			}
		}

		//***

		if (isset($args['compact_view_width'])) {
			$compact_view_width = intval($args['compact_view_width']);
		}

		//***

		$js_script_data = [
			'mode' => isset($args['mode']) ? $args['mode'] : 'ajax',
			'table_data' => $table_data, //for json mode
			'heads' => $columns,
			'hide_on_mobile' => '',
			'cells_width' => $cells_width,
			'orders' => $orders,
			'editable' => $editable,
			'custom_field_keys' => $custom_field_keys,
			'total_rows_count' => $per_page,
			'use_load_more' => $use_load_more,
			'css_classes' => isset($args['css_classes']) ? $args['css_classes'] : '',
			'no_found_text' => isset($args['no_found_text']) ? $args['no_found_text'] : '',
			'show_print_btn' => $show_print_button,
			'posted_id' => isset($args['filter_id']) ? intval($args['filter_id']) : 0, //for some program cases
			'compact_view_width' => $compact_view_width,
			'stop_notice' => isset($args['stop_notice']) ? $args['stop_notice'] : '',
			'pagination' => [
				'position' => $per_page_position, //t,b,tb,none
				'next' => [
					'class' => 'avalon23-btn',
					'content' => '&gt;'
				],
				'prev' => [
					'class' => 'avalon23-btn',
					'content' => '&lt;'
				],
				'input' => [
					'class' => 'form-control'
				],
			],
			'per_page_sel_position' => $per_page_sel_position, //t,b,tb,none
			'per_page_sel_pp' => $per_page_sel_pp,
			'request_data' => [
				'action' => $ajax_action,
				'fields' => array_keys($columns),
				'post_type' => isset($add_data['post_type']) ? $add_data['post_type'] : '',
				'wp_columns_actions' => $args['action'],
				'filter_id' => isset($args['id']) ? intval($args['id']) : 0,
				'predefinition' => serialize($predefinition),
				'filter_data' => $filter_data,
				'filter_provider' => $filter_provider,
				'orderby' => $orderby,
				'order' => $order,
				'per_page' => $per_page,
				'current_page' => $current_page,
				'shortcode_args_set' => serialize($args)
			]
		];

		//special param, not for customers
		if (isset($args['not_load_on_init'])) {
			$js_script_data['not_load_on_init'] = 1;
		}

		if ($as_script) {
			?>
						<div class='<?php echo esc_attr($as_script); ?>' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
			<?php echo esc_js(json_encode($js_script_data, JSON_HEX_QUOT | JSON_HEX_TAG)); ?>  
						</div>
			<?php
		}

		return json_encode($js_script_data);		
	}

}
