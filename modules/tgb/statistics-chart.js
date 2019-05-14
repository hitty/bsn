jQuery(document).ready(function(){
    var _chart_data = [];

    function getData(){  // получение информации с сервера
        var _params = {
            ajax: true,
            date_start: jQuery('#date_start').val(),
            date_end: jQuery('#date_end').val() 
        };
        var _url = '/admin/advert_objects/tgb/total_stats/';
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url, data: _params,
            success: function(_list){
                jQuery('#graphic_place, #graphic_ctrls').show();     // отображение графика и панели с элементами управления отображения показателей
                if(_list){
                    if (_list.html){
                        jQuery('#result_info_stats').html(_list.html);
                    }
                    if (_list.data){
                        _chart_data  = _list;
                        jQuery("#graphic_ctrls").css('height',_list.height);
                        drawChart();     
                    }
                } else
                    alert("Ошибка данных");
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                    alert("Error: "+textStatus+" "+errorThrown);
            },
            complete: function(){
            }
        });
        return true;
    }

    function drawChart(){ // отрисовка графика
        jQuery("#graphic_ctrls").css('height',_chart_data.height);
        var _data = new google.visualization.DataTable();
        _data.addColumn(_chart_data.fields[0][0],_chart_data.fields[0][1]);   // "Дата" - присутствует всегда
        var _colors = [];
        var _counter = 1;
        for (var _i=1;_i<_chart_data.fields.length;_i++){
            if (jQuery('#graphic_ctrls [data-chart-type="'+_counter+'"]:checked').length){   // проверка на наличие галочки для отображения показателя
                _data.addColumn(_chart_data.fields[_i][0],_chart_data.fields[_i][1]);
                _colors.push(_chart_data.colors[_i-1]);    
            }
            jQuery('#graphic_ctrls_'+_counter).css('color',_chart_data.colors[_i-1]);    // -1, т.к. дата идет первым столбцом, а она отображается всегда
            _counter++;                    
        }
        
    
        var _arr = [];
        for (var _j=0;_j<_chart_data.data.length;_j++){
            var _counter = 0;
            var _line = [];
            _line.push(_chart_data.data[_j][0][1]);
            for (var _i=1;_i<_chart_data.fields.length;_i++){
                if (jQuery('#graphic_ctrls [data-chart-type="'+_i+'"]:checked').length){
                    _line.push(_chart_data.data[_j][_i][1]);  
                }  
            }
            _arr.push(_line);
        }
        _data.addRows(_arr);
        var _options = {fontSize: 9,
                       'height':_chart_data.height,
                       'width':_chart_data.width,
                       'hAxis' : {maxAlternation: 3, slantedText: 'true', slantedTextAngle:60, showTextEvery:Math.floor(_arr.length/30)},
                       'legend':{position: 'bottom'},
                       'colors': _colors,
                       'chartArea':{left:45,top:10,width:"90%"},
                       'animation':{duration: 500,easing: 'out'}};
                  

        var chart = new google.visualization.LineChart(document.getElementById('graphic_tgb'));
        chart.draw(_data, _options);       
    }

    jQuery(document).delegate(".hasDatepicker", "change", function(event){
        jQuery(".form_default .button").removeClass("pressed");
        if (jQuery('#date_start').val().length>0 && jQuery('#date_end').val().length>0)
            getData();
    });
     
    jQuery(document).delegate("#graphic_ctrls input[type=checkbox]", "change", function(event){    // управление отображением/скрытием показателей
        if (!jQuery("#graphic_ctrls input[type=checkbox]:checked").length){
            jQuery("#graphic_tgb").html('<strong style="color: red">Выберите показатели для графической визуализации</strong>');
            jQuery("#graphic_ctrls").css('height','165');
            return;
        }
        drawChart();
    });

    function getFormatDate(d){
        return ((d.getDate()<10)?'0':'')+d.getDate()+"."+(((d.getMonth()+1)<10)?'0':'')+(d.getMonth()+1)+"."+d.getFullYear();
    }

    jQuery(document).delegate(".form_default .button", "click", function(event){    // клик по кнопке быстрого выбора даты
        var _today = new Date();
        jQuery('#date_end').val(getFormatDate(_today));      // подстановка текущей даты в поле "по"
        switch(jQuery(this).attr('data-period')){
            case 'day':     // день
                jQuery(".form_default .button").removeClass("pressed");
                jQuery(this).addClass("pressed");
                jQuery('#date_start').val(getFormatDate(_today));
            break;
            case 'week':    // неделя
                var _last = new Date();
                _last.setDate(_last.getDate()-7);
                jQuery(".form_default .button").removeClass("pressed");
                jQuery(this).addClass("pressed");
                jQuery('#date_start').val(getFormatDate(_last));
            break;
            case 'month':   // месяц
                 var _last = new Date();
                _last.setMonth(_last.getMonth()-1);
                jQuery(".form_default .button").removeClass("pressed");
                jQuery(this).addClass("pressed");
                jQuery('#date_start').val(getFormatDate(_last));
            break;
            case 'quarter':       // квартал
                var _last = new Date();
                _last.setMonth(_last.getMonth()-4);
                jQuery(".form_default .button").removeClass("pressed");
                jQuery(this).addClass("pressed");
                jQuery('#date_start').val(getFormatDate(_last));
            break;
            case 'year':    // год
                var _last = new Date();
                _last.setYear(_last.getFullYear()-1);
                jQuery(".form_default .button").removeClass("pressed");
                jQuery(this).addClass("pressed");
                jQuery('#date_start').val(getFormatDate(_last));
            break;
        }
        getData();  
    });
});
