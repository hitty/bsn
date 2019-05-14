if($)(function(window, document, $, undefined){
    $.fn.estateSearch = function(opts) {
        var defaults = {
            ajax_search           : false, 
            map_search            : false, 
            click_type            : 'inner', 
            last_click            : 0, 
            estate_url            : '', 
            deal_type             : '', 
            estate_type           : '', 
            estate_type_current   : '', 
            id_object             : '', 
            selected_value        : '', 
            params                : Array,                       /* массив значений всех элементов */
            selected_el           : '',
            type_object_list      : '.select-object-type .list-data',
            input_list            : '.list-selector,input:visible,.extend-search-wrap .tab.active input,#user_objects,#only_photo,.extend-params input,.middle-panel input,input[name="id_housing_estate"],input[name=id_business_center],input[name=id_cottage]',
            ajax_count            : false,
            visited_ids            : Array()
        }
        var o = $.extend(defaults, opts || {});
        var init_selector = null;                           /* Элемент, к которому назначен вызов */
        
        /* функция стартовой инициализации */
        var start = function(){
            
            init_selector.submit(function(e){
                o.ajax_count = false;
                search_result();
                return false;
            });
            o.estate_type_current = init_selector.data('estate-type');
            if(o.estate_type_current == '') o.estate_type_current = 'build';
            
            //управление типом сделки
             jQuery( '.select-deal-type', init_selector ).on('change', function(){
                o.deal_type = jQuery('[name=deal_type]', init_selector).val();
                o.deal_type = o.deal_type == 2 ? 'sell' : 'rent';

                _inverted_deal_type = o.deal_type == 'rent' ? 'sell' : 'rent';
                jQuery( o.type_object_list + '  div', init_selector ).removeClass('hidden').siblings('div[data-deal=' + _inverted_deal_type + ']').addClass('hidden');
                var _inverted_value = o.id_object + '-' + o.estate_type + '-' + o.deal_type;
                if(jQuery( o.type_object_list + '  [data-value=' + _inverted_value + ']', init_selector ).length > 0) {
                   jQuery( o.type_object_list + '  [data-value=' + _inverted_value + ']', init_selector ).click();
                   jQuery( '.select-object-type').change(); 
                }
                else jQuery( o.type_object_list + ' div[data-deal=' + o.deal_type + '] li:first', init_selector ).click();
             })       
            
            //обработчик для паджинатора объектов в выдаче компании
            if(jQuery('.ajax-search-results').length > 0){
                jQuery('.ajax-search-results').each(function(){
                    //отключаем событие, чтобы не подключалось второй раз
                    jQuery(document).off('click','.paginator span');
                    jQuery(document).on('click','.paginator span',function(e){
                        e.preventDefault();
                        var _url = jQuery(this).attr('data-link');
                        if ( typeof _url == 'undefined' ) return false;
                        var _selector = jQuery(this).parents('.ajax-search-results').attr('class').replace(/(\s)/g,'.');
                       
                        getPendingContent('.' + _selector,_url);
                        jQuery(document).scrollTop(jQuery('#fast-search-form').offset().top-85);
                    });
                });
                o.ajax_search = true;
            }                                                                                                                                 
            
            //управление типами объектов
            jQuery(".list-selector.select-object-type", init_selector).on('change', function(event, value){
                getSelectedParams();
                
                jQuery('.tab, .extend-search-tab', init_selector).removeClass('active');

                //отображение средней части
                jQuery('.middle-panel div[data-index=' + o.selected_el.data('middle-index') + ']', init_selector).addClass('active').siblings('div').removeClass('active');
                jQuery('.tab[data-estate-type='+o.estate_type+']', init_selector).addClass('active').attr('data-deal', o.deal_type);
                
                //отбражение табов расширенного поиска
                jQuery('.extend-search-wrap div[data-estate-type=' + o.estate_type + '] .extend-search-tab[data-type~=' + o.selected_value + ']', init_selector).addClass('active');
                
                jQuery('.pick', jQuery('.select-object-type')).text(o.selected_el.data('title'));
                jQuery('.first-row li, .second-row li').removeClass('selected');
                o.selected_el.addClass('selected');
            });
            //автозаполнения
            jQuery('.autocomplete', jQuery('#fast-search-form')).each(function(){
                var _input = jQuery(this);
                _input.typeWatch({
                    callback: function(){
                        jQuery(this).next('input').val(0);
                        var _searchstring = this.text;
                        _input.addClass('wait');
                        jQuery.ajax({
                            type: "POST", dataType: 'json',
                            async: true, cache: false,
                            url: _input.data('url'),
                            data: {ajax: true, search_string: _searchstring},
                            success: function(msg){
                                if(msg.list.length>0) showPopupList(_input, msg.list);
                                else hidePopupList();       
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown){
                                console.log('Запрос не выполнен!');
                            },
                            complete: function(){
                                _input.removeClass('wait');
                            }
                        });
                    },
                    wait: 150,
                    highlight: true,
                    captureLength: 1
                }).blur(function(){
                    setTimeout(function(){hidePopupList()}, 350);
                });        
            })
            
            jQuery('.clear-input').on('click', function(){
               var _class = jQuery(this).prev('input').attr('name');
                jQuery('input[name='+_class+']').attr('value','').val('').siblings('input').val('').siblings('.clear-input').addClass('hidden');
                jQuery('.autocomplete.address').change();
            });    
            //управление адресом
            jQuery('.autocomplete.address').on('change', function(){
                if(jQuery(this).val()=='') jQuery('.list-picker.location').removeClass('disabled');
                else jQuery('.list-picker.location').addClass('disabled');
            })
             
            //расширенный поиск
            jQuery('.extend-search').on('change', function(e){
                jQuery('.extend-search-wrap').slideToggle(0);
            })
            jQuery('.list-selector.select-object-type').change();

            //поиск по карте
            if( init_selector.hasClass( 'map-search' ) ){
                o.ajax_search = true;    
                o.map_search = true;
                mapInit();    
            }
            //ajax подсчет кол-ва объектов (на лету)
            if( o.ajax_search == true ) {
                if( o.map_search == true) ymaps.ready(function() { search_result(); }) 
                else search_result();                
            } 
            init_selector.find(o.input_list).on('change', function(){
                
                if( o.ajax_search == false ) o.ajax_count = true;
                if( o.map_search == true) ymaps.ready(function() { search_result(); }) 
                else search_result();
            })
            //сброс фильтра
            jQuery('.ajax-count .reset-form', init_selector).on('click', function(){
                init_selector.find(o.input_list).each(function(){
                    var _this = jQuery(this);
                    var _type = _this.attr('type');
                    _name = _this.attr('name');  
                    if(_name != 'extend_search') {
                        if(_type == 'checkbox'){
                            if(_this.parent().hasClass('on')) _this.parent().click();
                            
                        } else {
                            if( _type == 'radio'){
                                jQuery('input[name=' + _name + ']:checked', init_selector).val(0).parent().click();
                            } else {
                                if(parseInt( _this.val() ) > 0){
                                    _this.val('');
                                    if( _this.parent().hasClass('list-selector')) {
                                        _this.siblings('ul').children('li:first-child').click();
                                    }
                                }
                            }
                        }
                    }
                    
                }); 
                jQuery('#geodata-picker-wrap .filter input').each(function(){
                    jQuery(this).val('');  
                    init_selector.find('.list-picker a').removeClass('active').siblings('.empty').addClass('active');
                })
                if( o.ajax_search == false ) o.ajax_count = true;
                if( o.map_search == true) ymaps.ready(function() { search_result(); }) 
                else search_result();
                
            });
            
            jQuery("#fast-search-form .subway-picker .pick .counter .count").change(function(){
                jQuery("#fast-search-form .district-picker, #fast-search-form .district-area-picker").trigger('change', "");
            });
            jQuery("#fast-search-form .district-picker .pick .counter .count, #fast-search-form .district-area-picker .pick .counter .count").change(function(){
                jQuery("#fast-search-form .subway-picker").trigger('change', "");
            });
            
            /* LOCATION */
            var _active_type = '';
            _geodata_ids = {'districts':[],'district-areas':[],'subways':[]};
            var _active_tab = '';
            var _offers_wrap = [];
            jQuery(document).on("click", ".list-picker.location", function(){
                var _this = jQuery(this);
                if(_this.hasClass('disabled')) return false;
                var _list = jQuery('#geodata-picker-wrap');
                jQuery('#background-shadow-expanded,#geodata-picker-bg').fadeIn(100);
                setTimeout(function(){
                    jQuery('#geodata-picker-wrap,#geodata-picker-bg').fadeIn(100).css({display:'table'});;
                }, 200)
                
                if(_active_type=='') _list.children('.filter').children('span').first().click();
                else jQuery('#geodata-picker-wrap').children('.filter').children().first().click();
                return false;
            });
            
            //заполнение массива элементами
            jQuery('.location-list > .selected-items', jQuery('#geodata-picker-wrap')).each(function(e){
                var _this = jQuery(this);
                var _type = _this.data('type');
                jQuery('.item.on', _this).each(function(e){
                    _geodata_ids[_type].push(jQuery(this).data('id'));
                });
                var _filter = jQuery('#geodata-picker-wrap .filter');
                jQuery('span', _filter).each(function(){
                    if(_geodata_ids[_type].length > 0) {
                        geodataInformer(_type, _geodata_ids[_type].length)
                    }
                })
                
                
            });
            
            jQuery(".filter span", jQuery('#geodata-picker-wrap') ).on('change', function(e, value){
                var _this = jQuery(this);
                if(typeof value != 'undefined') jQuery('input[type="hidden"]',_this).val(value);
                e.preventDefault();
            });
            jQuery(".filter span", jQuery('#geodata-picker-wrap') ).on('click', function(e){
                e.preventDefault();   
                var _el = jQuery(e.target); //ditrict-picker
                var _selector = _el.parent(); //items-list
                var _items = jQuery('.items-list .items');
                _active_type = _el.data('type');
                jQuery('#geodata-picker-wrap').attr('class', '').addClass('selected-' + _active_type);
                _el.addClass('on').siblings('.filter span').removeClass('on');
                jQuery('.location-list .selected-items.'+_active_type+'-list').addClass('on').siblings('.selected-items').removeClass('on');
                var _url = jQuery('input[type="hidden"]',_el).attr('data-url');
                var _values = Array();
                if(jQuery('input[type="hidden"]',_el).length>0)
                    if(jQuery('input[type="hidden"]',_el).val().length>0) _values = jQuery('input[type="hidden"]',_el).val().split(',');
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', cache: true,
                        url: _url, data: {ajax: true, selected: _values},
                        success: function(msg){ 
                            if( typeof(msg)=='object') {
                                _items.html(msg.html);
                                districtMark(); 
                            }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown){
                            console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
                        }
                    });
                    return false;
                });
                jQuery(document).on('click', '#geodata-picker-wrap .geodata-button button,#geodata-picker-wrap .closebutton,#geodata-picker-bg', function(event){
                    if(!jQuery('#geodata-picker-wrap .geodata-button button').is(':visible')) return false;
                    event.preventDefault();
                    jQuery('#geodata-picker-wrap,#geodata-picker-bg').fadeOut(100);
                    setTimeout(function(){
                        jQuery('#background-shadow-expanded').fadeOut(100);
                    }, 200)
                    if( o.ajax_search == false ) o.ajax_count = true;
                    if( o.map_search == true) ymaps.ready(function() { search_result(); }) 
                    else search_result();
                    
                    return false;
                });

            jQuery(document).keyup(function(e) {
                    switch(e.keyCode){
                        case 27: jQuery("button", jQuery('#geodata-picker-wrap')).click();  break;     // esc
                    }   
            });    

            //Сброс гео фильтра
            //заполнение массива элементами
            jQuery('#geodata-picker-wrap').delegate('#reset-geo','click',function(event){
                jQuery('.location-list > .selected-items').each(function(e){
                    var _this = jQuery(this);
                    var _type = _this.data('type');
                    _this.children('.item').each(function(e){
                        geoChoose('del', '', jQuery(this).data('id'), _type, event);
                    });
                });    
            })
            
            jQuery('body')
                .delegate('#geodata-picker-wrap .location-list .selected-items .item:not(.on)','mouseover', function(event) {
                    var _this = jQuery(this);
                    var _id = _this.data('id');
                    var _type = _this.parents('.selected-items').data('type');
                    if(_type!='subways'){
                        var _polygon = jQuery('#'+_type+'-svg > polygon[data-id = '+_id+']');
                        _polygon.attr('class','hover polygon');
                    } else{
                        subwayHover(_id,'mouseover');
                    }
                })
                .delegate('#geodata-picker-wrap .location-list .selected-items .item:not(.on)','mouseout', function(event) {
                    var _this = jQuery(this);
                    var _id = _this.data('id');
                    var _type = _this.parents('.selected-items').data('type');
                    if(_type!='subways'){
                        var _polygon = jQuery('#'+_type+'-svg > polygon[data-id = '+_id+']');
                        _polygon.attr('class','polygon');
                    } else{
                        subwayHover(_id,'mouseout');
                    }
                })

            
            jQuery('body')
 
                jQuery('.location-list .selected-items').delegate('.item','click',function(event){
                    if(jQuery(this).hasClass('on')) var _action = 'del';
                    else _action = 'add';
                    geoChoose(_action, jQuery(this).text(), jQuery(this).data('id'), jQuery(this).parents('div').parents('div').data('type'), event);
                })
                function geoChoose(_action, _title, _id, _active_type_click, _event){
                    var _selected_titles_wrap = jQuery('.form-wrap .selected-items[data-type='+_active_type_click+']');
                    if(_active_type_click == 'subways'){
                        var _span = jQuery('.subways-title-item[data-subway-title-id='+_id+']',jQuery('#subways-title-wrap'));
                        var _circle = jQuery('#subways-svg > circle[data-id='+_id+']');
                        var _class = _circle.attr('class');
                    }
                    if(_action == 'add'){
                        if( _geodata_ids[_active_type_click].length == 0 ) jQuery('#reset-geo').click();
                        if(jQuery.inArray(_id, _geodata_ids[_active_type_click]) == -1){
                            _geodata_ids[_active_type_click].push(_id);
                            jQuery('.empty-list',_selected_titles_wrap).hide();
                            _selected_titles_wrap.append("<div class='item' data-id='"+ _id+"'>"+_title+"</div>");
                             if(_active_type_click == 'subways'){
                                _span.addClass('active');
                                _circle.attr('class',_class+' active');
                             }
                             else {
                                 jQuery('#'+_active_type_click+'-svg').children('polygon[data-id='+_id+']').attr('class','polygon active');
                             }
                        }
                        jQuery('.location-list h5.'+_active_type_click+' ').addClass('active');
                        jQuery('.address-select').addClass('disabled').children('input').attr('disabled', 'disabled');
                        jQuery('.selected-items[data-type='+_active_type_click+'] .item[data-id='+_id+']').addClass('on').removeClass('hover');

                    } else {
                        _geodata_ids[_active_type_click].splice(_geodata_ids[_active_type_click].indexOf(_id), 1);
                         if(_active_type_click == 'subways' && _active_type_click == _active_type){
                             _span.removeClass('active');
                            _circle.attr('class',_class.replace(' active',''));
                         }
                         else {
                             jQuery('#'+_active_type_click+'-svg').children('polygon[data-id='+_id+']').attr('class','polygon');
                         }
                         jQuery('div.item[data-id='+_id+']',_selected_titles_wrap).remove();
                         if(_geodata_ids['district-areas'].length + _geodata_ids['districts'].length + _geodata_ids['subways'].length == 0) jQuery('.address-select').removeClass('disabled').children('input').attr('disabled', false);
                         if(_geodata_ids[_active_type_click].length == 0) jQuery('.location-list h5.'+_active_type_click+' ').removeClass('active');
                         jQuery('.selected-items[data-type='+_active_type_click+'] .item[data-id='+_id+']').removeClass('on');
                    }
                    jQuery('input#'+_active_type_click).val(_geodata_ids[_active_type_click].join(','));
                    var _filter = jQuery('#geodata-picker-wrap .filter');
                    jQuery('span', _filter).each(function(){
                        geodataInformer(jQuery(this).data('type'), _geodata_ids[jQuery(this).data('type')].length);
                    })

                    //leftsidebar
                    if(jQuery('#left-column #estate-search').length > 0 && ( typeof _event == "undefined" || ( _event.screenX && _event.screenX != 0 && _event.screenY && _event.screenY != 0) ) ) {
                        var _sidebar_wrap = jQuery('#left-column #estate-search');
                        jQuery('.' + _active_type_click + ' [data-id=' + _id + ']').click();
                    }

                    geodataInformer(_active_type_click, _geodata_ids[_active_type_click].length);
                }        
                function districtMark(){
                    if(_geodata_ids[_active_type]!==undefined && _geodata_ids[_active_type].length>0)
                    {
                        for(i=0; i < _geodata_ids[_active_type].length; i++){
                           if(_active_type == 'subways'){
                                jQuery('.subways-title-item[data-subway-title-id='+_geodata_ids[_active_type][i]+']',jQuery('#subways-title-wrap')).addClass('active');
                                jQuery('#subways-svg > circle[data-id='+_geodata_ids[_active_type][i]+']').attr('class',jQuery('#subways-svg > circle[data-id='+_geodata_ids[_active_type][i]+']').attr('class')+' active');
                           } else {
                               jQuery('#'+_active_type+'-svg').children('polygon[data-id='+_geodata_ids[_active_type][i]+']').attr('class','polygon active');
                           }
                        }    
                        geodataInformer(_active_type, _geodata_ids[_active_type].length);
                    }
                }

            //МЕТРО
            //hover над иконкой метро
            jQuery('body').delegate('#subways-svg > circle', 'mouseover',function(){
                subwayHover(jQuery(this).data('id'),'mouseover');
            }).delegate('#subways-svg > circle', 'mouseout',function(){
                subwayHover(jQuery(this).data('id'),'mouseout');
            })
            jQuery('body').delegate('#subways-title-wrap > .subways-title-item','mouseover',function(){
                subwayHover(jQuery(this).data('subway-title-id'),'mouseover');
            }).delegate('#subways-title-wrap > .subways-title-item', 'mouseout',function(){
                subwayHover(jQuery(this).data('subway-title-id'),'mouseout');
            })  

            
            //выбор линии метро
            jQuery('body').delegate('#subways-lines span', 'click', function(){
                if(jQuery(this).hasClass('on')) {
                    var _action = 'del';
                    jQuery(this).removeClass('on');
                } else {
                    _action = 'add';
                    jQuery(this).addClass('on');
                }
                jQuery('#subways-title-wrap .subways-title-item[data-line='+jQuery(this).data('id')+']').each(function(){
                    var _this = jQuery(this);
                    geoChoose(_action, _this.text(), _this.data('subway-title-id'), 'subways');    
                })
            })
            //клик по названию / иконке
            jQuery('body').delegate('#subways-title-wrap > .subways-title-item','click',function(){
                var _this = jQuery(this);
                var _action = 'add';
                if(_this.hasClass('active')) _action = 'del';
                geoChoose(_action, _this.text(), _this.data('subway-title-id'), _active_type);
            })
            jQuery('body').delegate('#subways-svg > circle', 'click',function(){
                var _this = jQuery(this);
                var _action = 'add';
                var _class = _this.attr('class');
                if(_class.indexOf('active') > 0) _action = 'del';
                geoChoose(_action, jQuery('.subways-title-item[data-subway-title-id='+_this.data('id')+']',jQuery('#subways-title-wrap')).text(), _this.data('id'), _active_type);
            })   

            
            // стоимость в КП
            jQuery('.estate-complex.cottage .select-object_type', init_selector).on('change', function(){
                if(parseInt(jQuery(this).children('input').val()) > 0) jQuery('.price-selector').removeClass('inactive');
                else jQuery('.price-selector').addClass('inactive');
                
            })
            
            
            if(jQuery('.catalog-item .object-types-list.links.simple-view').length > 0){
                var _links_wrap = jQuery('.catalog-item .object-types-list.links.simple-view');
                jQuery('.tab .expand', _links_wrap).on('click', function(){
                    jQuery(this).hide().parents('.tab').css({'max-height' : '100%'});
                })
            }  
            
            /*
            jQuery('.digit', init_selector).on('keyup', function(e){
                if( ! ( e.keyCode >= 48 && e.keyCode <=57 || e.keyCode >= 96 && e.keyCode <=105 || e.keyCode == 46 || e.keyCode == 8 )  ) return false;
                var _this = jQuery(this);
                var _val = _this.val().replace(/ /g,"");
                var _new_val = (_val.replace(/\D/g,"")).replace(/(\d)(?=(\d{3})+([^\d]|$))/g,"$1 ");
                _this.val( _new_val );
                
                n = getCursorPos(_this);
                i = _val;
                
                setCursorPos(_this,n)
                
                
                var _new_length = _new_val.length - _val.length;
                
                n = n + (_new_val.substring(0,n)).split(" ").length - 1;
                                
                setCursorPos(_this, n)
                
            })
            */
        }

        var getCursorPos = function(el){
            var o,a,e,n,i=el[0];
            return i.selectionStart?n=i.selectionStart:document.selection?(i.focus(),o=document.selection.createRange(),null===o||"undefined"==typeof o?n=0:(a=i.createTextRange(),e=a.duplicate(),a.moveToBookmark(o.getBookmark()),e.setEndPoint("EndToStart",a),n=e.text.length)):n=0,n
        }
        var setCursorPos = function(el,o){
            var a,e=el[0];
            e.createTextRange ? (a=e.createTextRange(),a.collapse(!0),a.moveEnd("character",o),a.moveStart("character",o),a.select()) : e.setSelectionRange&&e.setSelectionRange(o,o)
        }              
    
        
        /* сбор параметров формы */
        var search_result = function(){
            getSelectedParams();
            o.estate_url = jQuery('.list-data li[data-value=' + o.selected_value + ']', init_selector).data('url');
            
            var _form = jQuery(jQuery(".middle-panel .tab.active").length > 0 ? jQuery(".middle-panel .tab.active") : jQuery('.row', init_selector));
            
            _estate_type = _form.data('estate-type');
            var _deal_type = jQuery('#estate-deal-type', _form).length > 0 ? jQuery('#estate-deal-type', _form).val() : _form.data('deal');
            var params = [];
            var names = checkbox_params = []               

            init_selector.find(o.input_list).each(function(){
                var _this = jQuery(this);
                var _type = _this.attr('type');
                _name = _this.attr('name');  
                if( typeof _name == "string" && _name != 'extend_search' ) { 
                    
                    if(_type == 'checkbox'){
                        if(_this.parent().hasClass('on')) {
                            var _checkbox_name = _name.split(':');
                            if( typeof _checkbox_name[1]  == "undefined") {
                                params.push(_name + '=1');
                            }
                            else {
                                if(typeof checkbox_params[_checkbox_name[0]] == "undefined") checkbox_params[_checkbox_name[0]] = [];
                                checkbox_params[_checkbox_name[0]].push(_checkbox_name[1]); 
                            }
                        }
                    } else {
                        _value = _type == 'radio' ? jQuery('input[name=' + _name + ']:checked', init_selector).val() : _this.attr('value');
                         if(parseInt(_value) > 0) {
                             params.push(_name + '=' + _value.replace(/ /g,"") );
                         }
                    }
                        
                    
                }
            });  

            var _keys = Object.keys(checkbox_params);
            if(_keys.length > 0){
                for(i=0; i<_keys.length; i++){
                    params.push( _keys[0] + '=' + checkbox_params[_keys[0]] );
                }
            }
            if(o.ajax_count == true) params.push('ajax_count=1');
            //гео данные
            if(jQuery("#subways").length > 0 && jQuery("#districts").length > 0 && jQuery("#district-areas").length > 0){
                _subways = jQuery("#subways").val();
                _districts = jQuery("#districts").val();
                _district_areas = jQuery("#district-areas").val();
                if(_subways!='' && _subways!="undefined") params.push('subways=' + _subways)
                if(_district_areas!='' && _district_areas!="undefined") params.push('district_areas=' + _district_areas)
                if(_districts!='' && _districts!="undefined") params.push('districts=' + _districts)
            }
                            
            var _catalog = jQuery('#fast-search-form').attr('action');
            if( typeof o.estate_url === "object" ) return false;
            o.estate_url = _catalog + o.estate_url + (params.length > 0 ? ( o.estate_url.indexOf('?')>0 ? '&' : '?' ) + params.join('&') : '');

            // конструируем запрос
            if(o.ajax_count == true){
                jQuery('.ajax-count .button').addClass('preload');
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: true,
                    url: o.estate_url,
                    success: function(msg){
                        if(msg.ok) {
                            jQuery('.ajax-count .button').removeClass('preload');
                            jQuery('.ajax-count .button i', init_selector).text(msg.count);
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
                        return false;
                    }
                });  
                
                jQuery('.ajax-count .button', init_selector).on('click', function(){
                    init_selector.submit();
                });
            }
            else if(typeof o.map_search=='boolean' && o.map_search == true) { // поиск по карте
                getBounds();
                map_params = []
                map_params.push('top_left_lat='+_top_left_lat);
                map_params.push('top_left_lng='+_top_left_lng);
                map_params.push('bottom_right_lat='+_bottom_right_lat);
                map_params.push('bottom_right_lng='+_bottom_right_lng);
                pendingMapSearchPoints(o.estate_url + ( o.estate_url.indexOf('?')>0 ? '&' : (params.length > 0 ? '&' : '?' )) + map_params.join('&'), {ajax: true, map: true} );
                return false;
            } else{
                if(o.ajax_search){
                    getPendingContent('#ajax-search-results', o.estate_url);
                } else window.location.href = o.estate_url;
            } 
            return false;
        } 
        
        var onlyUnique = function (value, index, self) { 
            return self.indexOf(value) === index;
        }   
        var getSelectedParams = function(){
            o.selected_el = jQuery('.list-data li[data-value=' + jQuery("#estate-object-type").val() + ']', jQuery('.select-object-type'));
            
            o.deal_type = jQuery('[name=deal_type]', init_selector).val();
            o.deal_type = o.deal_type == 2 ? 'sell' : 'rent';

            o.estate_type = o.selected_el.data('type');
            o.id_object = o.selected_el.data('id');
            o.selected_value = (o.id_object > 0 ? o.id_object + '-' : '' ) + o.estate_type + '-' + o.deal_type;;
            
        }
        /* список автокомплета */
        var showPopupList = function(_el,_list, _type){
            var _wrapper = _el.parent();
            var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
            for(var i in _list){                   
                str += '<li data-id="'+_list[i].id+'" title="'+_list[i].title+(typeof _list[i].additional_title=='string'?_list[i].additional_title:'')+'">'+_list[i].title+(typeof _list[i].additional_title=='string'?'<span>'+_list[i].additional_title+'</span>':'')+'</li>';
            }
            str += '</ul>';
            hidePopupList(_wrapper);
            _wrapper.append(jQuery(str));
            jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
                var _parent_box = jQuery(this).closest('.typewatch_popup_list').parent();
                var _el_class = _el.attr('name');
                jQuery('input[name='+_el_class+']').next('.clear-input').removeClass('hidden').next('input').val( jQuery(this).data('id') );
                jQuery('input[name='+_el_class+']').val(jQuery(this).text()).attr('title',jQuery(this).text());
                hidePopupList(_parent_box);
            });
            
        }
                        
        var hidePopupList = function (_wrapper){
            if(!_wrapper) _wrapper = jQuery(document);
            jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
            jQuery(".typewatch_popup_list", _wrapper).remove();
        }        
                 
        //информер общего количества выбранных объектов
        var geodataInformer = function (_type, _length){
            var _el = jQuery('#geodata-picker-wrap .filter span[data-type='+_type+'] i');
            _el.text(_geodata_ids[_type].length);

            if( _length > 0 ){
                jQuery('.list-picker.location .' + _type).addClass('active').siblings('a').removeClass('active');
                jQuery('.list-picker.location .' + _type + ' i').text(_length);
            } else jQuery('.list-picker.location .empty').addClass('active').siblings('a').removeClass('active');
        }
            
        var subwayHover = function (_active, estate_url ){
            var _circle = jQuery('#subways-svg > circle[data-id='+_active+']');
            var _span = jQuery('.subways-title-item[data-subway-title-id='+_active+']',jQuery('#subways-title-wrap'));
            var _class = _circle.attr('class');
            if( estate_url == 'mouseover' ){
                _circle.attr('class',_class+' hover') ;
                _span.addClass('hover') ; 
                jQuery('#geodata-picker-wrap .location-list div[data-type=subways] .item[data-id='+_active+']').addClass('hover');
            }  else {
               _circle.attr('class',_class.replace(' hover',''));
               _span.removeClass('hover') ;
               jQuery('#geodata-picker-wrap .location-list div[data-type=subways] .item[data-id='+_active+']').removeClass('hover');
            }
        }

        var mapInit = function(){
            ymaps.ready(function () {
                markers = [];
                jQuery('#map-search-results').each(function(){
                    var _element = jQuery(this);
                    _event_type = '';
                     _placemark_statement = 'closed';
                    var _zoom = jQuery(this).hasClass('cottedzhnye_poselki') ? 8 : 11;
                    YMSR = new ymaps.Map(_element.attr('id'), {
                        center: [59.937538, 30.309452],
                        zoom: _zoom,
                        controls: []
                    });
                    _bounds_listen = YMSR.getBounds();
                    // пользовательский макет ползунка масштаба.
                    ZoomLayout = ymaps.templateLayoutFactory.createClass('<div class="custom-controls zoom-control transition"><div class="in" data-icon="add"></div><div class="out" data-icon="remove"></div></div>', {

                        // Переопределяем методы макета, чтобы выполнять дополнительные действия
                        // при построении и очистке макета.
                        build: function () {
                            // Вызываем родительский метод build.
                            ZoomLayout.superclass.build.call(this);

                            // Привязываем функции-обработчики к контексту и сохраняем ссылки
                            // на них, чтобы потом отписаться от событий.
                            this.zoomInCallback = ymaps.util.bind(this.zoomIn, this);
                            this.zoomOutCallback = ymaps.util.bind(this.zoomOut, this);

                            // Начинаем слушать клики на кнопках макета.
                            $('.zoom-control .in').bind('click', this.zoomInCallback);
                            $('.zoom-control .out').bind('click', this.zoomOutCallback);
                        },

                        clear: function () {
                            // Снимаем обработчики кликов.
                            $('.zoom-control .in').unbind('click', this.zoomInCallback);
                            $('.zoom-control .out').unbind('click', this.zoomOutCallback);

                            // Вызываем родительский метод clear.
                            ZoomLayout.superclass.clear.call(this);
                        },

                        zoomIn: function () {
                            var map = this.getData().control.getMap();
                            // Генерируем событие, в ответ на которое
                            // элемент управления изменит коэффициент масштабирования карты.
                            this.events.fire('zoomchange', {
                                oldZoom: map.getZoom(),
                                newZoom: map.getZoom() + 1
                            });
                        },

                        zoomOut: function () {
                            var map = this.getData().control.getMap();
                            this.events.fire('zoomchange', {
                                oldZoom: map.getZoom(),
                                newZoom: map.getZoom() - 1
                            });
                        }
                    }),
                      
                    zoomControl = new ymaps.control.ZoomControl({
                        options: {
                            layout: ZoomLayout
                        }
                    });

                    YMSR.controls.add(zoomControl, {
                        float: 'none', 
                        position: { 
                            top: 200, 
                            left: 20
                        }
                    })
                    
                    markers = new ymaps.GeoObjectCollection();
                    
                    //изменение зума    
                    YMSR.events
                      .add(['balloonopen','boundschange','click','sizechange','mousedown','mouseup','wheel'],function (e) { 
                          if(_event_type == '') _event_type = e.get('type');
                          else {
                              if(_placemark_statement == 'closed'){
                                  _new_event_type = e.get('type');
                                  if(
                                    _event_type == 'wheel' || 
                                    _event_type == 'sizechange' || 
                                    ( _event_type == 'mousedown' && _new_event_type == 'boundschange' ) 
                                  ) mapChange();
                                  _event_type = _new_event_type;
                              }
                          }
                      });
                })

                // Создание макета балуна на основе Twitter Bootstrap.
                MyBalloonLayout = ymaps.templateLayoutFactory.createClass(
                    '<div class="search-results-balloon br3">' +
                        '<a class="close" href="#" data-icon="clear"></a>' +
                        '<div class="arrow"></div>' +
                        '<div class="balloon-inner br3">' +
                        '$[[options.contentLayout observeSize]]' +
                        '</div>' +
                        '</div>', {
                        /**
                         * Строит экземпляр макета на основе шаблона и добавляет его в родительский HTML-элемент.
                         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/layout.templateBased.Base.xml#build
                         * @function
                         * @name build
                         */
                        build: function () {
                            this.constructor.superclass.build.call(this);
                           
                            this._$element = $('.search-results-balloon', this.getParentElement());

                            this.applyElementOffset();

                            this._$element.find('.close')
                                .on('click', $.proxy(this.onCloseClick, this));
                        },

                        /**
                         * Удаляет содержимое макета из DOM.
                         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/layout.templateBased.Base.xml#clear
                         * @function
                         * @name clear
                         */
                        clear: function () {
                            this._$element.find('.close')
                                .off('click');

                            this.constructor.superclass.clear.call(this);
                        },

                        /**
                         * Метод будет вызван системой шаблонов АПИ при изменении размеров вложенного макета.
                         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/IBalloonLayout.xml#event-userclose
                         * @function
                         * @name onSublayoutSizeChange
                         */
                        onSublayoutSizeChange: function () {
                            MyBalloonLayout.superclass.onSublayoutSizeChange.apply(this, arguments);

                            if(!this._isElement(this._$element)) {
                                return;
                            }

                            this.applyElementOffset();

                            this.events.fire('shapechange');
                        },

                        /**
                         * Сдвигаем балун, чтобы "хвостик" указывал на точку привязки.
                         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/IBalloonLayout.xml#event-userclose
                         * @function
                         * @name applyElementOffset
                         */
                        applyElementOffset: function () {
                            this._$element.css({
                                left: -(this._$element[0].offsetWidth / 2),
                                top: -(this._$element[0].offsetHeight + this._$element.find('.arrow')[0].offsetHeight)
                            });
                        },

                        /**
                         * Закрывает балун при клике на крестик, кидая событие "userclose" на макете.
                         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/IBalloonLayout.xml#event-userclose
                         * @function
                         * @name onCloseClick
                         */
                        onCloseClick: function (e) {
                            e.preventDefault();
                            this.events.fire('userclose'); 
                        },

                        /**
                         * Используется для автопозиционирования (balloonAutoPan).
                         * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/ILayout.xml#getClientBounds
                         * @function
                         * @name getClientBounds
                         * @returns {Number[][]} Координаты левого верхнего и правого нижнего углов шаблона относительно точки привязки.
                         */
                        getShape: function () {
                            if(!this._isElement(this._$element)) {
                                return MyBalloonLayout.superclass.getShape.call(this);
                            }

                            var position = this._$element.position();

                            return new ymaps.shape.Rectangle(new ymaps.geometry.pixel.Rectangle([
                                [position.left, position.top], [
                                    position.left + this._$element[0].offsetWidth,
                                    position.top + this._$element[0].offsetHeight + this._$element.find('.arrow')[0].offsetHeight
                                ]
                            ]));
                        },

                        /**
                         * Проверяем наличие элемента (в ИЕ и Опере его еще может не быть).
                         * @function
                         * @private
                         * @name _isElement
                         * @param {jQuery} [element] Элемент.
                         * @returns {Boolean} Флаг наличия.
                         */
                        _isElement: function (element) {
                            return element && element[0] && element.find('.arrow')[0];
                        }
                    }),

            // Создание вложенного макета содержимого балуна.
                MyBalloonContentLayout = ymaps.templateLayoutFactory.createClass(
                    '<div class="search-results-balloon-content br3">$[properties.balloonContent]</div>'
                );

                myBalloonContentBodyLayout = ymaps.templateLayoutFactory.createClass(
                    '<div>$[properties.body]</div>'
                );
                
                    
            })
        }

        var pendingMapSearchPoints = function(_url, _params){
            ymaps.ready(function () {
                markers.removeAll(); 
                markers = new ymaps.GeoObjectCollection(null);
                
                jQuery('#total-objects').addClass('waiting').html('');
                
                jQuery.ajax({     
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: _url, data: _params,
                    success: function(msg){
                        if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                            jQuery('#map-search-results').removeClass('waiting');
                            if( msg.total > 0 ){
                                for(i=0; i< msg.points.length; i++){
                                    //добавление метки объекта
                                    var placemark = new ymaps.Placemark([ msg.points[i]['lat'], msg.points[i]['lng'] ], {
                                       hintContent: msg.points[i]['title'],
                                       name : msg.points[i]['link'], 
                                       id : msg.points[i]['id'], 
                                       iconContent: msg.points[i]['total_objects'] > 1 ? msg.points[i]['total_objects'] : ''
                                    }, {
                                        // Запретим замену обычного балуна на балун-панель.
                                        balloonPanelMaxMapArea: 0,
                                        iconLayout: 'default#imageWithContent',
                                        iconImageHref: o.visited_ids.indexOf( msg.points[i]['id'] ) >=0 ? '/img/layout/bsn-map-tag-seen.svg' : '/img/layout/bsn-map-tag.svg',
                                        iconImageSize: [32, 44],
                                        iconImageOffset: [-16, -40],
                                        iconShadow: true,
                                        iconShadowImageHref: '/img/layout/bsn-map-tag-shadow.png',
                                        iconShadowImageSize: [21, 23],
                                        iconShadowImageOffset: [-1, -20], 
                                        
                                        balloonLayout: MyBalloonLayout,
                                        balloonContentBodyLayout: myBalloonContentBodyLayout,
                                        balloonContentLayout: MyBalloonContentLayout,
                                        balloonPanelMaxMapArea: 0,
                                        // Заставляем балун открываться даже если в нем нет содержимого.
                                        openEmptyBalloon: true
                                    });
                                    placemark.events.add('click', function (e) {
                                        var thisPlacemark = e.get('target');
                                        thisPlacemark.options.set('iconImageHref', '/img/layout/bsn-map-tag-seen.svg');
                                        var _name = thisPlacemark.properties.get('id');
                                        o.visited_ids.push( _name );
                                        console.log( o.visited_ids)
                                    });
                                    if( typeof msg.points[i]['html'] == 'string'){
                                        console.log( 'nsg - html');
                                        placemark.properties.set('balloonContent', msg.points[i]['html']);
                                        placemark.events.add('balloonopen', function (e) {
                                            _placemark_statement = 'open';
                                            jQuery('.search-results-balloon .arrow').show();
                                            jQuery('.lazy').lazy({
                                                afterLoad: function(element) {
                                                    element.removeClass('lazy');
                                                }
                                            })  

                                        })
                                    } else {
                                        console.log( 'nsg - non html');
                                        // Обрабатываем событие открытия балуна на геообъекте:
                                        // начинаем загрузку данных, затем обновляем его содержимое.
                                        placemark.events.add('balloonopen', function (e) {
                                            var geoObject = e.get('target');
                                            jQuery.ajax({     
                                                type: "POST", async: true,
                                                dataType: 'json', cache: false,
                                                url: geoObject.properties.get('name') + '&map_group_id=1',
                                                success: function(msg){
                                                    
                                                    geoObject.properties.set('balloonContent', msg.html);
                                                    var _el_height = parseInt( jQuery('.search-results-balloon-content .map-wrapper').height() ) + 40;
                                                    jQuery('.search-results-balloon-content, .search-results-balloon').css({'height' :  _el_height + 'px'})
                                                    jQuery('.search-results-balloon').css({'top' : '-' + (_el_height + 10 ) + 'px'})
                                                    jQuery('.search-results-balloon .arrow').show();
                                                    jQuery('.lazy').lazy({
                                                        afterLoad: function(element) {
                                                            element.removeClass('lazy');
                                                        }
                                                    })  
                                                }
                                            })
                                            _placemark_statement = 'open';
                                          
                                        });  
                                    }
                                    placemark.events.add('balloonclose', function (e) {
                                        _placemark_statement = 'closed';
                                    })
                                    markers.add(placemark);
                                  
                                }
                            }
                        } 
                        YMSR.geoObjects.add(markers);
                        jQuery('#total-objects').removeClass('waiting').html('Найдено '+msg.total+' '+makeSuffix(msg.total,['объект','объекта','объектов']));
                    }
                })
            })    
        }

        var getBounds = function(){

            switch(YMSR.getZoom()){
                case 10: _k = 8; break;
                case 11: _k = 6; break;
                case 12: _k = 4; break;
                case 13: _k = 2; break;
                case 14: _k = 1; break;
                case 15: _k = 0.5; break;
                case 16: _k = 0.05; break;
                case 17: _k = 0.005; break;
                case 18: _k = 0.001; break;
                default: _k = 1; break;
                    
            }
            var _bounds = YMSR.getBounds();
            _top_left_lat  = _bounds[1][0] - ( 0.001 * _k );
            _top_left_lng  = _bounds[0][1] + ( 0.002 * _k );
            _bottom_right_lat    = _bounds[0][0] + ( 0.001 * _k );
            _bottom_right_lng    = _bounds[1][1] - ( 0.002 * _k );

            // создаем географические границы прямоугольника
            var bounds = [[_top_left_lat, _top_left_lng], [_bottom_right_lat, _bottom_right_lng]];
        }

        var mapChange = function(){
            _bounds_listen_new = YMSR.getBounds();
            _bounds_difference_lat = _bounds_listen_new[0][0] > _bounds_listen[0][0] ? _bounds_listen[0][0] / _bounds_listen_new[0][0] : _bounds_listen_new[0][0] / _bounds_listen[0][0];
            _bounds_difference_lng = _bounds_listen_new[0][1] > _bounds_listen[0][1] ? _bounds_listen[0][1] / _bounds_listen_new[0][1] : _bounds_listen_new[0][1] / _bounds_listen[0][1];
            if( ( 1 - _bounds_difference_lat) * 1000 > 0.27 ||  ( 1 - _bounds_difference_lng) * 1000 > 0.27) search_result();
            _bounds_listen = _bounds_listen_new;
        }                
        return this.each(function(){
            init_selector = $(this);
            start();   
        });
    }
})(window, document, jQuery);   