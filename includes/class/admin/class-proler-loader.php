<?php
/**
 * Plugin loading class.
 *
 * @package    WordPress
 * @subpackage Multiple Products to Cart for WooCommerce
 * @since      7.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Proler_Loader' ) ) {
	/**
	 * Plugin loading class.
	 */
	class Proler_Loader {

		/**
		 * Plugin loader hooks
		 */
		public static function init_hooks(){
			if( !self::has_woocommerce() ) return;

			register_activation_hook( PROLER, array( 'Proler_Install', 'activate' ) );
			register_deactivation_hook( PROLER, array( 'Proler_Install', 'deactivate' ) );

			add_action( 'init', array( __CLASS__, 'init' ) );
			add_action( 'before_woocommerce_init', array( __CLASS__, 'enable_wc_hpos' ) );
		}
		
		public static function init(){
			self::includes();

			load_plugin_textdomain( 'product-role-rules', false, plugin_basename( dirname( PROLER ) ) . '/languages' );

            self::check_pro();
			
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'admin_head', array( __CLASS__, 'admin_menu_style' ) );
			
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );

			// woocommerce product data tab, tab and menu.
			add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'data_tab' ), 10, 1 );
			add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'data_tab_content' ) );
		}
		public static function includes(){
			include PROLER_PATH . 'includes/core-data.php';
            require PROLER_PATH . 'includes/class/admin/class-proler-install.php';
			include PROLER_PATH . 'includes/class/admin/class-proler-notice.php';

			include PROLER_PATH . 'includes/class/admin/class-proler-settings.php';
			include PROLER_PATH . 'includes/class/admin/class-proler-settings-template.php';
			
			include PROLER_PATH . 'includes/class/class-proler-front-helper.php';
			include PROLER_PATH . 'includes/class/class-proler-front-loader.php';
			include PROLER_PATH . 'includes/class/class-proler-cart.php';
		}
        public static function check_pro() {
			global $proler__;

			$proler__['has_pro']  = false;
			$proler__['prostate'] = 'none';

			// do_action( 'proler_admin_change_pro_state' );
            do_action( 'mpca_change_pro_state' );
		}



		/**
		 * Check if WooCommerce is active. If not deactive the plugin.
		 */
		public static function has_woocommerce() {
			if ( !function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin = 'product-role-rules/product-role-rules.php';
			$wc  = 'woocommerce/woocommerce.php';

			$has_woocommerce = is_plugin_active( $wc );
			$has_proler      = is_plugin_active( $plugin );

			if ( $has_proler && !$has_woocommerce ) {
				deactivate_plugins( $plugin );
				add_action( 'admin_notices', array( 'Proler_Notice', 'wc_missing_notice' ) );
			}

			return $has_proler && $has_woocommerce;
		}

		/**
		 * Admin menu items.
		 */
		public static function admin_menu() {
			global $proler__;

			// Main menu.
			add_menu_page(
				__( 'WooCommerce Role', 'product-role-rules' ),
				__( 'Role Pricing', 'product-role-rules' ),
				'manage_options',
				'proler-settings',
				array( __CLASS__, 'global_settings_page' ),
				plugin_dir_url( PROLER ) . 'assets/images/admin-icon.svg',
				56
			);

			// settings submenu - settings.
			add_submenu_page(
				'proler-settings',
				__( 'WooCommerce Role - Settings', 'product-role-rules' ),
				__( 'Role Pricing', 'product-role-rules' ),
				'manage_options',
				'proler-settings'
			);

			// settings submenu - Add new role.
			add_submenu_page(
				'proler-settings',
				__( 'Add new user role', 'product-role-rules' ),
				__( 'Add New Role', 'product-role-rules' ),
				'manage_options',
				'proler-newrole',
				array( __CLASS__, 'new_role_page' )
			);

			// settings submenu - Add new role.
			add_submenu_page(
				'proler-settings',
				__( 'General Settings', 'product-role-rules' ),
				__( 'Settings', 'product-role-rules' ),
				'manage_options',
				'proler-general-settings',
				array( __CLASS__, 'general_settings_page' )
			);

			// Conditional extra links.
			if ( 'activated' !== $proler__['prostate'] ) {
				add_submenu_page(
					'proler-settings',
					__( 'Get PRO', 'product-role-rules' ),
					'<span style="color: #ff8921;">' . __( 'Get PRO', 'product-role-rules' ) . '</span>',
					'manage_options',
					esc_url( $proler__['url']['pro'] )
				);
			}
		}

		public static function global_settings_page() {
			if( ! current_user_can( 'manage_options' ) ) return;
			Proler_Settings_Template::settings_page( 'settings' );
		}
		public static function general_settings_page() {
			if( ! current_user_can( 'manage_options' ) ) return;
			Proler_Settings_Template::settings_page( 'general-settings' );
		}
		public static function new_role_page() {
			if( ! current_user_can( 'manage_options' ) ) return;
			Proler_Settings_Template::settings_page( 'newrole' );
		}

		/**
		 * Admin menu styling
		 */
		public static function admin_menu_style() {
			?>
            <style>
				#toplevel_page_proler-settings img {
					width: 20px;
					opacity: 1 !important;
				}
				.notice h3{
					margin-top: .5em;
					margin-bottom: 0;
				}
			</style>
            <?php
		}

		/**
		 * Admin styles and scripts
		 */
		public static function admin_assets() {
			global $proler__;

			if( !self::in_admin_screen() ) return;

			// enqueue style.
			wp_register_style( 'proler-admin-style', plugin_dir_url( PROLER ) . 'assets/admin/admin.css', array(), PROLER_VER );
			wp_enqueue_style( 'proler-admin-style' );

			wp_register_script( 'proler-admin-script', plugin_dir_url( PROLER ) . 'assets/admin/admin.js', array( 'jquery', 'jquery-ui-slider', 'jquery-ui-sortable' ), PROLER_VER, false );
			wp_enqueue_script( 'proler-admin-script' );

			$dat = apply_filters( 'proler_admin_update_local_var', array(
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'has_pro'         => $proler__['has_pro'],
				'nonce'           => wp_create_nonce( 'ajax-nonce' ),
				'cat_nonce'       => wp_create_nonce( 'category_nonce' ),
				'settings_page'   => admin_url( 'admin.php?page=proler-settings' ),
				'right_arrow'     => plugin_dir_url( PROLER ) . 'assets/images/right.svg',
				'down_arrow'      => plugin_dir_url( PROLER ) . 'assets/images/down.svg',
				'delete_role_msg' => __( 'Are you sure you want to delete this role?', 'product-role-rules' ),
			) );

			wp_localize_script( 'proler-admin-script', 'proler', $dat );
		}
        public static function in_admin_screen(){
            global $proler__;

			$screen = get_current_screen();
			if( !$screen ) return false;

			$decoded_screen_id = urldecode( $screen->id );

			// check if '_page_' exists in the screen id.
			$has_page_in_url = strpos( $decoded_screen_id, '_page_' );

			// if screen id doesn't contain '_page_' and its not in plugin screen ids, return false.
			if ( false === $has_page_in_url ) {
				return in_array( $decoded_screen_id, $proler__['screen'], true );
			}

			foreach ( $proler__['screen'] as $suffix ) {
				// if suffix contains '_page_' and it matches partially with current screen id.
				if ( false !== strpos( $suffix, '_page_' ) && false !== strpos( $decoded_screen_id, $suffix ) ) {
					return true;
				}
			}

			return false;
        }

		/**
		 * Frontend styles and scripts
		 */
		public static function frontend_assets() {
			global $post;

			if( empty( $post ) || ! isset( $post->ID ) ) return;

			// enqueue style.
			wp_register_style( 'proler-front-style', plugin_dir_url( PROLER ) . 'assets/frontend.css', array(), PROLER_VER );
			wp_enqueue_style( 'proler-front-style' );

			wp_register_script( 'proler-front-script', plugin_dir_url( PROLER ) . 'assets/frontend.js', array( 'jquery' ), PROLER_VER, true );
			wp_enqueue_script( 'proler-front-script' );

			// localized data.
			$data = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'product' => $post->ID
			);

			// localize frontend data.
            wp_localize_script( 'proler-front-script', 'proler', $data );
		}



		/**
		 * Add WooCommerce product settings data tab
		 *
		 * @param array $default_tabs current product settings tabs.
		 */
		public static function data_tab( $default_tabs ) {
			global $post;
			global $proler__;

			$product = wc_get_product( $post->ID );
			$type    = $product->get_type();
			$proler__['product'] = array(
				'type' => $type
			);

			if( in_array( $type, array( 'grouped', 'external' ), true ) ) return $default_tabs;

			$default_tabs['role_based_pricing'] = array(
				'label'    => __( 'Role Based Pricing', 'product-role-rules' ),
				'target'   => 'proler_product_data_tab', // data tab panel id to focus.
				'priority' => 60,
				'class'    => array(),
			);

			return $default_tabs;
		}
		 /**
		 * Add product settings data tab content
		 */
		public static function data_tab_content() {
			global $proler__;

			$type = $proler__['product']['type'];
			if( in_array( $type, array( 'grouped', 'external' ), true ) ) return;

			// set a flag in which page the settings is rendering | option page or product level.
			$proler__['which_page'] = 'product';
			?>
			<div id="proler_product_data_tab" class="panel woocommerce_options_panel">
				<div id="mpcdp_settings">
					<div class="mpcdp_settings_section_title proler-page-title"><span class="proler-gradient"><?php echo esc_html__( 'Product Role Based Settings', 'product-role-rules' ); ?></span></div>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Role Based Pricing', 'product-role-rules' ); ?></div>
							<div class="mpcdp_option_description"><?php echo esc_html__( 'Choose Custom to overwrite the global pricing settings.', 'product-role-rules' ); ?></div>
						</div>
						<div class="col-md-6">
							<div class="switch-field">
								<?php Proler_Settings_Template::settings_type(); ?>
							</div>
						</div>
					</div>
					<div class="role-settings-content">
						<?php Proler_Settings_Template::role_settings_content(); ?>
					</div>
					<?php wp_nonce_field( 'proler_settings', 'proler_settings_nonce' ); ?>
				</div>
				<?php Proler_Settings_Template::popup(); ?>
			</div>
			<?php
		}


		/**
		 * WC high speed order-storage hook
		 */
		public static function enable_wc_hpos() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PROLER, true );
			}
		}
	}
}

Proler_Loader::init_hooks();
