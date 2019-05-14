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
            _this_input.val('').siblings('.clear-input').addClass('hidden');
            jQuery('#'+_this_input.attr('data-input')).val(0);
        })
    })
});

function showPopupList(_el,_list){
    
    var str = '<ul id="autocomplete_popup_list" style="top:35px">';
    for(var i in _list){
        var _text =  _list[i].title;
        str += '<li><span class="autocomplete_title" data-id="'+_list[i].id+'" >'+_text+'</span></li>';
    }
    str += '</ul>';
    hidePopupList();
    _el.parents('span').append(jQuery(str));
    jQuery("#autocomplete_popup_list li").bind('click', function(){
        _el.val( jQuery('.autocomplete_title',jQuery(this)).text());
        console.log(_el)
        _el.next('.clear-input').removeClass('hidden');
        jQuery("#"+_el.data('input')).attr("value",jQuery('.autocomplete_title',jQuery(this)).attr('data-id'));
        hidePopupList();
    });
}
function hidePopupList(){
    jQuery("#autocomplete_popup_list li").unbind('click');
    jQuery("#autocomplete_popup_list").remove();
}