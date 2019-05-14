jQuery(document).ready(function(){
    jQuery('.list_table').click(function(e){
        var _target = jQuery(e.target);
        if(_target.is('a') && _target.children('span')) _target = _target.children('span');
        switch(true){
            //перенос элемента в архив / восстановление
			case _target.hasClass('ico_restore') :
			case _target.hasClass('ico_archive') :
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: {ajax: true},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids.length){
                                    var _obj = null;
                                    for(var i=0;i<msg.ids.length;i++){
                                        _obj = jQuery('#item_'+msg.ids[i]);
                                        _obj.fadeOut(500,function(){_obj.remove();});
                                    }
                                } else console.log('Ни один элемент не затронут.');
                            } else console.log('Ошибка: '+msg.error);
                        } else console.log('Ошибка!');
                    },
                    error: function(){
                        console.log('1Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
			//вкл/выкл баннера
			case _target.attr('data-id')>0:	
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: document.location.pathname+'setStatus/',
                    data: {ajax: true, id : _target.attr('data-id'), value : _target.attr('data-state')==1?'':'checked', flag : _target.attr('name')},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids.length){
									_target.attr('checked',_target.attr('checked')=='checked'?false:'checked');
                                } else console.log('Ни один элемент не изменен.');
                            } else console.log('Ошибка: '+msg.error);
                        } else console.log('Ошибка!');
                    },
                    error: function(){
                        console.log('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                _target.attr('data-state',Math.abs(_target.attr('data-state')-1));
                return true;
        }
        return true;
    });
    manageUtm();
    jQuery('#checkbox_utm').on('click', function(){
        manageUtm();
    })
    jQuery('#direct_link, #p_field_utm_medium, #p_field_utm_source, #p_field_utm_campaign, #p_field_utm_content').on('keyup', function(){
         manageUtm();
    })

    manageZones();
    jQuery( 'input[name*=_set]').on('click', function(){
        manageZones();
    })
});
function manageUtm(){
    var _this = jQuery('#checkbox_utm');
    console.log(_this.is(':checked'))
    if(_this.is(':checked')){
        var _link = jQuery('#direct_link').val();
        var _utmTest = /utm_?/i; 
        var _template = '<span class="small_text red utm-error">Нельзя добавить метки. Прямая ссылка уже содержит метки</span>';
        if(_utmTest.test(_link)) {
            if(jQuery('.utm-error').length == 0) jQuery(_template).insertAfter(_this);
            jQuery('.utm-error').show();
            _this.attr('checked', false);
            jQuery('#utm-link').hide();
            return false;
        }
    } else {
        if(jQuery('.utm-error').length > 0) jQuery('.utm-error').hide();
        
        
    }

    if(jQuery('#utm-link').length != 0) jQuery('#utm-link').remove();
    var _direct_link = jQuery('#direct_link').val();
    var _link = [];
    jQuery('#utm_medium, #utm_source, #utm_campaign, #utm_content').each(function(){
        var _val = jQuery(this).val();
        var _name = jQuery(this).attr('name');
        if(_val != '')_link.push(_name + '=' + _val)
    })

    jQuery('#p_field_utm').append('<div id="utm-link">' + _direct_link + '?' + _link.join('&') + '</div>');
                                                        
    var _checked = jQuery('#checkbox_utm').is(':checked');
    if(_checked == true) jQuery('#p_field_utm_medium, #p_field_utm_source, #p_field_utm_campaign, #p_field_utm_content, #utm-link').show();
    else jQuery('#p_field_utm_medium, #p_field_utm_source, #p_field_utm_campaign, #p_field_utm_content, #utm-link').hide();
}
function manageZones(){
    
    var _zones = 0;
    jQuery( 'input[name*=_set]').each( function( index ){
        if( jQuery(this).is(':checked' ) ) _zones += Math.pow( 2, index + 1 );
    })
    jQuery( 'input[name=zones]' ).val(_zones )    ;
}