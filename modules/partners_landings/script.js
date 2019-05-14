jQuery(document).ready(function(){
    ymaps.ready(function () {
        var _element = jQuery('#map-box');
        var _lat = _element.data('lat');
        var _lng = _element.data('lng');
     
        myMap = new ymaps.Map('map-box', {
                center: [_lat, _lng], 
                zoom: 14
        });
        

        // Создаем метку и задаем изображение для ее иконки
        placemark = new ymaps.Placemark([_lat, _lng], {
           
            iconCaption: _element.data('address')
        }, {
            preset: 'islands#blueCircleDotIconWithCaption'
        });
        myMap.geoObjects.add(placemark); 
        myMap.behaviors.disable('scrollZoom');  
    });  

    jQuery('.photos').owlCarousel({
        loop: true,
                margin: 10,
                responsiveClass: true, 
                responsive: {
                  0: {
                    items: 2,
                    nav: true,
                    loop: false,
                    margin: 10
                  }
                }
    })

    $('[data-fancybox="images"]').fancybox({
         margin : [44,0,22,0],
  thumbs : {
    autoStart : true,
    axis      : 'x'
  }
    })
    
   
    jQuery('.dashed-link-blue').on('click', function(){
        var _top = jQuery( jQuery(this).data('element') ).offset().top - 20;
        $("html,body").animate({ scrollTop: _top }, "slow");    
        return false;
    })
    
});