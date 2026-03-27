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
			add_action( 'woocommerce_before_mini_cart', array( __CLASS__, 'before_minicart' ) );

			add_action( 'wp_ajax_proler_minicart', array( __CLASS__, 'proler_minicart' ) );
			add_action( 'wp_ajax_nopriv_proler_minicart', array( __CLASS__, 'proler_minicart' ) );

			add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'check_cart' ), 30 );
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

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$pd = Proler_Price_Handler::get_price_amount( $cart_item['data'] ); // price data.
				// error_log( '[cart total] prices [qty: ' . $cart_item['quantity'] . ' ]' );
				// error_log( print_r( $pd['prices'], true ) );

				if ( $pd['hide'] ) {
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				} elseif ( empty( $pd['prices'] ) ) {
					continue;
				}

				$item_price = ! empty( $pd['prices']['min'] ) ? (float) $pd['prices']['min'] : (float) $pd['prices']['max'];
				// error_log( '[cart total] item price ' . $item_price );
				$cart_item['data']->set_price( $item_price );
			}
		}

		/**
		 * Check cart items one final time to apply additional discounts
		 * @return void
		 */
		public static function check_cart(){
			self::update_cart_items();
		}

		/**
		 * Update cart items where change price or remove item.
		 * @return void
		 */
		public static function update_cart_items(){
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$pd = Proler_Price_Handler::get_price_amount( $cart_item['data'] ); // price data.

				if ( $pd['hide'] ) {
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				} elseif ( empty( $pd['prices'] ) ) {
					continue;
				}

				$item_price = ! empty( $pd['prices']['min'] ) ? (float) $pd['prices']['min'] : (float) $pd['prices']['max'];
				$cart_item['data']->set_price( $item_price );
			}

			// refresh cart again to cover new price changes.
			WC()->cart->calculate_totals();
		}

		/**
		 * Update product price and update minicart
		 */
		public static function before_minicart() {
			self::update_cart_items();
		}

		/**
		 * Update price and return minicart for AJAX minicart request
		 */
		public static function proler_minicart() {
			ob_start();
			WC()->cart->calculate_totals();
			woocommerce_mini_cart();
			$mini_cart = ob_get_clean();

			return wp_send_json(
				array(
					'fragments' => apply_filters(
						'woocommerce_add_to_cart_fragments',
						array(
							'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
						)
					),
					'cart_hash' => WC()->cart->get_cart_hash(),
				)
			);
		}
	}
}

Proler_Cart_Handler::init();
