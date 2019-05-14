var _contacts_box_wrap = '';
var _id_click = 0;
jQuery(document).ready(function(){

    
    //show users phones
    
    jQuery('p.hidden-phone span').on('click',function(e){
                      
            _contacts_box_wrap = jQuery(this).parents('div.contacts-box');
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: '/phones'+document.location.pathname, data:{type: 'click'},
                success: function(msg){
                    if(typeof(msg)=='object') {
                        jQuery(e.target).parent().siblings('p.shown-phone').html(msg.html);
                        _id_click = msg.id_click
                        jQuery('p.shown-phone').each(function(){
                            jQuery(this).siblings('.hidden-phone').remove();
                        })
                    }    
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                },
                complete: function(){
                }
            
            });   
            if(!_debug){
                try{ yaCounter21898216.reachGoal('click_phone'); return true; }catch(err){ }
            }  
        return false;     
        
    })
    
    //звонок успешен
    jQuery(document).on('click', '.response-box .up, .response-box .down',function(){
        success_click(jQuery(this).data('id'), jQuery(this).hasClass('up') ?  'success_call' : 'wrong_number'); 
        jQuery(this).parent('.response-box').html('').html('<span>Спасибо за отзыв!</span>')
        return false; 
    })
    
    jQuery(document).on('click','.abuse-list  > span', function(e){
        if(_id_click>0){ 
           success_click(_id_click); 
       }  else {
       }
       _this = jQuery(this);
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _this.data('href'),
            success: function(msg){
                if(typeof(msg)=='object' && msg.ok) {
                    jQuery('div.abuse-title').removeClass('active').fadeOut(100).next('div.abuse-list').remove();
                    jQuery('div.object-stats-box .abuse-sended').fadeIn(200);
                    if(_id_click>0) {  
                        jQuery('p.shown-phone.active').html('<div>Спасибо, вы помогли нам сделать сайт еще лучше!</div>');
                    }
                    
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log(XMLHttpRequest);
            },
            complete: function(){
                jQuery('p.shown-phone.active').html('<div>Спасибо, вы помогли нам сделать сайт еще лучше!</div>');
            }
        
        });          
       return false
    })

});

function success_click(_id_click, _success){
    if(!_debug) try{ _gaq.push(['_trackEvent', 'Целевое действие', 'Звонок совершен',,, false]) }catch(e){};
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: false,
        url: '/phones/', data:{type: _success, id:_id_click},
        success: function(msg){
              
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
        },
        complete: function(){
        }
    
    });   
}