jQuery(document).ready(function(){
    jQuery('#txt_district,#txt_subway').each(function(){
        
        var _input = jQuery(this);
        var _type =  jQuery(this).attr('id').replace('txt_','');
        _input.parent().css('position','relative');
        /* автокомплит улиц */
        _input.typeWatch({
            callback: function(){
                var _searchstring = this.text;
                _input.addClass('wait');
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: window.location.href,
                    data: {ajax: true, action: _type+'s_list', search_string: _searchstring},
                    success: function(msg){
                        if(typeof(msg)=='object' && msg.ok) {
                            if(msg.list.length>0) showSimplePopupList(_input, msg.list, _type);
						    else hideSimplePopupList(_input.parent());
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        alert('Запрос не выполнен!');
                    },
                    complete: function(){
                        _input.removeClass('wait');
                    }
                });
            },
            wait: 150,
            highlight: true,
            captureLength: 2
        }).blur(function(){
            setTimeout(function(){hideSimplePopupList(_input.parent())}, 350);
        });
})
    
});

function showSimplePopupList(_el,_list, _type){
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
    for(var i in _list){
        str += '<li data-id="'+_list[i].id+'">'+_list[i].title+'</span></li>';
    }
    str += '</ul>';
    hideSimplePopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).closest('.typewatch_popup_list').parent();
        jQuery("#id_"+_type).val( jQuery(this).data('id') );
        _el.val(jQuery(this).html());
        hideSimplePopupList(_parent_box);
    });
}
function hideSimplePopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}  