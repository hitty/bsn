jQuery(document).ready(function(){     
        if(typeof _message_check == 'undefined' && !_debug){    
            _popup_interval = window.setInterval(function(){
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: true,
                    url: '/members/messages/checknew/', data: {type:'popup',id:jQuery("#id_parent").val()},
                    success: function(msg){
                        if(msg.ok) {
                            if(jQuery('#popup-message-wrap').length == 0){
                                jQuery('body').append('<div id="popup-message-wrap"></div><audio id="popup-click" controls="controls" preload="auto"><source src="http://st1.bsn.ru/img/audio/beep.mp3"></source><source src="/audio/beep.ogg"></source>Your browser isnt invited for super fun time.</audio>');
                                audio = document.getElementById("popup-click");
                            }
                            audio.play();
                            var _template = jQuery('<div>'+msg.html+'</div>');
                            _template.prependTo("#popup-message-wrap").fadeIn(100);
                            jQuery('.members-menu li.messages, .auth-menu-links li a.messages').each(function(){
                                if(jQuery(this).children('i.amount').length == 0) jQuery(this).append('<i class="amount">0</i>');
                                jQuery(this).children('i.amount').text(msg.unread_count);
                            })
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                    }
                });
            }, 30000);
            
            jQuery('body').on('click',  '#popup-message-wrap .message-item .close', function(){
                jQuery(this).parent('div.title').parent('div.message-item').fadeOut(100).remove();
            })
        }
});