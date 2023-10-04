<?php

/**
 * Role based pricing admin settings class.
 */

if ( ! class_exists( 'ProlerSettings' ) ) {
    class ProlerSettings {
        private $data; // role based settings data array.
        private $page; // current settings page slug.


        function __construct(){}
        public function init(){

            add_action( 'admin_init', array( $this, 'save_plugin_settings' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );

            add_action( 'save_post', array( $this, 'save_settings' ), 10, 3 );
            add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'save_settings' ), 1 );

            // woocommerce product data tab, tab and menu.
            add_filter( 'woocommerce_product_data_tabs', array( $this, 'data_tab' ), 10, 1 );
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



        public function admin_menu(){

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
        public function data_tab( $default_tabs ) {

            $default_tabs['role_based_pricing'] = array(
                'label'   =>  __( 'Role Based Pricing', 'proler' ),
                'target'  =>  'proler_product_data_tab', // data tab panel id to focus.
                'priority' => 60,
                'class'   => array()
            );
        
            return $default_tabs;
        
        }
        public function data_tab_content(){

            ?>
            <div id="proler_product_data_tab" class="panel woocommerce_options_panel">
                <div id="mpcdp_settings" class="mpcdp_container">
                    <div class="mpcdp_settings_content">
                        <div class="mpcdp_settings_section">
                            <div class="mpcdp_settings_section_title">Product Role Based Settings</div>
                            <?php $this->settings_type(); ?>
                            <div class="role-settings-content">
                                <?php $this->role_settings_content(); ?>
                            </div>
                        </div>
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

            ?>
            <form action="" method="POST">
                <?php $this->settings_content(); ?>
            </form>
            <?php

        }
        


        public function settings_content(){

            global $proler__;

            ?>
            <div id="mpcdp_settings" class="mpcdp_container">
                <div id="mpcdp_settings_page_header">
                    <div id="mpcdp_logo">Role Based Pricing for WooCommerce</div>
                    <div id="mpcdp_customizer_wrapper"></div>
                    <div id="mpcdp_toolbar_icons">
                        <a class="mpcdp-tippy" target="_blank" href="<?php echo esc_url( $proler__['plugin']['docs'] ); ?>" data-tooltip="Documentation">
                        <span class="tab_icon dashicons dashicons-media-document"></span>
                        </a>
                        <a class="mpcdp-tippy" target="_blank" href="<?php echo esc_url( $proler__['plugin']['request_quote'] ); ?>" data-tooltip="Support">
                        <span class="tab_icon dashicons dashicons-email"></span>
                        </a>
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="col-md-3" id="left-side">
                        <div class="mpcdp_settings_sidebar" data-sticky-container="" style="position: relative;">
                            <div class="mpcdp_sidebar_tabs">
                                <div class="inner-wrapper-sticky">
                                    <?php $this->settings_menu(); ?>
                                    <?php $this->settings_submit(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" id="middle-content">
                        <div class="mpcdp_settings_content">
                            <div class="mpcdp_settings_section">
                                <?php 
                                    if( 'settings' === $this->page ){
                                        echo '<div class="mpcdp_settings_section_title">Global Role Based Settings</div>';
                                        $this->role_settings_content();
                                    }else if( 'newrole' === $this->page ){
                                        echo '<div class="mpcdp_settings_section_title">Create New User Role</div>';
                                        $this->new_role_content();
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div id="right-side">
                        <div class="mpcdp_settings_promo">
                            <div id="wfl-promo">
                                <?php include( PROLER_PATH . 'templates/admin/sidebar.php' ); ?>
                            </div>
                        </div>
                    </div>
                    <?php include( PROLER_PATH . 'templates/admin/popup.php' ); ?>
                </div>
            </div>
            <?php

        }
        public function settings_menu(){
            
            global $proler__;

            $pages = array(
                array(
                    'slug' => 'settings',
                    'url' => get_admin_url( null, 'admin.php?page=proler-settings' ),
                    'name' => 'Settings',
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
                    '<a href="%s"><div class="mpcdp_settings_tab_control %s"><span class="%s"></span><span class="label">%s</span></div></a>',
                    esc_url( $menu['url'] ),
                    $menu['slug'] === $this->page ? esc_attr( 'active' ) : '',
                    esc_html( $menu['icon'] ),
                    esc_html( $menu['name'] )
                );

            }

        }
        public function settings_submit(){
            
            $long = '';
            $short = '';
            if( 'settings' === $this->page ){
                $long = 'Save Settings';
                $short = 'Save';
            }else if( 'newrole' === $this->page ){
                $long = 'Add New Role';
                $short = 'Add';
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


        
        public function role_settings_content(){

            ?>
            <div class="pr-settings">
                <?php $this->saved_role_settings(); ?>
                <div class="demo-item" style="display:none;">
                    <?php $this->role_settings_head(); ?>
                    <?php $this->role_settings_details(); ?>
                </div>
            </div>
            <?php do_action( 'proler_admin_extra_section' ); ?>
            <div class="mpcdp_settings_option visible" style="margin-top:20px;">
                <div class="mpcdp_row">
                    <input type="hidden" value="" name="proler_data">
                    <a class="mpc-opt-sc-btn add-new" href="javaScript:void(0)">Add New</a>
                </div>
            </div>
            <?php

        }
        public function new_role_content(){
            
            global $proler__;

            ?>
            <div class="mpcdp_settings_toggle mpcdp_container">
                <div class="mpcdp_settings_option visible">
                    <div class="mpcdp_row">
                        <?php echo isset( $proler__['user_role_msg'] ) ? wp_kses_post( $proler__['user_role_msg'] ) : ''; ?>
                        <input type="text" name="proler_admin_new_role" placeholder="Example: 'B2B Customer'" >
                        <?php wp_nonce_field( 'proler_admin_create_new_role_customer' ); ?>
                    </div>
					<div class="mpcdp_row">
                        <div class="mpcdp_option_description">
                            <strong>IMPORTANT:</strong> Role name starts with letters and accepts letters, digits, spaces and '_' only.
                        </div>
                    </div>
				</div>
            </div>
            <div class="mpcdp_settings_toggle mpcdp_container">
				<div class="mpcdp_settings_option visible">
					<div class="mpcdp_row">
						<div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-6">
                            <h2>Current role names</h2>
                            <?php $this->user_role_list(); ?>
						</div>
					</div>
				</div>
			</div>
            <?php

        }



        public function saved_role_settings(){
            
            $data = $this->get_settings();

            if( empty( $data ) || ! isset( $data['roles'] ) ){
                return;
            }

            foreach( $data['roles'] as $role => $rd ){

                ?>
                <div class="mpcdp_settings_toggle pr-item">
                    <?php $this->role_settings_head( $role, $rd ); ?>
                    <?php $this->role_settings_details( $rd ); ?>
                </div>
                <?php

            }

        }
        public function role_settings_head( $role = '', $rd = array() ){

            $checked = isset( $rd['pr_enable'] ) && '1' === $rd['pr_enable'] ? 'on' : 'off';
            if( empty( $rd ) ){
                $checked = 'on';
            }

            ?>
            <div class="mpcdp_settings_option visible">
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_description col-md-6">
                        <?php $this->get_roles( $role ); ?>
                    </div>
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-6">
                        <?php $this->switch_box( 'Off', 'On', $checked ); ?>
                        <input type="checkbox" name="pr_enable" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;">
                        <a class="mpc-opt-sc-btn delete" href="javaScript:void(0)">Delete</a>
                        <a class="mpc-opt-sc-btn edit" href="javaScript:void(0)">+</a>
                    </div>
                </div>
            </div>
            <?php

        }
        public function role_settings_details( $rd = array() ){

            global $proler__;

            $discount_type = isset( $rd['discount_type'] ) ? $rd['discount_type']  : '';
            $pro_class = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';

            ?>
            <div class="mpcdp_settings_option" style="display:block;">
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_description col-md-9">
                        <div class="mpcdp_option_label">Discount</div>
                        <div class="mpcdp_option_description">
                            Add a custom page URL where the customer should be redirected.
                        </div>
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-9">
                        <input type="text" name="discount" value="<?php echo isset( $rd['discount'] ) ? esc_attr( $rd['discount'] ) : ''; ?>" placeholder="Type discount amount">
                    </div>
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-3">
                        <select name="discount_type">
                            <option value="percent" <?php echo 'percent' === $discount_type ? 'selected' : ''; ?>>%</option>
                            <option value="price" <?php echo 'price' === $discount_type ? 'selected' : ''; ?>><?php echo get_woocommerce_currency_symbol(); ?></option>
                        </select>
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_description col-md-9">
                        <div class="mpcdp_option_label">Hide Price</div>
                        <div class="mpcdp_option_description">
                            Add a custom page URL where the customer should be redirected.
                        </div>
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-9">
                        <input type="text" name="hide_txt" value="<?php echo isset( $rd['hide_txt'] ) ? esc_html( $rd['hide_txt'] ) : ''; ?>" placeholder="Type placeholder message">
                    </div>
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-3">
                        <?php

                        $checked = isset( $rd['hide_price'] ) && '1' === $rd['hide_price'] ? 'on' : 'off';
                        $this->switch_box( 'Show', 'Hide', $checked );
                        
                        ?>
                        <input type="checkbox" name="hide_price" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;">
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_description col-md-9">
                        <div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new">PRO</div>
                        <div class="mpcdp_option_label">Minimum Quantity</div>
                        <div class="mpcdp_option_description">
                            Add a custom page URL where the customer should be redirected.
                        </div>
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-9">
                        <input type="text" name="min_qty" class="<?php echo esc_attr( $pro_class ); ?>" value="<?php echo isset( $rd['min_qty'] ) ? esc_attr( $rd['min_qty'] ) : ''; ?>" placeholder="Minimum quantity to buy" data-protxt="Minimum Quantity">
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_description col-md-9">
                        <div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new">PRO</div>
                        <div class="mpcdp_option_label">Maximum Quantity</div>
                        <div class="mpcdp_option_description">
                            Add a custom page URL where the customer should be redirected.
                        </div>
                    </div>
                </div>
                <div class="mpcdp_row">
                    <div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-9">
                        <input type="text" name="max_qty" class="<?php echo esc_attr( $pro_class ); ?>" value="<?php echo isset( $rd['max_qty'] ) ? esc_attr( $rd['max_qty'] ) : ''; ?>" placeholder="Maximum quantity to by" data-protxt="Minimum Quantity">
                    </div>
                </div>
            </div>
            <?php

        }



        public function settings_type(){

            $data = $this->get_settings();

            $value = 'default';
            if( ! empty( $data ) && isset( $data['proler_stype'] ) ){
                $value = $data['proler_stype'];
            }

            $types = array(
                'default'      => 'Global',
                'proler-based' => 'Custom',
                'disable'      => 'Disable'
            );

            ?>
            <div class="mpcdp_settings_toggle mpcdp_container" data-toggle-id="wmc_redirect">
				<div class="mpcdp_settings_option visible" data-field-id="wmc_redirect">
					<div class="mpcdp_row">
						<div class="mpcdp_settings_option_description col-md-6">
							<div class="mpcdp_option_label">Role Based Settings</div>
                            <div class="mpcdp_option_description">Choose <strong>Custom</strong> for changing this product pricing option or others.</div>
						</div>
						<div class="mpcdp_settings_option_field mpcdp_settings_option_field_text col-md-6">
                            <div class="switch-field">
                                <?php

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
                                ?>
                            </div>
                        </div>
                    </div>
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
            <div class="pr-notice"><span class="dashicons dashicons-yes"></span> Settings saved successfully.</div>
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
        public function switch_box( $on, $off, $value ) {

			$checked = ! empty( $value ) && ( 'on' === $value || true === $value ) ? 'on' : 'off';

			?>
			<div class="hurkanSwitch hurkanSwitch-switch-plugin-box">
				<div class="hurkanSwitch-switch-box switch-animated-<?php echo esc_attr( $checked ); ?>">
					<a class="hurkanSwitch-switch-item <?php echo 'on' === $checked ? 'active' : ''; ?> hurkanSwitch-switch-item-color-success  hurkanSwitch-switch-item-status-on">
						<span class="lbl"><?php echo esc_html( $on ); ?></span>
						<span class="hurkanSwitch-switch-cursor-selector"></span>
					</a>
					<a class="hurkanSwitch-switch-item <?php echo 'off' === $checked ? 'active' : ''; ?> hurkanSwitch-switch-item-color-  hurkanSwitch-switch-item-status-off">
						<span class="lbl"><?php echo esc_html( $off ); ?></span>
						<span class="hurkanSwitch-switch-cursor-selector"></span>
					</a>
				</div>
			</div>
			<?php

		}
        
    }
}

$cls = new ProlerSettings();
$cls->init();
