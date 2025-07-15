<?php
/**
 * Role based pricing admin settings class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      3.0
 */

if ( ! class_exists( 'Proler_Settings' ) ) {

	/**
	 * Role based settings admin class
	 */
	class Proler_Settings {

		/**
		 * Class init hooks
		 */
		public function init() {
			add_action( 'admin_init', array( $this, 'save_plugin_settings' ) );
			add_action( 'save_post', array( $this, 'save_settings' ), 10, 3 );
			add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'save_settings' ), 1 );
		}

		/**
		 * Save settings initialization
		 */
		public function save_plugin_settings() {
            $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
            if( 'proler-newrole' === $page ){
                $this->add_new_role();
                $this->delete_custom_role();
                return;
            }

            $this->save_settings();
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
		public function save_settings( $post_id = 0, $post = array(), $update = false ) {
            global $proler__;

			if ( ! isset( $_POST['proler_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['proler_settings_nonce'] ) ), 'proler_settings' ) ) {
                return;
			}
            
            // get general settings data.
            $general = [];
            foreach( $proler__['general_settings'] as $field ){
                $key = $field['key'];
                if( !isset( $_POST[ $key ] ) ) continue;
                $general[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
            }
            $this->save_general_settings( $general );

			if ( ! isset( $_POST['proler_data'] ) || ( isset( $post->post_type ) && 'product' !== $post->post_type ) ) return;

            // get role based settings data.
			$settings = json_decode( sanitize_text_field( wp_unslash( $_POST['proler_data'] ) ), true );
            $this->save_role_settings( $settings, $post_id );
		}
        public function save_general_settings( $general ){
            foreach( $general as $key => $value ){
                update_option( $key, $value );
            }
        }
        public function save_role_settings( $data, $post_id ){
            if( !empty( $post_id ) ) {
				update_post_meta( $post_id, 'proler_data', $data );
			} else {
				update_option( 'proler_role_table', $data );
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
		public function add_new_role() {
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
			$all_roles = array_keys( wp_roles()->roles );
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
					'msg' => $msg
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
				'msg' => $msg
			);
		}

		/**
		 * Delete custom user role
		 */
		public function delete_custom_role() {
			global $proler__;

			if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'proler_delete_role' ) ) {
				return;
			}

			// if pro not enabled, return.
			if ( ! isset( $proler__['has_pro'] ) || ! $proler__['has_pro'] ) {
				return;
			}

			// get custom user role name to delete.
			$role = isset( $_GET['delete'] ) ? sanitize_key( wp_unslash( $_GET['delete'] ) ) : '';
            $this->remove_role( $role );
        }
        private function remove_role( $role ){
            global $proler__;
            if( empty( $role ) ) return;

			$role_name = '';
			foreach( wp_roles()->roles as $role_slug => $data ) {
                if( $role_slug === $role ) $role_name = $data['name'];
			}
            if( empty( $role_name ) ) return;

			remove_role( $role );

			$proler__['user_role_msg'] = array(
				'cls' => 'saved',
				'msg' => sprintf(
                    // translators: Placeholder %1$s is role name that is deleted.
                    __( '%1$s role deleted successfully.', 'product-role-rules' ),
                    "<strong>{$role_name}</strong>"
                )
			);
        }



		/**
         * Template class constructor
         */
        public static function get_settings(){
            global $post;
			return isset( $post->ID ) && !empty( $post->ID ) ? get_post_meta( $post->ID, 'proler_data', true ) : get_option( 'proler_role_table' );
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

$proler_settings_cls = new Proler_Settings();
$proler_settings_cls->init();