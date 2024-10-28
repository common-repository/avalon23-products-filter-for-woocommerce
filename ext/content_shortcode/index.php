<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Content_Shortcode {
	public $class = 'avalon23-content';
	public function __construct() {
		add_shortcode('avalon23_content', array($this, 'content_shortcode'));
		add_action('wp_head', array($this, 'wp_head'));
	}

	public function get_link() {
		return plugin_dir_url(__FILE__);
	}

	public function get_path() {
		return plugin_dir_path(__FILE__);
	}
	public function wp_head() {
		$avalon_prefix = 'av';
		if (Avalon23_Settings::get('filter_prefix') && Avalon23_Settings::get('filter_prefix') != -1) {
			$avalon_prefix = Avalon23_Settings::get('filter_prefix');
		}
		$filter_data = avalon23()->filter->current_request;
		wp_enqueue_script( 'avalon23-conteent-shortcode', $this->get_link() . 'js/content_shortcode.js', array('avalon23-filter'), AVALON23_VERSION);
		wp_localize_script( 'avalon23-conteent-shortcode', 'av23_content', array( 'prefix' =>  $avalon_prefix, 'filter_data' => $filter_data) );		
	}
	
	public function content_shortcode( $args, $content) {
		$args = shortcode_atts( array(
			'show_if' => '',
			'behavior' => 'standard', //opposite
			'mobile_behavior' => 'show_all', //hide, show
		), $args );
		extract($args);
		$class = $this->class;
		if (!empty($behavior) && 'standard' == $behavior) {
			$class .= ' avalon23-content-hide';
		}
		
		$div_args = array(
			'class' => $class,
			'data-behavior' => $behavior
		);		
		//show_if='filter_id+product_cat+pa_color:red,black'

		
		if ('show_all' != $mobile_behavior) {

			if ('show' == $mobile_behavior && !wp_is_mobile()) {
				return '';
			} elseif ('hide' == $mobile_behavior && wp_is_mobile()) {
				return '';
			}
		}
		
		if ($show_if) {
			$show_if_arg = explode('+', $show_if);
			foreach ($show_if_arg as $show_item) {
				$item_arr = explode(':' , $show_item, 2);
				if ( isset($item_arr[1]) && $item_arr[1] ) {
					$div_args['data-show-' . $item_arr[0]] = $item_arr[1];
				} else {
					$div_args['data-show-' . $item_arr[0]] = '_any_';
				}
			}			
		}
		
		return AVALON23_HELPER::draw_html_item('div', $div_args, do_shortcode($content));			
	}	
}

new Avalon23_Content_Shortcode();
