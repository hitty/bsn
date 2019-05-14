jQuery(document).ready(function(){
    
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({
                'buttonSetMain':false,
                'queueSizeLimit':1,
                'buttonText':'Добавить файл .txt со ссылками',
                'fileType':'text',
                'multi':false
            }
        );
    }
    
    //кнопка открытия формы добавления
    jQuery('.add-page-to-index').on('click',function(){
        if (jQuery('.page-index-add').hasClass('active')){
            jQuery(this).show();
            jQuery('.page-index-add').removeClass('active');
        }else{
            jQuery(this).hide();
            jQuery('.page-index-add').addClass('active');
        }
    });
    //добавляем страницы из файла
    jQuery('.add-pages-from-file').on('click',function(){
        if (jQuery('.pages-add-from-file').hasClass('active')){
            jQuery(this).show();
            jQuery('.pages-add-from-file').removeClass('active');
        }else{
            jQuery(this).hide();
            jQuery('.pages-add-from-file').addClass('active');
        }
    });
    //отмена добавления, очистка полей
    jQuery('.page-index-add .inputbox_clear').on('click',function(){
        jQuery('.add-page-to-index').click();
        jQuery(this).siblings('.input-value').attr("val",0);
        jQuery(this).siblings('#autocomplete_inputbox').children('#autocomplete_input_add').attr("val","");
    });
    //добавляем страницу в таблицу робота
    jQuery('.page-index-add .button.add').on('click',function(){
        var _page_id = jQuery('#autocomplete_value').val();
        var _url = window.location.href.split('?')[0] + "add/";
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: _url,
            data: {ajax: true, page_id: _page_id},
            success: function(msg){
                if(typeof(msg)=='object' && msg.ok) {
                    jQuery('.list_table').children('table').children('tbody').append("<tr><td><a title='"+msg.data.title+"' href="+msg.data.url+">"+msg.data.url+"</a></td> <td>"+msg.data.title+"</td> <td>"+msg.data.date_in+"</td> <td></td> <td class=\"small_icons ac\"><a href=\"/admin/seo/not_indexed/google/del/\" title=\"Удалить\" target=\"_blank\"><span class=\"ico_del\">Удалить</span></a></td></tr>");
                    jQuery('.page-index-add .inputbox_clear').click();
                } else console.log(msg.alert);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            },
            complete: function(){
            }
        });
    });
    //проверяем в индексе ли страница
    jQuery('.page-index-add .button.check').on('click',function(){
        var _bot_alias = window.location.href.split('/')[6];
        var _page_id = jQuery('#autocomplete_value').val();
        var _url = "/admin/seo/not_indexed/check/";
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: _url,
            data: {ajax: true, page_id: _page_id, bot_alias:_bot_alias},
            success: function(msg){
                if(typeof(msg)=='object' && msg.ok) {
                    if(msg.in_index) alert('Страница уже в индексе '+_bot_alias);
                    else alert('Страница не в индексе '+_bot_alias);
                } else console.log(msg.alert);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            },
            complete: function(){
            }
        });
    });
    jQuery('#file_src').on('change',function(){
        jQuery('.upload-datafile').submit();
    });
    function getData(){  // получение информации с сервера
        var _params = {
            ajax: true,
            date_start: jQuery('#date_start').val(),
            date_end: jQuery('#date_end').val() 
        };
        var _bot_alias = window.location.href.split('/')[6];
        var _url = '/admin/seo/not_indexed/'+_bot_alias+'/';
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
});