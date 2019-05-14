jQuery(document).ready(function(){
    
    
    
    //отправка вопроса
    jQuery("body").on("click", ".consults-view-form.built-in .send", function(e){
        var _values = {};
        _this_button = jQuery(this);
        
        var _error = false;
        var _form = jQuery(this).parents('form');
        _question_initiator = jQuery('#consults-button');
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
            _required = _this.attr('required');
            _name = _this.attr('name');
            if( (_required == 'required' && (_value == '' || _value == 0) || (_name == 'phone' && _value.length!=17)) || 
                (_name == 'email' && (_value.length > 0 && _value.match(/([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/) == null) )) {
                //отдельно для селекторов
                if(_this.parent().hasClass('list-selector')) _this.parent().addClass('red-border').next('span').addClass('active');
                else _this.addClass('red-border').next('span').addClass('active');
                
                _error = true;
            } else{
                _values[_name] = _value;
                _this.removeClass('red-border').next('span').removeClass('active');
            }
            
        });
        
        //если это форма на странице специалиста, отмечаем его id:
        if(_form.attr('data-responder') !== undefined) _values['responder'] = parseInt(_form.attr('data-responder'));
        
        e.stopPropagation();
        e.preventDefault();
        if(_error == true) return false;
        
        if (_this_button.hasClass('pressed')) return false;
        else _this_button.addClass('pressed');
        
        _silent_mode = false;
        
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/service/consultant/add/',
            data: {ajax: true, values: _values},  cache: false,
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                        
                        showSuccessPopup();
                        
                        _form.find('input, textarea').each(function(){
                            var _this = jQuery(this);
                            if(_this.attr('name') == 'name' || _this.attr('name') == 'email') return true;
                            _this.removeClass('red-border').next('span').removeClass('active');
                            if(_this.parent().hasClass('list-selector')){
                                _this.parent().removeClass('red-border').next('span').removeClass('active');
                                _this.next('ul').children('li').first().trigger('click');
                            } 
                            _this.val("");
                        });
                        
                    } else if(!_silent_mode) alert('Ошибка: '+msg.errors);
                } else if(!_silent_mode) alert('Ошибка!');
            },
            error: function(){
                if(!_silent_mode) alert('Server connection error!');
            }
        });
        return false;
    });
});