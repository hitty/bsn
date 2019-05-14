
var customIcons = {
  Магазины: {
    iconUrl: '//st.bsn.ru/img/map_icons/shops.png',
    iconSize: [28, 30]
  },
  Образование: {
    iconUrl: '//st.bsn.ru/img/map_icons/study.png',
    iconSize: [28, 30]
  },
  Парки: {
    iconUrl: '//st.bsn.ru/img/map_icons/parks.png',
    iconSize: [28, 30]
  },
  Спорт: {
    iconUrl: '//st.bsn.ru/img/map_icons/sport.png',
    iconSize: [28, 30]
  },
  Кафе: {
    iconUrl: '//st.bsn.ru/img/map_icons/cafe.png',
    iconSize: [28, 30]
  },
  Медицина: {
    iconUrl: '//st.bsn.ru/img/map_icons/medicine.png',
    iconSize: [28, 30]
  },
  Музеи: {
    iconUrl: '//st.bsn.ru/img/map_icons/museums.png',
    iconSize: [28, 30]
  },
  Кинотеатры: {
    iconUrl: '//st.bsn.ru/img/map_icons/theater.png',
    iconSize: [28, 30]
  }
  ,contacts: {
    iconUrl: '//st.bsn.ru/img/map_icons/icon-contacts.png',
    iconSize: [55, 66]
  }
  ,object: {
    iconUrl: '//st.bsn.ru/img/map_icons/object.png',
    iconSize: [35, 44]
  }  
};
_object_lat = 0;
_object_lng = 0;
_radius = 800;
_init = false;
_markers = [];
var _items = [];
_active_el = '';
_top_left_lat = 0.00;
_top_left_lng = 0.00;
_bottom_right_lat = 0.00;
_bottom_right_lng = 0.00;

