jQuery(document).ready(function(){ 
    
    jQuery('.list_table table td input').on('change', function(){
        var _el = jQuery(this);
        _el.addClass('wait');
        startPendingAction(_el.attr('data-url'),{field: _el.attr('name'), value: _el.val()}, function(){_el.removeClass('wait');})
    });

    
    //форма для редактирования очередей объекта
    getPendingContent('.object-queries-edit','queries-block/');
    
    // смена менеджера
    jQuery('.change-manager').on('change', function(){
        jQuery(this).next('input').fadeIn();
    })
    jQuery('.save-manager').each(function(){
        jQuery(this).on('click',function(){
            _this = jQuery(this)
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/estate/housing_estates/save_manager/',
                data: {ajax: true, id: _this.data('id'), id_manager: _this.siblings('.change-manager').val()},
                success: function(msg){ 
                    if(msg.ok)  _this.fadeOut();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            });
            
        })
    });
    
    //progress
    var _progress_wrap = jQuery('.progress-wrap');
    _max_id = parseInt(_progress_wrap.data('max-id'));

    _years = '<select class="year-select"><option value="0">-выберите год-</option>';
    var _current_year = new Date().getFullYear();
    for(_year=_current_year;_year>=2012;_year--){
        _years = _years + '<option value="'+_year+'">'+_year+'</option>';
    }
    _years = _years + '</select>';

    jQuery('.add', _progress_wrap).on('click', function(){
        _max_id = _max_id + 1;
        jQuery('.progress-list').prepend(  '<fieldset class="item_'+_max_id+'" data-id="'+_max_id+'">'+
                                '<select class="month-select"><option value="0">-выберите месяц-</option><option value="1">январь</option><option value="2">февраль</option><option value="3">март</option><option value="4">апрель</option><option value="5">май</option><option value="6">июнь</option><option value="7">июль</option><option value="8">август</option><option value="9">сентябрь</option><option value="10">октябрь</option><option value="11">ноябрь</option><option value="12">декабрь</option></select>'+
                                _years + 
                                '<span class="original-photo" style="margin-left: 20px;">Оригинальные фото<select class="original_photo-select"><option value="1">Да</option><option value="2">Нет</option></select></span>'+
                                '<span class="delete-item" title="Удалить запись"></span><input type="file" name="file_upload" id="file_upload_'+_max_id+'" data-id="'+_max_id+'" data-url="/admin/estate/housing_estates/progresses/photos/" data-session-id="'+_progress_wrap.data('session-id')+'" /></fieldset>');
        var _fieldset = jQuery('fieldset.item_'+_max_id, _progress_wrap);
        jQuery('#file_upload_'+_max_id, _fieldset).uploadifive();
        
    })
    
    jQuery('fieldset', _progress_wrap).each(function(){
        var _fieldset = jQuery(this);
        var _id = _fieldset.data('id');
        jQuery('#file_upload_'+_id, _fieldset).uploadifive();
    })
    
    jQuery(document).on('change', '.month-select, .year-select, .original_photo-select', function(){
        var _this = jQuery(this);
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: '/admin/estate/housing_estates/progresses/add/',
            data: {ajax: true, 
                   id:_this.parents('fieldset').data('id'), 
                   type: _this.attr('class').replace('-select',''), 
                   value:_this.val(), 
                   id_parent:jQuery('h1.pageTitle').data('id')
            },
            success: function(msg){ 
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            }
        });
        
    })
    
    jQuery(document).on('click', '.delete-item', function(){
        if(!confirm('Вы уверены, что нужно удалить эту запись?')) return false;
        var _fieldset = jQuery(this).parent('fieldset');
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: '/admin/estate/housing_estates/progresses/del/',
            data: {ajax: true, id: _fieldset.data('id')},
            success: function(msg){ 
                _fieldset.fadeOut(500);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            }
        });
    })
    
    
});


