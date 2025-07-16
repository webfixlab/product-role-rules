<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'Proler_Front_Helper' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class Proler_Front_Helper {

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



		/**
		 * Get regular and sale price of a product
		 *
		 * @param array $data settings data.
		 */
		public static function get_prices( $data ) {
			$enable = ! isset( $data['settings']['pr_enable'] ) || empty( $data['settings']['pr_enable'] ) ? false : true;

			if ( empty( $data ) || false === $enable ) return false;

			$has_range = 'variable' === $data['type'] || 'grouped' === $data['type'];
			$prices    = array(
				'rp' => $has_range ? $data['max_price'] : $data['regular_price'],
				'sp' => $has_range ? $data['min_price'] : $data['sale_price']
			);

			return self::apply_discount( $data, $prices );
		}

		/**
		 * Handle product discount
		 *
		 * @param array $data   Settings data.
		 * @param array $prices Regular and sale prices of the product.
		 */
		public static function apply_discount( $data, $prices ) {
			if( !isset( $data['settings']['discount'] ) || empty( $data['settings']['discount'] ) ) return $prices;

			$discount = array(
				'amount' => (float) $data['settings']['discount'],
				'type'   => $data['settings']['discount_type']
			);
			$discount = apply_filters( 'proler_get_discount', $discount, $prices, $data );

			$price = isset( $prices['sp'] ) && ! empty( $prices['sp'] ) ? $prices['sp'] : $prices['rp'];
			$price = !empty( $price ) ? (float) $price : $price;

			$sale_price = empty( $discount['type'] ) || 'percent' === $discount['type'] ? ( $price * ( 100 - $discount['amount'] ) ) / 100 : $price - $discount['amount'];
			$prices['sp'] = max( 0, $sale_price );

			return $prices;
		}

		/**
		 * Variable product price range html
		 *
		 * @param string $price   product price html.
		 * @param object $product product object.
		 * @param array  $data    settings data.
		 */
		public static function price_range( $price, $product, $data ) {
			if( empty( $data ) || !isset( $data['settings'] ) || !isset( $data['settings']['discount'] ) ) return $price;
			
			$discount = $data['settings']['discount'];
			if( empty( $discount ) || 0 === $discount ) return $price;

			$discount = (float) $discount;
			$discount = max(0, ( 100 - $discount ) );

			$if_percent = empty( $data['settings']['discount_type'] ) || 'percent' === $data['settings']['discount_type'] ? true : false;

			$min = $if_percent ? ( $data['min_price'] * $discount ) / 100 : $data['min_price'] - $discount;
			$max = $if_percent ? ( $data['max_price'] * $discount ) / 100 : $data['max_price'] - $discount;

			if( $min !== $max ){
				return wc_price( $min ) . ' - ' . wc_price( $max );
			}else if( $min === $max && $max !== (float) $data['rp'] ){
				return wc_format_sale_price( $data['rp'], $min );
			}else{
				return wc_price( $data['rp'] );
			}
		}

		/**
		 * Hide price or show placeholder price instead of price
		 *
		 * @param array $data settings data.
		 */
		public static function price_placeholder( $data ) {
			$is_hidden = isset( $data['settings']['hide_price'] ) ? $data['settings']['hide_price'] : '';
			$is_hidden = ! empty( $is_hidden ) && '1' === $is_hidden ? true : false;
			if ( ! $is_hidden ) return false;

			self::remove_add_to_cart();

			return isset( $data['settings']['hide_txt'] ) ? $data['settings']['hide_txt'] : __( 'Price hidden', 'product-role-rules' );
		}

		/**
		 * Remove add to cart button from product page
		 */
		public static function remove_add_to_cart(){
			remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
			remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
			remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
			remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
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
