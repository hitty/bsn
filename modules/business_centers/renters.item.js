_action = 'edit';
jQuery(document).ready(function(){
    
    var _results = jQuery('.ajax-results');
    showResults();
    
    //редактирование записи
    var _form = jQuery('.edit-form');
    jQuery(document).on('click', '.renters-list .item', function(e){
        
        if(!jQuery(e.target).hasClass('edit')) return false;
        _this = jQuery(this);
        
        jQuery('input', _form).each(function(){
            var _name = jQuery(this).attr('name') ;
            jQuery(this).val(jQuery('[data-name='+_name+']', _this).text())
        })
        _action = 'edit';
        jQuery('.edit-button').click();
    })
    //форма добавления
    jQuery('.button.add').on('click', function(){
        jQuery('input', _form).val('')
        _action = 'add';
        
        jQuery('.edit-button').click();
    })
    //отправка данных редактирования
    jQuery('.send', _form).on('click', function(){
        var _values = {};
        var _error = false;
        jQuery("form.edit-form").find('input').each(function(){
                var _this = jQuery(this);
                _this.removeClass('red-border').next('span').removeClass('active')
                _value = _this.val();
                _required = _this.attr('required');
                _name = _this.attr('name');
                if(_required == 'required' && (_value == '' || _value == 0)) {
                    _this.addClass('red-border').next('span').addClass('active');
                    _error = true;
                } else _values[_name] = _value;
                
        })
        if(_error == false){
            getPending(window.location + _action + '/', _values);
            jQuery('.closebutton').click();
            setTimeout(showResults, 100);
            return false;
        }
    })
    jQuery('.form-button .cancel').on('click', function(){
        jQuery('.closebutton').click();
        return false;
    })

    //форма добавления
    jQuery(document).on('click', '.renters-list .item .delete', function(e){
        if(!confirm('Вы уверены, что хотите удалить арендатора')) return false;
        getPending(window.location + 'del/', {id:jQuery(this).parents('.item').attr('data-id')});
        setTimeout(showResults, 100);
    })
    
    //сортировка
    jQuery('.list-header b').on('click', function(){
        var _el = jQuery(this).parents('span');
        if(_el.hasClass('down')) _el.removeClass('down').addClass('up');
        else _el.removeClass('up').addClass('down');
        _el.siblings('span').removeClass('up').removeClass('down');
        showResults()    
    })
});   
function showResults(){
    var _results = jQuery('.ajax-results');
    var _sortby = 0;
    jQuery('.list-header span').each(function(){
        if(jQuery(this).hasClass('up') || jQuery(this).hasClass('down')) {
            var _sortby = jQuery(this).data('sort');
            var _sortby = jQuery(this).hasClass('down') ? _sortby + 1 : _sortby;
            getPendingContent('.ajax-results', '/members/office/business_centers/renters/list/', {sortby: _sortby})
            return false;

        }
        
    })
    
}






     