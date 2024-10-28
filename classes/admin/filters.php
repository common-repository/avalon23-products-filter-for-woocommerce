<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Filters {

	private $db_table = 'avalon23_filters';
	private $db = null;

	public function __construct() {
		global $wpdb;
		$this->db = &$wpdb;
		$this->db_table = $this->db->prefix . $this->db_table;
		add_action('admin_init', array($this, 'admin_init'), 9999);
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {

			$this->add_table_action();

			add_action('wp_ajax_avalon23_create_filter', array($this, 'create'));
			add_action('wp_ajax_avalon23_save_filter_field', array($this, 'update'));
			add_action('wp_ajax_avalon23_delete_filter', array($this, 'delete'));
			add_action('wp_ajax_avalon23_clone_filter', array($this, 'clone_filter'));
		}
	}

	public function add_table_action() {
		add_action('avalon23_admin_table', function () {
			return [
				0 => [],
				'thumbnail' => [
					'title' => esc_html__('Thumb', 'avalon23-products-filter'),
					'order' => false
				],
				'title' => [
					'title' => esc_html__('Title', 'avalon23-products-filter'),
					'order' => 'asc',
					'editable' => 'textinput'
				],
				'shortcode' => [
					'title' => AVALON23_HELPER::draw_html_item('a', [
						'href' => 'https://avalon23.dev/document/avalon23/',
						'target' => '_blank'
							], esc_html__('Shortcode', 'avalon23-products-filter')),
					'order' => false
				],
				'status' => [
					'title' => esc_html__('Published', 'avalon23-products-filter'),
					'order' => false
				],
				'skin' => [
					'title' => esc_html__('Skin', 'avalon23-products-filter'),
					'editable' => 'select'
				],
				'actions' => [
					'title' => esc_html__('Actions', 'avalon23-products-filter'),
					'order' => false
				]
			];
		});
	}

	public function get( $filter_id ) {
		static $tables = [];

		if ($filter_id > 0) {
			if (!isset($tables[$filter_id])) {
				$tables[$filter_id] = $this->db->get_row("SELECT * FROM {$this->db_table} WHERE id = {$filter_id}", ARRAY_A);
			}
		} else {
			return [];
		}

		return $tables[$filter_id];
	}

	//ajax
	public function create() {
		$this->db->insert($this->db_table, [
			'title' => esc_html__('New Filter', 'avalon23-products-filter')
		]);

		$filter_id = intval($this->db->insert_id);

		//fields after creating
		$cols = new Avalon23_FilterItems();
		$cols->create($filter_id, 0, esc_html__('Title', 'avalon23-products-filter'), 'text_search');
		$cols->create($filter_id, 0, esc_html__('Price', 'avalon23-products-filter'), 'price');

		die(json_encode($this->get_admin_table_rows()));
	}

	//ajax
	public function update() {
		$filter_id = 0;
		if (isset($_REQUEST['posted_id'])) {
			$filter_id = intval($_REQUEST['posted_id']);
		}
		$field = '';
		if (isset($_REQUEST['field'])) {
			$field = sanitize_key($_REQUEST['field']);
		}
		$value = '';
		if (isset($_REQUEST['value'])) {
			$value = sanitize_text_field($_REQUEST['value']);
		}

		if ($filter_id > 0) {
			switch ($field) {
				case 'title':
				case 'skin':
					$value = AVALON23_HELPER::sanitize_text($value);
					$this->update_field($filter_id, $field, $value);
					break;

				case 'status':
				case 'thumbnail':
					$value = intval($value);
					$this->update_field($filter_id, $field, $value);
					break;
			}
		}

		die(json_encode([
			'value' => $value
		]));
	}

	public function update_field( $filter_id, $field, $value ) {
		$this->db->update($this->db_table, [$field => $value], array('id' => $filter_id));
	}

	//ajax
	public function delete() {
		$filter_id = 0;
		if (isset($_REQUEST['id'])) {
			$filter_id = intval($_REQUEST['id']);
		}
		$items = avalon23()->filter_items->get_items($filter_id, ['fields' => 'id']);
		if ($items) {
			foreach ($items as $item) {
				avalon23()->filter_items->delete($item['id']);
			}
		}
		//meta
		$meta = avalon23()->filter_items->meta->get_rows($filter_id, ['fields' => 'id']);
		if ($meta) {
			foreach ($meta as $m) {
				avalon23()->filter_items->meta->delete($m['id']);
			}
		}


		//table
		$this->db->delete($this->db_table, ['id' => $filter_id]);
	}

		//ajax
	public function clone_filter() {
		$donor_filter_id = 0;
		if (isset($_REQUEST['id'])) {
			$donor_filter_id = intval($_REQUEST['id']);
		}
		$table = $this->get($donor_filter_id);

		if ($table) {
			unset($table['id']);

			$this->db->insert($this->db_table, [
				'title' => esc_html__('New Filter', 'avalon23-products-filter')
			]);

			$new_filter_id = intval($this->db->insert_id);
			/* translators: %s is replaced with "string" */
			$table['title'] = sprintf(esc_html__('%s (clone)', 'avalon23-products-filter'), sanitize_text_field($table['title']));
			$this->db->update($this->db_table, $table, array('id' => $new_filter_id));

			$items = avalon23()->filter_items->get_items($donor_filter_id);
			$meta = avalon23()->filter_items->meta->get_rows($donor_filter_id);

			if (!empty($items)) {
				foreach ($items as $item) {
					unset($item['id']);
					$item['filter_id'] = $new_filter_id;
					$item['created'] = current_time('U', get_option('timezone_string'));
					avalon23()->filter_items->insert($item);
				}
			}

			if (!empty($meta)) {
				foreach ($meta as $m) {
					unset($m['id']);
					$m['filter_id'] = $new_filter_id;
					avalon23()->filter_items->meta->insert($m);
				}
			}
		}

		die(json_encode($this->get_admin_table_rows()));
	}

	private function get_thumbnail( $filter_id ) {
		$attachment_id = $this->get($filter_id)['thumbnail'];

		if ($attachment_id) {
			$img_src = wp_get_attachment_image_src($attachment_id, 'thumbnail');

			if (is_array($img_src) && ! empty($img_src[0])) {
				return AVALON23_HELPER::draw_html_item('a', array(
							'href' => 'javasctipt: void(0);',
							'onclick' => 'return avalon23_change_thumbnail(this);',
							'data-post-id' => $filter_id
								), AVALON23_HELPER::draw_html_item('img', array(
									'src' => $img_src[0],
									'width' => 40,
									'alt' => ''
				)));
			}
		} else {
			return AVALON23_HELPER::draw_html_item('a', array(
						'href' => 'javasctipt: void(0);',
						'onclick' => 'return avalon23_change_thumbnail(this);',
						'data-post-id' => $filter_id,
						'class' => 'avalon23-thumbnail'
							), AVALON23_HELPER::draw_html_item('img', array(
								'src' => AVALON23_LINK . 'assets/img/not-found.jpg',
								'width' => 40,
								'alt' => ''
			)));
		}
	}

	public function get_admin_table_rows() {

		$rows = [];
		$tables = $this->gets();

		if (!empty($tables)) {
			foreach ($tables as $t) {
				$filter_id = intval($t['id']);

				$rows[] = [
					'pid' => $filter_id,
					'thumbnail' => $this->get_thumbnail($filter_id),
					'title' => $this->get($filter_id)['title'],
					'shortcode' => AVALON23_HELPER::draw_html_item('input', [
						'type' => 'text',
						'class' => 'avalon23-shortcode-copy-container',
						'readonly' => 'readony',
						'value' => "[avalon23 id={$filter_id}]"
					]),
					'status' => AVALON23_HELPER::draw_switcher('status', $this->get($filter_id)['status'], $filter_id, 'avalon23_save_filter_field'),
					'skin' => AVALON23_HELPER::draw_select([], avalon23()->skins->get_skins(), $this->get($filter_id)['skin']),
					'actions' => AVALON23_HELPER::draw_html_item('a', array(
						'href' => "javascript: avalon23_main_table.call_popup({$filter_id}); void(0);",
						'class' => 'button avalon23-dash-btn-single',
						'title' => esc_html__('Filter options', 'avalon23-products-filter')
							), '<span class="dashicons-before dashicons-admin-generic"></span>')
					. AVALON23_HELPER::draw_html_item('a', [
						'href' => "javascript: avalon23_main_table.clone({$filter_id});void(0);",
						'title' => esc_html__('clone filter', 'avalon23-products-filter'),
						'class' => 'button avalon23-dash-btn-single'
							], '<span style="color:red;" class="dashicons-before dashicons-admin-page"></span>')
					. AVALON23_HELPER::draw_html_item('a', [
						'href' => "javascript: avalon23_main_table.delete({$filter_id});void(0);",
						'title' => esc_html__('delete filter', 'avalon23-products-filter'),
						'class' => 'button avalon23-dash-btn-single'
							], '<span class="dashicons-before dashicons-no"></span>')
				];
			}
		}

		return ['rows' => $rows, 'count' => count($rows)];
	}

	public function gets() {
		return $this->db->get_results("SELECT * FROM {$this->db_table} ORDER BY id DESC", ARRAY_A);
	}
	public function get_ids() {
		return $this->db->get_results("SELECT id FROM {$this->db_table} ORDER BY id DESC", ARRAY_A);
	}

	public function import( $data ) {
		AVALON23_HELPER::import_mysql_table($this->db_table, $data);
	}

}
