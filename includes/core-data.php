<?php

// Plugin core data
global $proler__;

$proler__                  = array(
    'activate_link'        => 'admin.php?page=mpc-settings-pricing',
    'prolink'              => 'https://webfixlab.com/plugins/role-based-pricing-woocommerce/',
    'notice'               => array(),
    'has_pro'              => false,
    'prostate'             => 'none',
    'roles'                => array(),
    'visitor_role_label'   => 'Unregistered user',
    'proler_admin_select'  => array(
        'default'          => 'Global settings',
        'proler-based'     => 'Product based',
        'none'             => 'Disable role pricing'
    ),
    'plugin_name'          => 'Role Based Pricing'
);

$proler__['plugin']        = array(
    'version'              => '2.0.5',
    'screen'               => array(
        'toplevel_page_proler-settings', // main setting page
        'role-pricing_page_proler-newrole',
        'product'
    ),
    'notice_interval'      => 15,
    'free_url'             => 'https://webfixlab.com/plugins/role-based-pricing-woocommerce/',
    'docs'                 => 'https://webfixlab.com/plugins/role-based-pricing-woocommerce/',
    'request_quote'        => 'https://webfixlab.com/contact/',
    'review_link'          => 'https://wordpress.org/support/plugin/product-role-rules/reviews/?rate=5#new-post',
    'name'                 => 'Product Role Rules'
);

// core options
$proler__['core_options']  = array(
    'proler_rating',
    'proler_notify_pro',
    'proler_global_hide_price',
    'proler_global_hide_txt',
    'proler_role_table'
);

// hook to modify global $proler__ data variable
do_action( 'proler_modify_core_data' );