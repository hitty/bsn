function c_getlist(_objects, _id_parent){
    $("#nextbtn").fadeOut('fast');
    $.ajax({
        url: '/'+_objects+'/comments/',
        cache: false,
        type: 'POST',
        async: true,
        dataType: 'json',
        data: {
            id_parent: _id_parent,
            shift: cmt_count,
            ajax:true
        },
        success: function(msg){
            if(msg.ok){
                $("#nextbtn").before(msg.html);
                if(!msg.eoc) $("#nextbtn").fadeIn('fast');
                cmt_count = cmt_count + msg.count;
            } else $("#nextbtn").before('Ошибка: '+msg.error);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log("Error: "+textStatus+" "+errorThrown);
        },
        complete: function(){}
    });
    return false;
}

jQuery(document).ready(function(){
        jQuery(".comments_item .reply").click(function(){
            jQuery('.reply').removeClass('hidden');
            jQuery('.comments_item form,.comments_item .user-avatar').remove();
            var _parent = jQuery(this).parents('.comments_item');
            var _id_parent = _parent.data('id-parent');
            _parent.append(jQuery('.comments_form').html()).find('form').append('<input type="hidden" value="'+_id_parent+'" name="id_parent" />');
            jQuery(this).addClass('hidden');
        
    });
    
    // voting
    jQuery('.vote-for').each(function(){
        jQuery('.vote-for-minus,.vote-for-plus', jQuery(this)).on('click', function(){
            _this_vote =  jQuery(this);
            if(_this_vote.hasClass('vote-for-plus'))  {
                _class = "plus";
                _inc = 1;
                _this_vote.siblings('.vote-for-minus').remove();
            } else  {
                _class = "minus";
                _inc = -1;
                _this_vote.siblings('.vote-for-plus').remove()
            }
            
            var _vote_wrap = _this_vote.siblings('.vote-container')
            var _total = parseInt(_vote_wrap.text())+_inc;
            if(_total>0) {_vote_wrap.addClass('green').removeClass('gray');}
            else if(_total==0) {_vote_wrap.removeClass('green').addClass('gray');}
            else {_vote_wrap.removeClass('green').removeClass('gray');}
            _vote_wrap.addClass(_class).text(_total);
            id_parent = _this_vote.parents('.vote-for').data('id');
            jQuery(this).remove();
            jQuery.ajax({
                url: '/comments/vote_for/',
                cache: false,
                type: 'POST',
                async: true,
                dataType: 'json',
                data: {
                    action: _class,
                    id_parent: id_parent,
                    ajax:true
                },
                success: function(msg){
                    if(msg.ok){
                        
                    } 
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log("Error: "+textStatus+" "+errorThrown);
                },
                complete: function(){}            
            })
        })
    })
});