<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Front_Helper' ) ) {

	class Proler_Front_Helper {

        /**
		 * Get product settings
		 *
		 * @param object $product.
		 */
		public static function get_product_settings( $product ) {
			if( ! is_object( $product ) ) {
				return array();
			}

			$id   = $product->get_id();
			$role = self::user_roles()[0];

			// New approach using transients | caching.
			$settings = get_transient( 'proler_settings' );
			if ( false === $settings ) {
				$settings = array();
			}

			if ( ! isset( $settings['global'] ) ) {
				// set global settings.
				$global_data = get_option( 'proler_role_table' );

				if ( ! empty( $global_data ) ) {
					$settings['global'] = self::extract_settings( $global_data['roles'] );
				}
			}

			if ( isset( $settings[ $id ][ $role ] ) && !empty( $settings[ $id ][ $role ] ) ) {
				return $settings[ $id ][ $role ];
			}

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

            $if_apply = self::if_apply_settings( $settings[ $id ] );
            $settings[$id] = $if_apply ? $settings[$id] : false;

			$cached_settings = [];
			$cached_settings[$id][$role] = $settings[$id];



            // add - apply_settings filter hook to modify it so that it won't be necessary to call those again.


            
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
				if ( 'global' === $role ) {
					continue;
				}

				if ( in_array( $role, $roles, true ) && ! empty( $settings ) && ! empty( $settings['pr_enable'] ) ) {
					return $settings;
				}
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
			
			$enable = $data['settings']['pr_enable'] ?? '';
			$enable = empty( $enable ) ? true : (bool) $enable;
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

			return apply_filters( 'proler_if_apply_settings', true, $data );
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
		}

        /**
		 * Get all user roles
		 */
		public static function user_roles() {
			$userid = get_current_user_id();
			if( 0 === $userid ) {
				return array( 'visitor' );
			}

			// get roles of currently logged in user.
			$user  = get_userdata( $userid );
			return $user->roles;
		}



        /**
		 * Get regular and sale price of a product
		 *
		 * @param array $data settings data.
		 */
		public static function get_prices( $data ) {
			$enable = $data['settings']['pr_enable'] ?? '';
			$enable = empty( $enable ) ? true : (bool) $enable;
			if ( empty( $data ) || false === $enable ) {
				return false;
			}

			$has_range = 'variable' === $data['type'] || 'grouped' === $data['type'];
			$prices    = array(
				'rp' => $has_range ? $data['max_price'] : $data['regular_price'],
				'sp' => $has_range ? $data['min_price'] : $data['sale_price']
			);
			// self::log( $prices );

			return self::apply_discount( $data, $prices );
		}

        /**
		 * Handle product discount
		 *
		 * @param array $data   Settings data.
		 * @param array $prices Regular and sale prices of the product.
		 */
		public static function apply_discount( $data, $prices ) {
			$dis  = $data['settings']['discount'] ?? 0;
			$type = $data['settings']['discount_type'] ?? '';
			$discount = array(
				'amount' => (float) $dis,
				'type'   => $type
			);
			$discount = apply_filters( 'proler_get_discount', $discount, $prices, $data );

			$price = isset( $prices['sp'] ) && ! empty( $prices['sp'] ) ? $prices['sp'] : $prices['rp'];
			$price = !empty( $price ) ? (float) $price : $price;

			$sale_price = empty( $discount['type'] ) || 'percent' === $discount['type'] ? ( $price * ( 100 - $discount['amount'] ) ) / 100 : $price - $discount['amount'];
			$prices['sp'] = max( 0, $sale_price );

			return $prices;
		}

        /**
		 * Hide price or show placeholder price instead of price
		 *
		 * @param array $data settings data.
		 */
		public static function price_placeholder( $data ) {
			$is_hidden = $data['settings']['hide_price'] ?? '';
			$is_hidden = !empty( $is_hidden ) && '1' === $is_hidden ? true : false;
			if ( ! $is_hidden ) return false;

			self::remove_add_to_cart();

			$txt = $data['settings']['hide_txt'] ?? '';
			return empty( $txt ) ? __( 'Price hidden', 'product-role-rules' ) : $txt;
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

		private static function log( $data ) {
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
