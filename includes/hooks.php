<?php
/**
 * Role based pricing hooks.
 * 
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      1.0
 */

/**
 * WC price change hook.
 */
function proler_woocommerce_get_price_html( $price, $product ){

    $price = proler_get_price( $price, $product, false );
    return $price;
    
}
add_filter( 'woocommerce_get_price_html', 'proler_woocommerce_get_price_html', 11, 2 );

/**
 * Change cart item price
 */
function proler_cart_item_price_html( $price_html, $cart_item, $cart_item_key ) {

    $price = proler_cart_item_price( $cart_item );
    
    if( empty( $price ) ){
        return $price_html;
    }else{
        return wc_price( $price );
    }

}
add_filter( 'woocommerce_cart_item_price', 'proler_cart_item_price_html', 10, 3 );

/**
 * Change cart item subtotal price
 */
function proler_cart_item_price_sub_total( $price_html, $cart_item, $cart_item_key ) {

    $price = proler_cart_item_price( $cart_item );

    $qty = 1;
    if( isset( $cart_item['quantity'] ) && ! empty( $cart_item['quantity'] ) ){
        $qty = $cart_item['quantity'];
    }

    if( empty( $price ) || ! is_numeric( $price ) ){
        return $price_html;
    }else{
        $price = $price * $qty;
        return wc_price( $price );
    }

}
add_filter( 'woocommerce_cart_item_subtotal', 'proler_cart_item_price_sub_total', 10, 3 );


/**
 * Set custom role based cart items prices.
 */
function proler_set_cart_price( $cart ) {

    // This is necessary for WC 3.0+
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ){
        return;
    }

    // Avoiding hook repetition (when using price calculations for example | optional)
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ){
        return;
    }

    // Loop through cart items
    foreach ( $cart->get_cart() as $cart_item ) {

        $price = proler_cart_item_price( $cart_item );

        if( ! empty( $price ) ){
            $cart_item['data']->set_price( $price );
        }

    }

}
add_action( 'woocommerce_before_calculate_totals', 'proler_set_cart_price', 10, 1 );
