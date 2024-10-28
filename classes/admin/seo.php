<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_SEO_Settings {
	public $action = 'avalon23_seo_table';
	public $action_seo_rules = 'avalon23_seo_rules_table';
	private static $key = 'avalon23_seo_settings';
	private static $seo_key = 'seo_rules';
	public function __construct() {
		
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);		
		
		add_filter('avlon23_export_data', array($this, 'export'), 9999);
		add_filter('avalon23_import_data', array($this, 'import'), 9999);
		
		add_action('wp_ajax_avalon23_save_seo_settings_field', array($this, 'save'));
		add_action('wp_ajax_avalon23_save_seo_rules_field', array($this, 'save_rules'));
		add_action('wp_ajax_avalon23_delete_seo_rules_field', array($this, 'delete_rules'));
		add_action('wp_ajax_avalon23_create_seo_rules_field', array($this, 'create_rules'));
		
		add_action('avalon23_draw_seo_tab', array($this, 'draw_options'));
	}
	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-seo-settings', AVALON23_ASSETS_LINK . 'js/admin/seo-settings.js', ['avalon23-generated-tables'], uniqid(), true);
			wp_enqueue_script('avalon23-seo-rules', AVALON23_ASSETS_LINK . 'js/admin/seo-rules.js', ['avalon23-generated-tables'], uniqid(), true);
		}
	}
	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('wp_ajax_avalon23_save_seo_field', array($this, 'save'));
		}
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
		
		add_action($this->action_seo_rules, function () {
			$profile = [
				0 => [
				//'ajax_action' => ''
				],
				'url' => [
					'title' => esc_html__('URL', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'order' => 'asc'
				],
				'meta_title' => [
					'title' => esc_html__('Title', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'custom_field_key' => true
				],				
				'meta_description' => [
					'title' => esc_html__('Description', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'custom_field_key' => true
				],
				'h1' => [
					'title' => esc_html__('H1', 'avalon23-products-filter'),
					'editable' => 'textinput',
					'custom_field_key' => true
				],					
				'delete' => [
					'title' => 'X'
				]
			];

			return $profile;
		});		
	}	
	public function draw_options() {
		?>
			<h1> <?php esc_html_e('SEO Settings', 'avalon23-products-filter'); ?></h1>
		<?php
		$this->draw_table_esc();
		?>
			<hr/>
			<h1> <?php esc_html_e('SEO Rules', 'avalon23-products-filter'); ?></h1>
		<?php		
		$this->draw_table_seo_rules();
	}
	
	public function draw_table_esc() {
		$table_html_id = 'avalon23-seo-settings-table';
		?>
		<div class='avalon23-seo-settings-json-data' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
		<?php
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => $this->action,
			'per_page_position' => 'none',
			'per_page_sel_position' => 'none',
			'per_page' => -1,
			'sanitize' => true,
			'table_data' => $this->get_rows()
				], $table_html_id, '');

		echo esc_textarea($json_data);
		?>
		</div>
		<?php
		$hide_text_search = true;
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php'));
	}	
	
	private function get_rows() {
		$oprions = array(
			[
				'title' => esc_html__('Do not index pages with search query', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher('enable_no_index', self::get('enable_no_index'), 0, 'avalon23_save_seo_settings_field'),
				'notes' => esc_html__('This is a useful feature for SEO site optimization. For example, if you use links with a search query in the content of the site, this setting will allow you not to create indexing of duplicate pages. If this link is in the seo rules then indexing occurs', 'avalon23-products-filter')
			],				
		);
		return $oprions;
	}
	

	public function export( $data ) {
		$data[$this->key] = get_option($this->key, []);
		if ($data[$this->key] && ! is_array($data[$this->key])) {
			$data[$this->key] = json_decode($data[$this->key], true);
		}		
		return $data;
	}
	public function import( $data ) {
		if (isset($data[$this->key]) && $data[$this->key]) {
			update_option($this->key, $data[$this->key]);
		}
		
		return $data;
	}
	public static function get( $key = null ) {

		$defaults = [
			'enable_no_index' => 1,
		];

		$settings = get_option(self::$key, []);

		if ($settings && ! is_array($settings)) {
			$settings = json_decode($settings, true);
		}

		$settings = array_merge($defaults, $settings);


		if ($key) {
			if (isset($settings[$key])) {
				return $settings[$key];
			} else {
				return false;
			}
		}

		return $settings;
	}

	//ajax
	public function save() {
		$settings = self::get();
		if (!isset($_REQUEST['value'])) {
			$_REQUEST['value'] = 0;
		}
		if (is_int($_REQUEST['value'])) {
			$value = intval($value);
		} else {
			$value = AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['value']));
		}

		if (!isset($_REQUEST['field'])) {
			$_REQUEST['field'] = '';
		}
		$settings[AVALON23_HELPER::sanitize_text(sanitize_text_field($_REQUEST['field']))] = $value;

		update_option(self::$key, $settings);

		die(json_encode([
			'value' => $value
		]));
	}	
	//rules
	public function draw_table_seo_rules() {
		$filter_prefixs = array();
		$filter_prefixs = avalon23()->filter->url_parser->get_filter_prefix ();
		if (!empty($filter_prefixs)) {
			?>
			<div>
				<select class="avalon23_seo_prefix">
					<?php foreach ($filter_prefixs as $id => $prefix ) { ?>
					<option  value="<?php echo esc_attr($prefix); ?>"><?php echo esc_attr($prefix); ?></option>
					<?php } ?>
				</select>
				<input type="text" class="avalon23_seo_search_link">
				<a class = 'button avalon23-dash-btn' href="<?php echo esc_js('javascript: avalon23_seo_rules_table.create();void(0);'); ?>" ><span class="dashicons-before dashicons-plus"></span> <?php esc_html_e('Add SEO rule', 'avalon23-products-filter'); ?></a>
			</div>
			<?php
		} else {
			esc_html_e('Notice', 'avalon23-products-filter');
		}
		
		
		$table_html_id = 'avalon23-seo-rules-table';
		$hide_text_search = true;
		$text_search_min_symbols = 1;
		$placeholder = esc_html__('search by url', 'avalon23-products-filter') . ' ...';
		?>
			<div class='avalon23-seo-rules-json-data' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
		<?php
		$json_data = avalon23()->admin->draw_table_data([
			'mode' => 'json',
			'action' => $this->action_seo_rules,
			'orderby' => 'url',
			'order' => 'asc',
			'per_page_position' => 'tb',
			'per_page_sel_position' => 't',
			'per_page' => 10,
			'table_data' => array_values($this->get_seo_rules_data()),
			'sanitize' => true
				], $table_html_id, '');
		echo esc_textarea($json_data);
		?>
			</div>
		<?php
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php'));		
		
	}
	
	public function get_seo_rules_data () {
		$rules = $this->get_rules();
		
		$data = [];
	
		if ($rules) {
			foreach ($rules as $key => $values) {
				$tmp = $values;
				$tmp['pid'] = $key;
				
				$tmp['delete'] = AVALON23_HELPER::draw_html_item('a', [
							'href' => "javascript: avalon23_seo_rules_table.delete({$key});void(0);",
							'title' => esc_html__('delete', 'avalon23-products-filter'),
							'class' => 'button avalon23-dash-btn-single'
								], '<span class="dashicons-before dashicons-no"></span>');				
				$data[$tmp['url']] = $tmp;
			}
		}
		
		return  $data;
	}
	public function save_seo_rules( $data, $key = null ) {
		$seo_rules = self::get( self::$seo_key );
		if (false === $seo_rules) {
			$seo_rules = array();
		}	
		if (!$key) {
			$key = hexdec(uniqid());
		}
		
		if ($seo_rules && ! is_array($seo_rules)) {
			$seo_rules = json_decode($seo_rules, true);
		}
		
		if (isset($data['url'])) {			
			$seo_rules[$key] = $data;			
			$settings = self::get();
			$settings[self::$seo_key] = $seo_rules;
			
			update_option(self::$key, $settings);
		}		
	}	
	public function get_rules( $key = null ) {
		
		$seo_rules = self::get( self::$seo_key );
		if ($seo_rules && ! is_array($seo_rules)) {
			$seo_rules = json_decode($seo_rules, true);
		}			
		
		if ($key) {
			if (isset($seo_rules[$key])) {
				return $seo_rules[$key];
			} else {
				return false;
			}			
			
		}

		return $seo_rules;		
	}	
	public function save_rules() {

		$field = '';
		$value = '';
		$key = hexdec(uniqid());
		if (isset($_REQUEST['field'])) {
			$field  = sanitize_textarea_field($_REQUEST['field']);
		}
		if (isset($_REQUEST['value'])) {
			$value  = sanitize_textarea_field($_REQUEST['value']);
		}
		if (isset($_REQUEST['posted_id'])) {
			$key  = sanitize_textarea_field($_REQUEST['posted_id']);
		}
		$data = $this->get_rules( $key );
		
		if ($data && $field) {
			$data[$field] = $value;
			$this->save_seo_rules($data, $key);
		}
	}
	public function delete_rules() {
		$key = '';
		if (isset($_REQUEST['id'])) {
			$key = sanitize_text_field($_REQUEST['id']);
		}
		$seo_rules = $this->get_rules();

		if ($seo_rules && isset($seo_rules[$key])) {
			unset($seo_rules[$key]);
			$settings = self::get();
			$settings[self::$seo_key] = $seo_rules;
			//bad fix
			//update_option(self::$key, $settings);
			update_option('avalon23_seo_settings', $settings);			
		}
	}

	public function create_rules() {
		$key = hexdec(uniqid()); 
		if (isset($_REQUEST['url'])) {
			$data['url']  = sanitize_textarea_field($_REQUEST['url']);
		}
		$data = array_merge($data, array(
			'meta_title' => '',
			'meta_description' => '',
			'h1' => ''
		));
		
		$this->save_seo_rules($data, $key);
		die(json_encode(array_values($this->get_seo_rules_data())));
	}
}

