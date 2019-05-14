jQuery(document).ready(function(){
    _notification_wrap = jQuery('.topmenu .topmenu-firstlevel .notifications')    ;
    if(_notification_wrap.length > 0){
        jQuery('li i', _notification_wrap).on('click', function(){
            var _this = jQuery(this);
            setRead(_this);
            return false;
        })
        
        jQuery('.delete-all', _notification_wrap).on('click', function(){
            jQuery('li i', _notification_wrap).each(function(){
                setRead(jQuery(this));
            })
        })
    }
});
function setRead(_this){
    var _count = _this.data('count');
    var _amount_notification_wrap = jQuery('.amount-total', jQuery('.topmenu .topmenu-firstlevel'))
    var _total_count = parseInt(_amount_notification_wrap.text()) - _count;
    _amount_notification_wrap.text(_total_count);
    if(_total_count == 0) {
        _notification_wrap.addClass('hidden').find('.delete-all').remove();
        _amount_notification_wrap.removeClass('active');
    }
    else {
        _notification_wrap.removeClass('hidden');
        _amount_notification_wrap.addClass('active');
    }
    var _li = _this.parent('li');
    _li.fadeOut(300);
    getPending('/notifications/setread/', {id:_li.data('id'), type: _li.attr('class').replace(' internal-link','')})
}
