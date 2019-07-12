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
    jQuery.fn.estate_search_left = function(opts) {
        var defaults = {
            sidebar_url             : [],                       /* URL сайдбара */            
            search_results_element  : '#search-results',        /* элемент - результаты поиска */
            page_element            : '#search-results .paginator',/* элемент - источник страницы */
            limit_on_page_element   : null,                     /* элемент - источник кол-ва строк на странице */
            sorting_element         : '#search-results #sort_selector',/* элемент - источник сортировки строк на странице*/
            scroll_to_element       : 'body',                   /* элемент - прокрутка до элемента */
            f_values                : [],                       
            accept_button_class     : 'accept-button',                                /* класс кнопки мультивыбора */
            accept_button           : '<span class="accept-button">Применить</span>', /* шаблон кнопка мультивыбора */
            first_instance          : false,                    /* вызов */
            onExit                  : function(selected_data){}, /* функция, предваряющая закрытие function(item_id){} */
            onDraw               : function(){}
        };
        var options = jQuery.extend(defaults, opts || {});
        var active_element = null;                          /* Элемент, к которому назначен вызов ajaxfilter */
        
        /* функция стартовой инициализации */
        var start = function(){
            var location = window.history.location || window.location;
            
            options.sidebar_url = active_element.data('url');
            
            active_element.on('click', 'a,.geodata-items', function(event){
                if(!jQuery(this).parents('.data').hasClass('multi-box')){
                    options.sidebar_url = typeof jQuery(this).attr('href') == "string" ? jQuery(this).attr('href') : jQuery(this).attr('data-link');
                    active_element.attr('data-url', options.sidebar_url);
                    if(jQuery(this).hasClass('radio')) jQuery(this).siblings('a').removeClass('on');
                    jQuery(this).toggleClass('on');
                    //geodata
                    var _parent = jQuery(this).parents('.data');
                    if((event.screenX && event.screenX != 0 && event.screenY && event.screenY != 0) && (_parent.hasClass('districts') ||_parent.hasClass('district-areas') ||_parent.hasClass('subways') )){
                        var _class = _parent.hasClass('districts') ? 'districts' : ( _parent.hasClass('district-areas') ? 'district-areas' : 'subways');
                        var _id = jQuery(this).data('id');
                        jQuery('.item[data-id='+_id+']', jQuery('#geodata-picker-wrap .selected-items[data-type='+_class+']')).click();
                    }
                    makeQuery();
                } else if(!jQuery(event.target).hasClass(options.accept_button_class)) multiChoose(jQuery(this))
                return false;
            })
           
            //мультивыбор
            active_element.on('click', '.multi', function(){
                var _el = jQuery(this);
                _el.toggleClass('cancel');
                var _parent = _el.parents('.data');
                _parent.toggleClass('multi-box');
                if(_el.hasClass('cancel')) {
                    jQuery('a', _parent).removeClass('on');
                    _el.text('Отменить выбор');
                } else {
                    _el.text('Выбрать несколько');
                } 
            }) 
            //применить мультивыбор
            active_element.on('click', '.' + options.accept_button_class, function(){
                countChosingElements(jQuery(this).parents('a'), true);
            })
            
            //сбросить фильтр
            active_element.on('click', '.reset', function(){
                options.sidebar_url = jQuery(this).data('url');
                makeQuery();
            }) 
            
            //видимость блоков сайдбара
            active_element.on('click', '.block .expand', function(){
                jQuery(this).parents('.data').addClass('on');
            }) 
            active_element.on('click', '.block .title', function(){
                jQuery(this).toggleClass('on');
                return false;
            })
            
            // паджинатор
            if(options.page_element !== null) {
                jQuery(document).on('click', options.page_element + ' span', function(e){
                    var _link = jQuery(this).data('link');
                    if(_link.length > 0) {
                        options.sidebar_url = _link;
                        makeQuery();
                        return false;
                    }
                })
            }      
                  
            // сортировка
            if(options.sorting_element !== null) {
                jQuery(document).on('change', options.sorting_element, function(e){
                    return false;
                })
            }         
            jQuery(document).on('click', '.typewatch_popup_list li', function(){
                makeQuery();
            })            
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 27: // esc
                        
                        if(jQuery('#address').val()!='' || jQuery('.typewatch_popup_list').length > 0){
                            hidePopupList();
                            jQuery('#address').val('');
                        }
                    break;     
                }
            });               
            jQuery(document).on('click', '.clear-input', function(){
               var _class = jQuery(this).prev('input').attr('name');
                jQuery('input[name='+_class+']').attr('value','').val('').siblings('input').val('').siblings('.clear-input').addClass('hidden');
                jQuery('.autocomplete.address').change();
                makeQuery();
            });    
                       
            jQuery(document).on('click', '.autocomplete.address', function(){
                if(jQuery(this).val()=='') jQuery('.list-picker.location').removeClass('disabled');
                else jQuery('.list-picker.location').addClass('disabled');
            })
            
            jQuery(document).on('click', '.paginator .paginator-link', function(){
                options.sidebar_url = jQuery(this).attr('href');
                makeQuery();
                return false;
            })
                  
        }
        
        //получение левого сайдбара
        var makeSidebar = function(){
            active_element.addClass('search-waiting');
            getPendingContent('#' + active_element.attr('id') + '', '/estate/leftsidebar/', {data: options.sidebar_url}, false, false, false, false, 
                function(){active_element.removeClass('search-waiting')}
            )    
            return false;
        };
        
        //получение данных и запись в историю
        var makeQuery = function(){
            options.f_values = [];
            jQuery('#estate-search .form-wrap input').each(function(){
                deleteFromUrl(jQuery(this).attr('name'));
                if(parseInt(jQuery(this).attr('value')) > 0) options.f_values.push(jQuery(this).attr('name') + '=' + jQuery(this).attr('value'));
            })
            options.sidebar_url = options.sidebar_url + ( options.f_values.length > 0 ? (options.sidebar_url.indexOf('?') > 0 ? '&' : '?') + options.f_values.join('&') : '');
            if(options.f_values.length > 0)options.sidebar_url = makeUrl(options.sidebar_url);
            history.pushState(null, null, options.sidebar_url);
            jQuery(options.search_results_element).addClass('search-waiting');
            
            makeSidebar(); 
            
            jQuery.ajax({
                url: options.sidebar_url,
                cache: false, type: 'POST',
                async: true, dataType: 'json',
                data: {ajax: true, new_search: true},
                success: function(msg){
                    if(msg.ok){
                        jQuery(options.search_results_element).html(msg.html).removeClass('search-waiting');
                        if(msg.h1) {
                            jQuery(options.search_results_element).siblings('h1').html(msg.h1);
                            document.title = msg.title;
                        }
                        if(msg.pretty_url) history.pushState(null, null, '/' + msg.pretty_url + '/');
                        jQuery('.central-text ').html(msg.seo_text);
                        
                    } 
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log("Error: "+textStatus+" "+errorThrown);                                                                                                                 
                },
                complete: function(){}            
            })
            
            jQuery('html, body').animate({scrollTop: jQuery(options.scroll_to_element).offset().top}, 200);
        }
        
        //мультивыбор
        var multiChoose = function(_el){
            jQuery('.accept-button').remove();
            _el.toggleClass('on').append(options.accept_button);
            countChosingElements(_el, false);
        }   
        
        //мультивыбор
        var countChosingElements = function(_el, _button_click){
            var _params = [];
            var _parent = _el.parents('.data');
            jQuery('a', _parent).each(function(){
                if(jQuery(this).hasClass('on')) _params.push(jQuery(this).data('id'));
            })
            if(_button_click == false){
                if(_params.length == 0) jQuery('.accept-button').remove();
            } else {
                var _type = _parent.data('type');
                var _url = _type + '=' + _params.join(',');
                options.sidebar_url = options.sidebar_url + ( _params.length > 0 ? (options.sidebar_url.indexOf('?') > 0 ? '&' : '?') + _url : '');
                makeQuery();
            }
            return false;
        }   
        
        //удаление из URL параметра
        var deleteFromUrl = function(parameter){
            var urlparts= options.sidebar_url.split('?');   
            if (urlparts.length>=2) {

                var prefix= encodeURIComponent(parameter)+'=';
                var pars= urlparts[1].split(/[&;]/g);

                //reverse iteration as may be destructive
                for (var i= pars.length; i-- > 0;) {    
                    //idiom for string.startsWith
                    if (pars[i].lastIndexOf(prefix, 0) !== -1) {  
                        pars.splice(i, 1);
                    }
                }

                options.sidebar_url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
                return options.sidebar_url;
            } else {
                return options.sidebar_url;
            }            
        }           
        var makeUrl = function(url){
            var _params = [], _url = '';
            var _params = url.slice(url.indexOf('?') + 1).split('&');
            var _url = url.slice(0, url.indexOf('?'));
            _params.sort();
            return _url + '?' + _params.join('&');
        }      
        return this.each(function(){
            active_element = jQuery(this);
            start(); 
            options.first_instance = true;  
        });
    }
})(window, document, jQuery);