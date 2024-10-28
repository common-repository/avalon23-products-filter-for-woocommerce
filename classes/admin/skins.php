<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Avalon23_Skins {

	private $skin_folder = null;
	private $skin_link = null;
	public  $default_colors = [];

	public function __construct() {
		$this->skin_folder = AVALON23_PATH . 'skins/';
		$this->skin_link = AVALON23_LINK . 'skins/';
		$this->default_colors = array(
			'#F7FBFC', //title
			'#769FCD', //counts checked
			'#B9D7EA', //slider/switch bar
			'#D6E6F2', //inside slider
			'#769FCD', //img checked
			'#769FCD', //border select
			'#587799' //text
			);		

		add_action('avalon23_extend_settings', array($this, 'add_settings'), 99);
	}

	public function get_skins() {
		$skins = [
			'default' => esc_html__('Default', 'avalon23-products-filter'),
		];
		$names = [
			'skin-1' => esc_html__('skin-1(Neutral blue)', 'avalon23-products-filter'),
			'skin-2' => esc_html__('skin-2(Neutral gray)', 'avalon23-products-filter'),
			'skin-3' => esc_html__('skin-3(Natural green)', 'avalon23-products-filter'),
			'skin-4' => esc_html__('skin-4(Orange accent)', 'avalon23-products-filter'),
			'skin-5' => esc_html__('skin-5(Elegant style)', 'avalon23-products-filter'),
			'skin-6' => esc_html__('skin-6(Soothing)', 'avalon23-products-filter'),
			'skin-7' => esc_html__('skin-7(Gray and brown)', 'avalon23-products-filter'),
			'skin-8' => esc_html__('skin-8(Delicate pink)', 'avalon23-products-filter'),
			'skin-9' => esc_html__('skin-9(Gray industrial)', 'avalon23-products-filter'),
			'skin10' => esc_html__('skin10(Warm tones)', 'avalon23-products-filter'),
			'skin11' => esc_html__('skin11(Warm tone with contrast)', 'avalon23-products-filter'),
			'skin12' => esc_html__('skin12(Pink and orange)', 'avalon23-products-filter'),
			'custom_av23' => esc_html__('Custom(select colors on settings)', 'avalon23-products-filter'),
			
		];
		$results = $this->get_css_files($this->skin_folder);

		if ($results) {
			foreach ($results as $key => $value) {
				$basename = basename($value, '.css');
				$skins[$basename] = $basename;
				if (isset($names[$basename]) && $names[$basename]) {
					$skins[$basename] = $names[$basename];
				}
			}
		}

		//***

		if (is_dir($this->get_wp_theme_dir())) {
			$results = $this->get_css_files($this->get_wp_theme_dir());
			if ($results) {
				foreach ($results as $key => $value) {
					$basename = basename($value, '.css');
					$skins[$basename] = $basename;
				}
			}
		}

		return $skins;
	}

	private function get_css_files( $folder ) {
		return glob("{{$folder}*.css}", GLOB_BRACE);
	}

	public function get( $filter_id ) {
		$skin = 'default';

		if (avalon23()->filters->get($filter_id)) {
			$skin = avalon23()->filters->get($filter_id)['skin'];

			if (!$skin) {
				$skin = 'default';
			}
		}

		return $skin;
	}

	public function include_css_file( $skin ) {
		if (!empty($skin)) {

			if ('default' !== $skin) {
				$file = $this->get_wp_theme_dir() . $skin . '.css';
				if (is_file($file)) {
					$css_link = $this->get_wp_theme_link() . $skin . '.css';
				} else {
					$file = $this->skin_folder . $skin . '.css';

					if (is_file($file)) {
						$css_link = $this->skin_link . $skin . '.css';
					}
				}
				if ($css_link) {
					wp_enqueue_style('avalon23-style-' . $skin, $css_link, array(), AVALON23_VERSION);
				}
				if ('custom_av23' == $skin) {
					$css = $this->get_custom_colors();
					if ($css) {
						wp_add_inline_style('avalon23-style-' . $skin, $css);
					}	
					
				}
			}
		}
	}
	public function get_custom_colors() {
		$css = ':root {' . PHP_EOL;
		for ($i = 0; $i < 7; $i++) {
			$default = '#F2F2F2';
			if (isset($this->default_colors[$i])) {
				$default = $this->default_colors[$i];
			}
			$color = Avalon23_Settings::get('skin_color' . $i);	
			if (!$color || -1 == $color) {
				$color = $default;
			}
			$name_op = $i + 1;
			$css .= '--avalon23-custom_av23-color' . $name_op . ': ' . esc_attr($color) . ';' . PHP_EOL;
		}
		$css .= '}' . PHP_EOL;	
		return $css;
	}
	private function get_wp_theme_dir() {
		return get_stylesheet_directory() . '/avalon23-skins/';
	}

	private function get_wp_theme_link() {
		return get_stylesheet_directory_uri() . '/avalon23-skins/';
	}

	public function get_theme_css( $identificator, $table_html_id ) {
		if (!empty($identificator)) {
			$css = '';
			if (is_int($identificator)) {
				$skin = $this->get($identificator);
			} else {
				$skin = $identificator;
			}

			if ('default' !== $skin) {
				$file = $this->get_wp_theme_dir() . $skin . '.css';
				if (is_file($file)) {
					$css = AVALON23_HELPER::render_html($file, ['TID' => $table_html_id], false);
				} else {
					$file = $this->skin_folder . $skin . '.css';
					if (is_file($file)) {
						$css = AVALON23_HELPER::render_html($file, ['TID' => $table_html_id], false);
					}
				}

				if (!empty($css)) {
					$css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
					$css = preg_replace('/\s{2,}/', ' ', $css);
					$css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
					$css = preg_replace('/;}/', '}', $css);
					return $css;
				}
			}
		}

		return '';
	}

	public function add_settings( $rows ) {
		$optimize_settings = array();	
		$colors_values = '';
		
		for ($i = 0; $i < 7; $i++) {
			$default = '#F2F2F2';
			if (isset($this->default_colors[$i])) {
				$default = $this->default_colors[$i];
			}
			$color = Avalon23_Settings::get('skin_color' . $i);	
			if (!$color || -1 == $color) {
				$color = $default;
			}
			
			$color_data = array(
				'class' => 'avalon23-color-options avalon23_override_field_type avalon23_skin_colors',
				'data-table-id' => 0,
				'data-field-id' => 0,
				'data-field' => 'skin_color' . $i,
				'value' => $color,
			);
			$name_op = $i + 1;
			$colors_values .=  '#' . $name_op . AVALON23_HELPER::draw_color_piker($color_data);
		}
			

		$optimize_settings = [
			[
				'title' => esc_html__('Сolors for custom skin', 'avalon23-products-filter'),
				'value' => $colors_values,
				'notes' => esc_html__('#1 Title,button background;  #2 counts, checked elements, hover, tooltip background;   #3 slider bar;  #4 filter elements background(checkbox,radio, labels, slider container);   #5 image checked icon;   #6 borders;   #7 text', 'avalon23-products-filter')
			],
		];		
		return array_merge($rows, $optimize_settings);
	}
}
