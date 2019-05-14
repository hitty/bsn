/**
* Address selector jQuery plugin
* 
* Входные данные:
* по умолчанию читаются из аттрибутов data-id, data-district_id, data-subway_id тэга, к которому применен плагин
* если в опциях переданы селекторы соответствующих тегов, то значения берутся из них
* 
* Результат:
* по умолчанию записывается в аттрибуты data-id, data-district_id, data-subway_id тэга, к которому применен плагин
* если в опциях были переданы селекторы соответствующих тегов, то значения записываются и в них
* 
*/
if(jQuery)(function(window, document, $, undefined){
    $.fn.addrselector = function(opts) {
        var defaults = {
            geo_id_element      : null,                     /* элемент - источник ID геопозиции */
            district_id_element : null,                     /* элемент - источник ID района */
            subway_id_element   : null,                     /* элемент - источник ID метро */
            link_element        : 'body',                   /* элемент, в который встраивается вся конструкция addrselector */
            ajax_url            : '',                       /* URL для AJAX-запросов */
            multiselect         : false,                    /* Выбор нескольких районов вместо одного и нескольких метро вместо одного */
            startmode           : 'address',                /* Режим инициализации плагина ( пусто или 'address' || 'subway' || 'district') */
            view                : 'geolocation',            /* Выбор метро/района/нас.пункта */
            onInit              : function(item_id, district_id, subway_id){},      /* функция, дополняющая инициализацию function(selected_id){} */
            onExit              : function(selected_item_data){}                    /* функция, предваряющая закрытие function(item_id){} */
        };
        var options = $.extend(defaults, opts || {});
        var active_element = null;                          /* Элемент, к которому назначен вызов addrselector */
        var current_item_data = null;                       /* Информация о текущем элементе */
        
        /* функция стартовой инициализации */
        var start = function(item_id, district_id, subway_id){
            var template = $('<div id="addrselector">'
                          +'<div id="addrselector-wrapper"></div>'
                          +'<div id="addrselector-box">'
                          +'<div id="addrselector-topline"></div>'
                          +'<div id="addrselector-dropdown" class="dropdownlist"></div>'
                          +'<a class="closebutton">Close</a>'
                          +'<a class="commitbutton">Выбрать</a>'
                          +'</div>'
                          +'</div>');
            $(options.link_element).append(template);
            /* обработка закрытия */
            $('#addrselector-box .closebutton').click(function(){
                $('#addrselector').hide(200, function(){$(this).remove();});
                return false;
            });
            /* обработка подтверждения выбора и закрытия */
            $('#addrselector-box .commitbutton').click(function(){
                returnValues();
                /* вызов функции onExit */
                if(typeof(options.onExit) == 'function')
                    options.onExit(current_item_data);
                $('#addrselector').remove();
                return false;
            });
            /* корректировка позиции блока и удержание на месте при скролле экрана */
            $('#addrselector-box').css('top', (30 + $(document).scrollTop()) + "px");
            /* вызов функции onInit */
            if(typeof(options.onInit) == 'function')
                options.onInit(item_id, district_id, subway_id);
            /* загрузка текущих элементов геолокации */
            loadAddress(item_id, district_id, subway_id);
        };
        var loadAddress = function(item_id, district_id, subway_id){
            $('#addrselector-topline').addClass('wait');
            $.ajax({
                type: "POST", async: true, dataType: 'json', cache: false, url: options.ajax_url,
                data: {
                    ajax: true, 
                    action: 'geoitems', 
                    item_id: item_id, 
                    district_id: district_id,
                    subway_id: subway_id,
                    multiselect: options.multiselect
                },
                success: function(msg){
                    if(typeof(msg)=='object') {
                        if(msg.ok){
                            showAddress(msg.items);
                            showDistrict(msg.district);
                            showSubway(msg.subway);
                            setTimeout(function() {
                                if(options.startmode=='district' && typeof(msg.district)=='object' && msg.district.items.length) showDistrictList();
                                if(options.startmode=='subway' && typeof(msg.subway)=='object' && msg.subway.items.length) showSubwayList();
                            }, 300);
                        } else alert('Ошибка: '+msg.error);
                    } else alert('Ошибка!');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    alert('Ошибка связи с сервером!');
                },
                complete: function(){
                    $('#addrselector-topline').removeClass('wait');
                }
            });
        };
        /* обработка клика на показ списка */
        var showDistrictList = function(){
            $('#addrselector-topline .subway > a').removeClass('choise_link_active');
            if($('#district-dropdown').is(':hidden')){
                $('.district > a').addClass('choise_link_active');
                $('#addrselector-dropdown, #subway-dropdown').slideUp(150);
                $('#district-dropdown').slideDown(150);
            } else {
                $('.district > a').removeClass('choise_link_active');
                setTimeout(function() {
                    $('#district-dropdown').slideUp(150);
                    $('#addrselector-dropdown').slideDown(150);
                }, 200);
            }
            return false;
        };
        var showDistrict = function(district){
            $('#district-dropdown').remove();
            if(typeof(district)=='object'){
                var distr_str = '<div class="district"><a class="choise_link">Район</a>';
                if(typeof(district.selected) == 'object'){
                    current_item_data.district_id = new Array();
                    current_item_data.district_title = new Array();
                    for(var i=0;i<district.selected.length;i++){
                        current_item_data.district_id.push(district.selected[i].id);
                        current_item_data.district_title.push(district.selected[i].title);
                        distr_str += '<span class="selected">'+district.selected[i].title+'</span>';
                    }
                } else {
                    current_item_data.district_id = new Array();
                    current_item_data.district_title = new Array();
                }
                $('#addrselector-topline').append($(distr_str+'</div>'));
                if(typeof(district.items)=='object' && district.items.length>0){
                    var dropdownlist = '<div id="district-dropdown" class="dropdownlist"><div class="title">Районы:</div>'+formatList(district.items)+'</div>';
                    $('#addrselector-box').remove('#district-dropdown').append($(dropdownlist));
                }                
                $('#addrselector-topline .district a').unbind('click').click(showDistrictList);
                /* обработка клика на выбор района */
                $('#district-dropdown a').click(function(){
                    var elem = $(this);
                    if(!options.multiselect){
                        var _state = elem.hasClass('selected');
                        $('#district-dropdown a').removeClass('selected');
                        if(!_state) elem.addClass('selected');
                    } else elem.toggleClass('selected');
                    current_item_data.district_id = new Array();
                    current_item_data.district_title = new Array();
                    $('#district-dropdown a.selected').each(function(){
                        current_item_data.district_id.push($(this).attr('data-item_id'));
                        current_item_data.district_title.push($(this).html());
                    });
                    var d_str = '<a class="choise_link">Район</a>';
                    for(var i=0;i<current_item_data.district_title.length;i++){
                        d_str += '<span class="selected">'+current_item_data.district_title[i]+'</span>';
                    }
                    $('#addrselector-topline .district').html(d_str);
                    if(!options.multiselect){
                        setTimeout(function() {
                                $('#district-dropdown').slideUp(150);
                                $('#addrselector-dropdown').slideDown(150);
                        }, 200);

                    }
                    $('#addrselector-topline .district a').unbind('click').click(showDistrictList);
                    return false;
                });
            } else {
                current_item_data.district_id = new Array();
                current_item_data.district_title = new Array();
            }
        };
        /* обработка клика на показ списка */
        var showSubwayList = function(){
            $('#addrselector-topline .district > a').removeClass('choise_link_active');
            if($('#subway-dropdown').is(':hidden')){
                $('.subway > a').addClass('choise_link_active');
                $('#addrselector-dropdown, #district-dropdown').slideUp(150);
                $('#subway-dropdown').slideDown(150);
            } else {
                $('.subway > a').removeClass('choise_link_active');
                setTimeout(function() {
                    $('#subway-dropdown').slideUp(150);
                    $('#addrselector-dropdown').slideDown(150);
                }, 200);
            }
            return false;
        };
        var showSubway = function(subway){
            $('#subway-dropdown').remove();
            if(typeof(subway)=='object'){
                var subway_str = '<div class="subway"><a class="choise_link">Метро</a>';
                if(typeof(subway.selected) == 'object'){
                    current_item_data.subway_id = new Array();
                    current_item_data.subway_title = new Array();
                    for(var i=0;i<subway.selected.length;i++){
                        current_item_data.subway_id.push(subway.selected[i].id);
                        current_item_data.subway_title.push(subway.selected[i].title);
                        subway_str += '<span class="selected">'+subway.selected[i].title+'</span>';
                    }
                } else {
                    current_item_data.subway_id = new Array();
                    current_item_data.subway_title = new Array();
                }
                $('#addrselector-topline').append($(subway_str+'</div>'));
                if(typeof(subway.items)=='object' && subway.items.length>0){
                    var dropdownlist = '<div id="subway-dropdown" class="dropdownlist"><div class="title">Станции метро:</div>'+formatList(subway.items)+'</div>';
                    $('#addrselector-box').remove('#subway-dropdown').append($(dropdownlist));
                }                
                $('#addrselector-topline .subway a').unbind('click').click(showSubwayList);
                /* обработка клика на выбор метро */
                $('#subway-dropdown a').click(function(){
                    var elem = $(this);
                    if(!options.multiselect){
                        var _state = elem.hasClass('selected');
                        $('#subway-dropdown a').removeClass('selected');
                        if(!_state) elem.addClass('selected');
                    } else elem.toggleClass('selected');
                    current_item_data.subway_id = new Array();
                    current_item_data.subway_title = new Array();
                    $('#subway-dropdown a.selected').each(function(){
                        current_item_data.subway_id.push($(this).attr('data-item_id'));
                        current_item_data.subway_title.push($(this).html());
                    });
                    var d_str = '<a class="choise_link">Метро</a>';
                    for(var i=0;i<current_item_data.subway_title.length;i++){
                        d_str += '<span class="selected">'+current_item_data.subway_title[i]+'</span>';
                    }
                    $('#addrselector-topline .subway').html(d_str);
                    if(!options.multiselect){
                        setTimeout(function() {
                            $('#subway-dropdown').slideUp(150);
                            $('#addrselector-dropdown').slideDown(150);
                        }, 200);
                    }
                    $('#addrselector-topline .subway a').unbind('click').click(showSubwayList);
                    return false;
                });
            } else {
                current_item_data.subway_id = new Array();
                current_item_data.subway_title = new Array();
            }
        };
        /* отображение списка (строки) элементов текущей локации */
        var showAddress = function(geo_items){
            $('#addrselector-topline').empty();
            var address_line = '<div class="address">';
            var cnt = geo_items.length;
            var title = '';
            for(var i=0;i<geo_items.length;i++){
                title = (title==''?'':title+' / ')+geo_items[i].title;
                cnt--;
                if(cnt<1) {
                    address_line += '<span data-item_id="'+geo_items[i].id+'">'+geo_items[i].title+'</span>';
                    current_item_data = geo_items[i];
                    current_item_data.title = title.substr(title.indexOf('/')+2);
                    loadList(geo_items[i].id);
                } else
                    address_line += '<a data-item_id="'+geo_items[i].id+'">'+geo_items[i].title+'</a>';
            }
            $('#addrselector-topline').append($(address_line));
            /* обработка клика для адреса*/
            $('#addrselector-topline .address a').click(function(){
                loadAddress($(this).attr('data-item_id'), current_item_data.district_id, current_item_data.subway_id);
                return false;
            });
        };
        /* загрузка списка дочерних локаций для указанного элемента */
        var loadList = function(parent_id){
            $.ajax({
                type: "POST", async: true, dataType: 'json', cache: false, url: options.ajax_url,
                data: {ajax: true, action: 'geolist', item_id: parent_id},
                success: function(msg){
                    if(typeof(msg)=='object') {
                        if(msg.ok){
                            showList(msg.items);
                        } else alert('Ошибка2: '+msg.error);
                    } else alert('Ошибка!');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    alert('Ошибка связи с сервером!');
                }
            });
        };
        var showList = function(geolist){
            $('#addrselector-dropdown').slideUp(150, function(){
                var listitems = $(this);
                listitems.empty().append(formatList(geolist)).slideDown(150);
                /* обработка нажатия на элемент списка */
                $('#addrselector-dropdown a').click(function(){
                    loadAddress($(this).attr('data-item_id'));
                    return false;
                });
            });
        };
        /* формирование 4-колоночного списка с алфавитом */
        var formatList = function(list){
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
                    cur_block = '<div class="abc_block"><span class="letter">'+abc+'</span>';
                }
                cur_block += '<a data-item_id="'+list[i].id+'"'+(list[i].selected?' class="selected"':'')+'>'+list[i].title+'</a>';
            }
            if(list.length>0) {
                cur_block += '</div>';
                _blocks.push(cur_block);
            }
            var col1block = '<div class="column1">';
            var col2block = '<div class="column2">';
            var col3block = '<div class="column3">';
            var col4block = '<div class="column4">';
            var cnt = 1;
            var onepart = Math.ceil(_blocks.length/4);
            var twopart = onepart*2;
            var threepart = onepart*3;
            for(i=0;i<_blocks.length;i++){
                if(cnt<=onepart) col1block += _blocks[i];
                else {
                    if(cnt<=twopart) col2block += _blocks[i];
                    else {
                        if(cnt<=threepart) col3block += _blocks[i];
                        else col4block += _blocks[i];
                    }
                }
                cnt++;
            }
            return col1block+'</div>'+col2block+'</div>'+col3block+'</div>'+col4block+'</div>';
        };
        // возврат выбранных значений
        var returnValues = function(){
            var elem;
            active_element.attr('data-id', current_item_data.id);
            if(options.geo_id_element) {
                elem = $(options.geo_id_element).first();
                if(elem.is('input')) elem.val(current_item_data.id);
                else elem.html(current_item_data.id);
            }
            var _value;
            if(options.multiselect) 
                _value = current_item_data.district_id.join(',');
            else
                _value = current_item_data.district_id;
            active_element.attr('data-district_id', _value);
            if(options.district_id_element) {
                elem = $(options.district_id_element).first();
                if(elem.is('input')) elem.val(_value);
                else elem.html(_value);
            }
            if(options.multiselect) 
                _value = current_item_data.subway_id.join(',');
            else
                _value = current_item_data.subway_id;
            active_element.attr('data-subway_id', _value);
            if(options.subway_id_element) {
                elem = $(options.subway_id_element).first();
                if(elem.is('input')) elem.val(_value);
                else elem.html(_value);
            }
        };

        return this.each(function(){
            $(this).click(function(){
                active_element = $(this);
                var id, elem;
                if(!active_element.attr('data-id')){
                    id = 0;
                    elem = $(options.geo_id_element).first();
                    if(elem.is('input')) id = elem.val();
                    else id = elem.html();
                    active_element.attr('data-id', id);
                }
                if(!active_element.attr('data-district_id')){
                    id = 0;
                    elem = $(options.district_id_element).first();
                    if(elem.is('input')) id = elem.val();
                    else id = elem.html();
                    active_element.attr('data-district_id', id);
                }
                if(!active_element.attr('data-subway_id')){
                    id = 0;
                    elem = $(options.subway_id_element).first();
                    if(elem.is('input')) id = elem.val();
                    else id = elem.html();
                    active_element.attr('data-subway_id', id);
                }
                options.startmode = (active_element.attr('data-mode') == 'subway_title' ? 'subway' : (active_element.attr('data-mode') == 'district_title' ? 'district' : '')),
                start(active_element.attr('data-id'),active_element.attr('data-district_id'),active_element.attr('data-subway_id'));
                return false;
            })
        });
    }
})(window, document, jQuery);