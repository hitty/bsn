jQuery(document).ready(function(){
    /*функция для кнопки проигрывания аудиозаписи звонка из admin/sale/stats*/
    jQuery('div.play-call-record').click(function(){
        //если проигрыватель скрыт,
        if ('/'+jQuery('#'+parseInt(this.id)+'_link')[0].innerHTML != jQuery('#a_player').attr('src')){
            //скрываем все остальные проигрыватели, меняем кнопки на ненажатые
            jQuery('.play-call-record').removeClass('active');
            //делаем видимым текущий
            jQuery('#a_player').css('visibility','visible');
            jQuery('#a_player').show();
            jQuery('#'+parseInt(this.id)+'_audio_start').addClass('active');
            jQuery('#a_player').attr("src",'/'+jQuery('#'+parseInt(this.id)+'_link')[0].innerHTML);
            var oAudio = document.getElementById('a_player');
            oAudio.load();
            oAudio.play();
        }
        else{
            var oAudio = document.getElementById('a_player');
            oAudio.pause();
            jQuery('#'+parseInt(this.id)+'_audio_start').css('background','url(/img/layout/audio-1.png) center no-repeat');
            jQuery('#'+parseInt(this.id)+'_audio_start').css('background-size','100% auto');
            jQuery('#'+parseInt(this.id)+'_audio').css('visibility','hidden');
            jQuery('#a_player').attr("src","");
            jQuery('#a_player').hide();
        }
        
        
    });     
    //инициализация и управление датами показа
    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker({
          timepicker:false,
          format:'d.m.Y',
          onChangeDateTime:function(dp,$input){
              $input.attr('value',$input.val())
              if($input.attr('id') == 'date_end')  {
                  jQuery('.ov-timer').attr('data-date-end',$input.val());
                  if(typeof refreshIntervalId!='undefined') clearInterval(refreshIntervalId);
                  setDateParams(jQuery('.ov-timer'), jQuery('.ov-timer').attr('data-date-end'));
              }
              $input.blur();
          }
        });

    }
    
    /*функция для кнопки скачивания аудиозаписи звонка из admin/sale/stats*/
    jQuery('div.download-call-record').click(function(){
        var audio_elem_id="#"+parseInt(this.id)+"_audio";
        var url='download/'+parseInt(this.id)+'/';
        window.open(url,'_blank');
    });
    //для функционирования переключателя неделя/месяц на странице sale/stats
    jQuery("#csf-week").click(function(){     
        //если кнопка была нажата, отжимаем ее, удаляем cookie
        if (getCookie('fixed_time_period') == "week"){
            setCookie('fixed_time_period', "",15,'/');
            jQuery('#csf-week')[0].checked=false;
            //убираем соответствующий get-параметр из url
            var _get = window.location.href.split('?')[1].split('&');
            for (var key in _get){
                if (_get[key].search('f_time_period')!=-1)_get.splice(key, 1);
            }
            _get = '?'+_get.join('&');
            window.location.href = window.location.href.split('?')[0]+_get;
        }else{
            setCookie('fixed_time_period', "week", 15,'/');
            jQuery('#csf-week')[0].checked=true;
            _url = window.location.href.split('?');
            if (_url[1]!=null) window.location.href = window.location.href.concat("&f_time_period=week");
            else window.location.href = window.location.href.concat("?f_time_period=week");
        }
        
    });
    jQuery("#csf-month").click(function(){
        //если кнопка была нажата, отжимаем ее, удаляем cookie
        if (getCookie('fixed_time_period') == "month"){
            setCookie('fixed_time_period', "",15,'/');
            jQuery('#csf-month')[0].checked=false;
            //убираем соответствующий get-параметр из url
            var _get = window.location.href.split('?')[1].split('&');
            for (var key in _get){
                if (_get[key].search('f_time_period')!=-1)_get.splice(key, 1);
            }
            _get = '?'+_get.join('&');
            window.location.href = window.location.href.split('?')[0]+_get;
        }else{
            setCookie('fixed_time_period', "month", 15,'/');
            jQuery('#csf-month')[0].checked=true;
            _url = window.location.href.split('?');
            if (_url[1]!=null) window.location.href = window.location.href.concat("&f_time_period=month");
            else window.location.href = window.location.href.concat("?f_time_period=month");
        }
    });
    
    //для сохранения нажатых кнопок переключателя на странице результата
    if (getCookie('fixed_time_period') == "week"){
        jQuery('#csf-week')[0].checked=true;
    }else
    if (getCookie('fixed_time_period') == "month"){
        jQuery('#csf-month')[0].checked=true;
    }else{
        setCookie('fixed_time_period', "",15,'/');
        jQuery('#csf-month')[0].checked=false;
        jQuery('#csf-week')[0].checked=false;
    }
    
    //для переключения количества элементов на странице sale/stats
    jQuery("div.st-output a:not(.output-active)").click(function(){
        setCookie('View_count', this.text, 15,'/');
        window.location.href = window.location.href;
    });
    
    //для контекстного меню добавления тегов
    jQuery('.tag').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        if ($(e.target).is('label')){
            $('.tagmenu').css('visibility','visible');
            $('.tagmenu').show();
            $('.tagmenu').css({top:e.pageY,left:e.pageX});
            $('.tagmenu').css('z-index','4');
        }
        //смотрим, какие теги уже были выбраны и ставим галочки в меню
        var _tag_ids = Array();
        $(e.target).parent().parent().children().each(function(){
          _tag_ids.push($(this).attr('id'));
        });
        if (_tag_ids[0]!=null && _tag_ids[0]!="")
            for (var _key in _tag_ids){
                $('#'+_tag_ids[_key]+'_tag')[0].checked = true;
            }
        $('.hidden-call-id')[0].id = $(e.target).parent().parent().parent()[0].id;
        $('#88_tag').blur();
        return true;
    });
    jQuery('.tagmenu').on('blur mouseleave',function(){
        $('.tagmenu').css('visibility','hidden');
        $('.tagmenu').css('z-index','0');
        //производим сохранение тегов, первый элемент - id звонка
        var _tag_data = Array($('.hidden-call-id')[0].id);
        $('.tag-checkbox').each(function(){
            if ($(this)[0].checked)
                _tag_data.push(parseInt($(this)[0].id));
        });
        _target = window.location.href.split('?')[0].concat('edit_tags/');
        $.ajax({
          type: 'POST',
          url: _target,
          data: 'data='+JSON.stringify(_tag_data),
          success: function(data){
              var _row_id = _tag_data[0];
              _tag_data.splice(0,1);
              var _existed_tags_ids = Array();
              jQuery('tr#'+_row_id).children('#tags').children().each(function(){
                  _existed_tags_ids.push($(this).attr('id'));
              });
              //перебираем переданные галочки, добавляем новые теги
              for (var _key in _tag_data){
                  //если это новый тег, то добавляем его
                  if (jQuery.inArray(_tag_data[_key].toString(),_existed_tags_ids) == -1){
                      var spanTag = document.createElement("span");
                      spanTag.setAttribute("id",_tag_data[_key]);
                      if (_tag_data[_key]==88 || _tag_data[_key]==480 || _tag_data[_key]==487){
                          spanTag.setAttribute("class","tag "+"tag-"+_tag_data[_key]);
                      }else{
                          spanTag.setAttribute("class","tag");
                      }
                      jQuery('tr#'+_row_id).children('#tags').append(spanTag);
                      //создаем текст тега
                      var spanTag_text = document.createElement("label");
                      spanTag_text.setAttribute("class","tag-text");
                      if (_tag_data[_key]!=88 && _tag_data[_key]!=480 && _tag_data[_key]!=487){
                          spanTag_text.innerHTML=jQuery('#'+_tag_data[_key]+'_title').first().text();
                      }
                      //добавляем внутрь нового тега
                      jQuery('tr#'+_row_id).children('#tags').children('#'+_tag_data[_key]).append(spanTag_text);
                      //привязываем обработку клика
                      jQuery('tr#'+_row_id).children('#tags').children('#'+_tag_data[_key]).children('.tag-text').bind("click",function(e){
                          e.preventDefault();
                          e.stopPropagation();
                          if ($(e.target).is('label')){
                              $('.tagmenu').css('visibility','visible');
                              $('.tagmenu').show();
                              $('.tagmenu').css({top:e.pageY,left:e.pageX});
                              $('.tagmenu').css('z-index','4');
                          }
                          //смотрим, какие теги уже были выбраны и ставим галочки в меню
                          var _tag_ids = Array();
                          $(e.target).parent().parent().children().each(function(){
                            _tag_ids.push($(this).attr('id'));
                          });
                          if (_tag_ids[0]!=null)
                              for (var _key in _tag_ids){
                                 $('#'+_tag_ids[_key]+'_tag')[0].checked = true;
                              }
                          $('.hidden-call-id')[0].id = $(e.target).parent().parent().parent()[0].id;
                          return true;
                      });
                      
                  }
              }
              //удаляем старые теги, у которых убрали галочку
              for(var _key in _existed_tags_ids){
                  //если для тега галочка не поставлена, удаляем элемент
                  if (jQuery.inArray(parseInt(_existed_tags_ids[_key]),_tag_data) == -1){
                      jQuery('tr#'+_row_id).children('#tags').children('#'+_existed_tags_ids[_key]).remove();
                  }
              }
              //если тегов не осталось, добавляем тег "Добавить"
              if (jQuery('tr#'+_row_id).children('#tags').children().length==0){
                  //тег
                  var spanTag = document.createElement("span");
                  spanTag.setAttribute("class","tag tag-add");
                  jQuery('tr#'+_row_id).children('#tags').append(spanTag);
                  //добавляем внутрь label
                  var spanTag_text = document.createElement("label");
                  spanTag_text.setAttribute("class","tag-text");
                  spanTag_text.innerHTML="Добавить";
                  jQuery('tr#'+_row_id).children('#tags').children('.tag.tag-add').append(spanTag_text);
                  //привязываем обработку клика
                  jQuery('tr#'+_row_id).children('#tags').children('.tag.tag-add').children('.tag-text').bind("click",function(e){
                      e.preventDefault();
                      e.stopPropagation();
                      if ($(e.target).is('label')){
                          $('.tagmenu').css('visibility','visible');
                          $('.tagmenu').show();
                          $('.tagmenu').css({top:e.pageY,left:e.pageX});
                          $('.tagmenu').css('z-index','4');
                      }
                      //смотрим, какие теги уже были выбраны и ставим галочки в меню
                      var _tag_ids = Array();
                      $(e.target).parent().parent().children().each(function(){
                        _tag_ids.push($(this).attr('id'));
                      });
                      if (_tag_ids[0]!=null)
                          for (var _key in _tag_ids){
                             $('#'+_tag_ids[_key]+'_tag')[0].checked = true;
                          }
                      $('.hidden-call-id')[0].id = $(e.target).parent().parent().parent()[0].id;
                      return true;
                  });
              }
              //если теги есть, удаляем тег Добавить
              else{
                  //если единственный оставшийся тег не "Добавить", удаляем
                  if(jQuery('tr#'+_row_id).children('#tags').children().length>=2)
                    jQuery('tr#'+_row_id).children('#tags').children('.tag.tag-add').remove();
              }
          },
          error: function(textStatus){
              //alert(textStatus);
              //document.location.reload();
          }
        });
        return true;
    });
});