jQuery(document).ready(function(){
    /* галерея фоток объекта */
    var _active = 0;
    var _photos_list = {};
    var _wrap =  jQuery('.gallery-box');
    jQuery('.thumbs-list a',_wrap).each(function(index){
          _photos_list.pop(jQuery(this).attr('href'));
    })
    jQuery('.gallery-box .thumbs-list a').click(function(){
        var _el = jQuery(this);
        var gallery_wrapper = _el.parents('.gallery-box');
        var _src = _el.attr('href');
        var _wrapper = jQuery(".big-image", gallery_wrapper);
        var _img = jQuery("img", _wrapper);
        if(_img.attr('src')==_src) return false;
        _el.addClass('active').siblings('a.active').removeClass('active');
        _wrapper.addClass('waiting');
        _img.fadeOut(300,function(){
            _img.one('load',function(){
                _img.fadeIn(300,function(){ _wrapper.removeClass('waiting'); });
            }).attr('src',_src);
        });
        return false;
    });
});
