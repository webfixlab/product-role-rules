/**
 * Frontend JavaScript
 *
 * @package    Wordpress
 * @subpackage Product Role Rules Premium
 * @since      3.0
 */

;(function($, window, document) {
    class roleBasedPricing{
        constructor(){
            $(document).ready(() => {
                // console.log('proler', proler);
                this.init();
            });
        }
        init(){
            const self = this;

            setTimeout(function(){
                self.loadMinicart();
            }, 1500);
        }
        
        loadMinicart(){
            $.ajax({
                method: "POST",
                url: proler.ajaxurl,
                data: {
                    'action': 'proler_minicart',
                },
                async: 'false',
                success: function( data ) {
                    // console.log('proler:minicart-loaded');
                    if ( data && data.fragments ) {
                        $.each( data.fragments, function( key, value ) {
                            $( key ).replaceWith( value );
                        });
                        $( document.body ).trigger( 'wc_fragments_refreshed' );
                    }
                },
                error: function() {
                    $( document.body ).trigger( 'wc_fragments_ajax_error' );
                }
            });
        }
    }

    new roleBasedPricing();
})(jQuery, window, document);
