<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

final class AVALON23_HELPER {
	public function __construct() {
		add_filter('cron_schedules', array($this, 'cron_schedules'), 10, 1);
		add_filter('avalon23_get_filtered_price_query', array($this, 'add_args_price_query'), 10, 2);
		
	}

	public static function draw_html_item( $type, $data, $content = '') {
		$item = '<' . $type;
		foreach ($data as $key => $value) {
			$item .= " {$key}='{$value}'";
		}

		if (!empty($content) || in_array($type, array('textarea'))) {
			$item .= '>' . $content . "</{$type}>";
		} else {
			$item .= ' />';
		}

		return $item;
	}

	public static function draw_select( $attributes, $options, $selected = '', $options_attributes = [], $value_as_key = false ) {
		$select = '<div class="avalon23-select-wrap"><select';
		foreach ($attributes as $key => $value) {
			$select .= " {$key}='{$value}'";
		}
		$select .= '>';

		//***

		if (!is_array($selected)) {
			$selected = [$selected];
		}

		$content = '';
		if (!empty($options) && is_array($options)) {
			foreach ($options as $key => $value) {
				$data_color = '';
				$data_option = '';
				if (isset($options_attributes[$key]) && !empty($options_attributes[$key])) {

					foreach ($options_attributes[$key] as $key_opt => $value_opt) {
						if ('color' == $key_opt && $value_opt) {
							$data_color = "data-color='{$value_opt}'";
							continue;
						}
						$data_option .= ' ' . $key_opt . '=' . $value_opt;
					}
				}

				$option_value = $key;

				if ($value_as_key) {
					$option_value = $value;
				}

				$content .= '<option ' . $data_option . ' ' . self::selected(in_array($option_value, $selected)) . ' ' . $data_color . ' value="' . $option_value . '" title="' . $value . '">' . $value . '</option>';
			}
		}

		$select .= $content . '</select></div>';
		return $select;
	}
	public static function _draw_switcher( $name, $is_checked, $page_id, $event = '', $custom_ajax_data = []) {
		$id = uniqid();
		$checked = 'data-n';
		$is_checked = boolval(intval($is_checked) > 0);

		if ($is_checked) {
			$checked = 'checked';
		}

		return '<div>' . self::draw_html_item('input', array(
					'type' => 'hidden',
					'name' => $name,
					'value' => $is_checked ? 1 : 0
				)) . self::draw_html_item('input', array(
					'type' => 'checkbox',
					'id' => $id,
					'class' => 'switcher23',
					'value' => $is_checked ? 1 : 0,
					'disabled' => 'disabled',
					$checked => $checked,
					'data-post-id' => $page_id,
					'data-event' => $event,
					'data-custom-data' => count($custom_ajax_data) ? json_encode($custom_ajax_data) : ''
				)) . self::draw_html_item('label', array(
					'for' => $id,
					'class' => 'switcher23-toggle'
						), '<span></span>') . '</div>';
	}
	public static function draw_select_group( $attributes, $options_group, $selected = '', $options_attributes = [], $value_as_key = false ) {

		$select = '<div class="avalon23-select-wrap"><select';
		foreach ($attributes as $key => $value) {
			$select .= " {$key}='{$value}'";
		}
		$select .= '>';

		//***

		if (!is_array($selected)) {
			$selected = [$selected];
		}
		$content = '';
		foreach ($options_group as $group_name => $options) {

			$content.= "<optgroup label='" . $group_name . "'>";
			if (!empty($options) && is_array($options)) {
				foreach ($options as $key => $value) {
					$data_color = '';
					$data_option = '';
					if (isset($options_attributes[$key]) && !empty($options_attributes[$key])) {

						foreach ($options_attributes[$key] as $key_opt => $value_opt) {
							if ('color' == $key_opt && $value_opt) {
								$data_color = "data-color='{$value_opt}'";
								continue;
							}
							$data_option .= ' ' . $key_opt . '=' . $value_opt;
						}
					}

					$option_value = $key;

					if ($value_as_key) {
						$option_value = $value;
					}
					if (is_array($value)) {
						continue;
					}
					$content .= '<option ' . $data_option . ' ' . self::selected(in_array($option_value, $selected)) . ' ' . $data_color . ' value="' . $option_value . '" title="' . $value . '">' . $value . '</option>';
				}
			}
			//$content.= '</optgroup>'; 
		}		

		$select .= $content . '</select></div>';
		return $select;
	}	

