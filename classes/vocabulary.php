<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Vocabulary {

	public static $translations = [];
	public $action = 'avalon23_vocabulary_table';
	private $db_table = 'avalon23_vocabulary';
	private $db = null;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->db_table = $this->db->prefix . $this->db_table;

		//WPML compatibility
		add_filter('avalon23_current_lang', function( $lang ) {
			if (class_exists('SitePress')) {
				global $sitepress;
				$sitepress->switch_lang(substr($lang, 0, 2), true);
			}

			return $lang;
		});

		self::$translations = $this->get_data();
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-vocabulary', AVALON23_ASSETS_LINK . 'js/admin/vocabulary.js', ['avalon23-generated-tables'], uniqid(), true);
		}
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			add_action('wp_ajax_avalon23_save_vocabulary_field', array($this, 'save'));
			add_action('wp_ajax_avalon23_create_vocabulary_field', array($this, 'create'));
			add_action('wp_ajax_avalon23_delete_vocabulary_field', array($this, 'delete'));

			//***

			add_action($this->action, function () {
				$profile = [
					0 => [
					//'ajax_action' => ''
					],
					'title' => [
						'title' => esc_html__('Key Word', 'avalon23-products-filter'),
						'editable' => 'textinput',
						'order' => 'asc'
					]
				];

				if (self::is_enabled()) {
					$languages = explode(',', Avalon23_Settings::get('languages'));
					if (!empty($languages)) {
						foreach ($languages as $key) {
							$key = trim($key);
							$profile[$key] = [
								'title' => $key,
								'editable' => 'textinput',
								'order' => 'asc'
							];
						}
					}
				}


				$profile['delete'] = [
					'title' => 'X'
				];

				return $profile;
			});
		}
	}

	public static function is_enabled() {
		return intval(Avalon23_Settings::get('languages')) > -1 && ! empty(Avalon23_Settings::get('languages'));
	}

	public function draw_table() {

		$table_html_id = 'avalon23-vocabulary-table';
		$hide_text_search = false;
		$text_search_min_symbols = 1;
		$placeholder = esc_html__('search by keyword', 'avalon23-products-filter') . ' ...';
		?>
			<div class='avalon23-vocabulary-json-data' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
		<?php
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => $this->action,
			'orderby' => 'title',
			'order' => 'asc',
			'per_page_position' => 'tb',
			'per_page_sel_position' => 't',
			'per_page' => 10,
			'table_data' => array_values(self::$translations),
			'sanitize' => true
				], $table_html_id, '');
		echo esc_textarea($json_data);
		?>
			</div>
		<?php
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php'));
	}

	private function get_data() {

		if ($this->db->get_var("SHOW TABLES LIKE '{$this->db_table}'") !== $this->db_table) {
			return []; //avoid notice while first activation before hook register_activation_hook
		}

		$sql = "SELECT * FROM {$this->db_table}";
		$res = $this->db->get_results($sql, ARRAY_A);

		//***

		$data = [];
		if (!empty($res)) {
			foreach ($res as $v) {
				$tmp = [
					'pid' => $v['id'],
					'title' => $v['title']
				];

				$translations = [];

				if ($v['translations']) {
					$translations = json_decode($v['translations'], true);
					if (json_last_error() !== 0) {
						$translations = [];
					}
				}

				$languages = explode(',', Avalon23_Settings::get('languages'));

				if (!empty($languages)) {
					foreach ($languages as $lang) {
						$tmp[$lang] = isset($translations[$lang]) ? $translations[$lang] : '';
					}
				}

				$tmp['delete'] = AVALON23_HELPER::draw_html_item('a', [
							'href' => "javascript: avalon23_vocabulary_table.delete({$v['id']});void(0);",
							'title' => esc_html__('delete', 'avalon23-products-filter'),
							'class' => 'button avalon23-dash-btn-single'
								], '<span class="dashicons-before dashicons-no"></span>');

				$data[$v['title']] = $tmp;
			}
		}

		return $data;
	}

	//ajax
	public function save() {
		$id = 0;
		if (isset($_REQUEST['posted_id'])) {
			$id = intval($_REQUEST['posted_id']);
		}

		if ($id) {
			$field = 'title';

			if (!isset($_REQUEST['value'])) {
				$_REQUEST['value'] = '';
			}
			$value = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['value']));
			if (!isset($_REQUEST['field'])) {
				$_REQUEST['field'] = '';
			}
			if ('title' !== $_REQUEST['field']) {
				$field = 'translations';
				$translations_obj = $this->db->get_row("SELECT {$field} FROM {$this->db_table} WHERE id = {$id}");
				$translations = $translations_obj->translations;

				if (!$translations) {
					$translations = [];
				} else {
					$translations = json_decode($translations, true);
					if (json_last_error() !== 0) {
						$translations = [];
					}
				}
				if (!isset($_REQUEST['field'])) {
					$_REQUEST['field'] = '';
				}
				$translations[AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['field']))] = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['value']));
				$value = json_encode($translations);
			}

			$this->db->update($this->db_table, [$field => $value], ['id' => $id]);
		}

		die('{}');
	}

	//ajax
	public function create() {
		$this->db->insert($this->db_table, [
			'title' => '0 __' . __('new translate', 'avalon23-products-filter') . ' ' . ( isset($_REQUEST['tail']) ) ? sanitize_text_field($_REQUEST['tail']) : ''
		]);

		die(json_encode(array_values($this->get_data())));
	}

	//ajax
	public function delete() {
		$id = 0;
		if (isset($_REQUEST['id'])) {
			$id = intval($_REQUEST['id']);
		}
		$this->db->delete($this->db_table, ['id' => $id]);
		die(json_encode(array_values($this->get_data())));
	}

	public static function get( $title_key, $lang = '' ) {
		$res = $title_key;

		if (isset(self::$translations[$title_key]) && ! empty(self::$translations[$title_key])) {
			if (empty($lang)) {
				$lang = apply_filters('avalon23_current_lang', get_locale());
			}

			if (isset(self::$translations[$title_key][$lang])) {
				$res = self::$translations[$title_key][$lang];
			}
		}

		if (empty($res)) {
			$res = $title_key;
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
