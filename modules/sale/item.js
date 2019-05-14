$(document).ready(function(){

//_background_template
    //initMap
    jQuery('.cda-map').on('click',function(){
        var _this = jQuery(this);
        var _lat = _this.data('lat');
        var _lng = _this.data('lng');
        var _phone = _this.data('');
        jQuery('body').append(_background_template);
        jQuery('#background-shadow-expanded').append('<div class="map-box"><h3>Адрес объекта</h3><div class="address-wrap"><span class="map-address"></span><span class="map-phone"></span></div><div id="card-map-wrapper"></div><a class="closebutton">X</a></div>');
        jQuery('.map-address', jQuery('.address-wrap')).text(_this.data('address'))
        jQuery('.map-phone', jQuery('.address-wrap')).text(_this.data('phone'))

        ymaps.ready(function(){
            
            var _element = jQuery('#card-map-wrapper');
            _zoom = 14;
            myMap = new ymaps.Map('card-map-wrapper', {
                    zoom: _zoom,
                    center: [_lat, _lng]
            });
            myMap.controls.add('typeSelector').add('smallZoomControl', { left: 5, top: 5 }); 
            // Создаем метку и задаем изображение для ее иконки
            myPlacemark = new ymaps.Placemark([_lat, _lng], {}, {
                iconImageHref: '/img/layout/icon_map.png', // картинка иконки
                iconImageSize: [39, 50],
                iconImageOffset: [0, -32] 
            });
            // Добавление метки на карту
            myMap.geoObjects.add(myPlacemark);        

            if(parseInt(_lat)>0 && parseInt(_lng)>0) {
                myPlacemark.geometry.setCoordinates([_lat, _lng]);
                myMap.setCenter([_lat, _lng]);
                myMap.setZoom(_zoom);
            } 
        })        
    })
    
    jQuery(document).on("click", ".map-box>.closebutton, .send-message>.closebutton, #background-shadow-expanded > #background-shadow-expanded-wrapper",function(){ 
         jQuery('#background-shadow-expanded').remove();
    });

    // текущая дата
    var _today = new Date();   
    var yesterday = new Date(_today.getTime() - (24 * 60 * 60 * 1000));    
    jQuery('.cd-timer-main').each(function(){
        var _this = jQuery(this);
        var _days_wrap = jQuery('.days-left',_this);
        var _hours_wrap = jQuery('.hours-left',_this);
        var _minutes_wrap = jQuery('.minutes-left',_this);
        // дата предстоящего события (год, месяц, число)
        var _mysql_date = _this.data('date-end').split(/[-]/);
        var _endDate = new Date(_mysql_date[0], _mysql_date[1]-1, _mysql_date[2]);
        // если событие еще не наступило
        if(yesterday <= _endDate){
            if(Math.floor(Math.round(_endDate-_today)/86400000) < 3) _this.addClass('red');
            var _timeLeft = timeToEvent(_endDate, 'split');
            _days_wrap.text(_timeLeft.days);
            _hours_wrap.text(_timeLeft.hours);
            _minutes_wrap.text(_timeLeft.minutes);
            window.setInterval(function(){ 
                var _timeLeft = timeToEvent(_endDate, 'split');
                _days_wrap.text(_timeLeft.days);
                _hours_wrap.text(_timeLeft.hours);
                _minutes_wrap.text(_timeLeft.minutes);
            },5000);           
        } 

    });
        
    // send message
    var _send_form = '<form class="send-message" action="" method="POST"><h3>Заявка</h3><a class="closebutton">X</a><input type="text" name="name"  placeholder="Имя*" required="required" /><input type="text" name="phone" id="phone"  placeholder="Телефон*" required="required" /><input type="email" name="email"  placeholder="Email" /><label class="checkbox public-offer"><input type="checkbox" value="0" name="public_offer_agree" id="public-offer-agree"  required="required"/><span class="internal-link" data-link="/public_offer/" data-target="_blank" title="Публичная оферта">С условиями</span> ознакомлен и согласен</label><button class="ok-btn disabled" value="Отправить">Отправить</button></form>';
    jQuery('#send-message').on('click',function(){
        jQuery('body').append(_background_template);
        jQuery('#background-shadow-expanded').append(_send_form);
        
    })
    jQuery("body").on("click", "form.send-message .ok-btn", function(){
        if(jQuery(this).hasClass('disabled')) return false;
        var _values = {};
        var _error = false;
        jQuery("form.send-message").find('input').each(function(){
            var _this = jQuery(this);
            _this.removeClass('red-border')
            var _type = _this.attr('type');
            if(_type == 'checkbox' && _this.parent().hasClass('on')){
                _value = 1;
            } else {
                _value = _this.val();
            }
            _required = _this.attr('required');
            _name = _this.attr('name');
            if(_required == 'required' && (_value == '' || _value == 0)) {
                _this.addClass('red-border').attr('title','Обязательное поле');
                _error = true;
            } else _values[_name] = _value;
            
        })
        if(_error == false){
            getPending(window.location, _values);
            jQuery('.send-message>.closebutton').click();
        }
    })
        
});

