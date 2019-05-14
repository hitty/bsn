jQuery(document).ready(function(){
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    
    function getApplicationsList(_page){
        if(typeof _page === undefined) _page = 0;
        var _params = {
            estate_type:jQuery('#filter_estatetype').val(),
            deal_type:jQuery('#filter_dealtype').val(),
            page:_page
        }
        var _url = jQuery('#members-h2').length == 0 ? '/applications/public_list/' : jQuery('.filter span.active').data('param')
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url, data: _params,
            success: function(msg){
                if(msg.ok){
                    jQuery('#ajax-search-results').fadeOut(100,function(){
                        jQuery('#ajax-search-results').html(msg.html).fadeIn(200);
                    });
                    jQuery('.sb-info').html(msg.results);
                    
                }
            }
        })
    }
    //паджинатор          
    jQuery(document).on('click','.paginator span', function(){
        $(this).addClass('active').siblings('span').removeClass('active');
        _page = $(this).data('link');
        getApplicationsList(_page);
        jQuery(document).scrollTop(jQuery('.central-column').offset().top-25);
        return false;
    });
    //по изменению фильтра корректируем выдачу
    jQuery('.list-selector').change(function(){
        getApplicationsList();
    });
    
    if( jQuery('#members-h2').length == 0 ) getApplicationsList();
    
    //комментарии
    jQuery('#ajax-search-results').on('click', '.comment span', function(){
        jQuery(this).toggleClass('active').siblings('span').toggleClass('active');
        jQuery('.user-comment', jQuery(this).closest('.item')).toggleClass('active')
    })
    
    // оплата заявка
    jQuery(document).on('click','.app-choosing-wrapper button',function(){
        var _public_list = jQuery(this).hasClass('public');
        var _elem = jQuery(this);
        var _cost = parseInt(_elem.data('cost'));
        if(_elem.parent().attr('class') == 'exclusive') jQuery('.app-choosing-wrapper').attr('data-url', _elem.closest('.app-choosing-wrapper').data('url').replace('in_work','in_work_exclusive'));
        _url = jQuery('.app-choosing-wrapper').attr('data-url');
        buyApplication(_url, _cost);
    }); 
    
    //бесплатная заявка
   jQuery(document).on('click','#ajax-search-results .free-for-payed',function(){
       var _url = '/applications/in_work/' + jQuery(this).closest('.item').attr('id') + '/'
       buyApplication(_url, 0);
   })
    
    function buyApplication(_url, _cost){
        if(_cost > 0) if(!confirm("С вашего счета будет списано " + _cost + " рублей. Продолжить?")) return false;
        
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url,
            data: {ajax: true},
            success: function(msg){
                if(msg.ok){
                    //если все хорошо, щелкаем по вкладке "В работе", если баланса не хватает - редирект на оплату
                    if(msg.pay_result){
                        //если это не лк, просто удаляем строку
                        window.location.href = '/members/conversions/applications/';
                        
                        
                    } 
                    else{
                        if(msg.late) alert('Эта заявка уже взята в работу');
                        else{
                            if(msg.cannot_buy) alert('Заявки могут покупать только специалисты');
                            else{
                                alert('Вашего баланса не хватает для оплаты');
                                if(window.location.href.match(/members/) == null) window.location.href = window.location.href.replace('/applications/','/members/pay/balance/');
                                else window.location.href = '/members/pay/balance/';
                            } 
                        } 
                    } 
                }
                else alert('Internal error');
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                return false;
            },
            complete: function(){
            }
        });        
    }
});