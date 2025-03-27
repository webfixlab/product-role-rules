<?php
/**
 * Role based pricing helper class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.1.0
 */

if ( ! class_exists( 'ProlerHelp' ) ) {

	/**
	 * Role based pricing helper class
	 */
	class ProlerHelp {
        private function log( $data ) {
			if ( true === WP_DEBUG ) {
				if ( is_array( $data ) || is_object( $data ) ) {
					error_log( print_r( $data, true ) );
				} else {
					error_log( $data );
				}
			}
		}


        /**
         * Convert given time, if any, to WP Timezone format
         *
         * @param string $value     Given date time string.
         * @param bool   $for_input If it's for displaying in an input field.
         */
        public function convert_to_wp_timezone( $value = '', $for_input = true ){
            $value       = !isset( $value ) || empty( $value ) ? 'now' : $value;
            $wp_timezone = new DateTimeZone( wp_timezone_string() );

            if( 'now' !== $value ){
                $datetime = new DateTime( $value, new DateTimeZone( 'UTC' ) ); 
                $datetime->setTimezone( $wp_timezone );
            }else{
                $datetime = new DateTime( $value, $wp_timezone );
            }

            return $for_input ? $datetime->format( 'Y-m-d\TH:i' ) : $datetime->format( 'Y-m-d h:i a' );
        }
        
        /**
         * Checks wheather the given schedulue is over or not
         *
         * @param string $date_from Schedule start datetime.
         * @param string $date_to   Schedule ending datetime.
         */
        public function check_schedule( $date_from, $date_to ){
            $wp_timezone = new DateTimeZone( wp_timezone_string() );

            // convert saved UTC datetime to WP Timezone.
            $now    = new DateTime( 'now', $wp_timezone );
            $ts_now = $now->getTimestamp();

            $in_schedule = true;
            if( isset( $date_from ) && !empty( $date_from ) ){
                $datetime_from = new DateTime( $date_from, new DateTimeZone( 'UTC' ) ); 
                $datetime_from->setTimezone( $wp_timezone );
                
                $ts_from     = $datetime_from->getTimestamp();
                $in_schedule = $ts_now < $ts_from ? false : $in_schedule;
            }
            if( isset( $date_to ) && !empty( $date_to ) ){
                $datetime_to = new DateTime( $date_to, new DateTimeZone( 'UTC' ) ); 
                $datetime_to->setTimezone( $wp_timezone );

                $ts_to       = $datetime_to->getTimestamp();
                $in_schedule = $ts_now > $ts_to ? false : $in_schedule;
            }

            return $in_schedule;
        }


        /**
		 * Prepare notice
		 *
		 * @param array  $data      Settings data.
		 * @param object $cart_item Cart item object.
		 */
		public function prepare_notice( $data, $cart_item ){
			$title    = $cart_item['data']->get_name();
			// $url      = $data['url'];
			$quantity = $cart_item['quantity'];
			$url      = $cart_item['data']->get_permalink();
			
			$settings = $data['settings'];
			if ( empty( $settings['min_qty'] ) && empty( $settings['max_qty'] ) ) {
				return '';
			}

			// trim title if it exceeds lengh 100.
			if ( strlen( $title ) > 100 ) {
				$title = substr( $title, 0, 100 ) . '...';
			}

			$product_info = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html( $title )
			);

			$message = '';
			if ( ! empty( $settings['min_qty'] ) && $quantity < $settings['min_qty'] ) {
				$message = sprintf(
					// translators: %1$s: product name with url, %2$s: minimum buying quantity.
					__( 'Quantity of %1$s can not be less than %2$s', 'product-role-rules-premium' ),
					wp_kses_post( $product_info ),
					esc_attr( $settings['min_qty'] )
				);
			} elseif ( ! empty( $settings['max_qty'] ) && $quantity > $settings['max_qty'] ) {
				$message = sprintf(
					// translators: %1$s: product name with url, %2$s: maximum buyable quantity.
					__( 'Quantity of %1$s can not be more than %2$s', 'product-role-rules-premium' ),
					wp_kses_post( $product_info ),
					esc_attr( $settings['max_qty'] )
				);
			}

			return $message;
		}
    }
}
