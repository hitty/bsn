var customIcons = {
  housing_estate: {
    icon: '/img/map_icons/icon_map_housing_complex.png'
  },
  cottage: {
    icon: '/img/map_icons/icon_map_cottage.png'
  },
  live: {
    icon: '/img/map_icons/icon_map_live.png'
  },
  build: {
    icon: '/img/map_icons/icon_map_build.png'
  },
  commercial: {
    icon: '/img/map_icons/icon_map_commercial.png'
  },
  country: {
    icon: '/img/map_icons/icon_map_country.png'
  },
  business_center: {
    icon: '/img/map_icons/icon_map_commercial_b.png'
  },
  no: {
    icon: '/img/layout/cottage_ico.png'
  }
};
    _object_lat = 0;
    _object_lng = 0;

jQuery(document).ready(function(){
    ymaps.ready(init);
    function init () {
       var _element = jQuery('#card-map-wrapper');
        var _object_lat = 59.938014; 
        var _object_lng = 30.307489;
        var _addr_wrap = jQuery('.txt-addr');
        var _zoom_type =  _addr_wrap.attr('data-zoom');
        _zoom = 14;
        switch(_zoom_type){
            case 'city': _zoom = 14; break;
            case 'region': _zoom = 11; break;
            case 'country': _zoom = 8; break;
        }
        myMap = new ymaps.Map('card-map-wrapper', {
                zoom: _zoom,
                center: [_object_lat, _object_lng]
            }, {
                autoFitToViewport: 'always'
            }   
        );
        myMap.controls.add('typeSelector').add('smallZoomControl', { left: 5, top: 5 }); 
        // Создаем метку и задаем изображение для ее иконки
        var _customIcon = _element.attr('data-icon');
        if(typeof _customIcon!='undefined' &&_customIcon.length>0) _icon = customIcons[_customIcon].icon;
        else _icon = customIcons['no'].icon;
        myPlacemark = new ymaps.Placemark([_object_lat, _object_lng], {}, {
            iconImageHref: _icon, // картинка иконки
            iconImageSize: [44, 46],
            iconImageOffset: [0, -32], 
            iconShadow: true,
            iconShadowImageHref: '/img/map_icons/icon_shadow.png',
            iconShadowImageSize: [53, 25],
            iconShadowImageOffset: [9, -16]             
        });
        // Добавление метки на карту
        myMap.geoObjects.add(myPlacemark);        

       _object_lat = _element.attr('data-lat');
       _object_lng = _element.attr('data-lng');
        if(parseInt(_object_lat)>0 && parseInt(_object_lng)>0) {
            myPlacemark.geometry.setCoordinates([_object_lat, _object_lng]);
            myMap.setCenter([_object_lat, _object_lng]);
            myMap.setZoom(_zoom);
            nearestMetro([_object_lat, _object_lng]);
        }            
        else {
            var _addr = _addr_wrap.attr('data-title');
            if(_addr!='') geoCoding(_addr);
        }
    }
    
    /* определение координат */
    var _iteration = 0;
   function geoCoding(addr){
       ++_iteration;
       if(_iteration==3) return false; 
       ymaps.geocode(addr, { results: 1 }).then(function (res) {
            // Выбираем первый результат геокодирования
            var _geoObject = res.geoObjects.get(0);
            if(_geoObject!=null){
                var _coords = _geoObject.geometry.getCoordinates()
                _object_lat = _coords[0].toFixed(4);
                _object_lng = _coords[1].toFixed(4);
                myMap.setCenter([_object_lat, _object_lng]);
                myMap.setZoom(_zoom);
                myPlacemark.geometry.setCoordinates([_object_lat, _object_lng]);
                nearestMetro([_object_lat, _object_lng]);
            } 
        });
       
   } 
   
   function nearestMetro(coords){
        var myGeocoder = ymaps.geocode(coords, {kind: 'metro'});
            myGeocoder.then(
                function (res) {
                    var _metro = '';
                    for(i=0;i<3;i++){
                        if(res.geoObjects.get(i)!=null){
                            var nearest = res.geoObjects.get(i);
                            var name = nearest.properties.get('name').replace('метро','');
                            var metro_coords = nearest.geometry.getCoordinates();
                            var distance = ymaps.coordSystem.geo.getDistance(coords, metro_coords) ;
                            if(distance>1000)   distance = (distance/1000).toFixed(1)+' км';
                            else  distance = Math.floor(distance)+' м';
                            _metro = _metro + '<span>' +name + ', ' + distance +' </span>'
                        }   else break;
                    }
                    if(_metro!='') { jQuery('.nearest-metro-box').show().append(_metro);
                        
                    }
                },
                function (err) {
                    alert('Ошибка');
                }
            );       
   }
})