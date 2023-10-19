<?php
/*
Plugin Name: Role Based Pricing for WooCommerce
Plugin URI: https://webfixlab.com/plugins/role-based-pricing-woocommerce/
Description: EASY to use and super FAST WooCommerce product role based pricing solution to add different prices for different roles.
Author: WebFix Lab
Author URI: https://webfixlab.com/
Version: 3.0
Requires at least: 4.9
Tested up to: 6.3.2
Requires PHP: 7.0
Tags: role based pricing, dynamic pricing, wholesale pricing, prices by user role, hide price
WC requires at least: 3.6
WC tested up to: 8.2.1
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: product-role-rules
*/

defined( 'ABSPATH' ) || exit;

// plugin path
define( 'PROLER', __FILE__ );
define( 'PROLER_PATH', plugin_dir_path( PROLER ) );

include( PROLER_PATH . 'includes/loader.php');