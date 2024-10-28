<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

add_action('avalon23_get_available_fields', function( $available_fields, $filter_id ) {
	$available_fields = [
		'text_search' => [
			'title' => esc_html__('Text search', 'avalon23-products-filter'),
			'view' => 'textinput',
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'options' => ['minlength', 'placeholder', 'livesearch', 'tax_search', 'meta_search'],
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'text_search');
			},
			'get_query_args' => function( $args, $value ) use( $filter_id ) {
				$value = trim($value);
				$field_key = 'text_search';
				$args['av23_text_search'] = $value;
				$tax_search = avalon23()->filter->options->get_option($filter_id, 'text_search', "{$field_key}-tax_search");
				$meta_search = avalon23()->filter->options->get_option($filter_id, 'text_search', "{$field_key}-meta_search");
				$where_sql = avalon23()->filter->get_main_search_query_sql($value, $tax_search, $meta_search);

				$_REQUEST['avalon23_txt_search'] = 1;
				if (!empty($value)) {
					
					add_filter('posts_where', function( $where = '' ) use( $where_sql ) {
						if (!isset($_REQUEST['avalon23_txt_search'])) {
							$_REQUEST['avalon23_txt_search'] = 0;
						}						
						if (1 != $_REQUEST['avalon23_txt_search']) {
							return $where ;
						}						
						$where .= ' AND ' . $where_sql;

						return $where;
					}, 9999);
							
					add_filter('_posts_groupby', function( $groupby ) {
						global $wpdb;

						$groupby_id = "{$wpdb->posts}.ID";
						if (!is_search() || strpos($groupby, $groupby_id) !== false) {
							return $groupby;
						}
						if (!strlen(trim($groupby))) {
							return $groupby_id;
						}
						return $groupby . ', ' . $groupby_id;
					});
					add_filter('posts_join', function( $join ) {
						global $wpdb;
						if (!isset($_REQUEST['avalon23_txt_search'])) {
							$_REQUEST['avalon23_txt_search'] = 0;
						}
						if (1 == $_REQUEST['avalon23_txt_search']) {
							$join .= "LEFT JOIN {$wpdb->term_relationships} as trm_r ON {$wpdb->posts}.ID = trm_r.object_id INNER JOIN {$wpdb->term_taxonomy} trm_t ON trm_t.term_taxonomy_id=trm_r.term_taxonomy_id INNER JOIN {$wpdb->terms} trm ON trm.term_id = trm_t.term_id";
							
							$join .= " LEFT JOIN $wpdb->postmeta AS postmeta ON ( {$wpdb->posts}.ID = postmeta.post_id )";
							
						}
						( int ) $_REQUEST['avalon23_txt_search']++;
						return $join;
					}, 99);
				}

				return $args;
			}
		],
        'post_author' => [
            'title' => esc_html__('Author(only paid version)', 'avalon23-products-filter'),
            'options' => ['dynamic_recount','show_count','hide_empty_terms','user_roles'],
            'get_query_args' => function($args, $value) {  
            
                return $args;
            },
            'get_draw_data' => function($filter_id) {

                return '';
            },            
        ],
		'post_date' => [
			'title' => esc_html__('Date', 'avalon23-products-filter'),
			'view' => 'calendar',
			'options' => [],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function() use( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'post_date');
			},
			'get_query_args' => function( $args, $value, $is_calendar_dir_to = false ) {

				add_filter('posts_where', function( $where = '' ) use( $value, $is_calendar_dir_to ) {
					$value = gmdate('Y-m-d H:i:s', $value);
					if ($is_calendar_dir_to) {
						$where .= "  AND post_date <= '{$value}'";
					} else {
						$where .= "  AND post_date >= '{$value}'";
					}

					return $where;
				}, 101);


				return $args;
			},
		],
		'post_modified' => [
			'title' => esc_html__('Modified', 'avalon23-products-filter'),
			'view' => 'calendar',
			'options' => [],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function() use( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'post_modified');
			},
			'get_query_args' => function( $args, $value, $is_calendar_dir_to = false ) {

				add_filter('posts_where', function( $where = '' ) use( $value, $is_calendar_dir_to ) {
					$value = gmdate('Y-m-d H:i:s', $value);
					if ($is_calendar_dir_to) {
						$where .= "  AND post_modified <= '{$value}'";
					} else {
						$where .= "  AND post_modified >= '{$value}'";
					}

					return $where;
				}, 101);


				return $args;
			},
		],
		'price' => [
			'title' => esc_html__('Price', 'avalon23-products-filter'),
			'meta_key' => '_price',
			'view' => 'range_slider',
			'options' => ['min', 'max'],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				$res = avalon23()->filter->get_field_drawing_data($filter_id, 'price');
				return $res;
			},
			'get_query_args' => function( $args, $value ) {
				$value = explode(':', $value);
				if (wc_tax_enabled() && 'incl' === get_option('woocommerce_tax_display_shop') && !wc_prices_include_tax()) {
					$tax_class = apply_filters('woocommerce_price_filter_widget_tax_class', ''); // Uses standard tax class.
					$tax_rates = WC_Tax::get_rates($tax_class);

					if ($tax_rates) {
						$value[0] -= WC_Tax::get_tax_total(WC_Tax::calc_inclusive_tax($value[0], $tax_rates));
						$value[1] -= WC_Tax::get_tax_total(WC_Tax::calc_inclusive_tax($value[1], $tax_rates));
					}
				}
				$args['meta_query'][] = array(
					'key' => '_price',
					'value' => array(intval($value[0]), intval($value[1])),
					'type' => 'numeric',
					'compare' => 'BETWEEN'
				);

				return $args;
			}
		],
        'on_sale' => [
            'title' => esc_html__('On Sale(only paid version)', 'avalon23-products-filter'),
            'view' => 'switcher',
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
            'options' => ['dynamic_recount','show_count','hide_empty_terms'],
            'get_draw_data' => function($filter_id) {
                return '';
            },                    
            'get_query_args' => function($args, $value) {
                return $args;
            },                   
        ],
		'sku' => [
			'title' => esc_html__('SKU', 'avalon23-products-filter'),
			'meta_key' => '_sku',
			'view' => 'textinput',
			'options' => ['minlength', 'placeholder'],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'sku');
			},
			'get_query_args' => function( $args, $value ) {
				if (!empty($value) && ! is_array($value)) {

					$args['meta_query'][] = array(
						'key' => '_sku',
						'value' => trim($value),
						'compare' => 'LIKE'
					);
				}

				return $args;
			}
		],
		'downloadable' => [
			'title' => esc_html__('Downloadable', 'avalon23-products-filter'),
			'meta_key' => '_downloadable',
			'options' => ['dynamic_recount', 'show_count', 'hide_empty_terms'],
			'view' => 'switcher',
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'downloadable');
			},
			'get_query_args' => function( $args, $value ) {

				if ($value) {
					$args['meta_query'][] = array(
						'key' => '_downloadable',
						'value' => 'yes',
						'compare' => '='
					);
				}

				return $args;
			},
			'get_count' => function( $filter_id, $value = '', $dynamic_recount = 0 ) {
				if ((int) $value) {
					return -1;
				}
				$dynamic_recount = avalon23()->filter->options->get_option($filter_id, 'downloadable', 'downloadable-dynamic_recount');
				if (-1 == $dynamic_recount || null == $dynamic_recount) {
					$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
				}
				if (!$dynamic_recount) {
					return -1;
				}

				return avalon23()->filter->get_field_count('downloadable', 1, $filter_id);
			},
		],
		'weight' => [
			'title' => esc_html__('Weight', 'avalon23-products-filter'),
			'meta_key' => '_weight',
			'view' => 'range_slider',
			'options' => ['min', 'max', 'dynamic_recount', 'show_count', 'hide_empty_terms'],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'weight');
			},
			'get_count' => function( $filter_id, $value = '', $dynamic_recount = 0 ) {
				$dynamic_recount = avalon23()->filter->options->get_option($filter_id, 'weight', 'weight-dynamic_recount');
				if (-1 == $dynamic_recount || null == $dynamic_recount) {
					$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
				}
				if (!$dynamic_recount || ( $dynamic_recount && empty($value) )) {
					$value = avalon23()->filter->get_min_max_field('weight', 'min', $filter_id);
					$value .= ':' . avalon23()->filter->get_min_max_field('weight', 'max', $filter_id);
				}

				return avalon23()->filter->get_field_count('weight', $value, $filter_id);
			},
			'get_query_args' => function( $args, $value ) {
				$value = explode(':', $value);

				$args['meta_query'][] = array(
					'key' => '_weight',
					'value' => array(intval($value[0]), intval($value[1])),
					'type' => 'numeric',
					'compare' => 'BETWEEN'
				);

				return $args;
			}
		],
        '_length' => [
            'title' => esc_html__('Length(only paid version)', 'avalon23-products-filter'),
            'meta_key' => '_length',
            'view' => 'range_slider',
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
            'options' => ['min', 'max','dynamic_recount','show_count','hide_empty_terms'],
            'get_draw_data' => function($filter_id) {
                return '';
            },      
            'get_query_args' => function($args, $value) {
                return $args;
            }
        ],
        'height' => [
            'title' => esc_html__('Height(only paid version)', 'avalon23-products-filter'),
            'meta_key' => '_height',
            'view' => 'range_slider',
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
            'options' => ['min', 'max','dynamic_recount','show_count','hide_empty_terms'],
            'get_draw_data' => function($filter_id) {
                return '';
            },            
            'get_query_args' => function($args, $value) {
                return $args;
            }
        ],
		'width' => [
			'title' => esc_html__('Width', 'avalon23-products-filter'),
			'meta_key' => '_width',
			'view' => 'range_slider',
			'options' => ['min', 'max', 'dynamic_recount', 'show_count', 'hide_empty_terms'],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'width');
			},
			'get_count' => function( $filter_id, $value = '', $dynamic_recount = 0 ) {
				$dynamic_recount = avalon23()->filter->options->get_option($filter_id, 'width', 'width-dynamic_recount');
				if (-1 == $dynamic_recount || null == $dynamic_recount) {
					$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
				}
				if (!$dynamic_recount || ( $dynamic_recount && empty($value) ) ) {
					$value = avalon23()->filter->get_min_max_field('width', 'min', $filter_id);
					$value .= ':' . avalon23()->filter->get_min_max_field('width', 'max', $filter_id);
				}

				return avalon23()->filter->get_field_count('width', $value, $filter_id);
			},
			'get_query_args' => function( $args, $value ) {
				$value = explode(':', $value);
				if (!isset($value[1])) {
					return $args;
				}
				$args['meta_query'][] = array(
					'key' => '_width',
					'value' => array(intval($value[0]), intval($value[1])),
					'type' => 'numeric',
					'compare' => 'BETWEEN'
				);

				return $args;
			}
		],
		'in_stock' => [
			'title' => esc_html__('In Stock', 'avalon23-products-filter'),
			'view' => 'switcher',
			'options' => [],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'in_stock');
			},
			'get_query_args' => function( $args, $value ) use( $filter_id ) {

				$args['meta_query'][] = array(
					'key' => '_stock_status',
					'value' => 'instock',
					'compare' => 'IN'
				);
				$outstock_posts = avalon23()->filter->get_instock_variable_ids();
				if ($outstock_posts) {
					if (!isset($args['post__not_in']) || ! is_array($args['post__not_in'])) {
						$args['post__not_in'] = array();
					}

					$args['post__not_in'] = array_merge($args['post__not_in'], $outstock_posts);
				}

				return $args;
			}
		],
		'sold_individually' => [
			'title' => esc_html__('Sold individually', 'avalon23-products-filter'),
			'view' => 'switcher',
			'options' => ['dynamic_recount', 'show_count', 'hide_empty_terms'],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				return avalon23()->filter->get_field_drawing_data($filter_id, 'sold_individually');
			},
			'get_query_args' => function( $args, $value ) {

				if ($value) {
					$args['meta_query'][] = array(
						'key' => '_sold_individually',
						'value' => 'yes',
						'compare' => '='
					);
				}
				return $args;
			},
			'get_count' => function( $filter_id, $value = '', $dynamic_recount = 0 ) {
				if ((int) $value) {
					return -1;
				}
				$dynamic_recount = avalon23()->filter->options->get_option($filter_id, 'sold_individually', 'sold_individually-dynamic_recount');
				if (-1 == $dynamic_recount || null == $dynamic_recount) {
					$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
				}
				if (!$dynamic_recount) {
					return -1;
				}

				return avalon23()->filter->get_field_count('sold_individually', 1, $filter_id);
			},
		],
		'average_rating' => [
			'title' => esc_html__('Average rating', 'avalon23-products-filter'),
			'meta_key' => '_wc_average_rating',
			'options' => ['dynamic_recount', 'show_count', 'hide_empty_terms'],
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
			'get_draw_data' => function( $filter_id ) {
				$field_key = 'average_rating';
				$hide_empty_terms = avalon23()->filter->options->get_option($filter_id, $field_key, "{$field_key}-hide_empty_terms");
				if (-1 == $hide_empty_terms || null == $hide_empty_terms || ! $hide_empty_terms) {
					$hide_empty_terms = avalon23()->filter_items->options->get($filter_id, 'hide_empty_terms', 0);
				}

				$show_count = avalon23()->filter->options->get_option($filter_id, $field_key, "{$field_key}-show_count");
				if (-1 == $show_count || null == $show_count || ! $show_count) {
					$show_count = avalon23()->filter_items->options->get($filter_id, 'show_count', 1);
				}

				$data = [
					'title' => avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['title'],
					'type' => 'meta',
					'view' => 'select',
					'options' => [
						[
							'id' => 1,
							'title' => Avalon23_Vocabulary::get(esc_html__('Sssss', 'avalon23-products-filter')),
							'count' => -1
						],
						[
							'id' => 2,
							'title' => Avalon23_Vocabulary::get(esc_html__('SSsss', 'avalon23-products-filter')),
							'count' => -1
						],
						[
							'id' => 3,
							'title' => Avalon23_Vocabulary::get(esc_html__('SSSss', 'avalon23-products-filter')),
							'count' => -1
						],
						[
							'id' => 4,
							'title' => Avalon23_Vocabulary::get(esc_html__('SSSSs', 'avalon23-products-filter')),
							'count' => -1
						],
						[
							'id' => 5,
							'title' => Avalon23_Vocabulary::get(esc_html__('SSSSS', 'avalon23-products-filter')),
							'count' => -1
						]
					],
					'placeholder' => strval(Avalon23_Vocabulary::get(avalon23()->filter->options->get_option($filter_id, $field_key, "{$field_key}-placeholder"))),
					'show_count' => $show_count,
					'hide_empty_terms' => $hide_empty_terms,
					'width_sm' => avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['width_sm'],
					'width_md' => avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['width_md'],
					'width_lg' => avalon23()->filter_items->get_by_field_key($filter_id, $field_key)['width_lg']
				];
				$dynamic_recount = avalon23()->filter->options->get_option($filter_id, $field_key, $field_key . '-dynamic_recount');
				if (-1 == $dynamic_recount || null == $dynamic_recount) {
					$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
				}
				if ($dynamic_recount) {
					foreach ($data['options'] as $key => $op) {
						$data['options'][$key]['count'] = avalon23()->filter->get_field_count($field_key, $op['id'], $filter_id);
					}
				}
				return $data;
			},
			'get_query_args' => function( $args, $value ) {
				$value = intval($value);
				$args['meta_query'][] = array(
					'key' => '_wc_average_rating',
					'value' => array($value, $value + 1),
					'type' => 'numeric',
					'type' => 'DECIMAL(10,2)',
					'compare' => 'BETWEEN'
				);

				return $args;
			}
		],
        'featured' => [
            'title' => esc_html__('Featured(only paid version)', 'avalon23-products-filter'),
            'view' => 'switcher',
			'optgroup' => esc_html__('Standard', 'avalon23-products-filter'),
            'options' => ['dynamic_recount','show_count','hide_empty_terms'],
            'get_draw_data' => function($filter_id) {
                return '';
            },
            'get_query_args' => function($args, $value) {
                return $args;
            }
        ],
	];

	//lets add woo taxonomies and attributes  
	//get all products taxonomies
	$taxonomy_objects = get_object_taxonomies('product', 'objects');
	unset($taxonomy_objects['product_type']);
	unset($taxonomy_objects['product_visibility']);
	unset($taxonomy_objects['product_shipping_class']);
	if (!empty($taxonomy_objects)) {
		foreach ($taxonomy_objects as $t) {
			$view_type = avalon23()->filter->options->get_option($filter_id, "{$t->name}", "{$t->name}-front-view");
			$options = ['tax_title', 'front-view'];
			switch ($view_type) {
				case 'hierarchy_dd':
					$options = array_merge($options, ['dynamic_recount', 'show_count', 'hide_empty_terms', 'hierarchy-title', 'show_title', 'toggle', 'mselect-logic', 'hierarchy_images', 'show_hierarchy_images']);
					break;
				case 'image':
					$options = array_merge($options, ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'image', 'show_title', 'toggle', 'mselect-logic']);
					break;
				case 'color':
					$options = array_merge($options, ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'color', 'show_title', 'toggle', 'mselect-logic']);
					break;
				case 'tax_slider':
					$options = array_merge($options, ['dynamic_recount', 'hide_empty_terms', 'show_title', 'toggle', 'mselect-logic']);
					break;
				case 'labels':
					$options = array_merge($options, ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'toggle', 'mselect-logic']);
					break;
				case 'checkbox_radio':
					$options = array_merge($options, ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'checkbox_template', 'toggle', 'mselect-logic']);
					break;
				case 'select':
					$options = array_merge($options, ['as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'mselect-logic']);
					break;
				
			}
			$options = array_merge($options, ['exclude', 'include']);

			$available_fields[$t->name] = [
				'title' => $t->label,
				'view' => 'taxonomy',
				'options' => $options,
				'optgroup' => esc_html__('Taxonomies', 'avalon23-products-filter'),
				'get_draw_data' => function( $filter_id )use( $t ) {
					return avalon23()->filter->get_taxonomy_drawing_data($filter_id, $t->name);
				},
				'get_count' => function( $filter_id, $term, $dynamic_recount = 0 )use( $t ) {
					$dynamic_recount = avalon23()->filter->options->get_option($filter_id, "{$t->name}", "{$t->name}-dynamic_recount");

					if (-1 == $dynamic_recount || null == $dynamic_recount) {
						$dynamic_recount = avalon23()->filter_items->options->get($filter_id, 'dynamic_recount', 0);
					}
					return avalon23()->filter->get_tax_count($term, $filter_id, $dynamic_recount);
				},
				'get_query_args' => function( $args, $value ) use( $filter_id, $t ) {

					if (!is_array($value)) {
						$value = explode(',', $value);
					}
					$logic = 'IN';

					if ($filter_id > 0) {
						$logic = avalon23()->filter->options->get_option($filter_id, "{$t->name}", "{$t->name}-mselect-logic");
						if (!in_array($logic, ['IN', 'NOT IN', 'AND'])) {
							$logic = 'IN';
						}
					}

					$args['tax_query'][] = array(
						'taxonomy' => $t->name,
						'field' => 'term_id',
						'terms' => (array) $value,
						'operator' => $logic
					);

					return $args;
				}
			];
		}
	}

	//***

	if ($filter_id > 0) {
		avalon23()->filter_items->meta->extend_filter_fields($filter_id);
	}

	return apply_filters('avalon23_extend_filter_fields', $available_fields, $filter_id);
}, 10, 2);

