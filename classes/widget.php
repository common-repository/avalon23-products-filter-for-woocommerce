<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23FilterWidget extends WC_Widget {

	public function __construct() {
		$this->widget_cssclass    = 'avalon23_filter_widget';
		$this->widget_description = esc_html__('To  show one  of  Avalon 23  filters', 'avalon23-products-filter');
		$this->widget_id          = 'avalon23_filter_widget';
		$this->widget_name        = esc_html__('Avalon23 Woocommerce filter', 'avalon23-products-filter');
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		$this->widget_start( $args, $instance );
		if (-1 == $instance['filter_id'] || ! $instance['filter_id']) {
			esc_html_e('Please  select a filter  on this widget', 'avalon23-products-filter');
		} else {
			echo do_shortcode('[avalon23 classes="avalon23_widget" id=' . $instance['filter_id'] . ']');
		}
		$this->widget_end( $args );
	}

	public function form( $instance ) {
		$title = esc_html__('Avalon 23  filter', 'avalon23-products-filter');
		if (isset($instance['title'])) {
			$title = $instance['title'];
		}
		$filter_id = -1;
		if (isset($instance['filter_id'])) {
			$filter_id = $instance['filter_id'];
		}
		$device_behavior = 0;
		if (isset($instance['device_behavior'])) {
			$device_behavior = $instance['device_behavior'];
		}
		$all_filters = avalon23()->filters->gets();
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title', 'avalon23-products-filter'); ?></label> 
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('filter_id')); ?>"><?php esc_html_e('Select filter', 'avalon23-products-filter'); ?></label> 
		<?php if (count($all_filters)) { ?>
				<select class="widefat" id="<?php echo esc_attr($this->get_field_id('filter_id')); ?>" name="<?php echo esc_attr($this->get_field_name('filter_id')); ?>">
					<option value="-1" <?php echo esc_attr(( -1 == $filter_id || ! $filter_id ) ? "selected='selected'" : ''); ?> ><?php esc_html_e('Not selected', 'avalon23-products-filter'); ?></option>
			<?php foreach ($all_filters as $filter) : ?>
						<option value="<?php echo esc_attr($filter['id']); ?>" <?php echo esc_attr(( $filter['id'] == $filter_id ) ? "selected='selected'" : ''); ?> ><?php echo esc_html($filter['title']); ?></option>
					<?php endforeach; ?>
				</select>
				<?php } else { ?>
			<p>
				<?php esc_html_e('Please  create filters', 'avalon23-products-filter'); ?> <a href="/wp-admin/admin.php?page=avalon23"><?php esc_html_e('here', 'avalon23-products-filter'); ?></a>
			</p>
			<?php } ?>
		</p>				
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('device_behavior')); ?>"><?php esc_html_e('Show on different devices', 'avalon23-products-filter'); ?></label> 
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('device_behavior')); ?>" name="<?php echo esc_attr($this->get_field_name('device_behavior')); ?>">
				<option value="0" <?php echo esc_attr(( 0 == $device_behavior ) ? "selected='selected'" : ''); ?> ><?php esc_html_e('all devices', 'avalon23-products-filter'); ?></option>
				<option value="1" <?php echo esc_attr(( 1 == $device_behavior ) ? "selected='selected'" : ''); ?> ><?php esc_html_e('Only desctop', 'avalon23-products-filter'); ?></option>
				<option value="2" <?php echo esc_attr(( 2 == $device_behavior ) ? "selected='selected'" : ''); ?> ><?php esc_html_e('Only mobil', 'avalon23-products-filter'); ?></option>
			</select>
		</p>
		<?php
	}

	/*
	 * save widget settings
	 */

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( !empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
		$instance['filter_id'] = ( !empty($new_instance['filter_id']) ) ? strip_tags($new_instance['filter_id']) : -1;
		$instance['device_behavior'] = ( !empty($new_instance['device_behavior']) ) ? strip_tags($new_instance['device_behavior']) : 0;

		return $instance;
	}

}
