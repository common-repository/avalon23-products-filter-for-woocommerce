<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Compatibility {
	
	public function __construct() {
		if (class_exists('ACF') && defined('ACF_VERSION')) {
			include_once AVALON23_PATH . 'compatibility/acf.php';
			new Avalon23_Compatibility_ACF();
		}
	}
	
}
