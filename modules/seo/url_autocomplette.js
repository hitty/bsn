jQuery(document).ready(function(){
    var _input = jQuery("#url");
    var _address = '/admin/seo/autocomplete/';
    /* автокомплит адреса */
    _input.typeWatch({
        callback: function(){
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: _address+'list/',
                data: {ajax: true, search_string: _searchstring},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showPopupList(_input, msg.list);
						else hidePopupList();
                    } else alert('Ошибка запроса к серверу!');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                },
                complete: function(){
                    _input.removeClass('wait');
                }
            });
        },
        wait: 750,
        highlight: true,
        captureLength: 2
    }).blur(hidePopupList());
});

function showPopupList(_el,_list){
    var str = '<ul class="typewatch_popup_list" data-simplebar="init" id="tags_popup_list">';
    for(var i in _list){
        str += '<li>'+_list[i].url+'</li>';
    }
    str += '</ul>';
    hidePopupList();
    jQuery('#p_field_url .fieldwrapper').append(jQuery(str));
    jQuery(".typewatch_popup_list li").bind('click', function(){
        _el.val( jQuery(this).html() );
        hidePopupList();
    });
}
function hidePopupList(){
    jQuery(".typewatch_popup_list li").unbind('click');
    jQuery(".typewatch_popup_list").remove();
}