<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Filter {

	public $options = null;
	public $current_request = array();
	public $current_category = null;
	public $avalon_prefix = 'av';
	public $filters_data = array();
	public $url_parser = null;
	public $redraw_parts = array();
	public $db = null;
	
	public function __construct() {
		//ajax speed up
		if (isset($_POST['_wpnonce']) || ( isset($_POST['_wpnonce']) && function_exists('wp_verify_nonce') && wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'avalon23-nonce') ) ) {
			if (isset($_POST['avalon23_action']) && 'do_ajax' == sanitize_title($_POST['avalon23_action'])) {
				remove_all_actions('wp_head');
				remove_all_actions('wp_footer');			
			}
		}

		$this->url_parser =new Avalon23_Url_Parce();
		
		$this->options = new Avalon23_FilterItemsFieldsOptions();

		if (Avalon23_Settings::get('filter_prefix') && Avalon23_Settings::get('filter_prefix') != -1) {
			$this->avalon_prefix = Avalon23_Settings::get('filter_prefix');
		}

		
		add_action('init', array($this, 'set_current_request'), 100);

		add_action('wp_ajax_avalon23_form_filter_redraw', array($this, 'redraw_filter'));
		add_action('wp_ajax_nopriv_avalon23_form_filter_redraw', array($this, 'redraw_filter'));

		add_action('wp_ajax_avalon23_get_products_data', array($this, 'get_products_data'));
		add_action('wp_ajax_nopriv_avalon23_get_products_data', array($this, 'get_products_data'));
		

		add_action('wp_head', function() {
			wp_enqueue_script('avalon23-filter', AVALON23_ASSETS_LINK . 'js/filter.js', [], uniqid(), true);

			if (Avalon23_Settings::get('do_after_ajax') != -1 && Avalon23_Settings::get('do_after_ajax')) {
				wp_add_inline_script('avalon23-filter', $this->get_js_function_after_ajax(Avalon23_Settings::get('do_after_ajax')));
			}
		});
		//query hooks
		add_filter('woocommerce_shortcode_products_query', array($this, 'shortcode_products_query'), 10, 3);
		add_action('woocommerce_product_query', array($this, 'parse_woo_query'), 9999, 2);
		//WOOT compatibility
		add_filter('woot_wp_query_args', array($this, 'shortcode_products_query'), 999, 2);
		//compatibility with catalog mode
		add_filter('woocommerce_is_filtered', array($this, 'woocommerce_is_filtered'), 20);

		$this->init_shortcode_no_products_found();
		
		//stuped  trik
		global $wpdb;
		$this->db = $wpdb;
	}			
	public function set_current_request() {
		$this->current_request = $this->get_current_get_request();
	}

	public function get_current_get_request() {
		$request = array();
		$ids = avalon23()->filters->get_ids();
		if (isset($_GET) && ! empty($_GET)) {
			foreach ($_GET as $key => $val) {
				foreach ($ids as $item) {
					$prefix = $this->generate_filter_prefix($item['id']);
					if (strpos($key, $prefix) === 0) {
						$count =1;
						$new_key = str_replace($prefix, '', $key, $count);
						if ($new_key) {
							$request[$item['id']][$new_key] = urldecode(sanitize_text_field($val));
						}						
					}					
				}
			
//				if (strpos($key, $this->avalon_prefix) === 0) {
//					$count = 1;
//					$new_key = str_replace($this->avalon_prefix, '', $key, $count);
//					if ($new_key) {
//						$request[$new_key] = urldecode(sanitize_text_field($val));
//					}
//				}
			}
		}

		$request = $this->url_parser->create_request($request);

		//fix for  wp search
		if (isset($_GET['s'])) {
			foreach ($request as $id =>$data) {
				$request[$id]['text_search'] = urldecode(sanitize_text_field($_GET['s']));
				$_GET['text_search'] = $request[$id]['text_search'];
				//unset($_GET['s']);				
			}

		}

		return apply_filters('avalon23_request_get_data', $request);
	}

	public function woocommerce_is_filtered( $is_filtered ) {
		if ($this->current_request) {
			$is_filtered = true;
		}
		return $is_filtered;
	}

	//ajax
	public function get_products_data() {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'avalon23-nonce') ) {
			die(json_encode(array('error'=>'Security error')));
		}
		$filter_data = array();
		$filter_id = 0;
		if (isset($_POST['filter_id']) && ! empty($_POST['filter_id'])) {
			$filter_id = intval($_POST['filter_id']);
		}
		if (isset($_POST['filter_data']) && ! empty($_POST['filter_data'])) {
			$filter_data = json_decode(stripcslashes(sanitize_text_field($_POST['filter_data'])), true);
		}
		if (isset($_POST['current_tax']) && ! empty($_POST['current_tax'])) {
			$curr_tax = json_decode(stripcslashes(sanitize_text_field($_POST['current_tax'])), true);
			if (isset($curr_tax['taxonomy']) &&  isset($curr_tax['term_id'])) {
				$term = get_term($curr_tax['term_id'], $curr_tax['taxonomy']);
				if ($term && !is_wp_error($term)) {
					$this->current_category = $term;
				}
			}
			
		} 
		
		if (isset($_POST['get_vars']) && ! empty($_POST['get_vars'])) {
			$_GET = json_decode(stripcslashes(sanitize_text_field($_POST['get_vars'])), true);
			$this->current_request = $this->get_current_get_request();
		}
		
		
		$filter_data['filter_id'] = $filter_id;

		$this->current_request[$filter_id] = $filter_data;		


		$query_vars  = array(
			//'nopaging' => true,
			'posts_per_page' => 8,
			'fields' => 'ids',
			'post_type' => 'product',
			'post_status' => 'publish',
			'tax_query' => array(),
			'meta_query' => array()
		);

		$visibility = $this->get_current_taxonomy_query($this->get_current_taxonomy());
		if (!empty($visibility)) {
			$query_vars['tax_query'][] = $visibility;
		}
		$query_vars['tax_query'] = $this->get_visibility_query($query_vars['tax_query']);
		//$query_vars = $this->get_start_count_query();
		$filter_request = $this->current_request;
		$query_args = $this->get_query($query_vars, $filter_request, 'ajax_text');
		$q = new WP_Query($query_args);
		$result = array();
		if ($q->have_posts()) {
			foreach ($q->posts as $p) {
				$result[] = array(
					'id' => $p,
					'url' => get_permalink($p),
					'title' => get_the_title($p),
					'img' => get_the_post_thumbnail_url( $p, 'thumbnail' )
				);

			}
		} else {
				$result[] = array(
					'id' => -1,
					'url' => '#',
					'title' => esc_html__('Nothing found...', 'avalon23-products-filter'),
					'img' => false
				);			
		}
		die(json_encode($result));
	}
	public function redraw_filter() {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'avalon23-nonce') ) {
			die(json_encode(array('error'=>'Security error')));
		}
		$res = $_POST;
		$filter_data = array();
		$filter_id = 0;
		if (isset($_POST['filter_id']) && ! empty($_POST['filter_id'])) {
			$filter_id = intval($_POST['filter_id']);
		}
		if (isset($_POST['filter_data']) && ! empty($_POST['filter_data'])) {
			$filter_data = json_decode(stripcslashes(sanitize_text_field($_POST['filter_data'])), true);
		}
		if (isset($_POST['current_tax']) && ! empty($_POST['current_tax'])) {
			$curr_tax = json_decode(stripcslashes(sanitize_text_field($_POST['current_tax'])), true);
			if (isset($curr_tax['taxonomy']) &&  isset($curr_tax['term_id'])) {
				$term = get_term($curr_tax['term_id'], $curr_tax['taxonomy']);
				if ($term && !is_wp_error($term)) {
					$this->current_category = $term;
				}
			}
			
		} 
		
		if (isset($_POST['get_vars']) && ! empty($_POST['get_vars'])) {
			$_GET = json_decode(stripcslashes(sanitize_text_field($_POST['get_vars'])), true);
			$this->current_request = $this->get_current_get_request();
		}
		
		
		$filter_data['filter_id'] = $filter_id;

		$this->current_request[$filter_id] = $filter_data;
		
		if (isset($_POST['show_btn_count']) && (int) $_POST['show_btn_count']) {
			$res['product_count'] = $this->get_field_count('', '', $filter_id);
		}
		
		$res['filter'] = $this->draw_filter_form_data('', $filter_id);
		
		$res['get'] = json_encode($this->current_request);

		die(json_encode($res));
	}

	//DRAWING
	//filter form data for table js filter
	public function draw_filter_form_data( $filter_args, $filter_id ) {
		
		if ($filter_id > 0 && empty($filter_args)) {
			$publish = ( avalon23()->filters->get($filter_id) ) ? avalon23()->filters->get($filter_id)['status'] : true;
			if (!$publish) {
				return '';
			}
			if (isset($this->filters_data[$filter_id]) && $this->filters_data[$filter_id]) {
				//$this->filters_data['filter_options']['debag_mode'] = ( isset($_GET['avalon23_debag']) && sanitize_text_field($_GET['avalon23_debag']) ) ? intval(sanitize_text_field($_GET['avalon23_debag'])) : 0;
				//$this->filters_data['filter_options']['_wpnonce'] = wp_create_nonce('avalon23-nonce');
				
				return json_encode(apply_filters('avalon23_filter_redraw_data', $this->filters_data[$filter_id]));
			}
			
			$ak = avalon23()->filter_items->get_acceptor_keys($filter_id);

			if (is_array($ak) && ! empty($ak)) {
				$filter_args = implode(',', $ak);
			}

			//get cache here		   
			if (avalon23()->optimize->is_active('recount')) {
				$temp_query = $this->get_start_count_query();
				$temp_filter_request = $this->current_request;
				$temp_filter_request[$filter_id]['filter_id'] = $filter_id;
				$temp_query_args = $this->get_query($temp_query, $temp_filter_request, 'cache_count');
				$filter_data = avalon23()->optimize->get_ceched_filter_data($temp_query_args, $filter_id);
				
				if ($filter_data) {
					
					$filter_data = json_decode($filter_data, true); 
					
					$filter_data['filter_options']['debag_mode'] = ( isset($_GET['avalon23_debag']) && sanitize_text_field($_GET['avalon23_debag']) ) ? intval(sanitize_text_field($_GET['avalon23_debag'])) : 0;
					$filter_data['filter_options']['_wpnonce'] = wp_create_nonce('avalon23-nonce');
					$this->filters_data[$filter_id] = $filter_data;
					return json_encode(apply_filters('avalon23_filter_redraw_data', $filter_data));
				}
			}

			if (!empty($filter_args)) {
				$filter_args = explode(',', $filter_args);
				$filter_data = [];

				if (!empty($filter_args) && is_array($filter_args)) {
					$available_fields = avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);

					foreach ($filter_args as $item) {

						if (!isset($available_fields[$item]['get_draw_data'])) {
							continue;
						}

						if (!avalon23()->filter_items->get_by_field_key($filter_id, $item)['is_active']) {
							continue;
						}

						$filter_data[$item] = $available_fields[$item]['get_draw_data']($filter_id);
					}

					//+++
					//lets order it as described in $filter_args
					$tmp = [];

					foreach ($filter_args as $k) {
						if (isset($filter_data[$k])) {
							$tmp[$k] = $filter_data[$k];

							//for meta calendars, ---to is just for data range transmitting and not exists
							if (isset($tmp[$k]['view']) && 'calendar' === $tmp[$k]['view']) {
								$tmp[$k . '---to'] = $tmp[$k];
								$title = $tmp[$k]['title'];
								/* translators: %s is replaced with "string" */
								$tmp[$k]['title'] = Avalon23_Vocabulary::get(sprintf(esc_html__('From: %s', 'avalon23-products-filter'), sanitize_text_field($title)));
								/* translators: %s is replaced with "string" */
								$tmp[$k . '---to']['title'] = Avalon23_Vocabulary::get(sprintf(esc_html__('To: %s', 'avalon23-products-filter'), sanitize_text_field($title)));
							}
						}
					}

					$filter_data = $tmp;
				}

				//***	 

				if (null == avalon23()->filters->get($filter_id)['options']) {
					$filter_data['filter_options'] = array();
				} else {
					$filter_data['filter_options'] = (array) json_decode(avalon23()->filters->get($filter_id)['options'], true);
				}
				
				$filter_query = array();
				if (isset($this->current_request[$filter_id])) {
					$filter_query = $this->current_request[$filter_id];
				}

				$filter_data['filter_options']['filter_data'] = $filter_query;
				$filter_data['filter_options']['avalon_prefix'] = $this->generate_filter_prefix($filter_id, $filter_data['filter_options']);
				$filter_data['filter_options']['autosubmit'] = avalon23()->filter_items->options->get($filter_id, 'autosubmit', 'no');
				$filter_data['filter_options']['ajax_mode'] = avalon23()->filter_items->options->get($filter_id, 'ajax_mode', 0);
				$filter_data['filter_options']['reset_text'] = avalon23()->filter_items->options->get($filter_id, 'reset_text', '');
				$filter_data['filter_options']['filter_text'] = avalon23()->filter_items->options->get($filter_id, 'filter_text', '');
				$filter_data['filter_options']['btn_position'] = avalon23()->filter_items->options->get($filter_id, 'btn_position', 'b');
				$filter_data['filter_options']['filter_navigation'] = avalon23()->filter_items->options->get($filter_id, 'filter_navigation', 'b');
				$filter_data['filter_options']['filter_navigation_additional'] = ( Avalon23_Settings::get('filter_navigation_container') != -1 ) ? Avalon23_Settings::get('filter_navigation_container') : '';
				$filter_data['filter_options']['filter_id'] = $filter_id;
				$filter_data['filter_options']['is_mobile'] = wp_is_mobile();
				$filter_data['filter_options']['shop_page'] = get_permalink(wc_get_page_id('shop'));
				$filter_data['filter_options']['debag_mode'] = ( isset($_GET['avalon23_debag']) && sanitize_text_field($_GET['avalon23_debag']) ) ? intval(sanitize_text_field($_GET['avalon23_debag'])) : 0;

				//url  parser
				$filter_data['filter_options']['init_url'] = $this->url_parser->check_if_init($filter_id);
				$filter_data['filter_options']['search_url'] = $this->url_parser->get_exact_prefix($filter_id);
				$filter_data['filter_options']['all_search_urls'] = $this->url_parser->get_filter_prefix();
				//to do add pseudonym - replace filter keys
				
				
				$filter_data['filter_options']['current_tax'] = null;
				$curr_term = $this->current_category;
				if ($curr_term) {
					$filter_data['filter_options']['current_tax'] = array(
						'taxonomy' => $curr_term->taxonomy,
						'term_id' => $curr_term->term_id
					);
				}
				$filter_data['filter_options']['_wpnonce'] = wp_create_nonce('avalon23-nonce');
				
				$filter_data['filter_options']['ajax_selectors'] = array(
					'product_container' => ( Avalon23_Settings::get('product_container') != -1 ) ? Avalon23_Settings::get('product_container') : '.products',
					'pagination_container' => ( Avalon23_Settings::get('pagination_container') != -1 ) ? Avalon23_Settings::get('pagination_container') : 'nav.woocommerce-pagination',
					'count_container' => ( Avalon23_Settings::get('count_container') != -1 ) ? Avalon23_Settings::get('count_container') : '.woocommerce-result-count',
					'no_products_found_container' => ( Avalon23_Settings::get('no_products_found_container') != -1 ) ? Avalon23_Settings::get('no_products_found_container') : '.woocommerce-info',
					'ajax_no_redraw' => apply_filters('avalon23_ajax_no_redraw_selector', '.woot_woocommerce_tables'), //WOOT compatibility
					'ajax_redraw' => apply_filters('avalon23_ajax_redraw_selector', $this->redraw_parts, $filter_data) //what redraw in ajax
				);

				//set cache here
				if (avalon23()->optimize->is_active('recount')) {
					$temp_query = $this->get_start_count_query();
					$temp_filter_request = $this->current_request;
					$temp_filter_request[$filter_id]['filter_id'] = $filter_id;
					$temp_query_args = $this->get_query($temp_query, $temp_filter_request, 'cache_count');
					avalon23()->optimize->set_ceched_filter_data($temp_query_args, $filter_id, $filter_data);
				}
				$this->filters_data[$filter_id] = $filter_data;
				return json_encode(apply_filters('avalon23_filter_redraw_data', $filter_data));
			}
		}

		return '';
	}
	public function generate_filter_prefix( $id, $options = array()) {
		
		$prefix = $this->avalon_prefix . $id . '_';
		
		return $prefix;
	}
	//assemble fields options
	public function get_field_drawing_data( $filter_id, $field_key ) {

		$available_fields = avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);

		$res = [];

		if (isset($available_fields[$field_key])) {

			$field_options = $available_fields[$field_key]['options'];
			$res['view'] = $available_fields[$field_key]['view'];

			if (!empty($field_options)) {
				foreach ($field_options as $option_key) {

					switch ($option_key) {
						case 'min':
						case 'max':
							$res[$option_key] = avalon23()->optimize->is_active('transient');
							if ('price' == $field_key) {
								$res[$option_key] = false;
							}

							if ($res[$option_key]) {
								
								$res[$option_key] = avalon23()->optimize->get_min_max_meta($field_key . $filter_id, $option_key);
								
							}
							if (!$res[$option_key]) {

								$res[$option_key] = $this->get_min_max_field($field_key, $option_key, $filter_id);
								if (avalon23()->optimize->is_active('transient')) {
									avalon23()->optimize->set_min_max_meta($field_key . $filter_id, $option_key, $res[$option_key]);
								}
							}


							break;
						case 'dynamic_recount':
						case 'show_count':
						case 'hide_empty_terms':
							$value = avalon23()->filter->options->get_option($filter_id, $field_key, "{$field_key}-{$option_key}");
							if (-1 == $value || null == $value) {
								$def_value = 0;
								if ('show_count' == $option_key) {
									$def_value = 1;
								}
								$value = avalon23()->filter_items->options->get($filter_id, $option_key, $def_value);
							}
							$res[$option_key] = $value;


							break;
						case 'placeholder':
							$res[$option_key] = Avalon23_Vocabulary::get($this->options->get_option($filter_id, $field_key, "{$field_key}-{$option_key}"));
							break;
						default:
							$res[$option_key] = $this->options->get_option($filter_id, $field_key, "{$field_key}-{$option_key}");
							break;
					}
				}
			}

			if (isset($available_fields[$field_key]['get_count'])) {
				$value = '';
				if (isset($this->current_request[$filter_id][$field_key])) {
					$value = $this->current_request[$filter_id][$field_key];
				}

				$res['count'] = $available_fields[$field_key]['get_count']( $filter_id, $value);
			} else {
				$res['count'] = -1;
			}
		}

		$res['title'] = Avalon23_Vocabulary::get(avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['title']);
		$res['width_sm'] = avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['width_sm'];
		$res['width_md'] = avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['width_md'];
		$res['width_lg'] = avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['width_lg'];

		return $res;
	}

	public function get_min_max_field( $field_key, $option_key, $filter_id ) {
		$value = -1;

		if ($filter_id > 0) {
			$value = avalon23()->filter->options->get_option($filter_id, $field_key, "{$field_key}-{$option_key}");
		}

		if ( ( !is_numeric($value) || intval($value) === -1 ) && 'price' == $field_key) {
			$prices = $this->get_filtered_price($filter_id);
			if ($prices) {
				$p = $option_key . '_price';

				$price = (int) $prices->$p;
				$tax_display_mode = get_option('woocommerce_tax_display_shop');
				if (wc_tax_enabled() && !wc_prices_include_tax() && 'incl' === $tax_display_mode) {
					$tax_class = apply_filters('woocommerce_price_filter_widget_tax_class', ''); // Uses standard tax class.
					$tax_rates = WC_Tax::get_rates($tax_class);

					if ($tax_rates) {
						$price += WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($price, $tax_rates));
					}
				}

				return $price;
			}
			
			return 0;
		}

		if (!is_numeric($value) || intval($value) === -1) {
			global $wpdb;
			if ('min' == $option_key) {
				$value = $wpdb->get_var($wpdb->prepare("SELECT min(cast(meta_value as UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key=%s", '_' . $field_key));
			} else {
				$value = $wpdb->get_var($wpdb->prepare("SELECT max(cast(meta_value as UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key=%s", '_' . $field_key));
			}

			if (null == $value || 0 == $value) {
				if ('min' == $option_key) {
					$value = $wpdb->get_var($wpdb->prepare("SELECT min(cast(meta_value as UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key=%s", $field_key));
				} else {
					$value = $wpdb->get_var($wpdb->prepare("SELECT max(cast(meta_value as UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key=%s", $field_key));
				}
				
				if (null == $value) {
					$value = 0;
				}

			}
		}
		return $value;
	}
	public function get_meta_drawing_data( $filter_id, $key) {

		$available_fields = avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);

		$image_size = apply_filters('avalon23_image_size', 'thumbnail', $key);
		$filter_data = [];
		$options = [];
		$options = avalon23()->filter_items->meta->get_meta_terms( $key, $filter_id );
		if (!empty($options)) {
			
			$view = $this->options->get_option($filter_id, $key, "{$key}-meta-front-view");
			if (!$view) {
				$view = 'select';
			}			
			$show_count = $this->options->get_option($filter_id, $key, "{$key}-show_count");
			if (-1 == $show_count || null == $show_count) {
				$show_count = avalon23()->filter_items->options->get($filter_id, 'show_count', 1);
			}

			$hide_empty_terms = $this->options->get_option($filter_id, $key, "{$key}-hide_empty_terms");
			if (-1 == $hide_empty_terms || null == $hide_empty_terms) {
				$hide_empty_terms = avalon23()->filter_items->options->get($filter_id, 'hide_empty_terms', 0);
			}	
			$show_title = $this->options->get_option($filter_id, $key, "{$key}-show_title");
			if (null == $show_title) {
				$show_title = 1;
			}
			$toggle = $this->options->get_option($filter_id, $key, "{$key}-toggle");
			if (null == $toggle) {
				$toggle = 'none';
			}	
			$filter_data = [
				'title' => Avalon23_Vocabulary::get( avalon23()->filter_items->get_by_field_key($filter_id, $key)['title'] ),
				'show_title' => $show_title,
				'view' => $view,
				'options' => [],
				'show_count' => $show_count,
				'hide_empty_terms' => $hide_empty_terms,
				'toggle' => $toggle,
				'multiple' => $this->options->get_option($filter_id, $key, "{$key}-as-mselect"),
				'width_sm' => avalon23()->filter_items->get_by_field_key($filter_id, $key)['width_sm'],
				'width_md' => avalon23()->filter_items->get_by_field_key($filter_id, $key)['width_md'],
				'width_lg' => avalon23()->filter_items->get_by_field_key($filter_id, $key)['width_lg'],
				'template' => $this->options->get_option($filter_id, $key, "{$key}-checkbox_template"),
			];

			foreach ($options as $m_value => $m_title) { 		
				$count = $available_fields[$key]['get_count']($filter_id, $m_value);
				$image = null;
				$color = null;
				if ('image' == $view) {
					$m_value_id = sanitize_key($m_value);
					$image = (int) $this->options->get_option($filter_id, "{$key}", "{$key}-meta-image_{$m_value_id}");
					if ($image) {
						$image = wp_get_attachment_image_url($image, $image_size);
					} else {
						$image = AVALON23_LINK . 'assets/img/not-found.jpg';
					}
				}				
				if ('color' == $view) {
					$m_value_id = sanitize_key($m_value);
					$image_id = (int) $this->options->get_option($filter_id, "{$key}", "{$key}-meta-color_img_{$m_value_id}");
					if ($image_id) {
						$image = wp_get_attachment_image_url($image_id, $image_size);
					}
					$color = $this->options->get_option($filter_id, "{$key}", "{$key}-meta-color_{$m_value_id}");
					if (!$color) {
						$color = '#000000';
					}
				}

				$image = apply_filters('avalon23_image_url', $image, array($m_value => $m_title));				
				
				$filter_data['options'][] = [
					'id' => $m_value,
					'title' => Avalon23_Vocabulary::get($m_title),
					'count' => $count,
					'parent' => 0,
					'image' => $image,
					'color' => $color					
				];				
			}
				
		}
		return $filter_data;
	}
	public function get_taxonomy_drawing_data( $filter_id, $taxonomy ) {
		global $wp_taxonomies;
		$image_size = apply_filters('avalon23_image_size', 'thumbnail', $taxonomy);
		$tax_args = array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false
		);
		$curr_term = $this->current_category;
		//if ($curr_term) {
			//if ($curr_term->taxonomy == $taxonomy) {
				//$tax_args['child_of'] = $curr_term->term_id;
			//}
		//}
		$terms = get_terms(apply_filters('avalon23_taxonomy_arg', $tax_args));
		
		$available_fields = avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);


		$filter_data = [];

		if (!empty($terms)) {
			if (isset($wp_taxonomies[$taxonomy])) {

				$view = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-front-view");
				if (!$view) {
					$view = 'select';
				}

				$show_count = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-show_count");
				if (-1 == $show_count || null == $show_count) {
					$show_count = avalon23()->filter_items->options->get($filter_id, 'show_count', 1);
				}

				$hide_empty_terms = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-hide_empty_terms");
				if (-1 == $hide_empty_terms || null == $hide_empty_terms) {
					$hide_empty_terms = avalon23()->filter_items->options->get($filter_id, 'hide_empty_terms', 0);
				}

				$title = Avalon23_Vocabulary::get($this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-tax_title"));
				if (-1 == $title || null == $title) {
					$title = Avalon23_Vocabulary::get($wp_taxonomies[$taxonomy]->label);
				}

				
				$show_title = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-show_title");
				if (null == $show_title) {
					$show_title = 1;
				}
				$toggle = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-toggle");
				if (null == $toggle) {
					$toggle = 'none';
				}

				$filter_data = [
					'title' => $title,
					'type' => 'taxonomy',
					'show_title' => $show_title,
					'view' => $view,
					'options' => [],
					'show_count' => $show_count,
					'hide_empty_terms' => $hide_empty_terms,
					'toggle' => $toggle,
					'multiple' => $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-as-mselect"),
					'width_sm' => avalon23()->filter_items->get_by_field_key($filter_id, $taxonomy)['width_sm'],
					'width_md' => avalon23()->filter_items->get_by_field_key($filter_id, $taxonomy)['width_md'],
					'width_lg' => avalon23()->filter_items->get_by_field_key($filter_id, $taxonomy)['width_lg'],
					'template' => $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-checkbox_template"),
				];
				if ('hierarchy_dd' == $view) {
					$filter_data['hierarchy_title'] = Avalon23_Vocabulary::get($this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-hierarchy-title"));
					$filter_data['show_hierarchy_images'] = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-show_hierarchy_images");	
					$filter_data['hierarchy_images'] = array();
					$levels = (int) AVALON23_HELPER::get_taxonomy_level_count_max($terms);
					for ($i = 0; $i < $levels; $i++) {
						$src = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-hierarchy_images_{$i}");
						if ($src) {
							$filter_data['hierarchy_images'][] = wp_get_attachment_image_url($src, $image_size);
						} else {
							$filter_data['hierarchy_images'][] = -1;
						}
					}
				}

				$include_only = [];
				$exclude = [];

				if ($filter_id > 0) {
					$include_only = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-include");
					if ($include_only) {
						$include_only = array_map(function( $id ) {
							return intval($id);
						}, explode(',', $include_only));
					} else {
						$include_only = [];
					}
					$exclude = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-exclude");
					if ($exclude) {
						$exclude = array_map(function( $id ) {
							return intval($id);
						}, explode(',', $exclude));
					} else {
						$exclude = [];
					}
				}

				$term_ids = [];
				$all_terms_ids = [];
				foreach ($terms as $t) {
					if (is_object($t)) {
						$all_terms_ids[] = $t->term_id;
					}
				}				
				foreach ($terms as $t) {
					if (is_object($t)) {

						if (in_array($t->term_id, $exclude)) {
							continue;
						}
						if (in_array($t->term_id, $term_ids)) {
							continue;
						}
						if (!empty($include_only)) {
							if (!in_array($t->term_id, $include_only)) {
								continue;
							}
						}


						$count = -1;
						if (isset($available_fields[$taxonomy])) {
							$count = $available_fields[$taxonomy]['get_count']($filter_id, $t);
						}
						$image = null;
						$color = null;
						if ('image' == $view) {
							$t_origin = AVALON23_HELPER::get_term_for_default_lang($t);
							$image = (int) $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-image_{$t_origin->slug}");
							if ($image) {
								$image = wp_get_attachment_image_url($image, $image_size);
							} else {
								$image = AVALON23_LINK . 'assets/img/not-found.jpg';
							}
						}
						if ('color' == $view) {
							$t_origin = AVALON23_HELPER::get_term_for_default_lang($t);
							$image_id = (int) $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-color_img_{$t_origin->slug}");
							if ($image_id) {
								$image = wp_get_attachment_image_url($image_id, $image_size);
							}
							$color = $this->options->get_option($filter_id, "{$taxonomy}", "{$taxonomy}-color_{$t_origin->slug}");
							if (!$color) {
								$color = '#000000';
							}
						}

						$image = apply_filters('avalon23_image_url', $image, $t);

						$filter_data['options'][] = [
							'id' => $t->term_id,
							'title' => Avalon23_Vocabulary::get($t->name),
							'count' => $count,
							'slug' => $t->slug,
							'parent' => in_array($t->parent, $all_terms_ids)? $t->parent: 0,
							'image' => $image,
							'color' => $color
						];
						$term_ids[] = $t->term_id;
					}
				}
			}
		}

		return $filter_data;
	}

	public function generate_query_arg( $query_args, $loop_name = '' ) {

		if (!empty($this->current_request) ) {

			$query_args = $this->get_query($query_args, $this->current_request, $loop_name);
		}
		return $query_args;
	}

	public function get_query( $query_args, $requests, $loop_name = 'avalon23' ) {

		if (!empty($requests)) {
			$query_args = apply_filters('avalon23_before_parse_query_args', $query_args, $requests, $loop_name);
			
			foreach ($requests as $id_f => $request) {
				if (!isset($request['filter_id']) ) {
					continue;
				}
				$filter_id = $request['filter_id'];
				$available_fields =  avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);
				if (is_array($available_fields)) {

					foreach ($available_fields as $key => $data) {
						if (!isset($data['get_query_args'])) {
							continue;
						}
						$query_func = $data['get_query_args'];
						if (strlen($key) > 5 && isset($request[$key . '---to'])) {
							$query_args = $query_func($query_args, $request[$key], true);
						} elseif (isset($request[$key])) {
							$query_args = $query_func($query_args, $request[$key]);
						}
					}
				}
				$query_args = $this->generate_predefined_query($query_args, $filter_id, $loop_name);				

			}			
			$query_args = apply_filters('avalon23_after_parse_query_args', $query_args, $requests, $loop_name);
		}


		return $query_args;
	}

	public function shortcode_products_query( $query_args, $atts, $loop_name = 'avalon23' ) {

		$query_args = $this->generate_query_arg($query_args, $loop_name);

		return $query_args;
	}

	public function parse_woo_query( $wp_query, $_this = null ) {
		$query_vars = $wp_query->query_vars;

		if (isset($query_vars['wc_query']) && 'product_query' == $query_vars['wc_query']) {
			$loop_name = 'shop';
			$query_args = $this->generate_query_arg($query_vars, $loop_name);
			
			foreach ($query_args as $key => $val) {
				$wp_query->set($key, $val);
			}

			$wp_query->is_search = false;
		}

		return $wp_query;
	}

	//dynamic  recount

	public function get_current_taxonomy( $term = null ) {
		if (!$this->current_category) {
			if (is_object(get_queried_object()) && 'WP_Term' == get_class(get_queried_object())) {
				$this->current_category = get_queried_object();
			}
		}
		return $this->current_category;
	}

	public function get_current_taxonomy_query( $term ) {
		$query = array();
		if ($term) {
			$query = array(
				'taxonomy' => $term->taxonomy,
				'field' => 'id',
				'terms' => array($term->term_id),
			);
		}
		return $query;
	}

	public function get_field_count( $key = '', $value = '', $filter_id = -1 ) {
		$count = -1;
		$query_vars = $this->get_start_count_query();
		$filter_request = $this->current_request;
		
		if ($key && $value) {
			$filter_request[$filter_id][$key] = $value;
		}

		$filter_request[$filter_id]['filter_id'] = $filter_id;

		$query_args = $this->get_query($query_vars, $filter_request, 'count');



		$q = new WP_Query($query_args);

		$count = $q->post_count;

		return $count;
	}
	public function get_meta_count( $key, $value, $filter_id  ) {
		$count = -1;
		$query_vars = $this->get_start_count_query();
		$filter_request = $this->current_request;

		$filter_request[$filter_id][$key] = $value;
		$filter_request[$filter_id]['filter_id'] = $filter_id;

		$query_args = $this->get_query($query_vars, $filter_request, 'count');

		//fix  for not standard logic
		$logic = avalon23()->filter->options->get_option($filter_id, $key, "{$key}-meta-logic");
		if ('NOT IN' == $logic ) {
			if (isset($query_args['meta_query']) && is_array($query_args['meta_query'])) {
				foreach ($query_args['meta_query'] as $key => $item) {
					if ( is_array($item) ) {
						foreach ($item as $dkey => $ditem) {
							if (isset($ditem['key']) && $ditem['key'] == $key) {		
								if (isset($ditem['compare']) && 'NOT IN' == $ditem['compare']) {
									$query_args['meta_query'][$key][$dkey]['compare'] = 'IN';
									$query_args['meta_query'][$key] = $query_args['meta_query'][$key][$dkey];
								}
							}							
						}
					}
				}
			}
		}		
		$q = new WP_Query($query_args);
		$count = $q->post_count;

		return $count;
	}
	public function get_tax_count( $term, $filter_id, $dynamic_recount = false ) {
		if (!$dynamic_recount) {
			$count = $term->count;
		} else {
			$query_vars = $this->get_start_count_query();
			$filter_request = $this->current_request;

			$filter_request[$filter_id][$term->taxonomy] = $term->term_id;
			$filter_request[$filter_id]['filter_id'] = $filter_id;

			$query_args = $this->get_query($query_vars, $filter_request, 'count');

			//fix  for not standard logic
			$logic = avalon23()->filter->options->get_option($filter_id, "{$term->taxonomy}", "{$term->taxonomy}-mselect-logic");
			if ('NOT IN' == $logic || 'AND' == $logic) {
				if (isset($query_args['tax_query']) && is_array($query_args['tax_query'])) {
					foreach ($query_args['tax_query'] as $key => $item) {
						if (isset($item['taxonomy']) && $item['taxonomy'] == $term->taxonomy) {
							if (isset($item['operator']) && 'NOT IN' == $item['operator']) {
								$query_args['tax_query'][$key]['operator'] = 'IN';
							} elseif (isset($item['operator']) && 'AND' == $item['operator']) {
								if (isset($this->current_request[$filter_id][$term->taxonomy])) {
									$terms = explode(',', $this->current_request[$filter_id][$term->taxonomy]);
									$query_args['tax_query'][$key]['terms'] = array_merge($query_args['tax_query'][$key]['terms'], $terms);
								}
							}
						}
					}
				}
			}
			//*********

			$q = new WP_Query($query_args);

			$count = $q->post_count;
		}
		return $count;
	}

	public function get_start_count_query() {
		$query = array(
			'nopaging' => true,
			'fields' => 'ids',
			'post_type' => 'product',
			'post_status' => 'publish',
			'tax_query' => array(),
			'meta_query' => array()
		);
//		if(isset($_GET['s']) AND  $_GET['s']){
//			$query['s']=$_GET['s'];
//		}
		$visibility = $this->get_current_taxonomy_query($this->get_current_taxonomy());
		if (!empty($visibility)) {
			$query['tax_query'][] = $visibility;
		}
		$query['tax_query'] = $this->get_visibility_query($query['tax_query']);

		return $query;
	}

	public function init_shortcode_no_products_found() {

		$shortcodes = array('products', 'recent_products', 'sale_products', 'best_selling_products', 'top_rated_products', 'featured_products', 'product_category');
		foreach ($shortcodes as $name) {
			add_action('woocommerce_shortcode_' . $name . '_loop_no_results', array($this, 'no_products_found'));
		}
		do_action('woocommerce_no_products_found');
	}

	public function no_products_found() {
		if ($this->current_request) {
			do_action('woocommerce_no_products_found');
		}
	}

	public function get_visibility_query( $tax_query ) {
		foreach ($tax_query as $key => $tax) {
			if (isset($tax['taxonomy']) && 'product_visibility' == $tax['taxonomy']) {
				unset($tax_query[$key]);
			}
		}
		$keys = array(
			'exclude-from-search',
			'exclude-from-catalog'
		);
		$arr_ads = wc_get_product_visibility_term_ids();
		$product_not_in = array();
		if (!is_array($keys)) {
			$keys = array($keys);
		}
		foreach ($keys as $key) {
			if (isset($arr_ads[$key]) || ! empty($arr_ads[$key])) {
				$product_not_in[] = $arr_ads[$key];
			}
		}
		if (!empty($product_not_in)) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field' => 'term_taxonomy_id',
				'terms' => $product_not_in,
				'operator' => 'NOT IN',
			);
		}

		return $tax_query;
	}

	public function get_main_search_query_sql( $search_terms, $tax_search = 0, $meta_search = '' ) {
		global $wpdb;

		if (!is_array($search_terms)) {
			$search_terms = explode(' ', $search_terms);
		}

		$sql = array();

		foreach ($search_terms as $term) {
			// Terms prefixed with '-' should be excluded.
			$include = '-' !== substr($term, 0, 1);

			if ($include) {
				$like = '%' . $wpdb->esc_like($term) . '%';
				if ($tax_search && $meta_search) {
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s) OR ( trm.name LIKE %s) OR ( postmeta.meta_key = %s AND  postmeta.meta_value LIKE %s))", $like, $like, $like, $like, $meta_search, $like);
				} elseif ($tax_search) {
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s) OR ( trm.name LIKE %s) )", $like, $like, $like, $like); // unprepared SQL ok.
				} elseif ($meta_search) { 
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s) OR ( postmeta.meta_key = %s AND  postmeta.meta_value LIKE %s))", $like, $like, $like, $meta_search, $like);
				} else {
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s))", $like, $like, $like); // unprepared SQL ok.
				}
				
			} else {
				$term = substr($term, 1);
				$like = '%' . $wpdb->esc_like($term) . '%';
				if ($tax_search && $meta_search) {
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title NOT LIKE %s) AND ($wpdb->posts.post_excerpt NOT LIKE %s) AND ($wpdb->posts.post_content NOT LIKE %s) AND (trm.name NOT LIKE %s) AND ( postmeta.meta_key = %s AND  postmeta.meta_value NOT LIKE %s))", $like, $like, $like, $like, $meta_search, $like);
				} elseif ($tax_search) {
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title NOT LIKE %s) AND ($wpdb->posts.post_excerpt NOT LIKE %s) AND ($wpdb->posts.post_content NOT LIKE %s) AND (trm.name NOT LIKE %s))", $like, $like, $like, $like); // unprepared SQL ok.
				} elseif ($meta_search) { 
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title NOT LIKE %s) AND ($wpdb->posts.post_excerpt NOT LIKE %s) AND ($wpdb->posts.post_content NOT LIKE %s) AND ( postmeta.meta_key = %s AND  postmeta.meta_value NOT LIKE %s))", $like, $like, $like, $meta_search, $like);
				} else {
					$sql[] = $wpdb->prepare("(($wpdb->posts.post_title NOT LIKE %s) AND ($wpdb->posts.post_excerpt NOT LIKE %s) AND ($wpdb->posts.post_content NOT LIKE %s))", $like, $like, $like); // unprepared SQL ok.
				}
				
			}

		}
