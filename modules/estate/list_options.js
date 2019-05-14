jQuery(document).ready(function(){
    var _def_val = getBSNCookie('View_count_estate');
    var _def_type = getBSNCookie('View_type');
    if(!_def_type) {
        _def_type = 'list';
        setBSNCookie('View_type', _def_type, 20, '/');
    }
    if(_def_val) jQuery('#count_selector .list-data li[data-value="'+_def_val+'"]').click();
    jQuery(document).on('change',"#count_selector", function(event, value){
        setBSNCookie('View_count_estate', value, 20, '/');
        window.location.href = window.location.href;
    });
    jQuery(document).on('change',"#sort_selector",function(event, value){
       window.location.href = jQuery(this).children('.list-data').data('link') + jQuery(this).children('input').val();
    })

    jQuery('.ajax-offers-container span').each(function(e){
        jQuery(this).on('click',function(){
            var _this = jQuery(this);
            if(jQuery(this).hasClass('active')) return false;
            getPendingContent('#ajax-search-results',_this.data('url'), false, false, 'white_wrap');
            _this.addClass('active').siblings('span').removeClass('active');
        })
    })
    

    
    //записываем переход с поиска в карточку + показать одинаковые объекты
    jQuery(document).on('click', '.estate-list .item .expand, .estate-list .item .hide', function(){
        var _this = jQuery(this);
        var _parent = _this.parents('.item');        
        if(!_parent.hasClass('with-variants')){
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url:  _this.attr('data-link') + 'from_search/',
                data: {ajax: true},
                success: function(_data){
                    return true;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                }
            });
        } else {
            _parent.toggleClass('active');
            var _variants_wrap = _parent.find('.variants-wrap');
            _this.siblings('.total-variants-wrap').toggleClass('active');
            _variants_wrap.toggleClass('active');
            var _list_url = _parent.parents('.estate-list').data('url');
            _url = _list_url + (_list_url.indexOf('?')>0 ? '&' : '?') + 'exclude_id=' + _parent.data('id') + '&group_id=' + _parent.data('group-id') + '&count=10&new_groups=true';
            if(typeof _parent.data('rooms') == 'number') _url = _url + '&rooms=' + _parent.data('rooms');
            if(_parent.hasClass('active') && _variants_wrap.children('.estate-list').length==0){
                _variants_wrap.addClass('waiting');                                             
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url:  _url,
                    data: {ajax: true},
                    success: function(msg){
                        if(msg.ok) _variants_wrap.removeClass('waiting').html(msg.html).slideDown(0);
                        return true;
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        return false;
                    }
                });
                
            } else if(jQuery('#left-column #estate-search').length == 0) _variants_wrap.slideToggle(300);
            return false;
        }    
    });
    jQuery(document).on('click', '.estate-list .item,.balloon-inner .item', function(e){
        if( typeof jQuery(this).data('link') != 'undefined' ) {
            if( !( jQuery(e.target).closest('.variants-wrap').length > 0 || jQuery(e.target).closest('.total-variants-info').length > 0 || jQuery(e.target).hasClass('star') ) && jQuery(this).data('link').length > 0) {
                window.open(jQuery(this).data('link')); 
                return false;
            } else if( jQuery(e.target).closest('div').attr('class') == 'item br3' && !(jQuery(e.target).hasClass('star')) ) {
                window.open(jQuery(this).data('link')); 
                return false;
                
            }
        }
    })
    if(jQuery('.estate-list .item')){
        jQuery(document).on({
            mouseenter: function () {
                var _img = jQuery(this).data('img-src');
                jQuery(this).parents('.variants-wrap').parents('.item').find('.photo').attr('style', 'background-image:url('+_img+')').addClass('fixed')
            },
            mouseleave: function () {
                jQuery('.estate-list .item .photo').removeClass('fixed')
            }
        }, '.estate-list .variants-wrap .item');
    }
});