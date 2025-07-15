<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'Proler_Front_Loader' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class Proler_Front_Loader {

		/**
		 * Frontend hooks initialization
		 */
		public static function init() {
			add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'get_price_html' ), 11, 2 );

			add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'discount_text_loop' ), 11 );
			add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'discount_text_single' ), 10 );

			add_filter( 'woocommerce_product_is_on_sale', array( __CLASS__, 'is_on_sale' ), 20, 2 );
			add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'archive_page_cart_btn' ), 10, 2 );
		}
		


		/**
		 * Get product price html
		 *
		 * @param string $price   product price html.
		 * @param object $product product object.
		 */
		public static function get_price_html( $price, $product ) {
			if( 'external' === $product->get_type() ) return $price;
			
			$data = Proler_Helper::get_product_settings( $product );

			if ( ! Proler_Helper::if_apply_settings( $data ) ) {
				return $price;
			}

			$placeholder = self::price_placeholder( $data );
			if ( false !== $placeholder ) {
				return $placeholder;
			}

			if ( 'variable' === $data['type'] || 'grouped' === $data['type'] ) {
				return self::price_range( $price, $product, $data );
			}

			$prices = self::get_prices( $data );
			if ( ! is_array( $prices ) ) {
				return $price;
			}
			
			return self::render_price_html( $prices, $data );
		}
		public static function render_price_html( $prices, $data ){
			$price_html = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? wc_price( $prices['rp'] ) : wc_format_sale_price( $prices['rp'], $prices['sp'] );

			return apply_filters( 'proler_get_price_html', $price_html, $prices, $data );
		}



		/**
		 * Change add to cart button on archive pages
		 *
		 * @param string $button  add to cart button text.
		 * @param object $product product object.
		 */
		public static function archive_page_cart_btn( $button, $product ) {
			$data = Proler_Helper::get_product_settings( $product );

			if ( empty( $data ) || ! isset( $data['settings'] ) ) {
				return $button;
			}

			if ( ! Proler_Helper::if_apply_settings( $data ) ) {
				return $button;
			}

			if ( isset( $data['settings']['hide_price'] ) && '1' === $data['settings']['hide_price'] ) {
				return '';
			}

			return $button;
		}

		/**
		 * Check to see if this product is on sale
		 *
		 * @param boolean $on_sale on sale status.
		 * @param object  $product product object.
		 */
		public static function is_on_sale( $on_sale, $product ) {
			if( 'external' === $product->get_type() ) return $on_sale;
			
			$data = Proler_Helper::get_product_settings( $product );

			if ( empty( $data ) || ! isset( $data['settings'] ) ) {
				return $on_sale;
			}

			if ( ! Proler_Helper::if_apply_settings( $data ) ) {
				return $on_sale;
			}

			if ( isset( $data['settings']['hide_price'] ) && '1' === $data['settings']['hide_price'] ) {
				return false;
			}

			$prices = self::get_prices( $data );

			if ( ! is_array( $prices ) || $prices['rp'] === $prices['sp'] || empty( $prices['sp'] ) ) {
				return $on_sale;
			}

			return true;
		}
		


		



		/**
		 * Get regular and sale price of a product
		 *
		 * @param array $data settings data.
		 */
		public static function get_prices( $data ) {
			$enable = ! isset( $data['settings']['pr_enable'] ) || empty( $data['settings']['pr_enable'] ) ? false : true;

			if ( empty( $data ) || false === $enable ) {
				return false;
			}

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


		
		/**
		 * Discount text for single product page
		 */
		public static function discount_text_single(){
			self::discount_text_loop();
		}

		/**
		 * Display discount text on shop and archive pages after price
		 */
		public static function discount_text_loop() {
			global $product;

			$data = Proler_Helper::get_product_settings( $product );
			if ( ! Proler_Helper::if_apply_settings( $data ) ) {
				return;
			}

			$is_hidden = isset( $data['settings']['hide_price'] ) ? $data['settings']['hide_price'] : '';
			$is_hidden = ! empty( $is_hidden ) && '1' === $is_hidden ? true : false;
			if ( $is_hidden ) {
				return;
			}

			if ( ! isset( $data['settings']['discount_text'] ) || empty( $data['settings']['discount_text'] ) ) {
				return '';
			}

			$text_data = array(
				'amount' => 0,
				'symbol' => get_woocommerce_currency_symbol()
			);

			if( isset( $data['settings']['discount'] ) && ! empty( $data['settings']['discount'] ) ){
				$text_data['amount'] = (float) $data['settings']['discount'];
				$text_data['symbol'] = 'price' === $data['settings']['discount_type'] ? $text_data['symbol'] : '%';
			}else if ( 'variable' === $data['type'] || 'grouped' === $data['type'] ) {
				$amount_max = 0;
				foreach( $product->get_children() as $child ){
					$product__ = wc_get_product( $child );
					
					$rp = $product__->get_regular_price();
					$sp = $product__->get_sale_price();

					if( ! empty( $sp ) && $rp > $sp ){
						$diff = $rp - $sp;
						if( $diff > $amount_max ){
							$amount_max = $diff;
						}
					}
				}
				if( $amount_max > 0 ){
					$text_data['amount'] = __( 'upto', 'product-role-rules' ) . ' ' . $amount_max;
				}
			}else{
				$rp = isset( $data['regular_price'] ) && ! empty( $data['regular_price'] ) ? (float) $data['regular_price'] : '';
				$sp = isset( $data['sale_price'] ) && ! empty( $data['sale_price'] ) ? (float) $data['sale_price'] : '';

				if( $text_data['amount'] > 0 ){
					// show that amount.
				}else if( empty( $sp ) || $rp === $sp ){
					$text_data['amount'] = 0;
				}else{
					$text_data['amount'] = $rp - $sp;
				}
			}

			// add hook to midify it for additional discounts.
			$text_data = apply_filters( 'proler_discount_text_loop', $text_data, $data, $product );

			if( $text_data['amount'] === 0 ){
				return;
			}

			echo '<div class="proler-saving">' . __( 'Save', 'product-role-rules' ) . ' ' . esc_html( $text_data['amount'] ) . esc_attr( $text_data['symbol'] ) . '</div>';
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

Proler_Front_Loader::init();
