jQuery(function(){    //datepicker init
    var _wrap = jQuery('.search-form-container')
    var _form = jQuery('form[name=search]');
    var _container = '.search-form-container .ajax-search-results';
    var _input = jQuery('#search-line input[name=query]', _wrap)
    
    jQuery('.search-button').on('click', function(){
        _wrap.addClass('active');
        jQuery('body').css({'position':'fixed'});
        _input.focus();
        jQuery('header,.content-mainpage,#left-column,#right-column').addClass('blured');
    })
    jQuery('.closebutton', _wrap).on('click', function(){
        _wrap.removeClass('active');
        jQuery('body').css({'position':'relative'});
        jQuery(_container).html('');
        _input.val('');
        jQuery('header,.content-mainpage,#left-column,#right-column').removeClass('blured');
    })
        
    a = new Date();
    _input.on('keyup', function(e){
        clearTimeout(jQuery.data(this, 'timer'));
        if (e.keyCode == 13) search(true);
        else if (e.keyCode == 27) jQuery('.closebutton', _wrap).click();
        else jQuery(this).data('timer', setTimeout(search, 500));
    })
    function search(force){
        var _val = _input.val();
        if (!force){
            if( _val.length > 1){
                var _url = '/search/?query=' + _val + '&sort=date&type=' + jQuery('input[name=content_type]').val();
                getPendingContent(_container, _url, {}, false, 'none')
            } else jQuery(_container).html('');
        }
    }
    
    jQuery('form', jQuery('#search-line')).on('submit', function(){
        return false;
    })
    
});
