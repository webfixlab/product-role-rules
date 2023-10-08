<?php
/**
 * Role based pricing frontend functions.
 * 
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      1.0
 */

function proler_get_price( $price, $product, $only_price = false ){

    $cls = new ProlerPlugin( $product );
    $data = $cls->settings();

    // echo '<pre>'; print_r( $data ); echo '</pre>';

    if( false === $data ){
        return $price;
    }

    $enable = isset( $data['pr_enable'] ) && ! empty( $data['pr_enable'] ) ? (boolean) $data['pr_enable'] : true;

    if( false === $enable ){
        return $price;
    }

    if( 'variable' === $product->get_type() ){
        return $cls->variable_price_range( $price, $product, $data );
    }

    $p = $cls->hide_price( $data );
    if( false !== $p ){
        return $p;
    }

    $p = $cls->get_prices( $data );
    if( false === $p ){
        return $price;
    }

    if( $only_price ){
        return isset( $p['sp'] ) && ! empty( $p['sp'] ) ? $p['sp'] : $p['rp'];
    }

    return $cls->price_html( $p );
}

/**
 * Set custom cart item price
 */
function proler_cart_item_price( $cart_item ){

    $product = wc_get_product( $cart_item['product_id'] );

    if( 'variable' === $product->get_type() ){
        $variation_id = $cart_item['data']->get_id();

        if( empty( $variation_id ) ){
            return '';
        }

        return proler_get_price( $cart_item['data']->get_price(), $cart_item['data'], true );
    }

    return proler_get_price( $cart_item['data']->get_price(), $product, true );

}
