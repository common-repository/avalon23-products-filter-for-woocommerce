<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
class Avalon23_Image_Map {

	public $options = array();

	public function __construct() {

		$this->options = array('image-map-main', 'image-map-checked', 'image-map-unchecked', 'coordinates-meta', 'coordinates');

		add_shortcode('avalon23_map', array($this, 'avalon23_map'));
		add_action('wp_enqueue_scripts', array($this, 'add_js'));
		//add filter  type
		add_filter('avalon23_taxonomy_front_view', array($this, 'add_filter_type'));
		add_filter('avalon23_meta_front_view', array($this, 'add_filter_type'));

		add_filter('avalon23_extend_filter_fields', array($this, 'add_settings'), 20, 2);
		add_filter('avalon23_fields_options_row_extend', array($this, 'settings_options'), 20, 2);

		//admin scripts
		add_action('admin_enqueue_scripts', array($this, 'js_css_enqueue'));
	}

	public function js_css_enqueue() {
		wp_enqueue_script('av23-image-map-admin', $this->get_link() . '/js/admin/image_map.js', array(), AVALON23_VERSION);
		wp_enqueue_style('av23-image-map-admin-css', $this->get_link() . '/css/admin/image_map.css', array(), AVALON23_VERSION);
	}

	public function add_js() {
		$prefix = '';
		if (avalon23()->optimize->is_active('js_css')) {
			$prefix = '.min';
		}
		wp_enqueue_script('avalon23-image-map-class', $this->get_link() . 'js/av23-image-map' . $prefix . '.js', array('avalon23-filter'), AVALON23_VERSION);
		wp_enqueue_style('avalon23-image-map-css', $this->get_link() . 'css/image_map' . $prefix . '.css', array(), AVALON23_VERSION);				
		wp_enqueue_script('avalon23-image-map-js', $this->get_link() . 'js/image_map.js', array('avalon23-image-map-class'), AVALON23_VERSION);
		
	}

	public function get_link() {
		return plugin_dir_url(__FILE__);
	}

	public function add_filter_type( $filters ) {
		$filters['image_map'] = esc_html__('Image map', 'avalon23-products-filter');
		return $filters;
	}

