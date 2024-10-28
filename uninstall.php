<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}
include_once plugin_dir_path(__FILE__) . 'classes/admin/db_controller.php';
$db_сontroller = new Avalon23_DB_Controller();
$db_сontroller->unistall();
