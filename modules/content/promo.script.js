jQuery(document).ready(function(){
    if( jQuery('.promo-menu-wrapper').length > 0 || jQuery('.promo-wrapper').length > 0 ){

        _lock_scroll = false;
        var _menu_wrap = jQuery('.promo-menu-wrapper .menu')
        var _promo_wrap = jQuery('.promo-wrapper')
        var _padding = 20;
        var _header_height = parseInt( jQuery('header').height() ) ;
        var _promo_wrap_height = parseInt( _promo_wrap.height() - _promo_wrap.offset().top ) ;
        var _menu_offset = parseInt( _menu_wrap.offset().top ) ;
        var _menu_height = parseInt( _menu_wrap.height() ) ;
        

        var _items_offsets = [];
        var _active_item = 1;
        setTimeout(function(){
            jQuery('.item', _promo_wrap).each(function(){
                var _this = jQuery(this);
                var _offset = parseInt( _this.offset().top );
                if( _offset > 10 ) _items_offsets[ _this.data('id') ] = parseInt( _this.offset().top );
            })
            
        }, 750)

        //клик по меню
        jQuery('li', _menu_wrap).on('click', function(){
            _lock_scroll = true;
            var _this = jQuery(this);
            _this.addClass('active').siblings('li').removeClass('active');
            
            var _id = _this.data('id');
            jQuery("html,body").animate({ scrollTop: _items_offsets[_id] - _header_height - _padding }, 500, function() {_lock_scroll = false} );
            return false;
        })

        scrollPromoWrappers( jQuery(window) );
        jQuery(window).scroll(function(){
            if( _lock_scroll == false )scrollPromoWrappers( jQuery(this) );
            return false;
        }); 
        
        function scrollPromoWrappers(_this){
            jQuery('.promo-menu-wrapper').height( parseInt( jQuery('.central-column').height() ) - _menu_height )
            var _promo_menu_wrap_height = parseInt( jQuery('.promo-menu-wrapper').height() ) ;
            
            var _top = parseInt(_this.scrollTop());
            //определение активной карточки
            for(i=0;i<_items_offsets.length;i++){
                if( _items_offsets[i]  - _header_height - _padding -1 < _top &&  ( i == _items_offsets.length - 1 || _items_offsets[i+1]  - _header_height - _padding -1 >= _top ) ){
                    if( i != _active_item ){
                        _active_item = i;
                        jQuery('li', _menu_wrap).removeClass('active');
                        jQuery('li[data-id=' + i + ']').addClass('active');
                    }
                }
                
            }  
            //фиксирование меню при прокрутке
            if( _top > _promo_menu_wrap_height) _menu_wrap.addClass('fixed-bottom').removeClass('fixed-top')     ;
            else if( _menu_offset < _top + _header_height + _padding) _menu_wrap.addClass('fixed-top').removeClass('fixed-bottom')     ;
            else _menu_wrap.removeClass('fixed-top').removeClass('fixed-bottom')
        };
    }
    
     jQuery(document).on('click', '.promo-button', function(e){
        var _el = jQuery(this);
        var _params = {id:_el.data('id')};
        getPending('/' + _el.data('type') + '/click/', _params)
            
    });
})
