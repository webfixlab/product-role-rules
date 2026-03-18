<?php
/**
 * Frontend product settings functions
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Product_Settings' ) ) {

	/**
	 * Frontend product settings class
	 */
	class Proler_Product_Settings {

		/**
		 * Get product settings
		 *
		 * @param object $product Product object.
		 */
		public static function get_settings( $product ) {
			if ( ! is_object( $product ) ) {
				return array();
			}

			$id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$role = self::user_roles()[0];

			// New approach using transients | caching.
			$cs = get_transient( 'proler_settings' ); // cached settings.
			$cs = ! $cs ? array() : $cs;

			if ( isset( $cs[ $id ] ) && isset( $cs[ $id ][ $role ] ) ) {
				return $cs[ $id ][ $role ];
			}

			// get settings.
			$rs = self::product_settings( $id, $role );
			if ( empty( $rs ) || -1 !== $rs ) { // empty or not disabled.
				$rs = self::global_settings( $role );
			}

			// is disabled role settings.
			if ( isset( $rs['pr_enabled'] ) && '1' !== $rs['pr_enabled'] ) {
				$rs = array();
			}

			// is not in product type.
			$ptype = $rs['product_type'] ?? '';
			if ( ! empty( $rs ) && ! empty( $ptype ) && ! $product->is_type( $ptype ) ) {
				$rs = array();
			}

			// not in set categories.
			if ( ! empty( $rs ) && ! self::is_in_cat( $rs, $product ) ) {
				$rs = array();
			}

			// apply hooks to modify role settings.
			$rs = apply_filters( 'proler_get_settings', $rs, $product );

			// update cache.
			if ( ! isset( $cs[ $id ] ) ) {
				$cs[ $id ] = array();
			}
			$cs[ $id ][ $role ] = $rs;
			set_transient( 'proler_settings', $cs, MINUTE_IN_SECONDS );

			return $rs;
		}

		/**
		 * Get user roles
		 *
		 * @return array
		 */
		private static function user_roles() {
			$uid = get_current_user_id(); // user id.
			if ( 0 === $uid ) {
				return array( 'visitor' );
			}

			// get roles of currently logged in user.
			$user = get_userdata( $uid );
			return $user->roles;
		}

		/**
		 * Get product specific role settings
		 *
		 * @param int    $id   Product ID.
		 * @param string $role User role.
		 */
		private static function product_settings( $id, $role ) {
			$ps = get_post_meta( $id, 'proler_data', true );
			if ( empty( $ps ) ) {
				return array();
			}

			$stype = $ps['proler_style'] ?? ''; // settings type.
			if ( 'disabled' === $stype ) { // settings disabled.
				return -1;
			} elseif ( 'proler-based' !== $stype ) { // set to use global settings.
				return array();
			}

			// role specific product settings.
			return isset( $ps['roles'] ) && isset( $ps['roles'][ $role ] ) && ! empty( $ps['roles'][ $role ] ) ? $ps['roles'][ $role ] : array();
		}

		/**
		 * Get global role based settings
		 *
		 * @param string $role User role.
		 * @return array
		 */
		private static function global_settings( $role ) {
			$gs = get_option( 'proler_role_table' ); // global settings.
			if ( empty( $gs ) ) {
				return array();
			}

			// role specific global settings.
			return isset( $gs['roles'] ) && isset( $gs['roles'][ $role ] ) && ! empty( $gs['roles'][ $role ] ) ? $gs['roles'][ $role ] : array();
		}

		/**
		 * If product in set categories
		 *
		 * @param array  $rs      Role settings.
		 * @param object $product Product object.
		 * @return bool
		 */
		private static function is_in_cat( $rs, $product ) {
			$cats = $rs['category'] ?? array();
			if ( empty( $cats ) ) {
				return true;
			}

			$ids = $product->get_category_ids();
			if ( empty( $ids ) ) {
				return true;
			}

			foreach ( $cats as $cat ) {
				$cat = (int) $cat;
				if ( in_array( $cat, $ids, true ) ) {
					return true;
				}

				// explore child categories.
				$childs = get_term_children( $cat, 'product_cat' );
				$childs = empty( $childs ) ? array( $cat ) : $childs;

				if ( count( array_intersect( $childs, $ids ) ) > 0 ) {
					return true;
				}
			}

			return false;
		}
	}
}
