
(function($){
    /**
     * uses admin localized variable | proler
     */
    $(window).on( 'scroll', function(){
        if( $(window).scrollTop() > 60 ){
            $( '.proler-admin-wrap' ).addClass( 'proler-stick-heading' );
        }else{
            if( $( '.proler-admin-wrap' ).hasClass( 'proler-stick-heading' ) ){
                $( '.proler-admin-wrap' ).removeClass( 'proler-stick-heading' );
            }
        }
    });

    $(document).ready(function(){

        $( 'body' ).on( 'click', '.wfl-nopro', function(e){

            var txt = $(this).attr( 'data-protxt' );

            $( '.wfl-popup .focus span' ).text( txt );
            $( '.wfl-popup' ).show();
            e.preventDefault(); 
        });

        $( 'body' ).on( 'input', '.wfl-nopro', function(e){

            var txt = $(this).attr( 'data-protxt' );

            $( '.wfl-popup .focus span' ).text( txt );
            $( '.wfl-popup' ).show();
            e.preventDefault();
            $(this).val( '' );
        });

        $( 'body' ).on( 'click', '.wfl-popup .close', function(){
            $( '.wfl-popup' ).hide();
        });




        $( 'body' ).on( 'click', 'input[name="proler_stype"]', function(){
            var v = $(this).val();

            $( '.prs-notice' ).remove();

            if( v == 'default' ){
                $( '.role-settings-content' ).hide( 'slow' );
            }else if( v == 'proler-based' ){
                $( '.role-settings-content' ).show( 'slow' );
            }else{
                $( '.role-settings-content' ).hide( 'slow' );
            }

        });



        // convert json to string - for using as input field value
        function json_populate_input( field, data ){
            var s = JSON.stringify( data );
            s = s.replaceAll( '\"', '\"' );
            $( 'input[name="' + field + '"]' ).val( s );
        }
        function validate_input( val ){
            val = val.replaceAll( '\'', ':*snglqt*:' ).replaceAll( '\"', ':*dblqt*:' );
            return val;
        }

        function get_role_settings( row ){
            var data = {};
                    
            if( row.find( 'input[name="hide_price"]' ).is( ':checked' ) ){
                data[ 'hide_price' ] = true;
            }

            if( row.find( 'input[name="hide_txt"]' ).val().length > 0 ){
                data[ 'hide_txt'] = validate_input( row.find( 'input[name="hide_txt"]' ).val() );
            }

            var discount = '';
            if( row.find( 'input[name="discount"]' ).val().length > 0 ){
                discount = validate_input( row.find( 'input[name="discount"]' ).val() );
            }
            data['discount'] = discount;

            var discount_type = '';
            if( row.find( 'select[name="discount_type"]' ).val().length > 0 ){
                discount_type = validate_input( row.find( 'select[name="discount_type"]' ).val() );
            }
            data['discount_type'] = discount_type;

            var min_qty = '';
            if( row.find( 'input[name="min_qty"]' ).val().length > 0 && proler.has_pro == true ){
                min_qty = validate_input( row.find( 'input[name="min_qty"]' ).val() );
            }
            data['min_qty'] = min_qty;

            var max_qty = '';
            if( row.find( 'input[name="max_qty"]' ).val().length > 0 && proler.has_pro == true ){
                max_qty = validate_input( row.find( 'input[name="max_qty"]' ).val() );
            }
            data['max_qty'] = max_qty;

            if( row.find( 'input[name="pr_enable"]' ).is( ':checked' ) ){
                data[ 'pr_enable' ] = true;
            }

            return data;
        }
        function get_settings(){
            var data = {};
            
            if( typeof $( 'input[name="proler_stype"]:checked' ).val() != 'undefined' ){
                data['proler_stype'] = $( 'input[name="proler_stype"]:checked' ).val();
            }

            data['roles'] = {};

            // per role data
            $( '.pr-settings' ).find( '.pr-item' ).each(function(){

                var role = $(this).find( '.proler-roles' ).val();   
                
                if( role.length > 0 ){
                    data['roles'][role] = get_role_settings( $(this) );
                }

            });
            
            return data;
        }
        function set_input_val( data ){
            if( typeof data == 'object' && !$.isEmptyObject( data ) ){
                json_populate_input( 'proler_data', data );                
            }else{
                $( 'input[name="proler_data"]' ).val( '' );
            }
        }

        

        function toggle_button( btn ){
            var wrap = btn.closest( '.hurkanSwitch-switch-box' );
    
            wrap.find( '.hurkanSwitch-switch-item' ).each(
                function(){
                    if ( $( this ).hasClass( 'active' ) ) {
                        $( this ).removeClass( 'active' );
                    } else {
                        $( this ).addClass( 'active' );
                    }
                }
            );
    
            btn.closest( '.mpcdp_settings_option_field' ).find( 'input[type="checkbox"]' ).trigger( 'click' );
        }
        $( '.pr-settings' ).on( 'click', '.hurkanSwitch-switch-item',  function(e){
            toggle_button( $( this ) );
        });
        $( '.pr-settings' ).on( 'click', '.mpc-opt-sc-btn.edit', function(){
            $(this).closest( '.pr-item' ).find( '.mpcdp_settings_option' ).each(function(){
                if( ! $(this).hasClass( 'visible' ) ){
                    $(this).toggle( 'slow' );
                }
            });
        });
        $( '.pr-settings' ).on( 'click', '.mpc-opt-sc-btn.delete', function(){
            $(this).closest( '.pr-item' ).hide( 'slow', function(){
                $(this).closest( '.pr-item' ).remove();
            });
        });
        $( '.mpc-opt-sc-btn.add-new' ).on( 'click', function(){
            $( '.pr-settings' ).append( '<div class="mpcdp_settings_toggle pr-item">' + $( '.demo-item' ).html() + '</div>' );
        });



        // init - frontend on load.
        if( typeof $( '.pr-settings' ).find( '.pr-item' ) == 'undefined' || $( '.pr-settings' ).find( '.pr-item' ).length == 0 ){
            $( '.mpc-opt-sc-btn.add-new' ).trigger( 'click' );
        }
        // init - admin product edit page.
        if( typeof $( 'input[name="proler_stype"]:checked' ).val() != 'undefined' ){
            var v = $( 'input[name="proler_stype"]:checked' ).val();

            if( v != 'proler-based' && $( '.role-settings-content' ).is( ':visible' ) ){
                $( '.role-settings-content' ).hide();
            }
        }



        // single product update/save button clicked event
        $( 'input[type="submit"],button.mpcdp_submit_button' ).on( 'click', function(e){
            // e.preventDefault();

            var data = get_settings();
            // console.log( data );
            
            set_input_val( data );
        });
    });
})(jQuery);