<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'Proler_Cart' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class Proler_Cart {

		/**
		 * Frontend hooks initialization
		 */
		public static function init() {
			add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'cart_item_price' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_item_subtotal' ), 10, 3 );
			add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'cart_total' ), 10, 1 );

			add_action( 'woocommerce_before_mini_cart', array( __CLASS__, 'before_minicart' ) );

			add_action( 'wp_ajax_proler_minicart', array( __CLASS__, 'proler_minicart' ) );
			add_action( 'wp_ajax_nopriv_proler_minicart', array( __CLASS__, 'proler_minicart' ) );
		}


		
		/**
		 * Modifies the cart item price HTML.
		 *
		 * @param string $price_html    HTML of the cart item price.
		 * @param mixed  $cart_item     Cart item data.
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return string Modified price HTML.
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		 */
		public static function cart_item_price( $price_html, $cart_item, $cart_item_key ) {
			$price = self::get_cart_item_price( $cart_item );

			if ( empty( $price ) ) return $price_html;

			return wc_price( $price );
		}

		/**
		 * Modifies the cart total price HTML.
		 *
		 * @param string $price_html    HTML of the cart item price.
		 * @param mixed  $cart_item     Cart item data.
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return string Modified price HTML.
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		 */
		public static function cart_item_subtotal( $price_html, $cart_item, $cart_item_key ) {
			// $qty   = isset( $cart_item['quantity'] ) && ! empty( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
			$price = self::get_cart_item_price( $cart_item );

			if ( empty( $price ) || ! is_numeric( $price ) ) return $price_html;

			return wc_price( $price );
		}

		/**
		 * Modify cart items total price.
		 *
		 * @param mixed $cart WooCommerce cart item.
		 */
		public static function cart_total( $cart ) {
			// This is necessary for WC 3.0+.
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

			// Avoiding hook repetition (when using price calculations for example | optional).
			if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

			// $removed_items = false;
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				// self::handle_cart_item( $cart_item, $cart_item_key );
				$data = self::get_cart_item_settings( $cart_item );
				if( !$data ) continue;

				$placeholder = self::price_placeholder( $data );
				if ( false !== $placeholder && !empty( $cart_item_key ) ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}
				
				$prices = self::get_prices( $data );
				if ( ! is_array( $prices ) ) continue;
				
				$price = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
				if ( ! empty( $price ) ) $cart_item['data']->set_price( $price );
			}

			// if( $removed_items ){
			// 	wc_add_notice(
			// 		__( 'Some items were removed from your cart due to pricing restrictions.', 'product-role-rules' ), 'notice'
			// 	);
			// }
		}

		public static function before_minicart(){
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$data = self::get_cart_item_settings( $cart_item );
				if( !$data ) continue;

				$placeholder = self::price_placeholder( $data );
				if ( false !== $placeholder && !empty( $cart_item_key ) ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}
				
				$prices = self::get_prices( $data );
				if ( ! is_array( $prices ) ) continue;
				
				$price = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
				if ( ! empty( $price ) ) $cart_item['data']->set_price( $price );
			}
		}

		public static function proler_minicart(){
			ob_start();

			// Force WooCommerce to recalculate cart.
			WC()->cart->calculate_totals();

			woocommerce_mini_cart();

			$mini_cart = ob_get_clean();

			$data = array(
				'fragments' => apply_filters(
					'woocommerce_add_to_cart_fragments',
					array(
						'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
					)
				),
				'cart_hash' => WC()->cart->get_cart_hash(),
			);

			wp_send_json( $data );
		}

		/**
		 * Get single cart item price
		 *
		 * @param object $cart_item WC Cart item object.
		 */
		public static function get_cart_item_price( $cart_item ) {
			// skip grouped product, altogether.
			// if( 'grouped' === $cart_item['data']->is_type( 'variable' ) ) {
			// 	return '';
			// }

			$data = self::get_cart_item_settings( $cart_item );
			if( !$data ) return '';

			$placeholder = self::price_placeholder( $data );
			if ( false !== $placeholder ) $placeholder;
			
			$prices = self::get_prices( $data );
			if ( ! is_array( $prices ) ) return '';
			
			return empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
		}

		/**
		 * Get cart item settings
		 *
		 * @param object $cart_item WC Cart item object.
		 */
		public static function get_cart_item_settings( $cart_item ){
			$id = $cart_item['product_id'];

			if( isset( $cart_item['variation_id'] ) && ! empty( $cart_item['variation_id'] ) ){
				$product = wc_get_product( $cart_item['variation_id'] );
				$data    = Proler_Helper::get_product_settings( $product );
			}else{
				$product = wc_get_product( $id );
				$data    = Proler_Helper::get_product_settings( $product );
			}

			return !self::if_apply_settings( $data ) ? false : $data;
		}









		

		/**
		 * Check if role based settings should apply
		 *
		 * @param array $data role based settings data.
		 */
		public static function if_apply_settings( $data ) {
			if ( false === $data || ( ! isset( $data['settings'] ) || false === $data['settings'] ) ) return false;
			
			$enable = isset( $data['settings']['pr_enable'] ) && ! empty( $data['settings']['pr_enable'] ) ? (bool) $data['settings']['pr_enable'] : true;
			if ( false === $enable ) return false;
			
			// check type.
			if ( isset( $data['settings']['product_type'] ) && ! empty( $data['settings']['product_type'] ) ) {
				if ( $data['type'] !== $data['settings']['product_type'] ) return false;
			}
			
			// check category and that could either be it's parent or in children.
			if ( 'variation' !== $data['type'] && ! Proler_Helper::if_in_cat( $data ) ) return false;

			return apply_filters( 'proler_if_apply_settings', $data );
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

Proler_Cart::init();
