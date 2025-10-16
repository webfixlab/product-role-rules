<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Front_Settings' ) ) {

	class Proler_Front_Settings {

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
			$settings = !$settings ? [] : $settings;
			if ( ! isset( $settings['global'] ) ) {
				// set global settings.
				$global_data = get_option( 'proler_role_table' );

				if ( ! empty( $global_data ) ) {
					$settings['global'] = self::extract_role_settings( $global_data['roles'] );
				}
			}

			// check if cart item quantity has changed.
			$cart_item_quantity = '';
			if( WC()->cart && isset( $settings[$id][$role] ) && !empty( $settings[$id][$role] ) ){
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ){
					if( $cart_item['product_id'] === $id ){
						$cart_item_quantity = $cart_item['quantity'];
						break;
					}
				}
			}
			
			$settings_qty = $settings[$id][$role]['quantity'] ?? '';
			$if_update    = !empty( $settings_qty ) && $cart_item_quantity !== (int) $settings_qty;

			if ( isset( $settings[ $id ][ $role ] ) && !empty( $settings[ $id ][ $role ] ) && !$if_update ) {
				return $settings[ $id ][ $role ];
			}
			
			$saved_settings = self::extract_product_settings( $id, $product, $settings['global'] );
			// self::log( $saved_settings );
			if( !empty( $saved_settings ) ){
				$saved_settings = self::get_product_details( $saved_settings, $id, $product );
			}

			$saved_settings = apply_filters( 'proler_get_settings', $saved_settings, $product );

            // add - apply_settings filter hook to modify it so that it won't be necessary to call those again.
			$saved_settings = self::apply_settings( $saved_settings );
			// self::log( $saved_settings );

			$cached_settings = [];
			$cached_settings[$id][$role] = $saved_settings;
            
			// set chache for 10 seconds. why? this should be at least an hour.
			set_transient( 'proler_settings', $cached_settings, 2 );

			return $saved_settings;
		}
		public static function extract_product_settings( $id, $product, $global_settings ){
			$actual_id        = $product->is_type( 'variation' ) ? $product->get_parent_id() : $id;
			$product_settings = get_post_meta( $actual_id, 'proler_data', true );

			if ( isset( $product_settings['proler_stype'] ) && 'disable' === $product_settings['proler_stype'] ) {
				return false; // if product level settings disabled.
			}

			$settings = [];
			// if no product level settings found use global settings.
			if ( empty( $product_settings ) || ! isset( $product_settings['proler_stype'] ) || 'default' === $product_settings['proler_stype'] ) {
				$settings = $global_settings;
			}
			
			// custom product level settings.
			$product_settings_type = $product_settings['proler_stype'] ?? '';
			if ( 'proler-based' === $product_settings_type ) {
				$settings = self::extract_role_settings( $product_settings['roles'] );
			}

			$enable = $settings['pr_enable'] ?? ''; // if settings is enabled for this role.
			if( !empty( $enable ) && '1' !== $enable ){
				return false;
			}

			$product_type = $settings['product_type'] ?? ''; // if product type filtering enabled.
			if( !empty( $product_type ) && !$product->is_type( $product_type ) ){
				return false;
			}
			
			// check category and that could either be it's parent or in children.
			if( !self::if_in_cat( $settings, $product ) ){
				return false;
			}

			return $settings;
		}
		public static function if_in_cat( $settings, $product ){
			$cat = $settings['category'] ?? '';
			if( empty( $cat ) ) return true;

			$product_cats = $product->get_category_ids();
			if( empty( $product_cats ) ) return true;

			$cat      = (int) $cat;
			$childs   = get_term_children( $cat, 'product_cat' );
			$childs[] = $cat;

			return count( array_intersect( $childs, $product_cats ) ) > 0;
		}
		public static function get_product_details( $data, $id, $product ){
			$data = array_merge( $data, array(
				'id'           => $id,
				'type'         => $product->get_type(),
				'price_suffix' => $product->get_price_suffix(),
				'title'        => $product->get_title(),
				'url'          => $product->get_permalink(),
				// 'cats'         => $product->get_category_ids()
			) );

			if ( $product->is_type( 'variable' ) ) {
				$data['min_price'] = $product->get_variation_price( 'min', true );
				$data['max_price'] = $product->get_variation_price( 'max', true );

				$data['regular_price'] = $product->get_variation_regular_price();
				$data['sale_price'] = $product->get_variation_sale_price();
			} elseif ( $product->is_type( 'variation' ) ) {
				$data['regular_price'] = (float) $product->get_regular_price();
				$data['sale_price']    = (float) $product->get_sale_price();

				$data['parent_id'] = $product->get_parent_id();
			} elseif ( $product->is_type( 'grouped' ) ) {
				$child_prices     = array();
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$children         = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );

				foreach ( $children as $child ) {
					if ( '' !== $child->get_price() ) {
						$child_prices[] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
					}
				}

				$data['min_price'] = min( $child_prices );
				$data['max_price'] = max( $child_prices );
			} else {
				$data['regular_price'] = (float) $product->get_regular_price();
				$data['sale_price']    = (float) $product->get_sale_price();
			}
			
			return self::get_cart_item_data( $data );
		}
		public static function get_cart_item_data( $data ){
			if( !WC()->cart ){
				return $data;
			}

			foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ){
				if( $cart_item['product_id'] === (int) $data['id'] ){
					$data['quantity']   = $cart_item['quantity'];	
					// $data['cart_price'] = $cart_item['data']->get_price();
					break;
				}elseif( isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] === (int) $data['id'] ){
					$data['quantity']   = $cart_item['quantity'];
					break;
				}
			}

			return $data;
		}

		/**
		 * Given settings data filter out necessary settings.
		 *
		 * @param array $data settings data array.
		 */
		public static function extract_role_settings( $data ) {
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
		
		public static function apply_settings( $data ){
			if( empty( $data ) ) {
				return $data;
			}
			
			// price hidden? apply placeholder price.
			$is_hidden = $data['hide_price'] ?? '';
			$is_hidden = !empty( $is_hidden ) && '1' === $is_hidden ? true : false;
			$data['hide_price'] = $is_hidden;

			if ( $is_hidden ){
				// self::remove_add_to_cart();
				$txt = $data['hide_txt'] ?? '';
				$data['hide_txt'] = empty( $txt ) ? __( 'Price hidden', 'product-role-rules' ) : $txt;
				// return $data;
			}

			// apply discount to all prices including min-max price, regular-sale price etc.
			return self::get_discounted_settings( $data );
		}
		public static function get_discounted_settings( $data ){
			$discount = $data['discount'] ?? '';
			$type     = $data['discount_type'] ?? '';
			if( empty( $discount ) || empty( $type ) ){
				return $data;
			}
			// self::log( 'discount ... ' . $discount . ', ' . $type );
			
			$discount   = empty( $discount ) ? '' : (float) $discount;
			$if_percent = false === strpos( $type, 'percent' ) ? false : true;
			// self::log( $data['id'] . ': ' . $data['title'] . '   --> discount ' . $discount . ', ' . $if_percent );

			$min = $data['min_price'] ?? '';
			$max = $data['max_price'] ?? '';
			$min = empty( $min ) ? '' : (float) $min;
			$max = empty( $max ) ? '' : (float) $max;
			if( !empty( $min ) ) $data['min_price'] = $if_percent ? ( $min - ( $min * $discount ) / 100 ) : $min - $discount;
			if( !empty( $max ) ) $data['max_price'] = $if_percent ? ( $max - ( $max * $discount ) / 100 ) : $max - $discount;
			
			$rp = $data['regular_price'] ?? '';
			$sp = $data['sale_price'] ?? '';
			$rp = empty( $rp ) ? '' : (float) $rp;
			$sp = empty( $sp ) ? '' : (float) $sp;
			// self::log( 'rp/sp ' . $rp . '/'. $sp . ' | min/max ' . $min . '/'. $max );
			// self::log( gettype( $rp )  . ' ' . $rp . ', ' . gettype( $sp ) . ' ' . $sp );
			if( empty( $sp ) && !empty( $rp ) ) $data['regular_price'] = $if_percent ? ( $rp - ( $rp * $discount ) / 100 ) : $rp - $discount;
			elseif( !empty( $sp ) ) $data['sale_price'] = $if_percent ? ( $sp - ( $sp * $discount ) / 100 ) : $sp - $discount;

			// self::log('[free:discounted]');
			// self::log( $data );
			// self::log( 'after ---------- ' . $data['type'] . ' - ' . $data['id'] . ' -----------' );
			// self::log( $data['id'] . ': ' . $data['title'] . '   --> rp/sp ' . $rp . '/' . $sp . '  --  min/max ' . $min . '/' . $max );
			return $data;
			// $discount = apply_filters( 'proler_get_discount', $discount, $prices, $data );
		}



		public static function get_price_html( $price, $product ){
			if( 'external' === $product->get_type() ) return $price;
			
			$settings = self::get_product_settings( $product );
			if( !$settings || empty( $settings ) ) {
				self::log( '[free] settings: none | [return price]' );
				return $price;
			}

			$is_hidden = $settings['hide_price'] ?? '';
			if( !empty( $is_hidden ) && '1' === $is_hidden ){
				self::log( '[free] settings: price hidden.' );
				self::remove_add_to_cart();

				$txt = $settings['hide_txt'] ?? '';
				return empty( $txt ) ? __( 'Price hidden', 'product-role-rules' ) : $txt;
			}

			$min = $settings['min_price'] ?? '';
			$max = $settings['max_price'] ?? '';

			$rp = $settings['regular_price'] ?? '';
			$sp = $settings['sale_price'] ?? '';

			$min = empty( $min ) ? '' : (float) $min;
			$max = empty( $max ) ? '' : (float) $max;
			$rp = empty( $rp ) ? '' : (float) $rp;
			$sp = empty( $sp ) ? '' : (float) $sp;
			// self::log( $settings );
			// self::log( '[free:price] '. $settings['id'] . ': ' . $settings['title'] . '   --> rp/sp ' . $rp . '/' . $sp . '  --  min/max ' . $min . '/' . $max );

			if( !empty( $min ) ){
				if( $min === $max && !empty( $rp ) ){
					if( !empty( $rp ) && !empty( $sp ) ) return wc_format_sale_price( $rp, $sp );
					else return wc_price( $rp );
				} else return wc_price( $min ) . ' - ' . wc_price( $max );
			}else{
				if( !empty( $sp ) ) return wc_format_sale_price( $rp, $sp );
				else return wc_price( $rp );
			}
		}
		public static function get_cart_item_total_price_html( $subtotal, $cart_item, $cart_item_key ){
			$item_price = self::get_product_price( $cart_item['data'] );
			if( empty( $item_price ) ) {
				return $subtotal;
			} elseif( -1 === $item_price ) {
				WC()->cart->remove_cart_item( $cart_item_key );
				return '';
			}
			
			return wc_price( $item_price * $cart_item['quantity'] );
		}
		public static function get_product_price( $product ){
			if( 'external' === $product->get_type() ) return '';
			
			$settings = self::get_product_settings( $product );
			if( !$settings || empty( $settings ) ) return '';

			$is_hidden = $settings['hide_price'] ?? '';
			if( !empty( $is_hidden ) && '1' === $is_hidden ) return -1;

			$rp = $settings['regular_price'] ?? '';
			$sp = $settings['sale_price'] ?? '';
			$rp = empty( $rp ) ? '' : (float) $rp;
			$sp = empty( $sp ) ? '' : (float) $sp;

			return empty( $sp ) ? $rp : $sp;
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
