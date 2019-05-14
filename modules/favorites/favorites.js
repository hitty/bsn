jQuery(document).ready(function(){
    _menu_el = jQuery('.topmenu .topmenu-firstlevel li.favorites a');
    _menu_favorites_amount = parseInt(  _menu_el.text() );

    jQuery(document).on("click", ".star", function(){
       moveToFavorites(jQuery(this))
    })
    //удаление из избранного для ЖК, КП, БЦ (старый дизайн)
    jQuery(document).delegate(".del-from-favorites.old", "click", function(event){
        event.stopPropagation();
        var _el = jQuery(this);
        var tr = _el.parent().parent('td').parent('tr');
        if(tr.length == 0)
            tr = _el.parent().parent().parent('td').parent('tr');
        var _type = _el.attr('data-type');   
        var _params = { id: _el.attr('data-id'),
                        type: _type};
        var _url = "/favorites/unclick/";
        if(!confirm("Вы уверены, что хотите удалить объект из Избранного?")) return false;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url,
            data: _params,
            success: function(msg){
                tr.hide('slow', function(){
                    //если объектов больше нет, сразу перезагружаем страницу
                    if(jQuery('.tablesorter').length == 1){
                        window.location.reload();
                        return;
                    }
                    var tbody =  tr.parent('tbody');
                    var _tablesorter = jQuery(this).parent().parent();
                    //количество элементов в текущей закладке
                    var tab_amount = parseInt(jQuery('#objects-list-title>.active:first span sup').html(),10) - 1;
                    //удаляем строку (false, чтобы остаться на той же странице паджинатора)
                    _tablesorter.DataTable().row(jQuery(this)).remove().draw(false);
                    if (tab_amount==0)
                        jQuery('#objects-list-title>.active:first').remove();
                    jQuery('#objects-list-title>.active:first span sup').html(tab_amount);
                    //если убрали все из раздела, переходим на первый
                    if (tbody.children('.odd').children('.dataTables_empty').length!=0){
                        tbody.parent('table').remove();
                        
                        var vis = jQuery('#objects-list-title li');
                        if (vis.length==0){
                            jQuery('.favorites-container #objects-list-title').remove();
                            jQuery('.favorites-container .middle-panel').remove();
                            jQuery('.favorites-container').append('<p>На данный момент в избранном записей нет.</p>');
                        } else {
                            jQuery('#objects-list-title li:first').click();
                        }                       
                    }
                    var fa = jQuery('.favorites').children('.amount');   
                    var amount = fa.html();
                    amount--;                                                    
                    if (amount==0)
                        amount="";
                    else
                        amount = parseInt(fa.html(),10)-1;
                    fa.html(amount);
                });
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert(errorThrown);
            }
        });
    });
    
    jQuery(document).delegate(".del-from-favorites", "click", function(event){
        if(jQuery(this).hasClass('old')) return false;
        event.stopPropagation();
        var _el = jQuery(this);
        var tr = _el.parents('.item');
        var _type = _el.attr('data-type');   
        var _params = { id: _el.attr('data-id'),
                        type: _type};
        var _url = "/favorites/unclick/";
        if(!confirm("Вы уверены, что хотите удалить объект из Избранного?")) return false;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url,
            data: _params,
            success: function(msg){
                tr.hide('slow', function(){
                    //если объектов больше нет, сразу перезагружаем страницу
                    if(jQuery('.middle-panel').children().length == 1){
                        window.location.reload();
                        return;
                    }
                    var tbody =  tr.parent('.estate-list');
                    
                    //количество элементов в текущей закладке
                    var tab_amount = parseInt(jQuery('#objects-list-title li.active sup').html(),10) - 1;
                    var active_head = jQuery('#objects-list-title li.active');
                    //текущая вкладка
                    var active_tab = jQuery(active_head.attr('data-tab-ref'));
                    
                    //если убрали все из раздела, переходим на первый
                    if (tab_amount == 0){
                        active_tab.remove();
                        active_head.remove();
                        //если вкладки остались, переходим на первую
                        var vis = jQuery('.tab');
                        if (vis.length==0){
                            jQuery('.favorites-container .middle-panel').remove();
                            jQuery('.favorites-container').append('<p>На данный момент в избранном записей нет.</p>');
                        } else {
                            jQuery('#objects-list-title li:first').click();
                        }                       
                    }
                    else jQuery('#objects-list-title li.active sup').html(tab_amount);
                    
                    var fa = jQuery('.favorites').children('.amount');   
                    var amount = fa.html();
                    amount--;                                                    
                    if (amount==0)
                        amount="";
                    else
                        amount = parseInt(fa.html(),10)-1;
                    fa.html(amount);
                });
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert(errorThrown);
            }
        });
    });
    
    //при наличии tablesorter (старый дизайн), инициализируем его
    if(jQuery('.tablesorter').size()>0){
        _options = 
         {
            "sDom": '<"top"<"list-title">irl>tp<"clear">',
            "searching": false,
            "stateSave": false,
            "pagingType": "full_numbers",
            "aLengthMenu": [15,30,60],
            "order": [[ 0, "desc" ]],
            "language" : {
                        "decimal": ".",
                        "thousands": " "
                    },
            fnDrawCallback: function(){
                //jQuery('.list-title').text("Избранное");
                //просматриавем все паджинаторы и, у кого 1 страница, прячем
                jQuery('.sp-pagination').each(function(){
                    jQuery(this).parent().children('.tablesorter').children('tbody').addClass('short');
                    if (jQuery(this).children('span').children().length == 1){
                        jQuery(this).addClass('hidden');
                        //нижняя граница таблицы - рисуем
                        jQuery(this).parent().children('.tablesorter').children('tbody').addClass('short');
                    }else{
                        jQuery(this).removeClass('hidden');
                        //нижняя граница таблицы - убираем
                        jQuery(this).parent().children('.tablesorter').children('tbody').removeClass('short');
                    }
                });
                jQuery('.object-row').off();
            }                                
        }
        
         if(typeof _add_options == 'object') {
             for(var key in _add_options){
                 _options[key] = _add_options[key]
             }
         }
         jQuery('.tablesorter').DataTable(
            _options
        );
     }
});

