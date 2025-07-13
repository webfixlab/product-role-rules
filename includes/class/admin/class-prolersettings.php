<?php
/**
 * Role based pricing admin settings class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      3.0
 */

if ( ! class_exists( 'ProlerSettings' ) ) {

	/**
	 * Role based settings admin class
	 */
	class ProlerSettings {



		/**
		 * Class init hooks
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			// woocommerce product data tab, tab and menu.
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'data_tab' ), 10, 1 );
			add_action( 'woocommerce_product_data_panels', array( $this, 'data_tab_content' ) );
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
				array( $this, 'global_settings_page' ),
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
				array( $this, 'new_role_page' )
			);

			// settings submenu - Add new role.
			add_submenu_page(
				'proler-settings',
				__( 'General Settings', 'product-role-rules' ),
				__( 'Settings', 'product-role-rules' ),
				'manage_options',
				'proler-general-settings',
				array( $this, 'general_settings_page' )
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
		 * Add WooCommerce product settings data tab
		 *
		 * @param array $default_tabs current product settings tabs.
		 */
		public function data_tab( $default_tabs ) {
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
		public function data_tab_content() {
			global $proler__;

			$type = $proler__['product']['type'];
			if( in_array( $type, array( 'grouped', 'external' ), true ) ) return;

			// set a flag in which page the settings is rendering | option page or product level.
			$proler__['which_page'] = 'product';

			$admin_tmp_cls = new ProlerAdminTemplate();
			$admin_tmp_cls->data_tab_content();
		}

		/**
		 * Render global settings page
		 */
		public function global_settings_page() {
			// check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$this->settings_page( 'settings' );
		}

		/**
		 * Render add new role page
		 */
		public function new_role_page() {
			// check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$this->settings_page( 'newrole' );
		}

		/**
		 * Render general settings page
		 */
		public function general_settings_page() {
			// check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$this->settings_page( 'general-settings' );
		}

		/**
		 * Display settings page
		 *
		 * @param string $page_slug curent settings page slug.
		 */
		public function settings_page( $page_slug ){
			$admin_tmp_cls = new ProlerAdminTemplate();
			$admin_tmp_cls->settings_page( $page_slug );
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

$cls = new ProlerSettings();
$cls->init();
