<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Widget_Price_Filter extends WC_Widget_Price_Filter {
	protected $filter_id = 0;
	public function __construct( $filter_id = 0 ) {
		
		$this->filter_id = $filter_id;
		
		add_filter( 'woocommerce_price_filter_sql', array($this, 'predefined_query'), 99, 3);
		parent::__construct();
	}
	public function get_prices() {

		if ( ( WC()->query->get_main_query() == null || ! WC()->query->get_main_query()->post_count ) && !avalon23()->filter->get_filter_data_by_key('price', wc_clean($_GET)) ) {
			return false;
		}

		return $this->get_filtered_price();
	}

	public function predefined_query( $query, $meta_query_sql, $tax_query_sql ) {
		
		global $wpdb;
		$args = avalon23()->filter->generate_predefined_query(array(), $this->filter_id);
		$tax_query = isset($args['tax_query']) ? $args['tax_query'] : array();

		$meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();

		if (avalon23()->filter->get_current_taxonomy() ) {
			$tax_query[] = array(
				'taxonomy' => avalon23()->filter->current_category->taxonomy,
				'terms' => avalon23()->filter->current_category->term_id,
				'field' => 'term_id',
			);
		}

		$meta_query = new WP_Meta_Query($meta_query);
		$tax_query = new WP_Tax_Query($tax_query);

		$meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
		$tax_query_sql = $tax_query->get_sql($wpdb->posts, 'ID');

		$in_sql = '';
		$not_in_sql = '';
		$author_in_sql = '';

		if (isset($args['post__in']) && $args['post__in']) {
			$in_sql = " AND {$wpdb->posts}.ID IN (" . implode(',', $args['post__in']) . ')';
		}

		if (isset($args['post__not_in']) && $args['post__not_in']) {
			$in_sql = " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $args['post__not_in']) . ')';
		}
		if (isset($args['author__in']) && $args['author__in']) {
			$in_sql = " AND {$wpdb->posts}.post_author  IN (" . implode(',', $args['author__in']) . ')';
		}

		$sql = "SELECT min( FLOOR( price_meta.meta_value + 0.0)  ) as min_price, max( CEILING( price_meta.meta_value + 0.0)  )as max_price FROM {$wpdb->posts} ";
		$sql .= " LEFT JOIN {$wpdb->postmeta} as price_meta ON {$wpdb->posts}.ID = price_meta.post_id " . $tax_query_sql['join'] . $meta_query_sql['join'];
		$sql .= " WHERE {$wpdb->posts}.post_type = 'product'
					AND {$wpdb->posts}.post_status = 'publish'
					AND price_meta.meta_key IN ('" . implode("','", array_map('esc_sql', apply_filters('woocommerce_price_filter_meta_keys', array('_price')))) . "')
					AND price_meta.meta_value > '' " . $tax_query_sql['where'] . $meta_query_sql['where'];
		$sql = apply_filters('avalon23_get_filtered_price_query', $sql, $args);		
		
		return $sql;
	}
	
}

