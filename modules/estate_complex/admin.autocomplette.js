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
                    data: {ajax: true, search_string: _searchstring, type: _this_input.data('type')},
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
    })
    
    jQuery('input.save-complex').each(function(){
        jQuery(this).on('click',function(){
            var _this = jQuery(this)
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/estate/estate_complexes_external/save_complex/',
                data: {ajax: true, id: _this.data('id'), id_complex: _this.data('value')},
                success: function(msg){ 
                    if(msg.ok)  _this.val('Сохранено').attr('disabled','disabled').next('span').fadeIn(200);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            });
            
        })
    });
    jQuery(document).on('click', function(e){
        if(jQuery(this).parents('#autocomplete_popup_list').length == 0)  hidePopupList()
    })
    
});

function showPopupList(_el,_list){
    
    var str = '<ul id="autocomplete_popup_list" style="top:22px; left:2px;">';
    for(var i in _list){
        var _text =  _list[i].title;
        str += '<li><span class="autocomplete_title" data-id="'+_list[i].id+'">'+_text+'</span></li>';
    }
    str += '</ul>';
    hidePopupList();
    _el.parents('span').append(jQuery(str));
    jQuery("#autocomplete_popup_list li").bind('click', function(){
        _el.val( jQuery('.autocomplete_title',jQuery(this)).text());
        jQuery("#"+_el.data('input')).attr('data-value', jQuery('.autocomplete_title',jQuery(this)).attr('data-id')).removeAttr('disabled');
        hidePopupList();
    });
}
function hidePopupList(){
    jQuery("#autocomplete_popup_list li").unbind('click');
    jQuery("#autocomplete_popup_list").remove();
}

