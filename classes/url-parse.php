<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Url_Parce {

	private $current_request = array();
	private $filter_prefix = array();


	public function __construct() {

		$this->init();
	}
	public function check_if_init ( $id ) {
		$filter_prefix = $this->get_filter_prefix();
		if (isset($filter_prefix[$id]) && $filter_prefix[$id]) {
			return true;
		}
		
		return false;
	}
	public function get_exact_prefix( $id ) {
		$filter_prefix = $this->get_filter_prefix();
		if (isset($filter_prefix[$id]) && $filter_prefix[$id]) {
			return $filter_prefix[$id];
		}
		
		return '';
	}	
	
	public function get_filter_prefix () {
		if (empty($this->filter_prefix)) {
			$ids = avalon23()->filters->get_ids();
			foreach ($ids as $item) {
				if (isset($item['id']) && $item['id']) {
					$prefix = avalon23()->filter_items->options->get($item['id'], 'filter_search_slug', '');
					
					if ($prefix && -1 != $prefix) {
						$this->filter_prefix[(int) $item['id']] = sanitize_title($prefix);
					}
				
				}
			}			
		}
		
		return $this->filter_prefix;
	}

	public function init() {
		
		add_action('avalon23_extend_options', array($this, 'add_options'), 99, 2);
		
		if (!is_admin()) {
			add_filter('do_parse_request', array($this, 'url_process'), 10, 3);
		}
	}

	public function url_process( $do, $WP, $extra_query_vars ) {

		if (!$this->get_url_request()) {
			return $do;
		}

		global $wp_rewrite;
		$post_data = $this->get_post();
		$get_data = $this->get_get();
		$self = isset($_SERVER['PHP_SELF']) ? sanitize_text_field($_SERVER['PHP_SELF']) : '';
		

		$WP->query_vars = [];
		$post_type_query_vars = [];

		if (is_array($extra_query_vars)) {
			$WP->extra_query_vars = & $extra_query_vars;
		} elseif (!empty($extra_query_vars)) {
			parse_str($extra_query_vars, $WP->extra_query_vars);
		}
		//Source wp-includes/class-wp.php
		$rewrite_rules = $wp_rewrite->wp_rewrite_rules();

		if (!empty($rewrite_rules )) {
			$path_info = isset($_SERVER['PATH_INFO']) ? sanitize_text_field($_SERVER['PATH_INFO']) : '';
			$error = '404';
			$WP->did_permalink = true;

			list( $path_info ) = explode('?', $path_info);
			$path_info= str_replace('%', '%25', $path_info);

			$cleared_url = $this->get_cleared_url($this->get_request_uri());

			list( $req_uri ) = explode('?', $cleared_url);
			$req_uri = str_replace($path_info, '', $req_uri);
			
			$req_uri = $this->check_url($req_uri);
			$path_info = $this->check_url($path_info);
			$self = $this->check_url($self);
			
			if (!empty($path_info) && !preg_match('|^.*' . $wp_rewrite->index . '$|', $path_info)) {
				$initial_path = $path_info;
			} else {

				if ($req_uri == $wp_rewrite->index) {
					$req_uri = '';
				}
				$initial_path = $req_uri;
			}
			$requested_file = $req_uri;

			$WP->request = $initial_path;
			// Look for matches.
			$request_match = $initial_path;

			if (empty($request_match)) {

				if (isset($rewrite_rules['$'])) {
					$WP->matched_rule = '$';
					$query_var = $rewrite_rules['$'];
					$matches = array('');
				}
			} else {

				foreach ((array) $rewrite_rules as $match => $query_var ) {
					// If the requesting file is the anchor of the match, prepend it
					// to the path info.
					if (!empty($requested_file) && strpos($match, $requested_file) === 0 && $requested_file != $initial_path) {
						$request_match = $requested_file . '/' . $initial_path;
					}

					if (preg_match("#^$match#", $request_match, $matches) ||
							preg_match("#^$match#", urldecode($request_match), $matches)) {

						if ($wp_rewrite->use_verbose_page_rules && preg_match('/pagename=\$matches\[([0-9]+)\]/', $query_var , $varmatch)) {
							// This is a verbose page match, let's check to be sure about it.
							$page = get_page_by_path($matches[$varmatch[1]]);
							if (!$page) {
								continue;
							}

							$post_status_obj = get_post_status_object($page->post_status);
							if (!$post_status_obj->public && !$post_status_obj->protected && !$post_status_obj->private && $post_status_obj->exclude_from_search) {
								continue;
							}
						}
						
						$WP->matched_rule = $match;
						break;
					}
				}
			}

			if (isset($WP->matched_rule)) {

				// Trim the query of everything up to the '?'.				
				$query_var  = preg_replace('!^.+\?!', '', $query_var );

				$query_var  = addslashes(\WP_MatchesMapRegex::apply($query_var, $matches));

				$WP->matched_query = $query_var;
				// Filter out non-public query vars
				parse_str($query_var, $perma_query_vars);
				// If we're processing a 404 request, clear the error var since we found something.

				if ('404' == $error) {
					unset($error, $get_data['error']);
					
				}
			}
			// If req_uri is empty or if it is a request for ourself, unset error.
			if (empty($initial_path) || $requested_file == $self || strpos($self , 'wp-admin/') !== false) {
				unset($error, $get_data['error']);
				
				if (isset($perma_query_vars) && strpos($self , 'wp-admin/') !== false && ( !isset($post_data['link']) )) {
					unset($perma_query_vars);
				}

				$WP->did_permalink = false;
				
			}
		}



		$WP->public_query_vars = apply_filters('query_vars', $WP->public_query_vars);

		foreach (get_post_types([], 'objects') as $post_type => $t) {
			if (is_post_type_viewable($t) && $t->query_var) {
				$post_type_query_vars[$t->query_var] = $post_type;
			}
		}

		foreach ($WP->public_query_vars as $wpvar) {
			if (isset($WP->extra_query_vars[$wpvar])) {
				$WP->query_vars[$wpvar] = $WP->extra_query_vars[$wpvar];
			} elseif (isset($get_data[$wpvar]) && isset($post_data[$wpvar]) && $get_data[$wpvar] !== $post_data[$wpvar]) {
				wp_die(esc_html__('Forbidden', 'avalon23-products-filter'), 400);
			} elseif (isset($post_data[$wpvar])) {
				$WP->query_vars[$wpvar] = $post_data[$wpvar];
			} elseif (isset($get_data[$wpvar])) {
				$WP->query_vars[$wpvar] = $get_data[$wpvar];
			} elseif (isset($perma_query_vars[$wpvar])) {
				$WP->query_vars[$wpvar] = $perma_query_vars[$wpvar];
			}

			if (!empty($WP->query_vars[$wpvar])) {
				if (!is_array($WP->query_vars[$wpvar])) {
					$WP->query_vars[$wpvar] = (string) $WP->query_vars[$wpvar];
				} else {
					foreach ($WP->query_vars[$wpvar] as $vkey => $v) {
						if (is_scalar($v)) {
							$WP->query_vars[$wpvar][$vkey] = (string) $v;
						}
					}
				}

				if (isset($post_type_query_vars[$wpvar])) {
					$WP->query_vars['post_type'] = $post_type_query_vars[$wpvar];
					$WP->query_vars['name'] = $WP->query_vars[$wpvar];
				}
			}
		}
		// Convert urldecoded spaces back into '+'.
		foreach (get_taxonomies(['object_type' => 'product'], 'objects') as $taxonomy => $t) {

			if ($t->query_var && isset($WP->query_vars[$t->query_var])) {
				$WP->query_vars[$t->query_var] = str_replace(' ', '+', $WP->query_vars[$t->query_var]);
			}
		}
		// Don't allow non-publicly queryable taxonomies to be queried from the front end.
		if (!is_admin()) {
			foreach (get_taxonomies(array('object_type' => 'product', 'publicly_queryable' => false), 'objects') as $taxonomy => $t) {

				if (isset($WP->query_vars['taxonomy']) && $taxonomy === $WP->query_vars['taxonomy']) {
					unset($WP->query_vars['taxonomy'], $WP->query_vars['term']);
				}
			}
		}
		// Limit publicly queried post_types to those that are 'publicly_queryable'.
		if (isset($WP->query_vars['post_type'])) {
			$queryable_post_types = get_post_types(array('publicly_queryable' => true));
			if (!is_array($WP->query_vars['post_type'])) {
				if (!in_array($WP->query_vars['post_type'], $queryable_post_types)) {
					unset($WP->query_vars['post_type']);
				}
			} else {
				$WP->query_vars['post_type'] = array_intersect($WP->query_vars['post_type'], $queryable_post_types);
			}
		}

		$WP->query_vars = wp_resolve_numeric_slug_conflicts($WP->query_vars);

		foreach ((array) $WP->private_query_vars as $var) {
			if (isset($WP->extra_query_vars[$var])) {
				$WP->query_vars[$var] = $WP->extra_query_vars[$var];
			}
		}

		if (isset($error)) {
			$WP->query_vars['error'] = $error;
		}


		$WP->query_vars = apply_filters('request', $WP->query_vars);

		global $wp_version;

		if ( version_compare($wp_version, '6.0') >= 0) {
			$WP->query_posts();
			$WP->handle_404();
			$WP->register_globals();
		} else {
			do_action_ref_array('parse_request', array(&$WP));			
		} 

		return false;
	}

	public function get_cleared_url ( $url ) {
		$request_url = $this->get_url_request($url);

		$url = str_replace($request_url, '/', $url);
		return $url;
	}

	public function get_url_request( $url = null ) {
		if (!$url) {
			$url = $this->get_request_uri();
		}
		if (!$this->current_request) {
			$this->current_request = $this->get_search_request($url);
		}
		return $this->current_request;
	}

	public function check_url( $url ) {
		$home = trim(parse_url(home_url(), PHP_URL_PATH) ?? '', '/');
		$home_regex = sprintf('|^%s|i', preg_quote($home, '|'));
		
		$url = trim($url, '/');
		$url = preg_replace($home_regex, '', $url);
		$url = trim($url, '/');		
		return $url;
	}
	public function get_search_request( $url ) {

		$cleared_url = $url;
		$clear_array = array('/page/', '?', '#');
		$request_url = '';
		foreach ($clear_array as $sign) {
			$tmp_url = explode($sign, $cleared_url, 2);
			$cleared_url = $tmp_url[0];
		}
		$filter_data = array();

		if ('/' != substr($cleared_url, -1)) {
			$cleared_url = $cleared_url . '/';
		}

		foreach ($this->get_filter_prefix() as $id => $prefix) {
			$pos = stripos($cleared_url, '/' . $prefix . '/');

			if (false !== $pos) {
				$request_url = substr($cleared_url, $pos);
				$filter_data['filter_id'] = $id;
				break;
			}
		}

		return $request_url;
	}
	public function create_request( $request ) {
		$url = $this->get_url_request();
		if ($url) {
			$query =  $this->parse_url_query($url);
			if ( isset($query['filter_id'])) {
				$request[$query['filter_id']] = $query;
			}

		}
		return $request;
	}
	public function parse_url_query( $url_query ) {
	
		$filter_data = array();
		$all_filter_data = array();
		$request_array = explode('/', trim($url_query, '/'));
		
		$filter_id = array_search($request_array[0], $this->get_filter_prefix());

		if (false === $filter_id) {
			return $filter_data;
		}

		$filter_data['filter_id'] = $filter_id;


		$ak = avalon23()->filter_items->get_acceptor_keys($filter_id);
		
		if (!empty($ak)) {
			$available_fields = avalon23()->get_available_fields( $filter_id );//apply_filters('avalon23_get_available_fields', [], $filter_id);
			foreach ($ak as $item) {
				$pseudonym = $item;//to do  filter  or  option
				$all_filter_data[$pseudonym]['name'] = $item;
				$all_filter_data[$pseudonym]['type'] = isset($available_fields[$item]['view']) ? $available_fields[$item]['view'] : '';
				$all_filter_data[$pseudonym]['options'] = array();
				if ('taxonomy' == $all_filter_data[$pseudonym]['type']) {
					$tax_args = array(
						'taxonomy' => $item,
						'hide_empty' => false
					);

					$terms = get_terms(apply_filters('avalon23_taxonomy_arg', $tax_args));
					foreach ($terms as $t) {
						if (is_object($t)) {
							$all_filter_data[$pseudonym]['options'][] = array(
								'slug' => $t->slug,
								'id' => $t->term_id
							);
						}
					}
				}
			}
		}
		
 
		foreach ($request_array as $string) {

			foreach ($all_filter_data as $f_key => $f_data) {
				$match_key = preg_replace('/^pa_/', '', $f_key);
				$s_match = preg_replace('/^' . $match_key . '(-|$)/', '', $string);			
				if ($s_match != $string) {

					if (isset($f_data['type']) && 'taxonomy' == $f_data['type']) {
						$terms_slug = explode('-and-', $s_match);
						$terms_id = array();
						if (isset($f_data['options'])) {
							foreach ($f_data['options'] as $term) {
								if (in_array($term['slug'], $terms_slug)) {
									$terms_id[] = $term['id'];
								}
							}
						}
						$filter_data[$f_data['name']] = implode(',', $terms_id);
					} elseif (isset($f_data['type']) && 'switcher' == $f_data['type']) {
						
						$filter_data[$f_data['name']] = 1;
						
					} elseif (isset($f_data['type']) && 'calendar' == $f_data['type']) {
						
						$s_match_data = preg_replace('/^--to-/', '', $s_match);	
						$key = $f_data['name'];
						if ($s_match_data!=$s_match) {
							$key .= '---to';
						}
						$filter_data[$key] = $s_match_data;
						
					} else {
						$needle = array('-and-', '-to-', '+');
						$replase = array(',', ':', ' ');
						$s_match = urldecode($s_match);
						$filter_data[$f_data['name']] = str_replace($needle, $replase, $s_match);
					}
				}
			}
		}

		return $filter_data;
	}
	public  function add_options ( $rows, $filter_id ) {

		return array_merge($rows, [
			[
				'id' => $filter_id,
				'title' => esc_html__('Filter search slug', 'avalon23-products-filter'),
				'value' => sanitize_title(avalon23()->filter_items->options->get($filter_id, 'filter_search_slug', '')),
				'value_custom_field_key' => 'filter_search_slug',
				'notes' => esc_html__('This option will activate URL mode. This is the slug in the url after which the search query in the link will be formed. Please use characters that are valid for URLs', 'avalon23-products-filter'),
			]			
		]);
		
	}
	
	public function get_request_uri() {
		$uri = false;
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = sanitize_text_field($_SERVER['REQUEST_URI']);
		}
		
		return apply_filters('avalon23_override_request_uri', $uri);
	}


	public function get_post () {
		if ( ( isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'avalon23-nonce') ) ||  $this->get_filter_prefix()) {
			return AVALON23_HELPER::sanitize_array($_POST);
		}
		
		return array();
	}
	
	public function get_get () {
		return AVALON23_HELPER::sanitize_array($_GET);
	}

}
