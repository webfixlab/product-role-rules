<?php
/**
 * Role based pricing frontend class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Price_Handler' ) ) {

	class Proler_Price_Handler {

        public static function get_price_html( $product ) {
            $pd = self::get_price_amount( $product );

            return array(
                'hide'  => $pd['hide'],
                'price' => $pd['hide'] ? $pd['prices'] : self::price_html( $pd['prices'] )
            );
		}

        /**
         * Complete product price either just amount or html
         * @param object $product Product object.
         * @return array {}
         */
        public static function get_price_amount( $product ) {
            // will need to cache it later !!!



            if( 'external' === $product->get_type() ) return ['hide' => false, 'prices' => ''];
			
			$rs = Proler_Product_Settings::get_settings( $product ); // role settings.
			if( !$rs || empty( $rs ) ) {
				return ['hide' => false, 'prices' => ''];
			}

			$is_hidden = $rs['hide_price'] ?? '';
			if( !empty( $is_hidden ) &&  ( $is_hidden || '1' === $is_hidden ) ){
				$txt = $rs['hide_txt'] ?? __( 'Price hidden', 'product-role-rules' );
                return ['hide' => true, 'prices' => $txt];
			}

			// get price.
			$prices = self::get_product_prices( $product );
			
			// apply discount.
			$prices = self::add_discount( $prices, $rs );

			return array(
                'hide'   => false,
                'prices' => $prices,
            );
        }
        public static function get_product_prices( $product ){
			// `price` cache key.
			$has_range = false;
			if ( $product->is_type( 'variable' ) ) {
				$has_range = true;

				$min = $product->get_variation_price( 'min', true );
				$max = $product->get_variation_price( 'max', true );
			} elseif ( $product->is_type( 'variation' ) ) {
				$max = $product->get_regular_price();
				$min = $product->get_sale_price();
			} elseif ( $product->is_type( 'grouped' ) ) {
				$children         = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );
				
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

				$child_prices = array();
				foreach ( $children as $child ) {
					if ( '' !== $child->get_price() ) {
						$child_prices[] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
					}
				}

				$has_range = true;

				$min = min( $child_prices );
				$max = max( $child_prices );
			} else {
				$max = $product->get_regular_price();
				$min = $product->get_sale_price();
			}

			return array(
				'min'       => empty( $min ) ? '' : (float) $min,
				'max'       => empty( $max ) ? '' : (float) $max,
				'has_range' => $has_range
			);
		}
		private static function add_discount( $prices, $rs ){
			$discount = $rs['discount'] ?? '';
			$type     = $rs['discount_type'] ?? '';
			if( empty( $discount ) || empty( $type ) ){
				return $prices;
			}
			
			$discount   = empty( $discount ) ? '' : (float) $discount;
			$if_percent = false === strpos( $type, 'percent' ) ? false : true;

			if( !empty( $prices['min'] ) ){
				$prices['min'] = $if_percent ? ( $prices['min'] - ( $prices['min'] * $discount ) / 100 ) : $prices['min'] - $discount;
			}
			if( !empty( $prices['max'] ) ){
				$prices['max'] = $if_percent ? ( $prices['max'] - ( $prices['max'] * $discount ) / 100 ) : $prices['max'] - $discount;
			}

			return $prices;
		}
		private static function price_html( $prices ){
            if( empty( $prices ) ){
                return '';
            }
			if( $prices['has_range'] ){
				return empty( $prices['min'] ) || $prices['min'] === $prices['max'] ? wc_price( $prices['max'] ) : wc_price( $prices['min'] ) . ' - ' . wc_price( $prices['max'] );
			}

			return !empty( $prices['min'] ) && $prices['min'] === $prices['max'] ? wc_format_sale_price( $prices['max'], $prices['min'] ) : wc_price( $prices['max'] );
		}
	}
}
