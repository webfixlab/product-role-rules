/**
 * uses admin localized variable | proler
 */

;(function($, window, document) {
    class roleBasedPricing{
		constructor(){
            $(document).ready(() => {
                this.init();
            });
        }
		init(){
			$('body').on('click input', '.wfl-nopro', function(e) {
                e.preventDefault();
                if ($(this).is('select')) {
                    $(this).blur();
                }

                const txt = $(this).attr('data-protxt');
                $('.proler-popup-wrap span.marker').text(txt);
                $('.proler-popup-wrap').show();
            });
			$('body').on('click', '.popup-close', function(){
				$('.proler-popup-wrap').hide();
			});
		}
	}
	new roleBasedPricing();
})(jQuery, window, document);











(function ($) {
	$( document ).ready( function () {
		$( 'body' ).on( 'click', 'input[name="proler_stype"]', function () {
			var v = $( this ).val();

			if ( v == 'default' ) {
				$( '.role-settings-content' ).hide( 'slow' );
			} else if ( v == 'proler-based' ) {
				$( '.role-settings-content' ).show( 'slow' );
			} else {
				$( '.role-settings-content' ).hide( 'slow' );
			}
		});

		// convert json to string - for using as input field value
		function json_populate_input( field, data ){
			var s = JSON.stringify( data );
			s     = s.replaceAll( '\"', '\"' );
			$( 'input[name="' + field + '"]' ).val( s );
		}
		function validate_input( val ){
			val = val.replaceAll( '\'', ':*snglqt*:' ).replaceAll( '\"', ':*dblqt*:' );
			return val;
		}
		function get_range_data( row ){
			var data = [];
			row.find( '.discount-range-wrap .disrange-item' ).each(function(){
				var row = $(this);
				var range  = {};
				if ( row.find( 'select[name="discount_type"]' ).val().length > 0 ) {
					range['discount_type'] = validate_input( row.find( 'select[name="discount_type"]' ).val() );
				}
				if ( row.find( 'input[name="min_value"]' ).val().length > 0 ) {
					range['min'] = validate_input( row.find( 'input[name="min_value"]' ).val() );
				}
				if ( row.find( 'input[name="max_value"]' ).val().length > 0 ) {
					range['max'] = validate_input( row.find( 'input[name="max_value"]' ).val() );
				}
				if ( row.find( 'input[name="discount_value"]' ).val().length > 0 ) {
					range['discount'] = validate_input( row.find( 'input[name="discount_value"]' ).val() );
				}
				if( range['min'] || range['max'] ){
					data.push( range );
				}
			});

			return data;
		}
		function get_role_settings( row ){
			var data = {};

			if ( row.find( 'input[name="discount_text"]' ).is( ':checked' ) ) {
				data[ 'discount_text' ] = true;
			}

			if ( row.find( 'input[name="hide_price"]' ).is( ':checked' ) ) {
				data[ 'hide_price' ] = true;
			}

			if ( row.find( 'textarea[name="hide_txt"]' ).val().length > 0 ) {
				data[ 'hide_txt'] = encodeURIComponent( row.find( 'textarea[name="hide_txt"]' ).val() );
			}

			var discount = '';
			if ( row.find( 'input[name="discount"]' ).val().length > 0 ) {
				discount = validate_input( row.find( 'input[name="discount"]' ).val() );
			}
			data['discount'] = discount;

			var discount_type = '';
			if ( row.find( 'select[name="discount_type"]' ).val().length > 0 ) {
				discount_type = validate_input( row.find( 'select[name="discount_type"]' ).val() );
			}
			data['discount_type'] = discount_type;

			var min_qty = '';
			if ( row.find( 'input[name="min_qty"]' ).val().length > 0 && proler.has_pro == true ) {
				min_qty = validate_input( row.find( 'input[name="min_qty"]' ).val() );
			}
			data['min_qty'] = min_qty;

			var max_qty = '';
			if ( row.find( 'input[name="max_qty"]' ).val().length > 0 && proler.has_pro == true ) {
				max_qty = validate_input( row.find( 'input[name="max_qty"]' ).val() );
			}
			data['max_qty'] = max_qty;

			if ( row.find( 'input[name="pr_enable"]' ).is( ':checked' ) ) {
				data[ 'pr_enable' ] = true;
			}

			var category = '';
			if ( row.find( 'select[name="category"]' ).val() && row.find( 'select[name="category"]' ).val().length > 0 ) {
				category = validate_input( row.find( 'select[name="category"]' ).val() );
			}
			data['category'] = category;

			var product_type = '';
			if ( row.find( 'select[name="product_type"]' ).val() && row.find( 'select[name="product_type"]' ).val().length > 0 ) {
				product_type = validate_input( row.find( 'select[name="product_type"]' ).val() );
			}
			data['product_type'] = product_type;
			
			const start = row.find('input[name="schedule_start"]').val();
			const end   = row.find('input[name="schedule_end"]').val();
			if(start.length > 0 || end.length > 0){
				data['schedule'] = {};
			}
			if(start.length > 0){
				data['schedule']['start'] = validate_input(start);
			}
			if(end.length > 0){
				data['schedule']['end'] = validate_input(end);
			}
			
			data['hide_regular_price'] = false;
			if ( row.find( 'input[name="hide_regular_price"]' ).is( ':checked' ) ) {
				data[ 'hide_regular_price' ] = true;
			}

			data['ranges'] = get_range_data( row );

			var additional_discount_display = '';
			if ( row.find( 'select[name="additional_discount_display"]' ).val().length > 0 ) {
				additional_discount_display = validate_input( row.find( 'select[name="additional_discount_display"]' ).val() );
			}
			data['additional_discount_display'] = additional_discount_display;

			return data;
		}
		function get_settings(){
			var data = {};

			if ( typeof $( 'input[name="proler_stype"]:checked' ).val() != 'undefined' ) {
				data['proler_stype'] = $( 'input[name="proler_stype"]:checked' ).val();
			}

			data['roles'] = {};

			// per role data
			$( '.pr-settings' ).find( '.pr-item' ).each( function () {
				var role = $( this ).find( '.proler-roles' ).val();
				if ( role.length > 0 ) {
					data['roles'][role] = get_role_settings( $( this ) );
				}
			});

			return data;
		}
		function set_input_val( data ){
			if ( typeof data == 'object' && ! $.isEmptyObject( data ) ) {
				json_populate_input( 'proler_data', data );
			} else {
				$( 'input[name="proler_data"]' ).val( '' );
			}
		}

		function show_diable_msg( btn ){
			const checkBox = btn.closest('.switch-box-wrap').find('input[type="checkbox"]');
			const status   = btn.closest('.col-md-6').find('.prdis-msg');
			console.log('checkbox', checkBox.length, 'status', status.length);
			
			if(!status || !checkBox) return;
			console.log('checkbox checked?', checkBox.is(':checked'), 'status hidden?', status.is(':hidden'));

			if(!checkBox.is(':checked') && status.is(':hidden')) status.show();
			else if(checkBox.is(':checked') && !status.is(':hidden')) status.hide();
		}
		function switchBoxHandler( btn ){
			var wrap = btn.closest( '.hurkanSwitch-switch-box' );

			wrap.find( '.hurkanSwitch-switch-item' ).each( function () {
				$(this).toggleClass('active');
			});

			// find closest wrapper to extract actualy checkbox field.
			const checkBox = btn.closest( '.proler-option-head' ).find( 'input[type="checkbox"]' );
			if(checkBox) checkBox.trigger('click');
			console.log( 'checkbox', checkBox );
			show_diable_msg( btn );
		}


		function checkRanges(){
			var allOK = true;
			$( '.pr-settings' ).find( '.pr-item' ).each( function () {
				var role = $( this ).find( '.proler-roles' ).val();
				if ( role.length > 0 ) {
					var row = $(this);
					row.find( '.disrange-item' ).each(function(){
						var min = $(this).find( 'input[name="min_value"]' ).val();
						var max = $(this).find( 'input[name="max_value"]' ).val();
						min = min ? parseFloat( min ) : '';
						max = max ? parseFloat( max ) : '';
						if( min && max && min > max ){
							allOK = false;
							$([document.documentElement, document.body]).animate({
								scrollTop: $( '.discount-ranges-main' ).offset().top - 100
							}, 2000);

							alert( 'Range error: Minimum value cannot be more than maximum.' );
						}
					});
				}
			});
			return allOK;
		}



		$( '.pr-settings' ).on( 'click', '.hurkanSwitch-switch-item', function (e) {
			switchBoxHandler( $( this ) );
		});

		$( '.pr-settings' ).on( 'click', '.proler-arrow img', function () {
			var item = $( this ).closest( '.pr-item' );

			if ( item.find( '.proler-option-content' ).is( ':visible' ) ) {
				$( this ).attr( 'src', proler.right_arrow );
			} else {
				$( this ).attr( 'src', proler.down_arrow );
			}

			item.find( '.proler-option-content' ).toggle( 'slow' );
		});

		$( '.pr-settings' ).on( 'click', '.proler-delete', function () {
			$( this ).closest( '.pr-item' ).hide( 'slow', function () {
				$( this ).closest( '.pr-item' ).remove();
			});
		});

		$( '.mpc-opt-sc-btn.add-new' ).on( 'click', function () {
			$( '.pr-settings' ).append( '<div class="mpcdp_settings_toggle pr-item">' + $( '.demo-item' ).html() + '</div>' );
		});

		// init - frontend on load.
		if ( typeof $( '.pr-settings' ).find( '.pr-item' ) == 'undefined' || $( '.pr-settings' ).find( '.pr-item' ).length == 0 ) {
			$( '.mpc-opt-sc-btn.add-new' ).trigger( 'click' );
		}

		$( 'body' ).on( 'click', '.add-new-disrange', function(){
			var wrap = $(this).closest( '.discount-ranges-main' );
			wrap.find( '.discount-range-wrap' ).append( $( '.discount-range-demo' ).html() );
		});

		// init - frontend on load.
		$( '.discount-ranges-main' ).each(function(){
			var discount_ranges = $(this).find( '.discount-range-wrap .mpcdp_row' );

			if ( discount_ranges.length === 0 ) {
				$(this).find( '.add-new-disrange' ).trigger( 'click' );
			}
		});

		$( 'body' ).on( 'click', '.delete-disrange', function(){
			$(this).closest( '.disrange-item' ).hide( 'slow', function(){
				$(this).remove();
			});
		});

		// init - admin product edit page.
		if ( typeof $( 'input[name="proler_stype"]:checked' ).val() != 'undefined' ) {
			var val = $( 'input[name="proler_stype"]:checked' ).val();

			if ( val != 'proler-based' && $( '.role-settings-content' ).is( ':visible' ) ) {
				$( '.role-settings-content' ).hide();
			}
		}

		// delete user role.
		$( '.proler-delete-role' ).on( 'click', function(e){
			if( ! confirm( proler.delete_role_msg ) ){
				e.preventDefault();
			}
		});

		// single product update/save button clicked event
		$( 'input[type="submit"],button.mpcdp_submit_button' ).on( 'click', function (e) {
			// e.preventDefault();
			if( checkRanges() === false ){
				e.preventDefault();
			}

			var data = get_settings();
			set_input_val( data );
		});

		var expanded = false;
		$( 'body' ).find( '.pr-item' ).each(function(){
			if( expanded === false && $(this).find( '.proler-option-content' ).is( ':hidden' ) ){
				$(this).find( '.proler-arrow img' ).trigger( 'click' );
				expanded = true;
			}
		});
	});
})( jQuery );