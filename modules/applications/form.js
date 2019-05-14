var _application_initiator = false;
var _application_type = 0;
jQuery(document).ready(function(){
    var _template = '<div id="background-shadow-expanded">'
                        +'<div id="background-shadow-expanded-wrapper"></div>'
                        +'<form class="application-view-form" action="" method="POST"><span class="form-title">Заявка</span><a class="closebutton"></a>'+
                            '<span>Ваше имя</span><input type="text" name="name" required="required"/><span>Обязательное поле</span>'+
                            '<span>Телефон</span><input type="text" name="phone" class="phone" required="required" autocomplete="off"/><span>Обязательное поле</span>'+
                            '<span>Email</span><input type="text" name="email" class="email" autocomplete="off"/><span>Неправильный email</span>'+
                            '<span>Кто вы</span><div class="list-selector grey app_user_type" data-estate-allowed="1234"><a href="#" class="pick">Выберите</a><a href="#" class="select">...</a>'+
                            '<input id="user_type" name="user_type" value="" required="required" type="hidden"><ul class="list-data"><li class="selected" data-value="">выберите</li><li data-value="1">Частное лицо</li><li data-value="2">Агент</li>'+
                            '</ul></div><span>Обязательное поле</span>'+
                            '<span>Комментарий</span><textarea name="user_comment" id="user-comment"></textarea>'+
                            ''+
                            '<div class="button-container">'+
                                '<div class="application-view-form-button">'+
                                    '<div class="single-selector id_work_status">'+
                                        '<label class="selector-title checkbox on" data-user-type="1">'+
                                            '<input id="id_work_status" class="lf" name="id_work_status" value="1" checked="checked" rel="subscribe_news" data-true-value="1" data-false-value="2" type="checkbox">'+
                                            'готов работать с агентом'+
                                        '</label>'+
                                        '<label class="selector-title checkbox on" data-user-type="2">'+
                                            '<input id="id_work_status" class="lf" name="id_work_status" value="4" checked="checked" rel="subscribe_news" data-true-value="4" data-false-value="3" type="checkbox">'+
                                            'только с собственником'+
                                        '</label>'+
                                    '</div>'+
                                    '<button class="green send" value="Отправить" onclick="try{ yaCounter21898216.reachGoal(\'send_app\'); return true; }catch(err){ }">Отправить</button>'+
                                '</div>'+
                            '</div>'+
                        '</form>'
                        +''
                    +'</div>';
    var _realtor_help_template = '<div id="background-shadow-expanded">'
                        +'<div id="background-shadow-expanded-wrapper"></div>'
                        +'<form class="application-view-form" action="" method="POST"><span class="form-title">Помощь риэлтора*</span><a class="closebutton"></a>'+
                            '<span>Ваше имя</span><input type="text" name="name" required="required"/><span>Обязательное поле</span>'+
                            '<span>Телефон</span><input type="text" name="phone" class="phone" required="required" autocomplete="off"/><span>Обязательное поле</span>'+
                            '<span>Email</span><input type="text" name="email" class="email" autocomplete="off"/><span>Неправильный email</span>'+
                            '<span>Хочу</span><div class="list-selector grey app_realtor_help_type"><a href="#" class="pick">Выберите</a><a href="#" class="select">...</a>'+
                            '<input id="realtor_help_type" name="realtor_help_type" value="" required="required" type="hidden"><ul class="list-data"><li class="selected" data-value="">выберите</li><li data-value="1">Купить недвижимость</li><li data-value="2">Продать недвижимость</li><li data-value="3">Сопровождение сделки</li><li data-value="4">Получить ипотечный кредит</li><li data-value="5">Сдать в аренду</li><li data-value="6">Арендовать недвижимость</li>'+
                            '</ul></div><span>Обязательное поле</span>'+
                            '<span>Комментарий</span><textarea name="user_comment" id="user-comment"></textarea>'+
                            '<button class="green send" value="Отправить" onclick="try{ yaCounter21898216.reachGoal(\'send_app\'); return true; }catch(err){ }">Отправить</button>'+
                            '<span class="comment">* Если Вы не нашли то, что искали, воспользуйтесь услугами профессионального риэлтора.</span>'+
                        '</form>'
                        +''
                    +'</div>';                    
    var _public_app_template = '<div id="background-shadow-expanded-2">'
                                    +'<div id="background-shadow-expanded-wrapper"></div>'
                                    +'<form class="application-view-form public" action="" method="POST"><span class="form-title">Заявка</span><a class="closebutton"></a>'+
                                        '<div class="left-column">'+
                                            '<div class="field-box"><span>Ваше имя</span><input type="text" name="name" required="required"/><span>Обязательное поле</span></div>'+
                                            '<div class="field-box"><span>Телефон</span><input type="text" name="phone" class="phone" required="required" autocomplete="off"/><span>Обязательное поле</span></div>'+
                                            '<div class="field-box"><span>Email<i>(необязательно)</i></span><input type="text" name="email" class="email" autocomplete="off"/><span>Неправильный email</span></div>'+
                                            '<div class="field-box wide"><span>Кто вы</span><div class="list-selector grey app_user_type" data-estate-allowed="1234"><a href="#" class="pick">Выберите</a><a href="#" class="select">...</a>'+
                                            '<input id="user_type" name="user_type" value="" required="required" type="hidden"><span>Обязательное поле</span><ul class="list-data"><li class="selected" data-value="">выберите</li><li data-value="1">Частное лицо</li><li data-value="2">Агент</li>'+
                                            '</ul></div></div>'+
                                        '</div>'+
                                        '<div class="right-column">'+
                                            '<div class="field-box">'+
                                                '<span>Тип сделки</span>'+
                                                '<div class="list-selector app_deal_type grey" data-estate-allowed="1234"><a href="#" class="pick">Не выбран</a><a href="#" class="select">...</a>'+
                                                    '<input id="deal_type" name="deal_type" value="" required="required" type="hidden"><ul class="list-data">'+
                                                    '<li class="selected" data-value="">Не выбран</li><li data-value="1">Аренда</li><li data-value="2">Покупка</li><li data-value="3">Сдам</li><li data-value="4">Продам</li></ul></div><span>Обязательное поле</span>'+
                                            '</div>'+
                                            '<div class="field-box">'+
                                                '<span>Тип недвижимости</span>'+
                                                '<div class="list-selector app_estate_type grey" data-estate-allowed="1234"><a href="#" class="pick">Не выбран</a><a href="#" class="select">...</a>'+
                                                    '<input id="estate_type" name="estate_type" value="" required="required" type="hidden"><ul class="list-data">'+
                                                    '<li class="selected" data-value="">Не выбран</li><li data-value="2">Новостройки</li><li data-value="1">Жилая</li><li data-value="3">Коммерческая</li><li data-value="4">Загородная</li>'+
                                                '</ul></div><span>Обязательное поле</span>'+
                                            '</div>'+
                                            '<span>Комментарий<i>(необязательно)</i></span><textarea name="user_comment" id="user-comment"></textarea>'+
                                        '</div>'+
                                        '<div class="button-container">'+
                                            '<div class="application-view-form-button public">'+
                                                '<div class="single-selector id_work_status">'+
                                                    '<label class="selector-title checkbox on" data-user-type="1">'+
                                                        '<input id="id_work_status" class="lf" name="id_work_status" value="1" checked="checked" rel="subscribe_news" data-true-value="1" data-false-value="2" type="checkbox">'+
                                                        'готов работать с агентом'+
                                                    '</label>'+
                                                    '<label class="selector-title checkbox on" data-user-type="2">'+
                                                        '<input id="id_work_status" class="lf" name="id_work_status" value="4" checked="checked" rel="subscribe_news" data-true-value="4" data-false-value="3" type="checkbox">'+
                                                        'только с собственником'+
                                                    '</label>'+
                                                '</div>'+
                                                '<button class="green send" value="Отправить" onclick="try{ yaCounter21898216.reachGoal(\'send_app\'); return true; }catch(err){ }">Отправить</button>'+
                                            '</div>'+
                                        '</div>'+
                                    '</form>'+
                                +'</div>';
    var _voting_he = "";
    if(window.location.href.match(/zhiloy_kompleks/) != null && jQuery('.mtitle').length > 0)_voting_he = jQuery('.mtitle').html().replace(/(Жилой комплекс )|\"/g,'');
    
    var _voting_template = '<div id="background-shadow-expanded">'+
                               '<div id="background-shadow-expanded-wrapper"></div>'+
                               '<form class="voting-view-form" action="" method="POST">'+
                                    '<span class="form-title">Оцените ЖК<span class="he-title">' + _voting_he + '</span></span><a class="closebutton"></a>'+
                                    '<div class="vote-line"><span>Транспортная доступность</span>' +
                                    '<span class="vote-stars"><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '<span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '</span></div>' +
                                    '<div class="vote-line"><span>Инфраструктура</span>' +
                                    '<span class="vote-stars"><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '<span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '</span></div>' +
                                    '<div class="vote-line"><span>Местоположение</span>' +
                                    '<span class="vote-stars"><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '<span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '</span></div>' +
                                    '<div class="vote-line"><span>Экологичность</span>' +
                                    '<span class="vote-stars"><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '<span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '</span></div>' +
                                    '<div class="vote-line"><span>Качество</span>' +
                                    '<span class="vote-stars"><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '<span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span><span class="star"></span>' +
                                    '</span></div>' +
                                    '<div class="button-container"><div class="voting-view-form-button"><span class="vote-sum">0</span><button class="green send" value="Отправить">Оценить</button></div></div>'+
                               '</form>' +
                           '</div>';
    _background_shadow_id = "#background-shadow-expanded";
    
    //создаем форму голосования
    jQuery('.people-rating-box').children('.mark').on('click',function(){
        _popup_order = true;
        jQuery('body').append(_voting_template);
        //обработка подтягивания звездочек левее
        jQuery(document).find('.vote-stars').children('.star').on('mouseover',function(){
            _num = jQuery(this).index();
            jQuery(this).parent().children().each(function(){
                if(jQuery(this).index()<_num) jQuery(this).addClass('hovered');
            });
        });
        //если не нажали, при уходе звездочки левее сбрасываются
        jQuery(document).find('.vote-stars').children('.star').on('mouseleave',function(){
            _num = jQuery(this).index();
            if(!jQuery(this).hasClass('in-favorites'))
                jQuery(this).parent().children().each(function(){
                    if(jQuery(this).index()<_num) jQuery(this).removeClass('hovered');
                });
        });
        //нажимаем: включаем все звездочки левее
        jQuery(document).find('.vote-stars').children('.star').on('click',function(){
            jQuery(this).removeClass('in-favorites').removeClass('hovered').siblings().removeClass('in-favorites').removeClass('hovered');
            jQuery(this).addClass('in-favorites').addClass('hovered');
            _num = jQuery(this).index();
            jQuery(this).parent().children().each(function(){
                if(jQuery(this).index()<_num) jQuery(this).addClass('hovered').addClass('in-favorites');
            });
            
            //сразу обновляем цифру-рейтинг
            var _rating = 0;
            var _params_num = jQuery('.vote-line').length;
            jQuery('.vote-line').each(function(){
                if(jQuery(this).children('.vote-stars').children('.in-favorites') !== null)
                _rating += jQuery(this).children('.vote-stars').children('.in-favorites').length;
            })
            if(_rating == 10*_params_num) jQuery('.vote-sum').html("10");
            else jQuery('.vote-sum').html((_rating/_params_num).toFixed(2));
            
            //пишем куки, чтобы запомнилось заполнение
            setBSNCookie('vote-line-'+jQuery(this).parents('.vote-line').index(),_num);
            setBSNCookie('vote-sum',(_rating/_params_num).toFixed(2));
        });
        
        jQuery('#background-shadow-expanded').fadeIn(100);
        setTimeout(function(){
            jQuery('#background-shadow-expanded .voting-view-form').addClass('active');
            jQuery('input.phone').mask('8 (000) 000-00-00');
            jQuery('input.phone').focus(function(){
                  if(jQuery(this).val().length == 0) jQuery(this).val('8');
            })
            jQuery('#background-shadow-expanded input[name=name]').focus();
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    //case 27: jQuery(".closebutton", "body").click();  break;     // esc
                    case 27: jQuery(_background_shadow_id).fadeOut(200);  break;     // esc
                    case 13: jQuery(_background_shadow_id + " .voting-view-form .send", "body").click(); break;             // enter
                }
            });           
            //заполняем заново то что было заполнено до сброса формы
            jQuery('.vote-line').each(function(){
                var _value = getBSNCookie('vote-line-'+jQuery(this).index());
                if(_value!==undefined && _value>0) jQuery(this).children('.vote-stars').children().eq(_value).click();
            });
            var _value = getBSNCookie('vote-sum');
            if(_value!==undefined && _value>0) jQuery('.vote-sum').html(_value);
        }, 200);
    });
    
    //скрытие по клику вне или крестику
        jQuery(document).on("click", ".closebutton, #background-shadow-expanded-wrapper",function(){ 
            jQuery(_background_shadow_id + ' .application-view-form').removeClass('active');
            jQuery(' .application-view-form').removeClass('active');
            setTimeout(function(){
                 jQuery(_background_shadow_id+" #background-shadow-expanded-wrapper").fadeOut(100);
                 
                 //jQuery(_background_shadow_id).remove();
                 
                 jQuery(_background_shadow_id).promise().done(function() {
                     jQuery(_background_shadow_id).remove();
                 }); 
            }, 350);
        });
    
    //щелкаем "оставить заявку"
    jQuery(document).on('click', '#application-button,#realtor-help-button', function(){ 
        _application_type = jQuery(this).attr('id') == 'realtor-help-button'  ? 2 : 1;
        _popup_order = true;
        //вызвавшая форма
        _application_initiator = jQuery(this);
        //в зависимости от того, что за заявка пихаем обычный или общий шаблон
        if(jQuery(this).hasClass('public')){
            jQuery('body').append(_public_app_template);
            _background_shadow_id = "#background-shadow-expanded-2";
            //изначально скрываем обе галочки "готов работать". будем менять при смене типа агент/собственник
            jQuery('.id_work_status').children().hide();
        }
        else{
            jQuery('body').append(_application_type == 1 ? _template : _realtor_help_template);
            
            //если страница акций, то 
            if(window.location.href.indexOf('promotions')!==-1){
                jQuery('.application-view-form').children('.closebutton').after('<span class="application-promotion-title">Акция &laquo;'+jQuery('.right-wrapper').children('h1').html()+'&raquo;</span>');
            }
            _background_shadow_id = "#background-shadow-expanded";
        }
		
		jQuery('.application-view-form').attr('data-agency-id',_application_initiator.attr('data-agency-id'));
        
        jQuery('.application-view-form').find(".list-selector").each(function(){
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
        
        jQuery(_background_shadow_id).fadeIn(100);
        
        jQuery('.app_user_type ul li').on('click',function(){
            if(jQuery(this).attr('data-value') == ""){
                jQuery('.id_work_status').removeClass('active').children().hide();
                return true;
            } 
            else if(parseInt(jQuery(this).attr('data-value')) > 0){
                jQuery('.id_work_status').addClass('active').children().hide();
                jQuery('.id_work_status').find('label[data-user-type="'+jQuery(this).attr('data-value')+'"]').show();
            }
        });
        jQuery('.id_work_status label input').on('click',function(){
            var _this = jQuery(this);
            if(_this.is(':checked') == true) _value = _this.attr('data-true-value');
            else _value = _this.attr('data-false-value');
            _this.val(_value).parent().toggleClass('on');
        });
        jQuery('.id_work_status label').on('click',function(){
            //jQuery(this).children('input').trigger('click');
        });
        
        setTimeout(function(){
            jQuery(_background_shadow_id + ' .application-view-form').addClass('active');
            jQuery('input.phone').mask('8 (000) 000-00-00');
            jQuery('input.phone').focus(function(){
                  if(jQuery(this).val().length == 0) jQuery(this).val('8');
            })
            jQuery(_background_shadow_id + ' input[name=name]').focus();
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    //case 27: jQuery(".closebutton", "body").click();  break;     // esc
                    case 27: jQuery(_background_shadow_id).fadeOut(200);  break;     // esc
                    case 13: jQuery(_background_shadow_id + " .application-view-form .send", "body").click(); break;             // enter
                }
            });           
        }, 200);

    });
    //отправка заявки
    jQuery("body").on("click", ".application-view-form .send", function(){
        var _values = {};
        if(jQuery(this).parents('.application-view-form').hasClass('mortgage')) return true;
        var _error = false;
        var _form = jQuery(this).parents('form');
        _application_initiator = jQuery('#realtor_help_type').length > 0 ? jQuery('#realtor-help-button'): jQuery('#application-button');
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
            } else _values[_name] = _value;
            
        })                             
        if(_error == false){
            _values['type'] = _application_initiator.data('type');
            _values['id'] = _application_initiator.data('id');
            _values['agency_id'] = _application_initiator.data('agency-id');
			if(_values['agency_id'] == null && jQuery('.application-view-form').attr('data-agency-id') != null)
                _values['agency_id'] = jQuery('.application-view-form').attr('data-agency-id');
            _values['agent_id'] = _application_initiator.data('agent-id');
            //если указано агентство или агент, объект трем
            if(parseInt(_values['agency_id']) > 0 || parseInt(_values['agent_id']) > 0) _values['id'] = 0;
            
            _values['id_work_status'] = jQuery('.id_work_status').find('label:visible input').val();
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/applications/add/',
                data: {ajax: true, values:_values},
                success: function(msg){
                    if(_form.hasClass('public')) _class = 'public';
                    else _class = '';
                    var _timeout = 3000;
                    var _paused_application_message = "";
                    if(msg.paused_application > 0){
                        //_paused_application_message = " Ваша заявка будет обработана после 8 января.";
                        _timeout = 5000;
                    }
                    if(msg.message !== undefined && msg.message.length > 0){
                        _form.addClass("wide-message");
                        _form.html("<div class='notification-accept "  + _class + "'><b>" + msg.name + ", спасибо за ваше обращение!"+_paused_application_message+"<br /><span class='worktime-for-app'>" + msg.message + "</span></b></div>");
                        _timeout = 7000;
                    }else _form.html("<div class='notification-accept "  + _class + "'><b>" + msg.name + ", спасибо за ваше обращение!"+_paused_application_message+"</b></div>");
                    if(jQuery(_background_shadow_id).length > 0) setTimeout(function(){jQuery(_background_shadow_id + ' #background-shadow-expanded-wrapper').click()}, _timeout);
                },
                complete: function(){
                }
            });
            //getPending('/applications/add/', _values);
            /*
            if(_form.hasClass('public')) _class = 'public';
            else _class = '';
                _form.html("<div class='notification-accept " + _class + "'><b>Спасибо за ваше обращение!</b></div>");
            if(jQuery(_background_shadow_id).length > 0) setTimeout(function(){jQuery(_background_shadow_id + ' #background-shadow-expanded-wrapper').click()}, 3000);
            */
        }else jQuery(this).removeClass('pressed');
        return false;
    });
    //отправка формы голосования
    jQuery("body").on("click", ".voting-view-form .send", function(){
        var _rating = 0;
        var _params_num = 0;
        var _values = {};
        //здесь будем накапливать значения по полям
        _values['fields'] = "";
        jQuery('.voting-view-form').children('.vote-line').children('.vote-stars').each(function(){
            if(jQuery(this).children('.in-favorites').length>9) _values['fields'] += "A";
            else _values['fields'] += jQuery(this).children('.in-favorites').length;
            _rating += jQuery(this).children('.in-favorites').length;
            ++_params_num;
        });
        //записываем общий рейтинг
        _values['rating'] = (_rating/_params_num).toFixed(2);
        //если ничего не заполнено, выходим
        if(_values['rating'] == 0){
            jQuery(this).blur();
            return false;
        }
        getPending('vote/', _values, '.vote-number');
        
        //стираем куки
        jQuery('.vote-line').each(function(){
            setBSNCookie('vote-line-'+jQuery(this).index(),0);
        });
        setBSNCookie('vote-sum',0);
        
        var _form = jQuery(this).parents('form');
        _form.addClass('voted');
        _form.html("<div class='notification-accept voting'><b>Спасибо, ваш голос учтен!</b></div>");
        if(jQuery(_background_shadow_id).length > 0)setTimeout(function(){jQuery(_background_shadow_id + ' #background-shadow-expanded-wrapper').click()}, 3000);
        jQuery('.people-rating-box').children('.mark').remove();
        return false;
    });
    
    
    
})