jQuery(document).ready(function(){
    var _element = jQuery('#card-map-wrapper');
    _zoom = 14;
    _correct = 0.0015;
    _object_lat = 59.938014; 
    _object_lng = 30.307489;
    var _addr_wrap = jQuery('.txt-addr');
    var _zoom_type =  _addr_wrap.attr('data-zoom');
    var _y_correct = 0;
    if(_addr_wrap.attr('data-y-correct')!==undefined) _y_correct =  _addr_wrap.attr('data-y-correct').length > 0 ? 0.0015 : 0;
    
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
    


    DG.then(function() {  
        // загрузка кода модуля
        return DG.plugin('//2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/leaflet.markercluster-src.js');
    }).then(function() {
        var markers = DG.markerClusterGroup();
        if(jQuery('#card-map-wrapper').length == 0) _enum = jQuery('.map-box').children();
        else _enum = jQuery('#card-map-wrapper');
        _enum.each(function(){
            _element = jQuery(this);
            if(parseInt(_element.attr('data-lat'))>0 && parseInt(_element.attr('data-lng'))>0){
               _object_lat = parseFloat(_element.attr('data-lat'));
               _object_lng = parseFloat(_element.attr('data-lng'));
            } else {
                DG.ajax({
                    url: 'https://catalog.api.2gis.ru/geo/search',
                    data: {
                        key: 'rufsll2928',
                        version: 1.3,
                        q: _addr_wrap.data('title')
                    },
                    type: 'GET',
                    success: function(data) {
                        if(data.error_message == "Geoobject not found") {
                            jQuery('span[data-tab-ref=".infrastructure"]').hide();
                        } else {
                            // считываем строку в WKT-формате и возвращаем объект Point
                            point = DG.Wkt.toPoints(data.result[0].centroid);

                            // извлекаем координаты для маркера
                            _object_lng = point[0];
                            _object_lat = point[1];

                            // центрируем карту в координаты маркера
                            myMap.panTo([_object_lat, _object_lng]);
                            var _icon_type = _enum.data('icon') == 'contacts' ? _enum.data('icon') : 'object';
                            _m = DG.marker([_object_lat, _object_lng],
                                            {icon: DG.icon(customIcons[_icon_type])}
                            ).addTo(myMap)                          
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }
            
            //регулируем количество элементов на карте (в организациях только maximize)
            if(_element.attr('data-only-maximize')!==undefined){
                myMap = DG.map(_element.attr('id'), {
                    center: [_object_lat - _correct + _y_correct, _object_lng],
                    zoom: _zoom,
                    geoclicker: true,
                    animate: true,
                    scrollWheelZoom : false,
                    
                    touchZoom: false,
                    boxZoom: false,
                    geoclicker: false,
                    zoomControl: false,
                    fullscreenControl: true
                });
            }else{
                myMap = DG.map(_element.attr('id'), {
                    center: [_object_lat - _correct + _y_correct, _object_lng],
                    zoom: _zoom,
                    geoclicker: true,
                    animate: true,
                    scrollWheelZoom : false,
                });
                DG.control.ruler({position: 'topright'}).addTo(myMap);
            } 
            var _icon_type = _enum.data('icon') == 'contacts' ? _enum.data('icon') : 'object';
            _m = DG.marker([_object_lat, _object_lng],
                            {icon: DG.icon(customIcons[_icon_type])}
            ).addTo(myMap)
            _m.bindPopup(jQuery('h1.mtitle').text());
                                
            jQuery(".filter span").click(function(){ 
                 var el = jQuery(this);
                 if(el.data('tab-ref') == '.infrastructure') {
                    jQuery('.map-box').addClass('expanded'); 
                    myMap.invalidateSize();
                    setTimeout(function(){ myMap.invalidateSize(); }, 300)
                    if(_init == false){
                        jQuery('li', jQuery('#infrastructure')).each(function(index){
                            //список объектов инфрастуктуры
                            _items[index] = jQuery(this).text();        
                        })
                        jQuery('#card-map-wrapper').addClass('waiting');
                        //получение элементов инфраструктуры
                        var _bounds = myMap.getBounds();
                        _top_left_lat  = _bounds.getNorthWest().lat;
                        _top_left_lng  = _bounds.getNorthWest().lng;
                        _bottom_right_lat    = _bounds.getSouthEast().lat;
                        _bottom_right_lng    = _bounds.getSouthEast().lng;
                        /*
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', cache: false,
                            url: '/infrastructure/', data: {items:_items, top_left_lat:_top_left_lat, top_left_lng:_top_left_lng, bottom_right_lat:_bottom_right_lat, bottom_right_lng:_bottom_right_lng},
                            success: function(msg){
                                if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                                    jQuery('#card-map-wrapper').removeClass('waiting');
                                    _markers = msg.markers;
                                    buildPoints();
                                }
                            }
                        })
                        */
                        
                        var _estate_type = window.location.href.split('/')[3].replace(/[^A-z\_]/,'');
                        var _deal_type = window.location.href.split('/')[4].replace(/[^A-z\_]/,'');
                        
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', cache: false,
                            url: '/infrastructure/nearest/', data: {estate_type: _estate_type, deal_type: _deal_type, lat: _object_lat, lng:_object_lng},
                            success: function(msg){
                                if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                                    jQuery('#card-map-wrapper').removeClass('waiting');
                                    //_markers.concat(msg.markers);
                                    _markers = msg.markers;
                                    buildPoints();
                                    jQuery('.infrastructure-nearest').html("");
                                    for(var _marker in _markers){
                                        _marker = _markers[_marker];
                                        _marker = _marker.pop();
                                        jQuery('.infrastructure-nearest').append('<div class="infrastructure-nearest_element">' + _marker.name + ', ' + _marker.distance + 'км</div>');
                                    }
                                    
                                }
                            }
                        })
                        
                    } else buildPoints();
                    
                } else if(_active_el == '.infrastructure'){
                    markers.removeFrom(myMap);
                    jQuery('.map-box').removeClass('expanded');
                    setTimeout(function(){ myMap.invalidateSize(); }, 300)
                    
                }
                _active_el = el.data('tab-ref'); 
                
            })            

            function buildPoints(){
                markers.removeFrom(myMap);
                markers = DG.markerClusterGroup();
                jQuery('li', jQuery('#infrastructure')).each(function(index){
                    if(jQuery(this).hasClass('active')){
                        var _name = jQuery(this).text();
                        if(typeof _markers[_name] != 'undefined'){
                            for(i=0; i< _markers[_name].length; i++){
                                _icon = DG.icon(customIcons[_name]);
                                m = DG.marker(
                                    [_markers[_name][i]['lat'], _markers[_name][i]['lng']],
                                    {icon: _icon}
                                ).addTo(markers)    
                                m.bindPopup(_markers[_name][i]['name']);
                                if(_init == false) jQuery(this).attr('data-items', _markers[_name].length);
                                markers.addLayer(m)
                            }
                        }
                    } 
                })  
                markers.addTo(myMap);
               _init = true;
            }
          
            jQuery('li', jQuery('#infrastructure')).on('click', function(){
                jQuery(this).toggleClass('active');
                buildPoints();
            })
        
        });
    });

})