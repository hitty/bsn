jQuery(document).ready(function(){
    getPendingContent(".webinars-block.list-all",window.location.href.replace(/\#.*$/,'') + "webinars_list/",false,false,false,false);
    _opened_listelector = null;
    jQuery(document).on('click','.webinar-item',function(e){
        var _target_elem = jQuery(e.target);
        if(_target_elem.hasClass('item_img') || _target_elem.hasClass('item_info-reg')){
            jQuery(this).find('.item_info-title').click();
        }
    });
    //сортировки списка
    if(jQuery('.webinars-block.list-all').length > 0){
        //строка сортировок
        jQuery('.webinars-box.list-all .sorting-box .sorting span').on('click', function(){
            var _val = 1;
            jQuery(this).addClass('active').siblings('span').removeClass('active').removeClass('up').removeClass('down');
            
            if(jQuery(this).hasClass('down') || !(jQuery(this).hasClass('up'))) {
                jQuery(this).removeClass('down').addClass('up');
                _val = jQuery(this).data('down-value');
            } else {
                jQuery(this).removeClass('up').addClass('down')
                _val = jQuery(this).data('up-value');
            }
            
            getPendingContent(".webinars-block.list-all",window.location.href.replace(/\#.*$/,'') + "webinars_list/?sortby=" + _val,false,false,false,false);
            return false;
        });
        jQuery('.dashed-link-blue').first().click();
    }
});