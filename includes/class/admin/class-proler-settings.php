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
				// $this->log('nonce faild while saving');
				return;
			}

			$this->save_general_settings();

			// check if this is role related scope or not, if not leave this place.
			if ( ! isset( $_POST['proler_data'] ) ) {
				// $this->log('no post data, saving');
				return;
			}

			if ( isset( $post->post_type ) && 'product' !== $post->post_type ) {
				// $this->log('not post type');
				return;
			}

			$data = json_decode( sanitize_text_field( wp_unslash( $_POST['proler_data'] ) ), true );
			$this->log( $data );
            $this->save_role_settings( $data, $post_id );

			// if ( isset( $data['proler_stype'] ) ) {
			// 	$data['proler_stype'] = sanitize_text_field( $data['proler_stype'] );
			// }

			// if ( ! isset( $data['roles'] ) ) {
			// 	if ( 0 !== $post_id ) {
			// 		update_post_meta( $post_id, 'proler_data', $data );
			// 	} else {
			// 		update_option( 'proler_role_table', $data );
			// 	}

			// 	// $this->log('no role data found');
			// 	return;
			// }

			// $rdt = array();

			// foreach ( $data['roles'] as $role => $rd ) {
			// 	$role         = $this->input_sanitize( $role );
			// 	$rdt[ $role ] = array();

			// 	// hide this price?
			// 	if ( isset( $rd['hide_price'] ) ) {
			// 		$rdt[ $role ]['hide_price'] = sanitize_key( $rd['hide_price'] );
			// 	}

			// 	if ( isset( $rd['discount_text'] ) ) {
			// 		$rdt[ $role ]['discount_text'] = sanitize_key( $rd['discount_text'] );
			// 	}

			// 	// if price is hidden, show this message instead of the blank price.
			// 	if ( isset( $rd['hide_txt'] ) ) {
			// 		$val = html_entity_decode( urldecode( $rd['hide_txt'] ) );
			// 		$val = str_replace( ':*dblqt*:', '"', $val );
			// 		$val = str_replace( ':*snglqt*:', '\'', $val );

			// 		$rdt[ $role ]['hide_txt'] = $val;
			// 	}

			// 	if ( isset( $rd['pr_enable'] ) ) {
			// 		$rdt[ $role ]['pr_enable'] = sanitize_key( $rd['pr_enable'] );
			// 	}

			// 	if ( isset( $rd['discount'] ) ) {
			// 		$rdt[ $role ]['discount'] = $this->input_sanitize( $rd['discount'] );
			// 	}
			// 	if ( isset( $rd['discount_type'] ) ) {
			// 		$rdt[ $role ]['discount_type'] = $this->input_sanitize( $rd['discount_type'] );
			// 	}

			// 	if ( isset( $rd['min_qty'] ) ) {
			// 		$rdt[ $role ]['min_qty'] = $this->input_sanitize( $rd['min_qty'] );
			// 	}
			// 	if ( isset( $rd['max_qty'] ) ) {
			// 		$rdt[ $role ]['max_qty'] = $this->input_sanitize( $rd['max_qty'] );
			// 	}

			// 	if ( isset( $rd['category'] ) ) {
			// 		$rdt[ $role ]['category'] = $this->input_sanitize( $rd['category'] );
			// 	}

			// 	if ( isset( $rd['schedule'] ) ) {
			// 		$wp_timezone = new DateTimeZone( wp_timezone_string() );

			// 		$rdt[ $role ]['schedule'] = array();
			// 		if ( isset( $rd['schedule']['start'] ) && !empty($rd['schedule']['start']) ) {
			// 			// $this->log('start set');
			// 			// Convert date of WP timezone to UTC and when using it again convert that to WP.
			// 			$datetime = new DateTime( $this->input_sanitize( $rd['schedule']['start'] ), $wp_timezone );
			// 			$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
			// 			$rdt[ $role ]['schedule']['start'] = $datetime->format( 'Y-m-d H:i:s' );
			// 		}
			// 		if ( isset( $rd['schedule']['end'] ) && !empty($rd['schedule']['end']) ) {
			// 			// $this->log('end set');
			// 			$datetime = new DateTime( $this->input_sanitize( $rd['schedule']['end'] ), $wp_timezone );
			// 			$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
			// 			$rdt[ $role ]['schedule']['end'] = $datetime->format( 'Y-m-d H:i:s' );
			// 		}
			// 		// $this->log($rdt[$role]['schedule']);
			// 	}

			// 	if ( isset( $rd['product_type'] ) ) {
			// 		$rdt[ $role ]['product_type'] = $this->input_sanitize( $rd['product_type'] );
			// 	}

			// 	if ( isset( $rd['hide_regular_price'] ) ) {
			// 		$rdt[ $role ]['hide_regular_price'] = sanitize_key( $rd['hide_regular_price'] );
			// 	}

			// 	if ( isset( $rd['ranges'] ) ) {
			// 		$rdt[ $role ]['ranges'] = array();

			// 		foreach ( $rd['ranges'] as $item ) {
			// 			$tmp = array();

			// 			if ( ! isset( $item['discount_type'] ) ) {
			// 				continue;
			// 			}

			// 			$tmp['discount_type'] = $item['discount_type'];
			// 			if ( isset( $item['min'] ) ) {
			// 				$tmp['min'] = $item['min'];
			// 			}
			// 			if ( isset( $item['max'] ) ) {
			// 				$tmp['max'] = $item['max'];
			// 			}
			// 			if ( isset( $item['discount'] ) ) {
			// 				$tmp['discount'] = $item['discount'];
			// 			}

			// 			$rdt[ $role ]['ranges'][] = $tmp;
			// 		}
			// 	}

			// 	if ( isset( $rd['additional_discount_display'] ) ) {
			// 		$rdt[ $role ]['additional_discount_display'] = $this->input_sanitize( $rd['additional_discount_display'] );
			// 	}
			// }

			// $data['roles'] = $rdt;
			// $this->log($data);

			// if ( ! empty( $post_id ) ) {
			// 	update_post_meta( $post_id, 'proler_data', $data );
			// } else {
			// 	update_option( 'proler_role_table', $data );
			// }
		}
        public function save_general_settings(){
            global $proler__;

			foreach( $proler__['general_settings'] as $field ){
				$key = $field['key'];
				if( !isset( $_POST[ $key ] ) ) continue;

                $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
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

			// f pro not enabled, return.
			if ( ! isset( $proler__['has_pro'] ) || ! $proler__['has_pro'] ) {
				return;
			}

			// get custom user role name to delete.
			$role = isset( $_GET['delete'] ) ? sanitize_key( wp_unslash( $_GET['delete'] ) ) : '';

			if ( empty( $role ) ) {
				return;
			}

			$role_name = '';
			foreach ( wp_roles()->roles as $role_slug => $data ) {
				if ( $role_slug === $role ) {
					$role_name = $data['name'];
				}
			}

			// check if the user role exists.
			if ( empty( $role_name ) ) {
				return;
			}

			remove_role( $role );

			$msg = sprintf(
				// translators: Placeholder %1$s is role name that is deleted.
				__( '%1$s role deleted successfully.', 'product-role-rules' ),
				'<strong>' . $role_name . '</strong>'
			);

			$proler__['user_role_msg'] = array(
				'cls' => 'saved',
				'msg' => $msg
			);
		}

		/**
		 * Custom input sanitization
		 *
		 * @param string $val input value to sanitize.
		 */
		public function input_sanitize( $val ) {
			$val = str_replace( ':*dblqt*:', '"', $val );
			$val = str_replace( ':*snglqt*:', '\'', $val );
			$val = sanitize_text_field( $val );

			return $val;
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