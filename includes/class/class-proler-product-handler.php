<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'Proler_Product_Handler' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class Proler_Product_Handler {

		/**
		 * Frontend hooks initialization
		 */
		public static function init() {
			add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'get_price_html' ), 11, 2 );

			add_action( 'woocommerce_before_template_part', array( __CLASS__, 'before_price' ), 19, 4 );

			add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'discount_text_loop' ), 11 );
			// add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'discount_text_single' ), 11 );

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
			return Proler_Front_Settings::get_price_html( $price, $product );
		}

		/**
		 * Add additional discounts info before price template
		 *
		 * @param mixed $template_name Template name.
		 * @param mixed $template_path Template path.
		 * @param mixed $located       Template located.
		 * @param mixed $action_args   Arguments args parameter.
		 */
		public static function before_price( $template_name, $template_path, $located, $action_args ){
			// self::log('template ' . $template_name );
			if( false === strpos( $template_name, 'single-product/price.php' ) ){
				// self::log( 'found simple price template' );
				return;
			}

			self::discount_text_loop();
		}

		/**
		 * Display discount text on shop and archive pages after price
		 */
		public static function discount_text_loop() {
			global $product;

			if( 'external' === $product->get_type() ) return;

			$settings = Proler_Front_Settings::get_product_settings( $product );
			if ( empty( $settings ) ) return;

			$is_hidden = $settings['hide_price'] ?? '';
			if ( !empty( $is_hidden ) && '1' === $is_hidden ) return;

			$discount = $settings['discount'] ?? '';
			$type     = $settings['discount_type'] ?? '';

			// self::log( 'discount:front ' . $discount . '/ ' . $type );
			if( isset( $settings['mad'] ) ){ // maximum available discount.
				$discount = 0 !== $settings['mad']['dis'] ? $settings['mad']['dis'] : $discount;
				$type     = $settings['mad']['per'] ? 'percent' : 'fixed';
			}
			// self::log( 'discount:front after' . $discount . '/ ' . $type );
			// self::log( $settings );

			if( empty( $discount ) ) return;
			?>
			<div class="proler-saving">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="m21.5 9.757-5.278 4.354 1.649 7.389L12 17.278 6.129 21.5l1.649-7.389L2.5 9.757l6.333-.924L12 2.5l3.167 6.333z"/></svg>
				<?php
					echo sprintf(
						// translators: %1$s: maximum discount volume, %2$s: discount type, either percent or amount.
						__( 'Get up to %1$s%2$s discount', 'product-role-rules' ),
						esc_attr( $discount ),
						'percent' === $type ? '%' : get_woocommerce_currency_symbol()
					);
				?>
			</div>
			<?php
		}

		/**
		 * Discount text for single product page
		 */
		// public static function discount_text_single(){
		// 	self::discount_text_loop();
		// }

		/**
		 * Check to see if this product is on sale
		 *
		 * @param boolean $on_sale on sale status.
		 * @param object  $product product object.
		 */
		public static function is_on_sale( $on_sale, $product ) {
			if( 'external' === $product->get_type() ) return $on_sale;
			
			$settings = Proler_Front_Settings::get_product_settings( $product );
			if ( empty( $settings ) || ! isset( $settings ) ) {
				return $on_sale;
			}

			if ( isset( $settings['hide_price'] ) && '1' === $settings['hide_price'] ) {
				return false;
			}

			$discount = $settings['discount'] ?? '';
			return empty( $discount ) ? $on_sale : true;
		}

		/**
		 * Change add to cart button on archive pages
		 *
		 * @param string $button  add to cart button text.
		 * @param object $product product object.
		 */
		public static function archive_page_cart_btn( $button, $product ) {
			$settings = Proler_Front_Settings::get_product_settings( $product );
			if ( empty( $settings ) || ! isset( $settings ) ) {
				return $button;
			}

			$hide_price = $settings['hide_price'] ?? '';
			return !empty( $hide_price ) && '1' === $hide_price ? '' : $button;
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

Proler_Product_Handler::init();
