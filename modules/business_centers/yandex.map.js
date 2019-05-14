jQuery(document).ready(function(){
    ymaps.ready(init);
    function init () {
        var _element = jQuery('#map-box');
        var _lat = _element.attr('data-lat');
        var _lng = _element.attr('data-lng');
        var myMap = new ymaps.Map('map-box', {
                center: [_lat, _lng], 
                zoom: 10
        });
        // Создаем метку и задаем изображение для ее иконки
        myPlacemark = new ymaps.Placemark([_lat, _lng], {}, {
            iconImageHref: '/img/layout/business_centers_ico.png', // картинка иконки
            iconImageSize: [36, 36], // размеры картинки
            iconImageOffset: [0, -18] // смещение картинки
        });
        // Добавление метки на карту
        myMap.geoObjects.add(myPlacemark);        
    }
})