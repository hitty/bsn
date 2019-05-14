/**
* Business centers offices $ plugin
* 
*/
if($)(function(window, document, $, undefined){
    $.fn.offices = function(opts) {
        var defaults = {
            corp_select_element     : null,                     /* элемент - выбор корпуса */            
            level_select_element    : null,                     /* элемент - выбор этажа */
            offices_element         : null,                     /* элемент - список офисов */
            info_element            : null,                     /* элемент - информация об офисе */
            plans_element           : null,                                 
            members_page            : null,                     /* страница управления */
            ids                     : [],                                   
            corp                    : null,                       /* массив значений выбранных элементов */
            onExit                  : function(selected_data){} /* функция, предваряющая закрытие function(item_id){} */
        };
        var options = $.extend(defaults, opts || {});
        var active_element = null;                          /* Элемент, к которому назначен вызов ajaxfilter */
        
        /* функция стартовой инициализации */
        var start = function(){
            jQuery(options.level_select_element).on('change', function(){
                makeQuery(false);
            })
            if(jQuery(options.corp_select_element).length > 0 && !options.members_page){
                jQuery(options.corp_select_element).on('change', function(){
                    options.corp = jQuery('input', jQuery(options.corp_select_element)).val();
                    jQuery('.list-data li[data-corp="'+options.corp+'"]', jQuery(options.level_select_element)).eq(0).click();
                    makeQuery(false);
                })
            }
            makeQuery(false);
            if(options.members_page) membersInit();
        };
        
        //построение запроса и вызов результатов
        var makeQuery = function(){
            if(jQuery(options.corp_select_element).length > 0){
                options.corp = jQuery('input', jQuery(options.corp_select_element)).val();
                jQuery('.list-data li[data-corp!="'+options.corp+'"]', jQuery(options.level_select_element)).removeClass('active').addClass('inactive').siblings('li[data-corp='+options.corp+']').addClass('active').removeClass('inactive');
            }
            var _id = active_element.data('id');
            var _id_level = jQuery(options.level_select_element).children('input').val();
            //получение списка офисов
            getOfficesList(_id, _id_level, null, null, options.corp);
            //получение списка планов
            getPlansList(_id, _id_level, null, options.corp);
        }
        //получение списка офисов
        var getOfficesList = function(_id, _id_level, _sortby, _ids, _corp){
            $.ajax({
                type: "POST", async: true, dataType: 'json', cache: false, url: '/business_centers/offices/list/',
                data: {ajax: true, id: _id, id_level: _id_level, sortby: _sortby, members_page: options.members_page, ids: _ids, corp: _corp},
                success: function(msg){
                    jQuery(options.offices_element).html(msg.html);
                    jQuery('.offices .list .item').each(function(){
                        var _this = jQuery(this);
                        var _id = _this.data('id');
                        //hover элементов
                        _this.on('mouseover', function(event) {
                            var _svg = jQuery('.plans svg .item[data-id='+_id+']');
                            if(_svg.length > 0){
                                var _class = _svg.attr('class');
                                 _svg.attr('class', _class+' hovered')   ;
                            }
                        }
                        ).on('mouseout', function(event) {
                            var _svg = jQuery('.plans svg .item[data-id='+_id+']');
                            if(_svg.length > 0){
                                var _class = _svg.attr('class');
                                _svg.attr('class', _class.replace(' hovered',''));
                            }
                        })
                        //выбор элементов
                        _this.on('click', function(event) {
                            var _this = jQuery(this);
                            var _id = _this.data('id');
                            
                            //публичная страница
                            if(!options.members_page) {
                                _this.siblings('.item').attr('class', 'item');
                                _this.attr('class', 'item active');
                                jQuery('.plans svg .item.active').attr('class', 'item');
                                jQuery('.plans svg .item[data-id='+_id+']').attr('class', 'item active');
                                options.ids = [_id];
                            } else {
                            //страница редактирования площадей
                                if(_this.hasClass('active')) {
                                    _this.attr('class', 'item');
                                    options.ids.splice( options.ids.indexOf(_id), 1 );
                                    jQuery('.plans svg .item[data-id='+_id+']').attr('class', 'item');
                                } else {
                                    options.ids.push(_id);
                                    _this.attr('class', 'item active');
                                    jQuery('.plans svg .item[data-id='+_id+']').attr('class', 'item active');
                                }
                            }
                            //информация об офисе
                            officeInfo(_id, options.ids);
                        })
                    })
                    //сортировка
                    jQuery('.offices .list .header span').on('click', function(){
                        getOfficesList(_id, _id_level, jQuery(this).data('sort'), options.ids, options.corp)        
                        if(options.members_page){
                            getPlansList(_id, _id_level, jQuery(this).data('sort'), options.corp)        
                            officeInfo(_id, options.ids)        
                        }
                        return false;
                    })
                    if(_sortby != null) jQuery('.plans svg .item.active').click();
                    //количество свободных офисов
                    if(jQuery('#business_centers_levels').val() != 'all'){
                        var _free_offices = msg.free_offices + ' ' + makeSuffix(msg.free_offices, ['офис','офиса','офисов']) + ' ' + makeSuffix(msg.free_offices, ['свободен','свободно','свободно']);
                        jQuery('.list-data li.selected i,.pick i', jQuery(options.level_select_element)).text(_free_offices)
                        jQuery('.pick', jQuery(options.level_select_element)).attr('title', _free_offices);
                    }
                }
            });
            
        }
        //получение списка офисов
        var getPlansList = function(_id, _id_level, _sortby, _corp){

            //получение планов
            $.ajax({
                type: "POST", async: true, dataType: 'json', cache: false, url: '/business_centers/offices/plans/',
                data: {ajax: true, id: _id, id_level: _id_level, members_page: options.members_page, corp: _corp},
                success: function(msg){
                    jQuery(options.plans_element).html(msg.html);

                    jQuery('.plans svg .item').each(function(){
                        var _this = jQuery(this);
                        var _id = _this.data('id');
                        var _class = _this.attr('class');
                        if(!_class.match(/rented/) && !_class.match(/storeroom/)){
                            _this.on('mouseover', function(event) {
                                var _class = _this.attr('class');
                                if(!_class.match(/active/)){
                                    var _item = jQuery('.offices .list .item[data-id='+_id+']');
                                    _item.attr('class', _class+' hovered');
                                    //if(!options.members_page) jQuery(".offices-box .offices .list-wrap .list .scrolled").slimScroll({ scrollTo: _item.offset().top - jQuery('.offices-box .offices .list').offset().top, animate: true });
                                }
                            }
                            ).on('mouseout', function(event) {
                                var _class = _this.attr('class');
                                if(!_class.match(/active/)){
                                    var _item = jQuery('.offices .list .item[data-id='+_id+']');
                                    var _class = _item.attr('class');
                                    _item.attr('class', _class.replace(' hovered',''));
                                }
                            })
                            _this.on('click', function(event) {
                                var _this = jQuery(this);
                                var _id = _this.data('id');
                                
                                //публичная страница
                                if(!options.members_page) {
                                    jQuery('.plans svg .item.active').attr('class', 'item');
                                    _this.attr('class', 'item active');
                                    jQuery('.offices .list .item[data-id='+_id+']').attr('class', 'item active').siblings('.item').attr('class', 'item');
                                    options.ids = [_id];
                                } else {
                                //страница редактирования площадей
                                    var _class = _this.attr('class');
                                    if(_class.match(/active/)) {
                                        _this.attr('class', _class.replace(' active',''));
                                        options.ids.splice( options.ids.indexOf(_id), 1 );
                                        jQuery('.offices .list .item[data-id='+_id+']').attr('class', 'item')
                                    } else {
                                        options.ids.push(_id);
                                        _this.attr('class', _class + ' active');
                                        jQuery('.offices .list .item[data-id='+_id+']').attr('class', 'item active')
                                    }
                                }
                                //информация об офисе
                                officeInfo(_id, options.ids);

                            })
                            
                        }
                    })
                    if(!options.members_page){
                        if(_sortby == null){
                            setTimeout(function(){
                                jQuery('.offices .list .item:first-child').click();    
                            }, 200)
                        }
                    }
                    
                                        
                }
            });
        }
        var officeInfo = function(_id, _ids){
            $.ajax({
                type: "POST", async: true, dataType: 'json', cache: false, url: '/business_centers/offices/info/',
                data: {ajax: true, id: _id, ids: options.ids, members_page: options.members_page},
                success: function(msg){
                    jQuery(options.info_element).html(msg.html);
                }
            })
        }
        /* управление офисами в ЛК */
        var membersInit = function(){
            var _template = jQuery('<div id="background-shadow-expanded">'
                          +'<div id="background-shadow-expanded-wrapper"></div>'
                          +'<div id="background-shadow-expanded-content"></div>'
                          +'</div>');
            //переключатель типов отображения
            jQuery('.view-type span').on('click', function(){
                var _this = jQuery(this);
                var _class = _this.attr('class');
                var _replace_class = _class == 'list' ? 'plans' : 'list';
                _this.addClass('active').siblings('span').removeClass('active');
                jQuery(options.level_select_element).removeClass(_replace_class).addClass(_class);
                jQuery('.offices').removeClass(_replace_class).addClass(_class);
                if( jQuery('#business_centers_levels').val() == 'all'){
                    if(options.corp) jQuery('.list-data li[data-corp="' + options.corp + '"]', jQuery(options.level_select_element)).eq(_class == 'plans' ? 1 : 0).click();
                    else jQuery('.list-data li', jQuery(options.level_select_element)).eq(_class == 'plans' ? 1 : 0).click();
                    options.ids = [];
                    jQuery('.offices .list .item').attr('class', 'item');
                }
            }) 
            if(jQuery(options.corp_select_element).length > 0){
                jQuery(options.corp_select_element).on('change', function(){
                    options.corp = jQuery('input', jQuery(options.corp_select_element)).val();
                    jQuery('.list-data li[data-corp="'+options.corp+'"]', jQuery(options.level_select_element)).eq(jQuery('.view-type span.active').hasClass('plans') ? 1 : 0).click();
                    makeQuery(false);
                })
            }

            //управление фильтром справа
            jQuery(document).on('click', '.offices-box .offices .info-wrap .filter li', function(){
                _class = jQuery(this).attr('class');
                if(_class == 'change'){
                    //заполнение полей если выбран 1 арендатор
                    if(options.ids.length == 1){
                        var _el = jQuery('.content .offices-box .offices .list .item.active');
                        jQuery('#date_start').attr('value', _el.attr('data-date-start'))
                        jQuery('#date_end').attr('value',_el.attr('data-date-end'))
                        jQuery('#change_renters').attr('value',_el.attr('data-id-rent'))
                    } else {
                        jQuery('#change_renters,#date_end,#date_start').attr('value', '');
                    }
                    jQuery('body').append(_template);
                    jQuery('#background-shadow-expanded').fadeIn(100);                   
                    jQuery('#background-shadow-expanded-content').html(jQuery('#change-renter-wrap').html());
                    jQuery('#background-shadow-expanded-content #change-renter').addClass('active');
                    jQuery('#change-renter .form-title span i').html(options.ids.length); 
                    listSelectorInit('');
                    jQuery('.datetimepicker').datetimepicker({
                          timepicker:false,
                          format:'d.m.y'
                    })
                } else if(_class == 'delete'){
                    if(!confirm('Вы уверены, что хотите удалить арендатора')) return false;
                    $.ajax({
                        type: "POST", async: true, dataType: 'json', cache: false, url: '/business_centers/offices/'+_class+'/',
                        data: {ajax: true, ids: options.ids},
                        success: function(msg){
                            jQuery(options.level_select_element).change(); 
                        }
                    })
                } else if(_class == 'commercial'){
                    if(!confirm('Вы действительно хотите сформировать по выбранным офисам коммерческое предложение? Это займет несколько секунд.')) return false;
                    window.location = '/business_centers/offices/'+_class+'/?ids=' + options.ids.join(',');
                }
            })          
            jQuery(document).on('click', '#background-shadow-expanded .closebutton,#background-shadow-expanded #background-shadow-expanded-wrapper', function(){
                jQuery('#background-shadow-expanded').remove();
            })
            //смена арендатора
            jQuery(document).on('click', '#background-shadow-expanded-content .button-container button', function(){
                $.ajax({
                    type: "POST", async: true, dataType: 'json', cache: false, url: '/business_centers/offices/change/',
                    data: {ajax: true, ids: options.ids, id_renter: jQuery('#change_renters', jQuery('#background-shadow-expanded')).val(), date_start: jQuery('#date_start', jQuery('#background-shadow-expanded')).val(), date_end: jQuery('#date_end', jQuery('#background-shadow-expanded')).val()},
                    success: function(msg){
                        jQuery(options.level_select_element).change(); 
                        jQuery('#background-shadow-expanded .closebutton').click();
                    }
                })
            })
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 27: jQuery('#background-shadow-expanded .closebutton').click();
                }
            });             

           jQuery(options.level_select_element).on('change', function(){
               options.ids = [];
               jQuery('.offices-box .offices .info-wrap').html('');
           })
           //фиксированная позиция фильтра при прокрутке
           var _el = jQuery('.offices-box .offices .fixed-wrap');
           var _menu_height = parseInt(jQuery('#userinfo-wrap').height());
           jQuery(window).scroll(function(){
                var _el_top = parseInt(_el.offset().top);        
                var _el_height = parseInt(_el.children('.fixed-wrap').height());        
                var _footer_top = parseInt(jQuery('footer').offset().top);
                var _top = parseInt(jQuery(this).scrollTop());
                if (_top + _menu_height > _el_top) {
                    _el.children('.info-wrap').addClass('fixed').removeClass('fixed-bottom');
                } else if (parseInt(jQuery('.offices.list').height()) > 500 && (_top + _menu_height + _el_height + 120 > _footer_top)) {
                    _el.children('.info-wrap').addClass('fixed-bottom').removeClass('fixed');
                } else  {
                    _el.children('.info-wrap').removeClass('fixed').removeClass('fixed-bottom');
                }   
               
            });                   
        }
        return this.each(function(){
            active_element = $(this);
            start();   
        });
    }
})(window, document, jQuery);