	private static function selected( $is, $echo = false ) {
		if ($is) {
			if ($echo) {
				echo 'selected';
			} else {
				return 'selected';
			}
		}
	}

	public static function draw_image_uploader( $attachment_id, $uploader_data, $uploader_delete_data ) {

		if ($attachment_id) {
			$img_src = wp_get_attachment_image_src($attachment_id, 'thumbnail');

			if (is_array($img_src) && !empty($img_src[0])) {

				return self::draw_html_item('div', ['class' => 'avalon23_image_container'], self::draw_html_item('a', $uploader_data, self::draw_html_item('img', array(
											'src' => $img_src[0],
											'width' => 40,
											'alt' => ''
								))) . self::draw_html_item('a', $uploader_delete_data + ['data-src' => AVALON23_LINK . 'assets/img/not-found.jpg', 'class' => 'avalon23_delete_img'], '<span class="dashicons dashicons-trash"></span>'));
			} else {
				return self::draw_html_item('div', ['class' => 'avalon23_image_container'], self::draw_html_item('a', $uploader_data, self::draw_html_item('img', array(
											'src' => AVALON23_LINK . 'assets/img/not-found.jpg',
											'width' => 40,
											'alt' => ''
								))) . self::draw_html_item('a', $uploader_delete_data + ['data-src' => AVALON23_LINK . 'assets/img/not-found.jpg', 'style' => 'display: none;', 'class' => 'avalon23_delete_img'], '<span class="dashicons dashicons-trash"></span>'));
			}
		} else {
			return self::draw_html_item('div', ['class' => 'avalon23_image_container'], self::draw_html_item('a', $uploader_data, self::draw_html_item('img', array(
										'src' => AVALON23_LINK . 'assets/img/not-found.jpg',
										'width' => 40,
										'alt' => ''
							))) . self::draw_html_item('a', $uploader_delete_data + ['data-src' => AVALON23_LINK . 'assets/img/not-found.jpg', 'style' => 'display: none;', 'class' => 'avalon23_delete_img'], '<span class="dashicons dashicons-trash"></span>'));
		}
	}

	public static function draw_color_piker( $color_data ) {
		return self::draw_html_item('div', ['class' => 'avalon23_color_container'], self::draw_html_item('input', [
							'type' => 'text',
								] + $color_data));
	}

	public static function draw_toggle_item( $title, $content ) {
		$toggle_id = uniqid('avalon23_');
		return self::draw_html_item('input', [
					'class' => 'avalon23-toggle-button',
					'type' => 'button',
					'value' => $title,
					'data-toggle-id' => $toggle_id,
					'onclick' => 'avalon23_toggle_content(this)'
				]) . self::draw_html_item('div', [
					'class' => 'avalon23-toggled-content ' . $toggle_id,
					'value' => $title,
						], $content);
	}

	public static function draw_switcher( $name, $is_checked, $page_id, $event = '', $custom_ajax_data = [] ) {
		$id = uniqid();
		$checked = 'data-n';
		$is_checked = boolval(intval($is_checked) > 0);

		if ($is_checked) {
			$checked = 'checked';
		}

		return '<div>' . self::draw_html_item('input', array(
					'type' => 'hidden',
					'name' => $name,
					'value' => $is_checked ? 1 : 0
				)) . self::draw_html_item('input', array(
					'type' => 'checkbox',
					'id' => $id,
					'class' => 'switcher23',
					'value' => $is_checked ? 1 : 0,
					$checked => $checked,
					'data-post-id' => $page_id,
					'data-event' => $event,
					'data-custom-data' => count($custom_ajax_data) ? json_encode($custom_ajax_data) : ''
				)) . self::draw_html_item('label', array(
					'for' => $id,
					'class' => 'switcher23-toggle'
						), '<span></span>') . '</div>';
	}

