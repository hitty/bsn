jQuery(document).ready(function(){
    jQuery('#registration-form button').on('submit',function(){
        return false;
    });
    jQuery('#registration-form button').on('click',function(e){
        
        var _values = {};
        var _error = false;
        var _form = jQuery(this).parents('form');
        _reg_initiator = jQuery('#reg-button');
        _form.find('input, textarea').each(function(){
            var _this = jQuery(this);
            _this.removeClass('red-border').next('span').removeClass('active');
            if(_this.parent().hasClass('list-selector')) _this.parent().removeClass('red-border').next('span').removeClass('active');
            var _type = _this.attr('type');
            if(_type == 'checkbox' && _this.parent().hasClass('on')){
                _value = 1;
            } else {
                _value = _this.val();
            }
            _required = _this.attr('data-required');
            _name = _this.attr('name');
            if( (_required == 'true' && (_value == '' || _value == 0) || (_name == 'phone' && _value.length!=17)) || 
                (_name == 'email' && (_value.length > 0 && _value.match(/([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/) == null) )) {
                //отдельно для селекторов
                if(_this.parent().hasClass('list-selector')) _this.parent().addClass('red-border').next('span').addClass('active');
                else _this.addClass('red-border').next('span').addClass('active');
                
                _error = true;
            } else _values[_name] = _value;
            
        });
        
        //если это форма на странице специалиста, отмечаем его id:
        if(_form.attr('data-responder') !== undefined) _values['responder'] = parseInt(_form.attr('data-responder'));
        
        e.stopPropagation();
        e.preventDefault();
        
        if(_error == true) return false;
        
        var _url = window.location.href.replace(/\?.*$/,'') + 'registration/';
        jQuery.ajax({
            url: _url,
            cache: false,
            type: 'POST',
            async: true,
            dataType: 'json',
            data: {
                form_data: _values,
                ajax:true
            },
            success: function(msg){
                if(msg.ok){
                    jQuery('#registration-form').after("<div class='notification-accept answer'><b>Спасибо за регистрацию!</b></div>");
                    jQuery('#registration-form').remove();
                    setTimeout("jQuery('.notification-accept').fadeOut(500);",1000);
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log("Error: "+textStatus+" "+errorThrown);
            },
            complete: function(){}
        });
        
        return false;
    });
    jQuery('.registration-topblock .green.send').on('click',function(){
        if(jQuery(this).attr('data-link') !== undefined){
            return;
        }else{
            $('html, body').animate({
                scrollTop: $("#registration-form").offset().top - 50
            }, 500);
        }
    });
    
    if( jQuery('#datetime-filter .list li.active').length > 0) jQuery('#datetime-filter .list li.active').click();
});