	public function settings_options( $row, $args ) {
		if (isset($args['option']) && in_array($args['option'], $this->options)) {
			$col = avalon23()->filter_items->get($args['field_id'], ['field_key', 'options']);
			switch ($args['option']) {
				case 'image-map-main':
				case 'image-map-checked':
				case 'image-map-unchecked':
					$key = $args['field_key'] . '-' . $args['option'];
					$images_fields = '';
					$uploader_data = array(
						'href' => 'javasctipt: void(0);',
						'onclick' => 'return avalon23_change_image(this);',
						'data-table-id' => $args['filter_id'],
						'data-field-id' => $args['field_id'],
						'data-key' => $key,
						'data-save_input' => 1,
					);
					$uploader_delete_data = array(
						'href' => 'javasctipt: void(0);',
						'onclick' => 'return avalon23_delete_image(this);',
						'data-table-id' => $args['filter_id'],
						'data-field-id' => $args['field_id'],
						'data-key' => $key,
						'data-save_input' => 1,
					);
					$title = '';
					//'data-redraw' => 1,
					if ('image-map-main' == $args['option']) {
						$title = esc_html__('Main image Map', 'avalon23-products-filter');
						$notes = esc_html__('The main image on which the filter elements can be located.', 'avalon23-products-filter');
					}
					if ('image-map-checked' == $args['option']) {
						$title = esc_html__('Marker checked', 'avalon23-products-filter');
						$notes = esc_html__('Marker image for the filter element that is selected.', 'avalon23-products-filter');
					}
					if ('image-map-unchecked' == $args['option']) {
						$title = esc_html__('Marker', 'avalon23-products-filter');
						$notes = esc_html__('Marker image for filter element.', 'avalon23-products-filter');
					}

					$val = avalon23()->filter_items->options->field_options->extract_from( $col['options'], $key);

					$input = AVALON23_HELPER::draw_html_item('input', [
								'class' => 'avalon23-filter-field-option avalon23-image-map-input',
								'type' => 'text',
								'value' => $val,
								'data-table-id' => $args['filter_id'],
								'data-key' => $key,
								'data-field-id' => $args['field_id'],
								'data-redraw' => 1
					]);

					$row = [
						'pid' => 0,
						'title' => $title,
						'value' => AVALON23_HELPER::draw_image_uploader($val, $uploader_data, $uploader_delete_data) . $input,
						'notes' => $notes,
					];

					break;
				case 'coordinates-meta':
				case 'coordinates':
					$key = $args['field_key'] . '-' . $args['option'];
					$main_image = avalon23()->filter_items->options->field_options->extract_from($col['options'], $args['field_key'] . '-image-map-main');
					$main_data = wp_get_attachment_image_src($main_image, 'full');
					if (is_array($main_data) && !empty($main_data[0])) {
						$main_image_src = $main_data[0];
					} else {
						$main_image_src = false;
					}

					$marker = avalon23()->filter_items->options->field_options->extract_from($col['options'], $args['field_key'] . '-image-map-unchecked');
					$marker_data = wp_get_attachment_image_src($marker, 'full');
					if (is_array($marker_data) && !empty($marker_data[0])) {
						$marker_src = $marker_data[0];
					} else {
						$marker_src = $this->get_link() . 'img/marker.png';
					}

					$points = array(); //'slug'=>['name', 'x' , 'y']
					if ('coordinates-meta' == $args['option']) {
						$terms = avalon23()->filter_items->meta->get_meta_terms($args['field_key'], $args['filter_id']);
						foreach ($terms as $term_key => $term_name) {
							$term_key_id = sanitize_key($term_key);
							$coord_x = avalon23()->filter_items->options->field_options->extract_from($col['options'], $args['field_key'] . '_x_' . $term_key_id);
							$coord_y = avalon23()->filter_items->options->field_options->extract_from($col['options'], $args['field_key'] . '_y_' . $term_key_id);
							$points[$term_key_id] = array(
								'name' => $term_name,
								'x' => $coord_x,
								'y' => $coord_y,
							);
						}
					} else {
						$terms = get_terms(array(
							'taxonomy' => $args['field_key'],
							'hide_empty' => false
						));
						foreach ($terms as $term) {
							$coord_x = avalon23()->filter_items->options->field_options->extract_from($col['options'], $args['field_key'] . '_x_' . $term->slug);
							$coord_y = avalon23()->filter_items->options->field_options->extract_from($col['options'], $args['field_key'] . '_y_' . $term->slug);
							$points[$term->slug] = array(
								'name' => $term->name,
								'x' => $coord_x,
								'y' => $coord_y,
							);
						}
					}
					$shortcode = '[avalon23_map key="' . $args['field_key'] . '" filter_id="' . $args['filter_id'] . '"]';
					$row = [
						'pid' => 0,
						'title' => esc_html__('Coordinates', 'avalon23-products-filter') . empty($terms),
						'value' => AVALON23_HELPER::draw_toggle_item(esc_html__('Show/hide the image with markers', 'avalon23-products-filter'), $this->draw_coordinates_settings($main_image_src, $marker_src, $points, $args)),
						'notes' => esc_html__('Place filter elements on the main image. You can display this filter anywhere you want using a shortcode:', 'avalon23-products-filter') . $shortcode,
					];
					break;
			}
		}
		return $row;
	}

