<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Settings {

	private static $key = 'avalon23_settings';
	public $action = 'avalon23_settings_table';

	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'), 9999);
	}

	public function admin_enqueue_scripts() {
		if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
			wp_enqueue_script('avalon23-settings', AVALON23_ASSETS_LINK . 'js/admin/settings.js', ['avalon23-generated-tables'], uniqid(), true);
		}
	}

	public function admin_init() {
		if (AVALON23_HELPER::can_manage_data()) {
			$this->add_table_action();
			add_action('wp_ajax_avalon23_save_settings_field', array($this, 'save'));

			//custom CSS
			add_action('wp_ajax_avalon23_save_table_custom_css', function() {
				avalon23()->filters->update_field(( isset($_REQUEST['filter_id']) ) ? intval($_REQUEST['filter_id']) : 0, 'custom_css', ( isset($_REQUEST['value']) ) ? sanitize_text_field($_REQUEST['value']) : '');
				exit(0);
			});

			add_action('wp_ajax_avalon23_get_table_custom_css', function() {
				die(esc_textarea(self::get_table_custom_css( ( isset($_REQUEST['filter_id']) ) ? intval($_REQUEST['filter_id']) : 0)));
			});

			//***
			//SHOW BUTTON ON THE TOP OF ADMIN PANEL
			add_action('admin_bar_menu', function( $wp_admin_bar ) {
				if (intval(self::get('show_btn_in_admin_bar'))) {
					$args = array(
						'id' => 'avalon23-btn',
						'title' => __('Avalon23', 'woocommerce-currency-switcher'),
						'href' => admin_url('admin.php?page=avalon23'),
						'meta' => array(
							'class' => 'wp-admin-bar-avalon23-btn',
							'title' => 'Avalon23 - WooCommerce Products Filters'
						)
					);
					$wp_admin_bar->add_node($args);
				}
			}, 250);
		}
	}

	//for admin
	public static function get_table_custom_css( $filter_id ) {
		if (avalon23()->filters->get($filter_id)) {
			return avalon23()->filters->get($filter_id)['custom_css'];
		}

		return '';
	}

	//for front
	public static function get_table_custom_prepared_css( $filter_id, $table_html_id ) {
		$css = self::get_table_custom_css($filter_id);
		if ($css) {
			$css = "/* FILTER CUSTOM CSS */ #{$table_html_id} " . $css;
			$css = str_replace('}' . PHP_EOL, "} #{$table_html_id} ", $css);
		}

		return $css;
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

	public function draw_table() {
		$table_html_id = 'avalon23-settings-table';
		return avalon23()->admin->draw_table_data([
					'mode' => 'json',
					'action' => $this->action,
					'per_page_position' => 'none',
					'per_page_sel_position' => 'none',
					'per_page' => -1,
					'table_data' => $this->get_rows()
						], $table_html_id, 'avalon23-settings-json-data') . AVALON23_HELPER::render_html('views/table.php', array(
					'table_html_id' => $table_html_id,
					'hide_text_search' => true
		));
	}
	public function draw_table_esc() {
		$table_html_id = 'avalon23-settings-table';
		?>
		<div class='avalon23-settings-json-data' data-table-id='<?php echo esc_attr($table_html_id); ?>' style='display: none;'>
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
		return apply_filters('avalon23_extend_settings', []);
	}

	//*******************************************************************************

	public static function get( $key = null ) {

		$defaults = apply_filters('avalon23_extend_settings_default', [
			'show_btn_in_admin_bar' => 1,
			'thumbnail_size' => 40
		]);

		$settings = get_option(self::$key, []);

		if ($settings && ! is_array($settings)) {
			$settings = json_decode($settings, true);
		}

		$settings = array_merge($defaults, $settings);


		if ($key) {
			if (isset($settings[$key])) {
				return $settings[$key];
			} else {
				return -1;
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

}
