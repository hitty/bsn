jQuery(document).ready(function(){
    var _input = jQuery(".autocomplete_input");
    /* автокомплит тегов */
    _input.each(function(){
        var _this_input = jQuery(this);
        _this_input.typeWatch({
            callback: function(){
                var _searchstring = this.text;
                _this_input.addClass('wait');
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: _this_input.attr('data-url'),
                    data: {ajax: true, search_string: _searchstring},
                    success: function(msg){ 
                        if(typeof(msg)=='object' && msg.ok) {
                            if(msg.list.length>0) showPopupList(_this_input, msg.list);
						    else hidePopupList();
                        } else console.log(msg.alert);
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log('Запрос не выполнен!');
                    },
                    complete: function(){
                        _this_input.removeClass('wait');
                    }
                });
            },
            wait: 150,
            highlight: true,
            captureLength: 2
        }).blur(hidePopupList());
        jQuery(this).next('.clear-input').on('click', function(){
            var _this_input = jQuery(this).prev('input');
            _this_input.val('');
            jQuery('#'+_this_input.attr('data-input')).val(0);
        })
    })
});

function showPopupList(_el,_list){
    
    var str = '<ul id="autocomplete_popup_list" style="top:35px">';
    for(var i in _list){
        var _text =  _list[i].title;
        console.log(typeof _list[i].subway_title)
        str += '<li><span class="autocomplete_title" data-id="'+_list[i].id+'" '+( typeof _list[i].subway_title == 'string' ? 'data-subway-title="'+_list[i].subway_title+'"' : "")+' '+( typeof _list[i].way_type_title == 'string' ? 'data-way_type-title="'+_list[i].way_type_title+'"' : "")+' '+( typeof _list[i].way_time_title == 'string' ? 'data-way_time-title="'+_list[i].way_time_title+'"' : "")+' '+( typeof _list[i].district_title == 'string' ? 'data-district-title="'+_list[i].district_title+'"' : "")+' '+( typeof _list[i].district_area_title == 'string' ? 'data-district_area-title="'+_list[i].district_area_title+'"' : "")+' '+( typeof _list[i].id_subway == 'string' ? 'data-id-subway="'+_list[i].id_subway+'"' : "")+'  '+( typeof _list[i].id_district == 'string' ? 'data-id-district="'+_list[i].id_district+'"' : "")+' '+( typeof _list[i].id_district_area == 'string' ? 'data-id-district_area="'+_list[i].id_district_area+'"' : "")+'  '+( typeof _list[i].id_way_type == 'string' ? 'data-id-way_type="'+_list[i].id_way_type+'"' : "")+' '+( typeof _list[i].way_time == 'string' ? 'data-way_time="'+_list[i].way_time+'"' : "")+' >'+_text+'</span></li>';
    }
    str += '</ul>';
    hidePopupList();
    _el.parents('span').append(jQuery(str));
    jQuery("#autocomplete_popup_list li").bind('click', function(){
        _el.val( jQuery('.autocomplete_title',jQuery(this)).text());
        jQuery("#"+_el.data('input')).val( jQuery('.autocomplete_title',jQuery(this)).attr('data-id'));
        var _el_geo = jQuery('.autocomplete_title',jQuery(this));                 
        if(jQuery('#subway_title').length > 0           && typeof _el_geo.attr('data-subway-title')!='undefined') jQuery('#subway_title').val( _el_geo.attr('data-subway-title') )
        if(jQuery('#id_subway').length > 0              && typeof _el_geo.attr('data-id-subway')!='undefined') jQuery('#id_subway').val( _el_geo.attr('data-id-subway') )
        if(jQuery('#district_title').length > 0         && typeof _el_geo.attr('data-district-title')!='undefined') jQuery('#district_title').val( _el_geo.attr('data-district-title') )
        if(jQuery('#id_district').length > 0            && typeof _el_geo.attr('data-id-district')!='undefined') jQuery('#id_district').val( _el_geo.attr('data-id-district') )
        if(jQuery('#district_area_title').length > 0    && typeof _el_geo.attr('data-district_area-title')!='undefined') jQuery('#district_area_title').val( _el_geo.attr('data-district_area-title') )
        if(jQuery('#id_district_area').length > 0       && typeof _el_geo.attr('data-id-district_area')!='undefined') jQuery('#id_district_area').val( _el_geo.attr('data-id-district_area') )
        if(jQuery('#id_way_type').length > 0            && typeof _el_geo.attr('data-id-way_type')!='undefined') jQuery('#id_way_type').val( _el_geo.attr('data-id-way_type') )
        if(jQuery('#way_time').length > 0               && typeof _el_geo.attr('data-way_time')!='undefined') jQuery('#way_time').val( _el_geo.attr('data-way_time') )
        hidePopupList();
    });
}
function hidePopupList(){
    jQuery("#autocomplete_popup_list li").unbind('click');
    jQuery("#autocomplete_popup_list").remove();
}