	public static function draw_switcher_toggle( $name, $is_checked, $page_id, $event = '', $toggled_content = '', $custom_ajax_data = [] ) {
		$id = uniqid();
		$checked = 'data-n';
		$is_checked = boolval(intval($is_checked) > 0);

		if ($is_checked) {
			$checked = 'checked';
		}

		return '<div>' . self::draw_html_item('input', array(
					'type' => 'hidden',
					'name' => $name,
					'value' => $is_checked ? 1 : 0
				)) . self::draw_html_item('input', array(
					'type' => 'checkbox',
					'id' => $id,
					'class' => 'switcher23',
					'value' => $is_checked ? 1 : 0,
					$checked => $checked,
					'data-post-id' => $page_id,
					'data-event' => $event,
					'data-custom-data' => count($custom_ajax_data) ? json_encode($custom_ajax_data) : ''
				)) . self::draw_html_item('label', array(
					'for' => $id,
					'class' => 'switcher23-toggle'
						), '<span></span>') . '<div class="avalon23_switcher23_toggled">' . $toggled_content . '</div></div>';
	}

	public static function strtolower( $string ) {
		if (function_exists('mb_strtolower')) {
			$string = mb_strtolower($string, 'UTF-8');
		} else {
			$string = strtolower($string);
		}

		return $string;
	}

	public static function string_id_array( $string ) {

		if (!is_string($string) || empty($string)) {
			return array();
		}
		$ids = explode(',', trim($string));

		$ids = array_map(function( $val ) {
			return intval($val);
		}, $ids);
		$ids = array_filter($ids);
		return $ids;
	}

	public static function string_slugs_array( $string ) {
		if (!is_string($string) || empty($string)) {
			return array();
		}
		$slugs = explode(',', trim($string));

		$slugs = array_map('trim', $slugs);
		$slugs = array_filter($slugs);
		return $slugs;
	}

