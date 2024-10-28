<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
include_once AVALON23_PATH . 'classes/admin/seo.php'; 

class Avalon23_SEO {
	public $no_index = true;
	public $settings = null;
	public $current_rule = false;
	private $current_replace_vars = [];

	public function __construct() {
		$this->settings = new Avalon23_SEO_Settings();			

		//front
		add_filter( 'wp_robots', array($this, 'wp_robots_add_follow'));
		add_action( 'wp_head', array( $this, 'meta_head' ), 1);
		
		add_filter( 'document_title_parts', array( $this, 'set_title'), 10000, 2 );
		add_filter( 'the_title', array( $this, 'set_page_title'), 10000, 2 );
		add_filter( 'woocommerce_page_title', array( $this, 'set_h1') );
		
		remove_action( 'wp_head', 'rel_canonical');
		remove_action( 'wp_head', 'index_rel_link' );		
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'gutenberg_render_title_tag', 1 );
		
		//ajax redraw
		add_filter('avalon23_ajax_redraw_selector', array( $this, 'redraw_title'), 10000, 2 ); 
	}
	public function is_search_going() {
		$is_search = false;
		$is_search = avalon23()->filter->woocommerce_is_filtered($is_search);
		
		return $is_search;
	}
	public function is_seo_rules() {
		$is_seo = $this->settings::get('enable_no_index');	
		if ($this->check_search_rules()) {
			$is_seo = true;
		}
		return $is_seo;
	}
	public function check_search_rules() {
		$rules = $this->settings->get_rules();
		$current_url = avalon23()->filter->url_parser->get_request_uri();
		if ($this->current_rule) {
			return $this->current_rule;
		}
		if (!$rules) {
			return $this->current_rule;
		}
		foreach ($rules as $key => $rule_data) {
			if (isset($rule_data['url'])) {
				$needle = array('{any}', '/');
				$replase = array('.*', '\/');
				$url = str_replace($needle, $replase, $rule_data['url']);
				preg_match('/' . $url . '/', $current_url, $matches);
				if ( $matches ) {
					$this->current_rule = $rule_data;
					break;
				}
			}
		}
		
		return $this->current_rule;
	}
	public function meta_head () {
		//show  meta desc  title  
		$rule = $this->check_search_rules();		
		$this->add_canonical($rule);
		if (!$rule) {
			return false;
		}
		
		if (!isset($rule['meta_description'])) {
			$rule['meta_description'] = '';
		}
				
		$rule['meta_description'] = apply_filters('avalon23_seo_meta_description', $this->replace_vars($rule['meta_description'], $this->get_current_replace_vars()));
		$this->show_meta_description($rule['meta_description']);

		
	}
	public function add_canonical( $rule ) {
		$current_url = avalon23()->filter->url_parser->get_request_uri();
		if (!$rule) {
			$current_url = avalon23()->filter->url_parser->get_cleared_url( $current_url );
		}

		$canonical_link = apply_filters('avalon23_seo_canonical', home_url( $current_url ));
		?>
			<link rel="canonical" href="<?php echo esc_attr($canonical_link); ?>" />
		<?php
	}

	public function set_title( $title, $sep = '-') {
		
		$rule = $this->check_search_rules();
		
		if ($rule) {
			if ( isset($rule['meta_title']) && $rule['meta_title'] ) {
				 $title['title'] =  apply_filters('avalon23_seo_meta_title', $this->replace_vars($rule['meta_title'], $this->get_current_replace_vars()));
			}	
		}
		return $title;
	}
	public function set_h1 ( $title ) {
		$rule = $this->check_search_rules();
		if ($rule) {
			if ( isset($rule['h1']) && $rule['h1'] ) {
				$title = apply_filters('avalon23_seo_h1', $this->replace_vars($rule['h1'], $this->get_current_replace_vars()));
			}				
		}		
		
		return $title;
	}	
	
	public function redraw_title( $redraw, $data) {
		
		$redraw[] = '.entry-title';
		$redraw[] = '.woocommerce-products-header__title';
		
		return $redraw;
	}
	public function set_page_title( $title, $id ) {
		$rule = $this->check_search_rules();
		if ($rule && is_page($id)) {
			if ( isset($rule['h1']) && $rule['h1'] ) {
				$title = apply_filters('avalon23_seo_h1', $this->replace_vars($rule['h1'], $this->get_current_replace_vars()));
			}				
		}				
		return $title;		
		
	}	
	
	private function show_meta_description( $string ) {
		?>
		<meta name="description" content="<?php echo esc_attr($string); ?>" />
		<?php
		
	}

	public function get_current_replace_vars() {
		if (!count($this->current_replace_vars)) {
			$this->current_replace_vars = $this->get_replace_vars();
		}
		
		return $this->current_replace_vars;
	}
	public function get_replace_vars( $search_request = [] ) {
		if (!count($search_request)) {
			$search_request = avalon23()->filter->get_current_get_request();
		}		
		$search_vars = array();
		if (isset($search_request['filter_id']) && $search_request['filter_id']) {
			$filter_data = avalon23()->filter->draw_filter_form_data( array(), $search_request['filter_id'] );
			if ( $filter_data  ) {
				$filter_data = json_decode($filter_data, true);
				foreach ($search_request as $key => $search) {
					
					if (isset($filter_data[$key]) && $filter_data[$key]) {
						if (isset($filter_data[$key]['title']) && $filter_data[$key]['title']) {
							$search_vars[$key . '_title'] = $filter_data[$key]['title'];
						}
						if (isset($filter_data[$key]['view'])) {
							switch ($filter_data[$key]['view']) {
								case 'textinput':
									$search_vars[$key] = $search;
									break;
								case 'switcher':
									$search_vars[$key] = $filter_data[$key]['title'];
									break;
								case 'range_slider':
									$search = explode(':', $search );
									if (2 == count($search)) {
										/* translators: 1: min data 2: max data*/										
										$search_vars[$key] = Avalon23_Vocabulary::get(sprintf(esc_html__('From: %1$s to %1$s', 'avalon23-products-filter'), $search[0], $search[1]));
									}								
									break;
								default:
									if (isset($filter_data[$key]['options']) && $filter_data[$key]['options']) {
										$search_array = explode(',', $search);
										
										$temp_search = [];
										foreach ($filter_data[$key]['options'] as $option) {
											
											if (in_array($option['id'], $search_array)) {
												$temp_search[] = $option['title'];
											}
										}
										
										if (count($temp_search)) {
											$search_vars[$key] = implode(', ', $temp_search);
										}
									}
							}
						}
						
					}
				}

			}
		}		
		return $search_vars;
	}
	public function replace_vars( $string, $replace_vars) {
		foreach ($replace_vars as $key => $var) {
			$string = str_replace('{' . $key . '}', $var, $string);
		}
		$string = preg_replace( '/\{[a-zA-Z0-9_\W]+?\}/m', '', $string );
		
		return $string;
	}
	public function wp_robots_add_follow( $robots ) {
		
		if ($this->is_search_going()) {
			if ($this->is_seo_rules()) {
				$robots['index']  = true;
				$robots['follow'] = true;	
				$robots['noindex']  = false;
				$robots['nofollow'] = false;					
			} else {
				$robots['noindex']  = true;
				$robots['nofollow'] = true;	
				$robots['index']  = false;
				$robots['follow'] = false;					
			}

		}

		return $robots; 
	}
		
	
}
