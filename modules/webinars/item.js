jQuery(document).ready(function(){
    jQuery('button', jQuery('#simple-form')).on('click', function(){
        var _params = {};
        _params['ajax'] = true;
        _error = false;
        jQuery('input', jQuery('#simple-form')).each(function(){
            var _val = jQuery(this).val();
            var _name = jQuery(this).attr('name');
            jQuery(this).removeClass('error');
            if(_val == '' || (_name == 'user_email' && !validateEmail(_val)) ) {
                _error = true;
                jQuery(this).addClass('error');
            }
            _params[_name] = _val;
        })
        if(_error == true) return false;
        _params['id'] = jQuery('#simple-form').data('id');
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: '/webinars/send_message/', data: _params,
            success: function(msg){
                if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                    jQuery('#simple-form').html('<div class="form-title">Спасибо за регистрацию. На ваш Email отправлена информация о вебинаре.</div>');
                    var _users_left = jQuery('.users-left b');
                    var _users_left_val = parseInt(_users_left.text()) - 1;
                    if(_users_left_val == 0) _users_left.parents('.users-left').fadeOut(300);
                    else _users_left.text(_users_left_val);
                    
                    jQuery('.tab.users').append('<span class="user-item"><img src="'+(msg.user["photo"]!=undefined ? '/img/uploads/big/'+msg.user["subfolder"]+'/'+msg.user['photo'] : '/img/layout/no_avatar_med.gif')+'" alt="">'+msg.user['user_name']+'</span>');
                    jQuery('.total-users b').text(parseInt(jQuery('.total-users b').text())+1);
                    
                } else {
                }
                return false;
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
            },
            complete: function(){
            }

        });
        return false;     

    })
});