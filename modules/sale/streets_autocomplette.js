jQuery(document).ready(function(){
    var _input = jQuery("#txt_street");
    _input.parent().css('position','relative');
    /* автокомплит улиц */
    _input.typeWatch({
        callback: function(){
            jQuery("#id_street").val(0);
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: window.location.href,
                data: {ajax: true, action: 'streets_list', search_string: _searchstring, geo_id: jQuery('#geolocation_id').val()},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showStreetsPopupList(_input, msg.list);
						else hideStreetsPopupList();
                    } else alert('Выберите населенный пункт!');
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
    }).blur(function(){
        setTimeout(function(){hideStreetsPopupList(jQuery("#txt_street").parent())}, 350);
    });

    jQuery("#house, #corp").on('change, keyup',function(e){
        fillAddress();
    })    
});

function showStreetsPopupList(_el,_list){
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
    for(var i in _list){
        str += '<li data-id="'+_list[i].id_street+'">'+_list[i].offname+' '+_list[i].shortname+'</li>';
    }
    str += '</ul>';
    hideStreetsPopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).closest('.typewatch_popup_list').parent();
        jQuery("#id_street").val( jQuery(this).attr('data-id') );
        _el.val(jQuery(this).html());
        hideStreetsPopupList(_parent_box);
        fillAddress();
        //если есть карта, то ставим отметку на карте
        if(jQuery('#map-box').size()>0) setMarkerPlace();
    });
}
function hideStreetsPopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}
// заполнение полного адреса от выбранных улицы+дома+корпуса
function fillAddress(){
    var _street = jQuery('#txt_street').val();
    if(_street!=''){
        var _house = jQuery('#house').val();
        if(_house>0) _street = _street + ', д.'+_house;
        var _corp = jQuery('#corp').val();
        if(_corp!='' && _corp!='0') _street = _street + ', корп.'+_corp;
        jQuery('#txt_addr').val(_street);
    }
}    