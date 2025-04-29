<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.0
 */

if ( ! class_exists( 'PRoleR' ) ) {

	/**
	 * Plugin class for frontend feature
	 */
	class PRoleR {



		/**
		 * Price formatting decimal point
		 *
		 * @var int
		 */
		public $dp;

		/**
		 * WooCommerce decimal separator
		 *
		 * @var string
		 */
		public $ds;

		/**
		 * WooCommerce thousand separator
		 *
		 * @var string
		 */
		public $ts;

		/**
		 * Frontend functionality class constructor
		 */
		public function __construct() {
			$this->dp = get_option( 'woocommerce_price_num_decimals', 2 ); // decimal point.
			$this->ds = get_option( 'woocommerce_price_decimal_sep', '.' ); // decimal separator.
			$this->ts = get_option( 'woocommerce_price_thousand_sep', ',' ); // thousand separator.
		}

		/**
		 * Frontend hooks initialization
		 */
		public function init() {
			add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 11, 2 );

			add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'discount_text_loop' ), 11 );
			add_action( 'woocommerce_single_product_summary', array( $this, 'discount_text_single' ), 10 );

			add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cart_item_subtotal' ), 10, 3 );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'cart_total' ), 10, 1 );

			add_filter( 'woocommerce_product_is_on_sale', array( $this, 'is_on_sale' ), 20, 2 );

			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'archive_page_cart_btn' ), 10, 2 );


			add_action( 'woocommerce_before_mini_cart', array( $this, 'before_minicart' ) );

			add_action( 'wp_ajax_proler_minicart', array( $this, 'proler_minicart' ) );
			add_action( 'wp_ajax_nopriv_proler_minicart', array( $this, 'proler_minicart' ) );
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
		public function cart_item_price( $price_html, $cart_item, $cart_item_key ) {
			$price = $this->get_cart_item_price( $cart_item );

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
		public function cart_item_subtotal( $price_html, $cart_item, $cart_item_key ) {
			// $qty   = isset( $cart_item['quantity'] ) && ! empty( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
			$price = $this->get_cart_item_price( $cart_item );

			if ( empty( $price ) || ! is_numeric( $price ) ) {
				return $price_html;
			}

			return wc_price( $price );
		}

		/**
		 * Modify cart items total price.
		 *
		 * @param mixed $cart WooCommerce cart item.
		 */
		public function cart_total( $cart ) {
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
				// $this->handle_cart_item( $cart_item, $cart_item_key );
				$data = $this->get_cart_item_settings( $cart_item );
				if( !$data ) continue;

				$placeholder = $this->price_placeholder( $data );
				if ( false !== $placeholder && !empty( $cart_item_key ) ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}
				
				$prices = $this->get_prices( $data );
				if ( ! is_array( $prices ) ) continue;
				
				$price = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
				if ( ! empty( $price ) ) {
					$cart_item['data']->set_price( $price );
				}
			}

			// if( $removed_items ){
			// 	wc_add_notice(
			// 		__( 'Some items were removed from your cart due to pricing restrictions.', 'product-role-rules' ), 'notice'
			// 	);
			// }
		}

		public function before_minicart(){
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$data = $this->get_cart_item_settings( $cart_item );
				if( !$data ) continue;

				$placeholder = $this->price_placeholder( $data );
				if ( false !== $placeholder && !empty( $cart_item_key ) ){
					WC()->cart->remove_cart_item( $cart_item_key );
					continue;
				}
				
				$prices = $this->get_prices( $data );
				if ( ! is_array( $prices ) ) continue;
				
				$price = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? $prices['rp'] : $prices['sp'];
				if ( ! empty( $price ) ) {
					$cart_item['data']->set_price( $price );
				}
			}
		}

		public function proler_minicart(){
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
		public function get_cart_item_price( $cart_item ) {
			// skip grouped product, altogether.
			// if( 'grouped' === $cart_item['data']->is_type( 'variable' ) ) {
			// 	return '';
			// }

			$data = $this->get_cart_item_settings( $cart_item );
			if( !$data ) return '';

			$placeholder = $this->price_placeholder( $data );
			if ( false !== $placeholder ) $placeholder;
			
			$prices = $this->get_prices( $data );
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
		public function get_cart_item_settings( $cart_item ){
			$id = $cart_item['product_id'];

			if( isset( $cart_item['variation_id'] ) && ! empty( $cart_item['variation_id'] ) ){
				$product = wc_get_product( $cart_item['variation_id'] );
				$data    = $this->get_product_settings( $product );
			}else{
				$product = wc_get_product( $id );
				$data    = $this->get_product_settings( $product );
			}

			return !$this->if_apply_settings( $data ) ? false : $data;
		}



		/**
		 * Get product price html
		 *
		 * @param string $price   product price html.
		 * @param object $product product object.
		 */
		public function get_price_html( $price, $product ) {
			if( 'external' === $product->get_type() ) return $price;
			
			$data = $this->get_product_settings( $product );

			if ( ! $this->if_apply_settings( $data ) ) {
				return $price;
			}

			$placeholder = $this->price_placeholder( $data );
			if ( false !== $placeholder ) {
				return $placeholder;
			}

			if ( 'variable' === $data['type'] || 'grouped' === $data['type'] ) {
				return $this->price_range( $price, $product, $data );
			}

			$prices = $this->get_prices( $data );
			if ( ! is_array( $prices ) ) {
				return $price;
			}
			
			$price_html = empty( $prices['sp'] ) || $prices['rp'] === $prices['sp'] ? wc_price( $prices['rp'] ) : wc_format_sale_price( $prices['rp'], $prices['sp'] );

			$new_html = apply_filters( 'proler_get_price_html', $price_html, $prices, $data, $product );
			return empty( $new_html ) ? $price_html : $new_html;
		}



		/**
		 * Change add to cart button on archive pages
		 *
		 * @param string $button  add to cart button text.
		 * @param object $product product object.
		 */
		public function archive_page_cart_btn( $button, $product ) {
			$data = $this->get_settings( $product );

			if ( empty( $data ) || ! isset( $data['settings'] ) ) {
				return $button;
			}

			if ( ! $this->if_apply_settings( $data ) ) {
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
		public function is_on_sale( $on_sale, $product ) {
			if( 'external' === $product->get_type() ) return $on_sale;
			
			$data = $this->get_settings( $product );

			if ( empty( $data ) || ! isset( $data['settings'] ) ) {
				return $on_sale;
			}

			if ( ! $this->if_apply_settings( $data ) ) {
				return $on_sale;
			}

			if ( isset( $data['settings']['hide_price'] ) && '1' === $data['settings']['hide_price'] ) {
				return false;
			}

			$prices = $this->get_prices( $data );

			if ( ! is_array( $prices ) || $prices['rp'] === $prices['sp'] || empty( $prices['sp'] ) ) {
				return $on_sale;
			}

			return true;
		}
		


		/**
		 * Get role based settings for a given product
		 *
		 * @param object $product product object.
		 */
		public function get_settings( $product ) {
			// global $post;
			// if( is_object( $post ) && $product->get_id() !== $post->ID ) {
			// 	$product = wc_get_product( $post->ID );
			// }

			return $this->get_product_settings( $product );
		}

		/**
		 * Get product settings
		 *
		 * @param object $product.
		 */
		public function get_product_settings( $product ) {
			if( ! is_object( $product ) ) {
				return array();
			}

			$id   = $product->get_id();
			$role = $this->user_roles()[0];

			// New approach using transients | caching.
			$settings = get_transient( 'proler_settings' );
			if ( false === $settings ) {
				$settings = array();
			}

			if ( ! isset( $settings['global'] ) ) {
				// set global settings.
				$global_data = get_option( 'proler_role_table' );

				if ( ! empty( $global_data ) ) {
					$settings['global'] = $this->extract_settings( $global_data['roles'] );
				}
			}

			if ( isset( $settings[ $id ][ $role ] ) ) {
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
				$settings[ $id ]['settings'] = $this->extract_settings( $data['roles'] );
				if( empty( $settings[ $id ]['settings'] ) ){
					$settings[ $id ]['settings'] = $settings['global'] ?? array();
				}
			}

			// Convert saved UTC datetime to wp.
			if( isset( $settings[$id]['settings']['schedule'] ) ){
				$wp_timezone = new DateTimeZone( wp_timezone_string() );
									
				$date_from = $settings[$id]['settings']['schedule']['start'] ?? '';
				$date_to   = $settings[$id]['settings']['schedule']['end'] ?? '';

				$from = new DateTime( $date_from, new DateTimeZone( 'UTC' ) );
				$to   = new DateTime( $date_to, new DateTimeZone( 'UTC' ) );
				
				$from->setTimezone( $wp_timezone );
				$to->setTimezone( $wp_timezone );

				$date_from = $from->format( 'Y-m-d H:i:s' );
				$date_to   = $to->format( 'Y-m-d H:i:s' );

				if( ! empty( $date_from ) ){
					$settings[$id]['settings']['schedule']['start'] = $date_from;
				}
				if( ! empty( $date_to ) ){
					$settings[$id]['settings']['schedule']['end'] = $date_to;
				}
			}

			$cached_settings = [];
			$cached_settings[$id][$role] = $settings[$id];

			// set chache for 10 seconds. why? this should be at least an hour.
			set_transient( 'proler_settings', $cached_settings, 3 );

			return $settings[ $id ];
		}

		/**
		 * Given settings data filter out necessary settings.
		 *
		 * @param array $data settings data array.
		 */
		public function extract_settings( $data ) {
			$roles           = $this->user_roles();
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
		public function if_apply_settings( $data ) {
			if ( false === $data || ( ! isset( $data['settings'] ) || false === $data['settings'] ) ) {
				return false;
			}
			
			$enable = isset( $data['settings']['pr_enable'] ) && ! empty( $data['settings']['pr_enable'] ) ? (bool) $data['settings']['pr_enable'] : true;
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
			if ( 'variation' !== $data['type'] && ! $this->if_in_cat( $data ) ) {
				return false;
			}

			return apply_filters( 'proler_if_apply_settings', $data );
		}



		/**
		 * Get regular and sale price of a product
		 *
		 * @param array $data settings data.
		 */
		public function get_prices( $data ) {
			$enable = ! isset( $data['settings']['pr_enable'] ) || empty( $data['settings']['pr_enable'] ) ? false : true;

			if ( empty( $data ) || false === $enable ) {
				return false;
			}

			$has_range = 'variable' === $data['type'] || 'grouped' === $data['type'];
			$prices    = array(
				'rp' => $has_range ? $data['max_price'] : $data['regular_price'],
				'sp' => $has_range ? $data['min_price'] : $data['sale_price']
			);

			return $this->apply_discount( $data, $prices );
		}

		/**
		 * Handle product discount
		 *
		 * @param array $data   Settings data.
		 * @param array $prices Regular and sale prices of the product.
		 */
		public function apply_discount( $data, $prices ) {
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
		public function price_range( $price, $product, $data ) {
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
		public function price_placeholder( $data ) {
			$is_hidden = isset( $data['settings']['hide_price'] ) ? $data['settings']['hide_price'] : '';
			$is_hidden = ! empty( $is_hidden ) && '1' === $is_hidden ? true : false;
			if ( ! $is_hidden ) return false;

			$this->remove_add_to_cart();

			return isset( $data['settings']['hide_txt'] ) ? $data['settings']['hide_txt'] : __( 'Price hidden', 'product-role-rules' );
		}

		/**
		 * Remove add to cart button from product page
		 */
		public function remove_add_to_cart(){
			remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
			remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
			remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
			remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
		}


		
		/**
		 * Discount text for single product page
		 */
		public function discount_text_single(){
			$this->discount_text_loop();
		}

		/**
		 * Display discount text on shop and archive pages after price
		 */
		public function discount_text_loop() {
			global $product;

			$data = $this->get_product_settings( $product );
			if ( ! $this->if_apply_settings( $data ) ) {
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
		 * Check if current product falls in settings category.
		 */
		public function if_in_cat( $data ){
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

			// if ( ! isset( $data['settings']['category'] ) || empty( $data['settings']['category'] ) ) {
			// 	return true;
			// }

			// $cat = (int) $data['settings']['category'];

			// $settings_cats   = get_term_children( $cat, 'product_cat' ); // category children.
			// $settings_cats[] = $cat;

			// $in = false;
			// foreach( $data['cats'] as $id ){
			// 	if( in_array( $id, $settings_cats, true ) ) {
			// 		$in = true;
			// 		break;
			// 	}
			// }
			
			// return $in;
		}



		/**
		 * Get all user roles
		 */
		public function user_roles() {
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

if ( ! isset( $GLOBALS['PRoleR'] ) ) {
    $GLOBALS['PRoleR'] = new PRoleR();
    $GLOBALS['PRoleR']->init();
}
