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
class Proler_Settings_Page {

    /**
     * Current settings page slug
     *
     * @var string.
     */
    private static $page;

    /**
     * Render global settings page
     */
    public static function global_settings_page() {
        self::settings_page( 'settings' );
    }

    /**
     * Render add new role page
     */
    public static function new_role_page() {
        self::settings_page( 'newrole' );
    }

    /**
     * Render general settings page
     */
    public static function general_settings_page() {
        self::settings_page( 'general-settings' );
    }

    /**
     * Display settings page
     *
     * @param string $page_slug curent settings page slug.
     */
    public static function settings_page( $page_slug ){
        if ( ! current_user_can( 'manage_options' ) ) {
            return; // check user capabilities.
        }

        self::$page = $page_slug;

        self::get_settings_page( $page_slug );
    }



    /**
     * Settings page initialization
     *
     * @param string $page_slug settings page slug.
     */
    public static function get_settings_page( $page_slug ) {
        global $proler__;

        // set a flag in which page the settings is rendering | option page or product level.
        $proler__['which_page'] = 'option_page';
        ?>
        <form action="" method="POST">
            <div id="mpcdp_settings" class="mpcdp_container">
                <?php self::get_settings_page_header(); ?>
                <div class="mpcdp_row">
                    <div class="col-md-3" id="left-side">
                        <?php self::settings_page_navigation(); ?>
                    </div>
                    <div class="col-md-6" id="middle-content">
                        <div class="mpcdp_settings_content">
                            <div class="mpcdp_settings_section">
                                <?php self::settings_page_title(); ?>
                                <?php Proler_Admin_Settings_Helper::settings_saved_notice(); ?>
                                <?php self::get_settings_page_content(); ?>
                                <?php wp_nonce_field( 'proler_settings', 'proler_settings_nonce' ); ?>
                            </div>
                        </div>
                    </div>
                    <div id="right-side">
                        <div class="mpcdp_settings_promo">
                            <div id="wfl-promo">
                                <?php self::settings_page_sidebar(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php
        self::pro_popup( $page_slug );
    }
    public static function get_settings_page_header(){
        global $proler__;
        ?>
        <div id="mpcdp_settings_page_header">
            <div id="mpcdp_logo"><?php echo esc_html__( 'Role Based Pricing for WooCommerce', 'product-role-rules' ); ?></div>
            <div id="mpcdp_customizer_wrapper"></div>
            <div id="mpcdp_toolbar_icons">
                <a target="_blank" href="<?php echo esc_url( $proler__['url']['support'] ); ?>" data-tooltip="<?php echo esc_html__( 'Support', 'product-role-rules' ); ?>">
                <span class="tab_icon dashicons dashicons-email"></span>
                </a>
            </div>
        </div>
        <?php
    }
    public static function settings_page_navigation(){
        ?>
        <div class="mpcdp_settings_sidebar" style="position: relative;">
            <?php self::settings_menu(); ?>
            <?php self::settings_submit(); ?>
        </div>
        <?php
    }
    public static function settings_menu() {
        global $proler__;

        $pages = array(
            array(
                'slug'   => 'settings',
                'url'    => get_admin_url( null, 'admin.php?page=proler-settings' ),
                'name'   => __( 'Role Based Settings', 'product-role-rules' ),
                'icon'   => 'dashicons dashicons-groups',
                'target' => 'general',
                'class'  => '',
            ),
            array(
                'slug'   => 'newrole',
                'url'    => get_admin_url( null, 'admin.php?page=proler-newrole' ),
                'name'   => __( 'Add New Role', 'product-role-rules' ),
                'icon'   => 'dashicons dashicons-insert',
                'target' => 'new-user-role',
                'class'  => '',
            ),
            array(
                'slug'   => 'general-settings',
                'url'    => get_admin_url( null, 'admin.php?page=proler-general-settings' ),
                'name'   => __( 'General Settings', 'product-role-rules' ),
                'icon'   => 'dashicons dashicons-admin-settings',
                'target' => 'general-settings',
                'class'  => '',
            ),
        );

        if ( 'activated' !== $proler__['prostate'] ) {
            $pages[] = array(
                'slug'   => 'pro',
                'url'    => $proler__['url']['pro'],
                'name'   => __( 'Get PRO', 'product-role-rules' ),
                'icon'   => 'dashicons dashicons-external',
                'target' => 'get-pro',
                'class'  => 'proler-nav-orange',
            );
        }

        foreach ( $pages as $menu ) {
            printf(
                '<a href="%s"><div class="mpcdp_settings_tab_control %s"><span class="%s"></span><span class="label">%s</span></div></a>',
                esc_url( $menu['url'] ),
                $menu['slug'] === self::$page ? esc_attr( 'active' ) : '',
                esc_html( $menu['icon'] ),
                esc_html( $menu['name'] )
            );
        }
    }
    public static function settings_submit() {
        if ( 'newrole' === self::$page ) {
            return;
        }

        $long  = '';
        $short = '';
        if ( 'settings' === self::$page || 'general-settings' === self::$page ) {
            $long  = __( 'Save Settings', 'product-role-rules' );
            $short = __( 'Save', 'product-role-rules' );
        }
        ?>
        <div class="mpcdp_settings_submit">
            <div class="submit">
                <button class="mpcdp_submit_button">
                    <div class="save-text"><?php echo esc_html( $long ); ?></div>
                    <div class="save-text save-text-mobile"><?php echo esc_html( $short ); ?></div>
                </button>
            </div>
        </div>
        <?php
    }
    public static function settings_page_title(){
        ?>
        <div class="proler-admin-page-title">
            <?php
                if ( 'settings' === self::$page ) {
                    echo esc_html__( 'Global Role Based Settings', 'product-role-rules' );
                } elseif ( 'newrole' === self::$page ) {
                    echo esc_html__( 'Add a Custom User Role', 'product-role-rules' );
                } elseif ( 'general-settings' === self::$page ) {
                    echo esc_html__( 'General Settings', 'product-role-rules' );
                }
            ?>
        </div>
        <?php
    }
    public static function get_settings_page_content(){
        if ( 'settings' === self::$page ) {
            self::role_settings_content();
        } elseif ( 'newrole' === self::$page ) {
            self::new_role_content();
        } elseif ( 'general-settings' === self::$page ) {
            self::general_settings_content();
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
    }
    public static function new_role_content() {
        Proler_Admin_Settings_Helper::pro_info_msg( 'new-role' );
        ?>
        <div class="new-role-wrap">
            <div class="mpcdp_settings_toggle mpcdp_container">
                <div class="mpcdp_settings_option visible">
                    <div class="mpcdp_row">
                        <div class="col-md-6">
                            <input type="text" name="proler_admin_new_role" placeholder="<?php echo esc_html__( 'Example: \'B2B Customer\'', 'product-role-rules' ); ?>" >
                            <?php wp_nonce_field( 'proler_admin_create_new_role_customer' ); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="mpcdp_settings_submit" id="proler-role-create">
                                <div class="submit">
                                    <button class="mpcdp_submit_button">
                                        <div class="save-text"><?php echo esc_html__( 'Add New Role', 'product-role-rules' ); ?></div>
                                        <div class="save-text save-text-mobile"><?php echo esc_html__( 'Add', 'product-role-rules' ); ?></div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php self::user_role_list(); ?>
        </div>
        <?php
    }    public static function user_role_list() {
        global $proler__;
        global $wp_roles;

        // get all user roles.
        $roles = $wp_roles->get_names();
        $roles['visitor'] = __( 'Unregistered user', 'product-role-rules' );
        asort( $roles ); // sort the roles in title ascending order.

        // pro missing indicator class.
        $pro_class = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';

        // default WordPress user roles | for avoiding these.
        $default_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager' );
        ?>
        <div class="mpcdp_settings_toggle proler-custom-roles">
            <div class="mpcdp_settings_option visible">
                <div class="proler-role-list">
                    <div class="role-list-type"><span class="dashicons dashicons-admin-users"></span><?php echo esc_html__( 'Custom User Roles', 'product-role-rules' ); ?></div>
                    <ul>
                        <?php foreach ( $roles as $role => $name ) : ?>
                            <?php
                                if ( in_array( $role, $default_roles, true ) ) {
                                    continue;
                                }
                            ?>
                            <li>
                                <?php echo esc_html( $name ); ?>
                                <?php if ( ! in_array( $role, array( 'global', 'visitor' ), true ) ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=proler-newrole&delete=' . esc_attr( $role ) . '&nonce=' . esc_attr( wp_create_nonce( 'proler_delete_role' ) ) ) ); ?>" class="proler-delete-role"><span class="dashicons dashicons-trash <?php echo esc_attr( $pro_class ); ?>" data-protxt="<?php echo esc_html( $name ); ?>"></span></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="mpcdp_settings_toggle ">
            <div class="mpcdp_settings_option visible">
                <div class="proler-role-list">
                    <div class="role-list-type"><span class="dashicons dashicons-groups"></span><?php echo esc_html__( 'Default User Roles', 'product-role-rules' ); ?></div>
                    <ul>
                        <?php foreach ( $roles as $role => $name ) : ?>
                            <?php
                                if ( ! in_array( $role, $default_roles, true ) ) {
                                    continue;
                                }
                            ?>
                            <li><?php echo esc_html( $name ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    public static function general_settings_content(){
        global $proler__;
        Proler_Admin_Settings_Helper::pro_info_msg( 'general-settings' );
        ?>
        <div class="mpcdp_settings_toggle mpcdp_container pr-settings general-settings">
            <div class="mpcdp_settings_option visible">
                <div class="mpcdp_settings_section">
                    <?php
                        foreach( $proler__['general_settings'] as $field ){
                            self::general_settings_section( $field );
                        }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    public static function general_settings_section( $data ){
        global $proler__;
        ?>
        <?php if( isset( $data['section_title'] ) && !empty( $data['section_title'] ) ) : ?>
            <div class="mpcdp_settings_section_title"><?php echo esc_html( $data['section_title'] ); ?></div>
        <?php endif; ?>
        <div class="mpcdp_row">
            <div class="col-md-6">
                <?php if ( 'activated' !== $proler__['prostate'] ) : ?>
                    <div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
                <?php endif; ?>
                <div class="mpcdp_option_label">
                    <?php echo esc_html( $data['field_name'] ); ?>
                </div>
                <div class="settings-desc-txt">
                    <?php echo esc_html( $data['desc'] ); ?>
                </div>
            </div>
            <div class="col-md-6">
                <?php self::general_settings_field( $data ); ?>
            </div>
        </div>
        <?php
    }
    public static function general_settings_field( $data ){
        global $proler__;

        $pro_class   = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';
        $saved_value = get_option( $data['key'], $data['default'] );

        if( isset( $data['options'] ) && !empty( $data['options'] ) ){
            ?>
            <select name="<?php echo esc_attr( $data['key'] ); ?>" class="<?php echo esc_attr( $pro_class ); ?>" data-protxt="<?php echo esc_html( $data['pro_txt'] ); ?>">
                <?php foreach( $data['options'] as $value => $label ) : ?>
                    <option
                        value="<?php echo esc_attr( $value ); ?>"
                        <?php echo $value === $saved_value ? 'selected' : ''; ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }






    public static function settings_page_sidebar(){
        global $proler__;

        $sidebar_title = __( 'Upgrade to PRO Now', 'product-role-rules' );
        $side_tagline  = __( 'Get maximum/minimum quantity support with PRO', 'product-role-rules' );
        $side_button   = __( 'Get PRO', 'product-role-rules' );

        // change on prostate.
        if ( 'installed' === $proler__['prostate'] ) {
            $sidebar_title = __( 'Activate PRO Now', 'product-role-rules' );
            $side_tagline  = __( 'Get maximum/minimum quantity support with PRO', 'product-role-rules' );
            $side_button   = __( 'Activate PRO', 'product-role-rules' );
        } elseif ( 'activated' === $proler__['prostate'] ) {
            $sidebar_title = __( 'PRO License Activated', 'product-role-rules' );
            $side_tagline  = __( 'Get our exclusive PRO Support 24/7 only for you.', 'product-role-rules' );
        }
        ?>
        <div class="proler-sidebar">
            <div class="sidebar_top">
                <h2><?php echo esc_html( $sidebar_title ); ?></h2>
                <div class="tagline_side"><?php echo wp_kses_post( $side_tagline ); ?></div>
                <?php if ( isset( $proler__['prostate'] ) && 'activated' !== $proler__['prostate'] ) : ?>
                    <div class="proler-side-pro"><a href="<?php echo esc_url( $proler__['url']['pro'] ); ?>" target="_blank"><?php echo esc_html( $side_button ); ?></a></div>
                <?php endif; ?>
            </div>
            <div class="sidebar_bottom">
                <ul>
                    <li>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__( 'Maximum Quantity: Set an upper limit on the number of items a customer can purchase for a specific product.', 'product-role-rules' ); ?>
                    </li>
                    <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__( 'Minimum Quantity: Establish a minimum number of items that a customer must purchase for a specific product.', 'product-role-rules' ); ?>
                    </li>
                    <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__( 'Rocket speed support: Most of our customer\'s problem solved within 24 hours of their first contact.', 'product-role-rules' ); ?>
                    </li>
                </ul>
            </div>
            <div class="support">
                <p><a href="<?php echo esc_url( $proler__['url']['support'] ); ?>" target="_blank"><?php echo esc_html__( 'Contact us', 'product-role-rules' ); ?></a></p>
            </div>
        </div>
        <?php
    }
    public static function pro_popup( $type ){
        global $proler__;
        $text = 'newrole' !== $type ? __( 'Please upgrade to PRO to use', 'product-role-rules' ) : __( 'Please upgrade to PRO to delete', 'product-role-rules' );
        ?>
        <div class="proler-popup-wrap">
            <div class="popup-content">
                <span class="dashicons dashicons-dismiss popup-close"></span>
                <div class="pro-badge"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
                <div class="popup-focus">
                    <?php echo esc_html( $text ); ?>
                    <span class="marker"></span>
                </div>
                <p><?php echo esc_html__( 'All exclusive features for a year starting $59.00 (USD) only!', 'product-role-rules' ); ?></p>
                <a href="<?php echo esc_url( $proler__['url']['pro'] ); ?>" class="get-pro-btn" target="_blank"><?php echo esc_html__( 'Get PRO', 'product-role-rules' ); ?></a>
            </div>
        </div>
        <?php
    }
}
