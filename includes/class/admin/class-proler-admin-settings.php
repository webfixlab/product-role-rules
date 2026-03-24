<?php
/**
 * Role based pricing admin settings class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      3.0
 */

if ( ! class_exists( 'Proler_Admin_Settings' ) ) {

	/**
	 * Role based settings admin class
	 */
	class Proler_Admin_Settings {

		/**
		 * If we should update global settings.
		 *
		 * @var string
		 */
		private static $page = '';

		/**
		 * Class init hooks
		 */
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'save_plugin_settings' ) );

			add_action( 'save_post', array( __CLASS__, 'save_settings' ), 30, 3 );
			add_action( 'woocommerce_ajax_save_product_variations', array( __CLASS__, 'save_settings' ), 30, 1 );
		}

		/**
		 * Save settings initialization
		 */
		public static function save_plugin_settings() {
			if ( ! isset( $_POST['proler_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['proler_settings_nonce'] ) ), 'proler_settings' ) ) {
				return;
			}

			$page       = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			self::$page = 'global';

			if ( 'proler-newrole' === $page ) {
				self::add_new_role();
			} else {
				self::save_settings();
			}
		}

		/**
		 * Save settings
		 *
		 * @param int     $post_id product post id for saving settings meta data.
		 * @param object  $post    admin post object.
		 * @param boolean $update  whether post is being updated or not.
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		 */
		public static function save_settings( $post_id = 0, $post = array(), $update = false ) {
			global $proler__;

			if ( isset( $_POST['proler_product_settings_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['proler_product_settings_nonce'] ) ), 'proler_product_settings' ) ) {
				self::$page = 'product';
			}

			if ( empty( self::$page ) ) {
				return;
			}

			// General settings data.
			foreach ( $proler__['general_settings'] as $field ) {
				$key = $field['key'];
				if ( ! isset( $_POST[ $key ] ) ) {
					continue;
				}

				update_option( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
			
			$data = isset( $_POST['proler_data'] ) ? sanitize_text_field( wp_unslash( $_POST['proler_data'] ) ) : '';
			if( empty( $data ) ){
				return;
			}

			if( 0 === $post_id ){
				update_option( 'proler_role_table', json_decode( $data, true ) ); // Is it necessary? Couldn't I use this when retrieving it?
				return;
			}

			if ( isset( $post->post_type ) && 'product' === $post->post_type ) {
				error_log( '[saved product settings to ' . $post_id . ']' );
				update_post_meta( $post_id, 'proler_data', json_decode( $data, true ) ); // Is it necessary? Couldn't I use this when retrieving it?
			}
		}

		/**
		 * Add new user role
		 *
		 * Must start with letter and only letters, digits, '_' ( underscore ) and ' ' ( space ) allowed
		 * Must be more than three (3) characters long
		 * Must not exists before, as user role
		 * 'Customer' user role must exists or defined before
		 */
		public static function add_new_role() {
			global $proler__;

			if ( ! isset( $_POST['proler_admin_new_role'] ) ) {
				return;
			}
			if ( ! check_admin_referer( 'proler_admin_create_new_role_customer' ) ) {
				return;
			}

			$msg            = '';
			$new_role       = sanitize_user( wp_unslash( $_POST['proler_admin_new_role'] ) );
			$new_role_clean = str_replace( '-', '_', sanitize_title( $new_role ) );
			$is_valid       = false;

			if ( preg_match( '/^[a-zA-Z][a-zA-Z0-9_ ]*$/', $new_role ) && strlen( $new_role ) >= 3 ) {
				$is_valid = true;
			} else {
				$msg = sprintf( __( 'Sorry, role name must start with a letter and only include letters, digits, spaces, and underscores, and be at least 3 characters long.', 'product-role-rules' ) );
			}

			// all available user roles.
			$all_roles   = array_keys( wp_roles()->roles );
			$all_roles[] = 'visitor';

			$str = '<strong>' . esc_html( $new_role ) . '</strong>';

			if ( $is_valid && in_array( $new_role_clean, $all_roles, true ) ) {
				$is_valid = false;

				$msg = sprintf(
					// translators: Placeholder %1$s is role name.
					__( 'Sorry, cannot add %1$s role, already exists.', 'product-role-rules' ),
					wp_kses_post( $str )
				);
			}

			if ( $is_valid && in_array( 'customer', $all_roles, true ) ) {
				add_role( $new_role_clean, $new_role, get_role( 'customer' )->capabilities );

				$msg = sprintf(
					// translators: Placeholder %1$s is role name.
					__( '%1$s role created successfully.', 'product-role-rules' ),
					$str
				);

				$proler__['user_role_msg'] = array(
					'cls' => 'saved',
					'msg' => $msg,
				);

				return;
			}

			if ( empty( $msg ) ) {
				$msg = sprintf(
					// translators: Placeholder %1$s is role name.
					__( '%1$s cannot be created.', 'product-role-rules' ),
					$str
				);
			}

			$proler__['user_role_msg'] = array(
				'cls' => 'warning',
				'msg' => $msg,
			);
		}
	}
}

Proler_Admin_Settings::init();