function moveToFavorites(_el){
  if (_el.parent('li').hasClass('auth-login-favorites') || _el.hasClass('del-from-favorites'))
   return;
   var type = _el.attr('data-type');   
    var _params = { id: _el.attr('data-id'),
                    type: type};
    var _url = '/favorites/' + ( _el.hasClass('in-favorites') ? 'unclick/' : 'click/' );
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: true,
        url: _url, data: _params,
        success: function(msg){
            if( jQuery('body.members').length > 0) {
                var _active_filter = jQuery('.filter span.active'); 
                _el.closest('.item').fadeOut(200);
                amount = parseInt(  jQuery('sup', _active_filter).html() );
                if( amount == 1 ) {
                    if( _active_filter.next('span').length > 0 ) _active_filter.next('span').click()
                    else if( _active_filter.prev('span').length > 0 ) _active_filter.prev('span').click()
                    else document.location.reload()
                    _active_filter.remove();
                } else {
                    jQuery('.filter span.active sup').html ( amount - 1 )
                }
            }
           if(_el.hasClass('in-favorites')){
               _el.removeClass('in-favorites').attr('data-icon', 'star_border').attr('title', 'В избранное');      
               if( jQuery('span', _el).length == 0 ) _el.text('В избранное');
               --_menu_favorites_amount;
           } else {
               ++_menu_favorites_amount;
               _el.addClass('in-favorites').attr('data-icon', 'star').attr('title', 'Удалить из избранного');      
               if( jQuery('span', _el).length == 0 ) _el.text('Удалить из избранного');
           }
           _menu_el.text( _menu_favorites_amount );
           if( _menu_favorites_amount > 0 ) _menu_el.closest('li').addClass('active');
           else _menu_el.closest('li').removeClass('active');
           //когда в избранном уже что-то есть прибавление работает корректно
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
            //console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
        }
    });
   return false;
// do something
}