_ajax_search = false;
var _estate_search_included = true;

jQuery(document).ready(function(){
    
    function showPopupList(_el,_list, _type){
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
    
    function hidePopupList(_wrapper){
        if(!_wrapper) _wrapper = jQuery(document);
        jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
        jQuery(".typewatch_popup_list", _wrapper).remove();
    }  
    

    _background_template = '<div id="background-shadow-expanded">'
                        +'<div id="background-shadow-expanded-wrapper"></div>'
                        +'</div>'
                    +'</div>';
    
    /* LOCATION */
    var _active_type = '';
    var _geodata_ids = {'districts':[],'district-areas':[],'subways':[]};
    var _active_tab = '';
    var _offers_wrap = [];
    jQuery(".list-picker.location").on("click", function(){
        var _this = jQuery(this);
        //атрибут выбора вкладки для тегов таргета
        if(jQuery(this).attr('data-active-tab') != undefined) _active_type = jQuery(this).attr('data-active-tab');
        if(_this.hasClass('disabled')) return false;
        var _list = jQuery('#geodata-picker-wrap');
        jQuery('body').append(_background_template);
        jQuery('#background-shadow-expanded').fadeIn(100);
        setTimeout(function(){
            jQuery('#geodata-picker-wrap').fadeIn(100).css({display:'table'});;
        }, 200)
        
        //alert(_active_type);
        if(_active_type=='') _list.children('.filter').children('span').first().click();
        //else if(jQuery('#popup-object-types').length > 0) jQuery('#popup-object-types').siblings('i').text(jQuery('#popup-object-types').val().split(',').length);
        
        //alert(jQuery('#geodata-picker-wrap').children('.filter').children('.' + _active_type + "-picker").length + ' = length');
        if(_active_type.length > 0) jQuery('#geodata-picker-wrap').children('.filter').children('.' + _active_type + "-picker").click();
        
        if(jQuery(this).attr('data-on-exit') == undefined) return false;
    });

    
    //заполнение массива элементами
    jQuery('.location-list > .selected-items', jQuery('#geodata-picker-wrap')).each(function(e){
        var _this = jQuery(this);
        var _type = _this.data('type');
        _this.children('.item.on').each(function(e){
            _geodata_ids[_type].push(jQuery(this).data('id'));
        });
        var _filter = jQuery('#geodata-picker-wrap .filter');
        jQuery('span', _filter).each(function(){
            var _el = jQuery('#geodata-picker-wrap .filter span[data-type='+_type+'] i');
            _el.text(_geodata_ids[_type].length);
        })
        
    });
    
    jQuery(".filter span").on('change', function(e, value){
        var _this = jQuery(this);
        
        if(typeof value != 'undefined') jQuery('input[type="hidden"]',_this).val(value);
        e.preventDefault();
    });
    jQuery(".filter span:not(.object-types-picker)").on('click', function(e){
        jQuery('#geodata-picker-wrap .items-list').removeClass('wided').siblings('.location-list').removeClass('hidden');
        if(jQuery('.items-list .bottom-block').length != 0) jQuery('.items-list .bottom-block .selected-total i').text("0");
        e.preventDefault();
        var _el = jQuery(e.target); //ditrict-picker
        var _selector = _el.parent(); //items-list
        var _items = jQuery('.items-list .items');
        _active_type = _el.attr('data-type');
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
        
    jQuery('body').delegate("#background-shadow-expanded, #geodata-picker-wrap .close-btn",'click',function(event){ 
        event.preventDefault();
        //чистим форму
        jQuery('#geodata-picker-wrap.target-outed .filter').find('input').val("");
        jQuery('.location-list').find('.item.on').click();
        jQuery('#geodata-picker-wrap').children('.items-list').children('.items').html("");
            
        jQuery('#geodata-picker-wrap').fadeOut(100);
        setTimeout(function(){
            jQuery('#background-shadow-expanded').fadeOut(100);
            jQuery(document).scrollTop(jQuery('.targeting-block .blue-h').offset().top - jQuery('.targeting-block .blue-h').height() - 10);
        }, 200)
        return false;
        //jQuery("button", jQuery('#geodata-picker-wrap')).click();
    });
    
    jQuery(document).keyup(function(e) {
            switch(e.keyCode){
                case 27: jQuery("button", jQuery('#geodata-picker-wrap')).click();  break;     // esc
            }   
    });    

    //Сброс гео фильтра
    //заполнение массива элементами
    jQuery('#geodata-picker-wrap').delegate('#reset-geo','click',function(){
        jQuery('.location-list > .selected-items').each(function(e){
            var _this = jQuery(this);
            var _type = _this.data('type');
            _this.children('.item').each(function(e){
                geoChoose('del', '', jQuery(this).data('id'), _type);
            });
        });    
    })
    //РАЙОНЫ ГОРОДА
    jQuery('body').delegate('#districts-svg > polygon, #district-areas-svg  > polygon', 'click',function(){
        //alert('!@#$%^');
         var _this = jQuery(this);
         var _class = _this.attr('class');
         if(_active_type == undefined || _active_type.length == 0) _active_type = jQuery('.filter .on').attr('data-type');
         if(_class=='polygon') {
             geoChoose('add', _this.attr('title'), _this.data('id'), _active_type);
         } else {
             geoChoose('del', _this.attr('title'), _this.data('id'), _active_type);
         }
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
        .delegate('#districts-svg > polygon, #district-areas-svg  > polygon','mouseover', function(event) {
            jQuery('body').append('<span id="geodata-tooltip" style="position:absolute;"></span>');
            jQuery('#geodata-tooltip').html(jQuery(this).attr('title'));
            var _width = jQuery('#geodata-tooltip').width();
            var _this = jQuery(this);
            _this.mousemove(function( event ) {
                jQuery('#geodata-tooltip').css({left:event.pageX-(_width/2), top: event.pageY+20});
            }); 
            jQuery('#geodata-picker-wrap .location-list div[data-type='+jQuery('#geodata-picker-wrap .filter .active').data('type')+'] .item[data-id='+_this.data('id')+']').addClass('hover');
        })
        .delegate('#districts-svg > polygon, #district-areas-svg  > polygon','mouseout', function(event) {
            var _this = jQuery(this);
            jQuery('#geodata-tooltip').remove();
            jQuery('#geodata-picker-wrap .location-list div[data-type='+jQuery('#geodata-picker-wrap .filter .active').data('type')+'] .item[data-id='+_this.data('id')+']').removeClass('hover');
        }); 
        jQuery('.location-list .selected-items').delegate('.item','click',function(){
            var _reset = false;
            if(jQuery(this).hasClass('on')) var _action = 'del';
            else _action = 'add';
            //alert("condition = " + (jQuery('#geodata-picker-wrap').hasClass('.target-outed') && jQuery('#geodata-picker-wrap').attr('data-reset') == "true"));
            if(jQuery('#geodata-picker-wrap').hasClass('.target-outed') && jQuery('#geodata-picker-wrap').attr('data-reset') == "true") _reset = true;
            geoChoose(_action, jQuery(this).text(), jQuery(this).data('id'), jQuery(this).parents('div').data('type'), _reset);
        });
        function resetGeoForm(){
            jQuery('.location-list .selected-items').children('.item.on').each(function(){
                geoChoose('del', jQuery(this).text(), jQuery(this).data('id'), jQuery(this).parents('div').data('type'), true);
            });
        }
        //последний параметр означает сброс формы без записи в input
        function geoChoose(_action, _title, _id, _active_type_click, _reset){
            var _selected_titles_wrap = jQuery('.form-wrap .selected-items[data-type='+_active_type_click+']');
            if(_active_type_click == 'subways'){
                var _span = jQuery('.subways-title-item[data-subway-title-id='+_id+']',jQuery('#subways-title-wrap'));
                var _circle = jQuery('#subways-svg > circle[data-id='+_id+']');
                var _class = _circle.attr('class');
            }
            if(_action == 'add'){
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
                //для всплывашки выбора тегов - корректируем "Выделено всего"
                if(jQuery('.items-list .bottom-block').length != 0) jQuery('.items-list .bottom-block .selected-total i').html(_geodata_ids[_active_type_click].length);
            } else {
                _geodata_ids[_active_type_click].splice(_geodata_ids[_active_type_click].indexOf(_id), 1);
                 if(_active_type_click == 'subways' && _active_type_click == _active_type){
                     _span.removeClass('active');
                     if(_class != undefined) _circle.attr('class',_class.replace(' active',''));
                 }
                 else {
                     jQuery('#'+_active_type_click+'-svg').children('polygon[data-id='+_id+']').attr('class','polygon');
                 }
                 jQuery('div.item[data-id='+_id+']',_selected_titles_wrap).remove();
                 if(_geodata_ids['district-areas'].length + _geodata_ids['districts'].length + _geodata_ids['subways'].length == 0) jQuery('.address-select').removeClass('disabled').children('input').attr('disabled', false);
                 if(_geodata_ids[_active_type_click].length == 0) jQuery('.location-list h5.'+_active_type_click+' ').removeClass('active');
                 jQuery('.selected-items[data-type='+_active_type_click+'] .item[data-id='+_id+']').removeClass('on');
                 //для всплывашки выбора тегов - корректируем "Выделено всего"
                 if(jQuery('.items-list .bottom-block').length != 0) jQuery('.items-list .bottom-block .selected-total i').html(_geodata_ids[_active_type_click].length);
            }
            if(_reset == false) jQuery('input#'+_active_type_click).val(_geodata_ids[_active_type_click].join(','));
            var _filter = jQuery('#geodata-picker-wrap .filter');
            jQuery('span:not(.object-types-picker)', _filter).each(function(){
                jQuery(this).children('i').text(_geodata_ids[jQuery(this).data('type')].length);
            })
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
                //для всплывашки выбора тегов - корректируем "Выделено всего"
                if(jQuery('.items-list .bottom-block').length != 0) jQuery('.items-list .bottom-block .selected-total i').html(_geodata_ids[_active_type].length);
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
    function subwayHover(_active,_estate_url){
        var _circle = jQuery('#subways-svg > circle[data-id='+_active+']');
        var _span = jQuery('.subways-title-item[data-subway-title-id='+_active+']',jQuery('#subways-title-wrap'));
        var _class = _circle.attr('class');
        if(_estate_url=='mouseover'){
            _circle.attr('class',_class+' hover') ;
            _span.addClass('hover') ; 
            jQuery('#geodata-picker-wrap .location-list div[data-type=subways] .item[data-id='+_active+']').addClass('hover');
        }  else {
           _circle.attr('class',_class.replace(' hover',''));
           _span.removeClass('hover') ;
           jQuery('#geodata-picker-wrap .location-list div[data-type=subways] .item[data-id='+_active+']').removeClass('hover');
        }
        
        
    }
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
});