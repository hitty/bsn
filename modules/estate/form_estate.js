_error_cost = false;
jQuery(document).ready(function(){
    //общая форма
    var _form = jQuery('#item-edit-form');
    //значение типа недвижимости 
    var _estate_type = _form.attr('class');
    //управление показом кол-ва комнат при выборе - комната (жилая)
    jQuery(".id_type_object", _form).on('change', function(event, value){
       var _this = jQuery(this);
       var _val = _this.children('input[type=hidden]').val();
       var _rooms_div = jQuery('.rooms_total', _form);
       if(jQuery('.estate-estimate').length > 0) jQuery('.estate-estimate').addClass('hidden');
       if(_val==2 ){ 
           _rooms_div.show();
           jQuery('.single-selector.is_apartments').removeClass('active');
           jQuery('.single-selector.is_penthouse').removeClass('active');
       }
       else {
           _rooms_div.hide();
           if(_val==1) {
               jQuery('.single-selector.studio').addClass('active');
               jQuery('.single-selector.is_apartments').addClass('active');
               jQuery('.single-selector.is_penthouse').addClass('active');
               if(jQuery('.estate-estimate').length > 0) jQuery('.estate-estimate').removeClass('hidden');
           } else {
               jQuery('.single-selector.studio').removeClass('active');
               jQuery('.single-selector.is_apartments').removeClass('active');
               jQuery('.single-selector.is_penthouse').removeClass('active');
               jQuery('#rooms_sale').attr('disabled', false);
           }
       }
    });
    jQuery('.studio label').on('click', function(){
        if(jQuery('#studio').val() == 2) {
           jQuery('.single-selector.square_kitchen').show();
            jQuery('#rooms_sale').attr('disabled', false).siblings('span').addClass('required');
        } else {
           jQuery('.single-selector.square_kitchen').hide();
           jQuery('#rooms_sale').attr('disabled', 'disabled').siblings('span').removeClass('required'); 
        }
    })
    //управление показом мес и годом рассрочки при выборе - рассрочка ()
    jQuery(_form).on('click', 'input[name=installment]', function(event, value){
       var _val = jQuery(this).val();
       var _installment_months = jQuery('.installment_months', _form);
       var _installment_years = jQuery('.installment_years', _form);
       var _first_payment = jQuery('.first_payment', _form);
       if(_val==1 ){ 
           _installment_months.show();
           _installment_years.show();
           _first_payment.show();
       } else { 
           _installment_months.hide();
           _installment_years.hide();
           _first_payment.hide();
       }
    });


/* карта */
    if(jQuery('#map-box').length > 0){
        ymaps.ready(function () {
            var _element = jQuery('#map-box');
            var _lat_el = jQuery('#lat');
            var _lng_el = jQuery('#lng');
            var _lat = _lat_el.val();
            var _lng = _lng_el.val();
            if(parseInt(_lat)==0 && parseInt(_lng)==0){
               _lat = 59.938014; 
               _lng = 30.307489; 
            }
            myMap = new ymaps.Map('map-box', {
                    center: [_lat, _lng], 
                    zoom: 14
            });
            myMap.controls.add('typeSelector').add('smallZoomControl', { left: 5, top: 5 }); 

            // Создаем метку и задаем изображение для ее иконки
            placemark = new ymaps.Placemark([_lat, _lng], {
                hintContent: 'Передвиньте отметку для точного определения местоположения.'
            }, {
                iconImageHref: '/img/layout/map_icons/add_icon.png', 
                iconImageSize: [39, 50],
                iconImageOffset: [-18, -50], 
                draggable: true
            });
            myMap.geoObjects.add(placemark);  

            //Отслеживаем событие перемещения метки
            placemark.events.add("dragend", function (e) {            
                coords = this.geometry.getCoordinates();
               
                ymaps.geocode(coords, { results: 1 }).then(function (res) {
                    // Выбираем первый результат геокодирования
                    var _geoObject = res.geoObjects.get(0);
                    console.log(_geoObject)
                    
                });                
                myMap.setCenter([coords[0].toFixed(4), coords[1].toFixed(4)]);
                    _lat_el.val(coords[0].toFixed(4));
                    _lng_el.val(coords[1].toFixed(4));            
            }, placemark);
                              
        });
    }
    
    jQuery('#txt_district,#txt_subway,#housing_estate,#cottage,#business_center').each(function(){
        
        var _input = jQuery(this);
        var _type =  jQuery(this).attr('id').replace('txt_','');
        _input.parent().css('position','relative');
        /* автокомплит улиц */
        _input.typeWatch({
            callback: function(){
                var _searchstring = this.text;
                _input.addClass('wait');    
                if(_type == 'housing_estate') _url = '/zhiloy_kompleks/title/'
                else if(_type == 'cottage') _url = '/cottedzhnye_poselki/title/'
                else if(_type == 'business_center') _url = '/business_centers/title/'
                else _url = '/geodata/'+_type+'s_list/';
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: _url,
                    data: {ajax: true, search_string: _searchstring},
                    success: function(msg){
                        if(typeof(msg)=='object' && msg.ok) {
                            if(msg.list.length>0) showSimplePopupList(_input, msg.list, _type);
                            else hideSimplePopupList(_input.parent());
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
            setTimeout(function(){hideSimplePopupList(_input.parent())}, 350);
        });
    });
    
    //ставим галочку публичной оферты
    jQuery('#public-offer-agree').click();
    
    //если ошибки есть, скроллим до первой
    if(jQuery('.error').length > 0) jQuery('html, body').animate({scrollTop:  jQuery('.error').first().offset().top}, 800);
});

function showSimplePopupList(_el,_list, _type){
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list">';
    for(var i in _list){
        str += '<li data-id="'+_list[i].id+'">'+_list[i].title+'</span></li>';
    }
    str += '</ul>';
    hideSimplePopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).parent().parent();
        jQuery("#id_"+_type).val( jQuery(this).data('id') );
        _el.val(jQuery(this).html());
        hideSimplePopupList(_parent_box);
        var _id = _el.attr('id');
        if(_id != 'housing_estate' && _id != 'cottage' && _id != 'business_center') fillAddress();
         _el.next('.clear-input').removeClass('hidden');
    });
    
}
function hideSimplePopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}  

