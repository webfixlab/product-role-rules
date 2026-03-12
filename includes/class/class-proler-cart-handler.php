<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'Proler_Cart_Handler' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class Proler_Cart_Handler {

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
			$pd = Proler_Price_Handler::get_price_html( $cart_item['data'] ); // price data.
			if( isset( $pd['hide'] ) && $pd['hide'] ){
				self::remove_add_to_cart(); // price is hidden.
				return $pd['price'];
			}
			return empty( $pd['price'] ) ? $price_html : $pd['price'];
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
		public static function cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
			$pd = Proler_Price_Handler::get_price_amount( $cart_item['data'] ); // price data.
			if( $pd['hide'] ){
				return $pd['prices'];
			}

			if( empty( $pd['prices'] ) ){
				return $subtotal;
			}

			$item_price = !empty( $pd['prices']['min'] ) ? (float) $pd['prices']['min'] : (float) $pd['prices']['max'];
			return wc_price( $item_price * $cart_item['quantity'] );
		}

		/**
		 * Modify cart items total price.
		 *
		 * @param mixed $cart WooCommerce cart item.
		 */
		public static function cart_total( $cart ) {
			// This is necessary for WC 3.0+.
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			// hook repetition check.
			if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
				return;
			}

			// $removed_items = false;
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$pd = Proler_Price_Handler::get_price_amount( $cart_item['data'] ); // price data.
				if( $pd['hide'] ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}elseif( empty( $pd['prices'] ) ){
					continue;
				}

				$item_price = !empty( $pd['prices']['min'] ) ? (float) $pd['prices']['min'] : (float) $pd['prices']['max'];
				$cart_item['data']->set_price( $item_price );
			}
		}

		public static function before_minicart(){
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$pd = Proler_Price_Handler::get_price_amount( $cart_item['data'] ); // price data.
				if( $pd['hide'] ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}elseif( empty( $pd['prices'] ) ){
					continue;
				}

				$item_price = !empty( $pd['prices']['min'] ) ? (float) $pd['prices']['min'] : (float) $pd['prices']['max'];
				$cart_item['data']->set_price( $item_price );
			}
		}

		public static function proler_minicart(){
			ob_start();
			WC()->cart->calculate_totals();
			woocommerce_mini_cart();
			$mini_cart = ob_get_clean();

			return wp_send_json( array(
				'fragments' => apply_filters(
					'woocommerce_add_to_cart_fragments',
					array(
						'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
					)
				),
				'cart_hash' => WC()->cart->get_cart_hash(),
			) );
		}
	}
}

Proler_Cart_Handler::init();
