jQuery(document).ready(function(){
    
    jQuery(document).on("click",".object-unsubscribe", function(){
        var _url = "/objects_subscriptions/unsubscribe/";
        var _id = parseInt( jQuery(this).attr('data-id') );
        var _params = { id: _id };
        var _el = jQuery('.subscriptions-list .item[data-id=' + _id + ']');
        if(!confirm('Вы уверены, что хотите отписаться от рассылки?')) return false;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json',
            cache: false,
            url: _url, data: _params,
            success: function(_list){
                _el.fadeOut(300);
                var _filter = jQuery('.filter.switch-types');
                var _active_element = jQuery('.active', _filter);
                var _count = parseInt( jQuery('sup', _active_element).text() );
                jQuery('sup', _active_element).text( _count - 1 );
                if( _count - 1 == 0 ) _active_element.next('span').click();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert("Error: "+textStatus+" "+errorThrown);    
            },
            complete: function(){
                jQuery('.spinner').hide();
            }
        }); 
        return false; 
    }); 
    
    /*jQuery('.unsubscribe').on('click',function(){
        var title = jQuery(this).parent('td').parent('tr').find('a').html();
        unsubscr_elem = jQuery(this);
        showConfirmWindow("Отписка от поисковой рассылки","Вы уверены, что хотите отписаться от рассылки?<br><strong title='"+title+"'>"+title+"</strong>","Отписаться","Отмена");
        return false;  
    });
    */
    jQuery('.object-new-results').not('.noresults').click(function(){
        var _href = jQuery(this).parent().parent().children('.object-data').children('.central-td-box').children('.object-address-href').attr('href');
        if(_href.length>0) window.open(_href,'_blank');
    });
                                                          
    jQuery('.period-selector').on('change',function(){
        var _el = jQuery(this);
        var _params = { id: _el.data('id'),
                        id_period: _el.children('input').val()};
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', 
            cache: false,
            url: "/objects_subscriptions/period_change/", 
            data: _params,
            success: function(response){
            
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert("Error: "+textStatus+" "+errorThrown);    
            },
            complete: function(){
                jQuery('.spinner').hide();
            }
        });
        return false;   
    });
    jQuery('.period-selector').on('click',function(){
        return false;
    });
    
    jQuery('.object-data .central-td-box .expand').on('click', function(){
        jQuery(this).toggleClass('active');
        jQuery(this).next('div').toggleClass('active');
    })
    
    jQuery('.subscriptions-list .item .confirm').each( function(){
        jQuery(this).on('click', function(){
            const _this = jQuery(this);
            const _id = _this.data('id');
            getPending( '/objects_subscriptions/confirm/', {id:_id}, false, 
                function(){
                    _this.closest('.item').removeClass('inactive');
                }
            )    
        })
    })
});                       