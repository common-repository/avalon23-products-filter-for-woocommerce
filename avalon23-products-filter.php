<?php
/**
* Plugin Name: Avalon23 Products Filter for WooCommerce
* Description: New generation of WooCommerce Products Filters
* Requires at least: 6.0
* Tested up to: WP 6.4
* Author: Paradigma Tools
* Author URI: https://avalon23.dev/
* Version: 1.1.5
* Requires PHP: 7.4
* Tags: filters, woocommerce, products
* Text Domain: avalon23-products-filter
* Domain Path: /languages
* WC requires at least: 7.0
* WC tested up to: 8.6
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( in_array( 'avalon23-products-filter/avalon23-products-filter.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return false;
}
//+++
define( 'AVALON23_PATH', plugin_dir_path( __FILE__ ) );
define( 'AVALON23_LINK', plugin_dir_url( __FILE__ ) );
define( 'AVALON23_ASSETS_LINK', AVALON23_LINK . 'assets/' );
define( 'AVALON23_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'AVALON23_VERSION', '1.1.5' );

include_once AVALON23_PATH . 'classes/admin/db_controller.php';

register_activation_hook( AVALON23_PATH . 'avalon23-products-filter.php', function () {
	$db_сontroller = new Avalon23_DB_Controller();
	$db_сontroller->create_db_tables();
} );
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
//classes
include_once AVALON23_PATH . 'data/fields.php';
include_once AVALON23_PATH . 'data/settings.php';
include_once AVALON23_PATH . 'data/fields-options.php';
include_once AVALON23_PATH . 'data/filter-options.php';

include_once AVALON23_PATH . 'classes/storage.php';
include_once AVALON23_PATH . 'classes/helper.php';
include_once AVALON23_PATH . 'classes/admin/admin.php';
include_once AVALON23_PATH . 'classes/admin/settings.php';
include_once AVALON23_PATH . 'classes/vocabulary.php';
include_once AVALON23_PATH . 'classes/admin/predefinition.php';
include_once AVALON23_PATH . 'classes/filter.php';
include_once AVALON23_PATH . 'classes/admin/skins.php';

include_once AVALON23_PATH . 'classes/admin/filter-items.php';
include_once AVALON23_PATH . 'classes/admin/filter-meta.php';
include_once AVALON23_PATH . 'classes/admin/filter-options.php';
include_once AVALON23_PATH . 'classes/admin/filter-items-fields-options.php';

include_once AVALON23_PATH . 'classes/admin/filters.php';
include_once AVALON23_PATH . 'classes/admin/rate_alert.php';

include_once AVALON23_PATH . 'classes/url-parse.php';
include_once AVALON23_PATH . 'classes/seo.php';

//extensions
include_once AVALON23_PATH . 'ext/enabled.php';

//optimize
include_once AVALON23_PATH . 'classes/optimization.php';

//compatibility
include_once AVALON23_PATH . 'compatibility/compatibility.php';

//29-05-2022
if ( ! class_exists( 'Avalon23' ) ) {
	class Avalon23 {

		public $tables = null;
		public $filter = null;
		public $predefinition = null;
		public $skins = null;
		public $filter_items = null;
		public $settings = null;
		public $vocabulary = null;
		public $admin = null;
		public $optimize = null;
		public $compatibility = null;
		public $notes_for_free = false;

		public $available_fields = array();

		public $filters = null;
		public $seo = null;
		public $db_сontroller = null;
		public $rate_alert = null;

		public function __construct() {
			$this->admin = new Avalon23_Admin();
			$this->settings = new Avalon23_Settings();
			$this->vocabulary = new Avalon23_Vocabulary();
			$this->compatibility = new Avalon23_Compatibility();
			$this->db_сontroller = new Avalon23_DB_Controller();

			//check db
			$this->db_сontroller->check_db();

			add_shortcode( 'avalon23', array( $this, 'avalon23_shortcode' ) );
			add_shortcode( 'avalon23_button', array( $this, 'avalon23_button' ) );
			add_shortcode( 'avalon23_h_images', array( $this, 'h_images_shortcode' ) );

			add_action( 'wp_ajax_avalon23_get_smth', array( $this, 'get_smth' ) );
			add_action( 'wp_ajax_nopriv_avalon23_get_smth', array( $this, 'get_smth' ) );
			//toggle filter
			add_action( 'avalon23_before_filter_draw', array( $this, 'before_filter_draw' ) );
			add_action( 'avalon23_after_filter_draw', array( $this, 'after_filter_draw' ) );

			add_action( 'wp_ajax_avalon23_import_data', array( $this, 'import_data' ) );

			//html drawindg
			add_action( 'avalon23_draw_popup', array( $this, 'draw_popup' ) );
			add_action( 'avalon23_draw_settings_table', array( $this, 'draw_settings_table' ) );
			add_action( 'avalon23_draw_main_table', array( $this, 'draw_main_table' ) );

			$this->filter = new Avalon23_Filter();
			$this->seo = new Avalon23_SEO();
			$this->predefinition = new Avalon23_Predefinition();
			$this->skins = new Avalon23_Skins();
			$this->filters = new Avalon23_Filters();
			$this->filter_items = new Avalon23_FilterItems();
			$this->optimize = new Avalon23_Optimization();

			add_action( 'admin_init', function () {
				if ( AVALON23_HELPER::can_manage_data() ) {
					add_filter( 'plugin_action_links_' . AVALON23_PLUGIN_NAME, function ($links) {
						return array_merge( array (
							'<a href="' . admin_url( 'admin.php?page=avalon23' ) . '">' . esc_html__( 'Filters', 'avalon23-products-filter' ) . '</a>',
							'<a target="_blank" href="' . esc_url( 'https://avalon23.dev/documentation/' ) . '"><span class="icon-book"></span>&nbsp;' . esc_html__( 'Documentation', 'avalon23-products-filter' ) . '</a>',
							'<a target="_blank" style="color: red; font-weight: bold;" href="' . esc_url( 'https://woocommerce.com/products/avalon23-products-filter-for-woocommerce/?quid=680f780906698c1f7013b62da75d710b' ) . '">' . esc_html__( 'Go Pro!', 'avalon23-products-filter' ) . '</a>'
						), $links );
					}, 50 );
				}
			}, 9999 );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			add_action( 'widgets_init', array( $this, 'register_widgets' ), 99 );

			if ( is_admin() ) {
				$this->rate_alert = new AVALON23_RATE_ALERT( $this->notes_for_free );
			}

		}

		public function admin_enqueue_scripts() {

			wp_enqueue_style( 'avalon23-system', AVALON23_ASSETS_LINK . 'css/admin/system.css', [], uniqid() );

			if ( isset ( $_GET['page'] ) && 'avalon23' == $_GET['page'] ) {

				wp_enqueue_media();

				wp_enqueue_script( 'avalon23-helper', AVALON23_LINK . 'assets/js/helper.js', [], uniqid(), true );


				wp_enqueue_style( 'selectm-23', AVALON23_ASSETS_LINK . 'css/selectm-23.css', [], uniqid() );
				wp_enqueue_script( 'selectm-23', AVALON23_ASSETS_LINK . 'js/selectm-23.js', [], uniqid() );

				wp_enqueue_style( 'avalon23-growls', AVALON23_ASSETS_LINK . 'css/growls.css', [], uniqid() );
				wp_enqueue_style( 'avalon23-popup-23', AVALON23_ASSETS_LINK . 'css/popup-23.css', [], uniqid() );
				wp_enqueue_style( 'avalon23-switcher-23', AVALON23_ASSETS_LINK . 'css/switcher-23.css', [], uniqid() );
				wp_enqueue_style( 'avalon23-options', AVALON23_ASSETS_LINK . 'css/admin/options.css', [], uniqid() );


				wp_enqueue_script( 'data-table-23', AVALON23_ASSETS_LINK . 'js/data-table-23/data-table-23.js', [], uniqid(), true );
				wp_enqueue_style( 'data-table-23', AVALON23_ASSETS_LINK . 'js/data-table-23/data-table-23.css', [], uniqid() );

				wp_enqueue_script( 'avalon23-generated-tables', AVALON23_ASSETS_LINK . 'js/admin/generated-tables.js', [ 'data-table-23' ], uniqid(), true );
				wp_enqueue_script( 'popup-23', AVALON23_ASSETS_LINK . 'js/popup-23.js', [], uniqid() );
				wp_enqueue_script( 'alasql', AVALON23_ASSETS_LINK . 'js/admin/alasql.min.js', [], '0.5.5', true );

				wp_enqueue_script( 'avalon23-spectrum', AVALON23_ASSETS_LINK . 'js/spectrum/spectrum.min.js', array(), uniqid() );
				wp_enqueue_style( 'avalon23-spectrum', AVALON23_ASSETS_LINK . 'js/spectrum/spectrum.min.css', array(), uniqid() );


				//codeEditor
				$custom_css_settings = [];
				$custom_css_settings['codeEditor'] = wp_enqueue_code_editor( array(
					'type' => 'text/css',
					'lineNumbers' => true,
					'indentUnit' => 2,
					'tabSize' => 2
				) );
				wp_localize_script( 'jquery', 'custom_css_settings', $custom_css_settings );
				wp_enqueue_script( 'code-editor' );
				wp_enqueue_style( 'code-editor' );
				wp_enqueue_style( 'wp-codemirror' );
				wp_enqueue_script( 'htmlhint' );
				wp_enqueue_script( 'csslint' );
				wp_enqueue_script( 'jshint' );

				wp_enqueue_script( 'avalon23-options', AVALON23_ASSETS_LINK . 'js/admin/options.js', [ 'data-table-23', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ], uniqid(), true );
				$this->wp_localize_script( 'avalon23-options' );
			}
		}

		public function wp_localize_script( $handle ) {

			wp_localize_script( $handle, 'avalon23_helper_vars', [ 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'assets_url' => AVALON23_LINK . 'assets/',
				'flags' => [], //flags for custom js actions which should not be inited twice
				'__GLOBAL_SETTINGS_KEY_EXAMPLE__' => Avalon23_Settings::get( 'hide_shop_cart_btn' ),
				'selected_lang' => apply_filters( 'avalon23_current_lang', get_locale() ),
				'lang' => [ 
					'loading' => Avalon23_Vocabulary::get( esc_html__( 'Loading ...', 'avalon23-products-filter' ) ),
					'no_data' => Avalon23_Vocabulary::get( esc_html__( 'No Data!', 'avalon23-products-filter' ) ),
					'error' => Avalon23_Vocabulary::get( esc_html__( 'Error!', 'avalon23-products-filter' ) ),
					'items' => Avalon23_Vocabulary::get( esc_html__( 'Products', 'avalon23-products-filter' ) ),
					'page' => Avalon23_Vocabulary::get( esc_html__( 'Page', 'avalon23-products-filter' ) ),
					'pages' => Avalon23_Vocabulary::get( esc_html__( 'Pages', 'avalon23-products-filter' ) ),
					'creating' => Avalon23_Vocabulary::get( esc_html__( 'Creating', 'avalon23-products-filter' ) ),
					'created' => Avalon23_Vocabulary::get( esc_html__( 'Created!', 'avalon23-products-filter' ) ),
					'clear' => Avalon23_Vocabulary::get( esc_html__( 'Clear', 'avalon23-products-filter' ) ),
					'cleared' => Avalon23_Vocabulary::get( esc_html__( 'Cleared!', 'avalon23-products-filter' ) ),
					'saving' => Avalon23_Vocabulary::get( esc_html__( 'Saving', 'avalon23-products-filter' ) ),
					'saved' => Avalon23_Vocabulary::get( esc_html__( 'Saved!', 'avalon23-products-filter' ) ),
					'adding' => Avalon23_Vocabulary::get( esc_html__( 'Adding', 'avalon23-products-filter' ) ),
					'added' => Avalon23_Vocabulary::get( esc_html__( 'Added!', 'avalon23-products-filter' ) ),
					'deleting' => Avalon23_Vocabulary::get( esc_html__( 'Deleting', 'avalon23-products-filter' ) ),
					'deleted' => Avalon23_Vocabulary::get( esc_html__( 'Deleted!', 'avalon23-products-filter' ) ),
					'updating' => Avalon23_Vocabulary::get( esc_html__( 'Updating', 'avalon23-products-filter' ) ),
					'cloning' => Avalon23_Vocabulary::get( esc_html__( 'Cloning', 'avalon23-products-filter' ) ),
					'cloned' => Avalon23_Vocabulary::get( esc_html__( 'Cloned!', 'avalon23-products-filter' ) ),
					'sure' => Avalon23_Vocabulary::get( esc_html__( 'Sure?', 'avalon23-products-filter' ) ),
					'm_notice' => Avalon23_Vocabulary::get( esc_html__( 'Notice!', 'avalon23-products-filter' ) ),
					'm_warning' => Avalon23_Vocabulary::get( esc_html__( 'Warning!', 'avalon23-products-filter' ) ),
					'm_error' => Avalon23_Vocabulary::get( esc_html__( 'Error!', 'avalon23-products-filter' ) ),
					'reset' => Avalon23_Vocabulary::get( esc_html__( 'Reset', 'avalon23-products-filter' ) ),
					'shortcodes_help' => Avalon23_Vocabulary::get( esc_html__( 'Shortcodes Help', 'avalon23-products-filter' ) ),
					'help' => Avalon23_Vocabulary::get( esc_html__( 'Help', 'avalon23-products-filter' ) ),
					'products' => Avalon23_Vocabulary::get( esc_html__( 'Products', 'avalon23-products-filter' ) ),
					'calendar23_names' => apply_filters( 'avalon23-get-calendar-names', [] ),
					'load_more' => apply_filters( 'avalon23-lang-load-more', Avalon23_Vocabulary::get( esc_html__( 'Load More', 'avalon23-products-filter' ) ) ),
					'next' => esc_html__( 'Next', 'avalon23-products-filter' ),
					'prev' => esc_html__( 'Previous', 'avalon23-products-filter' ),
					'select' => Avalon23_Vocabulary::get( esc_html__( 'select', 'avalon23-products-filter' ) ),
					'show_filter' => apply_filters( 'avalon23_show_filter_btn_txt', '<span class="dashicons-before dashicons-filter"></span>' . Avalon23_Vocabulary::get( esc_html__( 'show', 'avalon23-products-filter' ) ) ),
					'hide_filter' => apply_filters( 'avalon23_show_filter_btn_txt_hide', '<span class="dashicons-before dashicons-filter"></span>' . Avalon23_Vocabulary::get( esc_html__( 'hide', 'avalon23-products-filter' ) ) ),
					'filter_field_popup_title' => Avalon23_Vocabulary::get( esc_html__( 'Table #{0}; filter field: {1}', 'avalon23-products-filter' ) ),
					'product_title' => Avalon23_Vocabulary::get( esc_html__( 'Product title', 'avalon23-products-filter' ) ),
					'importing' => Avalon23_Vocabulary::get( esc_html__( 'Importing', 'avalon23-products-filter' ) ) . ' ...',
					'imported' => Avalon23_Vocabulary::get( esc_html__( 'Imported!', 'avalon23-products-filter' ) ),
					'online' => Avalon23_Vocabulary::get( esc_html__( 'Online!', 'avalon23-products-filter' ) ),
					'offline' => Avalon23_Vocabulary::get( esc_html__( 'Offline!', 'avalon23-products-filter' ) ),
					'filtering' => Avalon23_Vocabulary::get( esc_html__( 'Filtering', 'avalon23-products-filter' ) ),
					'filter' => Avalon23_Vocabulary::get( esc_html__( 'Filter', 'avalon23-products-filter' ) ),
					'search' => Avalon23_Vocabulary::get( esc_html__( 'Search', 'avalon23-products-filter' ) ),
					'free' => Avalon23_Vocabulary::get( esc_html__( 'Only paid version', 'avalon23-products-filter' ) ),
					'free_meta' => Avalon23_Vocabulary::get( esc_html__( 'In this version, only two meta fields are available', 'avalon23-products-filter' ) ),
					'done' => Avalon23_Vocabulary::get( esc_html__( 'Done!', 'avalon23-products-filter' ) )
				]
			]
			);
		}

		public function init() {

			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			$this->check_if_clear_cache();

			load_plugin_textdomain( 'avalon23-products-filter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			add_action( 'wp_head', array( $this, 'wp_head' ), 9999 );

			if ( AVALON23_HELPER::can_manage_data() ) {
				add_action( 'admin_menu', function () {
					add_submenu_page( 'woocommerce', __( 'Avalon23', 'woocommerce' ), __( 'Avalon23', 'woocommerce' ), 'publish_posts', 'avalon23', function () {
						$args = [];
						include ( str_replace( array ( '/', '\\' ), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/options.php' ) );
					} );
				}, 99 );
			}

			//***

			add_action( 'wp_print_footer_scripts', function () {
				$this->draw_popup();
			} );
		}

		public function wp_head() {

			if ( ! $this->optimize->is_active( 'js_css' ) ) {
				wp_enqueue_script( 'avalon23-helper', AVALON23_LINK . 'assets/js/helper.js', [], AVALON23_VERSION, true );

				wp_enqueue_style( 'avalon23-filter-general', AVALON23_LINK . 'assets/css/general.css', [], AVALON23_VERSION );
				wp_enqueue_style( 'avalon23-filter-grid', AVALON23_LINK . 'assets/css/grid/blueprint.css', [], AVALON23_VERSION );

				wp_enqueue_style( 'avalon23-popup-23', AVALON23_ASSETS_LINK . 'css/popup-23.css', [], AVALON23_VERSION );
				wp_enqueue_style( 'avalon23-growls', AVALON23_ASSETS_LINK . 'css/growls.css', [], AVALON23_VERSION );
				wp_enqueue_style( 'avalon23-switcher-23', AVALON23_ASSETS_LINK . 'css/switcher-23.css', [], AVALON23_VERSION );

				wp_enqueue_style( 'calendar-23', AVALON23_ASSETS_LINK . 'css/calendar-23.css', [], AVALON23_VERSION );
				wp_enqueue_script( 'calendar-23', AVALON23_ASSETS_LINK . 'js/calendar-23.js', [], AVALON23_VERSION );

				wp_enqueue_style( 'ranger-23', AVALON23_ASSETS_LINK . 'css/ranger-23.css', [], AVALON23_VERSION );
				wp_enqueue_script( 'ranger-23', AVALON23_ASSETS_LINK . 'js/ranger-23.js', [], AVALON23_VERSION );
				wp_enqueue_script( 'tax-ranger-23', AVALON23_ASSETS_LINK . 'js/tax-ranger-23.js', [], AVALON23_VERSION );

				wp_enqueue_style( 'selectm-23', AVALON23_ASSETS_LINK . 'css/selectm-23.css', [], AVALON23_VERSION );
				wp_enqueue_script( 'selectm-23', AVALON23_ASSETS_LINK . 'js/selectm-23.js', [], AVALON23_VERSION );

				wp_enqueue_script( 'select-23', AVALON23_ASSETS_LINK . 'js/select-23.js', [], AVALON23_VERSION );

				wp_enqueue_script( 'popup-23', AVALON23_ASSETS_LINK . 'js/popup-23.js', [], AVALON23_VERSION );

				wp_enqueue_script( 'avalon23-general', AVALON23_LINK . 'assets/js/general.js', [], AVALON23_VERSION, true );
				$this->wp_localize_script( 'avalon23-general' );

			} else {
				//optimize
				// CSS https://cssminifier.com/ general.css/blueprint.css /popup-23.css /growls.css /switcher-23.css/calendar-23.css /ranger-23.css /selectm-23.css 
				wp_enqueue_style( 'avalon23-general-css-min', AVALON23_ASSETS_LINK . 'css/compressed-front.min.css', [], AVALON23_VERSION );
				//JS https://jscompress.com/ helper.js/calendar-23.js/ ranger-23.js/ tax-ranger-23.js/ selectm-23.js/ select-23.js/ popup-23.js/ general.js
				wp_enqueue_script( 'avalon23-general-min', AVALON23_LINK . 'assets/js/compressed-front.min.js', [], AVALON23_VERSION, true );
				$this->wp_localize_script( 'avalon23-general-min' );
			}
			wp_enqueue_style( 'dashicons' );

		}

		public function avalon23_shortcode( $args ) {

			$args = (array) $args;

			$filter_id = 0;
			if ( isset ( $args['id'] ) ) {
				$filter_id = intval( $args['id'] );
			}

			$filter_html_id = '';

			//+++

			if ( $filter_id > 0 && ! isset ( $args['filter_html_id'] ) ) {
				$filter_html_id = $this->filter_items->options->get( $filter_id, 'filter__html_id', '' );
			}
			//toggle
			if ( $filter_id > 0 && ! isset ( $args['toggle_filter'] ) ) {
				$args['toggle_filter'] = $this->filter_items->options->get( $filter_id, 'toggle_filter', 'none' );
			}

			if ( isset ( $args['filter_html_id'] ) ) {
				$filter_html_id = $args['filter_html_id'];
			}

			if ( empty ( $filter_html_id ) ) {
				$filter_html_id = uniqid( 'f' );
			}

			//***
			$skin = '';

			if ( isset ( $args['skin'] ) ) {
				$skin = $args['skin'];
			} else {
				$skin = $this->skins->get( $filter_id );
			}

			if ( ! isset ( $args['classes'] ) ) {
				$args['classes'] = '';
			}
			$args['classes'] .= ' avalon23-style-' . $skin;
			$this->skins->include_css_file( $skin );
			//***	  

			if ( $filter_id ) {
				$style = Avalon23_Settings::get_table_custom_prepared_css( $filter_id, $filter_html_id );
				if ( $style ) {
					$style_key = 'avalon23-filter-general';

					if ( $this->optimize->is_active( 'js_css' ) ) {
						$style_key = 'avalon23-general-css-min';
					}
					wp_add_inline_style( $style_key, $style );
				}
			}


			return AVALON23_HELPER::render_html( 'views/filter.php', array(
				'filter_html_id' => $filter_html_id,
				'filter_id' => $filter_id,
				'published' => ( $filter_id > 0 && $this->filters->get( $filter_id ) ) ? $this->filters->get( $filter_id )['status'] : true,
				'classes' => $args['classes'],
				'shortcode_args' => $args,
				'filter' => $this->filter->draw_filter_form_data( ( isset ( $args['filter_form'] ) ? $args['filter_form'] : '' ), $filter_id ),
				'hide_filter_form' => isset ( $args['hide_filter_form'] ) ? boolval( $args['hide_filter_form'] ) : ( $filter_id > 0 ? boolval( $this->filter_items->options->get( $filter_id, 'hide_filter_form', false ) ) : false ),
			) );
		}

		public function register_widgets() {
			include_once AVALON23_PATH . 'classes/widget.php';
			include_once AVALON23_PATH . 'classes/woo-price-filter-widget.php';
			register_widget( 'Avalon23FilterWidget' );
		}

		public function h_images_shortcode( $args ) {
			$class = 'avalon23-h-image av23-shortcode avalon23-h-image-';
			if ( isset ( $args['filter_id'] ) && isset ( $args['taxonomies'] ) ) {
				$class .= $args['taxonomies'] . '-' . $args['filter_id'];
			}
			$width = '300px';
			if ( isset ( $args['width'] ) ) {
				$width = $args['width'];
			}

			return AVALON23_HELPER::draw_html_item( 'div', [ 
				'class' => $class,
				'style' => 'width:' . $width . ';',
			], ' ' );
		}
		public function avalon23_button( $args ) {

			$title = '';
			if ( isset ( $args['title'] ) ) {
				$title = Avalon23_Vocabulary::get( $args['title'] );
				unset( $args['title'] );
			}

			if ( empty ( $title ) ) {
				$title = Avalon23_Vocabulary::get( esc_html__( 'click me', 'avalon23-products-filter' ) );
			}

			$popup_title = '';
			if ( isset ( $args['popup_title'] ) ) {
				$popup_title = Avalon23_Vocabulary::get( $args['popup_title'] );
				unset( $args['popup_title'] );
			}

			$class = '';
			if ( isset ( $args['class'] ) ) {
				$class = $args['class'];
				unset( $args['class'] );
			}

			$args_json = json_encode( $args );

			return AVALON23_HELPER::draw_html_item( 'a', [ 
				'href' => "javascript: new Popup23({title: \"{$popup_title}\",posted_id: -1, what: JSON.stringify({$args_json})}); void(0);",
				'title' => $popup_title,
				'class' => $class
			], $title );
		}

		//ajax for popup
		public function get_smth() {
			$res = '';
			$what = '';
			if ( isset ( $_REQUEST['what'] ) ) {
				$what = strip_tags( sanitize_text_field( $_REQUEST['what'] ) );
			}

			if ( isset ( $_REQUEST['posted_id'] ) && -1 === intval( $_REQUEST['posted_id'] ) ) {
				//done such because shortcode can has diff arguments and no filter_id
				$shortcode_button_args = $what;
				$what = 'shortcode_button';
			}

			//***

			switch ( $what ) {
				case 'shortcode_button':
					$shortcode_button_args = json_decode( html_entity_decode( stripslashes( $shortcode_button_args ) ), ARRAY_A );
					$attr = '';
					foreach ( $shortcode_button_args as $attr_name => $value ) {
						$attr .= ' ' . $attr_name . "='" . $value . "'";
					}
					?>
					<div class="avalon23-content-in-popup">
						<?php echo do_shortcode( '[avalon23 ' . $attr . ']' ); ?>
					</div>
					<?php
					die();
					break;

				case 'export':
					$data = [];
					$data['avalon23_filters'] = $this->filters->gets();
					$data['avalon23_filters_fields'] = $this->filter_items->gets();
					$data['avalon23_filters_meta'] = $this->filter_items->meta->gets();
					$data['avalon23_vocabulary'] = $this->vocabulary->gets();
					$data['avalon23_settings'] = get_option( 'avalon23_settings', [] );
					if ( $data['avalon23_settings'] && ! is_array( $data['avalon23_settings'] ) ) {
						$data['avalon23_settings'] = json_decode( $data['avalon23_settings'], true );
					}

					$data = apply_filters( 'avlon23_export_data', $data );
					?>
					<textarea readonly="readonly"
						style="width: 100%; height: 500px"><?php echo esc_textarea( json_encode( $data, JSON_HEX_QUOT | JSON_HEX_TAG ) ); ?></textarea>
					<?php
					die();
					break;

				case 'import':
					?>
					<div class="avalon23-notice">
						<?php esc_html_e( 'ATTENTION! All existed Avalon23 data will be wiped!', 'avalon23-products-filter' ); ?>
					</div>
					<textarea autofocus="" id="avalon23-import-text" style="width: 100%; height: 300px;"></textarea><br />
					<a href="javascript: avalon23_import_options();void(0);" class="button avalon23-dash-btn">
						<span class="dashicons-before dashicons-arrow-up-alt"></span>
						<?php esc_html_e( 'Import', 'avalon23-products-filter' ); ?>
					</a>
					<?php
					die();
					break;

				default:
					$what = '';
					if ( isset ( $_REQUEST['what'] ) ) {
						$what = json_decode( stripslashes( sanitize_text_field( $_REQUEST['what'] ) ), true );
					}
					if ( isset ( $what['call_action'] ) && isset ( $_REQUEST['call_id'] ) ) {
						$res = apply_filters( $what['call_action'], $what['more_data'], sanitize_text_field( $_REQUEST['call_id'] ) );
					}

					break;
			}
			?>
			<div class="avalon23-content-in-popup">
				<?php echo esc_textarea( $res ); ?>
			</div>
			<?php

			die();
		}

		public function import_data() {
			if ( AVALON23_HELPER::can_manage_data() ) {
				if ( ! empty ( $_REQUEST['data'] ) ) {
					$data = json_decode( stripslashes( sanitize_text_field( $_REQUEST['data'] ) ), true );

					if ( json_last_error() == JSON_ERROR_NONE && is_array( $data ) ) {
						$data = apply_filters( 'avalon23_import_data', $data );
						foreach ( $data as $key => $value ) {
							switch ( $key ) {
								case 'avalon23_filters':
									$this->filters->import( $value );
									break;

								case 'avalon23_filters_fields':
									$this->filter_items->import( $value );
									break;

								case 'avalon23_filters_meta':
									$this->filter_items->meta->import( $value );
									break;

								case 'avalon23_vocabulary':
									$this->vocabulary->import( $value );
									break;

								case 'avalon23_settings':
									update_option( $key, $value );
									break;
							}
						}

					}
				}
			}

			die ( 'done' );
		}
		public function check_if_clear_cache() {

			$prev_version = get_option( 'avalon23_current_version', '' );
			if ( AVALON23_VERSION != $prev_version ) {
				if ( $this->optimize->is_active( 'recount' ) ) {
					$this->optimize->clear_count_cache();
				}

				update_option( 'avalon23_current_version', AVALON23_VERSION );
			}

		}
		public function get_available_fields( $id ) {

			if ( ! isset ( $this->available_fields[ $id ] ) ) {
				$this->available_fields[ $id ] = apply_filters( 'avalon23_get_available_fields', [], $id );
			}
			return $this->available_fields[ $id ];
		}
		public function draw_popup() {
			include ( str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/popup.php' ) );
		}
		public function draw_settings_table() {
			$this->settings->draw_table_esc();
		}
		public function before_filter_draw( $args ) {
			?>
			<div class='avalon23_filter_wrapper' data-filter_id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				if ( isset ( $args['id'] ) && $args['id'] && ( isset ( $args['toggle_filter'] ) && 'none' != $args['toggle_filter'] ) ) {

					$show = true;
					if ( 'hide' == $args['toggle_filter'] ) {
						$show = false;
					} elseif ( 'hide_mobile' == $args['toggle_filter'] && wp_is_mobile() ) {
						$show = false;
					}
					$show = apply_filters( 'avalon23_toggle_filter_state', $show, $args );
					$show_img = apply_filters( 'avalon23_toggle_filter_open_image', AVALON23_LINK . 'assets/img/filter_down.png', $args );
					$hide_img = apply_filters( 'avalon23_toggle_filter_close_image', AVALON23_LINK . 'assets/img/filter_up.png', $args );
					?>

					<div class='avalon23_toggle_wrapper' style="display:none;">
						<input id="avalon23_toggle_switch_<?php echo esc_attr( $args['id'] ); ?>" type="checkbox" <?php echo esc_attr( ( $show ) ? "checked='checked'" : '' ); ?> class="avalon23_toggle_switch" />
						<label for="avalon23_toggle_switch_<?php echo esc_attr( $args['id'] ); ?>">
							<span class="avalon23_toggle_open">
								<img class="avalon23_toggle_open_img" src="<?php echo esc_attr( $show_img ); ?>"
									alt="<?php esc_html_e( 'Open', 'avalon23-products-filter' ); ?>">
							</span>
							<span class="avalon23_toggle_close">
								<img class="avalon23_toggle_close_img" src="<?php echo esc_attr( $hide_img ); ?>"
									alt="<?php esc_html_e( 'Close', 'avalon23-products-filter' ); ?>">
							</span>
							<span class="av23_wcag_hidden">
								<?php esc_html_e( 'Open/close filter', 'avalon23-products-filter' ); ?>
							</span>
						</label>
						<div class="avalon23_toggled">
							<?php
				}
		}
		public function after_filter_draw( $args ) {
			if ( isset ( $args['id'] ) && $args['id'] && ( isset ( $args['toggle_filter'] ) && 'none' != $args['toggle_filter'] ) ) {
				?>
						</div>
					</div>
					<?php
			}
			?>
			</div>
			<?php
		}
		public function draw_main_table() {

			$table_html_id = 'avalon23-admin-table';
			$hide_text_search = false;
			?>
			<div class='avalon23-table-json-data' data-table-id='<?php echo esc_attr( $table_html_id ); ?>' style='display: none;'>
				<?php
				$json_data = avalon23()->admin->draw_table_data( [ 
					'action' => 'avalon23_admin_table',
					'mode' => 'json',
					'orderby' => 'id',
					'order' => 'desc',
					'per_page_sel_pp' => -1,
					'per_page' => -1,
					'sanitize' => true,
					'table_data' => $this->filters->get_admin_table_rows()
				], $table_html_id, '' );

				echo esc_textarea( $json_data );
				?>
			</div>
			<?php
			include ( str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, AVALON23_PATH . 'views/table.php' ) );
		}

	}
}

/**
 * Check if WooCommerce is active
 * */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	$GLOBALS['Avalon23'] = new Avalon23();

	function avalon23() {
		global $Avalon23;
		return $Avalon23;
	}

	add_action( 'init', array( avalon23(), 'init' ), 99 );
}
