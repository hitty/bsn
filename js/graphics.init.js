function getGraphicsContent(_element, _url){
    var _elem_array = new Array()
    var _url_array = new Array(); 
    if(typeof(_element) == 'object'){
        _elem_array =  _element;
        _element =  _elem_array.shift();
        _url_array =  _url;
        _url =  _url_array.shift(); 
    } 
    var elem = _element;
    if(typeof(_element) == 'string') elem = jQuery(_element);
    _params = {ajax: true};
    _cached = false;
    var expanded_width=0;
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: _cached,
        url: _url, data: _params,
        success: function(list){
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
                    var chart = new google.visualization.LineChart(document.getElementById(elem.attr('id')));
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
                    var chart = new google.visualization.PieChart(document.getElementById(elem.attr('id')));
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
                    var chart = new google.visualization.ColumnChart(document.getElementById(elem.attr('id')));
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
                    data.addRows(list['data']);
                    chart.draw(data, options);
                }         
            } else alert("Ошибка данных");
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
                alert("Error: "+textStatus+" "+errorThrown);
        },
        complete: function(){
        }
    });
    return true;
}