	public function add_settings( $available_fields, $filter_id ) {

		foreach ($available_fields as $key => $value) {
			if (isset($value['view']) && 'taxonomy' == $value['view']) {
				$view_type = avalon23()->filter->options->get_option($filter_id, $key, "{$key}-front-view");
				if ('image_map' == $view_type) {
					$available_fields[$key]['options'] = ['tax_title', 'front-view', 'as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'image-map-main', 'image-map-checked', 'image-map-unchecked', 'coordinates', 'toggle', 'mselect-logic'];
					$available_fields[$key]['get_draw_data'] = function( $filter_id )use( $key ) {
						$filter_data = avalon23()->filter->get_taxonomy_drawing_data($filter_id, $key);
						
						foreach ($filter_data['options'] as $id => $option) {
							$origin_slug = $option['slug'];
							$t_origin = AVALON23_HELPER::get_term_for_default_lang_by_id($option['id'], $key);
							if ($t_origin) {
								$origin_slug = $t_origin->slug;
							}
							$filter_data['options'][$id]['left'] = (float) avalon23()->filter->options->get_option($filter_id, $key, $key . '_x_' . $origin_slug);
							$filter_data['options'][$id]['top'] = (float) avalon23()->filter->options->get_option($filter_id, $key, $key . '_y_' . $origin_slug);
						}
						$main_image = avalon23()->filter->options->get_option($filter_id, $key, $key . '-image-map-main');
						$main_data = wp_get_attachment_image_src($main_image, 'full');
						$filter_data['main_image'] = false;
						if (is_array($main_data) && !empty($main_data[0])) {
							$filter_data['main_image'] = $main_data[0];
						}
						$marker = avalon23()->filter->options->get_option($filter_id, $key, $key . '-image-map-unchecked');
						$marker_data = wp_get_attachment_image_src($marker, 'full');
						$filter_data['marker'] = $this->get_link() . 'img/marker.png';
						if (is_array($marker_data) && !empty($marker_data[0])) {
							$filter_data['marker'] = $marker_data[0];
						}
						$marker_checked = avalon23()->filter->options->get_option($filter_id, $key, $key . '-image-map-checked');
						$marker_checked_data = wp_get_attachment_image_src($marker_checked, 'full');
						$filter_data['marker_checked'] = $this->get_link() . 'img/marker-checked.png';
						if (is_array($marker_checked_data) && !empty($marker_checked_data[0])) {
							$filter_data['marker_checked'] = $marker_checked_data[0];
						}
						
						return $filter_data;
					};
				}
			} elseif (isset($value['meta_key']) && $value['meta_key']) {
				$view_type = avalon23()->filter->options->get_option($filter_id, $value['meta_key'], "{$value['meta_key']}-meta-front-view");
				if ('image_map' == $view_type) {
					$available_fields[$key]['options'] = ['meta-front-view', 'as-mselect', 'dynamic_recount', 'show_count', 'hide_empty_terms', 'show_title', 'image-map-main', 'image-map-checked', 'image-map-unchecked', 'coordinates-meta', 'toggle', 'meta-logic'];
					$available_fields[$key]['get_draw_data'] = function( $filter_id )use( $key ) {
						$filter_data = avalon23()->filter->get_meta_drawing_data($filter_id, $key);

						foreach ($filter_data['options'] as $id => $option) {
							$filter_data['options'][$id]['left'] = (float) avalon23()->filter->options->get_option($filter_id, $key, $key . '_x_' . $option['id']);
							$filter_data['options'][$id]['top'] = (float) avalon23()->filter->options->get_option($filter_id, $key, $key . '_y_' . $option['id']);
						}

						$main_image = avalon23()->filter->options->get_option($filter_id, $key, $key . '-image-map-main');
						$main_data = wp_get_attachment_image_src($main_image, 'full');
						$filter_data['main_image'] = false;
						if (is_array($main_data) && !empty($main_data[0])) {
							$filter_data['main_image'] = $main_data[0];
						}
						$marker = avalon23()->filter->options->get_option($filter_id, $key, $key . '-image-map-unchecked');
						$marker_data = wp_get_attachment_image_src($marker, 'full');
						$filter_data['marker'] = $this->get_link() . 'img/marker.png';
						if (is_array($marker_data) && !empty($marker_data[0])) {
							$filter_data['marker'] = $marker_data[0];
						}
						$marker_checked = avalon23()->filter->options->get_option($filter_id, $key, $key . '-image-map-checked');
						$marker_checked_data = wp_get_attachment_image_src($marker_checked, 'full');
						$filter_data['marker_checked'] = $this->get_link() . 'img/marker-checked.png';
						if (is_array($marker_checked_data) && !empty($marker_checked_data[0])) {
							$filter_data['marker_checked'] = $marker_checked_data[0];
						}
						return $filter_data;
					};
				}
			}
		}
		return $available_fields;
	}

	public function draw_coordinates_settings( $main_image, $marker, $points, $args ) {
		ob_start()
		?>
						<div class="avalon23-image-map-settings">
							<div class="avalon23-image-map-scale">

		<?php if ($main_image) { ?>	
											<span onclick="avalon23_image_map_scale(this,'+')" class="avalon23-image-map-scale-plus"><span class="dashicons dashicons-insert"></span></span>
											<span onclick="avalon23_image_map_scale(this,'-')" class="avalon23-image-map-scale-minus"><span class="dashicons dashicons-remove"></span></span>
											<span ondrop="avalon23_drop_delete(event)" ondragover="avalon23_dragover_delete(event)" class="avalon23-image-map-delete avalon23-image-map-remove">
												<span class="dashicons dashicons-trash avalon23-image-map-remove"></span>
												<p><?php esc_html_e('Move point here to remove it', 'avalon23-products-filter'); ?></p>	
											</span>
											<div class="avalon23-image-map-wrap" ondrop="avalon23_drop_handler(event)" ondragover="avalon23_dragover_handler(event)">
											<img class="avalon23-image-map-main-img" src="<?php echo esc_url($main_image); ?>">
		<?php
		} else {
			esc_html_e('Please add main image!', 'avalon23-products-filter');
		}
		?>
		<?php
		foreach ($points as $slug => $point) {
			if ((float) $point['x'] > 0 && (float) $point['y'] > 0) {
				$style = 'left: ' . (float) $point['x'] . '%; top:' . (float) $point['y'] . '%; background: url(' . esc_url($marker) . ';';
				?>
														<div class="avalon23-image-draggable" avalon23-data-tooltip="<?php echo esc_textarea($point['name']); ?>"  id="avalon23_img_<?php echo esc_attr($slug); ?>" style="<?php echo esc_attr($style); ?>"  draggable="true" ondragstart="avalon23_dragstart_handler(event)">							
														</div>
		<?php
			}
		}
		?>
								</div>
							</div>
							<ul class="avalon23-image-map-point-list">
		<?php
		$key = $args['field_key'] . '-' . $args['option'];

		foreach ($points as $slug => $point) {
			?>
											<li class="avalon23-image-map-point-item" data-place="avalon23_img_<?php echo esc_attr($slug); ?>">
			<?php
			if ((float) $point['x'] <= 0 || (float) $point['y'] <= 0) {
				?>
														<div class="avalon23-image-draggable" avalon23-data-tooltip="<?php echo esc_textarea($point['name']); ?>"  id="avalon23_img_<?php echo esc_attr($slug); ?>" style="background: url(<?php echo esc_url($marker); ?>);"  draggable="true" ondragstart="avalon23_dragstart_handler(event)">							
														</div>
		<?php
			}
			$key_x = $args['field_key'] . '_x_' . $slug;
			$key_y = $args['field_key'] . '_y_' . $slug;
			?>
												<p class="avalon23-image-map-label"> <?php echo esc_textarea($point['name']); ?></p>
												<input type="text" class="coordinates_left_avalon23_img_<?php echo esc_attr($slug); ?> avalon23-filter-field-option" data-key="<?php echo esc_attr($key_x); ?>"  data-field-id="<?php echo esc_attr($args['field_id']); ?>" data-table-id="<?php echo esc_attr($args['filter_id']); ?>" value="<?php echo (float) $point['x']; ?>">
												<input type="text" class="coordinates_top_avalon23_img_<?php echo esc_attr($slug); ?> avalon23-filter-field-option" data-key="<?php echo esc_attr($key_y); ?>" data-field-id="<?php echo esc_attr($args['field_id']); ?>" data-table-id="<?php echo esc_attr($args['filter_id']); ?>" value="<?php echo (float) $point['y']; ?>">					
											</li>
		<?php
		}
		?>
							</ul>
						</div>
		<?php
		return ob_get_clean();
	}

	public function avalon23_map( $args ) {
		
		$class = 'avalon23-image-map av23-shortcode avalon23-image-map-';
		if (isset($args['filter_id']) && isset($args['key'])) {
			$class .= $args['key'] . '-' . $args['filter_id'];
		}
		$width = '100%';
		if (isset($args['width'])) {
			$width = $args['width'];
		}		
		
		return AVALON23_HELPER::draw_html_item('div', [
					'class' => $class,
					'style' => 'width:' . $width . ';',
						], ' ');	
	}

}

new Avalon23_Image_Map();
