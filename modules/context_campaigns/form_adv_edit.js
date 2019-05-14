_first_open_object_types = true;
jQuery(document).ready(function(){
    
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
    
    //если это новое объявление, скрываем кнопку для добавления картинки:
    if(!jQuery('.adv-fields').attr('data-id')) jQuery('.upload-block').hide();
    
    //переносим поля формы в левый столбец верхнего блока:
    jQuery('.adv-fields').children('.adv-fields-column.left').html(jQuery('.form-fields').html());
    
    //чистим то, откуда переносили
    jQuery('.form-fields').html("");
    
    //переносим select с выбором места размещения
    jQuery('.list-selector.id_place').children('#span_field_id_place').hide();
    jQuery('#p_field_id_place').children('.lf.fieldwrapper').html(jQuery('.list-selector.id_place'));
    
    //переносим поле с названием наверх
    jQuery('.adv-fields').children('.adv-fields-row.top').html(jQuery('.adv-fields-column.left').children('#p_field_title'));
    jQuery('.object-actions.inner').insertAfter(jQuery('.adv-fields').children('.adv-fields-row.top').children('#p_field_title'));
    jQuery('.adv-fields-column.left').children('#p_field_title').remove();
    
    //приводим поле в нужный вид: название с подчеркиванием, скрываем нужные поля, добавляем справа кнопку удаления
    var _adv_title = jQuery('.adv-fields').children('.adv-fields-row.top').children('#p_field_title');
    _adv_title.children('label').hide();
    _adv_title.append("<span class='adv-title active'><i class='title'></i></span><span class='note'><i>←</i>Нажмите, чтобы редактировать</span>");
    
    //если название не заполнено (например для нового объявления), пишем надпись, чтобы поле было видно
    if(_adv_title.children('.lf').children('input').val().length>0) _adv_title.children('.adv-title').children('i.title').html(_adv_title.children('.lf').children('input').val());
    else _adv_title.children('.adv-title').children('i.title').html("Название объявления");
    _adv_title.children('.lf').insertAfter(_adv_title.children('.adv-title'));
    
    //обрабатываем клик по названию для редактирования
    jQuery('.title').on('click',function(){
        jQuery(this).parent().parent().children('.lf.fieldwrapper').toggleClass('active');
        jQuery(this).parent().toggleClass('active');
        jQuery(this).parent().parent().children('.lf.fieldwrapper').children('input').focus();
        jQuery(this).parent().parent().children('.note').hide();
    });
    
    //обрабатываем нажатие Enter по полю ввода названия
    jQuery('.adv-fields').children('.adv-fields-row').children('#p_field_title').children('.lf.fieldwrapper').children('input').on('keydown',function(e){
        if(e.keyCode == 13) jQuery(this).blur();
    });
    
    //скрываем input и показываем измененное название по blur
    _adv_title.on('blur','.lf.active',function(){
        jQuery(this).toggleClass('active');
        jQuery(this).parent().children('.adv-title').toggleClass('active');
        //если значение не пусто, показываем его, иначе пишем надпись, чтобы было видно поле
        if(jQuery(this).children('input').val().length>0) jQuery(this).parent().children('.adv-title').children('i').html(jQuery(this).children('input').val());
        else jQuery(this).parent().children('.adv-title').children('i').html("Название объявления");
        jQuery(this).parent().children('.note').show();
    });
    
    //переносим поле со ссылкой вниз
    jQuery('.adv-fields').children('.adv-fields-row.bottom').html(jQuery('.adv-fields-column.left').children('#p_field_url'));
    jQuery('.adv-fields-column.left').children('#p_field_url').remove();
    
    var _adv_url =  jQuery('.adv-fields').children('.adv-fields-row.bottom').children('#p_field_url');
    //вставляем блоки с галочками для типа недвижимости и типа сделки
    jQuery('.deal-type-block').insertAfter(jQuery('.adv-fields').children('.adv-fields-column.left').children('#p_field_id_place'));
    jQuery('.estate-type-block').insertAfter(jQuery('.adv-fields').children('.adv-fields-column.left').children('#p_field_id_place'));
    //переносим поля для текста и заголовка тгб-таргета под ссылку
    jQuery('#p_field_banner_title').insertAfter('#p_field_url');
    jQuery('#p_field_banner_text').insertAfter('#p_field_banner_title');
    //убираем поле "Описание к баннеру"
    jQuery('#p_field_description').remove();
    //перенеосим поле со ссылкой на пиксель
    jQuery('.adv-fields-column.left').children('#p_field_get_pixel').insertAfter('#p_field_url');
    //переносим блок "статус объявления" в самый низ шаблона
    jQuery('.adv-fields').parent().children('.bottom-block').html(jQuery('.adv-fields-column.left').children('#p_field_published').html());
    jQuery('.adv-fields-column.left').children('#p_field_published').remove();
    jQuery('.adv-fields').parent().children('.bottom-block').attr('id','p_field_published');
    //переносим новую кнопку для загрузки картинки в левую колонку под выбор места размещенияs
    jQuery('.image-upload-block').insertAfter(jQuery('.adv-fields-column.left').children('#p_field_id_place'));
    //переносим блок с upload
    jQuery('.image-upload-block').children('span').html(jQuery('.upload-block'));
    
    //обрабатывем нажатия по кнопкам комнатности
    jQuery('button').unbind('click');
    
    jQuery('.room-tg-item').click(function(){
        jQuery(this).toggleClass('selected');
    });
    
    //скрываем типы объектов для других типов недвижимости
    jQuery('#estate_type').change(function(){
        var _estate_type = jQuery(this).val();
        //сначала все прячем
        jQuery('.tg.object_types').addClass('unactive');
        jQuery('.tg.rooms').addClass('unactive')
        jQuery('.tg.subways').addClass('unactive');
        jQuery('.tg.districts').addClass('unactive');
        jQuery('.tg.district_areas').addClass('unactive');
        jQuery('.type-live-box').hide();
        jQuery('.type-commercial-box').hide();
        jQuery('.type-country-box').hide();
        //жилая и стройка
        if((jQuery('#estate_type').val().match(/1/g)!=null)||(jQuery('#estate_type').val().match(/2/g)!=null)){
            if(jQuery('#estate_type').val().match(/1/g)!=null) jQuery('.tg.object_types').removeClass('unactive');
            jQuery('.tg.rooms').removeClass('unactive');
            jQuery('.tg.subways').removeClass('unactive');
            jQuery('.tg.districts').removeClass('unactive');
            jQuery('.tg.district_areas').removeClass('unactive');
            if(jQuery('#estate_type').val().match(/1/g)!=null){
                jQuery('.tg.object_types').show();
                jQuery('.type-live-box').show();
            } 
        }
        //загородная
        if(jQuery('#estate_type').val().match(/4/g)!=null){
            jQuery('.tg.object_types').removeClass('unactive');
            jQuery('.tg.subways').removeClass('unactive');
            jQuery('.tg.subways').children('.tg-item').show();
            jQuery('.tg.district_areas').removeClass('unactive');
            jQuery('.tg.district_areas').children('.tg-item').show();
            jQuery('.type-country-box').show();
            jQuery('.tg.object_types').show();
        }
        //коммерческая
        if(jQuery('#estate_type').val().match(/3/g)!=null){
            jQuery('.tg.object_types').removeClass('unactive');
            jQuery('.tg.subways').removeClass('unactive');
            jQuery('.tg.districts').removeClass('unactive');
            jQuery('.tg.district_areas').removeClass('unactive');
            jQuery('.type-commercial-box').show();
            jQuery('.tg.object_types').show();
        }
    });
    
    //обработка клика по красивому чекбоксу
    jQuery('.checkbox').on('click',function(){
        //radio
        if(jQuery(this).hasClass('radio')){
            //если щелкнули по отключенному просто выходим
            if(jQuery(this).children().attr('disabled') != undefined) return false;
            //выключаем все остальные radio в группе
            jQuery(this).parent().children('.checkbox').removeClass('on');
            //включаем наш
            jQuery(this).toggleClass('on');
            jQuery(this).children('input').change();
        }
        //checkbox
        else{
            jQuery(this).toggleClass('on');
            //
            if(jQuery(this).hasClass('on')){
                switch(true){
                    case jQuery(this).children('#estate-live').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val()+"1");
                        break;
                    case jQuery(this).children('#estate-build').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val()+"2");
                        break;
                    case jQuery(this).children('#estate-commercial').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val()+"3");
                        break;
                    case jQuery(this).children('#estate-country').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val()+"4");
                        break;
                    case jQuery(this).children('#deal-rent').length>0:
                        jQuery('#deal_type').val(jQuery('#deal_type').val()+"1");
                        break;
                    case jQuery(this).children('#deal-sell').length>0:
                        jQuery('#deal_type').val(jQuery('#deal_type').val()+"2");
                        break;
                }
            }
            else{
                switch(true){
                    case jQuery(this).children('#estate-live').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val().replace(/1/g,''));
                        break;
                    case jQuery(this).children('#estate-build').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val().replace(/2/g,''));
                        break;
                    case jQuery(this).children('#estate-commercial').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val().replace(/3/g,''));
                        break;
                    case jQuery(this).children('#estate-country').length>0:
                        jQuery('#estate_type').val(jQuery('#estate_type').val().replace(/4/g,''));
                        break;
                    case jQuery(this).children('#deal-rent').length>0:
                        jQuery('#deal_type').val(jQuery('#deal_type').val().replace(/1/g,''));
                        break;
                    case jQuery(this).children('#deal-sell').length>0:
                        jQuery('#deal_type').val(jQuery('#deal_type').val().replace(/2/g,''));
                        break;
                }
                var _estate_type = "";
                jQuery('.estate-type-block .options-list').children('.checkbox.on').each(function(){
                    _estate_type += jQuery(this).attr('data-value');
                });
                //alert(_estate_type);
                //чистим тип объекта
                jQuery('.tg.object_types .tg-list').children('span').click();
                jQuery('#estate-type').val(_estate_type).change();
            }
            
            //исходя из типов недвижимости, ограничиваем теги
            
            switch(true){
                case jQuery('#estate_type').val().match(/1|2/g) != null:
                    if(jQuery('#estate_type').val().match(/2/g)) jQuery('.tg.object_types').removeClass('unactive');
                    jQuery('.tg.rooms').removeClass('unactive');
                    jQuery('.tg.districts').removeClass('unactive');
                    break;
                case jQuery('#estate_type').val().match(/^3$/) != null:
                    jQuery('.tg.rooms').addClass('unactive');
                    break;
                case jQuery('#estate_type').val().match(/^4$/) != null:
                    jQuery('.tg.districts').addClass('unactive');
                    jQuery('.tg.rooms').addClass('unactive');
                    break;
            }
            
            if(jQuery('#estate_type').val().match(/3/) != null) jQuery('.tg.districts').removeClass('unactive');
            //jQuery('.tg.rooms').children('.room-tg-item').removeClass('selected');
        }
    });
    
    //обрабатываем нажатие по кнопке для загрузки изображений (щелкаем по скрытой старой)
    jQuery('.image-upload').on("click",function(){
        jQuery('#uploadInput').trigger('change');
        jQuery(this).parents('.upload-block').removeClass('error');
    });
    
    //обрабатываем нажатия по галочкам для типов сделки - изменяем значение невидимого поля формы
    jQuery('#deal_type').val(jQuery('#deal_type').val().replace(/0/,''));
    jQuery('#estate_type').val(jQuery('#estate_type').val().replace(/0/,''));
    
    //fileuploader init
    function init_uploader(){
        if(jQuery('#file_upload').length>0){
            jQuery('#file_upload').attr('data-url',jQuery('#file_upload').children('input').attr('data-url'));
            jQuery('#file_upload').attr('data-id',jQuery('#file_upload').children('input').attr('data-id'));
            jQuery('#file_upload').uploadifive({
                'queueSizeLimit':1,
                'buttonText':'Загрузить изображение',
                onChangeCount: function(){
                    //var _photos_count = parseInt(jQuery('#totalObjects').text());
                    var _photos_count = parseInt(jQuery('.itemsContainer').children('img').length);
                    //если загрузили картинку, пихаем ее направо
                    if(_photos_count == 1){
                        var _src = jQuery('.boxcaption_main').siblings('.itemsContainer').children('img').attr('src');
                        jQuery('.position-image').removeClass('active');
                        jQuery('.public-image').addClass('active');
                        _src = _src.replace(/\/sm\//,'/big/');
                        jQuery('.public-image').children('img').attr('src',_src);
                        //пихаем картинку в превьюшку снаружи
                        jQuery('.public-image').parents('.context-adv').children('.left-block-box').children('.img-preview').children('.campaign-img-box').children('img').attr('src',_src);
                        jQuery('.')
                    } else {
                        //если картинок не осталось, меняем убранную картинку на схему
                        var _url = "";
                        var _value = jQuery('#id_place').val();
                        switch(_value){
                            case "1":_url = "/img/layout/banner-search-r.png";break;
                            case "2":_url = "/img/layout/banner-item.png";break;
                            case "4":_url = "/img/layout/banner-search-c.png";break;
                        }
                        jQuery('.position-image').addClass('active');
                        jQuery('.position-image').css("background","url('"+_url+"')");
                        jQuery('.public-image').removeClass('active');
                        jQuery('.public-image').children('img').attr('src','');
                        //убираем картинку из превьюшки снаружи
                        jQuery('.public-image').parents('.context-adv').children('.left-block-box').children('.img-preview').children('.campaign-img-box').children('img').attr('src','');
                    }
                }
            });
        }
    }
    
    
    //разбивка на столбцы по типам недвижимости (жилая, коммерческая, загородная)
    function splitList(list,title,split_field){
        var colblocks = Array();
        var _j = 0;
        var _live = -1;var _commercial = -1;var _country = -1;
        if(jQuery('#estate-live').parent().hasClass('on')){
            colblocks[_j] = '<div class="column col'+(_j+1)+'"><div class="abc-block"><span class="letter">Жилая</span>';
            _live = _j;
            _j++;
        } 
        if(jQuery('#estate-commercial').parent().hasClass('on')){
            colblocks[_j] = '<div class="column col'+(_j+1)+'"><div class="abc-block"><span class="letter">Коммерческая</span>';
            _commercial = _j;
            _j++;
        } 
        if(jQuery('#estate-country').parent().hasClass('on')){
            colblocks[_j] = '<div class="column col'+(_j+1)+'"><div class="abc-block"><span class="letter">Загородная</span>';
            _country = _j;
            _j++;
        }
        for(var i=0;i<list.length;i++){
            switch(list[i]['txt_field']){
                case 'type_objects_live':j = _live;break;
                case 'type_objects_commercial':j = _commercial;break;
                case 'type_objects_country':j = _country;break;
            }
            if(colblocks[j])
                colblocks[j] += '<a data-item-txt="' + list[i].title + '" data-item-id="' + list[i].id+'" data-item-similar="'+list[i].estate_type+'"' + (list[i].selected?' class="selected"':'') + '>' + list[i].title + '</a>';
        }
        //alert('<div class="items-list"><h3>' + title + '</h3>' + colblocks.join('</div></div>') + '</div></div>');
        return '<div class="items-list fitted">' + colblocks.join('</div></div>') + '</div></div>';
    }
    
    function formatList(list, title, columns_count){
        if(arguments.length<2) columns_count = 4;
        var _blocks = new Array();
        var cur_block = '';
        var abc = '%';
        var cur_abc = '';
        for(var i=0;i<list.length;i++){
            cur_abc = list[i].title.substr(0,1).toUpperCase();
            if(abc != cur_abc) {
                if(i>0) {
                    cur_block += '</div>';
                    _blocks.push(cur_block);
                }
                abc = cur_abc;
                cur_block = '<div class="abc-block"><span class="letter">'+abc+'</span>';
            }
            cur_block += '<a data-item-txt="'+list[i].title+'" data-item-id="'+list[i].id+'"'+(list[i].selected?' class="selected"':'')+'>'+list[i].title+'</a>';
        }
        if(list.length>0) {
            cur_block += '</div>';
            _blocks.push(cur_block);
        }   
        var colblocks = new Array();
        for(i=0;i<columns_count;i++) colblocks[i] = '<div class="column col'+(i+1)+'">';
        var cnt = 0;
        var onepart = Math.ceil(_blocks.length/columns_count);
        for(i=0;i<_blocks.length;i++){
            cur_block = (cnt - cnt % onepart) / onepart + 1;
            colblocks[cur_block-1] += _blocks[i];
            cnt++;
        }
        return '<div class="items-list"><h3>'+title+'</h3>'+colblocks.join('</div>') + '</div></div>';
    }
    
    //обработчик для типов объектов отдельный, так как нужно обрабатывать одинаковые названия
    jQuery('.list-picker.object_types').children('input').on("change",function(){
        _tags_box = jQuery(this).parents('.tg.object_types').children('.tg-list');
        //убираем старые теги
        _tags_box.html("");
        
        var _tags_list = "";
        if(jQuery(this).attr("value").length>0) _tags_list = jQuery(this).attr("value").split('~');
        var _tag_ids = [];
        _this_class = 'object_types';
        //идем с шагом 2, так как для каждого тега дана пара id->title
        if(_tags_list.length > 0)
            for(var i=0;i<_tags_list.length;i+=2){
                _tags_box.html(_tags_box.html()+"<span class='tg-item' data-id='"+_tags_list[i]+"'>"+_tags_list[i+1]+"</span>");
                _tag_ids.push(_tags_list[i]);
            }
        if(_tags_list.length == 0){
            jQuery(this).siblings('.selected-tags-info').addClass('unactive');
            jQuery(this).siblings('.selected-tags-show-all').addClass('unactive');
        }
        else{
            jQuery(this).siblings('.selected-tags-info').removeClass('unactive').children('i').html(_tags_list.length/2);
            if(_tags_list.length > 20){
                jQuery('.tg.' + _this_class).find('.selected-tags-show-all').removeClass('unactive');
                //скрываем часть блока с тегами
                jQuery('.tg.' + _this_class).find('.tg-list').addClass('bottom-gradient-overflow');
            }
            else{
                jQuery('.tg.' + _this_class).find('.selected-tags-show-all').addClass('unactive');
                jQuery('.tg.' + _this_class).find('.tg-list').removeClass('bottom-gradient-overflow');
            } 
            jQuery(this).parents('.tg.'+_this_class).removeClass('error');
        }
        //убираем из поля value уже ненужные названия тегов, оставим только id
        jQuery(this).attr("value",_tag_ids.join(','));
    });
    
    //обработчик для показа тегов метро, района, района ЛО
    jQuery('.list-picker.subways, .list-picker.districts, .list-picker.district_areas').children('input').on("change",function(){
        //alert(jQuery(this).parents('.list-picker').attr('class') + jQuery(this).val());
        var _this_class = '';
        switch(true){
            case jQuery(this).parents('.tg').hasClass('subways'): _this_class = 'subways';break;
            case jQuery(this).parents('.tg').hasClass('districts'): _this_class = 'districts';break;
            case jQuery(this).parents('.tg').hasClass('district_areas'): _this_class = 'district_areas';break;
            default: return false;
        }
        _tags_box = jQuery(this).parents('.tg.'+_this_class).children('.tg-list');
        //убираем старые теги
        _tags_box.html("");
        if(jQuery(this).val() != ''){
            var _tags_list = jQuery(this).val().split(',');
            var _tag_ids = [];
            //идем с шагом 2, так как для каждого тега дана пара id->title
            if(_tags_list.length>0)
                for(var i=0;i<_tags_list.length;i+=2){
                        _tags_box.html(_tags_box.html()+"<span class='tg-item' data-id='"+_tags_list[i]+"'>"+_tags_list[i+1]+"</span>");
                        _tag_ids.push(_tags_list[i]);
                }
            //количество выбранных
            //alert(_tags_list.length + _this_class);
            if(_tags_list.length == 0){
                jQuery(this).siblings('.selected-tags-info').addClass('unactive');
                jQuery(this).siblings('.selected-tags-show-all').addClass('unactive');
            }
            else{
                jQuery(this).siblings('.selected-tags-info').removeClass('unactive').children('i').html(_tags_list.length/2);
                if(_tags_list.length > 20){
                    jQuery('.tg.' + _this_class).find('.selected-tags-show-all').removeClass('unactive');
                    //скрываем часть блока с тегами
                    jQuery('.tg.' + _this_class).find('.tg-list').addClass('bottom-gradient-overflow');
                }
                else{
                    jQuery('.tg.' + _this_class).find('.selected-tags-show-all').addClass('unactive');
                    jQuery('.tg.' + _this_class).find('.tg-list').removeClass('bottom-gradient-overflow');
                } 
                jQuery(this).parents('.tg.'+_this_class).removeClass('error');
            }
            //убираем из поля value уже ненужные названия тегов, оставим только id
            jQuery(this).attr("value",_tag_ids.join(','));
        }else{
            _tags_box.children('.tg-item').remove();
        }
    });
    
    //обработчик для "Показать остальные"
    jQuery('.tg .list-picker .selected-tags-show-all').on('click',function(){
        if(jQuery(this).hasClass('unactive')) return false
        var _changing = jQuery(this).html();
        jQuery(this).html(jQuery(this).attr('data-change')).parent().siblings('.tg-list').toggleClass('bottom-gradient-overflow');
        jQuery(this).attr('data-change',_changing);
    });
    
    _background_template = '<div id="background-shadow-expanded">'
                    +'<div id="background-shadow-expanded-wrapper"></div>'
                    +'</div>'
                +'</div>';
    
    jQuery('.list-picker').find('.pick').on('click',function(){
        var _this_class = jQuery(this).parents('.list-picker').attr('class').replace('list-picker ','').replace(' error','');
        switch(_this_class){
            case 'districts': _this_class = "districts";break;
            case 'district_areas': _this_class = "district-areas";break;
            case 'subways': _this_class = "subway";break;
            case 'object_types': _this_class = "object-types";break;
        }
        //отмечаем выбранные на всплывашке
        jQuery('.tg.geotargeting').find('input').each(function(){
            var _selected = jQuery(this).val();
            var _name = "";
            if(_selected.length > 0){
                _selected = _selected.split(',');
                _name = jQuery(this).attr('name');
                _name = _name.replace('_','-');
                jQuery('#geodata-picker-wrap.target-outed').find('.selected-items.' + _name +'-list').children('.item').each(function(){
                    if($.inArray(jQuery(this).attr('data-tag-id'),_selected) > -1) jQuery(this).click();
                });
            }
        });
        
        
        //отмечаем выбранные типы недвижимости на всплывашке
        jQuery('#popup-object-types').val(jQuery('.list-picker.object_types').find('input').val());
        jQuery('#popup-object-types').siblings('i').text(jQuery('.list-picker.object_types .selected-tags-info i').text()!=''?jQuery('.list-picker.object_types .selected-tags-info i').text():0);
        
        //если таргетинг отключен, убираем вкладку на всплывашке
        jQuery('.tg.geotargeting, .tg.object_types').each(function(){
            var _this_class = jQuery(this).children('.list-picker').attr('class').replace('list-picker ','');
            switch(_this_class){
                case 'districts': _this_class = "districts";break;
                case 'district_areas': _this_class = "district-areas";break;
                case 'subways': _this_class = "subway";break;
                case 'object_types': _this_class = "object-types";break;
            }
            if(jQuery(this).hasClass('unactive')) jQuery('#geodata-picker-wrap.target-outed .filter').children('.' + _this_class + '-picker').addClass('unactive');
            else jQuery('#geodata-picker-wrap.target-outed .filter').children('.' + _this_class + '-picker').removeClass('unactive');
        });
        
        jQuery('#geodata-picker-wrap.target-outed').attr('data-reset',"false");
        //alert('$%^' + _this_class);
        jQuery('.list-picker.location.outed').attr('data-active-tab',_this_class).click();
    });
    
    /* list-selector!! */
    _opened_listelector = null;
    jQuery(".list-selector").each(function(){
        var _selector = jQuery(this);
        jQuery(".list-selector").on("click",".pick",function(){
            _selector.toggleClass("dropped");
            if(_selector.hasClass("dropped")) _opened_listelector = _selector;
            else  _opened_listelector = null;
            return false;
        });
        jQuery(".list-selector").on("click",".select",function(){
            _selector.toggleClass("dropped");
            if(_selector.hasClass("dropped")) _opened_listelector = _selector;
            else  _opened_listelector = null;
            return false;
        });
        jQuery(".list-data li:not(.disabled)", _selector).click(function(event, first_call){
            if(typeof first_call == 'undefined') first_call = false;
            var _li = jQuery(this);
            _li.addClass("selected").siblings('li').removeClass("selected");
            if(_li.html()!=jQuery(".pick", _selector).html()){
                jQuery(".pick", _selector).html(_li.html()).attr('title',_li.html());
                _previous_value =  jQuery('input[type="hidden"]',_selector).val();
                jQuery('input[type="hidden"]',_selector).val(_li.attr("data-value"));
                if(!first_call) _selector.trigger('change', _li.html());
            }
            _selector.removeClass("dropped");
            jQuery('.list-selector.id_place').children('#id_place').change();
            
            jQuery('.list-selector.id_place').children('#id_place')
            _opened_listelector = null;
        });
        var _def_val = jQuery('input[type="hidden"]',_selector).val();
        var _active_item = jQuery('.list-data li[data-value="'+_def_val+'"]', _selector);
        if(!_active_item.size()) _active_item = jQuery('.list-data li:first', _selector);
        _active_item.trigger("click", true);
    });
    //
    
    //обрабатываем переключение места размещения(меняем схему, при наличии картинки, удаляем ее)
    jQuery('.list-selector.id_place').children('#id_place').on('change',function(){
        var _url = "";
        var _value = jQuery(this).val();
        switch(_value){
            case "1":_url = "/img/layout/banner-search-r.png";break;
            case "2":_url = "/img/layout/banner-item.png";break;
            case "4":_url = "/img/layout/banner-search-c.png";break;
            case "5":_url = "/img/layout/banner-item-pg.png";break;
        }
        
        //если изменили схему размещения, при наличии картинки, удаляем ее 
        if(jQuery(this).parents('.form-adv').children('.adv-fields').children('.right').children('.public-image').children('img').attr('src').length>0){
            jQuery('.boxcaption_del').click();
        }
        //вставляем схему
        jQuery(this).parents('.form-adv').children('.adv-fields').children('.right').children('.position-image').css("background","url('"+_url+"')");
    });
    
    //чтобы картинка отрисовалась при загрузке формы
    var _url = "";
    var _value = jQuery('.list-selector.id_place').children('#id_place').val();
    switch(_value){
        case "1":_url = "/img/layout/banner-search-r.png";break;
        case "2":_url = "/img/layout/banner-item.png";break;
        case "4":_url = "/img/layout/banner-search-c.png";break;
    }
    //если у объявления нет картинки, рисуем справа схему размещения
    if(jQuery('.list-selector.id_place').children('#id_place').parents('.form-adv').children('.adv-fields').children('.right').children('.public-image').children('img').attr('src').length==0){
        jQuery('.list-selector.id_place').children('#id_place').parents('.form-adv').children('.adv-fields').children('.right').children('.position-image').css("background","url('"+_url+"')");
    }
    //
    
    jQuery('#estate_type').change();
    init_uploader();
    //корректируем надпись на кнопке загрузки файла
    jQuery('.uploadifyButton').children('span').text("Загрузить изображение");
    jQuery('.uploadifyButton').children('span').css("cursor","pointer");
    
    //удаление картинки
    jQuery(document).on("click",'.delete-image',function(){
        if(!confirm('Вы уверены, что хотите удалить картинку?')) return false;
        jQuery('.public-image').removeClass('active');
        jQuery('.position-image').addClass('active');
        var _value = jQuery('#id_place').val();
        switch(_value){
            case "1":_url = "/img/layout/banner-search-r.png";break;
            case "2":_url = "/img/layout/banner-item.png";break;
            case "4":_url = "/img/layout/banner-search-c.png";break;
        }
        jQuery('.position-image').addClass('active');
        jQuery('.position-image').css("background","url('"+_url+"')");
        jQuery('.public-image').removeClass('active');
        jQuery('.itemsContainer').children('img').first().remove();
        //потому что объявление сразу уходит в архив
        //alert( jQuery(this).parents('.context-adv').find('.left-block-box .left-block').siblings('.img-preview').length);
        jQuery(this).parents('.context-adv').find('.left-block-box .left-block').removeClass('active').addClass('unactive').siblings('.img-preview').html("");
        jQuery('.boxcaption_del').click();
    });
    
    //при выборе "Изображение + текст" оставляем только 760x100 и галерею
    jQuery('#p_field_block_type').children('span').children('span').eq(1).on('click',function(){
        //прячем размеры блоков из селектора
        var _selector = jQuery('#p_field_id_place').children('.lf').children('.list-selector.id_place').children('.list-data');
        _selector.children().children('.block-size').hide();
        //убираем все места размещения кроме 760x100
        _selector.children('li[data-value=4]').siblings().addClass('disabled');
        _selector.children('li[data-value=5]').removeClass('disabled');
        //если старое место размещения уже не подходит, щелкаем по 760x100
        if(_selector.children('li.selected').hasClass('disabled')) _selector.children('li[data-value=4]').click();
        else _selector.children('li.selected').click();
        //делаем обязательными поля про заголовок и текст объявления
        jQuery('#p_field_banner_title').show().children('label').addClass('required').siblings('.lf').children('input').attr('required',"required");
        jQuery('#p_field_banner_text').show().children('label').addClass('required').siblings('.lf').children('input').attr('required',"required");
        //чтобы показывалось "Загрузить изображение 80x80"
        jQuery('#uploadInput').show();
        jQuery('#file_upload_queue').show();
        if(jQuery('#intext_img_sizes').length == 0)
            jQuery('#file_upload_queue').children('.uploadifyButton').append('<span id="intext_img_sizes">' + _selector.children('li[data-value=4]').attr('data-width_txtimg') + "x" + _selector.children('li[data-value=4]').attr('data-height_txtimg') + '</span>');
    });

    //при выборе "Текст" оставляем только 200x300 (и щелкаем по нему) и 200x100
    jQuery('#p_field_block_type').children('span').children('span').eq(2).on('click',function(){
        //прячем размеры картинки и кнопку загрузки
        jQuery('#intext_img_sizes').remove();
        //прячем кнопку загрузки фото
        jQuery('#file_upload_queue').hide();
        //прячем размеры блоков из селектора
        var _selector = jQuery('#p_field_id_place').children('.lf').children('.list-selector.id_place').children('.list-data');
        _selector.children().children('.block-size').hide();
        //делаем обязательными поля про заголовок и текст объявления
        jQuery('#p_field_banner_title').show().children('label').addClass('required').siblings('.lf').children('input').attr('required',"required");
        jQuery('#p_field_banner_text').show().children('label').addClass('required').siblings('.lf').children('input').attr('required',"required");
        _selector.children('li[data-value=1]').removeClass('disabled').siblings().addClass('disabled');
        _selector.children('li[data-value=2]').removeClass('disabled');
        if(_selector.children('li.selected').hasClass('disabled')) _selector.children('li[data-value=1]').click();
    });
    //чтобы сразу все сделалось при первоначальном выборе
    jQuery('#p_field_block_type').children('span').children('span.checkbox.on').click();
    
    //при выборе "Изображение" позволяем выбирать любой пункт
    jQuery('#p_field_block_type').children('span').children('span').eq(0).on('click',function(){
        var _list_selector = jQuery('#p_field_id_place').children('.lf').children('.list-selector.id_place');
        jQuery('#intext_img_sizes').remove();
        jQuery('#uploadInput').show();
        jQuery('#file_upload_queue').show();
        jQuery('#p_field_banner_title').hide();
        jQuery('#p_field_banner_text').hide();
        //чтобы показывалось "Загрузить изображение"
        _list_selector.children('a.pick').children('.block-size').show();
        _list_selector.children('.list-data').children().children('.block-size').show();
        _list_selector.children('.list-data').children().removeClass('disabled');
        //делаем необязательными поля про заголовок и текст объявления
        jQuery('#p_field_banner_title').hide().children('label').removeClass('required').siblings('.lf').children('input').removeAttr('required');
        jQuery('#p_field_banner_text').hide().children('label').removeClass('required').siblings('.lf').children('input').removeAttr('required');
        
    });
    
    //обработчик для переключателя поля статуса объявления (кликаем по скрытому старому элементу)
    jQuery('fieldset.targeting-block').children('.adv-status').children('.switcher').on('click',function(){
        jQuery(this).toggleClass('checked');
        if( jQuery(this).attr('data-status') == 1){
            jQuery(this).attr('data-status',2);
            jQuery(this).parent().parent().children('.lf.fieldwrapper').children('span').eq(1).click();
        }
        else{
            jQuery(this).attr('data-status',1);
            jQuery(this).parent().parent().children('.lf.fieldwrapper').children('span').first().click();
        }
    });
    
    //переносим блок с переключателем статуса объявления в блок #p_field_published
    jQuery('span.adv-status').insertAfter(jQuery('.form-adv').children('#p_field_published').children('.lf.fieldwrapper'));
    //прячем старый блок статуса (два чекбокса)
    jQuery('.form-adv').children('#p_field_published').children('.lf.fieldwrapper').hide();
    
    //по измнению select записываем в базу новые ограничения на картинку
    jQuery('.list-selector.id_place').children('.list-data').children('li').on('click',function(){
        var _id_place = jQuery('#id_place').val();
        var _id = jQuery(this).parents('.form-adv').attr('id').replace(/[^0-9]*/,'');
        var _url = jQuery('input[id="file_upload"]').attr('data-url')+"reqts/";
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url, data: {ajax: true, id_place: _id_place, id:_id}
        });
    });
    
    //если это 1 шаг создания объявления, то это не нужно
    if(jQuery('.undo.creation').length == 0 && _geopopup_handlers_included == false){
        _geopopup_handlers_included = true;
        //удаление тегов по нажатию на крестик
        jQuery(document).on("click",'.tg-item',function(){
            var _tg_id = jQuery(this).attr('data-id');
            //удаляем этот id из списка категории
            var _list_picker = jQuery(this).parent().siblings('.list-picker');
            var _ids_list = _list_picker.children('input').val().replace(_tg_id,'').replace(/^,|,$/,'').replace(/,,/,',');
            _list_picker.children('input').val(_ids_list);
            _list_picker.children('.selected-tags-info').children('i').html(parseInt(_list_picker.children('.selected-tags-info').children('i').html()) - 1)
            //корректируем контролы и скрытие
            var _tags_count = jQuery(this).siblings('.tg-item').length;
            if(_tags_count == 0) _list_picker.children('.selected-tags-info').addClass('unactive');
            if(_tags_count < 10){
                _list_picker.children('.selected-tags-show-all').addClass('unactive');
                jQuery(this).parent().removeClass('bottom-gradient-overflow');
            } 
            jQuery(this).remove();
        });
        
        //маски для минимальной и максимальной цен
        jQuery("#input-price-floor").mask('000 000 000', {reverse: true});
        jQuery("#input-price-top").mask('000 000 000', {reverse: true});
        
        //если это архивное объявление, добавляем класс unactive чтобы плашка была серой
        if(jQuery('.adv-status').children('.switcher').hasClass('checked')) jQuery('.adv-fields-row.top').children('p').addClass('unactive');
        
        //кнопки выделить все и снять выделение в нижнем блоке
        jQuery(".bottom-block .select-all-btn").click(function(){
            if(jQuery('.location-list').hasClass('hidden')) jQuery('.items-list .column').find('a:not(.selected)').click();
            else jQuery('.location-list .selected-items.on').children('.item:not(.on)').click();
        });
        jQuery(".bottom-block .diselect-all-btn").click(function(){
            if(jQuery('.location-list').hasClass('hidden')) jQuery('.items-list .column').find('a.selected').click();
            else jQuery('.location-list .selected-items.on').children('.item.on').click();
        });
    
        //содержимое вкладки с типами для большой всплывашки
        jQuery('.filter .object-types-picker').on('click',function(e){
            e.preventDefault();
            jQuery(this).addClass('on').siblings('span').removeClass('on');
            jQuery('#geodata-picker-wrap .items-list').addClass('wided').siblings('.location-list').addClass('hidden');
            
            var _url = jQuery('.object-types-picker').children('input').attr('data-url');
            var _values,_tag_ids;
            _tag_ids = [];
            var _selected_values;
            if(_first_open_object_types) _selected_values = jQuery('.list-picker.object_types').find('input').val();
            else _selected_values = jQuery('#popup-object-types').val();
            
            if(_selected_values.match(/\~/g) == null){
                _tag_ids = _selected_values.split(',');
            }else{
                _values = _selected_values.split('~');
                if(_values.length > 0)
                    for(var i=0;i<_values.length;i+=2)
                        _tag_ids.push(_values[i]);
            }
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: true,
                url: _url, data: {ajax: true, selected: _tag_ids},
                success: function(msg){ 
                    if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok && msg.list.length ) {
                        var _html = "";
                        //устанавливаем во вкладку значение 
                        //jQuery('#geodata-picker-wrap .filter .object-types-picker i').html(msg.list.length);
                        //если указано поле split_on, разбиваем на столбцы по значению указанного поля
                        if(msg.split_on) _html = splitList(msg.list,msg.title,msg.split_on);
                        else _html = formatList(msg.list,msg.title,4);
                        jQuery('#geodata-picker-wrap').children('.location-list').children('.selected-items').removeClass('on');
                        jQuery('#geodata-picker-wrap').children('.items-list').children('.items').html("").append(jQuery(_html));
                        var _type_objects_value = [];
                        jQuery(".items-list .abc-block a").click(function(){
                            jQuery(this).toggleClass('selected');
                            var _type_objects_value = [];
                            var _counter = 0;
                            jQuery('.items-list').find('a.selected').each(function(){
                                if(jQuery(this).attr('data-item-similar'))
                                    _type_objects_value.push(jQuery(this).attr('data-item-id'),(jQuery(this).attr('data-item-txt') + jQuery(this).attr('data-item-similar')));
                                else
                                    _type_objects_value.push(jQuery(this).attr('data-item-id'),jQuery(this).attr('data-item-txt'));
                                _counter++;
                            });
                            jQuery('#geodata-picker-wrap .filter .object-types-picker i').html(_counter);
                            jQuery('.items-list .bottom-block .selected-total i').html(_counter)
                            //названия типов объектов могут включать запятые, поэтому разделитель другой
                            jQuery('#popup-object-types').val(_type_objects_value.join('~'));
                        });
                        var _counter = 0;
                        //сразу же заполняем значение input для типов объектов, если это первая отрисовка
                        if((jQuery('#popup-object-types').val().match(/~/) != null || jQuery('#popup-object-types').val() == "")){
                            _first_open_object_types = false;
                            jQuery('.items-list').find('a.selected').each(function(){
                                _type_objects_value.push(jQuery(this).attr('data-item-id'));
                                _counter++;
                            });
                            jQuery('#popup-object-types').val(_type_objects_value.join(','));
                        }
                        //если переключили обратно на эту вкладку - наоборот, по input отщелкиваем <a>
                        else{
                            _first_open_object_types = false;
                            var _type_objects_value = jQuery('#popup-object-types').val().split(',');
                            _counter = _type_objects_value.length;
                            jQuery('.items-list').find('a').each(function(){
                                if(jQuery.inArray(jQuery(this).attr('data-item-id'),_type_objects_value) >= 0) jQuery(this).addClass('selected');
                                else jQuery(this).removeClass('selected');
                            });
                        }
                        jQuery('.items-list .bottom-block .selected-total i').html(_counter);
                        jQuery('#geodata-picker-wrap .filter .object-types-picker i').html(_counter);
                        //jQuery('.central-column').css('height',jQuery('.central-column').height()+jQuery('.items-list').height()+'px');
                    } else {
                        //alert('Ошибка данных!'); 
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    //alert('Ошибка обращения к серверу!'); 
                }
            });
        });
    
        //alert("!@#");
    
        //обработчик для кнопки "Применить" всплывашки общего выбора
        jQuery('.geodata-button.context-tags-apply').click(function(e){
            //метка что форму будем чиситить
            jQuery('#geodata-picker-wrap.target-outed').attr('data-reset',"true");
            //alert(jQuery('#geodata-picker-wrap.target-outed').length);
            //заполняем все input тегов местоположения
            
            jQuery('.location-list .selected-items').each(function(){
                
                //читаем название блока, к которому принадлежат теги
                var _tag_category = "";
                switch(true){
                    case jQuery(this).hasClass('districts-list'): _tag_category = "districts";break;
                    case jQuery(this).hasClass('district-areas-list'): _tag_category = "district_areas";break;
                    case jQuery(this).hasClass('subways-list'): _tag_category = "subways";break;
                    default: return false;
                }
                
                var _selected_items = jQuery(this).children('.item.on');
                var _result = new Array();
                var _size = 0;
                if(_selected_items != undefined) _size = _selected_items.length;

                //alert(_tag_category + ', si =  ' + _size);
                
                if(_size == 0){
                    jQuery(".tg." + _tag_category).find('input').val("");
                    jQuery(".tg." + _tag_category).find('.tg-list').removeClass('bottom-gradient-overflow').siblings('.list-picker').children('span').addClass('unactive');
                    
                    jQuery(".tg." + _tag_category).find('input').trigger('change',"");
                    return;
                }
                
                _selected_items.each(function(){
                    if(jQuery(this).attr('data-tag-id') != undefined && jQuery(this).attr('data-tag-id').length > 0) _result.push(jQuery(this).attr('data-tag-id'),jQuery(this).html());
                });
                
                jQuery(".tg." + _tag_category).find('input').val(_result.join(','));
                jQuery(".tg." + _tag_category).find('input').trigger('change', _result.join(','));
                
                if(_result.join(',').length > 0) jQuery(".tg." + _tag_category).removeClass('error').find('.error-box').remove();
            });
            
            //отдельно читаем и пишем данные по типам объектов - тут отщелкивать не надо, этот кусок цепляется отдельно
            //если значение менялось, то надо записывать
            if(jQuery('#popup-object-types').val().match(/~/) != null){
                jQuery('.tg.object_types').find('input').val(jQuery('#popup-object-types').val());
                if(jQuery('#popup-object-types').val().length > 0) jQuery(".tg.object_types").removeClass('error').find('.error-box').remove();
                jQuery(".tg.object_types").find('input').change();
            }else if(jQuery('#popup-object-types').val().length == 0){
                jQuery('.tg.object_types').find('input').val("");
                jQuery(".tg.object_types").find('input').trigger('change',"");
            }
            
            jQuery('#geodata-picker-wrap').children('.items-list').children('.items').html("")
            
            //чистим форму, т.к. она общая
            jQuery('#geodata-picker-wrap.target-outed .filter').find('input').val("");
            jQuery('.location-list').find('.item.on').click();
            
            jQuery('#geodata-picker-wrap').fadeOut(100);
            setTimeout(function(){
                jQuery('#background-shadow-expanded').fadeOut(100);
                jQuery(document).scrollTop(jQuery('.targeting-block .blue-h').offset().top - jQuery('.targeting-block .blue-h').height() - 10);
            }, 200);
            return false;
        });
    }
    
    jQuery('#p_field_block_type .lf.fieldwrapper span.on').click();
    
    //когда все загрузилось, показываем кнопку "Сохранить"
    jQuery('.save-adv-form').show();
    clearTimeout();
    
    
    
    //скроллим до верха формы (не сразу, потому что форма еще грузится)
    setTimeout("jQuery(document).scrollTop(jQuery('.adv-campaign-edit-block').offset().top+jQuery('.adv-campaign-edit-block').height()-10);clearTimeout();",300);
    
    
});