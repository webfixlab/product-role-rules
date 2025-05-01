<?php
/**
 * Role based pricing plugin data structure.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      1.0
 */

global $proler__;

$proler__ = array(
	'name'       => __( 'Product Role Rules', 'product-role-rules' ),
	'notice'     => array(),
	'has_pro'    => false,
	'prostate'   => 'none',
	'notice_gap' => 15, // days.
	'screen'     => array(
		'_page_proler-settings', // main setting page.
		'_page_proler-newrole',
		'_page_proler-general-settings',
		'product',
	),
	'url'        => array(
		'free'    => 'https://wordpress.org/plugins/product-role-rules/',
		'review'  => 'https://wordpress.org/support/plugin/product-role-rules/reviews/?rate=5#new-post',
		'support' => 'https://webfixlab.com/contact/',
		'pro'     => 'https://webfixlab.com/plugins/role-based-pricing-woocommerce/',
		'wc'      => 'https://wordpress.org/plugins/woocommerce/',
	),
	'general_settings' => array(
		array(
			'key'           => 'proler_stock_less_than_min',
			'section_title' => __( 'Purchase limit settings', 'product-role-rules' ),
			'field_name'    => __( 'If stock is below the minimum quantity?', 'product-role-rules' ),
			'desc'          => __( 'Choose if you want to allow users to purchase available stock or not when product stock is less than minimum limit.', 'product-role-rules' ),
			'default'       => 'strict',
			'pro_txt'       => __( 'Purchase limit with stock', 'product-role-rules' ),
			'options'       => array(
				'strict' => __( 'Don\'t allow purchase', 'product-role-rules' ),
				'allow' => __( 'Allow purchase of available stock only', 'product-role-rules' ),
			),
		),
		array(
			'key'           => 'proler_min_max_notice_place',
			'field_name'    => __( 'Where to show quantity limit warnings?', 'product-role-rules' ),
			'desc'          => __( 'Choose where you want to show a message when users try to buy less than the minimum or more than maximum limit.', 'product-role-rules' ),
			'default'       => 'only_cart',
			'pro_txt'       => __( 'Purchase limit notice position', 'product-role-rules' ),
			'options'       => array(
				'only_cart'    => __( 'Show only on cart page', 'product-role-rules' ),
				'product_cart' => __( 'Show on product page and cart', 'product-role-rules' ),
			),
		),
		array(
			'key'           => 'proler_cart_info_msg',
			'section_title' => __( 'Others', 'product-role-rules' ),
			'field_name'    => __( 'Cart info message', 'product-role-rules' ),
			'desc'          => __( 'Cart info message example  - "You already have x in your cart"? Please note, by default it will not show if no discount tiers or the product is not in the cart.', 'product-role-rules' ),
			'default'       => 'level_on',
			'pro_txt'       => __( 'Cart info message', '' ),
			'options'       => array(
				'level_on'  => __( 'Show', 'product-role-rules' ),
				'level_1'   => __( 'Hide only when no discount tiers available', 'product-role-rules' ),
				'level_off' => __( 'Hide', 'product-role-rules' ),
			),
		)
	),
);

// hook to modify global $proler__ data variable.
do_action( 'proler_modify_core_data' );
