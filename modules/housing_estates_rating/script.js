jQuery(document).ready(function(){
    
    //заполнение перс.информации
    if( jQuery( '.expert-personal-wrapper').length > 0){
        jQuery( '.expert-personal-wrapper' ).formSubmit(
            {
                onFormSuccess:function(data){
                    jQuery( '.expert-personal-wrapper' ).remove();
                    
                }
            }
        ) 
    }
    
    jQuery('.estate-list .item').on('click', function(){
        if( !jQuery(this).hasClass('can-vote') ) return false;
    })
    
    
    //голосование из выдачи
    jQuery('.vote-wrap .vote').each(function(){ 
        jQuery(this).popupWindow(
            {
                onInit: function(){
                    voting( jQuery('#background-shadow-inner'), 'list' )
                },
                popupCallback:function(data){
                    var _wrap = jQuery('.estate-list .item[data-id=' + data.id + ']');
                    _wrap.removeClass('can-vote');
                    jQuery('.vote', _wrap).remove();
                    jQuery('.photo', _wrap).append('<span class="rating br3">' + data.rating + '</span>');
                    if( data.resume_popup && jQuery( '.send-resume' ).length > 0 && jQuery( '.rating-resume-wrapper' ).length == 0 ) {
                        jQuery('#background-shadow').remove();
                        jQuery( '.send-resume' ).click();
                    }                    
                }
            }
        ) 
    });
    
    //голосование из карточки
    jQuery( '.application-fixed form' ).formSubmit(
        {
            onInit: function(){
                voting( jQuery('.application-fixed form'), 'item' )
            },
            onFormSuccess:function(data){
                setTimeout(function(){
                    jQuery( '.application-fixed.housing-estate-voting' ).remove();
                   
                    if( data.resume_popup && jQuery( '.send-resume' ).length > 0 && jQuery( '.rating-resume-wrapper' ).length == 0 ) {
                        jQuery( '.send-resume' ).click();
                    }

                }, 2700);
                
            }
        }
    ) 
    
    //popup резюме
    if( jQuery('.send-resume').length > 0) {
        
        jQuery('.send-resume').popupWindow(
            {
                onInit:function(data){
                    
                    jQuery('#file_upload').uploadifive(
                        {
                            'queueSizeLimit'  : 1
                        }
                    );
                    
                },
                popupCallback:function(data){
                     jQuery('.send-resume').remove();
                }
            }
        ) 
    }   
    
    //пересчет рейтинга
    if( jQuery('.estate-list .item').length > 0 ) {
        if( jQuery('.estate-list .item.can-vote').length == 0 && jQuery( '.send-resume' ).length > 0 && jQuery( '.rating-resume-wrapper' ).length == 0 ) {
            jQuery( '.send-resume' ).click();
        } 
        _popup_interval = window.setInterval(function(){
            return false;
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: true,
                url: '/zhiloy_kompleks/votes/', data: {ajax: true},
                success: function(msg){
                    if(msg.ok) {
                        var _wrap = jQuery( '.estate-list');
                        for( i=0; i < msg.list.length; i++ ){
                            var _item = jQuery( '.item[data-id=' + msg.list[i].id + ']', _wrap);
                            if( _item.hasClass('can-vote') ) {
                                _item.removeClass( 'can-vote' ).find('.photo').append('<span class="rating br3">' + msg.list[i].expert_rating + '</span>');
                            }
                        }
                  
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                }
            });
        }, 2000); 
    
    }
});

function voting(_wrap){
    var _button = jQuery('.button', _wrap);
    var _total_items = jQuery('.row', _wrap).length;
    _params = [];
    //обработка подтягивания звездочек левее
    jQuery('.stars span', _wrap).on('mouseover',function(){
        var _parent = jQuery(this).closest('.row');
        if( _parent.hasClass('voted') ) return false;
        _current_rating = jQuery(this).index() + 1;
        jQuery('.stars span', _parent).each(function(){
            if(jQuery(this).index()<_current_rating){
                jQuery(this).addClass('hovered');
            }
        });
    });
    //если не нажали, при уходе звездочки левее сбрасываются
    jQuery('.stars span', _wrap).on('mouseleave',function(){
        var _parent = jQuery(this).closest('.row');
        if( _parent.hasClass('voted') ) return false;
        _current_rating = jQuery(this).index() + 1;
        jQuery('.stars span', _parent).each(function(){
            if( jQuery(this).index()<_current_rating ){
                jQuery(this).removeClass('hovered');
            }
        });
    });    
    
    //нажимаем: включаем все звездочки левее
    jQuery('.stars span', _wrap).on('click',function(){
        var _parent = jQuery(this).closest('.row');
        if( _parent.hasClass('voted') ) return false;
        jQuery(this).parent().addClass('active');
        jQuery('.stars span', _parent).removeClass('active').removeClass('hovered');
        _current_rating = jQuery(this).index() + 1;
        jQuery('.stars span', _parent).each(function(){
            if(jQuery(this).index()<_current_rating) jQuery(this).addClass('active');
        });
        _params[_parent.data('type')] = _current_rating;
        jQuery('#' + _parent.data('type'), _wrap).val( _current_rating )
        if( Object.keys(_params).length == _total_items) _button.removeClass('disabled');
        
    });
    _button.on('click',function(){           
        if( _button.hasClass('disabled') ) return false;
    }) 
}
