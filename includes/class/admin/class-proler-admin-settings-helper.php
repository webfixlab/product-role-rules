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
		public static function settings_saved_notice() {
			global $proler__;

			// check cached object if delete role notice exists.
			$cache = get_transient( 'proler_admin_cache' );
			if ( false !== $cache && isset( $cache['msg'] ) && ! empty( $cache['msg'] ) ) {
				self::update_notice( $cache['msg'], $cache['cls'] );

				delete_transient( 'proler_admin_cache' );
				return;
			}

			// add new role notice.
			if ( isset( $proler__['user_role_msg'] ) && ! empty( $proler__['user_role_msg'] ) ) {
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
		 * Display settings update notice
		 *
		 * @param string $msg  Notice message.
		 * @param string $icon Notice icon.
		 */
		public static function update_notice( $msg, $icon ) {
			?>
			<div class="proler-saved-settings">
				<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
				<?php echo wp_kses_post( $msg ); ?>
			</div>
			<?php
		}

		/**
		 * Display pro info message
		 *
		 * @param string $page_slug Page slug.
		 */
		public static function pro_info_msg( $page_slug ) {
			if ( 'new-role' !== $page_slug ) {
				return;
			}
			?>
			<div class="proler-pro-info-row">
				<span class="dashicons dashicons-info-outline"></span>
				<?php echo esc_html__( 'Please note: Role name must start with a letter and allows only letters, numbers, spaces or underscores!', 'product-role-rules' ); ?>
			</div>
			<?php
		}

		/**
		 * Display taxonomy terms from given data
		 *
		 * @param string $taxonomy Given taxonomy name.
		 * @param array  $data     All terms data.
		 * @param string $slug     Slug to navigate terms data.
		 */
		public static function display_terms( $taxonomy, $data, $slug ) {
			$args = array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			);

			$cats = get_terms( $args );
			if ( empty( $cats ) ) {
				return;
			}

			$values = isset( $data[ $slug ] ) ? $data[ $slug ] : array();
			$values = ! empty( $values ) && ! is_array( $values ) ? array( $values ) : array();
			$values = ! empty( $values ) && is_array( $values ) ? array_map(
				function ( $val ) {
					return (int) $val;
				},
				$values
			) : array();

			foreach ( $cats as $cat ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $cat->term_id ),
					! empty( $values ) && in_array( $cat->term_id, $values, true ) ? 'selected' : '',
					esc_attr( $cat->name ),
				);
			}
		}
	}
}
