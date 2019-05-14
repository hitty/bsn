jQuery(document).ready(function(){
    ///zhiloy_kompleks/block/rating_mainpage/region/
    jQuery( '.housing-estates-rating-inner .item').each( function(){
        jQuery(this)
        .on('mouseover', function(){
            if( jQuery(this).hasClass( 'right' ) ){
                jQuery(this).siblings( '.item' ).css({'opacity':'0.2'}).siblings( '.item.right' ).css({'opacity':'1'});
            } else {
                jQuery(this).siblings( '.item' ).css({'opacity':'1'}).siblings( '.item.right' ).css({'opacity':'0.2'});
            }
        })
        .on('mouseleave', function(){
            jQuery( '.housing-estates-rating-inner .item').css({'opacity':'1'});
        })
    })
    
    _filter_trigger = false;
    jQuery('.filter.select-class span').on('click', function(){
        buildList();
        setTimeout(manageRatingItems, 500);
    })

    jQuery( '.list-selector', jQuery( '.filters-wrap' ) ).each(function(){
        jQuery(this).on( 'change', function(){
            if( jQuery(this).hasClass('select-region') ){
                jQuery( 'li:first', jQuery( '.list-selector.select-district' ) ).click();
                jQuery( 'li', jQuery( '.filters-wrap .list-selector.select-district .list-data' ) ).hide();
                var _region = jQuery( 'input[name=region]' ).val();
                if( _region == '' ) jQuery( '.rating-mainpage .filters-wrap .list-selector.select-district' ).fadeOut(200);
                else {
                    jQuery( '.district' + _region ).addClass( 'active' ).siblings( 'span' ).removeClass( 'active' );
                    jQuery( '.rating-mainpage .filters-wrap .list-selector.select-district' ).fadeIn(200).find( 'li[data-region=' + _region + ']' ).show();
                }
            }    
            buildList();
            setTimeout( manageRatingItems, 500 );
        })
    })
    
    manageRatingItems();
    function manageRatingItems(){
        //стиль показа сниппетов ЖК
        jQuery('.housing-estates-rating-inner').each(function(){ 
            var _wrap = jQuery(this);
            var _parent_offset_top = parseInt(_wrap.offset().top);
            var _parent_height = parseInt(_wrap.height());
            var _index = 0;
            var _total_items = parseInt(jQuery('.item', _wrap).length);
            var _item_height = 80;
            var _ids = new Array();
            jQuery('.item', _wrap).each(function(){
                _index++;
                var _this = jQuery(this);
                var _top = parseInt(_this.offset().top) - _parent_offset_top;
                var _bottom = _parent_height - _top - _item_height;
                var _el = _this.find('.housing-estate-item');
                var _el_height = parseInt(_el.height()) ;
                console.log( _this )
                if(_el_height > _bottom){
                    var _el_bottom = parseInt(  ( _total_items  - _index) / 2 ) * _item_height;
                    _el.css({'bottom' : '-' + _el_bottom + 'px', 'top' : 'auto'});
                    jQuery('.arrow', _el).css({'bottom' : _el_bottom + _item_height/2 + 'px', 'top' : 'auto'});
                } else if(_top < 80) {
                    var _el_top = parseInt( (_index/2 - 1) ) * _item_height;
                    console.log( _index )
                    _el.css({'top' : '-' + _el_top + 'px'});
                    jQuery('.arrow', _el).css({'top' : _el_top + _item_height/2 + 'px'});
                }
                _ids.push(_this.data('id'));
            }) 
            
            //отображение на карте
            if( jQuery(this).closest('.tab.active' ).length > 0 &&  jQuery('#map-search-results').length > 0) pendingMapPoints( '/zhiloy_kompleks/?map_mode=true&ids=' + _ids.join(','), {ajax: true, map: true} );
        })
    }
    function buildList(){
        var _params = new Array();    
        jQuery( 'input.filter-value' ).each( function(){
            var _this = jQuery(this);
            var _value = parseInt( _this.val() );
            if( _value > 0 ) _params.push( _this.attr('name') + '=' + _value )        
        })
        if( _params.length > 0 ) getPendingContent( '#housing-estates-rating-block', '/zhiloy_kompleks/block/rating_mainpage/region/?' + _params.join('&') )
    }
    
})