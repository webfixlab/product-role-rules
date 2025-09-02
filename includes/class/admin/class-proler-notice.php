<?php
/**
 * Plugin notice handler class
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      4.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Notice Handler Class.
 */
class Proler_Notice {

    /**
     * Add notice handler hook
     */
    public static function init(){
        add_action( 'init', array( __CLASS__, 'admin_notice' ) );
    }

    /**
     * Core notice handler
     */
    public static function admin_notice() {
        if( self::in_scope() ){
            remove_all_actions( 'admin_notices' );
        }

        self::handle_notice_responses();

        $notices = array(
            array(
                'key'      => 'proler_notify_pro',
                'callback' => 'pro_notice'
            ),
            array(
                'key'      => 'proler_rating',
                'callback' => 'feedback_notice'
            ),
        );
        foreach( $notices as $item ){
            if( self::if_show_notice( $item['key'] ) ) {
                add_action( 'admin_notices', array( __CLASS__, $item['callback'] ) );
            }
        }
    }

    /**
     * Check if current page is in our plugin's scopes
     *
     * @return bool
     */
    private static function in_scope(){
        global $proler__;

        $screen     = get_current_screen();
        $current_id = urldecode( $screen->id ); // current screen id.

        if( in_array( $current_id, $proler__['screen'], true ) ) return true;

        $partial_match = true;
        foreach ( $proler__['screen'] as $screen_id ) {
            // if screen id contains '_page_' and it matches partially with current screen id.
            if ( false !== strpos( $screen_id, '_page_' ) && false === strpos( $current_id, $screen_id ) ) {
                $partial_match = false;
            }
        }

        return $partial_match;
    }

    /**
     * Process notice responses based on user actions
     */
    public static function handle_notice_responses() {
        if ( isset( $_GET['prnonce'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['prnonce'] ) ), 'proler_rating_nonce' ) ) {
            return;
        }

        if ( isset( $_GET['proler_rating'] ) ) {
            $task = sanitize_title( wp_unslash( $_GET['proler_rating'] ) );

            if ( 'done' === $task ) {
                update_option( 'proler_rating', 'done' );
            } elseif ( 'cancel' === $task ) {
                update_option( 'proler_rating', gmdate( 'Y-m-d' ) );
            }
        } elseif ( isset( $_GET['proler_notify_pro'] ) ) {
            $task = sanitize_title( wp_unslash( $_GET['proler_notify_pro'] ) );

            if ( 'cancel' === $task ) {
                update_option( 'proler_notify_pro', gmdate( 'Y-m-d' ) );
            }
        }
    }

    /**
     * If given key name notice should be displayed after calculating interval time
     *
     * @param string $key option meta key to determine the saved date.
     */
    public static function if_show_notice( $key ) {
        global $proler__;

        $value = get_option( $key );
        if ( empty( $value ) ) {
            update_option( $key, gmdate( 'Y-m-d' ) );
            return false;
        }

        // if notice is done displaying forever?
        if ( 'done' === $value ) {
            return false;
        }

        // see if interval period passed.
        $difference  = date_diff( date_create( gmdate( 'Y-m-d' ) ), date_create( $value ) );
        $days_passed = (int) $difference->format( '%d' );

        return $days_passed < $proler__['notice_gap'] ? false : true;
    }



    /**
     * WooCommerce not active notice. WooCommerce MUST be active before activating this plugin.
     */
    public static function wc_missing_notice() {
        global $proler__;

        $wc = sprintf( '<a href="%s">%s</a>', esc_url( $proler__['url']['wc'] ), __( 'WooCommerce', 'product-role-rules' ) );
        ?>
        <div class="error">
            <p>
                <?php
                    echo wp_kses_post(
						sprintf(
							// translators: Placeholder %1$s is for the variable WooCommerce plugin link.
							__( 'Please install and activate %1$s first', 'product-role-rules' ),
							wp_kses_post( $wc )
						)
					);
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Notice for asking user feedback
     */
    public static function feedback_notice() {
        global $proler__;

        // get current page.
        $page = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // dynamic extra parameter adding before adding new url parameters.
        $page .= strpos( $page, '?' ) !== false ? '&' : '?';
        $page .= 'prnonce=' . wp_create_nonce( 'proler_rating_nonce' ) . '&';

        $plugin = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $proler__['url']['review'] ),
            esc_html( $proler__['name'] )
        );
        $review = sprintf(
            '<a href="%s">' . __( 'WordPress.org', 'product-role-rules' ) . '</a>',
            esc_url( $proler__['url']['review'] )
        );
        ?>
        <div class="notice notice-info is-dismissible">
            <h3><?php echo esc_html( $proler__['name'] ); ?></h3>
            <p>
                <?php
                    echo wp_kses_post(
                        sprintf(
                            // translators: %1$s: plugin name, %2$s: review link.
                            __( 'Excellent! You\'ve been using %1$s for a while. We\'d appreciate if you kindly rate us on %2$s.', 'product-role-rules' ),
                            wp_kses_post( $plugin ),
                            wp_kses_post( $review )
                        )
                    );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $proler__['url']['review'] ); ?>" class="button-primary">
                <?php echo esc_html__( 'Rate it', 'product-role-rules' ); ?>
                </a> <a href="<?php echo esc_url( $page ); ?>proler_rating=done" class="button">
                <?php echo esc_html__( 'Already Did', 'product-role-rules' ); ?>
                </a> <a href="<?php echo esc_url( $page ); ?>proler_rating=cancel" class="button">
                <?php echo esc_html__( 'Cancel', 'product-role-rules' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * PRO plugin advertising notice
     */
    public static function pro_notice() {
        global $proler__;

        // get current page.
        $page = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // dynamic extra parameter adding before adding new url parameters.
        $page .= strpos( $page, '?' ) !== false ? '&' : '?';
        $page .= 'prnonce=' . wp_create_nonce( 'proler_rating_nonce' ) . '&';

        ?>
        <div class="notice notice-warning is-dismissible">
            <h3>
                <?php echo esc_html( $proler__['name'] ); ?> <?php echo esc_html__( 'PRO', 'product-role-rules' ); ?>
            </h3>
            <p>
                <?php echo esc_html__( 'Get maximum/minimum quantity support with PRO.', 'product-role-rules' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $proler__['url']['pro'] ); ?>" class="button-primary">
                    <?php echo esc_html__( 'Get PRO', 'product-role-rules' ); ?>
                </a> <a href="<?php echo esc_url( $page ); ?>proler_notify_pro=cancel" class="button">
                    <?php echo esc_html__( 'Cancel', 'product-role-rules' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

Proler_Notice::init();
