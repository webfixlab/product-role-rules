<?php
/**
 * Plugin notice handler class
 *
 * @package    WordPress
 * @subpackage Multiple Products to Cart for WooCommerce
 * @since      7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MPC Admin Notice Handler Class.
 */
class Proler_Notice {
    public static function init(){
        add_action( 'init', array( __CLASS__, 'admin_notice' ) );
        add_action( 'admin_head', array( __CLASS__, 'remove_admin_notice' ) );
    }

    /**
     * Add plugin related notices.
     */
    public static function admin_notice() {
        global $proler__;

        if ( isset( $_GET['prnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['prnonce'] ) ), 'proler_rating_nonce' ) ) {
            if ( isset( $_GET['proler_rating'] ) ) {
                $task = sanitize_key( wp_unslash( $_GET['proler_rating'] ) );

                if ( 'done' === $task ) {
                    // never show this notice again.
                    update_option( 'proler_rating', 'done' );
                } elseif ( 'cancel' === $task ) {
                    // show this notice in a week again.
                    update_option( 'proler_rating', gmdate( 'Y-m-d' ) );
                }
            }
        } elseif ( isset( $_GET['pinonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['pinonce'] ) ), 'proler_pro_info_nonce' ) ) {
            if ( isset( $_GET['proler_notify_pro'] ) ) {
                if ( 'cancel' === sanitize_key( wp_unslash( $_GET['proler_notify_pro'] ) ) ) {
                    update_option( 'proler_notify_pro', gmdate( 'Y-m-d' ) );
                }
            }
        } else {
            if ( self::date_difference( 'proler_rating', $proler__['notice_gap'], 'done' ) ) {
                // show notice to rate us after 15 days interval.
                add_action( 'admin_notices', array( __CLASS__, 'ask_feedback_notice' ) );
            }

            $proinfo = get_option( 'proler_notify_pro' );
            if ( empty( $proinfo ) || '' === $proinfo ) {
                add_action( 'admin_notices', array( __CLASS__, 'pro_notice' ) );
            } elseif ( self::date_difference( 'proler_notify_pro', $proler__['notice_gap'], '' ) ) {
                // show notice to inform about pro version after 15 days interval.
                add_action( 'admin_notices', array( __CLASS__, 'pro_notice' ) );
            }
        }
    }

    /**
     * Check if notice interval is passed given interval
     *
     * @param string $key             Notice type option name.
     * @param int    $notice_interval Notice interval in days.
     * @param string $skip_           Whether this notice purpose is complete.
     */
    public static function date_difference( $key, $notice_interval, $skip_ = '' ) {
        $value = get_option( $key );

        if ( empty( $value ) || '' === $value ) {
            // if skip value is meta value - return false.
            if ( '' !== $skip_ && $skip_ === $value ) {
                return false;
            } else {
                $c   = date_create( gmdate( 'Y-m-d' ) );
                $d   = date_create( $value );
                $dif = date_diff( $c, $d );
                $b   = (int) $dif->format( '%d' );

                // if days difference meets minimum given interval days - return true.
                if ( $b >= $notice_interval ) {
                    return true;
                }
            }
        } else {
            add_option( $key, gmdate( 'Y-m-d' ) );
        }

        return false;
    }

    public static function remove_admin_notice(){
        remove_all_actions( 'admin_notices' );
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
							// translators: Placeholder %s is for the variable WooCommerce plugin link.
							__( 'Please install and activate %s first', 'product-role-rules' ),
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
    public static function ask_feedback_notice() {
        global $proler__;

        // get current page.
        $page = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        $page .= strpos( $page, '?' ) !== false ? '&' : '?';
        $nonce = wp_create_nonce( 'proler_rating_nonce' );

        $plugin = sprintf(
            '<strong><a href="%s">%s</a></strong>',
            esc_url( $proler__['url']['review'] ),
            esc_html( $proler__['name'] )
        );

        $review = sprintf(
            '<strong><a href="%s">%s</a></strong>',
            esc_url( $proler__['url']['review'] ),
            __( 'WordPress.org', 'product-role-rules' )
        );

        ?>
        <div class="notice notice-info is-dismissible">
            <h3><?php echo esc_html( $proler__['name'] ); ?></h3>
            <p>
                <?php
                    echo wp_kses_post(
                        sprintf(
                            // translators: %1$s: plugin name with url, %2$s: WordPress and review url.
                            __( 'Excellent! You\'ve been using %1$s for a while. We\'d appreciate if you kindly rate us on %2$s', 'product-role-rules' ),
                            wp_kses_post( $plugin ),
                            wp_kses_post( $review )
                        )
                    );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $proler__['url']['review'] ); ?>" class="button-primary">
                    <?php echo esc_html__( 'Rate it', 'product-role-rules' ); ?>
                </a> <a href="<?php echo esc_url( $page ); ?>mpca_rate_us=done&nonce=<?php echo esc_attr( $nonce ); ?>" class="button">
                    <?php echo esc_html__( 'Already Did', 'product-role-rules' ); ?>
                </a> <a href="<?php echo esc_url( $page ); ?>mpca_rate_us=cancel&nonce=<?php echo esc_attr( $nonce ); ?>" class="button">
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
        $page = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // dynamic extra parameter adding beore adding new url parameters.
        $page .= strpos( $page, '?' ) !== false ? '&' : '?';

        $pro_feature = sprintf(
            '<strong>%s</strong>',
            __( '5+ PRO features available!', 'product-role-rules' )
        );

        $pro_link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url( $proler__['url']['pro'] ),
            __( 'PRO features here', 'product-role-rules' )
        );

        ?>
        <div class="notice notice-warning is-dismissible">
            <h3><?php echo esc_html( $proler__['name'] ); ?> <?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></h3>
            <p>
                <?php
                    echo wp_kses_post(
                        sprintf(
                            // translators: %1$s: pro features number, %2$s: pro feature list url.
                            __( '%1$s Supercharge Your WooCommerce Stores with our light, fast and feature-rich version. See all %2$s', 'product-role-rules' ),
                            wp_kses_post( $pro_feature ),
                            wp_kses_post( $pro_link )
                        )
                    );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $proler__['url']['pro'] ); ?>" class="button-primary">
                    <?php echo esc_html__( 'Get PRO', 'product-role-rules' ); ?>
                </a> <a href="<?php echo esc_url( $page ); ?>mpca_notify_pro=cancel&pinonce=<?php echo esc_attr( wp_create_nonce( 'proler_pro_info_nonce' ) ); ?>" class="button">
                    <?php echo esc_html__( 'Cancel', 'product-role-rules' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

Proler_Notice::init();
