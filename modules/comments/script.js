    scrollToElement = function (){ 
        alert(jQuery('#comments-form .not-viewed').html()) 
    }

jQuery(document).ready(function(){
    if(jQuery('#comments-form').length > 0){  
        jQuery(jQuery('#comments-form .list').get().reverse()).each(function(e){
            var _el = jQuery(this);   
           if(_el.parents('.comments-simple-form').length > 0) _parent_class = '.comments-simple-form ';
           else _parent_class = ''; 
            _comment_params =  {url: _el.data('url'), id_parent: _el.data('parent-id'), type: _el.data('type') , feedback: _el.data('feedback'), only_comments: _el.data('only-comments') };
            getPendingContent(_parent_class + '#comments-form .' + _el.attr('class'), '/comments/list/', _comment_params, false, false, false );
        })
        
        jQuery('#comments-form .info-sort-box .sorting span').on('click', function(){       
            var _val = 1;
            jQuery(this).siblings('span,a').removeClass('active').removeClass('up').removeClass('down');
            if(jQuery(this).hasClass('down') || !(jQuery(this).hasClass('up'))) {
                jQuery(this).removeClass('down').addClass('up');
                _val = jQuery(this).data('down-value');
            } else {
                jQuery(this).removeClass('up').addClass('down')
                _val = jQuery(this).data('up-value');
            }
            getPendingContent('#comments-form .list', '/comments/list/?sortby='+_val, _comment_params, false, false, false );    
            return false;
        })
        
        var _el = jQuery('#comments-form .list');
        // voting
        jQuery(_el).on('click', '.vote-for-minus,.vote-for-plus', function(){
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
        
        //send message
        jQuery(_el).on('click', 'button', function(){
            
            var _values = {};
            var _error = false;
            var _this = jQuery(this);
            var _textarea = _this.parent('.info-wrap').siblings('textarea');
            _values['text'] = _textarea.val();
            _values['id_comment_parent'] = parseInt(_this.parents('.item').attr('data-id-parent'));
            _values['id_comment_answer'] = parseInt(_this.parents('.item').attr('data-id-answer'));
            _values['url'] = _el.data('url');
            _values['id_parent'] = _el.data('parent-id');
            _values['type'] = _el.data('type');
            _this.siblings('textarea,input').each(function(){
                jQuery(this).removeClass('red-border');
                _value = jQuery(this).val();
                _name = jQuery(this).attr('name');
                if( (
                        (
                            _value == '' || _value == 0 || 
                                (_name == 'comment_text' && _value.length <= 6 )
                        ) && _name != 'author_email'
                    ) || (
                        (_name == 'author_email' && (_value.length > 0 && _value.match(/([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/) == null) ) 
                    )
                ){
                    _error = true;
                    jQuery(this).addClass('red-border');
                } else _values[_name] = _value;
                
            })
            if(_error == true) return false;
            jQuery.ajax({
                url: '/comments/add/',
                cache: false,
                type: 'POST',
                async: true,
                dataType: 'json',
                data: _values,
                success: function(msg){
                    if(msg.ok){
                        var _el = jQuery('#comments-form .not-viewed').length > 0 ? jQuery('#comments-form .not-viewed') : jQuery('#comments-form .list');
                        getPendingContent('#comments-form .list', '/comments/list/', {url: _el.data('url'), id_parent: _el.data('parent-id'), type: _el.data('type') }, false, false, false, setTimeout(function(){ jQuery('html, body').animate({scrollTop:  _el.offset().top - 35}, 400) }, 400 ) );
                    } 
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log("Error: "+textStatus+" "+errorThrown);                                                                                                                 
                },
                complete: function(){}            
            })
            
        })

        // reply to comment
        jQuery(_el).on('click', '.reply', function(){        
            jQuery( '.reply' ).not(this).removeClass('active');
            jQuery(this).toggleClass('active');
            if(!jQuery(this).hasClass('active'))  jQuery('.item .form').remove();
            else {
                jQuery('.item .form', _el).remove();
                var _wrap = jQuery(this).parents('.item');
                var _form = _el.find('.form');
                var _name = _wrap.find('.username').text();
                _wrap.append('<div class="form">' + _form.html() + '</div>');
                var _textarea = jQuery('textarea', _wrap);
                _textarea.val(_name+', ');
             
                var resizeTextarea = function(el) {
                    jQuery(el).css('height', 'auto').css('height', el.scrollHeight);
                };
                _textarea.on('keyup input', function() { 
                    resizeTextarea(this); }).removeAttr('data-autoresize');
                    }
                })
        
       jQuery(document).on('click', function(e){
           if(jQuery(e.target).parents('#comments-form').length == 0) jQuery('.item', _el).removeClass('active');
        })
        
           
    }	
   
   
    
   
});

 