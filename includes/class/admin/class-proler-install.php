<?php
/**
 * Installation related functions
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * MPC Installation Class.
 */
class Proler_Install {
    
    /**
     * Static initialization function
     */
    public static function init(){
        register_activation_hook( PROLER, array( __CLASS__, 'activate' ) );
        register_deactivation_hook( PROLER, array( __CLASS__, 'deactivate' ) );
        
        add_filter( 'plugin_action_links_' . plugin_basename( PROLER ), array( __CLASS__, 'plugin_action_links' ) );
        add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
    }

    /**
     * Plugin activation function. Fires once when activating.
     */
    public static function activate(){
        // runs only once, when activating the plugin.
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation function
     */
    public static function deactivate(){
        flush_rewrite_rules();
    }

    /**
     * Display action links on the plugin screen
     *
     * @param array $links Plugin action links.
     *
     * @return array
     */
    public static function plugin_action_links( $links ){
        global $proler__;

        $action_links             = array();
        $action_links['settings'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=proler-settings' ) ),
            __( 'Settings', 'product-role-rules' )
        );

        if ( ! in_array( 'activated', explode( ' ', $proler__['prostate'] ), true ) ) {
            $action_links['premium'] = sprintf(
                '<a href="%s" style="font-weight: bold;background: linear-gradient(94deg, #0090F7, #BA62FC, #F2416B, #F55600);background-clip: text;color: transparent;">%s</a>',
                esc_url( $proler__['url']['pro'] ),
                __( 'Get PRO Plugin', 'product-role-rules' )
            );
        }

        return array_merge( $action_links, $links );
    }

    /**
     * Display row meta on plugin screen.
     *
     * @param mixed $links Plugin row meta.
     * @param mixed $file  Plugin base file.
     *
     * @return array
     */
    public static function plugin_row_meta( $links, $file ){
        global $proler__;

        // if it's not mpc plugin, return.
        if ( plugin_basename( PROLER ) !== $file ) {
            return $links;
        }

        $row_meta            = array();
        $row_meta['apidocs'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $proler__['url']['support'] ),
            __( 'Support', 'product-role-rules' )
        );

        return array_merge( $links, $row_meta );
    }
}

Proler_Install::init();
