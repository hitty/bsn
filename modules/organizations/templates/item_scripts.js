jQuery(document).ready(function(){
        jQuery('.central-column').addClass('handbook-page company');
        jQuery('.main-content .right-column:not(.form-column)').addClass('handbook-page company');
        //при необходимости делаем широким central-column
        if(jQuery('.bordered-wrapper.topblue').hasClass('payed-company-page.company')){
            jQuery('.central-column').addClass('payed');
            jQuery('.main-content .right-column:not(.form-column)').addClass('payed');
        }
        if(jQuery('.description').height()>85){
            jQuery('.description').css('height','85px');
            jQuery('.description').addClass('shortened');
            jQuery('.read-next').offset({ left:jQuery('.read-next').offset().left,top:jQuery('.description.shortened').offset().top+jQuery('.description.shortened').height()-5 });
            jQuery('#application-button').offset({top:jQuery('#application-button').offset().top+10});
        }
        else{
            jQuery('.read-next.dashed-link-blue').remove();
        }
        jQuery('#application-button').offset({ top: jQuery('.specialization').offset().top});
        if(jQuery('.tabs-box').children('.filter').children('span[data-tab-ref=".objects"]').length>0 && jQuery('.tabs-box').children('.filter').children('span[data-tab-ref=".objects"]').children('sup').html().length>0){
            jQuery('.tabs-box').children('.filter').children('span[data-tab-ref=".objects"]').click();
            jQuery('.form-wrap').children('.middle-panel').children('.tab.active').find('button').click();
        }
        jQuery(document).scroll(function(){
            var _right_fixedcolumn_height = parseInt(jQuery('.right-column-fixed').height());
            var _top = parseInt(jQuery(this).scrollTop());
            var _doc_height = jQuery(window).height();
            _footer_top = 0;
            
            if(jQuery('.right-column-fixed').length > 0){
                
                if(jQuery('#middle-bottom-banner').length > 0) _footer_top = parseInt(jQuery('#middle-bottom-banner').offset().top)
                else if(jQuery('footer').length > 0) _footer_top = parseInt(jQuery('footer').offset().top);
            
                if(_top>jQuery('.right-column-fixed').offset().top){
                    //вниз
                    jQuery('.right-column-fixed').children('.application-wrapper').offset({top:Math.min(_footer_top - jQuery('.application-wrapper').height()-80,_top),
                                                                                       right:0});
                }else{
                    //вверх
                    jQuery('.right-column-fixed').children('.application-wrapper').offset({top:jQuery('.estate-highlighted-list').offset().top + jQuery('.estate-highlighted-list').height() + 20,
                                                                                           right:0});
                }
                    
            }
        });
        //если в блоке выделенных разъедутся на две строки тип объекта и площадь, корректируем
        if(jQuery('.estate-highlighted-list').length>0){
            jQuery('.estate-highlighted-list').children('.item').each(function(){
                if(jQuery(this).children('.content').children('.line.object-type').height() > 40){
                    var _block = jQuery(this).children('.content').children('.line.object-type');
                    while(_block.height()>40){
                        _block.children('.object-type').css('font-size',(parseInt(_block.children('.object-type').css('font-size').replace(/[^0-9]/g,'')) - 1) + 'px');
                        _block.children('.full-square').children('b').css('font-size',(parseInt(_block.children('.full-square').children('b').css('font-size').replace(/[^0-9]/g,'')) - 1) + 'px');
                    }
                }
            });
        }
        
        if(jQuery('.tab.objects .fast-search .tab.active button').length > 0){
            jQuery('.tab.objects .fast-search .tab.active button').click();   
        }
    });