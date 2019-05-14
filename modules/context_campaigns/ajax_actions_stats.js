jQuery(document).ready(function(){
    //читаем данные для графиков(сразу все), и потом рисуем их
    function getGraphicData(_first_call){
        
        //var _campaign_id = parseInt(jQuery('#filter_campaign').val().replace(/[^0-9]*/,''));
        var _campaign_id = jQuery('#main_filter_campaign').children('li.active').attr('data-id');
        var _url = '/members/context_campaigns/'+_campaign_id+'/stats/gr-all/';
        //собираем значения фильтров
        var _date_start = jQuery('.range-selector').children('#filter_date_start').val();
        var _date_end = jQuery('.range-selector').children('#filter_date_end').val();
        var _group_by = jQuery('.groupby').children('#filter_groupby').val();
        
        //собираем (при необходимости) значения с выделенных строк
        
        //вкладка текущей кампании
        
        var _selected = {};
        _selected.ids_list="";
        _selected.colors_list="";
        //_selected_list = "";
        var _selected_ids = "";
        //собираем id отмеченных
        jQuery('#advs_table').children('.adv-list').children('.dataTables_wrapper').children('.tablesorter').children('tbody').children('tr').children('td.display-status.active').each(
        function(){
            if(jQuery(this).parent().hasClass('disabled')) return false;
            if(_selected.ids_list.length<1){
                _selected.ids_list = jQuery(this).parents('tr').attr('id');
                _selected.colors_list = jQuery(this).parents('tr').children('.title').attr('data-color');
            }
            else{
                _selected.ids_list += ',' + jQuery(this).parents('tr').attr('id');
                _selected.colors_list += ',' + jQuery(this).parents('tr').children('.title').attr('data-color');
            }
            //если это первый вызов, заносим в фильтр id выделенных (первых пяти)
            if(_first_call) _selected_ids += ',' + jQuery(this).parents('tr').attr('id');
        });
        //если это первый вызов, заполняем data-value вкладки кампании списком id выделенных
        if(_first_call){
            //вкладка текущей кампании
            var _this_campaign_li = jQuery('#main_filter_campaign').children('li.selected');
            //читаем список из вкладки и стираем его
            var _selected_list = _this_campaign_li.attr('data-value').split('?')[1].split('&')[2];
            if(_selected_list!==undefined) _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value').replace('&' + _selected_list,''));
            //пишем новый список выделенных
            _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value') + "&advs_selected=" + _selected_ids.substring(1, _selected_ids.length));
        }
        
        //если в таблице что-то есть, рисуем графики
        if(jQuery('#advs_table').children('.adv-list').children('.dataTables_wrapper').length>0){
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _url,
                data: {ajax: true,date_start:_date_start,date_end:_date_end,group_by:_group_by,ids:_selected.ids_list,colors:_selected.colors_list},
                success: function(_data){
                    //если все хорошо, то по полученным данным рисуем сразу все графики
                    if(_data.ok){
                        var _gr_list = _data['global_result'];
                        var _group_by = _data['group_by'];
                        if(_gr_list.length>0)
                            for(var i=0;i<_gr_list.length;i++){
                                drawGraphic(_gr_list[i],_group_by);
                            }
                        if(jQuery('.chart.active').length>0) jQuery('.chart.active').click();
                        else jQuery('.chart.line').click();
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    return false;
                },
                complete: function(){
                }
            });
        }
        else{
            //если данных нет, стираем подписи к осям
            jQuery('.x-title').html("");
            jQuery('.y-title').html("");
        }
    }
    
    //рисуем график по считанным данным
    function drawGraphic(list,_group_by){
        if(list){
            //если загрузилось, рисуем график
            var data = new google.visualization.DataTable();
            //читаем набор полей
            var fields=list['fields'];
            for (k=0;k<fields.length;k++){
                data.addColumn(fields[k][0], fields[k][1]);
            }
            
            //var _id_campaign = parseInt(jQuery('#filter_campaign').val().replace(/[^0-9]*/,''));
            var _id_campaign = jQuery('#main_filter_campaign').children('li.active').attr('data-id');
            
            var _x_title = "";
            switch(_group_by){
                case 'day': _x_title = "Дни";break;
                case 'week': _x_title = "Недели";break;
                case 'month': _x_title = "Месяцы";break;
            }
            //определяем тип графика и задаем опции
            if (list['type']=='Line'){
                var options = {'width':list['width']||'100%',
                                fontSize: 11,
                                'title':"title",
                               'height':list['height']||'100%',
                               'hAxis' : {
                                            showTextEvery:(list['show_every']||1),
                                            title:_x_title
                                         },
                                legend:{position: 'right'},
                                legend:'none',
                                colors:list['colors']||null,
                                'chartArea':{left:50,top:10,width:"80%"},
                                'showTextEvery':1,
                               'animation':{duration: 500,easing: 'out'}};
                //собираем название элемента, куда пихнем график
                var _elem_id = "";
                if(list['selection']){
                    _elem_id += _id_campaign + "-graphic-" + list['selection'] + "-" + list['type'].substring(0,1).toLowerCase();
                }
                var chart = new google.visualization.LineChart(document.getElementById(_elem_id));
            } else if (list['type']=='Pie'){
                if(list['is3D']==undefined) list['is3D'] = true;
                var options = {'width':list['width']||'400',
                               'height':list['height']||'200',
                                top:0,
                                fontSize: 11,
                                left:0,
                                pieSliceText: 'none',
                                'legend':{position: list['legend_position']||'right'},
                                is3D: list['is3D'],
                                colors:list['colors']||null,
                                'chartArea':{left:50,top:10,width:"100%"},
                                'animation':{duration: 500,easing: 'out'}};
                //собираем название элемента, куда пихнем график
                var _elem_id = "";
                if(list['selection']){
                    _elem_id += _id_campaign + "-graphic-" + list['selection'] + "-" + list['type'].substring(0,1).toLowerCase();
                }
                var chart = new google.visualization.PieChart(document.getElementById(_elem_id));
            } else if (list['type']=='Column'){
                var options = {'width':list['width']||'100%',
                                fontSize: 11,
                               'height':list['height']||'100%',
                               'legend':{position: list['legend_position']||'bottom'},
                               'hAxis' : {showTextEvery:list['show_every']||1,title:_x_title},
                                title : list['v_axe_title']||'',
                                colors : list['colors']||null,
                                'chartArea':{left:50,top:10,width:"90%"},
                                'vAxis':{title : list['v_axe_title']||''},
                                axisTitlesPosition:'out',
                                'showTextEvery':1,
                               'animation':{duration: 500,easing: 'out'}};
                //собираем название элемента, куда пихнем график
                var _elem_id = "";
                if(list['selection']){
                    _elem_id += _id_campaign + "-graphic-" + list['selection'] + "-" + list['type'].substring(0,1).toLowerCase();
                }
                var chart = new google.visualization.ColumnChart(document.getElementById(_elem_id));
            }
            
            try{
                data.addRows(list['data']);
                //dateFormatter.format(data,0);
                chart.draw(data, options);
            }
            catch(e){
                //alert(JSON.stringify(e));
            }
        }
        return true;
    }
    
    //преобразуем дату в формат dd.mm.yy
    function DateFormat(today){
        var dd = today.getDate();
        var mm = today.getMonth()+1;

        var yy = today.getFullYear().toString().substr(2,2);
        if(dd<10){
            dd='0'+dd
        } 
        if(mm<10){
            mm='0'+mm
        }
        var date_str = dd+'.'+mm+'.'+yy;
        return date_str;
    }
    
    //если через # передан id кампании с которой пришли
    if(document.URL.indexOf('#') != -1){
        _campaign_id = document.URL.substring(document.URL.indexOf("#")+1);
        jQuery('#filter_campaign').val(_campaign_id);
        var _campaign_title = jQuery('.change-campaign').children('.list-data').children('li[data-id="' + _campaign_id + '"]').html();
        if(_campaign_title === undefined) return false;
        if(_campaign_title.length>35) _campaign_title = _campaign_title.substring(0,35) + '...';
        jQuery('.change-campaign').children('.pick').html(_campaign_title);
        jQuery('.change-campaign').children('.list-data').children('li').removeClass('selected');
        jQuery('.change-campaign').children('.list-data').children('li[data-id="' + _campaign_id + '"]').addClass('selected').addClass('active');
        //устанавливаем значение id кампании в members-stats-wrap
        jQuery('.members-stats-wrap').attr('date-campaign_id',_campaign_id);
    }
    
    
    function set_active(_advs_selected){
        if(_advs_selected!==undefined){
            for(var i=0;i<_advs_selected.length;i++){
                jQuery('tr[id="'+_advs_selected[i]+'"]').children('.display-status').addClass('active').children('i').addClass('active');
            }
        }
    }
    
    //сначала скрываем все графики
    jQuery('.chart-area').children('span').addClass('active');
    jQuery('.chart-area').children('span').removeClass('active');
    
    
    //изменение типа графика
    jQuery('.chart').on('click',function(){
        //выборка
        var _selection = jQuery('#filter_chart').val();
        //id кампании
        //var _id_campaign = jQuery('#filter_campaign').val();
        var _id_campaign = jQuery('#main_filter_campaign').children('li.active').attr('data-id');
        //тип графика
        var _type = jQuery(this).attr('class').replace(/chart\s?/,'').replace(/\s?active/,'').charAt(0);
        jQuery('.controls-box').children('span').removeClass('active');
        jQuery(this).addClass('active');
        jQuery('.chart-area').children('span').removeClass('active');
        jQuery('.chart-area').children('#'+_id_campaign+'-graphic-'+_selection+'-'+_type).addClass('active');
        var _group_by = jQuery('#filter_groupby').val();
        //если пироговый график, или нет данных, убираем подписи к осям
        if(_type == 'p'){
            jQuery('.x-title').html("");
            jQuery('.y-title').html("");
        }
        else{
            //устанавливаем значение подписи к X:
            switch(_group_by){
                case "day": jQuery('.x-title').html("Дни");break;
                case "week": jQuery('.x-title').html("Недели");break;
                case "month": jQuery('.x-title').html("Месяцы");break;
            }
            //устанавливаем значение подписи к Y
            switch(_selection){
                case "show":jQuery('.y-title').html("Показы");break;
                case "click":jQuery('.y-title').html("Клики");break;
                case "ctr":jQuery('.y-title').html("CTR %");break;
                case "fin":jQuery('.y-title').html("Расходы");break;
            }
        }
        
    });
    
    //при изменении типа графика, еще раз щелкаем кнопку с графиком, чтобы перерисовалось
    jQuery('.list-selector.change-chart').children('.list-data').children('li').click(function(e){
        jQuery('#filter_chart').val(jQuery(e.target).attr('data-value'));
        jQuery('.controls-box').children('.chart.active').click();
    });
    
    //при изменениии кампании, переключаем chart-area и adv_table
    jQuery('.list-selector.change-campaign').children('.list-data').children('li').click(function(e){
        var _campaign_id = jQuery(e.target).attr('data-id');
        jQuery('.chart-area').children().removeClass('active');
        jQuery('.chart-area').children('#' + _campaign_id + '-graphic-show-l').addClass('active');
        jQuery(this).parent().children('li').removeClass('active');
        jQuery(this).addClass('active');
        //если название кампании длинное, подрезаем его до 35 символов
        var _new_campaign_title = jQuery(this).html();
        if(_new_campaign_title.length>38) _new_campaign_title = _new_campaign_title.substring(0,35) + '...';
        jQuery('.list-selector.change-campaign.active').children('.pick').html(_new_campaign_title);
        window.location = document.URL.substring(0,document.URL.indexOf("#")) + "#" + _campaign_id;
    });
    
    //вместо hover в css, потому что оттуда не забрать нужный цвет
    
    jQuery(document).on('mouseenter','.adv-table-row',function(e){
        if(!jQuery(this).children('.display-status').hasClass('active'))
            jQuery(this).children('.display-status').children('i').addClass('hover').css('background-color',jQuery(this).attr('data-color'));
        else
            jQuery(this).children('.display-status').children('i').addClass('hover').css('background-color',"#FFFFFF");
    });
    jQuery(document).on('mouseleave','.adv-table-row',function(e){
        if(!jQuery(this).children('.display-status').hasClass('active'))
            jQuery(this).children('.display-status').children('i').removeClass('hover').css('background-color',"#FFFFFF");
        else
            jQuery(this).children('.display-status').children('i').removeClass('hover').css('background-color',jQuery(this).attr('data-color'));
    });
    
    
    //когда щелкаем по "выберите период" селектор должен сбрасываться
    jQuery('.select-time-period').children('.list-data').children('li').first().on('click',function(){
        jQuery('.range-selector').children().val("");
    });
    
    //при клике по строчке, кликаем по кнопке справа
    jQuery(document).on('click','.adv-table-row',function(e){
        var _i;
        if(jQuery(e.target).parent().hasClass('display-status')) _i = jQuery(e.target)
        else _i = jQuery(this).children('.display-status').children('i');
        
        var _this_campaign_li = jQuery('#main_filter_campaign').children('li.selected');
        
        //если элемент активен, устанавливаем соответствующий цвет
        if(!jQuery(_i).hasClass('active')){
            jQuery(_i).parent().addClass('active').children('i').addClass('active');
            jQuery(_i).css('background-color',jQuery(this).attr('data-color'));
            jQuery(_i).removeClass('hover');
            //добавляем id в data-value вкладки кампании
            _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value') + ',' + jQuery(this).attr('id'));
        }
        else{
            jQuery(_i).removeClass('active').children('i').removeClass('active');
            jQuery(_i).parent().removeClass('active');
            jQuery(_i).css('background-color',"#FFFFFF");
            jQuery(_i).removeClass('hover');
            //убираем id из data-value вкладки кампании
            _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value').replace(',' + jQuery(this).attr('id'),''));
            
        }
        getGraphicData();
    });
    
    //клик по "выделить все"
    jQuery(document).on('click','th.statuses',function(){
        _this_tbody = jQuery(this).parent().parent().parent().children('tbody');
        var _this_campaign_li = jQuery('#main_filter_campaign').children('li.selected');
        var _selected_list = _this_campaign_li.attr('data-value').split('?')[1].split('&')[2];
        //читаем выделенных из вкладки кампании и стираем его
        if(_selected_list!==undefined) _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value').replace('&' + _selected_list,''));
        _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value') + '&advs_selected=');
        //alert(_this_campaign_li.attr('data-value'));
        //если кнопка была ненажата, делаем все строчки активными
        if(!jQuery(this).hasClass('active')){
            jQuery(this).addClass('active');
            
            _this_tbody.children('tr').children('td.display-status').each(function(){
                if(!jQuery(this).hasClass('active')){
                    jQuery(this).addClass('active');
                    jQuery(this).children('i').addClass('active');
                    jQuery(this).children('i').css('background-color',jQuery(this).parent('tr').attr('data-color'));
                }
                //добавляем во вкладку кампании новый id (если это не последняя строка - по ней будем кликать, она запишется)
                if(jQuery(this).parent('tr').attr('id') != jQuery(this).parent().parent('tbody').children('tr').last().attr('id'))
                    jQuery('#main_filter_campaign').children('li.selected').attr('data-value',jQuery('#main_filter_campaign').children('li.selected').attr('data-value') + ',' + jQuery(this).parent('tr').attr('id'));
            });
            //у последнего убираем active и сразу же кликаем по нему, чтобы графики перерисовались
            _this_tbody.children('tr').children('td.display-status').last().removeClass('active').children('i').removeClass('active').parent().click();
        }
        else{
            //убираем выделение с "глаза"
            jQuery(this).removeClass('active');
           //если кнопка была нажата, снимаем со всех выделение, потом щелкаем на первую строку
           _this_tbody.children('tr').children('td.display-status').removeClass('active').children().removeClass('active').css('background-color','#FFFFFF');
            //щелкаем на первую строчку
           _this_tbody.children('tr').first().children('td.display-status').click();
        }
    });
    
    //инициализируем ajax-фильтр
    jQuery('.advs_table').ajaxfilter({
        url_element             : '#main_filter_campaign li',
        limit_on_page_element   : '#limit-selector',
        page_element            : '.paginator',
        query_form_element      : '#ajax-filter',
        onExit                  : function(data){}
    });
    
    //клик по "выделить все"
    jQuery(document).on('click','th.statuses',function(){
        _this_tbody = jQuery(this).parent().parent().parent().children('tbody');
        var _this_campaign_li = jQuery('#main_filter_campaign').children('li.selected');
        var _selected_list = _this_campaign_li.attr('data-value').split('?')[1].split('&')[2];
        //читаем выделенных из вкладки кампании и стираем его
        if(_selected_list!==undefined) _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value').replace('&' + _selected_list,''));
        _this_campaign_li.attr('data-value',_this_campaign_li.attr('data-value') + '&advs_selected=');
        //alert(_this_campaign_li.attr('data-value'));
        //если кнопка была ненажата, делаем все строчки активными
        if(!jQuery(this).hasClass('active')){
            jQuery(this).addClass('active');
            //alert('select all');
            _this_tbody.children('tr').children('td.display-status').each(function(){
                if(!jQuery(this).hasClass('active')){
                    jQuery(this).addClass('active');
                    jQuery(this).children('i').addClass('active');
                    jQuery(this).children('i').css('background-color',jQuery(this).parent('tr').attr('data-color'));
                }
                //добавляем во вкладку кампании новый id (если это не последняя строка - по ней будем кликать, она запишется)
                if(jQuery(this).parent('tr').attr('id') != _this_tbody.children('tr').last().attr('id'))
                    jQuery('#main_filter_campaign').children('li.selected').attr('data-value',jQuery('#main_filter_campaign').children('li.selected').attr('data-value') + ',' + jQuery(this).parent('tr').attr('id'));
            });
            //у последнего убираем active и сразу же кликаем по нему, чтобы графики перерисовались
            _this_tbody.children('tr').children('td.display-status').last().removeClass('active').children('i').removeClass('active');
            _this_tbody.children('tr').last().click();
        }
        else{
            //alert('diselect all');
            //убираем выделение с "глаза"
            jQuery(this).removeClass('active');
           //если кнопка была нажата, снимаем со всех выделение, потом щелкаем на первую строку
           _this_tbody.children('tr').children('td.display-status').removeClass('active').children().removeClass('active').css('background-color','#FFFFFF');
            //щелкаем на первую строчку
           _this_tbody.children('tr').first().click();
        }
    });
    
    //кликаем по вкладке списка чтобы все нарисовалось
    setTimeout("jQuery('#main_filter_campaign').children('li.active').click();",200);
    
});