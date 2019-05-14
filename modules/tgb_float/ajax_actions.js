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
                                } else alert('Ни один элемент не затронут.');
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('1Server connection error!');
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
                                } else alert('Ни один элемент не изменен.');
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                _target.attr('data-state',Math.abs(_target.attr('data-state')-1));
                return true;
        }
        return true;
    });

});