jQuery(document).ready(function(){
    getPendingContent(['#mainpage-banner'],   ['/banners/mainpage/new/']);
    jQuery('.filter span').on('click', function(){
        var _this = jQuery(this);
        if(_this.data('link') !=''){
            var _button = jQuery(this).parent().parent().children('.center-button-block');
            _button.attr('data-link', _this.data('link')).children('span').text(_this.data('text'));
        }
        return false;
    })
    
   jQuery('.list-selector', jQuery('.filters-wrap')).each(function(){
        jQuery(this).on('change', function(){
            var _values = new Array();
            jQuery('input', jQuery('.filters-wrap')).each(function(){
                if( jQuery(this).val() != '' ) _values.push( jQuery(this).attr('name') + '=' + jQuery(this).val() );
            });
            _url = '/service/ratings/?mainpage=true' + ( _values.length > 0 ? '&' + _values.join('&') : '');
            getPendingContent('#housing-estates-rating-block', _url);
            
        })
    })
   
});