jQuery(document).ready(function(){
    var _input = jQuery("#txt_region");
    _input.parent().css('position','relative');
    /* автокомплит улиц */
    _input.typeWatch({
        callback: function(){
            jQuery("#geo_id").val(0);
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/geodata/regions_list/',
                data: {ajax: true, search_string: _searchstring},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showRegionsPopupList(_input, msg.list);
                        else hideRegionsPopupList();
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
        setTimeout(function(){hideRegionsPopupList(jQuery("#txt_region").parent())}, 350);
    });
});

function showRegionsPopupList(_el,_list){
    
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list">';
    for(var i in _list){
        str += '<li data-id="'+_list[i].id+'" data-id_district="'+_list[i].id_district+'" data-id_region="'+_list[i].id_region+'" data-item="'+_list[i].g_offname+'"  data-region="'+_list[i].region+'" data-district_title="'+_list[i].district_title+'">'+_list[i].g_offname+' <span>'+_list[i].region+'</span></li>';
    }
    str += '</ul>';
    hideRegionsPopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).parent().parent();
        jQuery("#geo_id").val( jQuery(this).data('id') );
        jQuery("#geolocation").val( jQuery(this).data('region') );
        var _id_region = parseInt(jQuery(this).data('id_region'));
        if(_id_region == 78){
            jQuery("#id_district").val( jQuery(this).data('id_district') );
            var _district_title = jQuery(this).data('district_title');
            if(_district_title!='' && _district_title!='-') jQuery("#txt_district").val(_district_title).attr('disabled',false);
        } else {
            jQuery("#id_district").val(0);
            jQuery("#txt_district").attr('disabled','disabled').val('-');
        }
        jQuery('#txt_street').focus();
        _el.val(jQuery(this).data('item'));
        hideRegionsPopupList(_parent_box);
        jQuery("#id_street").val(0);
        jQuery('#house').val(''); 
        jQuery('#corp').val(''); 
        jQuery('#txt_street').val(''); 
        jQuery('#txt_addr').val(''); 
        fillAddress();
         _el.next('.clear-input').removeClass('hidden');
    });
}
function hideRegionsPopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}  
jQuery(document).ready(function(){
    var _input = jQuery("#txt_street");
    _input.parent().css('position','relative');
    /* автокомплит улиц */
    _input.typeWatch({
        callback: function(){
            jQuery("#id_street").val(0);
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/geodata/streets_list/',
                data: {ajax: true, search_string: _searchstring, geo_id: jQuery('#geo_id').val()},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showStreetsPopupList(_input, msg.list);
                        else hideStreetsPopupList();
                    } else if(jQuery('#geo_id').val()==0)alert('Выберите населенный пункт!');
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
        setTimeout(function(){hideStreetsPopupList(jQuery("#txt_street").parent())}, 350);
    });

    if( jQuery("#house, #corp, #txt_street").length > 0 ){
        jQuery("#house, #corp, #txt_street").on('change, keyup',function(e){
            fillAddress();
        }) 
    } else if( jQuery("#txt_addr, #id_district_area, #id_district").length > 0 ){
        jQuery("#txt_addr, #id_district_area, #id_district").on('change, keyup',function(e){
            fillAddress();
        }) 
    }
    

});

