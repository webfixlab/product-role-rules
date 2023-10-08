<?php
/**
 * Role based pricing admin functions.
 * 
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      1.0
 */

/**
 * Calculate date difference and some other accessories
 * @param $key | option meta key
 * @param $notice_interval | Alarm after this day's difference
 * @param @skip_ | skip this value
 */
function proler_date_diff( $key, $notice_interval, $skip_ = '' ){

    $value = get_option( $key );

    if( empty( $value ) || $value == '' ){

        // if skip value is meta value - return false
        if( $skip_ != '' && $skip_ == $value ){
            return false;        
        }else{

            $c   = date_create( date( 'Y-m-d' ) );
            $d   = date_create( $value );
            $dif = date_diff( $c, $d );
            $b   = (int) $dif->format( '%d' );
            
            // if days difference meets minimum given interval days - return tru
            if( $b >= $notice_interval ) return true;

        }

    }else add_option( $key, date( 'Y-m-d' ) );

    return false;

}

// display what you want to show in the notice
function proler_client_feedback_notice(){

    global $proler__;

    // get current page
    $page  = sanitize_url( $_SERVER['REQUEST_URI'] );

    // dynamic extra parameter adding beore adding new url parameters
    $page .= strpos( $page, '?' ) !== false ? '&' : '?';

    ?>
    <div class="notice notice-info is-dismissible">
        <h3><?php echo esc_html( $proler__['plugin']['name'] ); ?></h3>
        <p>
            Excellent! You've been using <strong><a href="<?php echo esc_url( $proler__['plugin']['review_link'] ); ?>"><?php echo esc_html( $proler__['plugin']['name'] ); ?></a></strong> for a while. We'd appreciate if you kindly rate us on <strong><a href="<?php echo esc_url( $proler__['plugin']['review_link'] ); ?>">WordPress.org</a></strong>
        </p>
        <p>
            <a href="<?php echo esc_url( $proler__['plugin']['review_link'] ); ?>" class="button-primary">Rate it</a> <a href="<?php echo esc_url( $page ); ?>proler_rating=done" class="button">Already Did</a> <a href="<?php echo esc_url( $page ); ?>proler_rating=cancel" class="button">Cancel</a>
        </p>
    </div>
    <?php

}

// Only for free version - inform about pro ( Immediate after free active Cancelable - trigger every 15 days)
function proler_pro_info(){

    global $proler__;

    // get current page
    $page  = sanitize_url( $_SERVER['REQUEST_URI'] );

    // dynamic extra parameter adding beore adding new url parameters
    $page .= strpos( $page, '?' ) !== false ? '&' : '?';

    ?>
    <div class="notice notice-warning is-dismissible">
        <h3><?php echo esc_html( $proler__['plugin']['name'] ); ?> PRO</h3>
        <p><strong>Get maximum/minimum quantity support with PRO.</strong></p>
        <p><a href="<?php echo esc_url( $proler__['prolink'] ); ?>" class="button-primary">Get PRO</a> <a href="<?php echo esc_url( $page ); ?>proler_notify_pro=cancel" class="button">Cancel</a></p>
    </div>
    <?php

}

// if this is correctly within our plugin screen scope
function proler_in_screen_scope(){

    global $proler__;

    $screen = get_current_screen();

    // check with our plugin screens
    if( in_array( $screen->id, $proler__['plugin']['screen'] ) ) return true;
    else return false;

}

// Notice - if woocommerce is deactivated - auto deactivate this plugin
function proler_show_wc_new_inactive_notice(){

    global $proler__;
    
    ?>
    <div class="error">
        <p><a href="<?php echo esc_url( $proler__['plugin']['free_url'] ); ?>" target="_blank"><?php echo esc_html( $proler__['plugin']['name'] ); ?></a> plugin has been deactivated due to deactivation of <a href="<?php echo esc_url( $proler__['plugin']['woo_url'] ); ?>" target="_blank">WooCommerce</a> plugin</p>
    </div>
    <?php

}

// Notice - this plugin needs woocommerce plugin first
function proler_show_wc_inactive_notice(){

    global $proler__;
    
    ?>
    <div class="error">
        <p>Please install and activate <a href="<?php echo esc_url( $proler__['plugin']['woo_url'] ); ?>" target="_blank">WooCommerce</a> plugin first</p>
    </div>
    <?php

}

// Client feedback - rating
function proler_client_feedback(){

    global $proler__;

    if( isset( $_GET['proler_rating'] ) ){
        $task = sanitize_title( $_GET['proler_rating'] );
        
        if( $task == 'done' ) update_option( 'proler_rating', "done" );
        else if( $task == 'cancel' ) update_option( 'proler_rating', date( 'Y-m-d' ) );
        return;
        
    }else if( isset( $_GET['proler_notify_pro'] ) ){
        
        if( $_GET['proler_notify_pro'] == 'cancel' ) update_option( 'proler_notify_pro', date( 'Y-m-d' ) );
        return;
    }

    if( proler_date_diff( 'proler_rating', $proler__['plugin']['notice_interval'], 'done' ) ){
        // show notice to rate us after 15 days interval
        add_action( 'admin_notices', 'proler_client_feedback_notice' );
    }

    if( ! get_option( 'proler_notify_pro', false ) ){
        add_action( 'admin_notices', 'proler_pro_info' );
    }else{
        if( proler_date_diff( 'proler_notify_pro', $proler__['plugin']['notice_interval'], '' ) ){
            // show notice to inform about pro version after 15 days interval
            add_action( 'admin_notices', 'proler_pro_info' );
        }
    }

}

