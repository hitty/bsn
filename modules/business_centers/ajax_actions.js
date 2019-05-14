jQuery(document).ready(function(){
	jQuery('#redirect_submit').click(function(e){
        var _form = jQuery('#business_centers_form');
        _form.attr('action',_form.attr('action')+'?redirect=true');
        _form.submit();
    })
	//fileuploader init
	if(jQuery('#file_upload').length>0){
		jQuery('#file_upload').uploadifive();
	}	
    //fileuploader init
    jQuery('.item', jQuery('.offices-list .list')).each(function(){
        var _fieldset = jQuery(this);
        var _id = _fieldset.data('id');
        jQuery('#file_upload_'+_id, _fieldset).uploadifive({
            queueSizeLimit : 4
        });
    })
    jQuery('.change-manager').on('change', function(){
        jQuery(this).next('input').fadeIn();
    })
    jQuery('.save-manager').each(function(){
        jQuery(this).on('click',function(){
            _this = jQuery(this)
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/estate/business_centers/save_manager/',
                data: {ajax: true, id: _this.data('id'), id_manager: _this.siblings('.change-manager').val()},
                success: function(msg){ 
                    if(msg.ok)  _this.fadeOut();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            });
            
        })
    }); 
    
    if(jQuery('.offices-list').length > 0)   {
        jQuery(document).on('click', '.offices-list .item span', function(){
            if(jQuery(this).attr('class') != 'status' && jQuery(this).attr('class') != 'object_type' && jQuery(this).attr('class') != 'id_facing' && jQuery(this).attr('type') != 'file') jQuery(this).addClass('active');
        })
        
        //редактирование ячейки
        jQuery(document).on('click', '.offices-list .item input', function(){
            if(jQuery(this).attr('type') != 'file'){
                if(jQuery(this).attr('type')!='checkbox') return false;
                else changeValue(jQuery(this).parents('span'));
            }
        })
        jQuery(document).on('change', '.offices-list .item select', function(){
            changeValue(jQuery(this).parents('span'));
        })
        jQuery(document).on('click', '.offices-list .item span', function(){
            if(jQuery(this).attr('class') != 'status' && jQuery(this).attr('class') != 'object_type' && jQuery(this).attr('class') != 'id_facing' && jQuery(this).attr('type') != 'file'){
                if(jQuery(this).prev('.active').length ==0) changeValue(jQuery('.active'));
                jQuery('.active').removeClass('active');
                jQuery(this).addClass('active').next('input').focus();
            }
        })
        //сохранение после клика в любое место кроме активного элемента
        
        //управление табом и ентером
        jQuery('body').on('keydown', 'input', function(e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                e.stopPropagation();
                changeValue(jQuery('.active'));
            } else if(e.keyCode == 9){
                e.stopPropagation();
                e.preventDefault();
                if(jQuery('.active').next('input').next('span').length!=0) jQuery('.active').next('input').next('span').click();
                else if(jQuery('.active').parent('div').next('div').children('span:first-child').length!=0) jQuery('.active').parent('div').next('div').children('span:first-child').click()
                
            }
        }); 
       
       jQuery(document).on('click', function(e){
           if(jQuery(e.target).parents('#svg').length!=0) return false; 
           else if(jQuery(e.target).prev('active') && jQuery(e.target).hasClass('active')=='' && jQuery('.active').length!=0) changeValue(jQuery('.active'));
        })
               
        jQuery('body').delegate('#svg g', 'click',function(){
            
            jQuery('#item-'+jQuery(this).attr('id')+' span:first-child').click()
        })

        
        jQuery('body')
                .delegate('#svg g','mouseover', function(event) {
                    if(jQuery('#edit').hasClass('selected')){
                        var _g_class = jQuery(this).attr('class');
                        if(typeof _g_class == "string") jQuery(this).attr('class', _g_class + ' hovered');
                        var _item = jQuery('#item-'+jQuery(this).attr('id'));
                        if(_item.length > 0){
                            var _class = _item.attr('class') ;
                            _item.attr('class', _class + ' hovered');
                        }
                    }
                })
                .delegate('#svg g','mouseout', function(event) {
                    if(jQuery('#edit').hasClass('selected')){
                        var _g_class = jQuery(this).attr('class');
                        if(typeof _g_class == "string") jQuery(this).attr('class', _g_class.replace(' hovered',''));
                        var _item = jQuery('#item-'+jQuery(this).attr('id'));
                        if(_item.length > 0){
                            var _class = _item.attr('class') ;
                            _item.attr('class', _class.replace(' hovered',''));
                        }
                    }
                }); 
        jQuery('body')
                .delegate('.offices-list .list .item','mouseover', function(event) {
                        jQuery('#svg g#'+jQuery(this).attr('id').replace('item-','')).attr('class', 'hovered');
                        var _class = jQuery(this).attr('class') ;
                        jQuery(this).attr('class', _class + ' hovered');
                })
                .delegate('.offices-list .list .item','click', function(event) {
                        jQuery('#svg g polygon:first-child,#svg g rect:first-child').attr('class', '');
                        jQuery('#svg g#'+jQuery(this).attr('id').replace('item-','')).children(":first").attr('class', 'selected');
                        var _class = jQuery(this).attr('class') ;
                        jQuery(this).attr('class', _class + ' selected').siblings('.item').attr('class', 'item');
                })
                .delegate('.offices-list .list .item','mouseout', function(event) {
                        jQuery('#svg g#'+jQuery(this).attr('id').replace('item-','')).attr('class', '');
                        var _class = jQuery(this).attr('class') ;
                        jQuery(this).attr('class', _class.replace(' hovered',''));
                });         
         
                            
    }              
});
function changeValue(_this){
    _params = {ajax:true} 
    var _class = _this.attr('class');
    //редактируемое поле
    if(_class == 'status') {
        var _val = _this.children('input').is(':checked');
        _params[_this.attr('class')] = _val; 
        if(_val == true) _this.siblings('.object_type').children('select').val('1');   
    } else if( _class == 'object_type' || _class == 'id_facing'){
        var _val = _this.children('select').val();
        _params[_this.attr('class')] = _this.children('select').val();
        if(_val == 2 && _class == 'object_type') _this.siblings('.status').children('input').attr('checked', false);
    } else {
        var _input = _this.next('input');
        jQuery('.active').removeClass('active').text(_input.val());
        _params[_this.attr('class')] = _input.val();
    }
    
    _params['id'] = parseInt(_this.parents('div').attr('data-id'));                                         
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: true,
        url: '/admin/estate/business_centers/offices/data/', data: _params,
        success: function(msg){
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
            console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
        }
    });
}