//		if (!empty($sql) && !is_user_logged_in()) {
//			$sql[] = "($wpdb->posts.post_password = '')";
//		}

		return apply_filters('avalon23_text_search_where', implode(' AND ', $sql), $search_terms, $tax_search, $meta_search);
	}

	public function generate_predefined_query( $query, $filter_id, $loop_name = '' ) {
		$predefinition = avalon23()->predefinition->get($filter_id);

		foreach ($predefinition as $key => $value) {
			switch ($key) {
				case 'featured_only':
					if (1 == $value) {
						$query['tax_query'][] = array(
							'taxonomy' => 'product_visibility',
							'field' => 'name',
							'terms' => 'featured',
						);
					}
					break;
				case 'on_sale_only':
					if (1 == $value) {
						if (!isset($query['post__in']) || ! is_array($query['post__in'])) {
							$query['post__in'] = array();
						}
						if (!empty($query['post__in'])) {
							$query['post__in'] = array_intersect($query['post__in'], wc_get_product_ids_on_sale() ? wc_get_product_ids_on_sale() : [0]);
						} else {
							$query['post__in'] = wc_get_product_ids_on_sale();
						}
					}

					break;
				case 'ids':
				case 'ids_exclude':
					if (-1 != $value && is_string($value)) {
						$arg_name = 'post__in';
						if ('ids_exclude' == $key) {
							$arg_name = 'post__not_in';
						}
						if (!isset($query[$arg_name]) || ! is_array($query[$arg_name])) {
							$query[$arg_name] = array();
						}
						$ids = AVALON23_HELPER::string_id_array($value);
						$query[$arg_name] = array_merge($query[$arg_name], $ids);
					}
					break;
				case 'sku':
				case 'sku_exclude':
					if (-1 != $value && $value) {
						if (!isset($query['meta_query']) || ! is_array($query['meta_query'])) {
							$query['meta_query'] = array();
						}
						$compare = 'IN';
						if ('sku_exclude' == $key) {
							$compare = 'NOT IN';
						}
						$slugs = AVALON23_HELPER::string_slugs_array($value);
						if (!empty($slugs)) {
							$meta = array(
								'relation' => 'OR',
								array(
									'key' => '_sku',
									'value' => $compare,
									'compare' => 'IN'
								)
							);
							$query['meta_query'][] = $meta;
						}
					}

					break;
				case 'authors':
					if (-1 != $value && $value) {
						if (!isset($query['author__in']) || ! is_array($query['author__in'])) {
							$query['author__in'] = array();
						}
						$ids = AVALON23_HELPER::string_id_array($value);
						$query['author__in'] = array_merge($query['author__in'], $ids);
					}

					break;
				case 'bestsellers':
					if (-1 != $value && intval($value)) {
						$value = intval($value);
						$query['meta_key'] = 'total_sales';
						$query['orderby'] = 'meta_value_num';
						if ('count' == $loop_name) {
							$query['posts_per_page'] = $value;
							$query['nopaging'] = false;
						}
					}

					break;
				case 'newest':
					if (-1 != $value  && intval($value)) {
						$value = intval($value);
						$query['orderby'] = 'date';
						$query['order'] = 'DESC';
						if ('count' == $loop_name) {
							$query['posts_per_page'] = $value;
							$query['nopaging'] = false;
						}
					}
					break;
				case 'by_taxonomy':
				case 'not_by_taxonomy':
					if (-1 != $value && $value) {
						$taxonomies = array();
						$datas = explode('|', $value);
						$operator = 'IN';
						if ('not_by_taxonomy' == $key) {
							$operator = 'NOT IN';
						}
						foreach ($datas as $part) {
							$data = explode(':', $part, 2);
							if ('rel' == $data[0] && isset($data[1])) {
								$taxonomies['relation'] = $data[1];
								continue;
							}
							if (isset($data[1])) {
								$ids = AVALON23_HELPER::string_id_array($data[1]);
								//WPML compatibility
								if (class_exists('SitePress')) {
									$slugs = array();								
									foreach ($ids as $id) {
										$term = get_term( $id, $data[0] );
										$slugs[] = $term->slug;										
									}
									$taxonomies[] = array(
										'taxonomy' => $data[0],
										'field' => 'slug',
										'terms' => $slugs,
										'operator' => $operator,
									);									
								} else {
									$taxonomies[] = array(
										'taxonomy' => $data[0],
										'field' => 'id',
										'terms' => $ids,
										'operator' => $operator,
									);									
								}
							}
						}
						if (!empty($taxonomies)) {
							if (!isset($query['tax_query']) || ! is_array($query['tax_query'])) {
								$query['tax_query'] = array();
							}
							$query['tax_query'][] = $taxonomies;
						}
					}

					break;
				case 'in_stock_only':
					if (1 == $value ) {
						$outofstock_term = get_term_by('name', 'outofstock', 'product_visibility');
						if ($outofstock_term) {
							if (!isset($query['tax_query']) || ! is_array($query['tax_query'])) {
								$query['tax_query'] = array();
							}
							$query['tax_query'][] = array(
								'taxonomy' => 'product_visibility',
								'field' => 'term_taxonomy_id',
								'terms' => array($outofstock_term->term_taxonomy_id),
								'operator' => 'NOT IN'
							);
							if (!isset($query['meta_query']) || ! is_array($query['meta_query'])) {
								$query['meta_query'] = array();
							}
							$query['meta_query'][] = array(
								'key' => '_stock_status',
								'value' => 'outofstock',
								'compare' => 'NOT LIKE',
							);
						}
						$outstock_posts = $this->get_instock_variable_ids();
						if ($outstock_posts) {
							if (!isset($query['post__not_in']) || ! is_array($query['post__not_in'])) {
								$query['post__not_in'] = array();
							}

							$query['post__not_in'] = array_merge($query['post__not_in'], $outstock_posts);
						}
					}
					break;
				default:
			}
		}
		return $query;
	}

	public function get_instock_variable_ids() {

		global $wpdb;
		$products = array();
		$requests = $this->current_request;

		$prod_attributes = array();
		$ids = avalon23()->filters->get_ids();
		foreach ($ids as $item) {
			if (isset($requests[$item['id']])) {
				$request = $requests[$item['id']];
				foreach ($request as $key => $value) {
					if (substr($key, 0, 3) == 'pa_') {
						$prod_attributes[$key] = $value;
					}
				}				
			}
		}


		if (!empty($prod_attributes)) {

			
			$new_db = apply_filters('avalon23_use_new_db_in_stock', true);
			if ( ! $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "wc_product_attributes_lookup'") == $wpdb->prefix . 'wc_product_attributes_lookup' ) {
				$new_db = false;
			}
			if ($new_db) { 
				$attr_in_search = array();
				foreach ($prod_attributes as $attr_slug => $values) {
					$term_ids = explode(',', $values);
					if (!empty($term_ids)) {
						$attr_in_search[$attr_slug] = $term_ids;
					}
				}

				//generate SQL
				$attr_sql = array();
				$attr_sql2 = array();
				$prep_data = [];
				foreach ($attr_in_search as $tax=>$ids) {
					$attr_sql[] = "(a.taxonomy = '" . $tax . "' AND a.term_id IN (" . implode(', ', $ids) . '))';					
					$attr_sql_t[] = '(a.taxonomy =%s AND a.term_id  IN (' . implode(', ', array_fill(0, count($ids), '%d')) . '))'; 
					
					$attr_sql2[] = "(a.taxonomy = '" . $tax . "' AND a.term_id IN (" . implode(', ', $ids) . ') AND a.in_stock=1) ';					
					$attr_sql2_t[] = '(a.taxonomy =%s AND a.term_id  IN (' . implode(', ', array_fill(0, count($ids), '%d')) . ') AND a.in_stock=1)'; 				

					$prep_data = array_merge($prep_data , array($tax), $ids);
				}
				$sql_str = implode(' OR ', $attr_sql_t);
				$sql_str2 = implode(' OR ', $attr_sql2_t);
				$parent_ids = $this->db->get_col('SELECT a.product_or_parent_id '
						. 'FROM ' . $wpdb->prefix . 'wc_product_attributes_lookup a '
						. 'JOIN  ' . $wpdb->prefix . 'wc_product_attributes_lookup b on b.product_id=a.product_id '
						. 'WHERE a.is_variation_attribute = 1'
						//taxonomy
						. ' AND ( '
						//. '( ' . implode(' OR ', $attr_sql) . ' )'
						. '( ' . $this->db->prepare($sql_str, $prep_data) . ' )'
						. ' AND a.in_stock=0 ) '

						. ' GROUP BY a.product_or_parent_id');		
				
				$instock_parent_ids = $this->db->get_col('SELECT a.product_or_parent_id '
						. 'FROM ' . $wpdb->prefix . 'wc_product_attributes_lookup a '
						. 'JOIN  ' . $wpdb->prefix . 'wc_product_attributes_lookup b on b.product_id=a.product_id '
						. 'WHERE a.is_variation_attribute = 1'
						//taxonomy
						. ' AND '
						//. '( ' . implode(' OR ', $attr_sql2) . ' )'
						. '( ' . $this->db->prepare($sql_str2, $prep_data) . ' )'
						. ' AND a.in_stock=1 '																
						. ' GROUP BY a.product_id');
				
				$products = array_diff($parent_ids, $instock_parent_ids);	

			} else {
				
				$meta_query = array('relation' => 'AND');
				$meta_query[] = array(
					'key' => '_stock_status',
					'value' => 'outofstock'
				);
				$sub_meta_query = array('relation' => 'OR');

				foreach ($prod_attributes as $attr_slug => $attr_ids) {
					$attr_ids = explode(',', $attr_ids);
					for ($i = 0; $i < count($attr_ids); $i++) {
						$term = get_term($attr_ids[$i], $attr_slug);
						$slug = $term->slug;
						$sub_meta_query[] = array(
							'key' => 'attribute_' . $attr_slug,
							'value' => $slug
						);
					}
				}

				$meta_query[] = array($sub_meta_query);


				$args = array(
					'nopaging' => true,
					'suppress_filters' => true,
					'post_type' => array('product_variation'),
					'meta_query' => $meta_query
				);
				$query = new WP_Query($args);
				//print_r($query);exit;			
				if ($query->have_posts()) {
					foreach ($query->posts as $p) {
						$products[] = $p->post_parent;
					}
				}
				$products = array_unique($products);
				//exit				
			}
			
		}


		return $products;
	}
	public function get_filter_id_by_key( $key ) {
		$id = false;
		
		$regexp = '/(?<=^' . $this->avalon_prefix . ')([0-8]*)(?=_)/i';
		
		preg_match($regexp , $key, $matches);
		if (isset( $matches[0] )) {
			$id = (int) $matches[0];
		}
		
		return $id;
	}
	public function get_filter_data_by_key( $key, $request) {
		
		foreach ($request as $fkey => $value) {
			$regexp = '/(?<=^' . $this->avalon_prefix . ')([0-8]*)(?=_)/i';

			$regexp = '/^' . $this->avalon_prefix . '[0-9]*_/i'; 
			if (preg_replace($regexp, '', $fkey) == $key) {
				return $value;
			}
		}
		
		return false;
	}	
	

	public function get_filtered_price( $filter_id ) {
		$min_max = apply_filters('avalon23_min_max_prices', false, $filter_id);
		if ( $min_max ) {
			return $min_max;
		}

		$wid_obj = new Avalon23_Widget_Price_Filter( $filter_id );
		$min_max = $wid_obj->get_prices();

		return $min_max;
	}

	public function get_js_function_after_ajax( $js ) {
		return 'function avalon23_after_page_redraw(_this,response){ ' . stripcslashes($js) . ' }';
	}

}
