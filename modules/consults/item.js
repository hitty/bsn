jQuery(document).ready(function(){
    
    //строка сортировок
    jQuery('.sorting-box .sorting span').on('click', function(){       
        var _val = 1;
        jQuery(this).addClass('active').siblings('span').removeClass('active').removeClass('up').removeClass('down');
        
        if(jQuery(this).hasClass('down') || !(jQuery(this).hasClass('up'))) {
            jQuery(this).removeClass('down').addClass('up');
            _val = jQuery(this).data('down-value');
        } else {
            jQuery(this).removeClass('up').addClass('down')
            _val = jQuery(this).data('up-value');
        }
        
        getPendingContent(".answers-list-box",window.location.href + '?sortby=' + _val,false,false,false,false);
        return false;
    });
    
    jQuery(document).on('click','.answer-make_best',function(){
        var _this_box = jQuery(this).parents('.answer-box_item');
        jQuery.ajax({
            url: '/service/consultant/make_best/',
            cache: false,
            type: 'POST',
            async: true,
            dataType: 'json',
            data: {
                answer_id: _this_box.attr('data-id'),
                question_id: jQuery('.question-box').attr('data-id'),
                ajax:true
            },
            success: function(msg){
                if(msg.ok){
                    _this_box.addClass('best-answer').siblings().removeClass('best-answer');
                    _this_box.insertBefore(jQuery('.answers-box').children().first());
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log("Error: "+textStatus+" "+errorThrown);
            },
            complete: function(){}
        });
    });
});