	public static function render_html( $pagepath, $data = array(), $with_root = true ) {

		if (is_array($data) && ! empty($data)) {
			if (isset($data['pagepath'])) {
				unset($data['pagepath']);
			}
			extract($data);
		}

		//***

		ob_start();
		if ($with_root) {
			$pagepath = AVALON23_PATH . $pagepath;
		}
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $pagepath));
		return ob_get_clean();
	}

	public static function can_manage_data( $user_id = 0 ) {

		if (0 === $user_id ) {
			$user = wp_get_current_user();
		} else {
			$user = get_userdata($user_id);
		}

		if (in_array('administrator', $user->roles) || in_array('shop_manager', $user->roles)) {
			return true;
		}

		return false;
	}

	public static function draw_app_js( $file_name ) {
		return '<script>' . self::render_html(AVALON23_PATH . "assets/js/app/{$file_name}.js") . '</script>';
	}

	public static function draw_app_css( $file_name ) {
		return '<style>' . self::render_html(AVALON23_PATH . "assets/css/app/{$file_name}.css") . '</style>';
	}

	public static function sanitize_text( $string ) {
		return preg_replace('/[\n\r]/', '', trim(strip_tags($string)));
	}

	public static function wrap_text_to_container( $text, $h ) {
		return '<div class="avalon23-more-less-container" onclick="return avalon23_open_txt_container(this)"><div><strong>' . $h . '</strong>' . $text . '<a href="#" onclick="return avalon23_close_txt_container(this, event); void(0);" class="avalon23-more-less-container-closer">X</a></div></div>';
	}

	public static function import_mysql_table( $table, $data ) {
		global $wpdb;
		$table_tmp = str_replace($wpdb->prefix, '', $table);
		switch ($table_tmp) {
			case 'avalon23_filters':
				$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}avalon23_filters");
				break;
			case 'avalon23_filters_fields':
				$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}avalon23_filters_fields");
				break;
			case 'avalon23_filters_meta':
				$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}avalon23_filters_meta");
				break;
			case 'avalon23_vocabulary':
				$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}avalon23_vocabulary");
				break;
			default:
				$wpdb->query($wpdb->prepare('TRUNCATE TABLE %s', $table));
				break;
		}

		if (!empty($data)) {
			foreach ($data as $row) {
				$rd = [];
				foreach ($row as $key => $value) {
					$rd[$key] = $value;
				}

				$wpdb->insert($table, $rd);
			}
		}
	}

	public static function query_prepare( $query, $args ) {
		if (is_null($query)) {
			return;
		}
		$sql_val = array();

		$query = str_replace("'%s'", '%s', $query); // in case someone mistakenly already singlequoted it
		$query = str_replace('"%s"', '%s', $query); // doublequote unquoting
		$query = preg_replace('|(?<!%)%f|', '%F', $query); // Force floats to be locale unaware
		$query = preg_replace('|(?<!%)%s|', "'%s'", $query); // quote the strings, avoiding escaped strings like %%s
		if (!is_array($args)) {
			$args = array('val' => $args, 'type' => 'string');
		}
		foreach ($args as $item) {

			if (!is_array($item) || ! isset($item['val'])) {
				continue;
			}
			if (!isset($item['type'])) {
				$item['type'] = 'string';
			}
			$sql_val[] = self::escape_sql($item['type'], $item['val']);
		}
		return @vsprintf($query, $sql_val);
	}

	public static function escape_sql( $type, $value ) {
		switch ($type) {
			case 'string':
				global $wpdb;
				return $wpdb->_real_escape($value);
				break;
			case 'int':
				return intval($value);
				break;
			case 'float':
				return floatval($value);
				break;
			default:
				global $wpdb;
				return $wpdb->_real_escape($value);
		}
	}

	public function cron_schedules( $schedules ) {
		//$schedules stores all recurrence schedules within WordPress
		for ($i = 2; $i <= 7; $i++) {
			$schedules['days' . $i] = array(
				'interval' => $i * DAY_IN_SECONDS,
				/* translators: %s is replaced with "string" */
				'display' => sprintf(esc_html__('each %s days', 'avalon23-products-filter'), sanitize_text_field($i))
			);
		}

		return (array) $schedules;
	}

	public static function get_original_tax_id( $term ) {

		$translated_id = $term->term_id;
		if (function_exists('icl_object_id')) {
			$default_lang = apply_filters('wpml_default_language', null);
			$translated_id = icl_object_id($term->term_id, $term->taxonomy, false, $default_lang);
		}

		return $translated_id;
	}
	public static function get_original_tax_id_by_id( $term_id, $taxonomy ) {
		$translated_id = $term_id;
		if (function_exists('icl_object_id')) {
			$default_lang = apply_filters('wpml_default_language', null);
			$translated_id = icl_object_id($term_id, $taxonomy, false, $default_lang);
		}

		return $translated_id;			
	}
	public static function get_term_for_default_lang_by_id( $term_id, $taxonomy  ) {

		if (class_exists('SitePress')) {
			/* SitePress */
			global $sitepress;
			global $icl_adjust_id_url_filter_off;
			$default_term_id = self::get_original_tax_id_by_id( $term_id, $taxonomy );
			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$term = get_term($default_term_id, $taxonomy);
			$icl_adjust_id_url_filter_off = $orig_flag_value;
			return $term;
		}

		return false;
	}
	public static function get_term_for_default_lang( $term ) {

		if (class_exists('SitePress')) {
			/* SitePress */
			global $sitepress;
			global $icl_adjust_id_url_filter_off;
			$default_term_id = self::get_original_tax_id($term);
			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$term = get_term($default_term_id, $term->taxonomy);
			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}

		return $term;
	}
	public static function get_taxonomy_level_count( $terms, $parent_id, $level) {
		$parent = -1;	
		foreach ($terms as $term) {
			if ($term->parent == $parent_id) {	
				$parent = $term->term_id;
				break;
			}
		}
		$level++;
		if (-1 != $parent) {
			$level = self::get_taxonomy_level_count($terms, $term->term_id, $level);
		}

		return $level;
	}
	public static function get_taxonomy_level_count_max( $terms ) {
		$max_count = 0;
		$load_count = 0;
		foreach ($terms as $key =>$term) {
			if ($load_count > 4) {
				break;
			} 
			if (0 == $term->parent) {
				$load_count++;
				$temp_count = self::get_taxonomy_level_count($terms, $term->term_id, 0);
				if ($temp_count > $max_count) {
					$max_count = $temp_count;
				}
			}
			
		}

		return $max_count;
	}
	public function add_args_price_query( $s_query, $args ) {
		$in_sql = '';
		$not_in_sql = '';
		$author_in_sql = '';
		global $wpdb;
		if (isset($args['post__in']) && $args['post__in']) {
			$in_sql = " AND {$wpdb->posts}.ID IN (" . implode(',', $args['post__in']) . ')';
		}

		if (isset($args['post__not_in']) && $args['post__not_in']) {
			$in_sql = " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $args['post__not_in']) . ')';
		}
		if (isset($args['author__in']) && $args['author__in']) {
			$in_sql = " AND {$wpdb->posts}.post_author  IN (" . implode(',', $args['author__in']) . ')';
		}
		return $s_query . $in_sql . $not_in_sql . $author_in_sql;
	}
	public static function get_meta_filter_types() {
		$options = array(
			'textinput' => esc_html__('Text input', 'avalon23-products-filter'),	
			'select' => esc_html__('Select', 'avalon23-products-filter'),
			'checkbox_radio' => esc_html__('Checkbox/Radio', 'avalon23-products-filter'),
			'labels' => esc_html__('Labels', 'avalon23-products-filter'),
			'image' => esc_html__('Image', 'avalon23-products-filter'),
			'color' => esc_html__('Color', 'avalon23-products-filter')								
		);		
		
		return apply_filters('avalon23_meta_front_view', $options);
	}
	public static function get_taxonomy_filter_types() {
		$options = array(
			'select' => esc_html__('Select', 'avalon23-products-filter'),
			'checkbox_radio' => esc_html__('Checkbox/Radio', 'avalon23-products-filter'),
			'labels' => esc_html__('Labels', 'avalon23-products-filter'),
			'tax_slider' => esc_html__('Slider', 'avalon23-products-filter'),
			'hierarchy_dd' => esc_html__('Hierarchy dropdown', 'avalon23-products-filter'),
			'image' => esc_html__('Image', 'avalon23-products-filter'),
			'color' => esc_html__('Color', 'avalon23-products-filter')								
		);		
		
		return apply_filters('avalon23_taxonomy_front_view', $options);
	}
	public static function get_standard_filter_types() {
		$options = array(
			'textinput' => esc_html__('Text input', 'avalon23-products-filter'),
			'post_author' => esc_html__('Select', 'avalon23-products-filter'),
			'average_rating' => esc_html__('Select', 'avalon23-products-filter'),
			'calendar' => esc_html__('Сalendar', 'avalon23-products-filter'),
			'range_slider' => esc_html__('Slider', 'avalon23-products-filter'),
			'switcher' => esc_html__('Switcher', 'avalon23-products-filter')							
		);
		return apply_filters('avalon23_standard_front_view', $options);
	}	
	public static function get_filter_name( $filter_id, $key) {
		$tax_opt = self::get_taxonomy_filter_types();
		$meta_opt = self::get_meta_filter_types();
		$std_opt = self::get_standard_filter_types();
		
		$options = array_merge($tax_opt, $meta_opt, $std_opt);
		
		if (isset($options[$key])) {
			return [$key => $options[$key]];
		}

		$available_fields = apply_filters('avalon23_get_available_fields', [], $filter_id);
		if (isset($available_fields[$key]['get_draw_data'])) {
			$filter_data = $available_fields[$key]['get_draw_data']($filter_id);

			if ( isset($filter_data['view']) && isset($options[$filter_data['view']]) ) {
				return [$filter_data['view'] => $options[$filter_data['view']]];
			} else {
				return ['none' => 'html'];
			}

		} else {
			return ['none' => esc_html__('None', 'avalon23-products-filter')];
		}		

	}
	public static function sanitize_array( $array ) {
		
		foreach ($array as $key => $data) {
			if (is_array($data)) {
				self::sanitize_array($data);
			} else {
				$array[$key] = sanitize_text_field($data);
			}
		}
		
		return $array;
	}
}

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//for calendar strings
add_filter('avalon23-get-calendar-names', function( $names ) {
	return [
		'month_names' => [
			Avalon23_Vocabulary::get(esc_html__('January', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('February', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('March', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('April', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('May', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('June', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('July', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('August', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('September', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('October', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('November', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('December', 'avalon23-products-filter'))
		],
		'month_names_short' => [
			Avalon23_Vocabulary::get(esc_html__('Jan', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Feb', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Mar', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Apr', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('May', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Jun', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Jul', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Aug', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Sep', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Oct', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Nov', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Dec', 'avalon23-products-filter'))
		],
		'day_names' => [
			Avalon23_Vocabulary::get(esc_html__('Mo', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Tu', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('We', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Th', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Fr', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Sa', 'avalon23-products-filter')),
			Avalon23_Vocabulary::get(esc_html__('Su', 'avalon23-products-filter'))
		]
	];
});


