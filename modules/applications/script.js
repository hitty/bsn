   
    //если это обычный пользователь, все комментарии развернуты, нужно будет корректировать высоты строк
        function fitRows(){
            jQuery('.tablesorter').children('tbody').children('tr').each(function(){
                var _user_comment_block = jQuery(this).children('.date_in').children('.app-comments-block').children('.user-comment-block');
                if(_user_comment_block.length > 0){
                    var _add_height = _user_comment_block.offset().top + _user_comment_block.height() - jQuery(this).offset().top - jQuery(this).height();
                    jQuery(this).height(jQuery(this).height()+parseInt(_add_height)+20);
                }
            });
            return true;
        }
    
    jQuery(document).ready(function(){
        
        //сворачиваем или разворачиваем блок с комментариями
        jQuery(document).on('click','.adv-table-row .date_in .comment i:not(".no-comment")',function(){
            //если уже развернуто, сворачиваем
            var _app_comment_block = jQuery(this).parent().siblings('.app-comments-block');
            if(_app_comment_block.hasClass('active')){
                _app_comment_block.children('.user-comment-block').toggleClass('active');
                if(_app_comment_block.children('.active').length == 0) _app_comment_block.removeClass('active').parents('tr').removeClass('editing');
                else if(!_app_comment_block.parents('tr').hasClass('editing')) _app_comment_block.parents('tr').addClass('editing');
            }
            else if(!_app_comment_block.hasClass('active')){
                _app_comment_block.addClass('active').children('.user-comment-block').addClass('active');
                _app_comment_block.parents('tr').addClass('editing');
            }
            
            //меняем надпись на кнопке
            if(jQuery('.user-comment-block').hasClass('active')) jQuery(this).html("Скрыть");
            else jQuery(this).html("Комментарий");
            
            //при необходимости, корректируем высоту строки по содержимому (+20 - учитываем кнопки "Сохранить" и "Удалить" при hover)
            var _this_row = jQuery(this).parents('tr');
            var _add_height = _app_comment_block.offset().top + _app_comment_block.height() - _this_row.offset().top - _this_row.height();
            _this_row.height(_this_row.height()+parseInt(_add_height)+20);
        });
        
        //сохраняем или удаляем комментарий
        jQuery(document).on('click','.comment-block i',function(){
            _elem = jQuery(this);
            var _id = jQuery(this).parents('tr').attr('id');
            //если ткнули в "отмена" выходим
            if(_elem.hasClass('undo')) return false;
            if(_elem.hasClass('save')) _id += '/save/';
            else{
                _id += '/delete/';
                if(_elem.siblings('textarea').html().length>0 && !confirm('Вы уверены, что хотите удалить заметку?')) return false;
            } 
            var _url = window.location.href + 'comment/' + _id;
            _comment = jQuery(this).siblings('textarea').val();
            //пустую заметку не сохраняем
            if(_comment.length == 0) return false;
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true,comment:_comment},
                success: function(msg){
                    if(msg.ok){
                        //если удалили комментарий, сворачиваем поле и рисуем кнопку "Заметка"
                        if(_elem.hasClass('del')){
                            _elem.parents('tr').removeClass('editing');
                            //если комментарий полльзователя скрыт или отсутствует, сворачиваем строку
                            if(!_elem.parents('.comment-block').children('.user-comment-block').hasClass('active')) _elem.parents('.comment-block').removeClass('active');
                            _elem.siblings('textarea').html("");
                            //привязываем событие "Отмена" заново, так как нельзя было привязать делегатом
                            _elem.addClass('undo').removeClass('del').html("Отмена").on('click',function(){
                                jQuery(this).parent().toggleClass('active');
                                jQuery(this).parents('tr').removeClass('editing');
                                jQuery(this).siblings('textarea').html("");
                                jQuery(this).parent().removeClass('active');
                                if(jQuery(this).parents('app-comments-block').children('.active').length == 0) jQuery(this).parents('app-comments-block').removeClass('active');
                                
                                //при необходимости, корректируем высоту строки по содержимому (+20 - учитываем кнопки "Сохранить" и "Удалить" при hover)
                                var _app_comment_block = jQuery(this).parents('tr').children('.date_in').children('.app-comments-block');
                                jQuery('.add-comment', _app_comment_block).show();
                                var _this_row = jQuery(this).parents('tr');
                                var _add_height = _app_comment_block.offset().top + _app_comment_block.height() - _this_row.offset().top - _this_row.height();
                                _this_row.height(_this_row.height()+parseInt(_add_height)+20);
                                
                                if(jQuery(this).parents('tr').children('.app-info').children('.add-comment').length == 0)
                                    jQuery(this).parents('tr').children('.app-info').append('<i class="add-comment">Заметка</i>');
                            });
                            
                            //при необходимости, корректируем высоту строки по содержимому (+20 - учитываем кнопки "Сохранить" и "Удалить" при hover)
                            var _app_comment_block = _elem.parents('tr').children('.date_in').children('.app-comments-block');
                            var _this_row = _elem.parents('tr');
                            var _add_height = _app_comment_block.offset().top + _app_comment_block.height() - _this_row.offset().top - _this_row.height();
                            _this_row.height(_this_row.height()+parseInt(_add_height)+20);
                            
                            //возвращаем кнопку "Заметка"
                            if(_elem.parents('td').siblings('td.app-info').children('.add-comment').length == 0)
                                _elem.parents('td').siblings('td.app-info').append('<i class="add-comment">Заметка</i>');
                        }
                        else{
                            _elem.siblings('textarea').html(_comment);
                            if(_elem.siblings('.undo').length>0) _elem.siblings('.undo').addClass('del').removeClass('undo').html("Удалить");
                            //изменяем кнопку при успехе:
                            _elem.addClass('saved').html("Сохранено");
                            setTimeout("_elem.removeClass('saved').html('Сохранить')",2000);
                        } 
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        });
        
        //ограничиваем число символов в комментарии 140
        jQuery(document).on('keyup','textarea',function(e){
            if(e.keyCode == 13 && jQuery(this).val().indexOf('\n') >=0){
                //разбиваем по строкам
                var _lines = jQuery(this).val().split('\n');
                var _out = "";
                //если строк больше 5, удаляем лишние
                if(_lines.length>=5)
                    for(var i=_lines.length-1;i>=0;i--){
                        if(_lines[i].length == 0) _lines.splice(i,1);
                    }
                //оставляем только первые две строки
                _lines.splice(4,_lines.length-1);
                jQuery(this).val(_lines.join('\n'));
                return false;
            } 
            if(jQuery(this).val().length>=140) jQuery(this).val(jQuery(this).val().substring(0,140));
            
        });
        
        //кнопка "Отмена" для комментария
        jQuery(document).on('click','.undo',function(){
            //если это кнопка "Удалить", то событие уже отработало, выходим
            if(jQuery(this).hasClass('del')) return false;
            jQuery(this).parent().toggleClass('active');
            jQuery(this).parents('tr').removeClass('editing');
            jQuery(this).siblings('textarea').html("");
            jQuery(this).parent().removeClass('active');
            if(jQuery(this).parents('app-comments-block').children('.active').length == 0) jQuery(this).parents('app-comments-block').removeClass('active');
            
            //при необходимости, корректируем высоту строки по содержимому (+20 - учитываем кнопки "Сохранить" и "Удалить" при hover)
            var _app_comment_block = jQuery(this).parents('tr').children('.date_in').children('.app-comments-block');
            var _this_row = jQuery(this).parents('tr');
            var _add_height = _app_comment_block.offset().top + _app_comment_block.height() - _this_row.offset().top - _this_row.height();
            _this_row.height(_this_row.height()+parseInt(_add_height)+20);
            
            jQuery('.add-comment', jQuery(this).parents('tr')).removeClass('hidden');
        });
        
        //кнопка "Заметка" для добавления заметки менеджером
        jQuery(document).on('click','.add-comment',function(){
            var _app_comment_block = jQuery(this).parents('tr').children('.date_in').children('.app-comments-block');
            _app_comment_block.addClass('active').children('.comment-block').addClass('active');
            if(!_app_comment_block.parents('tr').hasClass('editing')) _app_comment_block.parents('tr').addClass('editing');
            
            //при необходимости, корректируем высоту строки по содержимому (+20 - учитываем кнопки "Сохранить" и "Удалить" при hover)
            var _this_row = jQuery(this).parents('tr');
            var _add_height = _app_comment_block.offset().top + _app_comment_block.height() - _this_row.offset().top - _this_row.height();
            _this_row.height(_this_row.height()+parseInt(_add_height)+20);
            
            //убираем кнопку "Заметка"
            jQuery(this).addClass('hidden');
        });
        
        //чтобы комментарий сохранялся по Enter
        jQuery('.comment-block').children('textarea').on('keyup',function(e){
            //если щелкнули по Enter, сохраняем
            if(e.keyCode == 13) jQuery(this).siblings('i.save').click();
        });
        
        
        //щелкаем на "В работе" (чтобы завершить)
        jQuery('span.title.in-work').on('click',function(){
            if(jQuery(this).hasClass('common-user')) return false;
            if(!confirm('Завершить заявку?')) return false;
            var _id = jQuery(this).parents('tr').attr('id');
            var _url = window.location.href + 'finish/' + _id;
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true},
                success: function(msg){
                    if(msg.ok){
                        //если все хорошо, корректируем количество и щелкаем по вкладке "Завершенные"
                        jQuery('#objects-list-title').children('.active').children('sup').html(parseInt(jQuery('#objects-list-title').children('.active').children('sup').html()) - 1);
                        jQuery('#objects-list-title').children().eq(3).children('sup').html(parseInt(jQuery('#objects-list-title').children().eq(3).children('sup').html()) + 1)
                        jQuery('#objects-list-title').children().eq(3).click();
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        });
        
        /////////////////////////////////////////////////////////////
        //кусок для кастомизированной всплывашки покупки
        ///////////////////////////////////////////////////////////
        var _modal_template = '<div id="background-shadow-expanded">'
                                +'<div id="background-shadow-expanded-wrapper"></div>'
                                +'<div class="app-choosing-wrapper active"><span class="form-title">Взять заявку в работу</span><a class="closebutton"></a>'
                                    +'<span class="title-text">Во всех спорных ситуациях, например, когда заявитель не отвечает на звонок, мы конечно же вернем деньги!</span>'
                                    +'<div class="choosing-block">'
                                        +'<div class="standart">'
                                            +'<span class="title">Стандарт</span>'
                                            +'<span class="cost"></span>'
                                            +'<span class="description">Доступна всем пользователям</span>'
                                            +'<button class="button grey" value="Выбрать">Выбрать</button>'
                                        +'</div>'
                                        +'<div class="exclusive">'
                                            +'<span class="title">Эксклюзив</span>'
                                            +'<span class="cost"></span>'
                                            +'<span class="description">Доступна только вам</span>'
                                            +'<button class="button green" value="Выбрать">Выбрать</button></div>'
                                        +'</div>'
                                    +'</div>'
                                +'</div>'
                            +'</div>';
        var _modal_realtor_template = '<div id="background-shadow-expanded">'
                                +'<div id="background-shadow-expanded-wrapper"></div>'
                                +'<div class="app-choosing-wrapper active realtor-block"><span class="form-title">Взять заявку в работу</span><a class="closebutton"></a>'
                                    +'<span class="title-text">Во всех спорных ситуациях, например, когда заявитель не отвечает на звонок, мы конечно же вернем деньги!</span>'
                                    +'<div class="choosing-block">'
                                        +'<div class="exclusive">'
                                            +'<span class="title">Заявка</span>'
                                            +'<span class="cost"></span>'
                                            +'<span class="description">Доступна только вам</span>'
                                            +'<button class="button grey" value="Выбрать">Выбрать</button>'
                                        +'</div>'
                                        
                                    +'</div>'
                                +'</div>'
                            +'</div>';
        function modal(e, _url, _cost_low, _cost_high, _can_be_exclusive, _el){
            e.stopPropagation();
            e.preventDefault();
            jQuery('body').append(_el.hasClass('realtor') ? _modal_realtor_template : _modal_template);
            jQuery('#background-shadow-expanded').show();
            jQuery('.standart').children('.cost').html(_cost_low + " руб.");
            jQuery('.exclusive').children('.cost').html(_cost_high + " руб.");
            jQuery('.app-choosing-wrapper').attr('data-url',_url);
            jQuery('.app-choosing-wrapper.active a.closebutton').on('click',function(){
                jQuery('#background-shadow-expanded').remove();
            });
            //обрабатываем клики по кнопкам (убираем старые, добавляем новые)
            jQuery(document).off('click','.app-choosing-wrapper button');
            jQuery(document).on('click','.app-choosing-wrapper button',function(){
                var _public_list = jQuery(this).hasClass('public');
                var _elem = jQuery(this);
                var _cost = 0;
                if(_elem.parent().attr('class') == 'standart') _cost = _cost_low
                else{
                    _cost = _cost_high;
                    jQuery('.app-choosing-wrapper').attr('data-url',_url.replace('in_work','in_work_exclusive'));
                }
                
                //if(!confirm("С вашего счета будет списано " + _cost + " рублей. Продолжить?")) return false;
                
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: jQuery('.app-choosing-wrapper').attr('data-url'),
                    data: {ajax: true},
                    success: function(msg){
                        if(msg.ok){
                            //если все хорошо, щелкаем по вкладке "В работе", если баланса не хватает - редирект на оплату
                            if(msg.pay_result){
                                //если это не лк, просто удаляем строку
                                if(window.location.href.match(/members/) == null){
                                    jQuery('.tablesorter').DataTable().row(_elem.parents('tr')).remove().draw(false);
                                    window.location.href = '/members/conversions/applications/#in-work';
                                } 
                                else{
                                    jQuery('.closebutton').click();
                                    jQuery('#objects-list-title').children('li[data-type="performing"]').click();
                                }
                                
                            } 
                            else{
                                if(msg.late) alert('Эта заявка уже взята в работу');
                                else{
                                    if(msg.cannot_buy) alert('Заявки могут покупать только специалисты');
                                    else{
                                        alert('Вашего баланса не хватает для оплаты');
                                        if(window.location.href.match(/members/) == null) window.location.href = window.location.href.replace('/applications/','/members/pay/balance/');
                                        else window.location.href = '/members/pay/balance';
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
            });
            
            if(parseInt(_can_be_exclusive)==0){
                jQuery('.exclusive').children('.button').remove();
                jQuery('.exclusive').children('.description').after("<span class='unavailable'>Недоступно</span>");
                jQuery('.standart').children('.button').removeClass('grey').addClass('green');
            }
            
            return false;
        }
        //////////
        //взятие в работу
        //////////
        jQuery(document).on('click', '.button.blue.in-work', function(ev){
            if(jQuery(this).hasClass('disabled'))
            var _target = jQuery(this);
            ev.stopPropagation();
            ev.preventDefault();
            var _url = window.location.href + 'in_work/' + jQuery(this).parents('tr').attr('id') + '/';
            //если это свой объект или бесплатная-для-платных-заявка, всплывашку не выдаем
            if(jQuery(this).parents('tr').children('td.app-type').children('.your-object').length>0 && jQuery(this).attr('low-cost') == 0 || jQuery(this).hasClass('free-for-payed')){
                var _public_list = jQuery(this).hasClass('public');
                var _elem = jQuery(this);
                var _url = window.location.href + 'in_work/' + jQuery(this).parents('tr').attr('id') + '/';
                
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: _url,
                    data: {ajax: true},
                    success: function(msg){
                        if(msg.ok){
                            //если все хорошо, щелкаем по вкладке "В работе", если баланса не хватает - редирект на оплату
                            if(msg.pay_result){
                                //если это публичная страница, просто удаляем строку
                                if(_public_list){
                                    jQuery('.tablesorter').DataTable().row(_elem.parents('tr')).remove().draw(false);
                                    window.location.href = '/members/conversions/applications/#in-work';
                                }
                                else{
                                    //корректируем цифры количества элементов во вкладках
                                    var _new_amount = parseInt(jQuery('#objects-list-title').children('li:eq(1)').children('sup').html()) - 1;
                                    jQuery('#objects-list-title').children('li:eq(1)').children('sup').html(_new_amount);
                                    _new_amount = parseInt(jQuery('#objects-list-title').children('li:eq(2)').children('sup').html()) + 1;
                                    jQuery('#objects-list-title').children('li:eq(2)').children('sup').html(_new_amount);
                                    
                                    //при необходимости корректируем отображаемый слева баланс и форматируем его в красивый вид
                                    var _balance_box = jQuery('.members-menu').children('.user-info').children('.balance').children('b');
                                    _balance_box.html(parseInt(_balance_box.html().replace(/[^0-9]/,'')) - msg.cost);
                                    _balance_box.html( number_format( _balance_box.html(), 0, '.', ' ' ) );
                                    
                                    jQuery('#objects-list-title').children('li:eq(2)').removeClass('disabled');
                                    jQuery('#objects-list-title').children('li:eq(2)').click();
                                }
                            } 
                            else{
                                if(msg.late) alert('Эта заявка уже взята в работу');
                                else{
                                    if(msg.cannot_buy) alert('Заявки могут покупать только специалисты');
                                    else window.location.href = window.location.href.replace('conversions/applications/','pay/balance/');
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
            else modal(ev, _url, jQuery(this).attr('low-cost'), jQuery(this).attr('high-cost'), jQuery(this).attr('data-exclusive'), jQuery(this));
            return false;
        });
        
        if(jQuery('.tablesorter.applications.common-user').length>0){
            setTimeout("fitRows();",200);
        }
        
        
    });