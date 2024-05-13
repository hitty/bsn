jQuery(document).ready(function(){
    var _input = jQuery("#autocomplete_input");
    /* автокомплит тегов */
    _input.typeWatch({
        callback: function(){
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: _input.attr('data-url'),
                data: {ajax: true, search_string: _searchstring},
                success: function(msg){ 
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showPopupHrefList(_input, msg.list);
						else hidePopupHrefList();
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
        wait: 150,
        highlight: true,
        captureLength: 2
    }).blur(hidePopupHrefList()); 
});

function showPopupHrefList(_el,_list){
    
    var str = '<ul id="autocomplete_popup_list">';
    for(var i in _list){
        var _text =  _list[i].url!=''?'<a href="'+_list[i].url+'">'+_list[i].title+'</a>':_list[i].title;
        str += '<li><span class="autocomplete_title">'+_text+'</span></li>';
    }
    str += '</ul>';
    hidePopupHrefList();
    jQuery('#autocomplete_inputbox').append(jQuery(str));
    jQuery("#autocomplete_popup_list li").bind('click', function(){
        jQuery("#autocomplete_add_input").val( jQuery('.autocomplete_title',jQuery(this)).html() );
        hidePopupHrefList();
    });
}
function hidePopupHrefList(){
    jQuery("#autocomplete_popup_list li").unbind('click');
    jQuery("#autocomplete_popup_list").remove();
}


jQuery(document).ready(function(){
    //автозаполнение ЖК
    jQuery('input.autocomplete').each(function(){
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
            },
            wait: 150,
            highlight: true,
            captureLength: 3
        }).blur(function(){
            setTimeout(function(){hidePopupList(_input)}, 350);
        });        
    })
    
 jQuery('.clear-input').on('click', function(){
       var _class = jQuery(this).prev('input').attr('name');
        jQuery('input[name='+_class+']').attr('value','').val('').siblings('input').val('');
        jQuery(this).addClass('hidden');
    });    
})


function showPopupList(_el,_list, _type){
        var _wrapper = _el.parent();
        var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
        for(var i in _list){                   
            str += '<li data-id="'+_list[i].id+'" title="'+_list[i].title+(typeof _list[i].additional_title=='string'?_list[i].additional_title:'')+'">' + (typeof _list[i].additional_title=='string'?'<span>'+_list[i].additional_title+'</span>, ':'') +  _list[i].title + '</li>';
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
   