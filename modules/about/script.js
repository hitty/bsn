jQuery(document).ready(function(){
    var _menu = jQuery('.menu');
    var _slogans = jQuery('.slogans');
    jQuery('div', _menu).on('click', function(){
        jQuery(this).addClass('active').siblings('div').removeClass('active');
        var _id = jQuery(this).data('id');
        jQuery('.bottom', _slogans).removeClass('bottom');
        jQuery('.active', _slogans).removeClass('active').addClass('bottom');
        jQuery('[data-id=' + _id + ']', _slogans).addClass('active');
        setTimeout(function(){
            jQuery('.bottom', _slogans).removeClass('bottom');    
        }, 500)
    })
})