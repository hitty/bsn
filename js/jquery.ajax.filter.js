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
if(jQuery)(function(window, document, jQuery, undefined){
    jQuery.fn.ajaxfilter = function(opts) {
        var defaults = {
            value_attribute         : 'value',                  /* аттрибут элемента - значение */
            url_element             : null,                     /* элемент - источник URL */
            query_form_element      : null,                     /* элемент - источник формы поиска */
            page_element            : null,                     /* элемент - источник страницы */
            limit_on_page_element   : null,                     /* элемент - источник кол-ва строк на странице */
            sorting_element         : null,                     /* элемент - источник сортировки строк на странице*/
            scroll_to_element       : 'body',                   /* элемент - прокрутка до элемента */
            link_element            : 'body',                   /* элемент, в который встраивается вся конструкция ajaxfilter */
            f_values                : [],                       /* массив значений всех элементов */
            first_instance          : false,                    /* вызов */
            onExit                  : function(selected_data){}, /* функция, предваряющая закрытие function(item_id){} */
            onDraw               : function(){}
        };
        var options = jQuery.extend(defaults, opts || {});
        var active_element = null;                          /* Элемент, к которому назначен вызов ajaxfilter */
        var current_item_data = null;                       /* Информация о текущем элементе */
        var target_element_url = "";                        /*добавлено для блоков объектов лк. забираем путь к блоку вкладки*/
        
        /* функция стартовой инициализации */
        var start = function(){
            // определение изначальных значений формы
            if(options.url_element !== null) {
                getUrl(false, false);
                getFormQuery();
                jQuery(options.url_element).on('click', function(){
                    if(jQuery(this).hasClass('disabled')) return false;
                    jQuery(this).addClass('active').siblings('li').removeClass('active');
                    target_element_url = jQuery(this).attr('data-value');
                    //если другая makeQuery уже не запущена, не вызываем. чтобы не грузилось два раза
                    makeQuery(true);
                });
            } else return false
            // определение кол-ва объектов 
            if(options.limit_on_page_element !== null) {
                setLimitOnPage(getBSNCookie('View_count_cabinet'));
                jQuery(options.limit_on_page_element).on('change', function(event, value){
                    setLimitOnPage(value);
                    makeQuery(false);
                });
            } 
            //определение сортировки
            if(options.sorting_element !== null) {
                setSortOnPage(getBSNCookie('View_sort_cabinet'));
                jQuery(options.sorting_element).on('change', function(event, value){
                    value = jQuery(this).children('input').val();
                    setSortOnPage(value);
                    makeQuery(false);
                });
            }
            // паджинатор
            if(options.page_element !== null) {
                jQuery(document).on('click', options.page_element+' span', function(){
                    
                    jQuery(this).addClass('active').siblings('span').removeClass('active');
                    
                    var _tab_page = 'page='+jQuery(this).data('link');
                    options.f_values[2] = _tab_page;
                    
                    var _tab_url = jQuery('#objects-list-title').children('li.active').attr('data-value');
                    if(_tab_url !== undefined){
                        //вставляем в URL вкладки номер страницы
                        if(_tab_url.indexOf('page') == -1){
                            var _insert_position = _tab_url.indexOf("?") + 1
                            _tab_url = [_tab_url.slice(0, _insert_position), _tab_page + "&", _tab_url.slice(_insert_position)].join('');
                        }else{
                            _tab_url = _tab_url.replace(/page\=[0-9+]/,_tab_page);
                        }
                        jQuery('#objects-list-title').children('li.active').attr('data-value',_tab_url);
                    }
                    
                    
                    
                    if(typeof jQuery(this).data('link') == 'string' || parseInt(jQuery(this).data('link')) > 0) makeQuery(true);
                    return false;
                });
            }
            //форма поиска
            if(options.query_form_element !== null) {
                //управление видимостью формы
                jQuery('.expand div', options.query_form_element).on('click', function(){
                    jQuery(this).removeClass('active').siblings('div').addClass('active');
                    
                    if(jQuery('.expanding-area').length > 0) jQuery('.params-wrap .expanding-area').fadeToggle(150);
                    else jQuery('.params-wrap, .submit-wrap', options.query_form_element).fadeToggle(150);
                    
                })
                //управление поведением переключателей формы
                manageFormBoxes();
                //построение запроса
                jQuery('#submit-ajax-form').on('click', function(){
                    getFormQuery();
                    makeQuery(false);
                });
                //сброс фильтра
                jQuery('.reset-filter', jQuery('#ajax-filter')).on('click', function(){
                    jQuery('input',options.query_form_element).each(function(){ 
                       
                        var _val = jQuery(this).val();
                        if(_val == parseInt(_val)) _val = 0;
                        else _val = '';
                        jQuery(this).val(_val);
                        
                    })
                    //для выпадающих списков выбираем верхнюю опцию
                    jQuery('#ajax-filter').find('ul').each(function(){
                        jQuery(this).children('li').removeClass('selected');
                        jQuery(this).children('li').first().addClass('selected');
                        jQuery(this).parent().children('.pick').html(jQuery(this).children('li').first().html());
                        
                    });
                    jQuery('#ajax-filter').children('.params-wrap').children().removeClass('active');
                    jQuery('.list-selector li[data-value=0]', options.query_form_element).click();
                    getFormQuery();
                    makeQuery(false);
                });
            } 
            
            //запрос (если вписываем не во вкладку списка - например, Финансы)
            makeQuery(false);
        };
        //получение URLа для запроса
        var getUrl = function(instance, from_tabs){
            //options.f_values[0] = jQuery(options.url_element+'.active').data(options.value_attribute);
            options.f_values[0] = jQuery(options.url_element+'.active,'+ options.url_element+'.selected').attr('data-' + options.value_attribute);
            //с 1-ой страницы
            if(instance == false) options.f_values[2] = 'page=1';
            if(from_tabs == true) options.f_values[4] = 'from_tabs=1';
            else options.f_values[4] = '';
            
        };
        //получение кол-ва объектов на странице
        var setLimitOnPage = function(value){
            setBSNCookie('View_count_cabinet', value, 30, '/');
            jQuery(options.limit_on_page_element+' .list-data li[data-value='+value+']').click();
            if(value=='null') value = 0;
            options.f_values[1] = 'count='+value;
            //с 1-ой страницы
            options.f_values[2] = 'page=1';
        }
        //получение сортировки объектов на странице
        var setSortOnPage = function(value){
            setBSNCookie('View_sort_cabinet', value, 30, '/');
            jQuery(options.sorting_element+' .list-data li[data-value='+value+']').click();
            if(value=='null') value = 0;
            options.f_values[1] = 'sortby='+value;
            //с 1-ой страницы
            options.f_values[2] = 'page=1';
        }
        //управление поведением переключателей формы
        var manageFormBoxes = function(){
            //управление периодом
            if(jQuery('.select-time-period', options.query_form_element).length > 0){    
                jQuery('.select-time-period', options.query_form_element).on('change', function(){  
                    var _val = jQuery('#filter_period').attr('value');
                    if(_val=='1') _diff  = 7;
                    else if(_val=='2') _diff = 30;
                    else return false;
                    jQuery('#filter_date_end').val(moment().format("DD.MM.YY"));
                    jQuery('#filter_date_start').val(moment().subtract(_diff, 'days').format("DD.MM.YY"));
                }).triggerHandler('change');
            }
            //инициализация временных отсечек - дни
            if(jQuery('.datetimepicker').length>0){
                jQuery('.datetimepicker').datetimepicker({
                  timepicker:false,
                  format:'d.m.y',
                  onChangeDateTime:function(dp,jQueryinput){
                      jQueryinput.attr('value',jQueryinput.val())
                      if(jQuery('#filter_period').length > 0 && (jQueryinput.attr('id') == 'filter_date_end' || jQuery('#filter_date_end').val()!='')) {
                          jQuery('.select-time-period li[data-value=0]', options.query_form_element).click();
                      }
                      jQueryinput.blur();
                  }
                });
            }    
        }            
        //получение значений формы поиска
        var getFormQuery = function(value){
            _ccount = 0;
            _form_values = [];
            jQuery('input',options.query_form_element).each(function(){
                var _val = jQuery(this).val();
                //проверяем, что это не просто поле ввода, а еще и есть имя
                if(_val!='' && _val!=0 && jQuery(this).prop('name')) {
                    //если указано, что поле числовое, убираем все кроме цифр (например для цены с отбивкой)
                    if(jQuery(this).attr('data-vtype') == 'numeric') _val = _val.replace(/[^0-9]/,'');
                    _form_values[_ccount] = jQuery(this).prop('name')+'='+_val;
                    _ccount++;   
                }
            })
            options.f_values[3] = _form_values.join('&');
        }
        //построение запроса и вызов результатов
        var makeQuery = function(from_tabs){
            getUrl(true, from_tabs);
            //читаем страницу, которую должны открыть
            var _tab_url = jQuery('#objects-list-title').children('li.active').attr('data-value');
            if(_tab_url !== undefined){
                //вставляем в URL вкладки номер страницы
                if(_tab_url.indexOf('page') == -1){
                    options.f_values[2] = "page=1";
                }else{
                    options.f_values[2] = _tab_url.match(/page\=[0-9]+/)[0];
                }
            }
            
            
            var _query = options.f_values.join('&');
            //
            if(options.first_instance == true) jQuery('html, body').animate({scrollTop: jQuery(options.scroll_to_element).offset().top}, 200);
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _query, data: {ajax: true},
                success: function(msg){
                    if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok && typeof(msg.html)=='string' && msg.html.length) {
                        if(typeof msg.types == 'object'){
                                                    
                            _status = jQuery('#objects-list-title').data('status');
                            var _tabs_total_objects = 0;
                            var _click = false;
                            for(i=0; i<msg.types[_status].length; i++){
                                var _li = jQuery('li[data-type="'+msg.types[_status][i].type+'"]', jQuery('#objects-list-title'))    ;
                                if(msg.types[_status][i].cnt > 0) {
                                    _li.removeClass('disabled');
                                    _li.children('sup').text(msg.types[_status][i].cnt);
                                    if(msg.from_tabs == 0 && _click == false && msg.stay == undefined) {_click = true; _li.click();}
                                } else {
                                    _li.addClass('disabled');
                                    _li.children('sup').text('');
                                }
                            }
                            if(msg.count == 0){
                                _value = '';
                                jQuery('#objects-list-title li').each(function(){
                                    var _this = jQuery(this);
                                    _value = _this.find('sup').text();
                                    if(_value != '') { _this.click(); return false;}
                                    
                                }).promise().done(function () {
                                    if(_value == ''){
                                        var elem = jQuery('.'+active_element.prop('class').replace(' ','.'));
                                        elem.fadeOut(100,function(){
                                            elem.html(msg.html).fadeIn(200);
                                        });
                                    }
                                })
                            } else {
                                var elem = jQuery('.'+active_element.prop('class').replace(' ','.'));
                                elem.fadeOut(100,function(){
                                    elem.html(msg.html).fadeIn(200);
                                    if(typeof(options.onDraw) == 'function'){
                                        window.setTimeout(options.onDraw(),210);
                                    }
                                });
                            }
                            var _total_objects = 0;
                            
                            //устанавливаем параметры в поля формы
                            if(msg.params_to_set !== undefined){
                                for(var _item in msg.params_to_set) if(jQuery('#filter_' + _item).length > 0) jQuery('#filter_' + _item).val(msg.params_to_set[_item]);
                            }
                            
                            //общее количество в меню
                            if(msg.page == 'cabinet'){
                                jQuery('.members-objects-count li').each(function(){
                                    var _status = jQuery(this).data('status');
                                    var _count = 0;
                                    for(i=0; i<msg.types[_status].length; i++){
                                       _count = _count + parseInt(msg.types[_status][i].cnt); 
                                    }
                                    jQuery(this).children('a,span').children('span').text(_count);
                                    _total_objects = _total_objects + _count;
                                    //управление ссылками объектов
                                    jQuery(this).children('a,span').attr('data-additional-href',options.f_values[3]);
                                })
                                if(jQuery('.members-menu .cabinet .amount').length == 0) jQuery('.members-menu .cabinet').append('<i class="amount"></i>');
                                if(jQuery('.auth-menu-links .cabinet .amount').length == 0) jQuery('.auth-menu-links .cabinet').append('<i class="amount"></i>');
                                jQuery('.members-menu .cabinet .amount, .auth-menu-links .cabinet .amount').text(_total_objects);
                            } else if(msg.page == 'applications'){jQuery('.members-objects-count .applications span').html(msg.types[_status][0].cnt);}
                        } else {
                            var elem = jQuery('.'+active_element.prop('class').replace(' ','.'));
                            elem.fadeOut(100,function(){
                                elem.html(msg.html).fadeIn(200);
                            });
                        }                 

                    }
                }
            });
            
            if(typeof(options.onExit) == 'function') options.onExit(_query);
            return false;
        }
        return this.each(function(){
            active_element = jQuery(this);
            start(); 
            options.first_instance = true;  
        });
    }
})(window, document, jQuery);