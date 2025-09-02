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
			$price = self::get_cart_item_price( $cart_item );
			if ( empty( $price ) ) {
				return $price_html;
			}

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
		public static function cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
			$price = self::get_cart_item_price( $cart_item );
			if ( empty( $price ) || ! is_numeric( $price ) ) {
				return $subtotal;
			}
			return wc_price( $price * $cart_item['quantity'] );
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

			// Avoiding hook repetition (when using price calculations for example | optional).
			if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
				return;
			}

			// $removed_items = false;
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				// self::handle_cart_item( $cart_item, $cart_item_key );
				$data = self::get_cart_item_settings( $cart_item );
				if( !$data ) continue;

				$placeholder = Proler_Front_Helper::price_placeholder( $data );
				if ( false !== $placeholder && !empty( $cart_item_key ) ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}
				
				$prices = Proler_Front_Helper::get_prices( $data );
				if ( ! is_array( $prices ) ) continue;
				
				$price = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
				if ( ! empty( $price ) ) {
					$cart_item['data']->set_price( $price );
				}
			}
		}

		public static function before_minicart(){
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$data = self::get_cart_item_settings( $cart_item );
				if( !$data ) continue;

				$placeholder = Proler_Front_Helper::price_placeholder( $data );
				if ( false !== $placeholder && !empty( $cart_item_key ) ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}
				
				$prices = Proler_Front_Helper::get_prices( $data );
				if ( ! is_array( $prices ) ) continue;
				
				$price = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
				if ( ! empty( $price ) ) {
					$cart_item['data']->set_price( $price );
				}
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

		/**
		 * Get single cart item price
		 *
		 * @param object $cart_item WC Cart item object.
		 */
		public static function get_cart_item_price( $cart_item ) {
			$data = self::get_cart_item_settings( $cart_item );
			if( !$data ) return '';

			$placeholder = Proler_Front_Helper::price_placeholder( $data );
			if ( false !== $placeholder ) $placeholder;
			
			$prices = Proler_Front_Helper::get_prices( $data );
			if ( ! is_array( $prices ) ) {
				return '';
			}
			
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
				$data    = Proler_Front_Helper::get_product_settings( $product );
			}else{
				$product = wc_get_product( $id );
				$data    = Proler_Front_Helper::get_product_settings( $product );
			}

			return $data;
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

Proler_Cart_Handler::init();
