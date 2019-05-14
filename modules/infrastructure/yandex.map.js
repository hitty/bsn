var customIcons = {
  Магазины: {
    iconGlyph: 'shopping-cart'
  },
  Образование: {
    iconGlyph: 'pencil'
  },
  Парки: {
    iconGlyph: 'deciduous'
  },
  Спорт: {
    iconGlyph: 'alert'
  },
  Кафе: {
    iconGlyph: 'flag'
  },
  Медицина: {
    iconGlyph: 'bullhorn'
  },
  Музеи: {
    iconGlyph: 'home'
  },
  Кинотеатры: {
    iconGlyph: 'film'
  }
  ,contacts: {
    iconImageHref: '/img/layout/bsn-map-tag.svg',
    iconImageSize: [30, 30],
    iconImageOffset: [-15, -28],
    iconShadow: true,
    iconShadowImageHref: '/img/layout/bsn-map-tag-shadow.png',
    iconShadowImageSize: [21, 23],
    iconShadowImageOffset: [-1, -20], 
  }
  ,object: {
    iconImageHref: '/img/layout/bsn-map-tag.svg',
    iconImageSize: [30, 30],
    iconImageOffset: [-15, -28],
    iconShadow: true,
    iconShadowImageHref: '/img/layout/bsn-map-tag-shadow.png',
    iconShadowImageSize: [21, 23],
    iconShadowImageOffset: [-1, -20], 
  }  
};
_object_lat = 0;
_object_lng = 0;
_radius = 800;
_init = false;
_markers = [];
markers = [];
var _items = [];
_active_el = '';
_top_left_lat = 0.00;
_top_left_lng = 0.00;
_bottom_right_lat = 0.00;
_bottom_right_lng = 0.00;

jQuery(document).ready(function(){
    ymaps.ready(function () {
        jQuery('.card-map-wrapper').each(function(){
           var _element = jQuery(this);
           _zoom = 14;
           _correct = 0.0015;
           
           if( typeof _element.attr('data-lat') == 'string' ) _object_lat = parseFloat(_element.attr('data-lat'));
           else _object_lat = 59.936683;
           if( typeof _element.attr('data-lng') == 'string' ) _object_lng = parseFloat(_element.attr('data-lng'));
           else _object_lng = 30.311061;
           var _addr_wrap = jQuery('.txt-addr');
           var _zoom_type =  _addr_wrap.attr('data-zoom');
           var _y_correct = 0;
           if(_addr_wrap.attr('data-y-correct')!==undefined) _y_correct =  _addr_wrap.attr('data-y-correct').length > 0 ? 0.0015 : 0;
           var _icon_type = _element.data('map-icon') == 'contacts' ? _element.data('map-icon') : 'object';
           switch(_zoom_type){
                case 'city': 
                    _zoom = 14; 
                    _correct = 0.0015;
                    _radius = 800;
                    break;
                case 'region': 
                    _zoom = 11; 
                    _correct = 0.015;
                    _radius = 2400;
                    break;
                case 'country': 
                    _zoom = 8; 
                    _correct = 0.15;
                    _radius = 7200;
                    break;
           }
           
           var  _text = typeof _element.data('title') != 'undefined' ? _element.data('title') : jQuery('h1').text();
           
           myMap = new ymaps.Map(_element.attr('id'), {
                center: [_object_lat , _object_lng],
                zoom: _zoom,
                controls: ["zoomControl"]
            }),
            //добавление метки объекта
            myPlacemark = new ymaps.Placemark(myMap.getCenter(), {
                hintContent: _text,
                balloonContent: jQuery('.txt-addr').data('title') + '<br />' + _text
            }, {
                iconLayout: 'default#image',
                iconImageHref: customIcons[_icon_type]['iconImageHref'],
                iconImageSize: customIcons[_icon_type]['iconImageSize'],
                iconImageOffset: customIcons[_icon_type]['iconImageOffset'],
                iconShadow: true,
                iconShadowImageHref: customIcons[_icon_type]['iconShadowImageHref'],
                iconShadowImageSize: customIcons[_icon_type]['iconShadowImageSize'],
                iconShadowImageOffset: customIcons[_icon_type]['iconShadowImageOffset']
            });
            myMap.behaviors.disable('scrollZoom'); 
            myMap.geoObjects.add(myPlacemark);

            //инфраструктура
            if( _element.data('infrastructure') == true ){
                jQuery('li', jQuery('#infrastructure')).each(function(index){
                    //список объектов инфрастуктуры
                    _items[index] = jQuery(this).text();        
                })
                //получение элементов инфраструктуры
                var _bounds = myMap.getBounds();
                _top_left_lat  = _bounds[1][0];
                _top_left_lng  = _bounds[0][1];
                _bottom_right_lat    = _bounds[0][0];
                _bottom_right_lng    = _bounds[1][1];
                markers = new ymaps.GeoObjectCollection(null)
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: '/infrastructure/', data: {top_left_lat:_top_left_lat, top_left_lng:_top_left_lng, bottom_right_lat:_bottom_right_lat, bottom_right_lng:_bottom_right_lng},
                    success: function(msg){
                        if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                            _markers = msg.markers;
                            buildPoints();
                        }
                    }
                })
                
                function buildPoints(){
                    markers.removeAll();
                    markers = new ymaps.GeoObjectCollection(null);
                    var _active = 0;
                    jQuery('li', jQuery('#infrastructure')).each(function(index){
                        var _name = jQuery(this).text();
                        if(typeof _markers[_name] != 'undefined'){
                            for(i=0; i< _markers[_name].length; i++){
                                if(jQuery(this).hasClass('active') || jQuery('#infrastructure .nearest').hasClass('active')){
                                    if(!jQuery('#infrastructure .nearest').hasClass('active') || i<1){
                                        _active++;
                                        //добавление метки объекта
                                        m = new ymaps.Placemark(
                                            [_markers[_name][i]['lat'], _markers[_name][i]['lng']],
                                            {
                                                hintContent: _markers[_name][i]['name'],
                                                balloonContent: '<b>' + _markers[_name][i]['name'] + '</b><br />' + _markers[_name][i]['address']
                                            }, {
                                                preset: 'islands#redGlyphIcon',
                                                iconGlyph: customIcons[_name]['iconGlyph'],
                                                iconOffset: [-8, 8]
                                            }
                                        );
                                        markers.add(m)
                                    }
                                }
                                if(_init == false) jQuery(this).attr('data-items', _markers[_name].length);
                            }
                        }
                    })  
                    if(_active > 0){
                        markers.add(myPlacemark)
                        myMap.geoObjects.add(markers);
                        //myMap.setBounds(markers.getBounds());
                    } else myMap.geoObjects.add(myPlacemark);
                }       
                jQuery('li', jQuery('#infrastructure')).on('click', function(){
                    if(jQuery(this).hasClass('nearest')){
                        if(jQuery(this).hasClass('active')) return false;
                        else {
                            jQuery(this).addClass('active').siblings('li').removeClass('active');
                        }
                    } else if (jQuery(this).hasClass('all')) jQuery(this).removeClass('active').siblings('li').addClass('active').siblings('li.nearest').removeClass('active');
                    else jQuery(this).toggleClass('active').siblings('li.nearest').removeClass('active');
                    buildPoints();
                })
                jQuery('.button, .close, i', jQuery('.infrastructure')).on('click', function(){
                    jQuery('#infrastructure').toggleClass('active');
                })
            }
        
                 
        })
   })
})