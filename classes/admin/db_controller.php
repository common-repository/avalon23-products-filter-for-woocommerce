<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_DB_Controller {
	private $version = '1.0';
	private $filters = '';
	private $filters_fields = '';
	private $filters_meta = '';
	private $vocabulary = '';
	private $cache = '';
	public function __construct() {
		global $wpdb;
		
		$this->filters = $wpdb->prefix . 'avalon23_filters';
		$this->filters_fields = $wpdb->prefix . 'avalon23_filters_fields';
		$this->filters_meta = $wpdb->prefix . 'avalon23_filters_meta';
		$this->vocabulary = $wpdb->prefix . 'avalon23_vocabulary';
		$this->cache = $wpdb->prefix . 'avalon23_cache';
	}
	public function check_db() {
		$this->check_main_tables();
			
		if ($this->is_old_version()) {
			$this->update_tables();
		}
	}
	public function update_tables() {
		//update tables
		return false;
	}
	public function is_old_version() {
		$db_version = get_option('avalon23_db_ver');
		if (version_compare($db_version, $this->version , '>=') ) {
			return false;
		} else {
			return true;
		}
	}
	public function check_main_tables() {
		global $wpdb;

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $this->filters ) ) ) == $this->filters ) {
			$this->create_db_tables();
			return true;
		} else {
			return false;
		}
	}


	public function create_db_tables() {
		global $wpdb;
		
		if (!function_exists('dbDelta')) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		$charset_collate = '';
		if (method_exists($wpdb, 'has_cap') && $wpdb->has_cap('collation')) {
			if (!empty($wpdb->charset)) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if (!empty($wpdb->collate)) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}
		}

		//***

		if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->filters)) !== $this->filters) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$this->filters}` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(256) NOT NULL DEFAULT 'New Filter',
					`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'is published',
					`thumbnail` int(11) NOT NULL DEFAULT '0',
					`skin` varchar(64) DEFAULT NULL,
					`custom_css` text,
					`predefinition` text,
					`options` text,
					 PRIMARY KEY (`id`)
				  ) {$charset_collate};";


			dbDelta($sql);
		}

		//+++

		if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->filters_fields)) !== $this->filters_fields) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$this->filters_fields}` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`filter_id` int(11) NOT NULL,
					`title` varchar(64) NOT NULL DEFAULT 'New Field',
					`field_key` varchar(48) DEFAULT NULL,
					`is_active` tinyint(1) NOT NULL DEFAULT '0',
					`width_sm` varchar(16) NOT NULL DEFAULT '12@sm',
					`width_md` varchar(16) NOT NULL DEFAULT '6@md',
					`width_lg` varchar(16) NOT NULL DEFAULT '4@lg',
					`notes` text COMMENT 'tootip on the site front',
					`options` text,
					`pos_num` int(4) NOT NULL DEFAULT '0' COMMENT 'position in table',
					`created` int(12) DEFAULT NULL,
					 PRIMARY KEY (`id`),
					 KEY `filter_id` (`filter_id`),
					 KEY `is_active` (`is_active`)
				  ) {$charset_collate};";


			dbDelta($sql);
		}

		//+++

		if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->filters_meta)) !== $this->filters_meta) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$this->filters_meta}` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(32) DEFAULT 'new meta key',
					`filter_id` int(11) NOT NULL,
					`meta_key` varchar(64) DEFAULT 'write_key_here',
					`meta_type` varchar(24) DEFAULT 'not_defined',
					`notes` text,				
					 PRIMARY KEY (`id`),
					 KEY `filter_id` (`filter_id`)
				  ) {$charset_collate};";


			dbDelta($sql);
		}

		//+++

		if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->vocabulary)) !== $this->vocabulary) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$this->vocabulary}` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`title` text,
				`translations` text,
				 PRIMARY KEY (`id`),
				 FULLTEXT KEY `title` (`title`)
			  ) {$charset_collate};";


			dbDelta($sql);
		}		
		add_option('avalon23_db_ver', $this->version);
	}
	public function unistall() {
		$settings = get_option('avalon23_settings', []);
		if ($settings && ! is_array($settings)) {
			$settings = json_decode($settings, true);
		}

		if (isset($settings['delete_db_tables']) && intval($settings['delete_db_tables'])) {
			$this->delete_data();
		}
		
	}
	private function delete_data() {
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}avalon23_filters`");
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}avalon23_filters_fields`");
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}avalon23_filters_meta`");
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}avalon23_vocabulary`");
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}avalon23_cache`");
		delete_option('avalon23_settings');
		delete_option('avalon23_seo');
	}
}

