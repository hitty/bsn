jQuery(document).ready(function(){
     _current_index = 1;
    jQuery('.slide-photogallery').each(function(){
        var _wrap = jQuery(this);
        var _total_indexes = parseInt(jQuery('img',_wrap).length);
        galleryHeight(jQuery('img', _wrap).first());
        console.log(_total_indexes)
        jQuery('.arrow-right',_wrap).on('click',function(){
            _current_index++;
            if(_current_index>_total_indexes) _current_index = 1;
            changePhoto(_current_index,_wrap);
            
        })
        jQuery('.arrow-left',_wrap).on('click',function(){
            _current_index --;
            if(_current_index<=0) _current_index = _total_indexes;
            changePhoto(_current_index,_wrap);
        })
            
        function changePhoto(_index,_current_wrap){
           galleryHeight(jQuery('img[data-pos='+_index+']',_current_wrap))
            jQuery('img[data-pos='+_index+']',_current_wrap).addClass('active').siblings('img').removeClass('active');
            jQuery('.counter .item[data-pos='+_index+']',_current_wrap).addClass('active').siblings('span').removeClass('active');
            
            
        }
        function galleryHeight(_el){
            jQuery('.slide-photogallery .photos').height(_el.height())
        }
    })

})
    
    
