var _template = '<div id="background-shadow-expanded">'
                    +'<div id="background-shadow-expanded-wrapper"></div>'
                    +'<form class="balance-manage-form" action="" method="POST">'+
                        '<span class="form-title">Баланс <b></b></span><a class="closebutton"></a>'+
                        '<span class="user-balance">Текущий баланс: <b></b> руб.</span>'+
                        '<div class="balance-fields"><span>Внести</span><span class="increase-money"><i></i><input type="number" class="digit" name="increase-money" id="increase-money" value="0" autocomplete="off"/></span></div>'+
                        '<div class="balance-fields"><span>Снять</span><span class="decrease-money"><i></i><input type="number" class="digit" name="decrease-money" id="decrease-money" value="0" autocomplete="off"/></span></div>'+
                        '<span class="company-balance">Баланс компании: <b></b> руб.</span>'+
                        '</form>'+
                        '<div class="button-container"><div class="balance-manage-form-button"><button class="green send" value="Подтвердить изменения">Подтвердить изменения</button></div></div>'
                        
                +'</div>';    
_this_balance_wrap = '';
_d_company_balance = 0;
jQuery(document).ready(function(){
    var _company_balance = _d_company_balance = parseInt(jQuery('.user-info .balance b').text().replace(' ',''));
    jQuery('.info .balance i').on('click',function(){ 
        
        _this_balance_wrap = jQuery(this);
        jQuery('body').append(_template);
        _user_balance = _d_user_balance = parseInt(jQuery(this).siblings('b').text().replace(' ',''));
        _user_name = jQuery(this).parents('.balance').parents('.info').siblings('.manage').children('.name').text();
        _user_id = jQuery(this).parents('.balance').parents('.info').parents('.item').data('id');
        jQuery('.user-balance b', jQuery('#background-shadow-expanded')).text(_user_balance)
        jQuery('.company-balance b', jQuery('#background-shadow-expanded')).text(_company_balance)
        jQuery('.form-title b', jQuery('#background-shadow-expanded')).text(_user_name)
        _popup_order = true;
        jQuery('#background-shadow-expanded').fadeIn(100);
        setTimeout(function(){
            jQuery('#background-shadow-expanded .balance-manage-form').addClass('active');
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 27: jQuery(".closebutton", "body").click();  break;     // esc
                    case 13: jQuery("#background-shadow-expanded .balance-manage-form .send", "body").click(); break;             // enter
                }
            });           
        }, 200);

    }) 
    jQuery(document).on("click", ".closebutton, #background-shadow-expanded > #background-shadow-expanded-wrapper",function(){ 
        jQuery('#background-shadow-expanded .balance-manage-form').removeClass('active');
        setTimeout(function(){
             jQuery('#background-shadow-expanded').fadeOut(100);
             jQuery( "#background-shadow-expanded" ).promise().done(function() {
                jQuery( "#background-shadow-expanded" ).remove();
             });
        }, 350);
    });    
    _active_input = '';
    jQuery(document).on('click', '.increase-money i, .decrease-money i', function(){
        jQuery(this).siblings('input').attr('disabled', false).focus();
        if(parseInt( jQuery(this).siblings('input').val()) ==0 ) jQuery(this).siblings('input').val('');
        var _id = jQuery(this).parents('span').attr('class') == 'increase-money'?'decrease-money':'increase-money'
        if(_active_input != _id){
            _active_input = _id;
            jQuery('#'+ _id).attr('disabled', 'disabled').val(0);
            if(_company_balance <0 ) return false;
            jQuery('#background-shadow-expanded .company-balance b, .user-info .balance b').text(_company_balance);
            jQuery('.user-balance b', jQuery('#background-shadow-expanded')).text(_user_balance)
            jQuery('#background-shadow-expanded .balance-manage-form .user-balance').removeClass('decrease').removeClass('increase');
        }
    })
    _dd_money_increase = _dd_money_decrease = 0;
    jQuery(document).on('keyup', '#increase-money,#decrease-money', function(e){
        console.log(e.keyCode)
        var _id = jQuery(this) .attr('id');
        _d_company_balance = _company_balance;
        if(jQuery(this).val().length==0) {
            var _money = 0;
            _d_user_balance = _user_balance
            
        } else var _money = parseInt(jQuery(this).val()); 
        var _sum = jQuery(this).val();
        if( _id == 'increase-money'){
            if(_d_company_balance - _money < 0){
                jQuery('#increase-money').val(_dd_money_increase)
                return false;
            }         
            if(_money > 0)_dd_money_increase = _money;
            console.log(_money)
            if(_money > 0) jQuery('#background-shadow-expanded .balance-manage-form .user-balance').removeClass('decrease').addClass('increase');
            _dd_company_balance = _d_company_balance - _money;
            _dd_user_balance = _d_user_balance + _money;
        } else if(_id == 'decrease-money'){
            if(_d_user_balance - _money < 0){
                jQuery('#decrease-money').val(_dd_money_decrease)
                return false;
            }       
            console.log(_money)
            if(_money > 0)_dd_money_decrease = _money;
            if(_money > 0) jQuery('#background-shadow-expanded .balance-manage-form .user-balance').removeClass('increase').addClass('decrease');
            _dd_company_balance = _d_company_balance + _money;
            _dd_user_balance = _d_user_balance - _money;
        }
        jQuery('#background-shadow-expanded .company-balance b').text(_dd_company_balance);
        jQuery('.user-balance b', jQuery('#background-shadow-expanded')).text(_dd_user_balance)
    });
    jQuery(document).on("click", ".balance-manage-form-button button", function(){
        var _summ = 0;
        var _increase_money = parseInt(jQuery('#increase-money').val());
        var _decrease_money = parseInt(jQuery('#decrease-money').val());
        if(_increase_money > 0) {
            _company_balance = _company_balance - _increase_money;
            if(_company_balance <0) return false;
            _summ = _increase_money;
            _action = 'increase';
            _user_balance = _user_balance + _summ;
        } else if(_decrease_money > 0){
                _company_balance = _company_balance + _decrease_money;
                if(_company_balance <0) return false;
                _summ = _decrease_money;
                _action = 'decrease';
                _user_balance = _user_balance - _summ;
        }
        if(_summ>0 && _user_balance>=0 && _company_balance>=0){
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/members/office/staff/balance_manage/',
                data: {ajax: true, type: _action, summ: _summ, id: _user_id},
                success: function(msg){
                    jQuery('.notification').remove();
                    _notification = jQuery('<div class="notification msgsuccess">'+(_action == 'increase'?'Баланс сотрудника'+_user_name+' успешно пополнен на '+_summ+' руб.':'С баланса сотрудника'+_user_name+' списано '+_summ+' руб.')+'</div>');
                    jQuery('.central-column').prepend(_notification);
                    
                    jQuery('#background-shadow-expanded .company-balance b, .user-info .balance b').text(_company_balance)
                    jQuery('.user-balance b', jQuery('#background-shadow-expanded')).text(_user_balance)
                    _this_balance_wrap.siblings('b').text(_user_balance + ' руб.')
                    jQuery('#increase-money').val('0');
                    jQuery('#decrease-money').val('0');

                    jQuery(".closebutton", "body").click();
                    setTimeout(function(){
                        _notification.remove();
                    }, 4000)

                }
            })
            
        }
        return false;
    })
    
    jQuery('.invite-staff', jQuery('#staff-edit')).on('click', function(){
        if(jQuery(this).hasClass('inactive')) return false;
        var _email = jQuery('#find-email').val();
        if(_emailTest.test(_email) == false) jQuery('#find-email').addClass('red-border');
        else jQuery('#find-email').removeClass('red-border');
        
        var _action_wrap = jQuery('.action-wrap', jQuery('#staff-edit'));
        var _comment_wrap = _action_wrap.next('.comment'); 
        _action_wrap.html('');
        _comment_wrap.text('');
        jQuery('#staff-edit').removeClass('edit').addClass('add');
        jQuery('#office-save').attr('data-id', '0');
        jQuery('#office-save').addClass('disabled');
        
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/members/office/staff/invite/',
            data: {ajax: true, email: _email},
            success: function(msg){
                jQuery('#email').val(_email);
                if(msg.action == 'user_new') {
                    _comment_wrap.text('Пользователей с таким email не найдено. Добавляйте нового.');
                    jQuery('#staff-edit').addClass('edit').removeClass('add');
                    jQuery('#office-save').removeClass('disabled');
                } else if(msg.action == 'user_alredy_in_agency') _comment_wrap.text('Вы не можете прикрепть данного пользователя. Он агент другого агентства.');
                else if(msg.action == 'user_exists') {
                    jQuery('#office-save').attr('data-id', msg.id);
                    _action_wrap.html('<div class="img"><img src="/'+ msg.photo +'" alt="" /><span class="title">'+msg.name+'</span></div>')
                    _comment_wrap.text('Нажмите «Сохранить анкету» чтобы пригласить данного пользователя.');
                    jQuery('#office-save').removeClass('disabled');
                } else {
                    _comment_wrap.text('Произошла ошибка. Попробуйте повторить данное действие позже'); 
                }
            }
        })
        
    })
    jQuery('.hire-staff', jQuery('#staff-edit')).on('click', function(){
        if(!confirm('Вы уверены, что нужно хотите отвязать сотрудника?')) return false;
        var _id = jQuery(this).data('id');
        
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/members/office/staff/hire/',
            data: {ajax: true, id: _id},
            success: function(msg){
                if(msg.ok)  document.location.href = "/members/office/staff/"
            }
        })
        
    })    
    //Управление кнопками сохранения
    jQuery('#office-save').on('click', function(){
        //добавление нового агента
        if(parseInt(jQuery(this).attr('data-id')) > 0 ){
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/members/office/staff/invite/add/',
                data: {ajax: true, id: jQuery(this).attr('data-id')},
                success: function(msg){
                    if(msg.ok)  document.location.href = "/members/office/staff/"
                }
            })
        
        }  else { // запрос формы
            jQuery('#simple-form').submit();
        }
    })
    
    if(jQuery('.invite-staff', jQuery('#staff-edit')).length > 0){
        var _invite_button = jQuery('.invite-staff', jQuery('#staff-edit'));
        jQuery('#find-email').on('keyup', function(){
            if(_emailTest.test(jQuery(this).val()) != false) {
                _invite_button.removeClass('inactive');
                
            } else {
                _invite_button.addClass('inactive');
                
            }
        })
    }
    //статус
    jQuery('.status-switcher i').on('click', function(){
        jQuery(this).toggleClass('active');
        jQuery('#status').val(jQuery(this).hasClass('active') ? 1 : 2);
    })
});