function showStreetsPopupList(_el,_list){
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list">';
    for(var i in _list){
        str += '<li data-id="'+_list[i].id_street+'" data-id_district="'+_list[i].id_district+'" data-district_title="'+_list[i].district_title+'">'+_list[i].offname+' '+_list[i].shortname+'</li>';
    }
    str += '</ul>';
    hideStreetsPopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).parent().parent();
        jQuery("#id_street").val( jQuery(this).attr('data-id') );

        var _district = parseInt(jQuery(this).attr('data-id_district'));
        if(_district>0) {
            jQuery("#id_district").val(_district);
            var _district_title = jQuery(this).attr('data-district_title');
            if(_district_title!='') jQuery("#txt_district").attr('disabled',false).val(_district_title);
             _el.next('.clear-input').removeClass('hidden');
        }
        _el.val(jQuery(this).html());
        hideStreetsPopupList(_parent_box);
        fillAddress();
         _el.next('.clear-input').removeClass('hidden');
    });
}
function hideStreetsPopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}

// заполнение полного адреса от выбранных улицы+дома+корпуса
function fillAddress(){
    var _hcs = jQuery("#house, #corp, #txt_street").length;
    var _adad = jQuery("#txt_addr, #id_district_area, #id_district").length;

    var _addr = _full_addr = new Array();
    if( _hcs ) {
        var _city = jQuery('#txt_region').val();
        var _geolocation = jQuery('#geolocation').val();
        var _street = jQuery('#txt_street').val();
        var _house = jQuery('#house').val();
        var _corp = jQuery('#corp').val();
        
        if(_city!='') _addr.push(_city)
        if(_geolocation!='') _addr.push(_geolocation)
        if(typeof _street != 'undefined') _addr.push(_street)
        if(typeof _house != 'undefined' && parseInt(_house)>0) _addr.push('д. '+_house.replace(/[^0-9]/g,''))
        if(typeof _corp != 'undefined' && _corp!='') _addr.push('к. '+_corp)
    } else if( _adad ){
        var _txt_addr = jQuery('#txt_addr').val();
        var _district_area = jQuery('#id_district_area').val();
        var _district = jQuery('#id_district').val();
        if(typeof _district_area != 'undefined' && parseInt(_district_area)>0) {
            
            _addr.push( 'Ленобласть, ' + jQuery( 'select#id_district_area option[value=' + _district_area + ']').text() + ' район');
        }
        else if(typeof _district != 'undefined'  && parseInt(_district)>0 ) {
            _addr.push( 'Санкт-Петербург, ' + jQuery( 'select#id_district option[value=' + _district_area + ']').text() + ' район');
        }
        if(typeof _txt_addr != 'undefined') _addr.push( _txt_addr );
    } else return false;
    console.log( _addr )
    console.trace( _addr )
    var _lat_el = jQuery('#lat');
    var _lng_el = jQuery('#lng');
    var _full_addr = _addr.join(', ');
    if( _hcs ) jQuery('#txt_addr').val(_addr.join(', '));
    if(typeof ymaps !== 'undefined'){
        ymaps.geocode(_addr.join(','), { results: 1 }).then(function (res) {
            // Выбираем первый результат геокодирования
            var _geoObject = res.geoObjects.get(0);
            if(_geoObject!=null){
                var _coords = _geoObject.geometry.getCoordinates()
                myMap.setCenter([_coords[0].toFixed(4), _coords[1].toFixed(4)]);
                placemark.geometry.setCoordinates([_coords[0].toFixed(4), _coords[1].toFixed(4)]);
                _lat_el.val(_coords[0].toFixed(4));
                _lng_el.val(_coords[1].toFixed(4));
            }
        });
    }
}


