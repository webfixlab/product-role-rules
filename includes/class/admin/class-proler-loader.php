<?php
/**
 * Role based pricing plugin loader class
 *
 * @package    WordPress
 * @subpackage Product Role Rules
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Loader' ) ) {

	/**
	 * Plugin loader main class
	 */
	class Proler_Loader {

		/**
		 * Plugin loader hooks
		 */
		public function init_hooks(){
			if ( ! $this->has_woocommerce() ) {
				return;
			}

			include PROLER_PATH . 'includes/class/admin/class-proler-install.php';

			add_action( 'init', array( $this, 'init' ) );
			add_action( 'before_woocommerce_init', array( $this, 'enable_wc_hpos' ) );
		}
		
		public function init(){
			$this->includes();

			load_plugin_textdomain( 'product-role-rules', false, plugin_basename( dirname( PROLER ) ) . '/languages' );
			
			do_action( 'proler_admin_change_pro_state' );
			
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_head', array( $this, 'admin_menu_style' ) );
			
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) ); // load admin scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'front_enqueue' ) ); // load frontend scripts.
		}

		private function has_woocommerce() {
			if ( !function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin      = 'product-role-rules/product-role-rules.php';
			$base_plugin = 'woocommerce/woocommerce.php';

			$has_base_plugin = is_plugin_active( $base_plugin );
			$has_plugin      = is_plugin_active( $plugin );

			if( !$has_plugin ) return false;

			// Deactive MPC if it's active while WooCommerce isn't.
			if ( $has_plugin && !$has_base_plugin ) {
				deactivate_plugins( $plugin );
				add_action( 'admin_notices', array( 'Proler_Notice', 'wc_missing_notice' ) );
			}

			return $has_plugin && $has_base_plugin;
		}
		
		/**
		 * WC high speed order-storage hook
		 */
		public function enable_wc_hpos() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PROLER, true );
			}
		}

		private function includes(){
			include PROLER_PATH . 'includes/core-data.php';

			// helper functions and templates.
			include PROLER_PATH . 'includes/class/admin/class-proler-settings-helper.php';
			include PROLER_PATH . 'includes/class/admin/class-proler-role-settings.php';

			// settings function.
			include PROLER_PATH . 'includes/class/admin/class-proler-settings-page.php';
			include PROLER_PATH . 'includes/class/admin/class-proler-product-settings.php';
			include PROLER_PATH . 'includes/class/admin/class-proler-settings.php';

			include PROLER_PATH . 'includes/class/class-proler-front-helper.php';
			include PROLER_PATH . 'includes/class/class-proler-product-handler.php';
			include PROLER_PATH . 'includes/class/class-proler-cart-handler.php';
		}

		/**
		 * Add admin menu bar
		 */
		public function admin_menu() {
			global $proler__;

			// Main menu.
			add_menu_page(
				__( 'WooCommerce Role', 'product-role-rules' ),
				__( 'Role Pricing', 'product-role-rules' ),
				'manage_options',
				'proler-settings',
				array( 'Proler_Settings_Page', 'global_settings_page' ),
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
				array( 'Proler_Settings_Page', 'new_role_page' )
			);

			// settings submenu - Add new role.
			add_submenu_page(
				'proler-settings',
				__( 'General Settings', 'product-role-rules' ),
				__( 'Settings', 'product-role-rules' ),
				'manage_options',
				'proler-general-settings',
				array( 'Proler_Settings_Page', 'general_settings_page' )
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

		/**
		 * Add admin menu styling
		 */
		public function admin_menu_style() {
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
		 * Enqueue admin scripts
		 */
		public function admin_enqueue() {
			// check scope, without it return.
			if ( ! $this->is_in_scope() ) {
				return;
			}

			// enqueue style.
			wp_register_style( 'proler-admin-style', plugin_dir_url( PROLER ) . 'assets/admin/admin.css', array(), PROLER_VER );
			wp_enqueue_style( 'proler-admin-style' );

			// enqueue scripts.
			wp_register_script( 'proler-admin-script', plugin_dir_url( PROLER ) . 'assets/admin/admin.js', array( 'jquery', 'jquery-ui-slider', 'jquery-ui-sortable' ), PROLER_VER, false );
			wp_enqueue_script( 'proler-admin-script' );

			wp_localize_script( 'proler-admin-script', 'proler', $this->get_admin_local_data() );
		}
		
		/**
		 * Get admin localized data
		 *
		 * @return array
		 */
		public function get_admin_local_data(){
			global $proler__;

			$local_data = array(
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'has_pro'         => $proler__['has_pro'],
				'nonce'           => wp_create_nonce( 'ajax-nonce' ),
				'cat_nonce'       => wp_create_nonce( 'category_nonce' ),
				'settings_page'   => admin_url( 'admin.php?page=proler-settings' ),
				'right_arrow'     => plugin_dir_url( PROLER ) . 'assets/images/right.svg',
				'down_arrow'      => plugin_dir_url( PROLER ) . 'assets/images/down.svg',
				'delete_role_msg' => __( 'Are you sure you want to delete this role?', 'product-role-rules' ),
			);

			// apply hook for editing localized variables in admin script.
			return apply_filters( 'proler_admin_update_local_var', $local_data );
		}

		/**
		 * Frontend styles and scripts enqueuing
		 */
		public function front_enqueue() {
			global $post;
			if( empty( $post ) || ! isset( $post->ID ) ){
				return;
			}

			// enqueue style.
			wp_register_style( 'proler-frontend-style', plugin_dir_url( PROLER ) . 'assets/frontend.css', array(), PROLER_VER );
			wp_enqueue_style( 'proler-frontend-style' );

			wp_register_script( 'proler-frontend-script', plugin_dir_url( PROLER ) . 'assets/frontend.js', array( 'jquery' ), PROLER_VER, true );
			wp_enqueue_script( 'proler-frontend-script' );

			// localize frontend data.
            wp_localize_script( 'proler-frontend-script', 'proler', $this->get_frontend_local_data() );
		}

		/**
		 * Get localized data for frontend script
		 *
		 * @return array
		 */
		public function get_frontend_local_data(){
			global $post;
			
			$data = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'product' => $post->ID
			);

			return $data;
		}



		/**
		 * Check if the current access scope is within plugin allowed screens
		 */
		public function is_in_scope() {
			global $proler__;

			$screen     = get_current_screen();
			$current_id = urldecode( $screen->id ); // current screen id.
			// $this->log( 'current screen id ' . $current_id );
			// $this->log( $proler__['screen'] );

			if( in_array( $current_id, $proler__['screen'], true ) ) return true;

			$partial_match = true;
			foreach ( $proler__['screen'] as $screen_id ) {
				// if screen id contains '_page_' and it matches partially with current screen id.
				if ( false !== strpos( $screen_id, '_page_' ) && false === strpos( $current_id, $screen_id ) ) {
					$partial_match = false;
				}
			}

			return $partial_match;
		}
		private function log( $data ) {
			if ( true === WP_DEBUG ) {
				if ( is_array( $data ) || is_object( $data ) ) {
					error_log( print_r( $data, true ) );
				} else {
					error_log( $data );
				}
			}
		}
	}
}

$cls = new Proler_Loader();
$cls->init_hooks();
