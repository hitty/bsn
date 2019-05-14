jQuery(document).ready(function(){
    var _input = jQuery(".typewatch");
    _input.parent().css('position','relative');
    /* автокомплит улиц */
    _input.on('blur',function(){
        if(jQuery(this).val() == "") jQuery(this).parents('p').removeClass('error')
    });
    _input.typeWatch({
        callback: function(e){
            var _this = jQuery(jQuery(this)[0].el);
            _this.parents('p').removeClass('error');
            var _action = _this.attr('class').replace('typewatch','').replace('lf','').trim();
            var _searchstring = this.text;
            var _geo_data = [];
            _geo_data = ({'region':jQuery('#id_region').val(),'district':jQuery('#id_district').val(),'area':jQuery('#id_area').val(),'city':jQuery('#id_city').val(),'place':jQuery('#id_place').val()});
            _this.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/service/geodata/address_adding/' + _action + '_list/',
                data: {ajax: true, action: 'streets_list', search_string: _searchstring, geo_data: _geo_data},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showStreetsPopupList(_this, msg.list);
                        else{
                            _this.parents('p').addClass('error');
                            hideStreetsPopupList();
                        }
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
    
    var _geo_level = _el.attr('class').replace('typewatch','').replace('lf','').trim();
    var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
    
    for(var i in _list) str += '<li data-id="'+_list[i].id+'">'+_list[i].offname+' '+_list[i].shortname+'</li>';
    
    str += '</ul>';
    hideStreetsPopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).closest('.typewatch_popup_list').parent();
        //устанавливаем значение в поле и id
        _el.val(jQuery(this).html());
        var _geo_level = _el.attr('class').replace('typewatch','').replace('lf','').trim();
        //в случае улицы, заполняем id_geodata
        if(_geo_level != 'street') jQuery('#id_' + _geo_level).val(jQuery(this).attr('data-id'));
        else{
            jQuery('#id_geodata').val(jQuery(this).attr('data-id'));
            jQuery('.address-add').addClass('disabled');
        } 
        
        
        
        //чистим (если нужно) поля ниже уровнем
        if(_geo_level == 'area' || _geo_level == 'district'){
            jQuery('#id_city').val(0);
            jQuery('#txt_city').val("");
            jQuery('#id_place').val(0);
            jQuery('#txt_place').val("");
        }
        else if(_geo_level == 'city'){
            jQuery('#id_place').val(0);
            jQuery('#txt_place').val("");
        }
        
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