jQuery('#estate-search .autocomplete').each(function(){
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
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showPopupList(_input, msg.list);
                        else hidePopupList();
                    }

                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                },
                complete: function(){
                    _input.removeClass('wait');
                }
            });
            return false;
        },
        wait: 150,
        highlight: true,
        captureLength: 2
    }); 
}); 
//показать список автозаполнения
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

//скрыть список автозаполнения
function hidePopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}   