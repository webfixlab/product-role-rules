<?php
/**
 * Role based pricing admin settings template class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      3.0
 */

if ( ! class_exists( 'Proler_Settings_Template' ) ) {

	/**
	 * Role based settings admin class
	 */
	class Proler_Settings_Template {

        /**
		 * Current settings page slug
		 *
		 * @var string.
		 */
		private static $page;
		


        /**
		 * Settings page initialization
         *
         * @param string $page_slug settings page slug.
		 */
		public static function settings_page( $page_slug ) {
            global $proler__;
            self::$page = $page_slug;
			?>
			<form action="" method="POST">
                <div id="mpcdp_settings">
                    <div id="mpcdp_settings_page_header">
                        <div id="mpcdp_logo"><?php echo esc_html__( 'Role Based Pricing for WooCommerce', 'product-role-rules' ); ?></div>
                        <div id="mpcdp_toolbar_icons">
                            <a target="_blank" href="<?php echo esc_url( $proler__['url']['support'] ); ?>" data-tooltip="<?php echo esc_html__( 'Support', 'product-role-rules' ); ?>">
								<span class="tab_icon dashicons dashicons-email"></span>
                            </a>
                        </div>
                    </div>
                    <div class="mpcdp_row">
                        <?php self::settings_content(); ?>
						<div id="right-side">
							<?php self::sidebar(); ?>
						</div>
						<?php self::popup(); ?>
                    </div>
                </div>
			</form>
			<?php
		}
        /**
		 * Display settings page content
		 */
		public static function settings_content() {
			global $proler__;

			// set a flag in which page the settings is rendering | option page or product level.
			$proler__['which_page'] = 'option_page';
			?>
            <div id="left-side" class="col-md-3">
				<div class="proler-nav-wrap">
					<?php self::settings_menu(); ?>
					<?php self::settings_submit(); ?>
				</div>
            </div>
            <div class="col-md-6" id="middle-content">
				<div class="mpcdp_settings_section_title proler-page-title">
					<span class="proler-gradient">
						<?php
							if ( 'settings' === self::$page ) {
								echo esc_html__( 'Global Role Based Settings', 'product-role-rules' );
							} elseif ( 'newrole' === self::$page ) {
								echo esc_html__( 'Add a Custom User Role', 'product-role-rules' );
							} elseif ( 'general-settings' === self::$page ) {
								echo esc_html__( 'General Settings', 'product-role-rules' );
							}
						?>
					</span>
				</div>
				<?php if( 'settings' === self::$page ) : ?>
					<div class="proler-collapse-wrap">
						<span class="proler-collapse-all">Collapse all</span>
					</div>
				<?php endif; ?>
				<?php
					self::settings_saved_notice();

					if ( 'settings' === self::$page ) {
						self::role_settings_content();
					} elseif ( 'newrole' === self::$page ) {
						self::new_role_content();
					} elseif ( 'general-settings' === self::$page ) {
						self::general_settings_content();
					}
				?>
				<?php wp_nonce_field( 'proler_settings', 'proler_settings_nonce' ); ?>
            </div>
			<?php

		}

        /**
		 * Add new role page content
		 */
		public static function new_role_content() {
			?>
			<div class="mpcdp_settings_section_description">
				<?php echo esc_html__( 'Role names can include letters, numbers, spaces or underscores. Just make sure it starts with a letter!', 'product-role-rules' ); ?>
			</div>
			<div class="new-role-wrap">
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
			<div class="mpcdp_settings_option">
				<?php self::user_role_list(); ?>
			</div>
			<?php
		}

		/**
		 * General settings page content
		 */
		public static function general_settings_content(){
			global $proler__;
			?>
			<div class="pr-settings general-settings">
				<div class="mpcdp_settings_option">
					<?php
						foreach( $proler__['general_settings'] as $field ){
							self::general_settings_section( $field );
						}
					?>
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
						<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
					<?php endif; ?>
					<div class="mpcdp_option_label">
						<?php echo esc_html( $data['field_name'] ); ?>
					</div>
					<div class="mpcdp_option_description">
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



        /**
		 * Display settings page menu
		 */
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
				array(
					'slug'   => 'pro',
					'url'    => $proler__['url']['free'],
					'name'   => __( 'Get PRO', 'product-role-rules' ),
					'icon'   => '',
					'target' => 'get-pro',
					'class'  => 'proler-nav-orange',
				),
			);

			foreach ( $pages as $menu ) {
				self::display_menu_item( $menu );
			}
		}
		public static function display_menu_item( $menu ){
			global $proler__;

			if( 'pro' === $menu['slug'] && 'activated' === $proler__['prostate'] ) return;
			$is_active = $menu['slug'] === self::$page ? 'active' : '';
			?>
			<a href="<?php echo esc_url( $menu['url'] ); ?>">
				<div class="nav-item <?php echo esc_attr( $menu['class'] ) . ' ' . esc_attr( $is_active ); ?>">
					<?php if( !empty( $menu['icon'] ) ) : ?>
						<span class="<?php echo esc_html( $menu['icon'] ); ?>"></span>
					<?php endif; ?>
					<span class="label"><?php echo esc_html( $menu['name'] ); ?></span>
				</div>
			</a>
			<?php
		}

		/**
		 * Display settings page submit button
		 */
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



        /**
		 * Display product page settings type indicator
		 */
		public static function settings_type() {
			$types = array(
				'default'      => __( 'Global', 'product-role-rules' ),
				'proler-based' => __( 'Custom', 'product-role-rules' ),
				'disable'      => __( 'Disable', 'product-role-rules' ),
			);

			$data     = Proler_Settings::get_settings();
			$selected = isset( $data['proler_stype'] ) && !empty( $data['proler_stype'] ) ? $data['proler_stype'] : 'default';

			foreach( $types as $slug => $value ) : ?>
			<div class="swatch-item">
				<input type="radio" id="proler_stype_<?php echo esc_attr( $slug ); ?>" name="proler_stype" value="<?php echo esc_attr( $slug ); ?>" <?php echo $slug === $selected ? 'checked' : ''; ?>>
				<label for="proler_stype_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $value ); ?></label>
			</div>
			<?php endforeach;
		}

        /**
		 * Role settings wrapper
		 */
		public static function role_settings_content() {
			?>
			<div class="pr-settings">
				<?php self::saved_role_settings(); ?>
			</div>
			<div class="demo-item">
				<?php self::role_settings_head(); ?>
				<?php self::role_settings_details(); ?>
			</div>
			<?php do_action( 'proler_admin_extra_section' ); ?>
			<a class="add-new" href="javaScript:void(0)"><?php echo esc_html__( 'Add New', 'product-role-rules' ); ?></a>
			<input type="hidden" value="" name="proler_data">
			<?php
		}

        /**
		 * Get saved role settings
		 */
		public static function saved_role_settings() {
			$data = Proler_Settings::get_settings();
			if ( empty( $data ) || ! isset( $data['roles'] ) ) return;

			foreach ( $data['roles'] as $role => $rd ) {
				?>
				<div class="pr-item">
					<?php self::role_settings_head( $role, $rd ); ?>
					<?php self::role_settings_details( $rd ); ?>
				</div>
				<?php
			}
		}

        /**
		 * Display inidividual role setting's header
		 *
		 * @param string $role user role name.
		 * @param array  $rd   role settings data.
		 */
		public static function role_settings_head( $role = '', $rd = array() ) {
			$checked = isset( $rd['pr_enable'] ) ? $rd['pr_enable'] : '';
			$checked = !empty( $checked ) && ( '1' === $checked || true === $checked ) ? 'on' : 'off';
			$checked = empty( $rd ) ? 'on' : $checked;

			$str = self::role_settings_overview( $rd );
			?>
			<div class="mpcdp_settings_option proler-option-head">
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php self::roles_select( $role ); ?>
					</div>
					<div class="col-md-6">
						<?php
							self::switch_box(
								esc_html__( 'Off', 'product-role-rules' ),
								esc_html__( 'On', 'product-role-rules' ),
								$checked
							);
						?>
						<input type="checkbox" name="pr_enable" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;">
						<span class="proler-arrow"><img src="<?php echo esc_url( plugins_url( 'product-role-rules/assets/images/right.svg' ) ); ?>"></span>
						<span class="dashicons dashicons-dismiss proler-delete"></span>
						<div class="mpcdp_option_description prdis-msg" style="display:<?php echo 'off' === $checked ? 'block' : 'none'; ?>;">
							<?php echo esc_html__( 'Settings disabled!', 'product-role-rules' ); ?>
						</div>
					</div>
				</div>
				<?php if ( ! empty( $str ) ) : ?>
					<div class="mpcdp_row">
						<div class="mpcdp_option_description role-overview">
							<?php echo wp_kses_post( $str ); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Display all roles select dropdown
		 *
		 * @param string $selected role name that should be selected.
		 */
		public static function roles_select( $selected ) {
			$roles = [];
			foreach ( wp_roles()->roles as $role => $data ) {
				$roles[ $role ] = $data['name'];
            }
			$roles[ 'visitor' ] = __( 'Unregistered user', 'product-role-rules' );
			?>
			<select name="proler_roles" class="proler-roles">
				<option value=""><?php echo __( 'Choose a role', 'product-role-rules' ); ?></option>
				<?php foreach( $roles as $role => $name ) : ?>
					<option value="<?php echo esc_attr( $role ); ?>" <?php echo $role === $selected ? 'selected' : ''; ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}

        /**
		 * Display individual role settings content
		 *
		 * @param array $rd role settings data.
		 */
		public static function role_settings_details( $rd = array() ) {
			global $proler__;
			// self::log( 'has pro? ' . $proler__['has_pro'] );
			$discount_type = isset( $rd['discount_type'] ) ? $rd['discount_type'] : '';
			$pro_class     = isset( $proler__['has_pro'] ) && !$proler__['has_pro'] ? 'wfl-nopro' : '';
			$ad_display    = isset( $rd['additional_discount_display'] ) ? $rd['additional_discount_display'] : 'table_min';
			?>
			<div class="mpcdp_settings_option proler-option-content" style="display:none;">
				<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Price Options', 'product-role-rules' ); ?></div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Hide Price', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Enable if you want to hide price or show custom text instead of price.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<?php
							$checked = isset( $rd['hide_price'] ) && '1' === $rd['hide_price'] ? 'on' : 'off';
							self::switch_box( __( 'Show', 'product-role-rules' ), __( 'Hide', 'product-role-rules' ), $checked );
						?>
						<input type="checkbox" name="hide_price" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;">
					</div>
				</div>
				<div class="mpcdp_row">
					<div class="col-md-12 <?php echo 'off' === $checked ? 'disabled' : ''; ?>">
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Custom Text Instead of Price', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Show a custom message instead of product price. Like "Only for B2B users". Enable "Hide Price" to use this.', 'product-role-rules' ); ?>
						</div>
						<textarea class="proler-widefat" name="hide_txt" cols="30" placeholder="<?php echo esc_html__( 'Placeholder text', 'product-role-rules' ); ?>" <?php echo 'off' === $checked ? esc_attr( 'disabled' ) : ''; ?>><?php echo isset( $rd['hide_txt'] ) ? esc_html( $rd['hide_txt'] ) : ''; ?></textarea>
					</div>
				</div>
				<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Purchase Limits', 'product-role-rules' ); ?></div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
							<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
						<?php endif; ?>
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Minimum Quantity', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Set the minimum product quantity a user must buy.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<input type="text" name="min_qty" class="<?php echo esc_attr( $pro_class ); ?>" value="<?php echo isset( $rd['min_qty'] ) ? esc_attr( $rd['min_qty'] ) : ''; ?>" data-protxt="<?php echo esc_html__( 'Minimum Quantity', 'product-role-rules' ); ?>">
					</div>
				</div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
							<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
						<?php endif; ?>
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Maximum Quantity', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Set the maximum product quantity a user can buy.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<input type="text" name="max_qty" class="<?php echo esc_attr( $pro_class ); ?>" value="<?php echo isset( $rd['max_qty'] ) ? esc_attr( $rd['max_qty'] ) : ''; ?>" data-protxt="<?php echo esc_html__( 'Minimum Quantity', 'product-role-rules' ); ?>">
					</div>
				</div>
				<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Discount Settings', 'product-role-rules' ); ?></div>
				<div class="mpcdp_row proler-discount">
					<div class="col-md-6">
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Flat Discount', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Set discount for all users of this role.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6 proler-inline">
						<input type="text" name="discount" value="<?php echo isset( $rd['discount'] ) ? esc_attr( $rd['discount'] ) : ''; ?>">
						<select name="discount_type">
							<option value="percent" <?php echo esc_attr( 'percent' === $discount_type ? 'selected' : '' ); ?>>%</option>
							<option value="price" <?php echo esc_attr( 'price' === $discount_type ? 'selected' : '' ); ?>><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></option>
						</select>
					</div>
				</div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Show Discount Text', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Displays a "Save up to ..." message for each product.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<?php
							$checked = ! isset( $rd['discount_text'] ) ? 'off' : 'on';
							self::switch_box(
								esc_html__( 'Show', 'product-role-rules' ),
								esc_html__( 'Hide', 'product-role-rules' ),
								$checked
							);
						?>
						<input type="checkbox" name="discount_text" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;">
					</div>
				</div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
							<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
						<?php endif; ?>
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Hide Regular Price', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Only show discounted price. Removes regular price and show only sale price.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<?php
							$checked = isset( $rd['hide_regular_price'] ) && '1' === $rd['hide_regular_price'] ? 'on' : 'off';

							self::switch_box(
								esc_html__( 'Hide', 'product-role-rules' ),
								esc_html__( 'Show', 'product-role-rules' ),
								$checked
							);
						?>
						<input type="checkbox" name="hide_regular_price" class="<?php echo esc_attr( $pro_class ); ?>" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;" data-protxt="<?php echo esc_html__( 'Hide Regular Price', 'product-role-rules' ); ?>">
					</div>
				</div>
				<div class="mpcdp_row proler-full-section">
					<div class="col-md-12">
						<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
							<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
						<?php endif; ?>
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Discount Tiers', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Offer more discount when user buys more, either by quantity or total spend. Examples: offer 30% off of product when user buys more than $2,000 or offer $15 off of product when user buys more than 10 items.', 'product-role-rules' ); ?>
						</div>
					</div>
				</div>
				<div class="mpcdp_row discount-ranges-main">
					<div class="col-md-12">
						<div class="discount-range-wrap">
							<?php
								if ( isset( $rd['ranges'] ) ) {
									foreach ( $rd['ranges'] as $item ) {
										self::discount_range_row( $item );
									}
								}
							?>
						</div>
						<div class="mpcdp_row discount-range-demo">
							<?php self::discount_range_row(); ?>
						</div>
						<div class="add-new-disrange"><?php echo esc_html__( 'Add Tier', 'product-role-rules' ); ?></div>
					</div>
				</div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
							<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
						<?php endif; ?>
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Discount Tiers View', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Choose how to display discount tiers.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<select name="additional_discount_display" class="<?php echo esc_attr( $pro_class ); ?>" data-protxt="<?php echo esc_html__( 'Discount Options', 'product-role-rules' ); ?>">
							<?php
								$ads = array(
									'table_max' => __( 'Table - show both min and max range', 'product-role-rules' ),
									'table_min' => __( 'Table - show only min', 'product-role-rules' ),
									'tag_max'   => __( 'List - show both min and max', 'product-role-rules' ),
									'tag_min'   => __( 'List - show only min', 'product-role-rules' ),
								);

								foreach( $ads as $val => $label ){
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $val ),
										$val === $ad_display ? esc_attr( 'selected' ) : '',
										esc_html( $label )
									);
								}
							?>
						</select>
					</div>
				</div>
				<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Apply Rule If...', 'product-role-rules' ); ?></div>
				<?php if ( 'option_page' === $proler__['which_page'] ) : ?>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Category', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Choose on which category this rule will apply.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<select name="category">
							<option value=""><?php echo esc_html__( 'Choose a category', 'product-role-rules' ); ?></option>
							<?php
								$args = array(
									'taxonomy'   => 'product_cat',
									'hide_empty' => false,
									'orderby'    => 'name',
									'order'      => 'ASC',
								);

								$cats = get_terms( $args );

								if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
									foreach ( $cats as $cat ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $cat->term_id ),
											isset( $rd['category'] ) && $cat->term_id === (int) $rd['category'] ? esc_attr( 'selected' ) : '',
											esc_html( $cat->name )
										);
									}
								}
							?>
						</select>
					</div>
				</div>
				<?php endif; ?>
				<?php if ( 'option_page' === $proler__['which_page'] ) : ?>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Product Type', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo esc_html__( 'Choose on which product type this rule will apply.', 'product-role-rules' ); ?>
						</div>
					</div>
					<div class="col-md-6">
						<select name="product_type">
							<?php
								$options = array(
									'none'     => __( 'Choose a type', 'product-role-rules' ),
									'simple'   => __( 'Simple', 'product-role-rules' ),
									'variable' => __( 'Variable', 'product-role-rules' )
								);

								foreach( $options as $val => $label ){
									printf(
										'<option value="%s" %s>%s</option>',
										'none' === $val ? '' : esc_attr( $val ),
										isset( $rd['product_type'] ) && $val === $rd['product_type'] ? 'selected' : '',
										esc_html( $label )
									);
								}
							?>
						</select>
					</div>
				</div>
				<?php endif; ?>
				<?php
					$date_from = $rd['schedule']['start'] ?? '';
					$date_to   = $rd['schedule']['end'] ?? '';
				?>
				<div class="mpcdp_row proler-full-section">
					<div class="col-md-12">
						<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
							<div class="mpcdp_settings_option_ribbon"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
						<?php endif; ?>
						<div class="mpcdp_option_label"><?php echo esc_html__( 'Schedule', 'product-role-rules' ); ?></div>
						<div class="mpcdp_option_description">
							<?php echo sprintf(
								// translators: %1$s is the current time.
								__( 'Set the time frame when this rule will be active. Note: current time is %1$s', 'product-role-rules' ),
								self::convert_to_wp_timezone( '', false )
							); ?>
							<?php if( ! self::check_schedule( $date_from, $date_to ) ) : ?>
								<br><span>Please note: The time schedule is <strong style="color: #f84f09;">INACTIVE</strong>.</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php $date_from = !empty( $date_from ) ? self::convert_to_wp_timezone( $date_from, true ) : ''; ?>
						<input
							type="datetime-local"
							name="schedule_start"
							class="<?php echo esc_attr( $pro_class ); ?>"
							data-protxt="<?php echo esc_html__( 'Schedule Start', 'product-role-rules' ); ?>"
							<?php echo !empty( $date_from ) ? "value=\"{$date_from}\"" : ''; ?>>
					</div>
					<div class="col-md-6">
						<input
							type="datetime-local"
							name="schedule_end"
							class="<?php echo esc_attr( $pro_class ); ?>"
							data-protxt="<?php echo esc_html__( 'Ending Date and Time', 'product-role-rules' ); ?>"
							<?php echo !empty( $date_from ) ? "value=\"{$date_to}\"" : ''; ?>>
					</div>
				</div>
			</div>
			<?php
		}

		/**
         * Convert given time, if any, to WP Timezone format
         *
         * @param string $value     Given date time string.
         * @param bool   $for_input If it's for displaying in an input field.
         */
        public static function convert_to_wp_timezone( $value = '', $for_input = true ){
            $value       = !isset( $value ) || empty( $value ) ? 'now' : $value;
            $wp_timezone = new DateTimeZone( wp_timezone_string() );

            if( 'now' !== $value ){
                $datetime = new DateTime( $value, new DateTimeZone( 'UTC' ) ); 
                $datetime->setTimezone( $wp_timezone );
            }else{
                $datetime = new DateTime( $value, $wp_timezone );
            }

            return $for_input ? $datetime->format( 'Y-m-d\TH:i' ) : $datetime->format( 'Y-m-d h:i a' );
        }

		/**
         * Checks wheather the given schedulue is over or not
         *
         * @param string $date_from Schedule start datetime.
         * @param string $date_to   Schedule ending datetime.
         */
        public static function check_schedule( $date_from, $date_to ){
            $wp_timezone = new DateTimeZone( wp_timezone_string() );

            // convert saved UTC datetime to WP Timezone.
            $now    = new DateTime( 'now', $wp_timezone );
            $ts_now = $now->getTimestamp();

            $in_schedule = true;
            if( isset( $date_from ) && !empty( $date_from ) ){
                $datetime_from = new DateTime( $date_from, new DateTimeZone( 'UTC' ) ); 
                $datetime_from->setTimezone( $wp_timezone );
                
                $ts_from     = $datetime_from->getTimestamp();
                $in_schedule = $ts_now < $ts_from ? false : $in_schedule;
            }
            if( isset( $date_to ) && !empty( $date_to ) ){
                $datetime_to = new DateTime( $date_to, new DateTimeZone( 'UTC' ) ); 
                $datetime_to->setTimezone( $wp_timezone );

                $ts_to       = $datetime_to->getTimestamp();
                $in_schedule = $ts_now > $ts_to ? false : $in_schedule;
            }

            return $in_schedule;
        }

        /**
		 * Display switch box
		 *
		 * @param string $on    on status text.
		 * @param string $off   off status text.
		 * @param string $value value of the switch box.
		 */
		public static function switch_box( $on, $off, $value ) {
			$checked = ! empty( $value ) && ( 'on' === $value || true === $value ) ? 'on' : 'off';
			?>
			<div class="hurkanSwitch">
				<div class="hurkanSwitch-switch-box switch-animated-<?php echo esc_attr( $checked ); ?>">
					<a class="hurkanSwitch-switch-item <?php echo 'on' === $checked ? 'active' : ''; ?> hurkanSwitch-switch-item-color-success  hurkanSwitch-switch-item-status-on">
						<span class="lbl"><?php echo esc_html( $off ); ?></span>
						<span class="hurkanSwitch-switch-cursor-selector"></span>
					</a>
					<a class="hurkanSwitch-switch-item <?php echo 'off' === $checked ? 'active' : ''; ?> hurkanSwitch-switch-item-color-  hurkanSwitch-switch-item-status-off">
						<span class="lbl"><?php echo esc_html( $on ); ?></span>
						<span class="hurkanSwitch-switch-cursor-selector"></span>
					</a>
				</div>
			</div>
			<?php
		}

        /**
		 * Display discount range row
		 *
		 * @param array $data saved data.
		 */
		public static function discount_range_row( $data = array() ) {
			global $proler__;

			$pro_class = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';

			$type     = isset( $data['discount_type'] ) ? $data['discount_type'] : 'amount_percent';
			$min      = isset( $data['min'] ) ? $data['min'] : '';
			$max      = isset( $data['max'] ) ? $data['max'] : '';
			$discount = isset( $data['discount'] ) ? $data['discount'] : '';
			?>
			<div class="mpcdp_row disrange-item">
				<select name="discount_type">
					<?php self::discount_tier_type( $type ); ?>
				</select>
				<input type="text" name="min" class="<?php echo esc_attr( $pro_class ); ?>" placeholder="<?php echo esc_html__( 'Min', 'product-role-rules' ); ?>" value="<?php echo esc_attr( $min ); ?>" data-protxt="<?php echo esc_html__( 'Min Value', 'product-role-rules' ); ?>">
				<input type="text" name="max" class="<?php echo esc_attr( $pro_class ); ?>" placeholder="<?php echo esc_html__( 'Max', 'product-role-rules' ); ?>" value="<?php echo esc_attr( $max ); ?>" data-protxt="<?php echo esc_html__( 'Max Value', 'product-role-rules' ); ?>">
				<input type="text" name="discount" class="<?php echo esc_attr( $pro_class ); ?>" placeholder="<?php echo esc_html__( 'Discount', 'product-role-rules' ); ?>" value="<?php echo esc_attr( $discount ); ?>" data-protxt="<?php echo esc_html__( 'Dynamic Discount', 'product-role-rules' ); ?>">
				<span class="dashicons dashicons-trash delete-disrange"></span>
			</div>
			<?php
		}
		public static function discount_tier_type( $type ){
			$symbol = get_woocommerce_currency_symbol();
			$options = array(
				'amount_percent' => __( 'Discount on Total', 'product-role-rules' ),
				'amount_fixed' => __( 'Discount on Total', 'product-role-rules' ),
				'quantity_percent' => __( 'Discount on Quantity', 'product-role-rules' ),
				'quantity_fixed' => __( 'Discount on Quantity', 'product-role-rules' )
			);
			foreach( $options as $key => $label ){
				$prefix = false !== strpos( $key, 'fixed' ) ? $symbol : '%';
				$prefix = sprintf( '(%s) %s', esc_html( $prefix ), esc_html( $label ) );
				?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php echo $key === $type ? 'selected' : ''; ?>><?php echo esc_html( $prefix ); ?></option>
				<?php
			}
		}

        /**
		 * Display individual role settings overview
		 *
		 * @param array $rd role settings data.
		 */
		public static function role_settings_overview( $rd ) {
			// if this role settings isn't enabled, return empty.
			if ( ! isset( $rd['pr_enable'] ) ) {
				return '';
			}

			$str = '';
			if ( isset( $rd['hide_price'] ) && '1' === $rd['hide_price'] ) {
				$str .= __( 'Price', 'product-role-rules' ) . ' <strong style="color: #d30e0e;">' . __( 'HIDDEN', 'product-role-rules' ) . '</strong>';
			}

			if ( isset( $rd['pr_enable'] ) && '1' !== $rd['pr_enable'] ) {
				$str = '';
			}

			return $str;
		}



		/**
		 * Display settings saved notice
		 */
		public static function settings_saved_notice(){
			global $proler__;

			// this update nontice is for "Add new role" page only.
			if( isset( $proler__['user_role_msg'] ) && !empty( $proler__['user_role_msg'] ) ){
				self::update_notice( $proler__['user_role_msg']['msg'], $proler__['user_role_msg']['cls'] );
				return;
			}

			if ( ! isset( $_POST['proler_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['proler_settings_nonce'] ) ), 'proler_settings' ) ) {
				return;
			}

			if ( ! isset( $_POST ) && ! isset( $_POST['proler_data'] ) ) {
				return;
			}

			self::update_notice( __( 'Your settings have been saved.', 'product-role-rules' ), 'saved' );
		}

		/**
		 * Summary of update_notice
		 * @param mixed $msg
		 * @param mixed $icon
		 * @return void
		 */
		public static function update_notice( $msg, $icon ){
			?>
			<div class="proler-saved-settings <?php echo esc_attr( $icon ); ?>">
				<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
				<?php echo wp_kses_post( $msg ); ?>
			</div>
			<?php
		}

        /**
		 * Display a list of all user roles
		 */
		public static function user_role_list() {
			$default = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager' );

			$custom_roles  = [];
			$default_roles = [];
			// $roles['global'] = __( 'All roles', 'product-role-rules' );
			foreach ( wp_roles()->roles as $role => $rd ) {
				if( in_array( $role, $default, true ) ) $default_roles[ $role ] = $rd['name'];
				else $custom_roles[ $role ] = $rd['name'];
			}
			$custom_roles['visitor'] = __( 'Unregistered user', 'product-role-rules' );

			// sort the roles in title ascending order.
			asort( $default_roles );
			asort( $custom_roles );
			?>
			<div class="mpcdp_row proler-custom-roles">
				<div class="col-md-12">
					<div class="proler-role-list">
						<h2><?php echo esc_html__( 'All Custom Roles', 'product-role-rules' ); ?></h2>
						<ul>
							<?php self::custom_role_list( $custom_roles ); ?>
						</ul>
					</div>
					<div class="proler-role-list">
						<h2><?php echo esc_html__( 'All Default Roles', 'product-role-rules' ); ?></h2>
						<ul>
							<?php self::default_role_list( $default_roles ); ?>
						</ul>
					</div>
				</div>
			</div>
			<?php
		}
		public static function custom_role_list( $roles ){
			global $proler__;
			$pro_class = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';
			
			foreach( $roles as $slug => $name ){
				if( 'visitor' === $slug ){
					echo sprintf( '<li>%s</li>', esc_html__( 'Visitor', 'product-role-rules' ) );
					continue;
				}
				echo sprintf(
					'<li>%s <a href="%s" class="proler-delete-role"><span class="dashicons dashicons-trash %s" data-protxt="%s"></span></a>',
					esc_html( $name ),
					esc_url( admin_url( 'admin.php?page=proler-newrole&delete=' . esc_attr( $slug ) . '&nonce=' . esc_attr( wp_create_nonce( 'proler_delete_role' ) ) ) ),
					esc_attr( $pro_class ),
					esc_html( $name )
				);
			}
		}
		public static function default_role_list( $roles ){
			foreach( $roles as $slug => $name ){
				echo sprintf( '<li>%s</li>', esc_html( $name ) );
			}
		}
		


		/**
		 * Display pro field pop-up
		 */
		public static function popup(){
			global $proler__;
			?>
			<div id="prolerpop" class="proler-popup">
				<div class="image-wrap">
					<span class="dashicons dashicons-dismiss mpcpop-close close"></span>
					<div class="mpc-focus focus">
						<?php echo esc_html__( 'Please upgrade to PRO to use', 'product-role-rules' ); ?> <span class="proler-gradient"></span>.
						<a href="<?php echo esc_url( $proler__['url']['free'] ); ?>" target="_blank"><?php echo esc_html__( 'Get PRO', 'product-role-rules' ); ?></a>
					</div>
					<div class="mpcex-features">
						<p><?php echo esc_html__( 'Maximum & minimum quantity fields available in PRO', 'product-role-rules' ); ?></p>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Display settings sidebar
		 */
		public static function sidebar(){
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
			<div class="sidebar_top">
				<h3><?php echo esc_html( $sidebar_title ); ?></h3>
				<div class="tagline_side"><?php echo wp_kses_post( $side_tagline ); ?></div>
				<?php if ( isset( $proler__['prostate'] ) && 'activated' !== $proler__['prostate'] ) : ?>
					<a href="<?php echo esc_url( $proler__['url']['free'] ); ?>" target="_blank"><?php echo esc_html( $side_button ); ?></a>
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
			<?php
		}


		private static function log( $data ) {
			if ( true === WP_DEBUG ) {
				if ( is_array( $data ) || is_object( $data ) ) {
					error_log( print_r( $data, true ) );
				} else {
					error_log( $data );
				}
			}
		}
    }
}
