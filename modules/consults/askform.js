jQuery(document).ready(function(){
    
    var _public_consults_template = '<div id="background-shadow-expanded-2">'
                                    +'<div id="background-shadow-expanded-wrapper"></div>'
                                    +'<form class="consults-view-form public popup" action="" method="POST"><span class="form-title">Задайте свой вопрос!</span><a class="closebutton"></a>'+
                                        '<div class="left-column consults"><div class="field-box required"><span>Ваше имя</span><input name="name" required="required" value="" type="text"><span>Обязательное поле</span></div>'+
                                        '<div class="field-box"><span>Заголовок<i>(необязательно)</i></span><input name="title" type="text"></div>'+
                                        '<div class="field-box required"><span>Тема вопроса</span><div class="list-selector question_category grey" data-estate-allowed="1234"><a href="#" class="pick">Не выбрана</a><a href="#" class="select">...</a>'+
                                        '<input id="category" name="category" value="" required="required" type="hidden"><ul class="list-data"><li class="selected" data-value="">Не выбрана</li><li data-value="10">Временная и постоянная прописка</li>'+
                                        '<li data-value="3">Вторичный рынок</li><li data-value="4">Долевое строительство</li><li data-value="7">Загородная недвижимость</li><li data-value="6">Коммерческая недвижимость</li><li data-value="5">Комнаты</li>'+
                                        '<li data-value="2">Первичный рынок</li><li data-value="13">Приватизация</li><li data-value="11">Сопровождение сделок с недвижимостью</li><li data-value="12">Страхование</li><li data-value="8">Судебная практика</li></ul>'+
                                        '</div><span>Обязательное поле</span></div></div>'+
                                        '<div class="right-column consults"><div class="field-box required"><span>Email</span><input name="email" class="email" autocomplete="off" required="required" value="" type="text"><span>Неправильный email</span>'+
                                        '</div><div class="field-box required"><span>Вопрос</span><textarea name="text" id="text" required="required"></textarea><span>Обязательное поле</span></div></div><div class="form-footer"><button class="green send" name="submit" value="Отправить">Отправить</button>'+
                                        '</div>'
                                +'</div>';
    
    _background_shadow_id = "#background-shadow-expanded-2";
    //щелкаем "оставить вопрос"
    jQuery(document).on('click', '#consults-button', function(){ 
        _popup_order = true;
        //вызвавшая форма
        _consults_initiator = jQuery(this);
        //в зависимости от того, что за заявка пихаем обычный или общий шаблон
        if(jQuery(this).hasClass('public')){
            //если формы еще нет, пихаем
            if(jQuery(".consults-view-form.popup").length == 0){
                jQuery('body').append(_public_consults_template);
                jQuery('.consults-view-form.popup').find(".list-selector").each(function(){
                    var _selector = jQuery(this);
                    jQuery(".select, .pick", _selector).click(function(){
                        _selector.toggleClass("dropped");
                        if(_selector.hasClass("dropped")) _opened_listelector = _selector;
                        else  _opened_listelector = null;
                        return false;
                    });
                    jQuery(".list-data li:not(.disabled)", _selector).click(function(event, first_call){
                        if(typeof first_call == 'undefined') first_call = false;
                        var _li = jQuery(this);
                        var _lhtml = _li.html();;
                        _li.addClass("selected").siblings('li').removeClass("selected");
                        if(_li.data('title')!='' && typeof _li.data('title')=='string') {_lhtml = _li.data('title');}
                        if(_lhtml!=jQuery(".pick", _selector).html()){
                            jQuery(".pick", _selector).html(_lhtml).attr('title',_lhtml);
                            _previous_value =  jQuery('input[type="hidden"]',_selector).val();
                            var _val = _li.attr("data-value");
                            jQuery('input[type="hidden"]',_selector).val(_val);
                            if(_val.length <= 1 && (_val=='' || _val==0)) _selector.removeClass('active');
                            else  _selector.addClass('active');
                            if(!first_call) _selector.trigger('change',_lhtml);
                        }
                        _selector.removeClass("dropped");
                        _opened_listelector = null;
                    });
                    var _def_val = jQuery('input[type="hidden"]',_selector).val();
                    var _active_item = jQuery('.list-data li[data-value="'+_def_val+'"]', _selector);
                    if(!_active_item.size()) _active_item = jQuery('.list-data li:first', _selector);
                    _active_item.trigger("click", true);
                });
                jQuery('#background-shadow-expanded-2 a.closebutton').on('click',function(){
                    jQuery(_background_shadow_id).fadeOut(200);
                });
            } 
            _background_shadow_id = "#background-shadow-expanded-2";
        }
        jQuery(_background_shadow_id).fadeIn(100);
        
        setTimeout(function(){
            jQuery('.consults-view-form.popup').addClass('active');
            //заполняем форму с закрепленной справа, если она есть
            if(jQuery('.right-column-fixed').find('.consults-view-form').length > 0){
                var _right_form = jQuery('.right-column-fixed').find('.consults-view-form');
                var _popup_form = jQuery('.consults-view-form.popup');
                _popup_form.attr('data-responder',_right_form.attr('data-responder'));
                _popup_form.find('input[name="name"]').val(_right_form.find('input[name="name"]').val());
                _popup_form.find('input[name="category"]').val(_right_form.find('input[name="category"]').val());
                _popup_form.find('input[name="email"]').val(_right_form.find('input[name="email"]').val());
                if(_right_form.find('.checkbox.agree').length > 0){
                    _popup_form.children('.form-footer').children('.send').before("<label class='checkbox agree on'>" + _right_form.find('.checkbox.agree').html() + "</label>");
                }
            }
            
            jQuery('.consults-view-form.popup').find('input[name="name"]').focus();
            
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    //case 27: jQuery(".closebutton", "body").click();  break;     // esc
                    case 27: jQuery(_background_shadow_id).fadeOut(200);  break;     // esc
                    case 13: jQuery(" .consults-view-form.popup .send", "body").click(); break;             // enter
                }
            });           
        }, 200);

    });
    //ставим галочку
    jQuery('.checkbox.agree').click();
    
    //отправка вопроса
    jQuery("body").on("click", ".consults-view-form .send", function(e){
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
            data: _values,  cache: false,
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                        
                        jQuery('.consultant-ask.control').remove();
                        if(_form.hasClass('popup')){
                            _form.html("<div class='notification-accept'><b>Спасибо за ваш вопрос!</b></div>");
                            setTimeout("jQuery('.notification-accept').fadeOut(500).parent().remove();jQuery('.form-box').removeClass('active');jQuery(_background_shadow_id).fadeOut(200).remove();",1000);
                        }else{
                            _form.after("<div class='notification-accept'><b>Спасибо за ваш вопрос!</b></div>");
                            _form.remove();
                            setTimeout("jQuery('.notification-accept').fadeOut(500);jQuery('.form-box').removeClass('active');_this_button.removeClass('pressed');",1000);
                        } 
                        
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