jQuery(document).ready(function(){
    //fileuploader init
    var _photos_weight_begin = parseInt(jQuery('#weight-bar').attr('data-photos-weight')) > 0 ? parseInt(jQuery('#weight-bar').data('photos-weight')) : 0;
    
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({
                onChangeCount: function(){
                    var _photos_count = 0;
                    _photos_count = parseInt(jQuery('.file_upload_queue').children('li:not(.uploadifyButton)').length);
                    if(jQuery('.fileUploadStat').children('.totalObjects').length>0)
                        _photos_count = parseInt(jQuery('.fileUploadStat').children('.totalObjects').html());
                    var _photos_weight = getPhotosWeight(_photos_count);
                    jQuery('#weight-bar').attr('data-photos-weight',_photos_weight)
                    checkForm(_photos_weight,false);
                },
                fileSizeLimit : '33MB',
                onError      : function(errorType) {
                    switch(errorType){
                        case "FILE_SIZE_LIMIT_EXCEEDED": alert('Превышен максимально допустимый размер файла'); break;
                        case "FORBIDDEN_FILE_TYPE": alert('Недопустимый тип файла'); break;
                    }
                    jQuery('.uploadifive_queue-item.error').remove();
                },
                buttonText: 'Загрузить фото'
            }
        );
        if(jQuery('#file_upload_video').length>0){
            jQuery('#file_upload_video').uploadifive({
                    'queueSizeLimit'    : 1,
                    'buttonText'        : 'Загрузите видео mp4, 3gp, ogg, avi, mov, wmf, wmv, mpeg до 200 МБайт',
                    'fileType'          : 'video',
                    'video'             : true,
                    'videoContainer'    : '.video-uploaded-wrap',
                    'fileSizeLimit'    : 16000000000
                }
            );  
        }
    }
    //setTimeout("_photos_weight_begin = parseInt(jQuery('.photos .fileUploadStat').children('.totalObjects').html());checkForm(_photos_weight_begin,false);",1500);
    
    //вес фотографий
    function getPhotosWeight(count){
        if(count <= 2) { return count; }
        else if(count>=3){
            if(count>20) count = 20;
            return (2 + count);
        }
    }

    checkForm(_photos_weight_begin,false);
    //работа с формами
    jQuery('#item-edit-form input, .list-selector, textarea').bind('change',function(){
        checkForm(_photos_weight_begin,false);
    });
    

    jQuery('#next_step, #publish, #save').click(function(){
        if(jQuery(this).hasClass('disabled')) {
            if(jQuery('.notification').length == 0){
                _notification = jQuery('<div class="notification msgerror">Заполните все обязательные поля формы.</div>');
                jQuery('.cabinet-wrap .crmp').prepend(_notification);
                setTimeout(function(){ _notification.click();},2000);
            }
            checkForm(_photos_weight_begin,true);
            if(jQuery('.object-statuses').length > 0 && jQuery('#publish.published').length == 0 && jQuery('.object-statuses li.active').length == 0) {
                _notification = jQuery('<div class="notification msgerror">Выберите тип публикации объекта</div>');
                if(jQuery('.status-description').children('.notification.msgerror').length == 0) jQuery('.status-description').prepend(_notification);
                //jQuery('.status-description').prepend(_notification);
                setTimeout(function(){ _notification.click();},7000);
                jQuery('html, body').animate({scrollTop:  jQuery('.status-description').offset().top - 150}, 200);
            }

            return false;
        }
        
        //для добавления без авторизации
        if(jQuery('.publish-wrap').length > 0){
            jQuery('html, body').animate({scrollTop:  jQuery('.publish-wrap').offset().top - 150}, 200);
            jQuery('button', jQuery('#item-edit-form')).addClass('disabled');
            jQuery('button', jQuery('#right-column')).addClass('disabled');
            return false;
        }
        
        _action = jQuery('#item-edit-form').attr('action');
        _get_params = 'step=3';
        
        var _step = parseInt(jQuery('.add-object-steps').attr('class').replace(/[^0-9]/g,''));
        if(jQuery('.object-statuses li.active').length == 0 && _step == 3) _status = jQuery('#object-status-value').val();
        else _status = parseInt(jQuery('.object-statuses li.active').data('value')); 
        
        
        if(_status > 0 && jQuery(this).attr('id')!='save') _get_params =  'action=pay_object&status='+_status;
        //alert(_action+'?'+_get_params);
        //return false;
        jQuery('#item-edit-form').attr('action', _action+'?'+_get_params).submit();
        return false;
    })
    
    //выбор статуса
    jQuery('.object-statuses li').on('click', function(){
        //если при этом телефон пуст, отмечаем что поле не заполнено
        if(jQuery('#seller_phone').length>0 && jQuery('#seller_phone').val().length <= 1){
            jQuery('#seller_phone').parent('.required').addClass('error');
            window.scrollTo(jQuery('.normal-contacts-box').offset().left,jQuery('.normal-contacts-box').offset().top-100);
            return false;
        }
        
        var _this = jQuery(this);
        _this.toggleClass('active').siblings('li').removeClass('active');
        if(_this.hasClass('active')) jQuery('button.green.published').text('Сохранить и применить');
        else jQuery('button.green.published').text('Сохранить');
        checkForm(_photos_weight_begin,false);
    })

    jQuery('.clear-input').on('click', function(){
       var _input = jQuery(this).siblings('input');
       var _id =  _input.attr('name').replace('txt_','id_');
       if(_id == 'id_region'){
            _input = jQuery('#txt_region, #txt_district, #txt_street, #house, #corp, #geolocation');
            var _i_ds =  jQuery('#geo_id, #id_district, #id_street');   
            jQuery('.single-selector.geolocation').show();
            jQuery('.single-selector.txt_district').show();
       } else if(_id == 'id_street'){
            _input = jQuery('#txt_street, #house, #corp');
            var _i_ds =  jQuery('#id_street'); 
       } else if(_id == 'housing_estate' || _id == 'cottage' || _id == 'business_center'){
            _input = jQuery('#housing_estate, #cottage, #business_center');
            var _i_ds =  jQuery('#id_housing_estate, #id_cottage, #id_business_center');   
       } else {
           var _i_ds = jQuery('#'+_id);
       }
        _input.attr('value','').val('').siblings('.clear-input');
        _i_ds.attr('value',0).val(0);
        _input.next('.clear-input').addClass('hidden');
       if(_id != 'housing_estate' && _id != 'cottage' && _id != 'business_center') fillAddress();
       checkForm(_photos_weight_begin,false);
    });
    
    //
    jQuery('#rooms_sale').on('change, keyup',function(e){
        jQuery('#rooms_total').val(jQuery(this).val());
    })
    // Pubslish container
    if(jQuery('.publish-wrap').length > 0){
        jQuery('.publish-tabs', jQuery('.publish-wrap')).each(function(){
            jQuery(this).on('click', function(){
                var _this = jQuery(this);
                _this.addClass('active').siblings('span').removeClass('active');
                jQuery('.publish-content').hide();
                jQuery(_this.data('content-container')).show();
            })
        })
        
        jQuery('.publish-tabs').first().click();
    }
    //send data
    jQuery('.publish-content').each(function(){
        var _wrap = jQuery(this);
        
        var _succ_text = "";
        var _fail_text = "";
        //флажок, нужно ли проверять google-капчу. 
        //при регистрации из добавления объекта не submit, поэтому нужно отдельно
        var _registration = false;
        
        if(_wrap.attr('id') == 'publish-auth'){
            _succ_text = "Ок, осуществляется вход";
            _fail_text = "Пара логин-пароль неверная";
            _registration = false;
        }else{
            _succ_text = "На указанный вами email высланы логин и пароль. Для завершения публикации объявления необходимо указать номер телефона и выбрать тип размещения (бесплатно/с услугой)";
            _fail_text = "Произошла ошибка во время регистрации";
            _registration = true;
        }
        
        jQuery('input[name="auth_login"], input[name="auth_passwd"]').on('click',function(){
            _wrap.find('input[name="auth_login"]').removeClass('red-border').next().html("");
            _wrap.find('input[name="auth_passwd"]').removeClass('red-border').next().html("");
        });
        
        jQuery('button', jQuery(this)).on('click',function(){
            if(jQuery(this).hasClass('disabled')) return false;
            
            _wrap.find('input').removeClass('red-border');
            
            var _params = {};
            _params['ajax'] = true;
            _error = false;
            _invalid_email = false;
            jQuery('input',_wrap).each(function(){
                var _val = jQuery(this).val();
                var _name = jQuery(this).attr('name');
                if((_name == 'auth_login' || _name == 'login_email') && (_val == '' || !validateEmail(_val)) ) {
                    _error = true;
                    _invalid_email = true;
                }
                _params[_name] = _val;
            });
            
            //если необходимо (регистрация из добавления), проверяем капчу
            if(_registration){
                _params['g-recaptcha-response'] = grecaptcha.getResponse();
            }
            
            _wrap.find('.error-title').html("");
            
            if(_error == true){
                if(_invalid_email){
                    if(_registration) _wrap.find('input[name="login_email"]').addClass('red-border').next().html("Некорректный email");
                    else _wrap.find('input[name="auth_login"]').addClass('red-border').next().html("Некорректный email");
                } 
                else{
                    _wrap.children('.auth_login').children('input').addClass('red-border');
                    _wrap.children('.auth_passwd').children('input').addClass('red-border');
                    _wrap.find('input[name="auth_login"]').next().html(_fail_text);
                }
                return false;
            }else{
                _wrap.children('.auth_login').children('input').removeClass('red-border');
                _wrap.children('.auth_passwd').children('input').removeClass('red-border');
                
                //_wrap.find('.response-msg').addClass('success').addClass('active').html(_succ_text);
            }
            
            _url = jQuery(this).data('url');
            
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: '/'+_url+'/', data: _params,
                success: function(msg){
                    if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                        _wrap.find('input').removeClass('red-border');
                        //чтобы пользователь успел прочитать, делаем alert
                        if(_registration) alert(_succ_text);
                        else _wrap.find('.response-msg').addClass('success').addClass('active').html(_succ_text);
                        _action = jQuery('#item-edit-form').attr('action');
                        jQuery('#item-edit-form').attr('action', _action+'?attach=1').submit();
                    } else {
                        //if(msg.error!='') _error_div.html(msg.error) ;
                        _wrap.children('.auth_login').children('input').addClass('red-border');
                        _wrap.children('.auth_passwd').children('input').addClass('red-border');
                        //_wrap.find('.response-msg').addClass('active').html(_fail_text);
                        //если указаны ошибки, показываем их:
                        if(_registration){
                            if(msg.errors !== undefined){
                                if(msg.errors['userexist'] !== undefined) _wrap.find('input[name="login_email"]').addClass('red-border').next().html(msg.errors['userexist']);
                                if(msg.errors['recaptcha'] !== undefined) _wrap.find('.g-recaptcha').next().html(msg.errors['recaptcha']);
                                if(msg.errors['login_email'] !== undefined) _wrap.find('input[name="login_email"]').addClass('red-border').next().html(msg.errors['login_email']);
                            }
                        }else _wrap.find('input[name="auth_login"]').next().html(_fail_text);
                    }
                    return false;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    //elem.addClass('system_error').html('Ошибка обращения к серверу!'); 
                    //console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
                },
                complete: function(){
                    //elem.removeClass('waiting');
                }

            });
            return false;     

        });
        
        jQuery('#publish-reg').on('click',function(e){
            var _target = jQuery(e.target);
            if(_target.attr('id') != 'public-offer-agree')
                if(jQuery(this).find('input[name="login_email"]').val().length > 0 && jQuery(this).find('input[name="login_name"]').val().length > 0 && jQuery(this).find('.checkbox').hasClass('on'))
                    jQuery(this).find('button[name="auth_submit"]').removeClass('disabled');
                else jQuery(this).find('button[name="auth_submit"]').addClass('disabled');
        });
        
    })
    
    jQuery('input[max]', jQuery('#item-edit-form')).each(function(){
        var _max = jQuery(this).attr('max');
        jQuery(this).on('keyup', function(){
            var _val = parseInt(jQuery(this).val());
            if(_val > _max) jQuery(this).val(parseInt(_val/10));
        })
    })
    
    jQuery('.publish-content .checkbox').on('click', function(e){
        var _button = jQuery(this).siblings('button');
        //так как эта обработка первая, результат наоборот
        if(jQuery(this).hasClass("on")){
            _button.addClass('disabled');
            jQuery(this).children('input').val("0");
        } 
        else if(jQuery(this).parents('.publish-content').find('input[name="login_email"]').val().length > 0 && 
                jQuery(this).parents('.publish-content').find('input[name="login_name"]').val().length > 0){
                    jQuery(this).children('input').val("1");
                    _button.removeClass('disabled');
                } 
    });
    
    jQuery('.id_user_type .list-data').on('click',function(){
        jQuery('.id_work_status').find('input').removeClass('disabled').siblings('input[data-user-type!=' + jQuery(this).find('.selected').attr('data-value') + ']').addClass('disabled');
        if(jQuery('.id_work_status').find('input.selected:not(.disabled)').length == 0) jQuery('.id_work_status').find('input:not(.disabled)').first().click();
    });
    if(jQuery('.id_work_status').find('input.selected:not(.disabled)').length == 0) jQuery('.id_work_status').find('input:not(.disabled)').first().click();
    
    _by_the_day = null;
    jQuery("input[name=by_the_day]").click(function() {
        checkCost(jQuery("#cost").val());
    });
    jQuery("#cost").on('keyup',function(e, value){
        checkCost(jQuery("#cost").val())
    });
    
    //модерация стоимости
    if(jQuery('#cost').length > 0){
        var _cost = parseInt(jQuery('#cost').val().replace(' ',''));
        if(_cost > 0) jQuery("#cost").trigger("change", true);
        
    }
    
    function checkCost(_cost){
        _by_the_day = 2;
        if(jQuery('input[name=by_the_day]').length > 0){
            _by_the_day = jQuery('input:radio[name=by_the_day]:checked').val();
        }
        
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: '/members/estate/moderate/'+jQuery('#item-edit-form').data('estate')+'/'+jQuery('#item-edit-form').data('deal')+'/',
            data: {ajax: true, cost: _cost, by_the_day: _by_the_day},
            success: function(msg){
                if(typeof(msg)=='object' && msg.ok) {
                    jQuery('#span_field_cost .error_tip').remove();
                    _error_cost = false;
                    jQuery('#cost').removeClass('red-border');
                }
                else {
                    _error_cost = true;
                    jQuery('#span_field_cost .error_tip').remove();
                    var _text = msg.status == 3 ? 'подозрительно большая' : 'подозрительно маленькая';
                    jQuery('#span_field_cost').append('<span class="error_tip"> (цена '+_text+')</span>')
                    jQuery('#cost').addClass('red-border');
                }
                checkForm(_photos_weight_begin,false);

            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            },
            complete: function(){
            }
        });
        
    }
    
    //перетаскивание фоток в лк
    if(jQuery.isFunction(jQuery('#file_upload_queue').sortable)){
        jQuery('#file_upload_queue').sortable({
            items: "li:not(.uploadifyButton)",
            stop: function( event, ui ) {
                _photos_order = [];
                jQuery('.file-upload-wrap.photos .itemsContainer').each(function(){
                    _photos_order.push(jQuery(this).attr('data-id_obj'));
                });
                jQuery('#photos_order').val(_photos_order.join(','));
            }
        });
        jQuery('#file_upload_queue').disableSelection();
    }
});

