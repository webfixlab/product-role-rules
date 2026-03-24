/**
 * Admin JavaScript
 *
 * @package    Wordpress
 * @subpackage Product Role Rules
 * @since      5.0
 */

(function ($, window, document) {
	class roleBasedPricing{
		constructor(){
			this.$tab         = ''; // product tab.
			this.$settings    = {}; // all role based settigns.
			this.$role        = ''; // current role.
			this.$roleSection = ''; // current role section wrapper.
			this.$fields      = { // all settings fields.
				pr_enable:                   'checkbox',
				hide_price :                 'checkbox',
				hide_txt :                   'textarea',
				discount:                    'text',
				discount_type:               'select',
				discount_text :              'checkbox',
				min_qty:                     'text',
				max_qty:                     'text',
				category:                    'selectbox',
				hide_regular_price:          'checkbox',
				product_type:                'select',
				ranges:                      '',
				additional_discount_display: 'select',
				schedule:                    '',
			};
			$( document ).ready(
				() =>
				{
					this.eventTriggers();
					this.init();
				}
			);
		}
		init(){
			// initialize a blank discount range.
			$( '.discount-ranges-main' ).each(
				( _, el ) =>
				{
					const discountRanges = $( el ).find( '.discount-range-wrap .mpcdp_row' );
					if ( ! discountRanges || 0 === discountRanges.length ) {
						$( el ).find( '.add-new-disrange' ).trigger( 'click' );
					}
				}
			);

			// initialize product tab section content.
			const productOptionTab = $( 'input[name="proler_stype"]' );
			if ( productOptionTab && 'undefined' !== productOptionTab ) {
				const tab = $( 'input[name="proler_stype"]:checked' ).val();
				$( '.role-settings-content' ).toggle( 'proler-based' === tab );
			}

			// initialize a blank role options section.
			const roleOptions = $( '.pr-settings' ).find( '.pr-item' );
			if ( ! roleOptions || 0 === roleOptions.length ) {
				$( '.mpc-opt-sc-btn.add-new' ).trigger( 'click' );
			}

			// expand first role settings section.
			$( $( '.pr-settings' ).find( '.pr-item' )[0] ).find( '.proler-arrow img' ).trigger( 'click' );
		}
		eventTriggers(){
			// content.
			$( document ).on(
				'click',
				'input[name="proler_stype"]',
				e => this.navigateTabContent( $( e.currentTarget ).val() )
			);
			$( '.pr-settings' ).on(
				'click',
				'.proler-arrow img',
				( e ) => this.collapseContent( $( e.currentTarget ) )
			);
			$( '.mpc-opt-sc-btn.add-new' ).on(
				'click',
				() => this.addNewRoleSection()
			);
			$( '.pr-settings' ).on(
				'click',
				'.proler-delete',
				( e ) => this.removeRoleSection( $( e.currentTarget ) )
			);
			$( 'body' ).on(
				'click',
				'.add-new-disrange',
				( e ) => $( e.currentTarget ).closest( '.discount-ranges-main' ).find( '.discount-range-wrap' ).append( $( '.discount-range-demo' ).html() )
			);

			// fields.
			$( '.pr-settings' ).on(
				'click',
				'.switch-point',
				e => this.switchBoxHandler( $( e.currentTarget ) )
			);

			// form submit.
			$( 'input[type="submit"],button.mpcdp_submit_button' ).on( // single product update/save button clicked event.
				'click',
				( e ) =>
				{
					if ( ! this.isFormSubmitReady() ) {
						e.preventDefault();
					}
				}
			);

			// others.
			$( 'body' ).on(
				'click input',
				'.wfl-nopro',
				( e ) =>
				{
					e.preventDefault();
					this.popupHandler( $( e.currentTarget ) )
				}
			);
			$( 'body' ).on(
				'click',
				'.popup-close',
				e => $( '.proler-popup-wrap' ).hide()
			);
			$( '.proler-delete-role' ).on( // delete user role.
				'click',
				( e ) =>
				{
					e.preventDefault(); // delete.
					if ( proler.has_pro && ! confirm( proler.delete_role_msg ) ) {
						e.preventDefault();
					}
				}
			);
		}
		navigateTabContent(tab){
			$( '.role-settings-content' ).toggle( 'proler-based' === tab );
		}
		collapseContent( item ){
			const optionContent = item.closest( '.pr-item' ).find( '.proler-option-content' );
			item.attr( 'src', optionContent.is( ':visible' ) ? proler.right_arrow : proler.down_arrow );
			optionContent.toggle( 'slow' );
		}
		addNewRoleSection(){
			const html = $( '.demo-item' ).html();
			$( '.pr-settings' ).append( `<div class="mpcdp_settings_toggle pr-item">${html}</div>` ); // phpcs:ignore.
		}
		removeRoleSection( item ){
			const sectionWrap = item.closest( '.pr-item' );
			sectionWrap.hide(
				'slow',
				() => sectionWrap.remove()
			);
		}

		isFormSubmitReady(){
			this.$settings = {}; // empty settings state.

			if ( 'undefined' !== typeof $( 'input[name="proler_stype"]' ) ) {
				this.$tab = $( 'input[name="proler_stype"]:checked' ).val();
			}

			$( '.pr-settings .pr-item' ).each(
				( _, el ) => this.setRoleSettings( $( el ) )
			);

			this.setSettingsFieldValue();
			return ! $.isEmptyObject( this.$settings );
		}
		setRoleSettings( roleSection ){
			this.$role = roleSection.find( 'select.proler-roles option:selected' ).val();
			if ( ! this.$role || 0 === this.$role.length ) {
				return;
			}

			this.$roleSection = roleSection;

			Object.keys( this.$fields ).forEach(
				key => this.setFieldValue( key )
			);
		}
		setFieldValue( key ){
			if ( ! this.$settings[ this.$role ] ) {
				this.$settings[ this.$role ] = {};
			}

			if ( 'ranges' === key ) {
				this.getDiscountTiers();
				return;
			} else if ( 'schedule' === key ) {
				this.getSchedule();
				return;
			}

			const type = this.$fields[ key ];

			let value = null;
			if ( 'selectbox' === type ) {
				value = this.$roleSection.find( `select[name = "${key}[]"]` ).val();
			} else if ( 'checkbox' === type ) {
				value = this.$roleSection.find( `input[name = "${key}"]` ).is( ':checked' );
			} else if ( 'select' === type ) {
				value = this.$roleSection.find( `select[name = "${key}"] option:selected` ).val();
			} else if ( 'text' === type ) {
				value = this.$roleSection.find( `input[name = "${key}"]` ).val();
			} else if ( 'textarea' === type ) {
				value = this.$roleSection.find( `textarea[name = "${key}"]` ).val();
			}

			if ( 'undefined' !== typeof value ) {
				this.$settings[ this.$role ][ key ] = value;
			}
		}
		getSchedule(){
			if ( ! this.$settings[ this.$role ][ 'schedule' ] ) {
				this.$settings[ this.$role ][ 'schedule' ] = {};
			}

			const start = this.$roleSection.find( 'input[name="schedule_start"]' ).val();
			const end   = this.$roleSection.find( 'input[name="schedule_end"]' ).val();

			if ( 'undefined' !== typeof start ) {
				this.$settings[ this.$role ]['schedule_start'] = start;
			}
			if ( 'undefined' !== typeof end ) {
				this.$settings[ this.$role ]['schedule_end'] = end;
			}
		}
		getDiscountTiers(){
			this.$roleSection.find( '.discount-range-wrap .disrange-item' ).each(
				( _, el ) => this.addTierItem( $( el ) )
			);
		}
		addTierItem( el ){
			const min = el.find( 'input[name="min_value"]' ).val();
			const max = el.find( 'input[name="max_value"]' ).val();
			if ( ( ! min || 0 === min.length ) && ( ! max || 0 === max.length ) ) {
				return;
			}

			if ( ! this.$settings[ this.$role ]['ranges'] ) {
				this.$settings[ this.$role ]['ranges'] = [];
			}
			this.$settings[ this.$role ]['ranges'].push(
				{
					discount_type: el.find( 'select[name="discount_type"] option:selected' ).val(),
					min: min,
					max: max,
					discount: el.find( 'input[name="discount_value"]' ).val()
				}
			);
		}
		setSettingsFieldValue(){
			if ( $.isEmptyObject( this.$settings ) ) {
				return true;
			}
			$( 'input[name="proler_data"]' ).val(
				JSON.stringify(
					{
						roles: this.$settings
					}
				)
			);
		}

		switchBoxHandler( btn ){
			btn.closest( '.switch-box' ).find( '.switch-point' ).each(
				( _, el ) => $( el ).toggleClass( 'active' )
			);

			const checkBox = btn.closest( '.switch-box-wrap' ).find( 'input[type="checkbox"]' );
			if ( checkBox && checkBox.length > 0 ) {
				checkBox.trigger( 'click' );
				btn.closest( '.col-md-6' ).find( '.prdis-msg' ).toggle( ! checkBox.is( ':checked' ) );
			}
		}

		popupHandler( item ){
			if ( item.is( 'select' ) ) {
				item.blur();
			}

			$( '.proler-popup-wrap span.marker' ).text( item.attr( 'data-protxt' ) );
			$( '.proler-popup-wrap' ).show();
		}
	}
	new roleBasedPricing();
})( jQuery, window, document );
