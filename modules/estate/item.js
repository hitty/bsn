jQuery(document).ready(function(){    
    
    if( jQuery('.gallery-wrapper .photos').length > 0 ){
    
        jQuery('.photos').Carousel({
            loop: true,
                    margin: 10,
                    responsiveClass: true, 
                    responsive: {
                      0: {
                        items: 4,
                        nav: true,
                        loop: false,
                        margin: 10
                      }
                    }
        })
    }
    
});