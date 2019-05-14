var _message_check = true;
jQuery(document).ready(function(){
            
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    
    jQuery(".timestamp").timeago();
    
    var ctrl = false;
    var deleteclicked = false;
    _last_click = new Date();
    
    jQuery('#sendmessage').click(function(){
        _click_time = new Date();
        _message = jQuery.trim(jQuery("#messagetext").val());
        if(_message == '' || _click_time.getSeconds() - _last_click.getSeconds() <= 1 && _click_time.getSeconds() - _last_click.getSeconds()>=0) return false;
        _last_click = new Date();
        var _params = {msgtext:jQuery("#messagetext").val(),id:jQuery("#id_to").val(),pid:jQuery("#id_parent").val()};
        var elem = new Object();
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: '/members/messages/send/', data: _params,
            success: function(data, textStatus, xhranswer){
                if (data['ok']){
                    jQuery('#messagetext').val('');
                    _last_direction = 'from';
                    if(typeof jQuery('.messageslist li').last().prop('class')!='undefined') _last_direction = jQuery('.messageslist li').last().prop('class').replace('message','');
                    
                    jQuery('#id_parent').attr('value',data['parentid']);
                    var _template = '<div class="text">'+data['msgtxt']+
                                        '<div class="timestamp">'+data['msgtime']+'</div>'+
                                    '</div>';
                    
                    jQuery('.messageslist').append('<li class="message'+data['msgdirection']+'">'+_template+'</li>');
                    
                    elem = jQuery('.message'+data['msgdirection']).last();
                    jQuery(".messageslist").animate({ scrollTop: jQuery(".messageslist").prop("scrollHeight") - jQuery(".messageslist").height() }, 0);        
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
            }
        });
    });

    jQuery('.dialog').click(function(){
        if (deleteclicked){
            deleteclicked = false;
        } else {
            document.location.href = jQuery(this).attr('url');
        }
    });
    
   
    jQuery(document).on('click', '.delete', function(){
        var _id = jQuery(this).data('id');
        var _params = {id: _id};
        if(confirm("Удалить диалог?")){
            getPending('/members/messages/delete/',_params);
            jQuery('.dialog-wrapper').html('');
            jQuery('.users-list .item[data-id-parent=' + _id + ']').remove();
            
        }
        deleteclicked = true;
    });
    _val = jQuery('#ctrlenter').val();
    if(_val==2)  jQuery('.ctrlenter', jQuery('#messageform')).click();
    jQuery('.ctrlenter', jQuery('#messageform')).on('click', function(){
        if(jQuery(this).hasClass('on')) _val = jQuery('#checkbox_ctrlenter').data('false-value');
        else _val = jQuery('#checkbox_ctrlenter').data('true-value');
        jQuery('#ctrlenter').val(_val);
        getPending('/members/messages/send_message_change/',{value:_val})
    });
    
    jQuery(document).keydown(function(e) {
        if (_val == 2){
            if(e.keyCode == 13 && ctrl){
                jQuery('#sendmessage').click();    
            }
        } else if (e.keyCode == 13){
            jQuery('#sendmessage').click();
        }
        if(e.keyCode == 17) ctrl = true; else ctrl = false;
    });
    
    jQuery('.objcomment').hover(function(){
        jQuery(this).siblings('.objpopup').css('display','block');
    });
    
    jQuery('.objcomment').mouseleave(function(){
        jQuery(this).siblings('.objpopup').css('display','none');
    });
    
    var _dialog_wrap = jQuery('.dialog-wrapper')  
    var _users_wrap = jQuery('.users-list')  
    var _form = jQuery('#messageform')  
    //инициализация диалога
    jQuery('.item', _users_wrap).on('click', function(){
        var _this = jQuery(this);
        jQuery('#id_to').attr('value', _this.data('id-user-to'));
        jQuery('#id_parent').attr('value', _this.data('id-parent'));
        jQuery('.unread-total', _this).remove();
        
        _gpval = _this.data('id-parent');
        setGPval();
        
        _form.show();
        _this.addClass('active').siblings('.item').removeClass('active');
        getPendingContent('.dialog-wrapper', _this.data('url'), false, false, false, false, false, function(){ jQuery(".messageslist").animate({ scrollTop: jQuery(".messageslist").prop("scrollHeight") - jQuery(".messageslist").height() }, 0);        } )
        //проверка на новые сообщения
        getPending('/members/messages/setread/',{ id: _this.data('id-parent') });
        jQuery('#messagetext').focus();
    })
    
    if( _dialog_wrap.length > 0) {
        _gpval = getGPval(document.location.href);
        if( _gpval !='' ) jQuery('.item[data-id-parent='+_gpval+']', _users_wrap).click();
        var _interval = _debug ? 500000000 : 30000;
        _messageSetInterval = window.setInterval(function(){
            var _params = {id:jQuery("#id_parent").val()};
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: true,
                url: '/members/messages/checknew/', data: _params,
                success: function(msg){
                    
                    if (msg.ok){
                        if(typeof jQuery('.messageslist li').last().prop('class')!='undefined') _last_direction = jQuery('.messageslist li').last().prop('class').replace('message','');
                        
                        jQuery('.messageslist').append(msg.html);
                        
                        jQuery(".timestamp").timeago();
                        jQuery(".messageslist").animate({ scrollTop: jQuery(".messageslist").prop("scrollHeight") - jQuery(".messageslist").height() }, 0);        
                        if(jQuery('#popup-click').length == 0){
                            jQuery('body').append('<audio id="popup-click" controls="controls" preload="auto"><source src="http://st1.bsn.ru/img/audio/beep.mp3"></source><source src="/audio/beep.ogg"></source>Your browser isnt invited for super fun time.</audio>');
                            audio = document.getElementById("popup-click");
                        }
                        audio.play();
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                }
            });
        }, _interval); 
    }  
    
    
   
});

