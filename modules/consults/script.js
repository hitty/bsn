jQuery(document).ready(function(){
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    //разворачиваем/сворачиваем форму
    /*
    jQuery('.consultant-ask').on('click',function(){
        jQuery('.consults-view-form').toggleClass('hidden');
        jQuery('.form-box').toggleClass('fading');
        setTimeout("jQuery('.form-box').toggleClass('active');jQuery('.form-box').toggleClass('fading');",50);
        var _text = jQuery(this).html();
        jQuery(this).html(jQuery(this).attr('data-text-alt'));
        jQuery(this).attr('data-text-alt',_text);
    });
    */
    
    //отправляем ответ
    jQuery('.answer-form button').on('click',function(){
        var _text = CKEDITOR.instances['answer_text'].getData();
        
        var _question_id = window.location.href.replace(/[^0-9]/g,'');
        jQuery.ajax({
            url: '/service/consultant/add-answer/',
            cache: false,
            type: 'POST',
            async: true,
            dataType: 'json',
            data: {
                text: _text,
                question_id: _question_id,
                ajax:true
            },
            success: function(msg){
                if(msg.ok){
                    jQuery('.answer-form').after("<div class='notification-accept answer'><b>Спасибо за ваш ответ!</b></div>");
                    jQuery('.answer-form').remove();
                    setTimeout("jQuery('.notification-accept').fadeOut(500);",1000);
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log("Error: "+textStatus+" "+errorThrown);
            },
            complete: function(){}            
        });
    });
    
    jQuery(document).ready(function(){
        
        jQuery(document).on('change',"#count_selector", function(event, value){
            setBSNCookie('View_count_estate', value, 30, '/');
        });
        
        jQuery(document).on('mouseenter','.consultant-item',function(){
            jQuery(this).prev('.consultant-item').addClass('prev-hover');
            var _button = jQuery(this).find('.blue.read-answers')
            if(_button.html() == "Свернуть") return true;
            if(_button.html() == _button.attr('data-hovertext')) _button.html(_button.attr('data-text'));
            else _button.html(_button.attr('data-hovertext'));
        });
        jQuery(document).on('mouseleave','.consultant-item',function(){
            if(!jQuery(this).hasClass('active')) jQuery(this).prev('.consultant-item').removeClass('prev-hover');
            var _button = jQuery(this).find('.blue.read-answers')
            if(_button.html() == "Свернуть") return true;
            if(_button.html() == _button.attr('data-hovertext')) _button.html(_button.attr('data-text'));
            else _button.html(_button.attr('data-hovertext'));
        });
        
        jQuery(document).on('click','.consultant-item',function(){
            var _button = jQuery(this).find('.blue.read-answers');
            jQuery(this).toggleClass('active');
            _button.html((jQuery(this).hasClass('active') ? "Свернуть" : _button.attr('data-hovertext')));
        });
        
        //сбрасываем фильтр
        jQuery('.reset-filter').on('click',function(){
            jQuery('#filter_dealtype').val("").siblings('.pick').attr('title',"Тип сделки").html("Тип сделки").siblings('.list-data').children().removeClass('selected').eq(0).addClass("selected");
            jQuery('#filter_estatetype').val("").siblings('.pick').attr('title',"Тип сделки").html("Тип сделки").siblings('.list-data').children().removeClass('selected').eq(0).addClass("selected");
        });
        
    });    
});