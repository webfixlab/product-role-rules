<?php
/**
 * Role based pricing admin settings class.
 *
 * @package    WordPress
 * @subpackage Role Based Pricing for WooCommerce
 * @since      5.0
 */

if ( ! class_exists( 'Proler_Admin_Settings_Helper' ) ) {

	/**
	 * Role based settings admin class
	 */
	class Proler_Admin_Settings_Helper {
        
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

        public static function pro_info_msg( $page ){
			global $proler__;
			$plugin = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $proler__['url']['pro'] ),
				__( 'pro plugin', 'product-role-rules' )
			);
			?>
			<div class="proler-pro-info-row">
				<span class="dashicons dashicons-info-outline"></span>
				<?php
					if( 'new-role' === $page ){
						echo sprintf(
							// translators: %s: is pro plugin name with url.
							__( 'Please note: you can add custom roles but to delete those you need the %s.', 'product-role-rules' ),
							wp_kses_post( $plugin )
						);
					}else{
						echo sprintf(
							// translators: %s: is pro plugin name with url.
							__( 'Please note: you can save pro field values but to use those you need the %s.', 'product-role-rules' ),
							wp_kses_post( $plugin )
						);
					}
				?>
			</div>
			<?php if( 'new-role' === $page ) : ?>
				<div class="proler-pro-info-row">
					<?php echo esc_html__( 'Please note: Role name must start with a letter and allows only letters, numbers, spaces or underscores!', 'product-role-rules' ); ?>
				</div>
			<?php
			endif;
		}
		private function log( $data ) {
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
