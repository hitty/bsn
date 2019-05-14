jQuery(document).ready(function(){
    var _input = jQuery("#txt_region");
    _input.parent().css('position','relative');
    /* автокомплит улиц */
    _input.typeWatch({
        callback: function(){
            jQuery("#geo_id").val(0);
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: window.location.href,
                data: {ajax: true, action: 'regions_list', search_string: _searchstring},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showRegionsPopupList(_input, msg.list);
						else hideRegionsPopupList();
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
        setTimeout(function(){hideRegionsPopupList(jQuery("#txt_region").parent())}, 350);
    });
});

function showRegionsPopupList(_el,_list){
    var _wrapper = _el.parent();
    var str = '<ul class="typewatch_popup_list" data-simplebar="init">';
    for(var i in _list){
        str += '<li data-id="'+_list[i].id+'" data-id_district="'+_list[i].id_district+'" data-id_region="'+_list[i].id_region+'" data-item="'+_list[i].g_offname+'"  data-region="'+_list[i].region+'" data-district_title="'+_list[i].district_title+'">'+_list[i].g_offname+' <span>'+_list[i].region+'</span></li>';
    }
    str += '</ul>';
    hideRegionsPopupList(_wrapper);
    _wrapper.append(jQuery(str));
    jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
        var _parent_box = jQuery(this).closest('.typewatch_popup_list').parent();
        jQuery("#geo_id").val( jQuery(this).data('id') );
        jQuery("#geolocation").val( jQuery(this).data('region') );
        var _id_region = parseInt(jQuery(this).data('id_region'));
        if(_id_region == 78){
            jQuery("#id_district").val( jQuery(this).data('id_district') );
            var _district_title = jQuery(this).data('district_title');
            if(_district_title!='' && _district_title!='-') jQuery("#txt_district").val(_district_title).attr('disabled',false);
        } else {
            jQuery("#id_district").val(0);
            jQuery("#txt_district").attr('disabled','disabled').val('-');
        }
        
        _el.val(jQuery(this).data('item'));
        hideRegionsPopupList(_parent_box);
        jQuery("#id_street").val(0);
        jQuery('#house').val(''); 
        jQuery('#corp').val(''); 
        jQuery('#txt_street').val(''); 
        jQuery('#txt_addr').val(''); 
        fillAddress();
        //если есть карта, то ставим отметку на карте
        if(jQuery('#map-box').size()>0) setMarkerPlace();
    });
}
function hideRegionsPopupList(_wrapper){
    if(!_wrapper) _wrapper = jQuery(document);
    jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
    jQuery(".typewatch_popup_list", _wrapper).remove();
}  