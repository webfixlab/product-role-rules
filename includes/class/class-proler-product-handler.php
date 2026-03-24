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

			add_filter( 'render_block_woocommerce/product-price', array( __CLASS__, 'block_price_template' ), 19, 2 );
			add_action( 'woocommerce_before_template_part', array( __CLASS__, 'before_price' ), 19, 4 );

			add_filter( 'render_block_woocommerce/product-button', array( __CLASS__, 'block_loop_add_to_cart' ), 10, 2 );
			if ( ! function_exists( 'wc_get_theme_support' ) || ! current_theme_supports( 'block-templates' ) ) {
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
			$pd = Proler_Price_Handler::get_price_html( $product ); // price data.
			if ( isset( $pd['hide'] ) && $pd['hide'] ) {
				return $pd['price'];
			}
			return empty( $pd['price'] ) ? $price : $pd['price'];
		}

		/**
		 * Add additional discounts info before price template
		 *
		 * @param mixed $template_name Template name.
		 * @param mixed $template_path Template path.
		 * @param mixed $located       Template located.
		 * @param mixed $action_args   Arguments args parameter.
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		 */
		public static function before_price( $template_name, $template_path, $located, $action_args ) {
			if ( false === strpos( $template_name, 'single-product/price.php' ) ) {
				return;
			}

			self::discount_text_loop();
		}

		/**
		 * Modify block price template
		 *
		 * @param string $content Block content.
		 * @param array  $block   Block data.
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		 */
		public static function block_price_template( $content, $block ) {
			ob_start();
			self::discount_text_loop();
			$content .= ob_get_clean();

			return $content;
		}

		/**
		 * Add to cart handler for loop pages
		 *
		 * @param string $content Block content.
		 * @param array  $block   Block data.
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		 */
		public static function block_loop_add_to_cart( $content, $block ) {
			global $product;

			if ( 'external' === $product->get_type() ) {
				return;
			}

			$settings = Proler_Product_Settings::get_settings( $product );
			if ( empty( $settings ) ) {
				return;
			}

			$hide_price = $settings['hide_price'] ?? '';
			if ( $hide_price || '1' === $hide_price ) {
				return '';
			}

			return $content;
		}

		/**
		 * Display discount text on shop and archive pages after price
		 */
		public static function discount_text_loop() {
			global $product;

			if ( 'external' === $product->get_type() ) {
				return;
			}

			$rs = Proler_Product_Settings::get_settings( $product ); // role settings.
			if ( empty( $rs ) ) {
				return;
			}

			$hide_price = $rs['hide_price'] ?? '';
			if ( $hide_price || '1' === $hide_price ) {
				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				return;
			}

			$is_disabled = $rs['discount_text'] ?? '';
			if ( $is_disabled || '1' === $is_disabled ) {
				return;
			}

			$discount = $rs['discount'] ?? '';
			$type     = $rs['discount_type'] ?? '';

			if ( isset( $rs['mad'] ) ) { // maximum available discount.
				$discount = 0 !== $rs['mad']['dis'] ? $rs['mad']['dis'] : $discount;
				$type     = false !== strpos( $rs['mad']['type'], 'percent' ) ? 'percent' : 'fixed';
			}

			if ( empty( $discount ) ) {
				return;
			}
			?>
			<div class="proler-save-wrap">
				<div class="proler-saving">
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: %1$s: maximum discount volume, %2$s: discount type, either percent or amount.
							__( 'Get up to <span>%1$s%2$s</span> discount', 'product-role-rules' ),
							esc_attr( $discount ),
							'percent' === $type ? '%' : esc_attr( get_woocommerce_currency_symbol() )
						)
					);
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Check to see if this product is on sale
		 *
		 * @param boolean $on_sale on sale status.
		 * @param object  $product product object.
		 */
		public static function is_on_sale( $on_sale, $product ) {
			if ( 'external' === $product->get_type() ) {
				return $on_sale;
			}

			$settings = Proler_Product_Settings::get_settings( $product );
			if ( empty( $settings ) || ! isset( $settings ) ) {
				return $on_sale;
			}

			$hide_price = $settings['hide_price'] ?? '';
			if ( $hide_price || '1' === $hide_price ) {
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
			$settings = Proler_Product_Settings::get_settings( $product );
			if ( empty( $settings ) || ! isset( $settings ) ) {
				return $button;
			}

			$hide_price = $settings['hide_price'] ?? '';
			return $hide_price || '1' === $hide_price ? '' : $button;
		}
	}
}

Proler_Product_Handler::init();
