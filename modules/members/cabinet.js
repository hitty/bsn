jQuery(document).ready(function(){
    
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    
    //управление статусами объекта (промо-премиум-обычный)
     jQuery(document).on('click', ".extension", function(event, value){
        var _this = jQuery(this);
        var _url  = _this.attr('data-link');
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: _url,
            data: {ajax: true},
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                        alert('Ваш объект продлен на месяц');
                        _this.siblings('.object-date').children('i').text( msg.date_end_formatted );
                    }
                }
            },
            error: function(){
               
            },
            complete: function(){
            }
        });
        return false;
    });  
    
    //кнопки перемещения в архив/удаления/публикации
    jQuery(document).on('click','.archive, .delete, .publish', function(){
        var _this = jQuery(this);
        _class = _this.attr('class');
        _link = _this.data('link');
        getPending(_link, {ajax:true});
        if ( !_this.hasClass('publish') ) _this.closest('.item').fadeOut(200, function(){ _this.closest('.item').remove()})
        
        return false;
    }); 
    
    if( jQuery('#popup-element').length > 0) {
        jQuery('#popup-element').popupWindow(
            {
                popupCallback:function(data){
                    var _cost = parseInt(data.cost);
                    var _balance_el = jQuery('.menu-wrapper .content .balance span');
                    var _balance = parseInt( _balance_el.text().replace(' ', '') );
                    _balance_el.text(_balance + ' Р')
                    setTimeout(function(){
                        window.location = window.location.pathname
                    }, 2500)                    
                }
            }
        ) 
                    
       jQuery('#popup-element').click();
    }    

  
    jQuery('.application-fixed.ask-question #application-button').on('click', function(){
        var _recipient_id = parseInt( jQuery('#support-recipient-id').attr('value') );
        if( ! (_recipient_id > 0 ) ) {
            getPending(
                '/members/messages/support/', 
                false, 
                false, 
                function(data){ 
                    if(data.ok){
                        jQuery('#support-parent-id').attr('value', data.support_parent_id);
                        jQuery('#support-recipient-id').attr('value', data.support_recipient_id);
                    }
                } 
            );
        }
    })    
    
});