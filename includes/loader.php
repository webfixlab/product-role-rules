<?php
global $proler__;

// Include admin settings functions
include( PROLER_PATH . 'includes/core-data.php' );
include( PROLER_PATH . 'includes/admin-functions.php' );

add_action( 'admin_head', 'proler_admin_head' );
add_action( 'init', 'proler_activation_process_handler' );

register_activation_hook( PROLER, 'proler_activation' );
register_deactivation_hook( PROLER, 'proler_deactivation' );
register_uninstall_hook( PROLER, 'proler_deleted_plugin' );

// for admin haed - handle notice and add menu styles
function proler_admin_head(){
    proler_handle_admin_notice();
    proler_add_admin_menu_icon_style();
}

// Start the plugin
function proler_activation_process_handler(){

    // check prerequisits
    if( ! proler_pre_activation() ) return;    

    proler_check_admin_pro();

    // add extra links right under plug
    add_filter( 'plugin_action_links_' . plugin_basename( PROLER ), 'proler_admin_add_plugin_action_links' );
    add_filter( 'plugin_row_meta', 'proler_admin_add_plugin_desc_meta', 10, 2 );

    // needs to be off the hook in the next version
    include( PROLER_PATH . 'includes/class/class-prolerplugin.php' );
    include( PROLER_PATH . 'includes/class/class-prolersettings.php' );

    include( PROLER_PATH . 'includes/hooks.php' );
    include( PROLER_PATH . 'includes/functions.php' );

    // Enqueue admin script and style
    add_action( 'admin_enqueue_scripts', 'proler_admin_enqueue_scripts' );
}

function proler_activation(){
    proler_activation_process_handler();
    flush_rewrite_rules();

    // handle fields
    do_action( 'proler_init_core_fields' );
}

function proler_deactivation() {
    flush_rewrite_rules();
}

function proler_deleted_plugin() {
    // delete all plugin options here
    global $proler__;
    
    // delete those options
    foreach( $proler__['core_options'] as $key ) delete_option( $key );
}
