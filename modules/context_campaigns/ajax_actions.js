jQuery(document).ready(function(){
    jQuery('.price-input').on("blur",function(){
        //определяем тип цены(верхняя/нижняя)
        var _price_type = jQuery(this).attr('id');
        if(_price_type == 'input-price-floor') _price_type = 1;
        else if(_price_type == 'input-price-top') _price_type = 2;
        else return false;
        var _price_value = parseInt(jQuery(this).val());
        if(!(jQuery('#input-price-floor').val()<=jQuery('#input-price-top').val())){
            alert('Верхняя граница цены должна быть больше нижней');
            return false;
        }
        //если что-то изменилось и цена отлична от нулевой, записываем
        if(_price_value!=undefined && _price_value>0)
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: 'targeting/edit/'+_price_type+'/'+_price_value,
                success: function(msg){
                    return true;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
    });
    jQuery('.tg-item').on("click",function(){
        _tg_item = jQuery(this);
        if(jQuery(this).hasClass("selected")){
            //если тег выделен и мы щелкаем, значит убираем тег
            var _tag_id = jQuery(this).attr('id');
            if( _tag_id==undefined || _tag_id<=0){
                alert('Ошибка удаления!');
                return false;
            }
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: 'targeting/delete/',
                data: {ajax: true,tag_id: _tag_id},
                success: function(msg){
                    _tg_item.removeClass('selected');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        }
        else{
            //если тег не выделен, выделяем его и добавляем в таргетинг кампании
            var _tag_info = jQuery(this).attr('id');
            //раздел, к которому принадлежит тег
            var _tag_group = jQuery(this).parent().attr('data-type');
            var _source_id = jQuery(this).attr('data-source-id');
            //ограничения применения тега
            var _tag_restrictions ="";
            //если id нету, значит тег нужно будет сначала создать
            if(_tag_info == undefined){
                //читаем текстовое значение
                _tag_info = jQuery(this).html();
                //читаем ограничения применения
                _tag_restrictions = jQuery(this).attr('data-restrictions');
            }
            //если все еще ничего, ошибка
            if( _tag_info==undefined ){
                alert('Ошибка добавления!');
                return false;
            }
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: 'targeting/add/',
                data: {ajax: true,tag_group: _tag_group, tag_info: _tag_info, source_id: _source_id, tag_restrictions: _tag_restrictions},
                success: function(msg){
                    _tg_item.addClass('selected');
                    jQuery('delayed_sql').html(jQuery('delayed_sql').html(),msg['delayed_sql']);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        }
    });
    //fileuploader init
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({'queueSizeLimit':200});
    }
    
    //скрываем типы объектов для других типов недвижимости
        function estate_type_specials(){
            var _estate_type = jQuery(this).val();
            //сначала все прячем
            jQuery('.object_types').hide();
            jQuery('.rooms').children('.tg-item').hide();
            jQuery('.subways').children('.tg-item').hide();
            jQuery('.districts').children('.tg-item').hide();
            jQuery('.district_areas').children('.tg-item').hide();
            jQuery('.type-live-box').hide();
            jQuery('.type-commercial-box').hide();
            jQuery('.type-country-box').hide();
            //жилая и стройка
            if((jQuery('#estate_type').val().match(/1/)!=null)||(jQuery('#estate_type').val().match(/2/)!=null)){
                jQuery('.rooms').children('.tg-item').show();
                jQuery('.subways').children('.tg-item').show();
                jQuery('.districts').children('.tg-item').show();
                jQuery('.district_areas').children('.tg-item').show();
                if(jQuery('#estate_type').val().match(/1/)!=null){
                    jQuery('.object_types').show();
                    jQuery('.type-live-box').show();
                } 
            }
            //загородная
            if(jQuery('#estate_type').val().match(/4/)!=null){
                jQuery('.subways').children('.tg-item').show();
                jQuery('.district_areas').children('.tg-item').show();
                jQuery('.type-country-box').show();
                jQuery('.object_types').show();
            }
            //коммерческая
            if(jQuery('#estate_type').val().match(/3/)!=null){
                jQuery('.subways').children('.tg-item').show();
                jQuery('.districts').children('.tg-item').show();
                jQuery('.district_areas').children('.tg-item').show();
                jQuery('.type-commercial-box').show();
                jQuery('.object_types').show();
            }
        }
    
    //скрываем типы объектов для других типов недвижимости
    jQuery('#estate_type').change(function(){
        var _estate_type = jQuery(this).val();
        
        switch(true){
            //жилая и стройка
            case (jQuery('#estate_type').val().match(/1/)):
            case (jQuery('#estate_type').val().match(/2/)):
                
                jQuery('.rooms').children('.tg-item').show();
                jQuery('.subways').children('.tg-item').show();
                jQuery('.districts').children('.tg-item').show();
                jQuery('.district_areas').children('.tg-item').show();
                if(_estate_type == "1") jQuery('.type-live-box').show();
                break;
            //коммерческая
            case (jQuery('#estate_type').val().match(/3/)):
                jQuery('.subways').children('.tg-item').show();
                jQuery('.districts').children('.tg-item').show();
                jQuery('.district_areas').children('.tg-item').show();
                jQuery('.type-commercial-box').show();
                break;
            //загородная
            case (jQuery('#estate_type').val().match(/4/)):
                jQuery('.subways').children('.tg-item').show();
                jQuery('.district_areas').children('.tg-item').show();
                jQuery('.type-country-box').show();
                break;
            //никакая
            case (jQuery('#estate_type').val().match(/0/)):
                jQuery('.rooms').children('.tg-item').hide();
                jQuery('.subways').children('.tg-item').hide();
                jQuery('.districts').children('.tg-item').hide();
                jQuery('.district_areas').children('.tg-item').hide();
                jQuery('.type-live-box').hide();
                jQuery('.type-commercial-box').hide();
                jQuery('.type-country-box').hide();
                break;
        }
    });
    
    //копирование рекламной кампании из списка
    jQuery(document).on('click','.ico_copy',function(){
        var _url = jQuery(this).attr('data-href');
        _obj = jQuery(this).parents('.context-adv');
        if (confirm("Вы уверены, что хотите скопировать этот контекстный рекламный блок?"))
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url,
            data: {ajax: true},
            success: function(msg){
                _obj.after(jQuery('<div class="context-adv"></div>'));
                var _next = _obj.next()
                switch(msg.status){
                    case 1: _next.addClass('active');
                    case 2: _next.addClass('unactive');
                }
                _next.append(_obj.html());
                
                var _adv_title = _next.find('.adv-title');
                _adv_title.children().first().html('#' + msg.id);
                _adv_title.children().last().html(msg.title);
                
                var _small_icons = _next.find('.small_icons');
                _small_icons.attr('data-id',msg.id);
                _small_icons.children('a').first().attr('href',_small_icons.children('a').attr('href').replace(/edit\/\d+/g,'edit/' + msg.id));
                _small_icons.find('.ico_copy').attr('data-href',_small_icons.find('.ico_copy').attr('data-href').replace(/copy\/\d+/g,'copy/' + msg.id));
                _small_icons.find('.ico_del').attr('data-href',_small_icons.find('.ico_del').attr('data-href').replace(/del\/\d+/g,'del/' + msg.id));
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                return false;
            },
            complete: function(){
            }
        });
    });
    //удаление рекламной кампании из списка
    //jQuery('.ico_del').click(function(){
    jQuery(document).on('click','.ico_del',function(){
        var _url = jQuery(this).attr('data-href');
        var _text = "";
        _obj = jQuery(this).parents('.context-adv');
        if(jQuery(this).parents('.context-adv').length == 0){
            _obj = jQuery(this).parents('.context-campaign');
            _text = " эту контестную рекламную кампанию?";
        }
        else{
            _obj = jQuery(this).parents('.context-adv');
            _text = " этот контекстный рекламный блок";
        }
        if (confirm("Вы уверены, что хотите удалить"+_text))
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url,
            data: {ajax: true},
            success: function(msg){
                _obj.fadeOut(500,function(){_obj.remove();});
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                return false;
            },
            complete: function(){
            }
        });
    });
    jQuery('#estate_type').change();
    //если цену уже можно редактировать, сдвигаем поля цены из формы в область редактирования таргетинга, где они и должны быть
    if(jQuery('#input-price-floor').length>0){
        jQuery('#price_floor').attr('type',"text");
        jQuery('#price_floor').show();
        jQuery('#price_floor').offset(jQuery('#input-price-floor').offset());
        jQuery('#price_top').attr('type',"text");
        jQuery('#price_top').show();
        jQuery('#price_top').offset(jQuery('#input-price-top').offset());
    }
    else{
        //скрываем возможность опубликовать при добавлении
        if(jQuery('.form_default').length>0)
            if(jQuery('.form_default').attr('action').match('[0-9]\/add').length>0)
                jQuery('#p_field_published').hide();
    }
    
    //обработчики для фильтров в блоке статистики
    jQuery("#f_campaign").on("change", function(event){
        if (jQuery('#date_start').val().length!=0 && jQuery('#date_start').val().length>0 && jQuery('#date_end').val().length>0)
            filter_activate("");
    });
    jQuery("#f_agency").on("change", function(){
        filter_activate("");
    });
    jQuery('#f_place').on('change',function(){
        filter_activate("");
    });
    jQuery('#date_start').on('change',function(){
        filter_activate("");
    });
    jQuery('#date_end').on('change',function(){
        filter_activate("");
    });
    function refresh_handler() {
        function refresh() {
           window.location.reload();
        }
        //setInterval(refresh, 30*1000); //every 5 minutes
    }
    if(jQuery('.adv-list').length>0){
        refresh_handler();
    }
});