function checkForm(_photos_weight,_mark_border){ 
    var _weight = 0;
    var _form = jQuery('#item-edit-form');
    var _error = false;
    
    if(jQuery('#geolocation').val() != "") jQuery('#span_field_id_district').addClass('unactive');
    
    var _this_step_max_weight = 0;
    
    jQuery('[id*=span_field]',_form).each(function(){
        var _this = jQuery(this);
        _this.prev('label').removeClass('error')
        _value = jQuery('[name='+_this.data('rel')+']', _form).val();
        var _input = _this.siblings('input');
        if(_value !== undefined && !_this.hasClass('unactive') && _this.attr('data-weight') !== undefined) _this_step_max_weight += parseInt(_this.attr('data-weight'));
        
        if((_value == '' || _value == 0) && 
            !_this.parents('.single-selector').hasClass('unactive') && 
            !_this.parents('.list-selector').hasClass('unactive') &&
            !_this.hasClass('unactive')) {
            //alert(_this.attr('id'));
            if(_this.hasClass('required') && _this.is(':visible')){
                if(_mark_border == true) _this.addClass('error').attr('title','Обязательное поле');
                _error = true;  
            } else if(_input.hasClass('typewatch')) {
                _input.next('i');
            }
        } else {
            if(_mark_border == true) _this.removeClass('error').attr('title','Обязательное поле');
            //подсчет весов
            var _weight_val = parseInt(_this.attr('data-weight'));
            if(_weight_val>0) {
                _weight = _weight + _weight_val;
            }
            if(!_this.parents('.single-selector').hasClass('unactive') && !_this.parents('.list-selector').hasClass('unactive') && _input.hasClass('typewatch')) {
                _input.next('i').removeClass('hidden');
            }
        }
    });
    
    if(jQuery('.add-object-steps').length > 0){
        //if(parseInt(jQuery('.add-object-steps').attr('class').replace(/[^0-9]/g,'')) == 3)
        jQuery('#weight-bar').attr('data-other-step-weight',parseInt(jQuery('#weight-bar').attr('data-weight')) - _weight);
    }
    
    
    jQuery('#weight-bar').attr('data-this-step-weight',_weight);
    var _total_weight = parseInt(jQuery('#weight-bar').attr('data-this-step-weight')) + 
                        parseInt(jQuery('#weight-bar').attr('data-other-step-weight')) + 
                        parseInt(jQuery('#weight-bar').data('photos-weight'));
    jQuery('#weight-bar').children('i').css({'width':(_total_weight)+'%'}).
                          siblings('b').text(_total_weight+'%');
    jQuery('[name=weight]').val(_total_weight);

    //выбран статус
    if(jQuery('.object-statuses').length > 0 && jQuery('#publish.published').length == 0 && jQuery('.object-statuses li.active').length == 0) {
        _error = true;
    }
    
    //если это публикация и галочка публичной оферты не поставлена
    if(jQuery('.object-manage.bottom.wide').hasClass('wpublic-offer')){
        if(jQuery('.object-manage.bottom.wide').find('#public-offer-agree:checked').length == 0){
            jQuery('#public-offer-agree').addClass('error');
            _error = true;
        }else jQuery('#public-offer-agree').removeClass('error');
    }
    
    if(_error == false && _error_cost == false){
        jQuery('button[class*=disabled]', jQuery('#right-column')).removeClass('disabled');
        jQuery('button[class*=disabled]', jQuery('#item-edit-form')).removeClass('disabled');
    }  else {
        jQuery('button', jQuery('#right-column')).addClass('disabled');            
        jQuery('button', jQuery('#item-edit-form')).addClass('disabled');
        //скроллим до ошибки
        if(jQuery('.error').length > 0) jQuery('html, body').animate({scrollTop:  jQuery('.error').first().offset().top - 150}, 800);
    }
    if(jQuery('#installment').attr('value') != 1){
        jQuery('.single-selector.first_payment').hide().children('#first_payment').val("");
        jQuery('.single-selector.installment_months').hide().children('#installment_months').val("");
        jQuery('.single-selector.installment_years').hide().children('#installment_years').val("");
    }
    jQuery('.list-selector.installment').children('.list-data').children('li').on('click',function(){
         if(jQuery(this).attr('data-value') == 1){
             jQuery('.single-selector.first_payment').show();
             jQuery('.single-selector.installment_months').show();
             jQuery('.single-selector.installment_years').show();
         }else{
             jQuery('.single-selector.first_payment').hide().children('#first_payment').val("");
             jQuery('.single-selector.installment_months').hide().children('#installment_months').val("");
             jQuery('.single-selector.installment_years').hide().children('#installment_years').val("");
         }
     });
     //в зависимости от шага, скрываем поля формы
    if(jQuery('.add-object-steps').length > 0){
        
        var _step = parseInt(jQuery('.add-object-steps').attr('class').replace(/[^0-9]/g,''));
        //2 шаг: район города для загородной; комнаты, номер дома, корпус для не тех типов
        //для 3 шага ограничения применяются из скрипта
        if(_step == 2){
            //убираем для участка в загородной
            if(jQuery('.estate-add-icon').hasClass('estate-country')){
                //для участка убираем
                if(jQuery('#id_type_object').val() == 13){
                    jQuery('.single-selector.rooms').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.house').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.corp').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.square_full').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.square_live').addClass('unactive').find('input').val("");
                    //площадь учатска в этом случае обязательна
                    jQuery('.single-selector.square_ground .selector-title').addClass('required').html("Площадь участка, сот *");
                }
                else{
                    jQuery('.single-selector.rooms').removeClass('unactive');
                    jQuery('.single-selector.house').removeClass('unactive');
                    jQuery('.single-selector.corp').removeClass('unactive');
                    jQuery('.single-selector.square_full').removeClass('unactive');
                    jQuery('.single-selector.square_live').removeClass('unactive');
                    //площадь учатска в этом случае не обязательна
                    jQuery('.single-selector.square_ground .selector-title').removeClass('required').html("Площадь участка, сот");
                }
            }
            else
            if(jQuery('.estate-add-icon').hasClass('estate-commercial')){
                //для участка убираем
                if(jQuery('#id_type_object').val() == 21){
                    jQuery('.single-selector.cost2meter').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.house').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.corp').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.square_full').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.square_usefull').addClass('unactive').find('input').val("");
                    jQuery('.single-selector.business_center').addClass('unactive').find('input').val("");
                    //площадь учатска в этом случае обязательна
                    jQuery('.single-selector.square_ground .selector-title').addClass('required').html("Площадь участка, сот *");
                }
                else{
                    jQuery('.single-selector.cost2meter').removeClass('unactive');
                    jQuery('.single-selector.house').removeClass('unactive');
                    jQuery('.single-selector.corp').removeClass('unactive');
                    jQuery('.single-selector.square_full').removeClass('unactive');
                    jQuery('.single-selector.square_usefull').removeClass('unactive');
                    jQuery('.single-selector.business_center').removeClass('unactive');
                    jQuery('.single-selector.square_ground .selector-title').removeClass('required').html("Площадь участка, сот");
                }
            }
        }
    }
}