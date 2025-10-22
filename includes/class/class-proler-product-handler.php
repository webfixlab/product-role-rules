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

			// if ( function_exists( 'wc_get_theme_support' ) && current_theme_supports( 'block-templates' ) ) {
			// 	// Block theme
			// 	// add_filter( 'render_block_woocommerce/product-price', [ __CLASS__, 'inject_tiers_into_price' ], 10, 2 );
			// 	add_filter( 'render_block_woocommerce/product-price', array( __CLASS__, 'block_price_template' ), 19, 2 );
			// } else {
			// 	// Classic theme
			// 	// add_action( 'woocommerce_before_template_part', array( __CLASS__, 'before_price' ), 19, 4 );
			// 	add_action( 'woocommerce_before_template_part', [ __CLASS__, 'before_price' ], 20, 4 );
			// }
			
			add_filter( 'render_block_woocommerce/product-price', array( __CLASS__, 'block_price_template' ), 19, 2 );
			add_action( 'woocommerce_before_template_part', [ __CLASS__, 'before_price' ], 19, 4 );
			
			add_filter( 'render_block_woocommerce/product-button', array( __CLASS__, 'block_loop_add_to_cart' ), 10, 2 );
			if( !function_exists( 'wc_get_theme_support' ) || !current_theme_supports( 'block-templates' ) ){
				add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'discount_text_loop' ), 11 );
			}
			
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

			// self::log('[old hook:fired]');
			self::discount_text_loop();
		}

		public static function block_price_template( $content, $block ){
			// self::log('[new block hook:fired]');

			ob_start();
			self::discount_text_loop();
			$content .= ob_get_clean();

			return $content;
		}

		public static function block_loop_add_to_cart( $content, $block ) {
			global $product;

			if( 'external' === $product->get_type() ) return;

			$settings = Proler_Front_Settings::get_product_settings( $product );
			if ( empty( $settings ) ) return;

			$hide_price = $settings['hide_price'] ?? '';
			if ( !empty( $hide_price ) && ( $hide_price || '1' === $hide_price ) ){
				return '';
			}

			return $content;
		}

		/**
		 * Display discount text on shop and archive pages after price
		 */
		public static function discount_text_loop() {
			global $product;

			if( 'external' === $product->get_type() ) return;

			$settings = Proler_Front_Settings::get_product_settings( $product );
			if ( empty( $settings ) ) return;

			$hide_price = $settings['hide_price'] ?? '';
			if ( !empty( $hide_price ) && ( $hide_price || '1' === $hide_price ) ){
				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				return;
			}

			$is_disabled = $settings['discount_text'] ?? '';
			if( !empty( $is_disabled ) && ( $is_disabled || '1' === $is_disabled ) ) return;

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
			<div class="proler-save-wrap">
				<div class="proler-saving">
					<?php
						echo sprintf(
							// translators: %1$s: maximum discount volume, %2$s: discount type, either percent or amount.
							__( 'Get up to <span>%1$s%2$s</span> discount', 'product-role-rules' ),
							esc_attr( $discount ),
							'percent' === $type ? '%' : get_woocommerce_currency_symbol()
						);
					?>
				</div>
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
