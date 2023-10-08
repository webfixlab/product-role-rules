<?php
/**
 * Role based pricing frontend class.
 * 
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      3.0
 */

if ( ! class_exists( 'ProlerPlugin' ) ) {
    class ProlerPlugin {
        private $data; // role based settings data array.
        private $product; // product object.
        private $roles; // current user roles.
        private $dp; // decimal point.

        function __construct( $product ) {
            $this->product = $product;
            $this->dp = get_option( 'woocommerce_price_num_decimals', 2 );
            $this->user_roles();
        }
        public function user_roles(){
            $roles = array();
        
            $userid = get_current_user_id();
        
            // for non-logged in users, show things as usual
            if( $userid == 0 ){
                $roles = array( 'visitor' );
            }else{
                // get roles of currently logged in user
                $user = get_userdata( $userid );
                $roles = $user->roles;        
            }

            $this->roles = $roles;
        
            return $roles;
        }
        public function extract_settings( $data ){

            // get setttings data array from settings data.
            $rrd = array(
                'global' => false,
                'role' => false
            );
            foreach( $data as $role => $rd ){

                if( 'global' === $role ){
                    $rrd['global'] = $rd;
                    continue;
                }elseif( ! in_array( $role, $this->roles, true ) ){
                    continue;
                }
                
                $rrd['role'] = $rd;
            }

            if( false === $rrd['role'] ){
                return $rrd['global'];
            }
            
            if( ! isset( $rrd['role']['pr_enable'] ) || empty( $rrd['role']['pr_enable'] ) ){
                return $rrd['global'];
            }
            
            return $rrd['role'];

        }
        public function settings(){

            if( 'variation' === $this->product->get_type() ){
                $data = get_post_meta( $this->product->get_parent_id(), 'proler_data', true );
            }else{
                $data = get_post_meta( $this->product->get_id(), 'proler_data', true );
            }

            // if not data on product level settings or its set to global settings.
            if( empty( $data ) || ! isset( $data['proler_stype'] ) || 'default' === $data['proler_stype'] ){
                // extract global settings.
                $data = get_option( 'proler_role_table' );

                if ( ! empty( $data ) ) {
                    return $this->extract_settings( $data['roles'] );
                }
            }

            if( empty( $data ) ){
                return false;
            }

            // product level settings disabled.
            if( isset( $data['proler_stype'] ) && 'disable' === $data['proler_stype'] ){
                // disabled settings.
                return false;
            }

            // product level settings is set to it's own custom settings.
            if( ! empty( $data ) && 'proler-based' === $data['proler_stype'] ){
                return $this->extract_settings( $data['roles'] );
            }
            
            return $data;
        }
        public function hide_price( $data ){
            if( isset( $data['hide_price'] ) && ! empty( $data['hide_price'] ) ){
                
                remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );

                if( true === $data['hide_price'] || '1' === $data['hide_price'] ){
                    return isset( $data['hide_txt'] ) ? $data['hide_txt'] : '';
                }else{
                    return '';
                }
            }
            return false;
        }
        public function discount( $data, $prices ){

            $price = ! empty( $prices['sp'] ) ? $prices['sp'] : $prices['rp'];

            $price = (float) $price;

            $discount = (float) $data['discount'];

            if( 'price' === $data['discount_type'] ){
                $price = $price - $discount;
            }else{
                if( $discount > 100 || $discount < 0 ){
                    return $price;
                }

                $price = ( $price * ( 100 - $discount ) ) / 100;
            }
            
            if( $price < 0 ){
                $price = 0;
            }

            $price = number_format( $price, $this->dp );

            return $price;

        }
        public function get_prices( $data ){

            $enable = ! isset( $data['pr_enable'] ) || empty( $data['pr_enable'] ) ? false : true;
            $has_price = isset( $data['regular_price'] ) && ! empty( $data['regular_price'] ) ? true : false;
            $has_discount = isset( $data['discount'] ) && ! empty( $data['discount'] ) ? true : false;

            // when price filtering is not applicable.
            if( empty( $data ) || false === $enable ){
                return false;
            }elseif( ! $has_price && ! $has_discount ){
                return false;
            }

            $prices = array(
                'rp' => $this->product->get_regular_price(),
                'sp' => $this->product->get_sale_price()
            );

            // check product level settings.
            if( $has_price ){

                if( isset( $data['sale_price'] ) && ! empty( $data['sale_price'] ) ){
                    $prices['sp'] = number_format( (float) $data['sale_price'], $this->dp );
                }else{
                    $prices['sp'] = '';
                }

                $prices['rp'] = number_format( (float) $data['regular_price'], $this->dp );

            }

            if( $has_discount ){
                $prices['sp'] = $this->discount( $data, $prices );
            }
            
            return $prices;

        }
        public function variable_price_range( $price, $product, $data ){
            $prices = array(
                'min' => $product->get_variation_price( 'min', true ),
                'max' => $product->get_variation_price( 'max', true )
            );
            
            $has_discount = isset( $data['discount'] ) && ! empty( $data['discount'] ) ? true : false;

            if( ! $has_discount ){
                return $price;
            }

            $min = $this->discount( $data, array(
                'rp' => $prices['min'],
                'sp' => ''
            ) );

            $max = $this->discount( $data, array(
                'rp' => $prices['max'],
                'sp' => ''
            ) );

            $price_html = '';

            if( ! empty( $min ) ){
                $price_html = wc_price( $min );
            }

            if( ! empty( $max ) ){
                $price_html .= ' - ' . wc_price( $max );
            }

            return $price_html;
        }
        public function price_html( $prices ){

            if( $prices['rp'] === $prices['sp'] || empty( $prices['sp'] ) ){
                return wc_price( $prices['rp'] );
            }else{
                return wc_format_sale_price( $prices['rp'], $prices['sp'] ) . $this->product->get_price_suffix();
            }
        }
    }
}