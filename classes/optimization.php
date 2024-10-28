<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Optimization {

	public $is_active_transient = false;
	public $is_active_recount = false;
	public $is_active_js_css = false;
	public $cache_tab = 'avalon23_cache';
	public $cache_life_time = 0;
	public $table_is_created = false;
	private $transient_keys = array(
		'avalon23_meta_value_max',
		'avalon23_meta_value_mim'
	);
	public $temp_current_data = array();
	
	public function __construct() {

		if (!$this->table_is_created) {
			$this->create_cache_table();
		}

		$this->transient_keys = apply_filters('avalon23_transient_keys', $this->transient_keys);

		add_action('avalon23_extend_settings', array($this, 'add_settings'), 11);

		$this->is_active_transient = (bool) ( Avalon23_Settings::get('optimization_transient') != -1 ) ? Avalon23_Settings::get('optimization_transient') : false;
		$this->is_active_recount = (bool) ( Avalon23_Settings::get('optimization_recount') != -1 ) ? Avalon23_Settings::get('optimization_recount') : false;
		$this->is_active_js_css = (bool) ( Avalon23_Settings::get('optimization_js_css') != -1 ) ? Avalon23_Settings::get('optimization_js_css') : false;

		$this->cache_life_time = ( Avalon23_Settings::get('optimization_life_time') != -1 ) ? Avalon23_Settings::get('optimization_life_time') : 'days4';


		//auto cleare cache 
		if ($this->cache_life_time) {
			add_action('avalon23_cache_count_data_auto_clean', array($this, 'clear_count_cache'));
			if (!wp_next_scheduled('avalon23_cache_count_data_auto_clean')) {
				wp_schedule_event(time(), $this->cache_life_time, 'avalon23_cache_count_data_auto_clean');
			}
		}

		//ajax
		add_action('wp_ajax_avalon23_optimize_clear_cache', array($this, 'clear_count_cache'));
		add_action('wp_ajax_avalon23_optimize_clear_transient', array($this, 'clear_all_transient'));
	}

	public function set_transient_value( $key, $data ) {
		set_transient($key, $data, 1 * 24 * 3600); //1 day
	}

	public function clear_all_transient() {
		foreach ($this->transient_keys as $key) {
			$this->clear_transient($key);
		}
	}

	public function is_active( $key ) {
		$prop = 'is_active_' . $key;
		return (bool) $this->$prop;
	}

	public function get_transient( $key ) {

		$data = get_transient($key);
		if ($data) {
			return $data;
		} else {
			return false;
		}
	}

	public function clear_transient( $key ) {
		delete_transient($key);
	}

	public function get_min_max_meta( $key, $type = 'max' ) {
		$data = $this->get_transient('avalon23_meta_value_' . $type);
		if (isset($data[$key])) {
			return $data[$key];
		}
		return false;
	}

	public function set_min_max_meta( $key, $type = 'max', $value = false ) {
		$data = $this->get_transient('avalon23_meta_value_' . $type);
		if (!is_array($data)) {
			$data = array();
		}
		$data[$key] = $value;
		$this->set_transient_value('avalon23_meta_value_' . $type, $data);
	}

	//recount cache
	public function create_cache_table() {
		global $wpdb;
		$charset_collate = '';
		$table_name = $wpdb->prefix . $this->cache_tab;
		$response = false;
		if (method_exists($wpdb, 'has_cap') && $wpdb->has_cap('collation')) {
			if (!empty($wpdb->charset) && !empty($wpdb->collate)) {
				$response = $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}avalon23_cache` (
					`mkey` varchar(64) NOT NULL,
					`filter_id` int(11) NOT NULL,
					`mvalue` text NOT NULL,
					KEY `mkey` (`mkey`),
					KEY `filter_id` (`filter_id`)
				  ) DEFAULT CHARACTER SET %s COLLATE %s", $wpdb->charset, $wpdb->collate));
			}
		} else {
			$response = $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}avalon23_cache` (
				`mkey` varchar(64) NOT NULL,
				`filter_id` int(11) NOT NULL,
				`mvalue` text NOT NULL,
				KEY `mkey` (`mkey`),
				KEY `filter_id` (`filter_id`)
			  )");
		}

		if (false === $response) {
			$this->table_is_created = false;
		}
	}

	public function create_cache_key( $arg ) {

		$key = md5(json_encode($arg));

		//wpml compatibility
		if (defined('ICL_LANGUAGE_CODE')) {
			$key .= ICL_LANGUAGE_CODE;
		}
		//Polylang compatibility
		if (class_exists('Polylang')) {
			$lang = get_locale();
			$key .= $lang;
		}

		return $key;
	}

	public function set_ceched_filter_data( $args, $filter_id, $filter_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->cache_tab;
		$cache_key = $this->create_cache_key($args);

		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}avalon23_cache WHERE   mkey=%s AND filter_id=%d", $cache_key, $filter_id));

		$filter_data_json = json_encode($filter_data);

		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}avalon23_cache (mkey, filter_id, mvalue) VALUES (%s, %d , %s)", $cache_key, $filter_id, $filter_data_json));
	}

	public function get_ceched_filter_data( $args, $filter_id ) {
		global $wpdb;
		$result = false;
		$table_name = $wpdb->prefix . $this->cache_tab;
		$cache_key = $this->create_cache_key($args);
		
		$value = $wpdb->get_results($wpdb->prepare("SELECT mkey,mvalue FROM {$wpdb->prefix}avalon23_cache WHERE filter_id=%d AND mkey=%s", $filter_id, $cache_key));

		if (!empty($value)) {
			$value = end($value);
			if (isset($value->mkey)) {
				$result = $value->mvalue;
			}
		}

		return $result;
	}

	public function clear_count_cache() {
		global $wpdb;

		//$table_name = $wpdb->prefix . $this->cache_tab;

		$wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}avalon23_cache`");
	}

	public function add_settings( $rows ) {

		$time_options = array(
			0 => esc_html__('do not clean cache automatically', 'avalon23-products-filter'),
			'hourly' => esc_html__('clean cache automatically hourly', 'avalon23-products-filter'),
			'twicedaily' => esc_html__('clean cache automatically twicedaily', 'avalon23-products-filter'),
			'daily' => esc_html__('clean cache automatically daily', 'avalon23-products-filter'),
			'days2' => esc_html__('clean cache automatically each 2 days', 'avalon23-products-filter'),
			'days3' => esc_html__('clean cache automatically each 3 days', 'avalon23-products-filter'),
			'days4' => esc_html__('clean cache automatically each 4 days', 'avalon23-products-filter'),
			'days5' => esc_html__('clean cache automatically each 5 days', 'avalon23-products-filter'),
			'days6' => esc_html__('clean cache automatically each 6 days', 'avalon23-products-filter'),
			'days7' => esc_html__('clean cache automatically each 7 days', 'avalon23-products-filter')
		);
		$data = array(
			'class' => 'avalon23-multiple-select',
			'data-action' => 'avalon23_save_settings_field',
			'data-values' => Avalon23_Settings::get('optimization_life_time'),
			'data-field' => 'optimization_life_time'
		);

		$btn_data_cache = array(
			'onclick' => 'avalon23_settings_table.clear_recount_cache();',
			'class' => 'avalon23-dash-btn'
		);
		$btn_data_transient = array(
			'onclick' => 'avalon23_settings_table.clear_transient_cache();',
			'class' => 'avalon23-dash-btn'
		);

		$optimize_settings = [
			[
				'title' => esc_html__('JS and CSS of optimization', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher('optimization_js_css', Avalon23_Settings::get('optimization_js_css'), 0, 'avalon23_save_settings_field'),
				'notes' => esc_html__('Include minified JS and CSS files', 'avalon23-products-filter')
			],			
			[
				'title' => esc_html__('First level of optimization', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher_toggle('optimization_transient', Avalon23_Settings::get('optimization_transient'), 0, 'avalon23_save_settings_field', AVALON23_HELPER::draw_html_item('button', $btn_data_transient, '<span class="dashicons-before dashicons-trash"></span>' . esc_html__('Clear transient', 'avalon23-products-filter'))),
				'notes' => esc_html__('Using a transient to cache some static filter data. Use only after complete plugin configuration', 'avalon23-products-filter')
			],
			[
				'title' => esc_html__('Advanced optimization', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_switcher_toggle('optimization_recount', Avalon23_Settings::get('optimization_recount'), 0, 'avalon23_save_settings_field', AVALON23_HELPER::draw_html_item('button', $btn_data_cache, '<span class="dashicons-before dashicons-trash"></span>' . esc_html__('Clear cache', 'avalon23-products-filter'))),
				'notes' => esc_html__('Dynamic recalculation and filter data caching.Use only after complete plugin configuration', 'avalon23-products-filter')
			], [
				'title' => esc_html__('Cache lifetime', 'avalon23-products-filter'),
				'value' => [
					'value' => AVALON23_HELPER::draw_select($data, $time_options, ( Avalon23_Settings::get('optimization_life_time') != -1 ) ? Avalon23_Settings::get('optimization_life_time') : 'days4'),
					'custom_field_key' => 'optimization_life_time'
				],
				'notes' => esc_html__('Depends on how often you change filter settings and product data', 'avalon23-products-filter')
		]];


		return array_merge($rows, $optimize_settings);
	}
	public function get_temp_current_data( $key ) {
		if (isset($this->temp_current_data[$key])) {
			return $this->temp_current_data[$key];
		} else {
			return array();
		}
		
	}
	public function set_temp_current_data( $key, $data ) {
		$this->temp_current_data[$key] = $data;
	}	

}
