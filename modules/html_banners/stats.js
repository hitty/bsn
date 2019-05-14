
jQuery(document).ready(function(){
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    jQuery('#ajax-search-results .dashed-link-blue').each(function(){
        jQuery(this).popupWindow(
        {
            onInit: function(){
                var _form = jQuery(".form-default");
                jQuery( "#date-start, #date-end", _form ).datepicker({
                    defaultDate: "0",
                    hideIfNoPrevNext: true,
                    numberOfMonths: 2,
                    dateFormat: 'dd.mm.yy'
                }); 
                
                var _button = jQuery( 'input[type=submit]', _form );
               
                jQuery('#date-start, #date-end',_form).change( function(){
                    show = 'yes'; 
                    jQuery( '#date-start, #date-end', _form ).each(function(index){
                        if( jQuery(this).val()== '') {
                            _button.hide();
                            return false;
                        } else _button.show();
                    });
                    
                }); 
                _button.on( 'click', function(){ 
                    getPendingContent(
                        '.results-wrapper.period .results',
                        jQuery(this).data('url'),
                        {
                            date_start : jQuery('#date-start').val(),
                            date_end   : jQuery('#date-end').val()
                        },
                        false, 
                        false, 
                        false,
                        false,
                        function(){
                            
                        }
                    );
                    return false;  
                    
                    
                      
                })
            }
            
        }) 
    })
        
    /*
       
    */
    
})