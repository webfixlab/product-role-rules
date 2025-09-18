<?php
/**
 * Role based pricing admin settings class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Role_Settings' ) ) {

	/**
	 * Role based settings admin class
	 */
	class Proler_Role_Settings {

        /**
         * Get role based settings for appropriate scope
         *
         * @var array
         */
        private static $data;

        public static function set_data(){
            global $post;

            self::$data = isset( $post->ID ) && !empty( $post->ID ) ? get_post_meta( $post->ID, 'proler_data', true ) : get_option( 'proler_role_table' );
        }
        
        public static function saved_role_settings() {
            self::set_data();

			if ( empty( self::$data ) || ! isset( self::$data['roles'] ) ) {
				return;
			}
			
			foreach ( self::$data['roles'] as $role => $rd ) {
				?>
				<div class="mpcdp_settings_toggle pr-item">
					<?php self::role_settings_item( $role, $rd ); ?>
				</div>
				<?php
			}
		}
        public static function role_settings_item( $role = '', $rd = [] ){
            self::role_settings_head( $role, $rd );
            self::role_settings_details( $rd );
        }
        public static function role_settings_head( $role = '', $rd = array() ) {
			$checked = isset( $rd['pr_enable'] ) && '1' === $rd['pr_enable'] ? 'on' : 'off';
			if ( empty( $rd ) ) {
				$checked = 'on';
			}

			$str = self::role_settings_overview( $rd );
			?>
			<div class="mpcdp_settings_option visible proler-option-head">
				<div class="mpcdp_row">
					<div class="col-md-6">
						<?php self::roles_select( $role ); ?>
					</div>
					<div class="col-md-6">
						<?php
							self::switch_box(
								esc_html__( 'Off', 'product-role-rules' ),
								esc_html__( 'On', 'product-role-rules' ),
								$checked,
								array(
									'key'      => 'pr_enable'
								)
							);
						?>
						<span class="proler-arrow"><img src="<?php echo esc_url( plugins_url( 'product-role-rules/assets/images/right.svg' ) ); ?>"></span>
						<span class="dashicons dashicons-dismiss proler-delete"></span>
						<div class="settings-desc-txt prdis-msg" style="display:<?php echo 'off' === $checked ? 'block' : 'none'; ?>;">
							<?php echo esc_html__( 'Settings disabled!', 'product-role-rules' ); ?>
						</div>
					</div>
				</div>
				<?php if ( ! empty( $str ) ) : ?>
					<div class="mpcdp_row">
						<div class="settings-desc-txt role-overview">
							<?php echo wp_kses_post( $str ); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
        public static function role_settings_overview( $rd ) {
			if ( ! isset( $rd['pr_enable'] ) || ( isset( $rd['pr_enable'] ) && '1' !== $rd['pr_enable'] ) ) {
				return ''; // if no settings exists or it's disabled, skip.
			}

			if ( isset( $rd['hide_price'] ) && '1' === $rd['hide_price'] ) {
				return __( 'Price', 'product-role-rules' ) . ' <strong style="color: #d30e0e;">' . __( 'HIDDEN', 'product-role-rules' ) . '</strong>';
			}

			return '';
		}
        public static function roles_select( $selected ) {
			global $wp_roles;

			$roles = $wp_roles->get_names();
			$roles['visitor'] = __( 'Unregistered user', 'product-role-rules' );
			asort( $roles ); // sort the roles in title ascending order.
            ?>
            <select name="proler_roles" class="proler-roles">
                <option value=""><?php echo esc_html__( 'Choose a role', 'product-role-rules' ); ?></option>
                <?php
                    foreach( $roles as $role => $name ) {
                        echo sprintf( '<option value="%s" %s>%s</option>',
                            esc_attr( $role ),
                            !empty( $selected ) && $role === $selected ? 'selected' : '',
                            esc_html( $name )
                        );
                    }
                ?>
            </select>
            <?php
		}
        public static function switch_box( $on, $off, $value, $field ) {
			$checked = ! empty( $value ) && ( 'on' === $value || true === $value ) ? 'on' : 'off';
			?>
			<div class="switch-box-wrap">
				<div class="switch-box">
					<a class="switch-point <?php echo 'on' === $checked ? 'active' : ''; ?> switch-on">
						<span class="lbl"><?php echo esc_html( $off ); ?></span>
						<span class="switch-pointer"></span>
					</a>
					<a class="switch-point <?php echo 'off' === $checked ? 'active' : ''; ?> switch-off">
						<span class="lbl"><?php echo esc_html( $on ); ?></span>
						<span class="switch-pointer"></span>
					</a>
				</div>
				<input
					type="checkbox"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="<?php echo isset( $field['class'] ) ? esc_attr( $field['class'] ) : ''; ?>"
					data-protxt="<?php echo isset( $field['label'] ) ? esc_html( $field['label'] ) : ''; ?>"
					<?php echo 'on' === $checked ? 'checked' : ''; ?>>
			</div>
			<?php

		}

        public static function role_settings_details( $rd = array() ) {
			global $proler__;

			$discount_type = isset( $rd['discount_type'] ) ? $rd['discount_type'] : '';
			$pro_class     = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';
			$ad_display    = isset( $rd['additional_discount_display'] ) ? $rd['additional_discount_display'] : 'table_min';
			?>
			<div class="mpcdp_settings_option proler-option-content" style="display:none;">
				<div class="mpcdp_settings_section">
					<div class="mpcdp_settings_section_title" style="margin-top: 20px;"><?php echo esc_html__( 'Price Options', 'product-role-rules' ); ?></div>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Hide Price', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Enable if you want to hide price or show custom text instead of price.', 'product-role-rules' ); ?>
							</div>
						</div>
						<div class="col-md-6">
							<?php
								$checked = isset( $rd['hide_price'] ) && '1' === $rd['hide_price'] ? 'on' : 'off';
								self::switch_box(
									__( 'Show', 'product-role-rules' ),
									__( 'Hide', 'product-role-rules' ),
									$checked,
									array(
										'key'      => 'hide_price'
									)
								);
							?>
						</div>
					</div>
					<div class="mpcdp_row">
						<div class="col-md-12">
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Custom Text Instead of Price', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Show a custom message instead of product price. Like "Only for B2B users". Enable "Hide Price" to use this.', 'product-role-rules' ); ?>
							</div>
							<textarea
								name="hide_txt"
								class="proler-widefat"
								placeholder="<?php echo esc_html__( 'Placeholder text', 'product-role-rules' ); ?>"
								cols="30"><?php echo isset( $rd['hide_txt'] ) ? esc_html( $rd['hide_txt'] ) : ''; ?></textarea>
						</div>
					</div>
				</div>
				<div class="mpcdp_settings_section">
					<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Purchase Limits', 'product-role-rules' ); ?></div>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
								<div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
							<?php endif; ?>
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Minimum Quantity', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
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
								<div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
							<?php endif; ?>
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Maximum Quantity', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Set the maximum product quantity a user can buy.', 'product-role-rules' ); ?>
							</div>
						</div>
						<div class="col-md-6">
							<input type="text" name="max_qty" class="<?php echo esc_attr( $pro_class ); ?>" value="<?php echo isset( $rd['max_qty'] ) ? esc_attr( $rd['max_qty'] ) : ''; ?>" data-protxt="<?php echo esc_html__( 'Maximum Quantity', 'product-role-rules' ); ?>">
						</div>
					</div>
				</div>
				<div class="mpcdp_settings_section">
					<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Discount Settings', 'product-role-rules' ); ?></div>
					<div class="mpcdp_row proler-discount">
						<div class="col-md-6">
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Flat Discount', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Set discount for all users of this role.', 'product-role-rules' ); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="mpcdp_row proler-inline">
								<div class="col-md-6">
									<input type="text" name="discount" value="<?php echo isset( $rd['discount'] ) ? esc_attr( $rd['discount'] ) : ''; ?>">
								</div>
								<div class="col-md-6">
									<select name="discount_type">
										<option value="percent" <?php echo esc_attr( 'percent' === $discount_type ? 'selected' : '' ); ?>>%</option>
										<option value="price" <?php echo esc_attr( 'price' === $discount_type ? 'selected' : '' ); ?>><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Show Discount Text', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Displays a "Save up to ..." message for each product.', 'product-role-rules' ); ?>
							</div>
						</div>
						<div class="col-md-6">
							<?php
								$checked = ! isset( $rd['discount_text'] ) ? 'off' : 'on';
								self::switch_box(
									esc_html__( 'Show', 'product-role-rules' ),
									esc_html__( 'Hide', 'product-role-rules' ),
									$checked,
									array(
										'key'      => 'discount_text'
									)
								);
							?>
						</div>
					</div>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
								<div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
							<?php endif; ?>
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Hide Regular Price', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Only show discounted price. Removes regular price and show only sale price.', 'product-role-rules' ); ?>
							</div>
						</div>
						<div class="col-md-6">
							<?php
								$checked = isset( $rd['hide_regular_price'] ) && '1' === $rd['hide_regular_price'] ? 'on' : 'off';

								self::switch_box(
									esc_html__( 'Hide', 'product-role-rules' ),
									esc_html__( 'Show', 'product-role-rules' ),
									$checked,
									array(
										'key'   => 'hide_regular_price',
										'class' => $pro_class,
										'label' => __( 'Hide Regular Price', 'product-role-rules' )
									)
								);
							?>
							<input type="checkbox" name="hide_regular_price" class="<?php echo esc_attr( $pro_class ); ?>" <?php echo 'off' === $checked ? '' : 'checked'; ?> style="display:none;" data-protxt="<?php echo esc_html__( 'Hide Regular Price', 'product-role-rules' ); ?>">
						</div>
					</div>
					<div class="mpcdp_row proler-row-title">
						<div class="col-md-12">
							<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
								<div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
							<?php endif; ?>
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Discount Tiers', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Offer more discount when user buys more, either by quantity or total spend. Examples: offer 30% off of product when user buys more than $2,000 or offer $15 off of product when user buys more than 10 items.', 'product-role-rules' ); ?>
							</div>
						</div>
					</div>
					<div class="mpcdp_row discount-ranges-main">
						<div class="col-md-12">
							<div class="discount-range-wrap">
								<?php self::saved_discount_ranges( $rd['ranges'] ?? [] ); ?>
							</div>
							<div class="mpcdp_row discount-range-demo">
								<?php self::discount_range_row(); ?>
							</div>
							<div class="mpcdp_row">
								<div class="col-md-12">
									<div class="mpc-opt-sc-btn add-new-disrange"><?php echo esc_html__( 'Add Tier', 'product-role-rules' ); ?></div>
								</div>
							</div>
						</div>
					</div>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
								<div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
							<?php endif; ?>
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Discount Tiers View', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
								<?php echo esc_html__( 'Choose how to display discount tiers.', 'product-role-rules' ); ?>
							</div>
						</div>
						<div class="col-md-6">
							<select name="additional_discount_display" class="<?php echo esc_attr( $pro_class ); ?>" data-protxt="<?php echo esc_html__( 'Discount Options', 'product-role-rules' ); ?>">
								<?php
									$ads = array(
										// 'table_max' => __( 'Table - show both min and max range', 'product-role-rules' ),
										'table' => __( 'Table', 'product-role-rules' ),
										// 'tag_max'   => __( 'List - show both min and max', 'product-role-rules' ),
										'list'   => __( 'List', 'product-role-rules' ),
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
				</div>
				<div class="mpcdp_settings_section">
					<div class="mpcdp_settings_section_title"><?php echo esc_html__( 'Apply Rule If...', 'product-role-rules' ); ?></div>
					<?php if ( 'option_page' === $proler__['which_page'] ) : ?>
					<div class="mpcdp_row">
						<div class="col-md-6">
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Category', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt">
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
							<div class="settings-desc-txt">
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
						$now = current_time( 'mysql' );
						$now_dt = new \DateTime( $now );
						$now = $now_dt->format( 'Y-m-d h:i A' );

						$date_from = $rd['schedule']['start'] ?? '';
						$date_to   = $rd['schedule']['end'] ?? '';
					?>
					<div class="mpcdp_row proler-row-title">
						<div class="col-md-12">
							<?php if ( 'activated' !== $proler__['prostate'] ) : ?>
								<div class="mpcdp_settings_option_ribbon mpcdp_settings_option_ribbon_new"><?php echo esc_html__( 'PRO', 'product-role-rules' ); ?></div>
							<?php endif; ?>
							<div class="mpcdp_option_label"><?php echo esc_html__( 'Schedule', 'product-role-rules' ); ?></div>
							<div class="settings-desc-txt proler-time-widget">
								<?php echo sprintf(
									// translators: %1$s is the current time.
									__( 'Set the time frame when this rule will be active. Current time: <span class="proler-server-time">%1$s</span>', 'product-role-rules' ),
									esc_html( $now )
								); ?>
								<?php do_action( 'proler_schedule_info', $date_from, $date_to ); ?>
							</div>
						</div>
					</div>
					<div class="mpcdp_row proler-schedule">
						<div class="col-md-6">
							<?php
								printf(
									'<input type="datetime-local" name="schedule_start" value="%s" placeholder="%s" class="%s" data-protxt="%s">',
									self::get_server_time( $date_from ),
									esc_html__( 'Starting Date and Time', 'product-role-rules' ),
									esc_attr( $pro_class ),
									esc_html__( 'Schedule Start', 'product-role-rules' )
								);
							?>
						</div>
						<div class="col-md-6">
							<?php
								printf(
									'<input type="datetime-local" name="schedule_end" value="%s" placeholder="%s" class="%s" data-protxt="%s">',
									self::get_server_time( $date_to ),
									esc_html__( 'Ending Date and Time', 'product-role-rules' ),
									esc_attr( $pro_class ),
									esc_html__( 'Schedule End', 'product-role-rules' )
								);
							?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
        public static function saved_discount_ranges( $data ){
            if ( !isset( $data ) || empty( $data ) ) {
                return;
            }

            foreach ( $data as $item ) {
                self::discount_range_row( $item );
            }
        }
        public static function discount_range_row( $data = array() ) {
			global $proler__;

			$pro_class = isset( $proler__['has_pro'] ) && ! $proler__['has_pro'] ? 'wfl-nopro' : '';
			$symbol    = get_woocommerce_currency_symbol();

			$type     = isset( $data['discount_type'] ) ? $data['discount_type'] : 'amount_percent';
			$min      = isset( $data['min'] ) ? $data['min'] : '';
			$max      = isset( $data['max'] ) ? $data['max'] : '';
			$discount = isset( $data['discount'] ) ? $data['discount'] : '';
			?>
			<div class="mpcdp_row disrange-item">
				<select name="discount_type">
					<option value="amount_percent" <?php echo 'amount_percent' === $type ? esc_attr( 'selected' ) : ''; ?>>
						(%) <?php echo esc_html__( 'Discount on Total', 'product-role-rules' ); ?>
					</option>
					<option value="amount_fixed" <?php echo 'amount_fixed' === $type ? esc_attr( 'selected' ) : ''; ?>>
						(<?php echo esc_html( $symbol ); ?>) <?php echo esc_html__( 'Discount on Total', 'product-role-rules' ); ?>
					</option>
					<option value="quantity_percent" <?php echo 'quantity_percent' === $type ? esc_attr( 'selected' ) : ''; ?>>
						(%) <?php echo esc_html__( 'Discount on Quantity', 'product-role-rules' ); ?>
					</option>
					<option value="quantity_fixed" <?php echo 'quantity_fixed' === $type ? esc_attr( 'selected' ) : ''; ?>>
						(<?php echo esc_html( $symbol ); ?>) <?php echo esc_html__( 'Discount on Quantity', 'product-role-rules' ); ?>
					</option>
				</select>
				<input type="text" name="min_value" <?php echo esc_attr( $pro_class ); ?>" placeholder="<?php echo esc_html__( 'Min', 'product-role-rules' ); ?>" value="<?php echo esc_attr( $min ); ?>" data-protxt="<?php echo esc_html__( 'Min Value', 'product-role-rules' ); ?>">
				<input type="text" name="max_value" <?php echo esc_attr( $pro_class ); ?>" placeholder="<?php echo esc_html__( 'Max', 'product-role-rules' ); ?>" value="<?php echo esc_attr( $max ); ?>" data-protxt="<?php echo esc_html__( 'Max Value', 'product-role-rules' ); ?>">
				<input type="text" name="discount_value" <?php echo esc_attr( $pro_class ); ?>" placeholder="<?php echo esc_html__( 'Discount', 'product-role-rules' ); ?>" value="<?php echo esc_attr( $discount ); ?>" data-protxt="<?php echo esc_html__( 'Dynamic Discount', 'product-role-rules' ); ?>">
				<span class="dashicons dashicons-trash delete-disrange"></span>
			</div>
			<?php
		}
        public static function get_server_time( $date ){
			if( empty( $date ) ) return '';

			$datetime = new \DateTime( $date, new \DateTimeZone( 'UTC' ) );
			$datetime->setTimezone( wp_timezone() );
			return $datetime->format( 'Y-m-d H:i:s' );
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
