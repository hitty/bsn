jQuery(document).ready(function(){
   var _wrap = jQuery('.expanded-list');
   if(jQuery('h3',_wrap).length==1) jQuery('h3',_wrap).click();
   jQuery('h3').on('click',_wrap, function(){
        
       var _this = jQuery(this); 
        var _list = _this.next('div.expanded-list-items');
        _this.siblings('h3').removeClass('on');
        _list.siblings('.expanded-list-items').slideUp();
        _list.slideToggle();
        _this.toggleClass('on');
   });
        
   jQuery('.vote-for .vote-button').click(function(e){
       var _target = jQuery(e.target);
       if(_target.is('a')){
            window.open(
              e.target,
              '_blank' 
            );
           return false;    
       }
       
       var _this = jQuery(this).parents('.item');
       var curr_expanded_list = _this.parent('.expanded-list-items ');

       if(_this.parents('div.expanded-list-items').hasClass('vote-for')){
           var _id = _this.attr('id');
           var konkurs_name = _this.attr('name');
           jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/konkurs/'+konkurs_name+'/voting/',
                data: {ajax: true, id:_id},
                success: function(msg){
                    if(typeof(msg)=='object') {
                        if(msg.ok) {
                            var all_votes =  parseInt(curr_expanded_list.attr("data-all-votes"),10)+1;  
                            curr_expanded_list.attr("data-all-votes",all_votes);
                            var pr_b = _this.find(".progressbar");
                            pr_b.attr("data-current-votes",(parseInt(pr_b.attr("data-current-votes"),10)+1));
                            var prb_lines = curr_expanded_list.find(".progressbar-fill");
                            var max_length = jQuery('.expanded-list .item .stats').width();
                            prb_lines.each(function(indx,element){               // Пересчет прогрессбаров всех элементов категории
                               var ratio = parseInt(jQuery(this).parent(".progressbar").attr("data-current-votes"),10)/all_votes;    // Процент проголосовавших
                               var new_width = max_length*ratio;   
                               jQuery(this).css({'width':new_width});
                            });
                            _this.addClass('voted');
                            _this.children(".vote-button").after('<span class="alreay-voted">Ваш голос учтен</span>');
                            _parent = _this.parents('.vote-for');
                            _parent.removeClass('vote-for');
                            _parent.prev('h3').children('span').attr('class','voted').text('Ваш голос отдан ' + _this.find('.title').text());
                            jQuery(".vbplace").remove();
                        } else alert('Ошибка: Вы не можете голосовать за данную номинацию');
                    } else alert('Ошибка!');
                },
                error: function(){
                    alert('Server connection error!');
                },
                complete: function(){
                }
            });
       }
       return false;
       
   }) 
});