// wp_kses_post
// Save all admin notices for displaying later
function proler_handle_admin_notice(){

    global $proler__;

    // check scope, without it return
    if( ! proler_in_screen_scope() ) return;

    // Buffer only the notices
    ob_start();

    do_action( 'admin_notices' );

    $content = ob_get_contents();
    ob_get_clean();
    
    // Keep the notices in global $proler__;
    array_push( $proler__['notice'], $content );

    // Remove all admin notices as we don't need to display in it's place
    remove_all_actions( 'admin_notices' );

}

// Admin menu icon and notice title CSS styles
function proler_add_admin_menu_icon_style() {

    ?>
    <style>
        #toplevel_page_proler-settings img {
            width: 20px;
            opacity: 1 !important;
        }
        .notice h3{
            margin-top: .5em;
            margin-bottom: 0;
        }
    </style>
    <?php

}

// Check conditions before actiavation of the plugin
function proler_pre_activation(){

    $plugin = 'product-role-rules/product-role-rules.php';

    // check if WC is active
    $is_wc_active = is_plugin_active( 'woocommerce/woocommerce.php' );

    // check if our plugin is active
    $is_proler_active = is_plugin_active( $plugin );

    if( ! $is_wc_active ){
        
        if( $is_proler_active ){
            deactivate_plugins( $plugin );
            add_action( 'admin_notices', 'proler_show_wc_new_inactive_notice' );
        } else add_action( 'admin_notices', 'proler_show_wc_inactive_notice' );

        return false;
    }

    proler_client_feedback();

    return true;

}

/**
 * check if pro is installed
 * @return true|false
 */
function proler_check_admin_pro(){

    global $proler__;
    
    // don't have pro
    $proler__['has_pro'] = false;

    // Pro state
    $proler__['prostate'] = 'none';

    // change states
    do_action( 'proler_admin_change_pro_state' );

}

// Add Settings to WooCommerce > Settings > Products > WC Multiple Cart
function proler_admin_add_plugin_action_links( $links ){

    global $proler__;

	$action_links             = array();
	$action_links['settings'] = sprintf( '<a href="%s">Settings</a>', esc_url( admin_url( 'admin.php?page=proler-settings' ) ) );

    if( $proler__['prostate'] != 'activated' ){
        $action_links['premium'] = sprintf( '<a href="%s" style="color: #FF8C00;font-weight: bold;text-transform: uppercase;">Get PRO</a>', esc_url( $proler__['prolink'] ) );
    }
    
	return array_merge( $action_links, $links );

}

function proler_admin_add_plugin_desc_meta( $links, $file ){
    
    // if it's not Role Based Product plugin, return
    if ( plugin_basename( PROLER ) !== $file ) return $links;

    global $proler__;

	$row_meta = array();

	$row_meta['docs']    = sprintf( '<a href="%s">Docs</a>', esc_url( $proler__['plugin']['docs'] ) );
	$row_meta['apidocs'] = sprintf( '<a href="%s">Support</a>', esc_url( $proler__['plugin']['request_quote'] ) );
    
	return array_merge( $links, $row_meta );

}

// Register and enqueue a custom stylesheet in the WordPress admin.
function proler_admin_enqueue_scripts() {

    global $proler__;

    // check scope, without it return
    if( ! proler_in_screen_scope() ) return;
    
    // enqueue style
    wp_register_style( 'proler_admin_style', plugin_dir_url( PROLER ) . 'assets/admin/admin.css', false, $proler__['plugin']['version'] );
    
    wp_enqueue_style( 'proler_admin_style' );
    
    wp_register_script( 'proler_admin_script', plugin_dir_url( PROLER ) . 'assets/admin/admin.js', array( 'jquery', 'jquery-ui-slider', 'jquery-ui-sortable' ), $proler__['plugin']['version'] );

    wp_enqueue_script( 'proler_admin_script' );
    
    $var = array(
        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        'has_pro'       => $proler__['has_pro'],
        'nonce'         => wp_create_nonce('ajax-nonce'),
        'settings_page' => admin_url( 'admin.php?page=proler-settings' ),
        'right_arrow'   => plugin_dir_url( PROLER ) . 'assets/images/right.svg',
        'down_arrow'    => plugin_dir_url( PROLER ) . 'assets/images/down.svg',
    );

    // apply hook for editing localized variables in admin script
    $var = apply_filters( 'proler_admin_update_local_var', $var );
    
    wp_localize_script( 'proler_admin_script', 'proler', $var );

}
