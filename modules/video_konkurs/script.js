jQuery(document).ready(function(){
    jQuery(".checkbox").on('click', function(e){
        checkForm();
    });   
    jQuery("#upload-form input").on('change', function(){
        checkForm();
    }) 
    jQuery('#upload-form').on('submit', function(){
        jQuery('#send-video').remove();
        jQuery('.waiting').removeClass('inactive');
    })
    //кнопка смотреть еще
    _page = 1;
    jQuery('.show-more').on('click', function(){
        _page = _page + 1;
        _this = jQuery(this)
        _this.addClass('waiting-button');
        jQuery.ajax({
            type: "GET", dataType: 'json',
            async: true, cache: false,
            url: '/video_konkurs_2015/list/',
            data: {ajax: true, page: _page, sortby: jQuery('#sortby').val()},
            success: function(msg){
                if(typeof(msg)=='object' && msg.ok) {
                    _this.removeClass('waiting-button');
                    jQuery('#movies-list').append(msg.html);
                    if(msg.hide_button) jQuery('.show-more').fadeOut();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            },
            complete: function(){
                
            }
        });
        
    })
    //автозаполнение ЖК
    jQuery('.autocomplete', jQuery('#upload-form')).each(function(){
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
    jQuery(document).on('click', '.likes', function(){
        _this = jQuery(this);
        if(_this.hasClass('active')) {
            var _action = 'minus';
            _this.text(parseInt(_this.text())-1).removeClass('active');
        } else {
            _action = 'plus';
            _this.text(parseInt(_this.text())+1).addClass('active');
        }
        getPending('/video_konkurs_2015/vote_for/', {id: _this.data('id'), action:_action});
        return false;
    })
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
    function hidePopupList(_wrapper){
        if(!_wrapper) _wrapper = jQuery(document);
        jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
        jQuery(".typewatch_popup_list", _wrapper).remove();
    }  
    jQuery('.clear-input').on('click', function(){
       var _class = jQuery(this).prev('input').attr('name');
        jQuery('input[name='+_class+']').attr('value','').val('').siblings('input').val('').siblings('.clear-input').addClass('hidden');
    });  
    
    jQuery('.complex-tabs span').on('click', function(){
        jQuery(this).addClass('selected').siblings('span').removeClass('selected');
        var _a = jQuery(this).parents('.complex-tabs').siblings('a');
        if(jQuery(this).data('complex-type')==1) {
            _a.show();
            jQuery('#estate_complex_title').attr('placeholder', 'Название ЖК').attr('value','');
            jQuery('#estate_complex_title').attr('data-url', '/zhiloy_kompleks/title/');
        } else {
            _a.hide();
            jQuery('#estate_complex_title').attr('placeholder', 'Название КП').attr('value','');
            jQuery('#estate_complex_title').attr('data-url', '/cottedzhnye_poselki/title/');
        }
        jQuery('#upload-form .typewatch_popup_list').remove();
        jQuery('#complex_type').attr('value', jQuery(this).data('complex-type'));
    })
})
_phone_error = _error = false;
function checkForm(){
    _error = false;
    jQuery("#upload-form").find('input').each(function(){
        var _this = jQuery(this);
        _this.removeClass('red-border')
        var _type = _this.attr('type');
        
        if(_type == 'checkbox' && _this.parent().hasClass('on')){
            _value = 1;
        } else {
            _value = _this.val();
        }
        _name = _this.attr('name');
        if((_value == '' || _value == 0)) {
            _this.addClass('red-border').attr('title','Обязательное поле');
            _error = true;
        } 
    })           
    
    if(_emailTest.test(jQuery('#email').val()) == false){
        _error = true;
        jQuery('#email').addClass('red-border');
    }    
    if(jQuery('#phone').val().length != 17) {
        _error = true;
        jQuery('#phone').addClass('red-border');
    }
    if(_error == true) {
        jQuery('#send-video').attr('disabled','disabled');
    } else {
        jQuery('#send-video').attr('disabled',false);
    }            
}
function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}