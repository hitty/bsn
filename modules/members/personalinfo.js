jQuery(document).ready(function(){
  
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive(
            {
                'queueSizeLimit'  : 1,
                onChangeCount: function(){
                    if(jQuery('#personalinfo').length > 0){
                       var _photos_count = parseInt(jQuery('.totalObjects').text());
                       jQuery('.user-avatar', jQuery('.left-column')).toggleClass('active');
                       jQuery('.user-avatar-small', jQuery('#userinfo-wrap')).toggleClass('active');
                       if(_photos_count == 1){
                            /* 
                            var _src = jQuery('.boxcaption_main').siblings('.itemsContainer').children('img').attr('src'); 
                            jQuery('.user-avatar .avatar', jQuery('.left-column')).attr('src', _src.replace('/sm/','/big/'));
                            jQuery('.user-avatar-small .avatar', jQuery('#userinfo-wrap')).attr('src', _src.replace('/sm/','/med/'));
                            */
                       } else {
                            jQuery('.user-avatar .avatar', jQuery('.left-column')).attr('src', '/img/layout/no_avatar_med.gif');
                            jQuery('.user-avatar-small .avatar', jQuery('#userinfo-wrap')).attr('src', '/img/layout/no_avatar_sm.gif');
                       }
                    }
                }
            }
        );
    }
    
    
    jQuery('input', jQuery('.notifications_list')).on('click', function(){
        var _this = jQuery(this);
        jQuery('input#' + _this.attr('name') + '[type=hidden]').val( _this.parent().hasClass('on') ? 2 : 1 );
        
    })
    jQuery('input[name=sex]', jQuery('#simple-form')).on('change', function(){
        jQuery('#sex-select').toggleClass('female');
        if(jQuery('#personalinfo').length > 0) jQuery('.members-menu .user-info span, .auth-menu-links li a.user-info span').toggleClass('female');
    })
    
})   
     

