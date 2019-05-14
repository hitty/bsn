jQuery(document).ready(function(){
    var _slider_index = 1;
    
    var _fixed_wrap = jQuery('.new-bsn-fixed');
    var _slides_total = jQuery('.slide', _fixed_wrap).length;
    jQuery('#new-bsn-button', _fixed_wrap).on('click', function(){
        _fixed_wrap.toggleClass('active');
        if( _fixed_wrap.hasClass('active') ) jQuery('body').append('<div id="background-shadow"><div id="background-shadow-bg"></div></div>');
        else {
            setTimeout(function(){
                jQuery('#background-shadow').fadeOut(200, function(){
                    jQuery( '#background-shadow' ).remove()
                })
            }, 100 );
        }
    })    
    _slider_width = jQuery('.slide', _fixed_wrap).width() + 80;
    _slider_count = jQuery('.slide', _fixed_wrap).length;
    jQuery('.arrow', _fixed_wrap).on('click', function(){
        if(jQuery(this).hasClass('left')) _slider_index = _slider_index - 1;
        else _slider_index = _slider_index + 1;
        
        if(_slider_index < 1) _slider_index = 1;
        if(_slider_index > _slider_count) _slider_index = _slider_count;
        
        if(_slider_index == 1)    jQuery('.arrow.left').addClass('inactive');
        else  jQuery('.arrow.left').removeClass('inactive');
        if(_slider_index == _slides_total)    jQuery('.arrow.right').addClass('inactive');
        else  jQuery('.arrow.right').removeClass('inactive');
        jQuery('.slides', _fixed_wrap).css( { 'left' : '-' + (_slider_index - 1 )* _slider_width + 'px' } )
        jQuery('.counter i', _fixed_wrap).text(_slider_index);
        console.log('_slider_count' + _slider_count +  '_slider_index' + _slider_index + '_slider_width' + _slider_width)
    })
    
    jQuery(document).on('click', '#background-shadow-bg', function(){
        if(_fixed_wrap.hasClass('active'))   jQuery('#new-bsn-button', _fixed_wrap).click();
    })
    
});
