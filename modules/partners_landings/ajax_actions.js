jQuery(document).ready(function(){
	//fileuploader init
	if(jQuery('#file_upload').length>0){
		jQuery('#file_upload').uploadifive({'queueSizeLimit':20});
	}	
    
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
});