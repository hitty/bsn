
jQuery(document).ready(function(){
    jQuery('.objects-count span').on('click', function(){
        jQuery( '.filter span[data-tab-ref="'+ jQuery(this).attr( 'data-tab-ref' )+ '"]').click();
    })   
    jQuery( '.auth-popup').each( function(){
        var _this = jQuery(this);
        _this.popupWindow({
            popupCallback: function(data){
                window.location.href = _this.data('link');
            }    
        })
    })
})