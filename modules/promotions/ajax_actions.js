jQuery(document).ready(function(){
	//fileuploader init
	if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({
            queueSizeLimit: 1
        });
    }
    
    jQuery('#estate_complex_type').on('change', function(){
        manageComplex(false);
        
    })
    manageComplex(true);  
    _prev_type = '';
    function manageComplex(_instance){
        var _val = parseInt(jQuery('#estate_complex_type').val());
        var _type = '';
        if(_val > 0) {
            switch(_val){
                case 1: _type = 'housing_estates_titles' ; break;
                case 2: _type = 'cottages_titles' ; break;
                case 3: _type = 'business_centers_titles' ; break;
            }
            jQuery('#estate_complex_title').attr('data-url', '/admin/access/promotions/'+_type+'/');
            jQuery('#p_field_estate_complex_title').show();
            if(_instance == true) _prev_type = _type;
            else if(_prev_type != _type){
               jQuery('#estate_complex_title').val('') ;
               jQuery('#id_estate_complex').val(0) ;
               jQuery('.ajax-items.promotions-objects-list').hide();
               _prev_type = _type;
            }
        }
        else {
           jQuery('#p_field_estate_complex_title').hide();
           jQuery('#estate_complex_title').val('') ;
           jQuery('#id_estate_complex').val(0) ;
        }
        console.log(_val)
    }
    
    if(jQuery('.promotions-objects-list').length > 0)   {
        jQuery(document).on('click', '.promotions-objects-list .item span', function(){
            jQuery(this).addClass('active');
            var _val = parseInt(jQuery(this).next('input').val());
            if(_val == 0) jQuery(this).next('input').val('');
        })
        
        //редактирование ячейки
        jQuery(document).on('click', '.promotions-objects-list .item input', function(){
            if(jQuery(this).val() == '')  return false;
            changeValue(jQuery(this).parents('span'));
        })
        jQuery(document).on('click', '.promotions-objects-list .item span', function(){
            jQuery('.active').removeClass('active');
            jQuery(this).addClass('active').next('input').focus();
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
    }
    jQuery(document).on('click', function(e){
        if(jQuery(e.target).prev('active') && jQuery(e.target).hasClass('active')=='' && jQuery('.active').length!=0) changeValue(jQuery('.active'));
    })    
    var _template = '<div class="item" id="item-0" data-id="0">'+
                '<span class="id_object">-</span>'+
                '<input name="id_object_0" value="0" type="text">'+
                '<b class="inactive"></b>'
            '</div>';
    jQuery('.ajax-items.promotions-objects-list .button.add-object').on('click', function(){
        jQuery('.ajax-items.promotions-objects-list .list').prepend(_template);
    })
    jQuery(document).on('click', '.promotions-objects-list .item .delete', function(){
        var _this = jQuery(this);
        var _item = _this.parents('.item');
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: '/admin/access/promotions/objects/delete/', data: {id: _item.data('id'), id_parent: jQuery('.ajax-items.promotions-objects-list').data('id-parent')},
            success: function(msg){
                _item.fadeOut();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
            }
        }); 
    })       
            
});
function changeValue(_this){
    _params = {ajax:true} 
    var _class = _this.attr('class');
    //редактируемое поле
    var _input = _this.next('input');
    if(parseInt(_input.val()) <= 0) return false;
    jQuery('.active').removeClass('active').text(_input.val());
    _params[_this.attr('class')] = _input.val();
    
    _params['id'] = parseInt(_this.parents('div').attr('data-id'));                                         
    _params['id_parent'] = parseInt(_this.parents('div').parents('div').parents('div').attr('data-id-parent'));                                         
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: true,
        url: '/admin/access/promotions/objects/data/', data: _params,
        success: function(msg){
            if(msg.ok){
                var _parent = _this.parents('.item');
                if(msg.wrong_complex == true || msg.wrong_object == true || msg.wrong_promotion == true){
                    _parent.remove();;
                    if(msg.wrong_complex == true) alert('У данного комплекса нет такого объекта');
                    else if(msg.wrong_object == true) alert('Нет такого объекта в данном типе неждвижимости');
                    else if(msg.wrong_promotion == true) alert('Такой акции нет');
                } else {
                    if(parseInt(msg.object.id)>0) {
                        _parent.attr('data-id', msg.object.id).attr('id', 'item_'+msg.object.id);
                        _parent.find('i').text(msg.object.id);
                        _parent.find('b').removeClass('inactive');
                        _parent.find('.id_object').text(msg.object.id_object).next('input').val(msg.object.id_object == 0 ? '' : msg.object.id_object);
                    } 
                }
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
            console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
        }
    });
}
function clearObjects(){
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: true,
        url: '/admin/access/promotions/objects/clear/', data: {id_parent: (jQuery('.ajax-items.promotions-objects-list').attr('data-id-parent'))},
        success: function(msg){
            jQuery('.ajax-items.promotions-objects-list .list').html('');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
            console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
        }
    });    
}