/* Housing estate queries */
jQuery(document).ready(function(){
    if(jQuery('.object-queries-edit').length > 0){
          jQuery(document).on("click", '.add-query', function(){
                //считаем сколько уже очередь и добавляем следующую
                var _num_queries = jQuery('div[id^="query"]').length - 1;
                _num_queries++; 
                //удаляем кнопки "Удалить очередь" у старых очередей
                jQuery('.delete-query').addClass('nodelete').parent().addClass('nodelete');
                //создаем новую очередь с соответствующим порядковым номером
                var _el = jQuery('.hidden-build-complete-content');
                jQuery('.query-complete-box', _el).attr('id', 'query_' + _num_queries);
                jQuery('.query-complete-box .item:first b', _el).text('Очередь ' + _num_queries);
                jQuery('.queries-edit-box').append(_el.html());
                jQuery('.query-complete-box', _el).attr('id', 'query_');
                //отключаем select у общего срока сдачи
                jQuery('#object-complete').find('select').prop('disabled',true);
                jQuery('#object-complete').find('select').val("");
                //убираем флажок "Сохранено"
                jQuery('.notification').removeClass('msgsuccess');
            });
            
            //убираем флажок "Сохранено" при корректировании данных
            jQuery(document).on("change", '.query-complete-box select', function(){
                jQuery('.notification').removeClass('msgsuccess');
            });
            
            jQuery(document).on("click", ".delete-query",function(){
                if(jQuery(this).hasClass('nodelete')) return false;
                if(!confirm("Вы уверены, что хотите удалить эту очередь?")) return false;
                jQuery(this).parents('.query-complete-box').remove();
                
                if(jQuery('div[id^="query"]').length == 0){
                    //если очередей нет, включаем select  общего срока сдачи
                    jQuery('#object-complete').find('select').prop('disabled',false);
                }else{
                    //у последней из оставшихся очередей(если они есть) убираем класс nodelete, чтобы можно было удалить
                    jQuery('.query-complete-box:visible').last().find('.nodelete').removeClass('nodelete');
                }
                jQuery('.notification').removeClass('msgsuccess');
                
                //jQuery('.delete-query').last().removeClass('nodelete').parent().removeClass('nodelete');
                
            });
            
            jQuery(document).on("click", '#save-queries', function(){
                var _params = new Array();
                var _queries_list = jQuery('.query-complete-box.clearfix:visible');
                for (var i = 0; i < _queries_list.length; i++){
                    var _query = parseInt(i + 1);
                    var _wrap = jQuery('#query_' + _query)
                    if(_wrap.length > 0){
                        _params.push({
                            query : _query,
                            num : jQuery('.num', _wrap).val(),
                            month : jQuery('.month', _wrap).val(),
                            year : jQuery('.year', _wrap).val(),
                            corpuses : jQuery('.object-corpuses', _wrap).val()
                        })
                    }
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: 'queries-edit/',
                    data: {ajax: true, values:_params},
                    success: function(msg){
                        if(typeof(msg)=='object' && msg.ok) {
                            jQuery('.notification').addClass('msgsuccess').text("Сохранено");
                        } else{
                            alert('Ошибка запроса к серверу!:'+msg.text);
                            jQuery('.notification').removeClass('msgsuccess');
                        } 
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        alert('Запрос не выполнен!');
                        jQuery('.notification').removeClass('msgsuccess');
                    },
                });
                return false;
            });
            //если указаны очереди, отключаем поля ввода(срок сдачи и корпуса) для самого объекта
            if(jQuery('.query-complete-box').length>0){
                jQuery('#object-complete').find('select').prop('disabled',true);
            }
            //отключаем редактирование вручную очередей, корпусов и сроков сдачи очередей
            jQuery('#phases').prop('disabled',true);
            jQuery('#korpuses').prop('disabled',true);
            jQuery('#build_complete').prop('disabled',true);
    }
});