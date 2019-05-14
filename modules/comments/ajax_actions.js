jQuery(document).ready(function(){
    jQuery('.list_table').click(function(e){
        var _target = jQuery(e.target);
        if(_target.is('a') && _target.children('span')) _target = _target.children('span');
        switch(true){
            case _target.hasClass('del_checked') :
               var selected_ids = [];
			   jQuery(".case:checked").each(function() {
                    selected_ids.push(jQuery(this).attr('value'));
                });
				if(selected_ids.length==0) return false;
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: '/admin/content/comments/del/',
                    data: {ajax: true,'selected_ids': selected_ids},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids.length){
                                    var _obj = null;
                                    for(var i=0;i<msg.ids.length;i++){
                                        _obj = jQuery('#item_'+msg.ids[i]);
                                        _obj.fadeOut(500,function(){_obj.remove();});
                                    }
                                } else alert('Ни один элемент не удален.');
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;    	
        }
        return true;
    });
	//Скрипт выбора чекбоксов
    jQuery("#selectall").click(function () {
          jQuery('.case').attr('checked', this.checked);
    });
    jQuery(".case").click(function(){
        if(jQuery(".case").length == jQuery(".case:checked").length) {
            jQuery("#selectall").attr("checked", "checked");
        } else {
            jQuery("#selectall").removeAttr("checked");
        }
 
    });	
    
    jQuery('.moderate-comment.button').on('click', function(){
        _this = jQuery(this);
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/admin/content/comments/publish/',
            data: {ajax: true,'id': jQuery(this).data('id')},
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                        _this.
                            siblings('.active').text('показывается').css({'color':'green'}).
                            siblings('.moderate').text('отмодерирован').css({'color':'green'});
                        _this.fadeOut();
                    } else alert('Ошибка: '+msg.error);
                } else alert('Ошибка!');
            },
            error: function(){
                alert('Server connection error!');
            },
            complete: function(){
            }
        });
        return false;        
        
    })
    
    
    
	
});