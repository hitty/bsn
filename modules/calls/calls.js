$(document).ready(function(){
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    
    var _tags_edit = $('.hidden-tag-flag').attr('value');
    $('li',$('.list-data')).click(function(){
        $('#f_status').val($(this).data('id'));
    });
 
    
    //обработка клика по тегу
    $(document).on("click", ".tag", function(e){
        var _this = $(this);
        if(_this.siblings('.tags-list-edit').length > 0){
            var  _edit_wrap = _this.siblings('.tags-list-edit');
            $('.tags-list-edit').removeClass('active');
            _this.siblings('.tags-list-edit').addClass('active');
        }
        e.preventDefault();
        e.stopPropagation();

        return true;
    });
     $(document).click(function(e){
        if((e.target.className != 'tags-list-edit')){
            $('.tags-list-edit').removeClass('active');
        }
     });       
     $(document).on('click', '.tags-list-edit .tag', function(){
         _this = $(this);
         _this.toggleClass('active').siblings('span').removeClass('active')
         .parents('.tags-list-edit').siblings('.tag[data-id='+$(this).data('id')+']').toggleClass('active').siblings('span').removeClass('active');
         if(_this.parents('.tags-list-edit').siblings('.tag.active').length == 0){
            _this.parents('.tags-list-edit').siblings('.tag.tag-add').addClass('active');
         }  else _this.parents('.tags-list-edit').siblings('.tag.tag-add').removeClass('active');
         
        $.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/members/conversions/calls/edit_tags/',
            data: {ajax: true, id: _this.parents('.tags-list-edit').parents('td').parents('tr').prop('id'), tag_id: jQuery('.tags-list-edit .active').attr('data-id'), active: $(this).hasClass('active')},
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                    } else console.log('Ошибка: '+msg.error);
                } else console.log('Ошибка!');
            },
            error: function(){
                console.log('Server connection error!');
            },
            complete: function(){
            }
        });
         
         
     })     
    //открытие звонка
    $(document).on('click', '.inwork', function(ev){
        _this = $(this);
        var _target = $(this);
        var _cost = parseInt($(this).data('cost'))/2;
        ev.stopPropagation();
        ev.preventDefault();
        var _text = 'Вы уверены, что хотите показать номер? Сумма списания составит '+_cost+' рублей';
        if(!confirm(_text)) return false;
            $.ajax({
                type: "POST", async: true,
                dataType: 'json', url: _target.data('link'),
                data: {ajax: true},
                success: function(msg){
                    if(typeof(msg)=='object') {
                        if(msg.ok) {
                            if(typeof msg.text == 'string'){
                                    _this.removeClass('inwork').addClass('notification msgerror').text(msg.text);
                            } else {
                                $('.members-menu .balance b').text(parseInt($('.members-menu .balance b').text().replace(' ','')) - _cost/2);
                                for(i=0; i<msg.ids.length; i++){
                                    $("span.phone-number[data-id="+msg.ids[i]+"]").text(msg.phone).next('span').fadeOut(200);
                                }
                            }
                        } else console.log('Ошибка: '+msg.error);
                    } else console.log('Ошибка!');
                },
                error: function(){
                    console.log('Server connection error!');
                },
                complete: function(){
                }
        });
        return false;
    });    
});