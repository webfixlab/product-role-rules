<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'Proler_Helper' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class Proler_Helper {

		/**
		 * Get product settings
		 *
		 * @param object $product.
		 */
		public static function get_product_settings( $product ) {
			if( ! is_object( $product ) ) return array();

			$id   = $product->get_id();
			$role = self::user_roles()[0];

			// New approach using transients | caching.
			$settings = get_transient( 'proler_settings' );
			if ( false === $settings ) $settings = array();

			if ( ! isset( $settings['global'] ) ) {
				// set global settings.
				$global_data = get_option( 'proler_role_table' );

				if ( ! empty( $global_data ) ) {
					$settings['global'] = self::extract_settings( $global_data['roles'] );
				}
			}

			if ( isset( $settings[ $id ][ $role ] ) ) return $settings[ $id ][ $role ];

			$data            = array();
			$settings[ $id ] = array(
				'id'            => $id,
				'type'          => $product->get_type(),
				'price_suffix'  => $product->get_price_suffix(),
				'title'         => $product->get_title(),
				'url'           => $product->get_permalink(),
				// 'cats'          => $product->get_category_ids()
			);

			if ( $product->is_type( 'variable' ) ) {
				$settings[ $id ]['min_price'] = $product->get_variation_price( 'min', true );
				$settings[ $id ]['max_price'] = $product->get_variation_price( 'max', true );

				$settings[ $id ]['rp'] = $product->get_variation_regular_price();
				$settings[ $id ]['sp'] = $product->get_variation_sale_price();

				$data = get_post_meta( $product->get_id(), 'proler_data', true );
			} elseif ( $product->is_type( 'variation' ) ) {
				$settings[ $id ]['regular_price'] = (float) $product->get_regular_price();
				$settings[ $id ]['sale_price']    = (float) $product->get_sale_price();

				$settings[ $id ]['parent_id'] = $product->get_parent_id();
				$data                         = get_post_meta( $product->get_parent_id(), 'proler_data', true );
			} elseif ( $product->is_type( 'grouped' ) ) {
				$child_prices     = array();
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$children         = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );

				foreach ( $children as $child ) {
					if ( '' !== $child->get_price() ) {
						$child_prices[] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
					}
				}

				$settings[ $id ]['min_price'] = min( $child_prices );
				$settings[ $id ]['max_price'] = max( $child_prices );

				$data = get_post_meta( $product->get_id(), 'proler_data', true );
			} else {
				$settings[ $id ]['regular_price'] = (float) $product->get_regular_price();
				$settings[ $id ]['sale_price']    = (float) $product->get_sale_price();

				$data = get_post_meta( $product->get_id(), 'proler_data', true );
			}

			// if no product level settings found use global settings.
			if ( empty( $data ) || ! isset( $data['proler_stype'] ) || 'default' === $data['proler_stype'] ) {
				$settings[ $id ]['settings'] = $settings['global'] ?? array();
			}

			// if product level settings disabled.
			if ( isset( $data['proler_stype'] ) && 'disable' === $data['proler_stype'] ) {
				$settings[ $id ]['settings'] = false;
			}

			// custom product level settings.
			if ( ! empty( $data ) && 'proler-based' === $data['proler_stype'] ) {
				$settings[ $id ]['settings'] = self::extract_settings( $data['roles'] );
				if( empty( $settings[ $id ]['settings'] ) ){
					$settings[ $id ]['settings'] = $settings['global'] ?? array();
				}
			}

			// Convert saved UTC datetime to wp.
			if( isset( $settings[$id]['settings']['schedule'] ) ){
				$wp_timezone = new DateTimeZone( wp_timezone_string() );
									
				$date_from = $settings[$id]['settings']['schedule']['start'] ?? '';
				$date_to   = $settings[$id]['settings']['schedule']['end'] ?? '';

				$from = new DateTime( $date_from, new DateTimeZone( 'UTC' ) );
				$to   = new DateTime( $date_to, new DateTimeZone( 'UTC' ) );
				
				$from->setTimezone( $wp_timezone );
				$to->setTimezone( $wp_timezone );

				if( ! empty( $date_from ) ){
					$date_from = $from->format( 'Y-m-d H:i:s' );
					$settings[$id]['settings']['schedule']['start'] = $date_from;
				}
				if( ! empty( $date_to ) ){
					$date_to   = $to->format( 'Y-m-d H:i:s' );
					$settings[$id]['settings']['schedule']['end'] = $date_to;
				}
			}

			$cached_settings = [];
			$cached_settings[$id][$role] = $settings[$id];

			// set chache for 10 seconds. why? this should be at least an hour.
			set_transient( 'proler_settings', $cached_settings, 3 );

			return $settings[ $id ];
		}

        /**
		 * Given settings data filter out necessary settings.
		 *
		 * @param array $data settings data array.
		 */
		public static function extract_settings( $data ) {
			$roles           = self::user_roles();
			$global_settings = $data['global'] ?? false;

			foreach ( $data as $role => $settings ) {
				if( 'global' === $role || empty( $settings['pr_enable'] ) ) continue;
				if( in_array( $role, $roles, true ) && ! empty( $settings ) ) return $settings;
			}

			return $global_settings;
		}

        /**
		 * Check if role based settings should apply
		 *
		 * @param array $data role based settings data.
		 */
		public static function if_apply_settings( $data ) {
			if ( false === $data || ( ! isset( $data['settings'] ) || false === $data['settings'] ) ) {
				return false;
			}
			
			$enable = isset( $data['settings']['pr_enable'] ) && ! empty( $data['settings']['pr_enable'] ) ? (bool) $data['settings']['pr_enable'] : true;
			if ( false === $enable ) {
				return false;
			}
			
			// check type.
			if ( isset( $data['settings']['product_type'] ) && ! empty( $data['settings']['product_type'] ) ) {
				if ( $data['type'] !== $data['settings']['product_type'] ) {
					return false;
				}
			}
			
			// check category and that could either be it's parent or in children.
			if ( 'variation' !== $data['type'] && ! self::if_in_cat( $data ) ) {
				return false;
			}

			return apply_filters( 'proler_if_apply_settings', $data );
		}

        /**
		 * Check if current product falls in settings category.
		 */
		public static function if_in_cat( $data ){
			if ( ! isset( $data['settings']['category'] ) || empty( $data['settings']['category'] ) ) {
				return true;
			}

			$cat = (int) $data['settings']['category'];
			$settings_cats = get_term_children( $cat, 'product_cat' ); // category children.
			$settings_cats[] = $cat;
			
			$id = (int) $data['id'];
			$product = wc_get_product( $id );
			if( empty( $product ) ){
				return true;
			}

			$product_cats = $product->get_category_ids();
			if( empty( $product_cats ) ){
				return true;
			}

			// check if category is set in settings.
			$in_cat = false;
			foreach( $product_cats as $cat ){
				if( in_array( $cat, $settings_cats, true ) ){
					$in_cat = true;
					break;
				}
			}
			return $in_cat;

			// if ( ! isset( $data['settings']['category'] ) || empty( $data['settings']['category'] ) ) {
			// 	return true;
			// }

			// $cat = (int) $data['settings']['category'];

			// $settings_cats   = get_term_children( $cat, 'product_cat' ); // category children.
			// $settings_cats[] = $cat;

			// $in = false;
			// foreach( $data['cats'] as $id ){
			// 	if( in_array( $id, $settings_cats, true ) ) {
			// 		$in = true;
			// 		break;
			// 	}
			// }
			
			// return $in;
		}

        /**
		 * Get all user roles
		 */
		public static function user_roles() {
			$userid = get_current_user_id();
			if( 0 === $userid ) return array( 'visitor' );

			// get roles of currently logged in user.
			$user = get_userdata( $userid );
			return $user->roles;
		}



		private function log( $data ) {
			if( true === WP_DEBUG ) {
				if( is_array( $data ) || is_object( $data ) ) {
					error_log( print_r( $data, true ) );
				} else {
					error_log( $data );
				}
			}
		}
	}
}
