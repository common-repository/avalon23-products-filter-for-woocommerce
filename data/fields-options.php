<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

add_action('avalon23_fields_options', function( $args ) {
	$filter_id = intval($args['filter_id']);
	$field_id = intval($args['field_id']);

	$col = avalon23()->filter_items->get($field_id, ['field_key', 'options']);
	$available_fields = apply_filters('avalon23_get_available_fields', [], $filter_id);

	$field_key = $col['field_key'];

	if ($col) {
		$rows = [];

		if (isset($args['field_id'])) {
			$field_id = intval($args['field_id']);
		}

		if (isset($available_fields[$field_key])) {
			if (isset($available_fields[$field_key]['options']) && ! empty($available_fields[$field_key]['options'])) {
				foreach ($available_fields[$field_key]['options'] as $option) {

					switch ($option) {
						case 'minlength':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Min Length', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => intval($val) > 0 ? intval($val) : 1,
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('String min length when search is possible', 'avalon23-products-filter'),
							];

							break;

						case 'placeholder':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Placeholder', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => strval($val),
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('Input placeholder', 'avalon23-products-filter'),
							];

							break;
						case 'meta_search':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Meta key', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => strval($val),
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('Meta field key for text search. To search by SKU, please, paste: _sku', 'avalon23-products-filter'),
							];

							break;							
						case 'livesearch':
							$key = $field_key . '-' . $option;

							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$ajax_data = [
								'filter_id' => $filter_id,
								'key' => $key,
								'field_id' => $field_id
							];
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('live search', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_switcher($key, $val, $filter_id, 'avalon23_save_filter_field_option', $ajax_data),
								'notes' => esc_html__('A list of products appears below the text search.', 'avalon23-products-filter'),
							];							
							break;						
						case 'tax_search':
							$key = $field_key . '-' . $option;

							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$ajax_data = [
								'filter_id' => $filter_id,
								'key' => $key,
								'field_id' => $field_id
							];
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Search by taxonomies', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_switcher($key, $val, $filter_id, 'avalon23_save_filter_field_option', $ajax_data),
								'notes' => esc_html__('Text search also works on attributes and categories', 'avalon23-products-filter'),
							];							
							break;
						case 'min':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Min', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => '' === $val ? -1 : $val,
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('Set custom min value. By default value is -1 and auto definition through database will be applied.', 'avalon23-products-filter'),
							];


							break;

						case 'max':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Max', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => '' === $val ? -1 : $val,
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('Set custom min value. By default value is -1 and auto definition through database will be applied.', 'avalon23-products-filter'),
							];

							break;

						case 'as-mselect':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$ajax_data = [
								'filter_id' => $filter_id,
								'key' => $key,
								'field_id' => $field_id
							];
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('MultiSelect', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_switcher($key, $val, $filter_id, 'avalon23_save_filter_field_option', $ajax_data),
								'notes' => esc_html__('This setting allows the user to select multiple terms at the same time.', 'avalon23-products-filter'),
							];

							break;

						case 'mselect-logic':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);

							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Logic', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									'IN' => 'IN',
									'AND' => 'AND',
									'NOT IN' => 'NOT IN'
										], $val),
								'notes' => esc_html__('Logic of the selected terms. Comparison logic within one taxonomy', 'avalon23-products-filter'),
							];

							break;
						case 'meta-logic':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);

							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Logic', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									'IN' => 'IN',
									'NOT IN' => 'NOT IN'
										], $val),
								'notes' => esc_html__('Logic of the selected meta values. Comparison logic within one meta', 'avalon23-products-filter'),
							];

							break;
						case 'toggle':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							if (!$val) {
								$val = 'none';
							}
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Toggle', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									'none' => esc_html__('None', 'avalon23-products-filter'),
									'opened' => esc_html__('Show as  open', 'avalon23-products-filter'),
									'closed' => esc_html__('Show as  hidden', 'avalon23-products-filter'),
                                    'mob_closed' => esc_html__('Show as  hidden only for  mobile(Only paid version)', 'avalon23-products-filter')        
                                        ], $val,[
                                            'mob_closed'=>['disabled'=>'disabled','style'=>'color:red;'],
                                        ]),
								'notes' => esc_html__('Displaying a filter item in a dropdown list. Only if title is displayed.', 'avalon23-products-filter'),
							];


							break;
                        case 'front-view':
                            $key = $field_key . '-' . $option;
                            $val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
                            $rows[] = [
                                'pid' => 0,
                                'title' => esc_html__('Front view', 'avalon23-products-filter'),
                                'value' => AVALON23_HELPER::draw_select([
                                    'style' => 'width: 100%',
                                    'class' => 'avalon23-filter-field-option',
                                    'data-table-id' => $filter_id,
                                    'data-key' => $key,
                                    'data-redraw'=>1,
                                    'data-field-id' => $field_id
                                        ], 
										AVALON23_HELPER::get_taxonomy_filter_types(),
										 $val,[
                                            'tax_slider'=>['disabled'=>'disabled','style'=>'color:red;'],
                                            'image'=>['disabled'=>'disabled','style'=>'color:red;'],
                                            'color'=>['disabled'=>'disabled','style'=>'color:red;'],
                                        ]),
                                'notes' => esc_html__('Taxonomy filter type', 'avalon23-products-filter'),
                            ];

                            break;
						case 'meta-front-view':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Front view', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-redraw' => 1,
									'data-field-id' => $field_id
										], [
									'textinput' => esc_html__('Text input', 'avalon23-products-filter'),	
									'select' => esc_html__('select', 'avalon23-products-filter'),
									'checkbox_radio' => esc_html__('checkbox/radio', 'avalon23-products-filter'),
									'labels' => esc_html__('labels', 'avalon23-products-filter'),
                                    'image'=>esc_html__('image(Only paid version)', 'avalon23-products-filter'),
                                    'color'=>esc_html__('color(Only paid version)', 'avalon23-products-filter')    		
										], $val,[
                                            'image'=>['disabled'=>'disabled','style'=>'color:red;'],
                                            'color'=>['disabled'=>'disabled','style'=>'color:red;'],
                                        ]),
								'notes' => esc_html__('Meta filter type', 'avalon23-products-filter'),
							];							
							break;	
						case 'meta_options':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Meta options', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('textarea', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								], strval($val)),
								'notes' => json_encode(avalon23()->filter_items->meta->get_meta_terms( $key, $filter_id )),
							];
							break;		
						case 'text_field':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);						
														
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Text field', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('textarea', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'style' => 'height: 250px;',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								], strval($val)),
								'notes' => esc_html__('Text, HTML tags and shortcodes', 'avalon23-products-filter'),
							];
							break;						
						case 'tax_title':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Title', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => strval($val),
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => htmlspecialchars(esc_html__('Taxonomy custom title', 'avalon23-products-filter')),
							];
							break;
						case 'user_roles':
							$key = $field_key . '-' . $option;
							global $wp_roles;
							$roles = $wp_roles->get_names();
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Select user roles', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option avalon23-multiple-select',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id,
										//'multiple'=>''				  
										], $roles, $val),
								'notes' => esc_html__('Select user roles to be displayed', 'avalon23-products-filter'),
							];
							break;
						case 'hierarchy-title':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);

							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Hierarchy Title', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => strval($val),
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => htmlspecialchars(esc_html__('Use these symbol ^ to separate the title by level: Country^State^City', 'avalon23-products-filter')),
							];
							break;
						case 'image':
							$key = $field_key . '-' . $option;

							$options = array();
							$filter = avalon23()->filter_items->get_by_field_key($filter_id, $field_key);
							if (isset($filter['options'])) {
								$options = (array) json_decode($filter['options'], true);
							}
							$terms = get_terms(array(
								'taxonomy' => $field_key,
								'hide_empty' => false
							));

							$images_fields = '';
							$uploader_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_change_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id);
							$uploader_delete_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_delete_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							foreach ($terms as $term) {
								$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_' . $term->slug);
								$uploader_data['data-key'] = $key . '_' . $term->slug;
								$uploader_delete_data['data-key'] = $key . '_' . $term->slug;
								$images_fields .= AVALON23_HELPER::draw_html_item('li', array(), AVALON23_HELPER::draw_image_uploader($val, $uploader_data, $uploader_delete_data) .
												AVALON23_HELPER::draw_html_item('label', array(), $term->name));
							}


							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Images', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_toggle_item(esc_html__('Images show/hide', 'avalon23-products-filter'), AVALON23_HELPER::draw_html_item('ul', array(), $images_fields)),
								'notes' => AVALON23_HELPER::draw_html_item('a', [
									'href' => 'https://avalon23.dev/how-to-increase-the-filter-image/',
									'target' => '_blank',
										], htmlspecialchars(esc_html__('if you need to resize the image', 'avalon23-products-filter'))),
							];


							break;
						case 'show_hierarchy_images':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$ajax_data = [
								'filter_id' => $filter_id,
								'key' => $key,
								'field_id' => $field_id
							];
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Show images', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_switcher($key, $val, $filter_id, 'avalon23_save_filter_field_option', $ajax_data),
								'notes' => esc_html__('Display images for hierarchical levels on top of the filter', 'avalon23-products-filter'),
							];
						
							break;
						case 'hierarchy_images':
							$key = $field_key . '-' . $option;
							$terms_i = get_terms(array(
								'taxonomy' => $field_key,
								'hide_empty' => false
							));
							$images_fields = '';
							$uploader_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_change_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
									);
							$uploader_delete_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_delete_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							$level = AVALON23_HELPER::get_taxonomy_level_count_max($terms_i);
							for ($i=0;$i < $level; $i++ ) {
								$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_' . $i);
								$uploader_data['data-key'] = $key . '_' . $i;
								$uploader_delete_data['data-key'] = $key . '_' . $i;
								$num_text = $i + 1;
								$images_fields .= AVALON23_HELPER::draw_html_item('li', array(), AVALON23_HELPER::draw_image_uploader($val, $uploader_data, $uploader_delete_data) .
												AVALON23_HELPER::draw_html_item('label', array(), esc_html__('Level', 'avalon23-products-filter') . $num_text ));
							}							
							
							$shortcode = " [avalon23_h_images filter_id='" . $filter_id . "' taxonomies='" . $field_key . "'] ";
							
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Hierarchy images', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_toggle_item(esc_html__('Images show/hide', 'avalon23-products-filter'), AVALON23_HELPER::draw_html_item('ul', array(), $images_fields)),
								'notes' => esc_html__('These are the images that will be displayed when choosing a taxonomy of a certain level. To show this image use this shortcode', 'avalon23-products-filter')
								. $shortcode
								. AVALON23_HELPER::draw_html_item('a', [
									'href' => 'https://avalon23.dev/how-to-increase-the-filter-image/',
									'target' => '_blank',
										], htmlspecialchars(esc_html__('if you need to resize the image', 'avalon23-products-filter'))),
							];
							
							break;
						case 'meta-image':
							$key = $field_key . '-' . $option;

							$filter = avalon23()->filter_items->get_by_field_key($filter_id, $field_key);
							
							$terms = avalon23()->filter_items->meta->get_meta_terms($field_key, $filter_id);
							
							$images_fields = '';
							$uploader_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_change_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id);
							$uploader_delete_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_delete_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							foreach ($terms as $term_key=>$term_name) {
								$term_key_id = sanitize_key($term_key);
								$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_' . $term_key_id);
								$uploader_data['data-key'] = $key . '_' . $term_key_id;
								$uploader_delete_data['data-key'] = $key . '_' . $term_key_id;
								$images_fields .= AVALON23_HELPER::draw_html_item('li', array(), AVALON23_HELPER::draw_image_uploader($val, $uploader_data, $uploader_delete_data) .
												AVALON23_HELPER::draw_html_item('label', array(), $term_name));
							}


							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Images', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_toggle_item(esc_html__('Images show/hide', 'avalon23-products-filter'), AVALON23_HELPER::draw_html_item('ul', array(), $images_fields)),
								'notes' => AVALON23_HELPER::draw_html_item('a', [
									'href' => 'https://avalon23.dev/how-to-increase-the-filter-image/',
									'target' => '_blank',
										], htmlspecialchars(esc_html__('if you need to resize the image', 'avalon23-products-filter'))),
							];


							break;	
						case 'meta-color':
							$key = $field_key . '-' . $option;
							$terms = avalon23()->filter_items->meta->get_meta_terms($field_key, $filter_id);
							$filter = avalon23()->filter_items->get_by_field_key($filter_id, $field_key);


							$images_fields = '';
							$uploader_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_change_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							$uploader_delete_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_delete_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							$color_data = array(
								'class' => 'avalon23-filter-field-option avalon23-color-field',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							foreach ($terms as $term_key=>$term_name) {
								$term_key_id = sanitize_key($term_key);
								
								$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_img_' . $term_key_id);
								$color = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_' . $term_key_id);
								if (!$color) {
									$color = '#000000';
								}
								$color_data['value'] = $color;
								$color_data['data-key'] = $key . '_' . $term_key_id;
								$uploader_data['data-key'] = $key . '_img_' . $term_key_id;
								$uploader_delete_data['data-key'] = $key . '_img_' . $term_key_id;
								$images_fields .= AVALON23_HELPER::draw_html_item('li', array(), AVALON23_HELPER::draw_image_uploader($val, $uploader_data, $uploader_delete_data) .
												AVALON23_HELPER::draw_color_piker($color_data) .
												AVALON23_HELPER::draw_html_item('label', array(), $term_name));
							}

							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Colors', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_toggle_item(esc_html__('Colors show/hide', 'avalon23-products-filter'), AVALON23_HELPER::draw_html_item('ul', array(), $images_fields)),
								'notes' => htmlspecialchars(esc_html__('For complex colors you can use an image. Images take priority over color.', 'avalon23-products-filter')),
							];


							break;							
						case 'color':
							$key = $field_key . '-' . $option;
							$options = array();
							$filter = avalon23()->filter_items->get_by_field_key($filter_id, $field_key);
							if (isset($filter['options'])) {
								$options = (array) json_decode($filter['options'], true);
							}
							$terms = get_terms(array(
								'taxonomy' => $field_key,
								'hide_empty' => false
							));

							$images_fields = '';
							$uploader_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_change_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							$uploader_delete_data = array(
								'href' => 'javasctipt: void(0);',
								'onclick' => 'return avalon23_delete_image(this);',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							$color_data = array(
								'class' => 'avalon23-filter-field-option avalon23-color-field',
								'data-table-id' => $filter_id,
								'data-field-id' => $field_id
							);
							foreach ($terms as $term) {
								$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_img_' . $term->slug);
								$color = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key . '_' . $term->slug);
								if (!$color) {
									$color = '#000000';
								}
								$color_data['value'] = $color;
								$color_data['data-key'] = $key . '_' . $term->slug;
								$uploader_data['data-key'] = $key . '_img_' . $term->slug;
								$uploader_delete_data['data-key'] = $key . '_img_' . $term->slug;
								$images_fields .= AVALON23_HELPER::draw_html_item('li', array(), AVALON23_HELPER::draw_image_uploader($val, $uploader_data, $uploader_delete_data) .
												AVALON23_HELPER::draw_color_piker($color_data) .
												AVALON23_HELPER::draw_html_item('label', array(), $term->name));
							}

							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Colors', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_toggle_item(esc_html__('Colors show/hide', 'avalon23-products-filter'), AVALON23_HELPER::draw_html_item('ul', array(), $images_fields)),
								'notes' => htmlspecialchars(esc_html__('For complex colors you can use an image. Images take priority over color.', 'avalon23-products-filter')),
							];


							break;
						case 'dynamic_recount':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							if (null == $val) {
								$val = -1;
							}
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Dynamic recount', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									-1 => esc_html__('Default', 'avalon23-products-filter'),
									0 => esc_html__('No', 'avalon23-products-filter'),
									1 => esc_html__('Yes', 'avalon23-products-filter'),
										], $val),
								'notes' => esc_html__('Actual counter values in the context of the current search', 'avalon23-products-filter'),
							];

							break;
						case 'hide_empty_terms':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							if (null == $val) {
								$val = -1;
							}
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Hide empty terms', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id,
									'disabled' => 'disabled'										
										], [
									-1 => esc_html__('Default', 'avalon23-products-filter'),
									0 => esc_html__('No', 'avalon23-products-filter'),
									1 => esc_html__('Yes', 'avalon23-products-filter'),
										], $val),
								'notes' => '<span class="avalon23_free">' . esc_html__('Hide terms that have a count of zero(Only paid version)', 'avalon23-products-filter') . '</span>',
							];

							break;
						case 'checkbox_template':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);

							if (null == $val) {
								$val = 0;
							}
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Template', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									0 => esc_html__('One column', 'avalon23-products-filter'),
									1 => esc_html__('Line', 'avalon23-products-filter'),
									2 => esc_html__('Two columns', 'avalon23-products-filter'),
									3 => esc_html__('Three columns', 'avalon23-products-filter'),
									4 => esc_html__('Four', 'avalon23-products-filter'),
									5 => esc_html__('Auto columns', 'avalon23-products-filter'),
										], $val),
								'notes' => esc_html__('How to display filter items(checkbox/radio)', 'avalon23-products-filter'),
							];

							break;
						case 'show_count':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);

							if (null == $val) {
								$val = -1;
							}
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Show count', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									-1 => esc_html__('Default', 'avalon23-products-filter'),
									0 => esc_html__('No', 'avalon23-products-filter'),
									1 => esc_html__('Yes', 'avalon23-products-filter'),
										], $val),
								'notes' => esc_html__('Show counter - how many products these terms have', 'avalon23-products-filter'),
							];

							break;
						case 'show_title':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);

							if (null == $val) {
								$val = 1;
							}
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Show Title', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'style' => 'width: 100%',
									'class' => 'avalon23-filter-field-option',
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									0 => esc_html__('No', 'avalon23-products-filter'),
									1 => esc_html__('Yes', 'avalon23-products-filter'),
										], $val),
								'notes' => esc_html__('Show/Hide label', 'avalon23-products-filter'),
							];

							break;
						case 'exclude':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Exclude', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'style' => 'width: 250px;',
									'type' => 'text',
									'value' => empty($val) ? '' : $val,
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('Using comma, write terms ids you want to exclude from the filter. Leave empty to disable.', 'avalon23-products-filter'),
							];

							break;

						case 'include':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter_items->options->field_options->extract_from($col['options'], $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Include', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_html_item('input', [
									'class' => 'avalon23-filter-field-option',
									'style' => 'width: 250px;',
									'type' => 'text',
									'value' => empty($val) ? '' : $val,
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
								]),
								'notes' => esc_html__('Using comma, write terms ids you want to see in the filter, another ones will not be displayed. Leave empty to disable.', 'avalon23-products-filter'),
							];

							break;

						case 'calendar-data-type':
							$key = $field_key . '-' . $option;
							$val = avalon23()->filter->options->get_option($filter_id, $field_key, $key);
							$rows[] = [
								'pid' => 0,
								'title' => esc_html__('Data type', 'avalon23-products-filter'),
								'value' => AVALON23_HELPER::draw_select([
									'class' => 'avalon23-filter-field-option',
									'type' => 'text',
									'value' => $val,
									'data-table-id' => $filter_id,
									'data-key' => $key,
									'data-field-id' => $field_id
										], [
									'unixtimestamp' => 'unixtimestamp',
									'datetime' => 'datetime',
										], $val),
								'notes' => esc_html__('In ACF is used datetime data type', 'avalon23-products-filter'),
							];

							break;
						default:
							$row = apply_filters('avalon23_fields_options_row_extend', false, array(
								'option' => $option,
								'field_key' => $field_key,
								'filter_id' =>$filter_id,
								'field_id' => $field_id
							));
							if ($row) {
								$rows[] = $row;
							}	
							
					}
				}
			}
		}

		//+++

		$args = apply_filters('avalon23_fields_options_extend', [
			'filter_id' => $filter_id,
			'field_key' => $field_key,
			'field_id' => $field_id,
			'rows' => $rows
		]);
	}

	return $args;
});

