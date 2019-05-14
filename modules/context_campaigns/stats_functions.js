jQuery(document).ready(function(){
    //функции для графиков:
    //читаем данные для графиков(сразу все), и потом рисуем их
    function getGraphicData(){
        var _url = '/members/context_campaigns/{$campaign_id}/stats/gr-all/';
        //собираем значения фильтров
        var _date_start = jQuery('.select-dates').children('#filter_date_start').val();
        var _date_end = jQuery('.select-dates').children('#filter_date_end').val();
        var _group_by = jQuery('.group-by').children('#filter_groupby').val();
        //собираем (при необходимости) значения с выделенных строк
        var _selected = {};
        _selected.ids_list="";
        _selected.colors_list="";
        jQuery('.display-status.active').each(function(){
            if(_selected.ids_list.length<1){
                _selected.ids_list = jQuery(this).parents('tr').attr('id');
                _selected.colors_list = jQuery(this).parents('tr').children('.title').attr('data-color');
            }
            else{
                _selected.ids_list += ',' + jQuery(this).parents('tr').attr('id');
                _selected.colors_list += ',' + jQuery(this).parents('tr').children('.title').attr('data-color');
            }
        });
        
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url,
            data: {ajax: true,date_start:_date_start,date_end:_date_end,group_by:_group_by,ids:_selected.ids,colors:_selected.colors},
            success: function(_data){
                //если все хорошо, то по полученным данным рисуем сразу все графики
                if(_data.ok){
                    var _gr_list = _data['global_result'];
                    if(_gr_list.length>0)
                        for(var i=0;i<_gr_list.length;i++){
                            drawGraphic(_gr_list[i]);
                        }
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                return false;
            },
            complete: function(){
            }
        });
    }
    //рисуем график по считанным данным
    function drawGraphic(list){
        if(list){
            //если загрузилось, рисуем график
            var data = new google.visualization.DataTable();
            //читаем набор полей
            var fields=list['fields'];
            for (k=0;k<fields.length;k++){
                data.addColumn(fields[k][0], fields[k][1]);
            }
            
            //определяем тип графика и задаем опции
            if (list['type']=='Line'){
                var options = {'width':list['width']||'100%',
                                fontSize: 8,
                               'height':list['height']||'100%',
                               'hAxis' : {maxAlternation: 3, slantedTextAngle:60, showTextEvery:5},
                               'legend':{position: list['legend_position']||'bottom'},
                                colors:list['colors']||null,
                                'chartArea':{left:30,top:10,width:"80%"},
                               'animation':{duration: 500,easing: 'out'}};
                //собираем название элемента, куда пихнем график
                var _elem_id = "";
                if(list['selection']){
                    _elem_id += "graphic-" + list['selection'] + "-" + list['type'].substring(0,1).toLowerCase();
                }
                var chart = new google.visualization.LineChart(document.getElementById(_elem_id));
            } else if (list['type']=='Pie'){
                if(list['is3D']==undefined) list['is3D'] = true;
                var options = {'width':list['width']||'400',
                               'height':list['height']||'200',
                                top:0,
                                fontSize: 8,
                                left:0,
                                pieSliceText: 'none',
                                'legend':{position: list['legend_position']||'right'},
                                is3D: list['is3D'],
                                colors:list['colors']||null,
                                'chartArea':{left:0,top:10,width:"95%"},
                                'animation':{duration: 500,easing: 'out'}};
                //собираем название элемента, куда пихнем график
                var _elem_id = "";
                if(list['selection']){
                    _elem_id += "graphic-" + list['selection'] + "-" + list['type'].substring(0,1).toLowerCase();
                }
                var chart = new google.visualization.PieChart(document.getElementById(_elem_id));
            } else if (list['type']=='Column'){
                var options = {'width':list['width']||'100%',
                                fontSize: 11,
                               'height':list['height']||'100%',
                               'legend':{position: list['legend_position']||'bottom'},
                               'hAxis' : {showTextEvery:list['show_every']||2},
                                title : list['v_axe_title']||'',
                                colors : list['colors']||null,
                                'chartArea':{left:30,top:10,width:"95%"},
                                'vAxis':{title : list['v_axe_title']||''},
                                axisTitlesPosition:'out',
                               'animation':{duration: 500,easing: 'out'}};
                //собираем название элемента, куда пихнем график
                var _elem_id = "";
                if(list['selection']){
                    _elem_id += "graphic-" + list['selection'] + "-" + list['type'].substring(0,1).toLowerCase();
                }
                var chart = new google.visualization.ColumnChart(document.getElementById(_elem_id));
            }
            
            //если list['multi']==TRUE, значит передано несколько массивов,
            //и у графика будет несколько опций выбора (например тип квартиры)
            if (list['multi']){
                data.addRows(list['data'][0]);
                if (list['colors']){
                    options['colors']=list['colors'][0]||null;
                    if ((options['colors']!=null)&&(typeof options['colors'] != 'object')) options['colors']=[options['colors']];
                }
                //чистим div с кнопками, пржеде чем добавлять новые
                for(k=0;k<list['options_names'].length;k++){
                    var _class = k==0?'active':'';
                    button_html='<li id="opt_'+k+'" class="'+_class+'">'+list['options_names'][k]+'</li>';
                    var _ul = elem.siblings('ul.ajax-graphics-tabs');
                    _ul.append(button_html);
                    jQuery('li#opt_'+k,_ul).click(function() {
                        var array_number=parseInt(this.id[this.id.length-1],10);
                        data.removeRows(0,data.getNumberOfRows());
                        data.addRows(list['data'][array_number]);
                        if (list['colors']){
                            options['colors']=list['colors'][array_number]||options['colors']||null;
                            if (typeof options['colors'] != 'object') options['colors']=[options['colors']];
                        }
                        chart.draw(data, options);
                        var el = jQuery(this);
                        el.addClass('active').siblings('li.active').removeClass('active');
                    });
                }
                chart.draw(data, options);
            }
            else{
                //если опций выбора нет, просто чистим div с кнопками
                try{
                    data.addRows(list['data']);
                    chart.draw(data, options);
                }
                catch(e){
                    
                }
                
            }
        }
        return true;
    }
});
