var _form  = {};
jQuery(document).ready(function(){
    if(jQuery('.estate-evaluation').length > 0){
        _form = jQuery('.estate-evaluation');
        jQuery('button', _form).on('click', function(){
            var _values = {};
            var _error = false;
            var _form = jQuery(this).parents('form');
            _form.find('input').each(function(){
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
                if( 
                    (_required == 'required' && (_value == '' || _value == 0) || (_name == 'phone' && _value.length!=17) ) ||
                    ( _name == 'email' && ( _value.length > 0 && _value.match(/([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/) == null) ) 
                ) {
                    //отдельно для селекторов
                    if(_this.parent().hasClass('list-selector')) _this.parent().addClass('red-border').next('span').addClass('active');
                    else _this.addClass('red-border').next('span').addClass('active');
                    _error = true;
                } else {
                    if(_type == 'radio') {
                        if(_this.is(':checked')) _values[_name] = _value;
                    }
                    else _values[_name] = _value;
                        
                }
                
            })        
            
            if(_error == false){
                jQuery('#estimate-result').text('');
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: '/estate_estimate/result/',
                    data: _values,
                    success: function(msg){
                        if(typeof(msg)=='object' && msg.ok) {
                            _form.find('input').each(function(){
                                jQuery(this).val('');
                            })
                           window.location = '/estate_estimate/' + msg.hash + '/';
                           /*
                           _form.addClass('notification-accept').text('Мы отправили ссылку на отчет с онлайн-оценкой квартиры по заданным параметрам на указанный вами адрес электронной почты') ;
                           jQuery('#estimate-result').text('') ;
                           jQuery('.estimate-again').css({'display':'block'});
                           */
                        } else {
                            jQuery('#estimate-result').text('К сожалению на данный момент в нашей базе недостаточно статистических данных для расчета. Попробуйте указать другие параметры.');
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log('Запрос не выполнен!');
                    },
                    complete: function(){
                       
                    }
                });

                
            } else jQuery('#estimate-result').text('Заполните выделенные красным поля.');
            return false;
        })
        jQuery('.autocomplete', _form).each(function(){
            var _input = jQuery(this);
            _input.typeWatch({
                callback: function(){
                    jQuery(this).siblings('input').val(0);
                    var _searchstring = this.text;
                    _input.addClass('wait');
                    jQuery.ajax({
                        type: "POST", dataType: 'json',
                        async: true, cache: false,
                        url: _input.data('url'),
                        data: {ajax: true, search_string: _searchstring},
                        success: function(msg){
                            if(typeof(msg)=='object' && msg.ok) {
                                if(msg.list.length>0) showPopupList(_input, msg.list);
                                else hidePopupList();
                            }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown){
                            console.log('Запрос не выполнен!');
                        },
                        complete: function(){
                            _input.removeClass('wait');
                        }
                    });
                },
                wait: 150,
                highlight: true,
                captureLength: 3
            }).blur(function(){
                setTimeout(function(){hidePopupList(_input)}, 350);
            });        
        })

        jQuery('.clear-input').on('click', function(){
           var _class = jQuery(this).prev('input').attr('name');
            jQuery('input[name='+_class+']').attr('value','').val('').siblings('input').val('').siblings('.clear-input').addClass('hidden');
        });     

    
    }
    

    //геокодирование адреса
    if( jQuery('.estate-estimate-wrapper #card-map-wrapper').length > 0 ){
        jQuery('#street-build,#house,#corp').on('change', function(){
            if( jQuery('#street-build').val() != '' )  {
                var _address = jQuery('#street-build').val();
                _address += jQuery('#house').val() != '' ? ', дом '  + jQuery('#house').val() : '';
                _address += jQuery('#corp').val() != '' ? ', корпус '  + jQuery('#corp').val() : '';
                console.log( _address )
                ymaps.geocode( _address, { results: 1 }).then(function (res) {
                    // Выбираем первый результат геокодирования
                    var _geoObject = res.geoObjects.get(0);
                    if(_geoObject!=null){
                        var _coords = _geoObject.geometry.getCoordinates()
                        myMap.setCenter([_coords[0].toFixed(4), _coords[1].toFixed(4)]);
                        myPlacemark.geometry.setCoordinates([_coords[0].toFixed(4), _coords[1].toFixed(4)]);
                    }
                });
                
            }
        })
    }
    
});
function showPopupList(_el,_list, _type){
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
    for(var i in _list){                   
        str += '<li data-id="'+_list[i].id+'" title="'+_list[i].title+(typeof _list[i].additional_title=='string'?_list[i].additional_title:'')+'">'+_list[i].title+(typeof _list[i].additional_title=='string'?'<span>'+_list[i].additional_title+'</span>':'')+'</li>';
    }
    str += '</ul>';
    hidePopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).closest('.typewatch_popup_list').parent();
        var _el_class = _el.attr('name');
        jQuery('input[name='+_el_class+']').next('.clear-input').removeClass('hidden').next('input').val( jQuery(this).data('id') );
        jQuery('input[name='+_el_class+']').val(jQuery(this).text()).attr('title',jQuery(this).text());
        hidePopupList(_parent_box);
    });
    
}
function hidePopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}  