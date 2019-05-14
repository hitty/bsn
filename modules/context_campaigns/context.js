jQuery(document).ready(function(){
        var _now = new Date();
        //инициализируем datetimepicker
        if(jQuery('.datetimepicker').length>0){
            jQuery('.datetimepicker').datetimepicker();
            jQuery('.datetimepicker').datetimepicker({
                timepicker:false,
                format:'d.m.Y',
                minDate:_now.dateFormat("d.m.Y")
            });
        }
        //запрещаем ручное редактирование дат
        jQuery('.datetimepicker').on('keydown',function(){
            return false;
        });
        
        //при изменении даты начала на не-сегодняшнюю автоматически делаем кампанию неактивной, так как дата начала больше чем сегодня
        jQuery('.xdsoft_calendar').on('mousedown',function(e){
            var _now = Date.now();
            var _start = jQuery('.adv-campaign-edit-block').children('.top-block').children('.period').children('.from').children('input').val().split('.');
            var _date_start = Date.parse('23:59 ' + _start[1] + '/' + _start[0] + '/' + _start[2]);
            //в случае, если выбрана сегодняшняя дата, снимаем блок с переключателя "активна/не активна"
            if(_date_start > _now && Math.ceil((_date_start - _now) / (1000 * 3600 * 24)) > 1)
                jQuery('.adv-campaign-edit-block').children('.top-block').children('.status').children('.switcher').addClass('checked').addClass('fixed');
            else
                jQuery('.adv-campaign-edit-block').children('.top-block').children('.status').children('.switcher').removeClass('fixed');
        });
        ///переносим форму редактирования кампании в новый div:
        var _old_cform = jQuery('.form_default').children('fieldset');
        var _cform = jQuery('.adv-campaign-edit-block');
        
        //переносим поле с названием:
        if(_old_cform.children('#p_field_title').children('.lf').children().val().length>0)
            _cform.children('.top-block').children('.campaign-title').children('.title').html(_old_cform.children('#p_field_title').children('.lf').children().val());
        else
            _cform.children('.top-block').children('.campaign-title').children('.title').html("Название вашей кампании");
        _old_cform.children('#p_field_title').children('.lf.fieldwrapper').insertAfter(_cform.children('.top-block').children('.campaign-title').children('.title'));
        
        //по нажатию на название показываем поле ввода
        jQuery('.top-block').children('.campaign-title').children('.title.active').on('click',function(){
            jQuery(this).hide();
            jQuery(this).parent().children('.note').hide();
            jQuery(this).parent().children('.lf.fieldwrapper').toggleClass('active');
            jQuery(this).parent().children('.lf.fieldwrapper').children('input').focus();
        });
        //по снятию фокуса с поля ввода названия, меняем обратно
        jQuery('.top-block').children('.campaign-title').children('.lf.fieldwrapper').children('input').on('blur',function(){
            jQuery(this).parent().toggleClass('active');
            if(jQuery(this).val().length>0)
                jQuery(this).parent().parent().children('.title').html(jQuery(this).val());
            else
                jQuery(this).parent().parent().children('.title').html("Название вашей кампании");
            jQuery(this).parent().parent().children('.title').show();
            jQuery(this).parent().parent().children('.note').show();
        });
        //переносим в новую форму поля ввода даты
        _cform.children('.top-block').children('.period').children('i.from').append(_old_cform.children('#p_field_date_start').children('.lf.fieldwrapper').children('input'));
        _cform.children('.top-block').children('.period').children('i.till').append(_old_cform.children('#p_field_date_end').children('.lf.fieldwrapper').children('input'));
        
        //обработчик для переключателя поля статуса кампании
        jQuery('.top-block').children('.status').children('.switcher').on('click',function(){
            if(jQuery(this).hasClass('fixed')) return false;
            jQuery(this).toggleClass('checked');
            if( jQuery(this).attr('data-status') == 1) jQuery(this).attr('data-status',2);
            else jQuery(this).attr('data-status',1);
        });
        //переносим поле "Бюджет"
        _old_cform.children('#p_field_balance').children('.lf').insertAfter(_cform.children('.cbottom-block').children('.balance').children('.field-title'));
        //переносим поле "Описание"
        _old_cform.children('#p_field_description').children('.lf').insertAfter(_cform.children('.cbottom-block').children('.description').children('.field-title'));
        //прячем остатки старой формы
        _old_cform.hide();
        
        //обработчик для кнопки сохранения кампании
        jQuery('#campaign-save-button').on('click',function(){
            //собираем данные из формы кампании
            var _cform = jQuery('.adv-campaign-edit-block');
            var _url = jQuery('.form_default').attr('action');
            _cform_data = {};
            _cform_data['title'] = _cform.children('.top-block').children('.campaign-title').children('.lf').children('input').val();
            _cform_data['date_start'] = _cform.children('.top-block').children('.period').children('.from').children('input').val();
            _cform_data['date_end'] = _cform.children('.top-block').children('.period').children('.till').children('input').val();
            _cform_data['published'] = _cform.children('.top-block').children('.status').children('.switcher').attr('data-status');
            _cform_data['balance'] = _cform.children('.cbottom-block').children('.balance').children('.lf').children('#balance').val();
            _cform_data['description'] = _cform.children('.cbottom-block').children('.description').children('.lf').children('.lf.plaintext').val();
            _cform_data['submit'] = true;
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true,campaign_data:_cform_data},
                success: function(msg){
                    if(msg.ok){
                        //если все хорошо, показываем уведомление
                        jQuery('.adv-campaign-msg-box').html('<div class="notification msgsuccess">Данные сохранены.</div>');
                        jQuery('.adv-campaign-msg-box').show();
                        //через 5 секунд прячем его
                        setTimeout('jQuery(".adv-campaign-msg-box").fadeOut(700);clearTimeout();', 5000);
                        //при необходимости переходим на страницу редактирования добавленной кампании
                        if(msg.id) window.location.href = window.location.pathname.replace(/add/,msg.id);
                    } 
                    else{
                        //разбираем ошибки
                        var _errors = JSON.parse(msg.errors);
                        if(_errors){
                            if(_errors['balance'])
                                jQuery('.adv-campaign-edit-block').children('.cbottom-block').children('.balance').addClass('error').append("<span class='error-box'>"+_errors['balance']+"</span>");
                            if(_errors['title'])
                                jQuery('.adv-campaign-edit-block').children('.top-block').children('.campaign-title').addClass('error').append("<span class='error-box'>"+_errors['title']+"</span>");
                        }
                        jQuery('.adv-campaign-msg-box').html('<div class="notification msgerror">Ошибка. Проверьте правильность заполнения формы.</div>');
                    } 
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        });
        ///
        //удаление объявления (кнопка 'Удалить')
        jQuery('.central-column').on('click','.delete.outer',function(){
            var _id = jQuery(this).attr("data-id");
            var _url = jQuery(this).attr("data-url");
            if(confirm('Вы уверены, что хотите удалить контекстное объявление?'))
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true,id:_id},
                success: function(msg){
                    if(msg.ok){
                        jQuery('#context-block-'+_id).fadeOut(700);
                        jQuery('#context-block-'+_id).remove();
                        //уменьшаем счетчик общего количества объявлений
                        var _adv_amount = parseInt(jQuery('.adv-list-length').html().replace(/[^0-9]*/,''));
                        //есил объявления кончились, чистим заголовок и счетчик
                        if(_adv_amount == 1){
                            jQuery('.adv-list-title').html("");
                            jQuery('.adv-list-length').html("");
                        }
                        else jQuery('.adv-list-length').html("Всего: "+(_adv_amount - 1));
                    }
                    else alert('ошибка удаления объявления');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        });
        
        //обработка удаления объявления при редактировании
        jQuery('.context-adv-edit').on('click','.delete',function(){
            var _id = jQuery(this).attr("data-id");
            var _url = jQuery(this).attr("data-url");
            if(confirm('Вы уверены, что хотите удалить контекстное объявление?'))
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true,id:_id},
                success: function(msg){
                    if(msg.ok) jQuery('#context-block-'+_id).fadeOut();
                    else alert('ошибка удаления объявления');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        });
        //рисуем форму для добавления (кнопка 'Добавить')
        jQuery('.add-item').children('.button').on("click",function(){
            //отлючаем кнопку или сворачиваем форму и включаем, если она уже отключена
            if(jQuery(this).hasClass('disabled')){
                jQuery(this).removeClass('disabled');
                jQuery('.context-adv.new').removeClass('edit');
                jQuery('.context-adv.new').children('.form-adv').html("");
                //показываем все остальные объявления, кнопку "Сохранить кампанию", паджинатор и заголовок со счетчиком объявлений
                jQuery('.context-adv').show();
                jQuery('#campaign-save-button').show();
                jQuery('.adv-list-title').show();
                jQuery('.adv-list-length').show();
                jQuery('.paginator').show();
                //скрываем то, что относилось к добавлению
                jQuery('.save-adv-form').hide();
                jQuery('.context-adv.new').hide();
                return false;
            }
            else jQuery(this).addClass('disabled');
            
            //убираем все другие формы, щелкая по кнопкам
            jQuery('.undo').click();
            
            //скрываем все остальные объявления, кнопку "Сохранить кампанию", паджинатор и заголовок со счетчиком объявлений
            jQuery('.context-adv').hide();
            jQuery('#campaign-save-button').hide();
            jQuery('.adv-list-title').hide();
            jQuery('.adv-list-length').hide();
            jQuery('.paginator').hide();
            //скрываем кнопку "Сохранить", чтобы она не висела при загрузке
            jQuery('.save-adv-form').hide();
            
            //раскрываем форму редактирования
            jQuery('.context-adv.new').show();
            jQuery('.context-adv.new').addClass('edit');
            
            //отрисовываем содержимое
            getPendingContent("#adv-edit-new",jQuery('#adv-edit-new').attr('action'));
        });
        
        //рисуем форму под объявлением (кнопка 'Редактирование')
        jQuery(document).on("click",'span.edit',function(){
            //если у этого объявления форма скрыта, раскрываем ее
            if(!jQuery(this).parent().parent().children('.context-adv-edit').hasClass("active")){
                //скрываем все другие формы и чистим их содержимое
                jQuery('.context-adv-edit').removeClass('active');
                jQuery('.form-adv').html("");
                //скрываем все остальные объявления, кнопку "Добавить", кнопку "Сохранить кампанию", паджинатор и заголовок со счетчиком объявлений
                jQuery('.context-adv').hide();
                jQuery('.add-item').children('.button').hide();
                jQuery('#campaign-save-button').hide();
                jQuery('#context-block-'+jQuery(this).attr('data-id')).show();
                jQuery('.adv-list-title').hide();
                jQuery('.adv-list-length').hide();
                jQuery('.paginator').hide();
                //перед раскрытием формы прячем кнопку "сохранить", чтобы она не висела пока форма грузится
                jQuery(this).parent().parent().children('.context-adv-edit').children('.save-adv-form').hide();
                //раскрываем нашу
                jQuery(this).parent().parent().children('.context-adv-edit').addClass('active');
                getPendingContent("#adv-edit-"+jQuery(this).attr('data-id'),'/members/context_campaigns/'+jQuery(this).attr('data-campaign-id')+'/edit/'+jQuery(this).attr('data-id'));
                //прячем панель с кнопками справа (с которой щелкнули)
                jQuery(this).parent().addClass('disabled');
                //прячем левый блок (с названием, статистикой, информацией по таргетингу)
                jQuery(this).parent().parent().children('.left-block').hide();
            }
            else{
                //скрываем нашу
                jQuery(this).parent().parent().children('.context-adv-edit').removeClass('active');
                jQuery('#adv-edit-'+jQuery(this).attr('data-id')).html("");
            }
        });
        
        //закрываем форму без сохранения (кнопка 'Отмена')
        jQuery(document).on('click','span.undo',function(){
            if(jQuery(this).hasClass('creation')){
                //отменяем создание рекламного блока
                jQuery('.add-item').children().removeClass('disabled');
                jQuery('.context-adv.new').removeClass('edit');
                jQuery('.context-adv.new').children('.form-adv').html("");
                //показываем все остальные объявления, кнопку "Сохранить кампанию", паджинатор и заголовок со счетчиком объявлений
                jQuery('.context-adv').show();
                jQuery('#campaign-save-button').show();
                jQuery('.adv-list-title').show();
                jQuery('.adv-list-length').show();
                jQuery('.paginator').show();
                //скрываем то, что относилось к добавлению
                jQuery('.save-adv-form').hide();
                jQuery('.context-adv.new').hide();
                return false;
            }
            else{
                jQuery(this).parents('.context-adv-edit').removeClass('active');
                //показываем панель с кнопками справа
                jQuery(this).parents('.context-adv-edit').parent().children('.object-actions').removeClass('disabled');
                //показываем левый блок (с названием, статистикой, информацией по таргетингу)
                jQuery(this).parents('.context-adv-edit').parent().children('.left-block').show();
                //убираем форму редактирования
                jQuery('#adv-edit-'+jQuery(this).attr('data-id')).html("");
                //показываем все остальные объявления, кнопку "Добавить", кнопку "Сохранить кампанию"
                jQuery('.context-adv').show();
                jQuery('.context-adv.new').hide();
                jQuery('.add-item').children('.button').show();
                jQuery('.paginator').show();
                jQuery('.adv-list-title').show();
                jQuery('.adv-list-length').show();
                jQuery('#campaign-save-button').show();
            }
        });
        
        //сохраняем данные из формы редактирования объявления (кнопка 'Сохранить')
        jQuery(document).on("click",'.save-adv-form',function(){
            var _form = jQuery(this).parent().children('.form-adv');
            var _url = _form.attr('action');
            var _continue = jQuery(this).hasClass('continue');
            _item_id = jQuery(this).parent().children('.form-adv').children('.adv-fields').attr('data-id');
            var _new_item = false;
            //если форма новая, то после успешного сохранения ее необходимо будет пихнуть в список
            if(_url.search(/add/)) _new_item = true; 
            _form_data = {};
            //читаем название
            _form_data['title'] = _form.children('.adv-fields').children('.top').find('[name]').val();
            //собираем данные из куска формы слева
            _form.children('.adv-fields').children('.left').children('p').each(function(){
                var _field = jQuery(this).children('.lf.fieldwrapper').find('[name]');
                if(_field.length>1)
                    for(var _i=0;_i<_field.length;_i++){
                        if(_field.eq(_i).val()>0 && _field.eq(_i).attr('id') !== undefined){
                            _form_data[_field.attr('name')] = _field.eq(_i).val();
                            break;
                        }
                    }
                else{
                    if(_field.attr('name') !== undefined)
                        _form_data[_field.attr('name')] = _field.val();
                } 
            });
            //читаем ссылку на переход
            _form_data['url'] = _form.children('.adv-fields').children('.bottom').find('[name="url"]').val();
            _form_data['get_pixel'] = _form.children('.adv-fields').children('.bottom').find('[name="get_pixel"]').val();
            _form_data['banner_title'] = _form.children('.adv-fields').children('.bottom').find('[name="banner_title"]').val();
            _form_data['banner_text'] = _form.children('.adv-fields').children('.bottom').find('[name="banner_text"]').val();
            //дозаполняем _form_data из estate_type и deal_type, которые не внутри <p>:
            _form_data['estate_type'] = _form.children('.adv-fields').children('.left').children('#estate_type').val();
            _form_data['deal_type'] = _form.children('.adv-fields').children('.left').children('#deal_type').val();
            if(jQuery('#published_value').attr("value")!==undefined)
                _form_data['published'] = jQuery('#published_value').attr("value");
            //заполняем таргетинг
            var _targeting_data = {};
            var _object_type = jQuery('.tg.object-type');
            //ограничения по цене
            _targeting_data['price_top'] = jQuery('.tg.price').find('#input-price-top').val();
            _targeting_data['price_floor'] = jQuery('.tg.price').find('#input-price-floor').val();
            //комнатность
            _targeting_data['rooms'] = "";
            jQuery('.tg.rooms').find('.room-tg-item.selected').each(function(){
                if(_targeting_data['rooms'].length = 0) _targeting_data['rooms'] = jQuery(this).attr('id');
                else _targeting_data['rooms'] += ","+jQuery(this).attr('id');
            });
            
            //типы объекта
            _targeting_data['object_types'] = jQuery('.tg.object_types').find('input').val();
            //метро
            _targeting_data['subways'] = jQuery('.tg.subways').find('input').val();
            //районы
            _targeting_data['districts'] = jQuery('.tg.districts').find('input').val();
            //районы области
            _targeting_data['district_areas'] = jQuery('.tg.district_areas').find('input').val();
            
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true,form_data:_form_data,targeting_data:_targeting_data,submit:true},
                success: function(msg){
                    if(msg.saved){
                        //если форма новая, то гасим форму добавления и пихаем объявление в общий список, обновляем счетчик объявлений
                        if(msg.id){
                            //обновляем значение счетчика
                            if(jQuery('.adv-list-length').html()) jQuery('.adv-list-length').html("Всего: " + (parseInt(jQuery('.adv-list-length').html().replace(/[^0-9]*/,''))+1));
                            else jQuery('.adv-list-length').html("Всего: 1");
                            //если это объявление первое, добавляем надпись
                            if(jQuery('.adv-list-title').html().length == 0) jQuery('.adv-list-title').html("Список объявлений");
                            //скрываем и чистим форму
                            jQuery('#adv-edit-new').fadeOut();
                            jQuery('#adv-edit-new').html("");
                            //скрываем остатки блока добавления
                            jQuery('.context-adv.new').removeClass('edit');
                            jQuery('.add-item').children('.button').click();
                            //создаем новый блок в списке
                            jQuery('.adv-list').html(jQuery('.adv-list').html()+"<div id='context-block-"+msg.id+"' class='context-adv'><div class='left-block unactive'></div><span class='object-actions'></span><div class='context-adv-edit'></div></div>");
                            jQuery('#context-block-'+msg.id).children('.left-block').html(jQuery('#context-block-'+msg.id).children('.left-block').html()+'<span class="info-panel"></span><span class="targeting-panel"></span>');
                            var _context_block_info = jQuery('#context-block-'+msg.id).children('.left-block').children('.info-panel');
                            var _context_block_targeting = jQuery('#context-block-'+msg.id).children('.left-block').children('.targeting-panel');
                            var _context_block_actions = jQuery('#context-block-'+msg.id).children('.object-actions');
                            var _context_block_form = jQuery('#context-block-'+msg.id).children('.context-adv-edit');
                            //собираем верхнюю строчку блока (название, статистика)
                            _context_block_info.html('<span class="adv title"></span><span class="adv ctr"></span><span class="adv shows"></span><span class="adv clicks"></span>');
                            _context_block_info.children('.adv.title').html(msg.form_data['title']);
                            _context_block_info.children('.adv.ctr').html("CTR: <i>0%</i>");
                            _context_block_info.children('.adv.shows').html("Показов: <i>0</i>");
                            _context_block_info.children('.adv.clicks').html("Кликов: <i>0</i>");
                            //собираем нижнюю строчку блока (таргетинг)
                            _context_block_targeting.html("<span class='targeting-info'></span><span class='targeting-estate'></span><span class='image-info'></span>");
                            _context_block_targeting.children(".targeting-info").html("<i class='title-text'>Таргетинг</i>");
                            //собираем правую панель (с кнопками управления)
                            _context_block_actions.html('<span title="Редактировать" class="edit" data-id="'+msg.id+'" data-campaign-id="'+jQuery('.adv-list-title').attr('data-id')+'">Изменить</span>');
                            _context_block_actions.html(_context_block_actions.html()+'<span title="Удалить" class="delete outer" data-id="'+msg.id+'" data-url="/members/context_campaigns/'+jQuery('.adv-list-title').attr('data-id')+'/del/'+msg.id+'/">Удалить</span>');
                            //собираем форму, в которой будет редактирование
                            _context_block_form.html('<form class="form-adv" id="adv-edit-'+msg.id+'" method="post" action="/members/context_campaigns/'+jQuery('.adv-list-title').attr('data-id')+'/edit/'+msg.id+'/"></form>');
                            _context_block_form.html(_context_block_form.html()+'<button class="save-adv-form green">Сохранить и закрыть</button><i class="save-adv-form continue">Сохранить</i>'+'<span class="form-result-box" style="display:none"></span>');
                            //кликаем по кнопке "Редактировать", чтобы для нового объекта сразу раскрылась форма редактирования
                            _context_block_actions.children('.edit').click();
                        }
                        if(msg.id!==undefined) _item_id = msg.id;
                        if(msg.active == "2" && jQuery('#context-block-'+_item_id).hasClass('active')) jQuery('#context-block-'+_item_id).children('.left-block').removeClass('active').addClass('unactive');
                        if(msg.active == "1" && !jQuery('#context-block-'+_item_id).hasClass('active')) jQuery('#context-block-'+_item_id).children('.left-block').removeClass('unactive').addClass('active');
                        //обновляем на плашке таргетинг по типу объекта, ограничения по цене, типы сделки и недвжиимости, название объявления
                        if(msg.adv_info!==undefined>0){
                            //таргетинг
                            if(msg.adv_info.tags_info!==undefined){
                                jQuery('#context-block-'+_item_id).children('.left-block').children('.targeting-panel').children('.targeting-info').children('.square-info').children('.object-types').html("<b>Тип объекта: </b><i>"+msg.adv_info.tags_info+"</i>");
                            }
                            //тип сделки и недвижимости
                            if(msg.adv_info.deal_text!==undefined){
                                jQuery('#context-block-'+_item_id).children('.left-block').children('.targeting-panel').children('.targeting-estate').html(msg.adv_info.deal_text);
                            }
                            //данные по картинке
                            if(msg.adv_info.photo_info!==undefined){
                                jQuery('#context-block-'+_item_id).children('.left-block').children('.targeting-panel').children('.image-info').html(msg.adv_info.photo_info);
                            }
                            //название объявления
                            if(msg.adv_info.title!==undefined){
                                jQuery('#context-block-'+_item_id).children('.left-block').children('.info-panel').children('.adv.title').html(msg.adv_info.title);
                            }
                        }
                        //если объявление отправлено на модерацию, запрещаем редактировать и закрываем форму
                        if(msg.moderation){
                            _continue = false;
                            jQuery('.adv-form-msg').html('<div class="notification msgsuccess">Объявление сохранено и отправлено на модерацию</div>');
                            //сразу изменяем плашку снаружи
                            jQuery('#context-block-'+_item_id).children('.left-block').addClass('moderation');
                            jQuery('#context-block-'+_item_id).children('.object-actions').addClass('moderation');
                        }
                        else{
                            if(msg.archivation){
                                jQuery('#context-block-'+_item_id).children('.left-block').addClass('unactive');
                                jQuery('#context-block-'+_item_id).children('.object-actions').addClass('unactive');
                            }
                            jQuery('.adv-form-msg').html('<div class="notification msgsuccess">Данные сохранены.</div>');
                        }
                        //если нету метки "продолжить", щелкаем по кнопке "Отмена", чтобы свернуть форму
                        if(!_continue){
                            //скроллим до начала формы (поправка на 36 из-за верхней панели), чтобы было видно уведомление, что все хорошо
                            if(jQuery('.adv-form-msg').children('.notification').length > 0) jQuery(document).scrollTop(jQuery('.adv-form-msg').children('.notification').offset().top-36);
                            //уведомление
                            setTimeout('jQuery(".adv-form-msg").fadeOut(700);clearTimeout();', 2000);
                            // щелкаем по кнопке "Отмена", чтобы свернуть форму
                            setTimeout('jQuery(\'#context-block-\'+_item_id).children(\'.context-adv-edit.active\').children(\'.form-adv\').children(\'.adv-fields\').children(\'.adv-fields-row.top\').children(\'.object-actions\').children(\'.undo\').click();clearTimeout();',3000);
                        }
                        else{
                            setTimeout('jQuery(".adv-form-msg").fadeOut(700);clearTimeout();', 5000);
                            //убираем все .error у полей - раз сохранили, ошибок уже нету
                            jQuery('p').removeClass('error');
                            jQuery('span').removeClass('error');
                            jQuery('.error-box').remove();
                            //обновляем набор типов недвижимости в заглавной плашке формы
                            if(msg.estate_type) jQuery('.form-fields').attr('data-estate-type',msg.estate_type);
                            //если галочка стоит только на загородной, скрываем район города и убираем оттуда все теги
                            if(jQuery('.form-fields').attr('data-estate-type').match(/^4$/)!==null){
                                jQuery('.targetings_list').children('.tg.districts').addClass('unactive');
                                jQuery('.targetings_list').children('.tg.districts').children('.tg-item').remove();
                            }else
                                jQuery('.targetings_list').children('.tg.districts').removeClass('unactive');
                            //если галочка стоит на жилой или новостройках, показываем комнатность
                            if(jQuery('.form-fields').attr('data-estate-type').match(/1|2/))
                                jQuery('.targetings_list').children('.tg.rooms').removeClass('unactive');
                            else{
                                //если что-то другое, скрываем комнатность и отжимаем кнопки
                                jQuery('.targetings_list').children('.tg.rooms').addClass('unactive');
                                jQuery('.targetings_list').children('.tg.rooms').children('.room-tg-item').removeClass('selected');
                            }
                            //скроллим до начала формы (поправка на 36 из-за верхней панели), чтобы было видно уведомление, что все хорошо
                            jQuery(document).scrollTop(_form.children('.adv-form-msg').children('.notification').offset().top-36);
                        }
                    }
                    else{
                        var _errors = msg.errors;
                        var _error_text = "";
                        if(!_item_id) _item_id = 'new';
                        //чистим старые ошибки
                        jQuery('p').removeClass('error');
                        jQuery('span').removeClass('error');
                        jQuery('.error-box').remove();
                        //в зависимости от набора ошибок, отмечаем поля и набираем текст с пояснением
                        if(_errors!==undefined){
                            var _default_text = "Значение не может быть пустым";
                            var _targeting_block = jQuery('#adv-edit-'+_item_id).children('.targeting-block').children('.targetings_list');
                            var _left_block = jQuery('#adv-edit-'+_item_id).children('.adv-fields').children('.left');
                            var _bottom_block = jQuery('#adv-edit-'+_item_id).children('.adv-fields').children('.bottom');
                            if(_errors.image!==undefined){
                                _left_block.children('.image-upload-block').children('span').children('.upload-block').addClass('error');
                                _left_block.children('.image-upload-block').addClass('error');
                                _left_block.children('.image-upload-block').append("<span class='error-box'>"+_errors.image+"</span>");
                                _error_text = _errors.image;
                            }
                            if(_errors.targeting_area!==undefined){
                                _targeting_block.children('.subways, .districts, .district_areas').addClass('error');
                                _targeting_block.children('.subways, .districts, .district_areas').children('.list-picker').append("<span class='error-box'>"+_errors.targeting_area+"</span>");
                                _error_text = _errors.targeting_area;
                            }
                            if(_errors.targeting_type!==undefined){
                                //_targeting_list.children('.object_types').addClass('error');
                                _targeting_block.children('.object_types').addClass('error').find('.list-picker').addClass('error');
                                _targeting_block.children('.object_types').find('.list-picker').append("<span class='error-box'>"+_errors.targeting_type+"</span>");
                                _error_text = _errors.targeting_type;
                            }
                            if(_errors.estate_type!==undefined){
                                _left_block.children('.estate-type-block').addClass('error').attr('data-error',_errors.estate_type);
                                _left_block.children('.estate-type-block').append("<span class='error-box'>" + _errors.estate_type + "</span>");
                                _error_text = "значение не может быть пустым";
                            }
                            if(_errors.deal_type!==undefined){
                                _left_block.children('.deal-type-block').addClass('error').attr('data-error',_errors.deal_type);
                                _left_block.children('.deal-type-block').append("<span class='error-box'>" + _errors.deal_type + "</span>");
                                _error_text = "значение не может быть пустым";
                            }
                            if(_errors.url!==undefined){
                                _bottom_block.children('#p_field_url').addClass('error').attr('data-error',_errors.url);
                                _bottom_block.children('#p_field_url').append("<span class='error-box'>" + _errors.url + "</span>");
                            }
                            if(_errors.banner_title !== undefined){
                                _bottom_block.children('#p_field_banner_title').addClass('error').attr('data-error',_errors.url);
                                _bottom_block.children('#p_field_banner_title').append("<span class='error-box'>" + _errors.banner_title + "</span>");
                            }
                            if(_errors.banner_text !== undefined){
                                _bottom_block.children('#p_field_banner_text').addClass('error').attr('data-error',_errors.url);
                                _bottom_block.children('#p_field_banner_text').append("<span class='error-box'>" + _errors.banner_text + "</span>");
                            }
                        }
                        jQuery('.adv-form-msg').html("<div class='notification msgerror'>Ошибка. Проверьте правильность заполнения формы: "+decodeURIComponent(_error_text)+"</div>");
                        //скроллим до начала формы (поправка на 36 из-за верхней панели), чтобы было видно уведомление с ошибками
                        jQuery(document).scrollTop(_form.children('.adv-form-msg').children('.notification').offset().top-36);
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        });

        //функции для обновления данных по кликам, показам и CTR
        function refresh_handler() {
            function refresh(){
               var _url = window.location.pathname+"advlist_stats/";
               //читаем список id объявлений, для которых нужны данные
               _ids_list = {};
               _ids_list['ids_list'] = Array();
               jQuery('context-adv').each(function(){
                   _ids_list['ids_list'].push(jQuery(this).attr('id').replace(/[^0-9]/,''));
               });
               jQuery.ajax({
                   type: "POST", async: true,
                   dataType: 'json', cache: false,
                   url: _url,
                   data: {ajax: true,ids_list:_ids_list},
                   success: function(msg){
                       if(msg.ok){
                           //если все хорошо, записываем полученные данные
                           for(i=0;i<msg.data.length;i++){
                               jQuery('#context-block-'+msg.data[i]['id']).children('.bottom-panel').children('.shows').children('i').html(msg.data[i]['shows']);
                               jQuery('#context-block-'+msg.data[i]['id']).children('.bottom-panel').children('.clicks').children('i').html(msg.data[i]['clicks']);
                               if(msg.data[i]['ctr'] == null) msg.data[i]['ctr'] = 0;
                               jQuery('#context-block-'+msg.data[i]['id']).children('.bottom-panel').children('.ctr').children('i').html(msg.data[i]['ctr']+"%");
                           }
                       }
                   },
                   error: function(msg){
                   },
                   complete: function(){
                   }
               });
            }
            refresh();
            //setInterval(refresh, 5*1000); //every 30 sec
        }
        
        //обрабатываем нажатие Enter по полю ввода названия кампании
        jQuery('.adv-campaign-edit-block').children('.top-block').children('.campaign-title').children('.lf.fieldwrapper').children('input').on('keydown',function(e){
            if(e.keyCode == 13) jQuery(this).blur();
        });
        
        //маска для баланса
        jQuery("input#balance").mask('000 000 000 000 000', {reverse: true});
        //маски для дат
        jQuery("input[name='date_start']").mask('00.00.0000', {reverse: true});
        jQuery("input[name='date_end']").mask('00.00.0000', {reverse: true});
        
        //если есть объявления, запускаем обновление данных
        if(jQuery('.adv-list').children().length>0){
            refresh_handler();
        }
        
        //если дата старта кампании позже чем сегодня, блокируем переключение "активна/не активна"
        var _now = Date.now();
        var _start = jQuery('.adv-campaign-edit-block').children('.top-block').children('.period').children('.from').children('input').val().split('.');
        var _date_start = Date.parse('23:59 ' + _start[1] + '/' + _start[0] + '/' + _start[2]);
        if(_date_start > _now && Math.ceil((_date_start - _now) / (1000 * 3600 * 24)) > 1)
            jQuery('.adv-campaign-edit-block').children('.top-block').children('.status').children('.switcher').addClass('checked').addClass('fixed');
        else
            jQuery('.adv-campaign-edit-block').children('.top-block').children('.status').children('.switcher').removeClass('fixed');
        jQuery('#id_place').change();
        
        jQuery('.datetimepicker').on('click',function(){
            setTimeout("jQuery('.xdsoft_datetimepicker').css('top','290px');clearTimeout();",50);
        });
        
        //подрезаем слишком длинные заголовки объявлений, чтобы лезли в одну строчку
        jQuery('.adv.title').each(function(){
            while(jQuery(this).height()>23){
                jQuery(this).html(jQuery(this).html().substring(0,jQuery(this).html().length-5)+'...');
            }
        });
        //если через # передан id объявления, которое нужно
        if(document.URL.indexOf('#') != -1){
            var _adv_id = document.URL.substring(document.URL.indexOf("#")+1);
            jQuery('#context-block-' + _adv_id).children('.object-actions').children('.edit').click();
        }
    });