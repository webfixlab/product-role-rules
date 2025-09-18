<?php
/**
 * Admin settings page class
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings Page Class.
 */
class Proler_Product_Settings {

    /**
     * Get role based settings for appropriate scope
     *
     * @var array
     */
    private static $data;

    /**
     * Current settings page slug
     *
     * @var string.
     */
    private static $page;

    public static function init(){
        // woocommerce product data tab, tab and menu.
        add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'data_tab' ), 10, 1 );
        add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'data_tab_content' ) );
    }

    /**
     * Add WooCommerce product settings data tab
     *
     * @param array $default_tabs current product settings tabs.
     */
    public static function data_tab( $default_tabs ) {
        global $post;
        global $proler__;

        $product = wc_get_product( $post->ID );
        $type    = $product->get_type();
        $proler__['product'] = array(
            'type' => $type
        );

        if( in_array( $type, array( 'grouped', 'external' ), true ) ) return $default_tabs;

        $default_tabs['role_based_pricing'] = array(
            'label'    => __( 'Role Based Pricing', 'product-role-rules' ),
            'target'   => 'proler_product_data_tab', // data tab panel id to focus.
            'priority' => 60,
            'class'    => array(),
        );

        return $default_tabs;
    }

    /**
     * Add product settings data tab content
     */
    public static function data_tab_content() {
        global $post;
        global $proler__;

        self::$data = isset( $post->ID ) && !empty( $post->ID ) ? get_post_meta( $post->ID, 'proler_data', true ) : get_option( 'proler_role_table' );

        $type = $proler__['product']['type'];
        if( in_array( $type, array( 'grouped', 'external' ), true ) ) return;

        // set a flag in which page the settings is rendering | option page or product level.
        $proler__['which_page'] = 'product';

        self::get_data_tab_content();
    }

    public static function get_data_tab_content() {
        ?>
        <div id="proler_product_data_tab" class="panel woocommerce_options_panel">
            <div id="mpcdp_settings" class="mpcdp_container">
                <div class="mpcdp_settings_content">
                    <div class="mpcdp_settings_section">
                        <?php self::get_tab_content_header(); ?>
                        <div class="role-settings-content">
                            <?php self::role_settings_content(); ?>
                        </div>
                        <?php wp_nonce_field( 'proler_product_settings', 'proler_product_settings_nonce' ); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    public static function get_tab_content_header(){
        ?>
        <div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Product Role Based Settings', 'product-role-rules' ); ?></div>
        <div class="role-settings-head mpcdp_settings_toggle mpcdp_container">
            <div class="mpcdp_settings_option visible">
                <div class="mpcdp_row">
                    <div class="col-md-6">
                        <div class="mpcdp_option_label"><?php echo esc_html__( 'Role Based Pricing', 'product-role-rules' ); ?></div>
                        <div class="settings-desc-txt"><?php echo esc_html__( 'Choose Custom to overwrite the global pricing settings.', 'product-role-rules' ); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="switch-field">
                            <?php self::settings_type(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    public static function settings_type() {
        $types = array(
            'default'      => __( 'Global', 'product-role-rules' ),
            'proler-based' => __( 'Custom', 'product-role-rules' ),
            'disable'      => __( 'Disable', 'product-role-rules' )
        );
        
        $saved_type = !empty( self::$data ) && isset( self::$data['proler_stype'] ) ? self::$data['proler_stype'] : 'default';
        foreach ( $types as $slug => $label ) {
            $checked = $slug === $saved_type ? 'checked' : '';
            ?>
            <div class="swatch-item">
                <input type="radio" id="proler_stype_<?php echo esc_attr( $slug ); ?>" name="proler_stype" value="<?php echo esc_attr( $slug ); ?>" <?php echo esc_attr( $checked ); ?>>
                <label for="proler_stype_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></label>
            </div>
            <?php
        }
    }
    public static function role_settings_content() {
        ?>
        <div class="pr-settings">
            <?php Proler_Admin_Settings_Helper::pro_info_msg( 'role-settings' ); ?>
            <?php Proler_Role_Settings::saved_role_settings(); ?>
            <div class="demo-item" style="display:none;">
                <?php Proler_Role_Settings::role_settings_item(); ?>
            </div>
        </div>
        <?php do_action( 'proler_admin_extra_section' ); ?>
        <div class="mpcdp_settings_option visible" style="margin-top:20px;">
            <div class="mpcdp_row">
                <input type="hidden" value="" name="proler_data">
                <a class="mpc-opt-sc-btn add-new" href="javaScript:void(0)"><?php echo esc_html__( 'Add New', 'product-role-rules' ); ?></a>
            </div>
        </div>
        <?php
        Proler_Settings_Page::pro_popup( 'product-settings' );
    }
}

Proler_Product_Settings::init();
