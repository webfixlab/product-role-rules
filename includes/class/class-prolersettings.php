<?php

/**
 * Role based pricing admin settings class.
 */

if ( ! class_exists( 'ProlerSettings' ) ) {
    class ProlerSettings {
        private $data; // role based settings data array.
        private $page; // current settings page slug.
        private $is_settings; // flag to identify if it's plugin settings page or single product edit page.



        function __construct(){}
        public function init(){

            add_action( 'admin_init', array( $this, 'save_plugin_settings' ) );
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

            add_action( 'save_post', array( $this, 'save_settings' ), 10, 3 );
            add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'save_settings' ), 1 );

            // woocommerce product data tab, tab and menu.
            add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_data_tab' ), 10, 1 );
            add_action( 'woocommerce_product_data_panels', array( $this, 'data_tab_content' ) );

        }



        public function save_plugin_settings(){

            global $pagenow;
            $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

            // run this for plugin settings pages only.
            if( ! empty( $pagenow ) && 'admin.php' === $pagenow ){

                if( 'proler-settings' === $page ){
                    $this->save_settings();
                }else if( 'proler-newrole' === $page ){
                    $this->add_new_role();
                }

            }

        }
        public function add_admin_menu(){

            global $proler__;

            // Main menu
            add_menu_page( 
                'WooCommerce Role',
                'Role Pricing',
                'manage_options',
                'proler-settings',
                array( $this, 'global_settings_page' ),
                plugin_dir_url( PROLER ) . 'assets/images/admin-icon.svg',
                56
            );

            // settings submenu - settings
            add_submenu_page(
                'proler-settings',
                'WooCommerce Role - Settings',
                'Role Pricing',
                'manage_options',
                'proler-settings'
            );

            // settings submenu - Add new role
            add_submenu_page(
                'proler-settings',
                'Add new user role',
                'Add New Role',
                'manage_options',
                'proler-newrole',
                array( $this, 'new_role_page' )
            );
            
            // conditional extra links
            global $submenu;
            $label = '';
            if( $proler__['prostate'] == 'none' ){
                $submenu['proler-settings'][] = array( '<span style="color: #ff8921;">Get PRO</span>', 'manage_options', esc_url( $proler__['prolink'] ) );
            }

        }
        public function save_settings( $post_id = '', $post = array(), $update = '' ){
            
            // check if this is role related scope or not, if not leave this place
            if( ! isset( $_POST['proler_data'] ) ) return;

            if( isset( $post->post_type ) && 'product' !== $post->post_type ){
                return;
            }
            
            $d = sanitize_text_field( $_POST['proler_data'] );
            $d = str_replace( '\\', '', $d );
            
            $data = json_decode( $d, true ); // true for returning array
            
            if( isset( $data['proler_stype'] ) ){
                $data['proler_stype'] = sanitize_text_field( $data['proler_stype'] );
            }

            if( ! isset( $data['roles'] ) ){
                
                if( ! empty( $post_id ) ){
                    update_post_meta( $post_id, 'proler_data', $data );
                }else{
                    update_option( 'proler_role_table', $data );
                }

                return;
            }

            $rdt = array();
            
            foreach( $data['roles'] as $role => $rd ){

                $role = $this->input_sanitize( $role );
                $rdt[ $role ] = array();

                // hide this price?
                if( isset( $rd['hide_price'] ) ) $rdt[$role]['hide_price']       = sanitize_key( $rd['hide_price'] );

                // if price is hidden, show this message instead of the blank price
                if( isset( $rd['hide_txt'] ) ) $rdt[$role]['hide_txt']           = $this->input_sanitize( $rd['hide_txt'] );
                
                if( isset( $rd['pr_enable'] ) ) $rdt[$role]['pr_enable']         = sanitize_key( $rd['pr_enable'] );
                
                if( isset( $rd['discount'] ) ) $rdt[$role]['discount']           = $this->input_sanitize( $rd['discount'] );
                if( isset( $rd['discount_type'] ) ) $rdt[$role]['discount_type'] = $this->input_sanitize( $rd['discount_type'] );

                if( isset( $rd['min_qty'] ) ) $rdt[$role]['min_qty']             = $this->input_sanitize( $rd['min_qty'] );
                if( isset( $rd['max_qty'] ) ) $rdt[$role]['max_qty']             = $this->input_sanitize( $rd['max_qty'] );

            }

            $data['roles'] = $rdt;

            if( ! empty( $post_id ) ){
                update_post_meta( $post_id, 'proler_data', $data );
            }else{
                update_option( 'proler_role_table', $data );
            }

        }
        public function add_data_tab( $default_tabs ) {

            $default_tabs['role_based_pricing'] = array(
                'label'   =>  __( 'Role Based Pricing', 'proler' ),
                'target'  =>  'proler_product_data_tab', // data tab panel id to focus.
                'priority' => 60,
                'class'   => array()
            );
        
            return $default_tabs;
        
        }
        public function data_tab_content() {
            // post edit page.

            $data = $this->get_settings();
            $this->data = $data;
            $this->is_settings = false;


            $display = isset( $data['proler_stype'] ) && 'proler-based' === $data['proler_stype'] ? 'block' : 'none';

            ?>
            <div id="proler_product_data_tab" class="panel woocommerce_options_panel">
                <div class="proler-container">
                    <div class="pr-settings-content">
                        <h4>Select an option</h4>
                        <?php $this->settings_type(); ?>
                    </div>
                    <div class="pr-settings" style="display:<?php echo esc_attr( $display ); ?>;">
                        <?php $this->all_role_settings_wrap(); ?>
                        <div class="pr-demo-item" style="display:none;">
                            <?php $this->role_settings_wrap(); ?>
                        </div>
                    </div>
                    <button type="button" class="button pri-new-item" style="display:<?php echo esc_attr( $display ); ?>;">Add new</button>
                    <div class="proler-input">
                        <input type="hidden" name="proler_data" value="">
                    </div>
                </div>
                <?php include( PROLER_PATH . 'templates/admin/popup.php' ); ?>
            </div>
            <?php
        
        }



        public function global_settings_page(){

            $this->data = $this->get_settings();
            $this->page = 'settings';

            $this->settings_page();
            
        }
        public function new_role_page(){

            $this->page = 'newrole';
            $this->settings_page();

        }



        public function settings_page(){

            // check user capabilities
            if ( ! current_user_can( 'manage_options' ) ) return;
        
            // check if the user have submitted the settings
            // WordPress will add the "settings-updated" $_GET parameter to the url
            if ( isset( $_GET['settings-updated'] ) ) {
                // add settings saved message with the class of "updated"
                add_settings_error( 'wporg_messages', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
            }
        
            // show error/update messages
            settings_errors( 'wporg_messages' );

            $this->load_settings_template();

        }
        public function load_settings_template(){

            // plugin settings page.

            global $proler__;
            $this->is_settings = true;

            ?>
            <div class="proler-admin-wrap">
                <div class="proler-heading">
                    <h1 class=""><?php echo esc_html( $proler__['plugin_name'] ); ?> - Settings</h1>
                    <div class="proler-heading-desc">
                        <p>
                            <a href="<?php echo esc_url( $proler__['plugin']['docs'] ); ?>" target="_blank">DOCUMENTATION</a> | <a href="<?php echo esc_url( $proler__['plugin']['request_quote'] ); ?>" target="_blank">SUPPORT</a>
                        </p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="col-9" id="col9-special">
                        <form action="" method="POST">
                            <div class="row">
                                <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                                    <?php $this->settings_menu(); ?>
                                </nav>
                            </div>
                            <div class="wrabpa-sections">
                                <?php
                                    if( 'settings' === $this->page ){
                                        $this->global_settings_section();
                                    }else if( 'newrole' === $this->page ){
                                        $this->new_role_section();
                                    }
                                ?>
                            </div>
                        </form>
                    </div>
                    <div class="col-3">
                        <?php include( PROLER_PATH . 'templates/admin/sidebar.php' ); ?>
                    </div>
                    <?php
                    include( PROLER_PATH . 'templates/admin/popup.php' ); ?>
                </div>
            </div>
            <?php

        }



        public function get_settings(){

            global $post;

            if( isset( $post->ID ) ){
                $data = get_post_meta( $post->ID, 'proler_data', true );

            }else{
                $data = get_option( 'proler_role_table' );
            }
            
            if( empty( $data ) ){
                return array();
            }

            return $data;

        }
        public function settings_type(){

            $data = $this->data;

            $value = 'default';
            if( ! empty( $data ) && isset( $data['proler_stype'] ) ){
                $value = $data['proler_stype'];
            }

            $types = array(
                'default' => 'Global Settings',
                'proler-based' => 'Product Based',
                'disable' => 'Disable Role Pricing'
            );

            echo '<div class="switch-field">';

            foreach( $types as $v => $label ){
                $checked = $v === $value ? 'checked' : '';

                echo '<div class="swatch-item">';

                echo sprintf(
                    '<input type="radio" id="proler_stype_%s" name="proler_stype" value="%s" %s><label for="proler_stype_%s">%s</label>',
                    esc_attr( $v ),
                    esc_attr( $v ),
                    esc_attr( $checked ),
                    esc_attr( $v ),
                    esc_html( $label )
                );

                echo '</div>';
                
            }

            echo '</div>';

            // show notice.
            if( 'default' === $value ){
                ?>
                <span class="prs-notice"><a href="<?php echo esc_url( admin_url( 'admin.php?page=proler-settings' ) ); ?>">View Global Settings</a>.</span>
                <?php
            }else if( 'disable' === $value ){
                ?>
                <span class="prs-notice"><em>Role Based Pricing is disabled for this product.</em></span>
                <?php
            }

        }
        public function all_role_settings_wrap(){
            
            $data = $this->data;

            if( empty( $data ) || ! isset( $data['roles'] ) ){
                return;
            }

            foreach( $data['roles'] as $role => $rd ){
                
                $disable = isset( $rd['pr_enable'] ) && '1' === $rd['pr_enable'] ? '' : 'pr-disabled';

                echo sprintf(
                    '<div class="pr-item %s">',
                    esc_attr( $disable )
                );

                $this->role_settings_wrap( $role, $rd );
                
                echo '</div>';

            }

        }
        public function role_settings_wrap( $role = '', $rd = array() ){

            ?>
            <div class="pri-head">
                <?php $this->get_roles( $role ); ?>
                <div class="pri-buttons">
                    <label class="switch">
                        <input type="checkbox" name="pr_enable" value="Yes" <?php echo isset( $rd['pr_enable'] ) && '1' === $rd['pr_enable'] ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                    <span class="pri-delete dashicons dashicons-trash"></span>
                </div>
            </div>
            <div class="pri-content" style="display:none;">
                <table>
                    <tbody>
                        <?php $this->role_settings( $rd ); ?>
                    </tbody>
                </table>
            </div>
            <?php

        }
        public function role_settings( $rd ){

            global $proler__;

            $discount_type = isset( $rd['discount_type'] ) ? $rd['discount_type']  : '';

            $pro_class = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';

            ?>
            <tr>
                <td>Discount</td>
                <td>
                    <input type="text" class="wc_input_price" name="discount" value="<?php echo isset( $rd['discount'] ) ? esc_attr( $rd['discount'] ) : ''; ?>" placeholder="">
                    <select name="discount_type">
                        <option value="percent" <?php echo 'percent' === $discount_type ? 'selected' : ''; ?>>%</option>
                        <option value="price" <?php echo 'price' === $discount_type ? 'selected' : ''; ?>><?php echo get_woocommerce_currency_symbol(); ?></option>
                    </select>
                </td>
                <td>Hide Price</td>
                <td>
                    <input type="text" name="hide_txt" placeholder="Placeholder text if price is hidden" value="<?php echo isset( $rd['hide_txt'] ) ? esc_html( $rd['hide_txt'] ) : ''; ?>">
                    <label class="switch">
                        <input type="checkbox" name="hide_price" value="Yes" <?php echo isset( $rd['hide_price'] ) && '1' === $rd['hide_price'] ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="prr-qty-field">
                    Minimum Quantity
                    <?php if( ! empty( $pro_class ) ) : ?>
                        <div class="ribbon_pro"><span class="dashicons dashicons-lock"></span></div>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="text" class="qty-field wc_input_price <?php echo esc_attr( $pro_class ); ?>" name="min_qty" value="<?php echo isset( $rd['min_qty'] ) ? esc_attr( $rd['min_qty'] ) : ''; ?>" data-protxt="Minimum quantity">
                </td>
                <td class="prr-qty-field">
                    Maximum Quantity
                    <?php if( ! empty( $pro_class ) ) : ?>
                        <div class="ribbon_pro"><span class="dashicons dashicons-lock"></span></div>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="text" class="qty-field wc_input_price <?php echo esc_attr( $pro_class ); ?>" name="max_qty" value="<?php echo isset( $rd['max_qty'] ) ? esc_attr( $rd['max_qty'] ) : ''; ?>"data-protxt="Maximum quantity">
                </td>
            </tr>
            <?php

        }

        
        
        public function get_roles( $selected ){

            global $proler__;
            ?>
            <select name="proler_roles" class="proler-roles">
                <option value="">Choose a role</option>
                <option value="global" <?php echo 'global' === $selected ? 'selected' : ''; ?>>Global</option>
                <?php
                foreach( wp_roles()->roles as $role => $role_data ){
                    $name = $role_data['name'];

                    // if given value and this one match, make it selected
                    $s = '';
                    if( $role == $selected ) $s = ' selected';

                    echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $role ), esc_html( $s ), esc_html( $name ) );
                }
                ?>
                <option value="visitor"<?php echo $selected == 'visitor' ? ' selected' : '';  ?>><?php echo esc_html( $proler__['visitor_role_label'] ); ?></option>
            </select>
            <?php

        }
        public function settings_menu(){

            global $proler__;

            $pages = array(
                array(
                    'slug' => 'settings',
                    'url' => get_admin_url( null, 'admin.php?page=proler-settings' ),
                    'name' => 'Global Settings',
                    'icon' => 'dashicons dashicons-admin-settings',
                    'target' => 'general',
                    'class' => ''
                ),
                array(
                    'slug' => 'newrole',
                    'url' => get_admin_url( null, 'admin.php?page=proler-newrole' ),
                    'name' => 'Add New Role',
                    'icon' => 'dashicons dashicons-admin-users',
                    'target' => 'new-user-role',
                    'class' => ''
                ),
                array(
                    'slug' => 'pro',
                    'url' => $proler__['plugin']['free_url'],
                    'name' => 'Get PRO',
                    'icon' => '',
                    'target' => 'get-pro',
                    'class' => 'proler-nav-orange'
                )
            );

            foreach( $pages as $menu ){
                echo sprintf(
                    '<a class="nav-tab %s %s" href="%s" data-target="%s" target="%s"><span class="%s"></span> %s</a>',
                    $menu['slug'] === $this->page ? esc_attr( 'nav-tab-active' ) : '',
                    esc_attr( $menu['class'] ),
                    esc_url( $menu['url'] ),
                    esc_attr( $menu['target'] ),
                    'pro' === $menu['slug'] ? '_blank' : '',
                    esc_html( $menu['icon'] ),
                    esc_html( $menu['name'] )
                );
            }

        }
        public function show_notice(){
            
            global $proler__;

            // Display notices
            if( isset( $proler__['notice'] ) ){
                foreach( $proler__['notice'] as $notice ){
                    echo wp_kses_post( $notice );
                }
            }

            if( ! isset( $_POST ) || ! isset( $_POST['proler_data'] ) ){
                return;
            }
            
            ?>
            <div class="pr-notice">
                Settings Saved.
            </div>
            <?php

        }



        public function global_settings_section(){

            global $proler__;

            ?>
            <div class="section general">
                <div class="prolersg-role-section">
                    <div class="role-table">
                        <h2>Role Based Pricing</h2>
                        <?php $this->show_notice(); ?>
                        <div class="pr-settings">
                            <?php $this->all_role_settings_wrap(); ?>
                            <div class="pr-demo-item" style="display:none;">
                                <?php $this->role_settings_wrap(); ?>
                            </div>
                        </div>
                        <button type="button" class="button pri-new-item">Add new</button>
                        <div class="proler-input">
                            <input type="hidden" name="proler_data" value="">
                        </div>
                    </div>
                </div>
            </div>
            <?php do_action( 'proler_admin_extra_section' ); ?>
            <div class="">
                <input type="hidden" value="" name="role_table_data">
                <?php wp_nonce_field( 'proler_settings' ); ?>                    
                <input type="submit" value="Save changes" class="button-primary woocommerce-save-button roletable-save">
            </div>
            <?php

        }
        public function new_role_section(){

            global $proler__;

            ?>
            <div class="section new-user-role">
                <div class="role-inner">
                    <div class="create-role-wrap">
                        <h2>Add new user role</h2>
                        <?php echo isset( $proler__['user_role_msg'] ) ? wp_kses_post( $proler__['user_role_msg'] ) : ''; ?>
                        <input type="text" name="proler_admin_new_role" placeholder="Example: 'B2B Customer'" >
                        <?php wp_nonce_field( 'proler_admin_create_new_role_customer' ); ?>
                        <input type="submit" value="Create new role" class="button-primary woocommerce-save-button roletable-save">
                        <p><strong>IMPORTANT: Role name starts with letters and <br>accepts letters, digits, spaces and '_' only.</strong></p>
                    </div>
                </div>
                <div class="role-inner">
                    <div class="user-role-list">
                        <h2>Current role names</h2>
                        <?php $this->user_role_list(); ?>
                    </div>
                </div>
            </div>
            <?php

        }



        public function user_role_list(){
            
            global $proler__;

            echo '<ul>';

            foreach( wp_roles()->roles as $role => $role_data ){
                echo sprintf(
                    '<li><span class="dashicons dashicons-yes"></span>%s</li>',
                    esc_html( $role_data['name'] )
                );
            }
            echo sprintf(
                '<li><span class="dashicons dashicons-yes"></span>%s</li>',
                esc_html( $proler__['visitor_role_label'] )
            );

            echo '</ul>';

        }
        public function input_sanitize( $val ){

            $val = str_replace( ':*dblqt*:', '"', $val );
            $val = str_replace( ':*snglqt*:', '\'', $val );
            $val = sanitize_text_field( $val );
            
            return $val;

        }
        public function add_new_role(){

            /**
             * add new user role
             * 
             * Notes - 
             *      1. Must start with letter and only letters, digits, '_' ( underscore ) and ' ' ( space ) allowed
             *      2. Must be more than three (3) characters logn
             *      3. Must not exists before, as user role
             *      4. 'Customer' user role must exists or defined before
             */
            global $proler__;

            // if nothing to save, skip
            if( ! isset( $_POST['proler_admin_new_role'] ) ) return;

            // if not eligible to save, return
            if( ! check_admin_referer( 'proler_admin_create_new_role_customer' ) ) return;

            // for error, indicate what went wrong, if possible
            $msg = '';
            $proler__['user_role_msg'] = '';

            // get role name
            $new_role = sanitize_user( $_POST['proler_admin_new_role'] );
            $new_role__ = str_replace( '-', '_', sanitize_title( $new_role ) );

            // flag: to continue adding new role
            $is_valid = false;

            // if anything other than letters, digits, '_' and ' ' exists in the name, don't go ahead
            if( preg_match( "/^[a-zA-Z]+[a-zA-Z0-9_ ]+$/", $new_role ) ) $is_valid = true;

            // check if minimum 3 characters
            if( strlen( $new_role ) < 3 ){
                $msg = '<p class="error">Sorry, role name must be at least 3 characters.</p>';
                $is_valid = false;
            }

            // check if customer role exists also
            $customer_exists = false;

            if( $is_valid ){
                foreach( wp_roles()->roles as $role => $role_data ){

                    // first check for customer
                    if( $role == 'customer' ) $customer_exists = true;

                    // check if asking role does not exit
                    if( $role == $new_role__ ){
                        $msg = sprintf( '<p>Sorry, cannot add <strong>%s</strong> role, already exists.</p>', esc_html( $new_role ) );
                        $is_valid = false;
                        break;
                    }
                }
            }

            // finally add this role
            if( $is_valid && $customer_exists ){
                add_role( $new_role__, $new_role, get_role( 'customer' )->capabilities );
                $notify['is_done'] = true;
                $msg = sprintf( '<p><strong>%s</strong> role created successfully.</p>', esc_html( $new_role ) );
            }else{
                $notify['is_done'] = false;
                if( empty( $msg ) ) $msg = sprintf( '<p class="error">Sorry, <strong>%s</strong> cannot be created.</p>', esc_html( $new_role ) );
            }

            // save, if any, message for displaying letter.
            if( !empty( $msg ) ) $proler__['user_role_msg'] = $msg;

        }
        public function delete_role(){
            if( !isset( $_GET['proler_delete_role'] ) ) return;

            $role = sanitize_key( $_GET['proler_delete_role'] );
            remove_role( $role );
        }
        
    }
}

$cls = new ProlerSettings();
$cls->init();
