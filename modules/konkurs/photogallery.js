_template = '<div id="gallery-box-expanded">'
                +'<div id="gallery-box-expanded-wrapper"></div>'
                +'<div id="gallery-box-expanded-content">'
                    +'<div class="gallery-box-expanded-image">'
                        +'<div class="left-arrow" style="left: 0px;"><b></b></div>'
                        +'<div class="right-arrow" style="right: 0px;"><b></b></div>'
                        +'<img src="" id="gallery-box-expanded-image" align="center" />'
                    +'</div>'
                    +'<span id="gallery-box-title"></span>'
                    +'<span class="vbplace"></span>'
                    +'<span id="vote-count"></span>'
                    +'<a class="closebutton">Закрыть</a>'
                    +'<p id="txt-describe"></p>'
                    +'<div class="scl-buttons"></div>'
                    +'</div>'
                +'</div>'
            +'</div>';
_vote_button = "<div class='vote-button-container'><input type='button' id='vote-button' value='Отдать свой голос'/></div>";
_index = _vId = _gpid = 0;
_parts = new Array();
_readmore_lnk_txt = '<span id="show-dsc-all"> <a id="show-dsc">Читать дальше...</a></span> ';
_photos_list = [];
getGPvars();

jQuery(document).ready(function(){
    _wrap =  jQuery('.photokonkurs-list');
    
    /* галерея фоток объекта */
    if(jQuery('.vote-item img',_wrap).length){
        jQuery('.vote-item img',_wrap).each(function(index){
              _href = jQuery(this).attr('src');
              _photos_list.push(_href);
              if (jQuery(this).siblings('p').attr('id') == _gpid) _index = _photos_list.length-1;
        })
    }
    
    if (_gpid > 0){
        jQuery('body').append(_template);//добавляем _template в конец body. после этого экран затемнен, но фото еще нет
        changeBigPhoto(true);//устанавливаем большую фотографию
    }
    //клик по ссылке nextbutton
    jQuery(document).on("click", "div.right-arrow",function(){
         _index++;
         if(typeof(_photos_list[_index])=='undefined') _index = 0;
         changeBigPhoto(false);    
    });
    
    //клик по ссылке prevbutton
    jQuery(document).on("click", "div.left-arrow",function(){
         _index--;
         if(_index < 0) _index = _photos_list.length-1;
         changeBigPhoto(false);    
    });
    
    jQuery(document).on("click", "div#gallery-box-expanded-content>.closebutton, div#gallery-box-expanded > div#gallery-box-expanded-wrapper",function(){ 
         jQuery('#gallery-box-expanded').remove();
    });
    
    //клик по верхней картинке - разворачиваем ее в большую
    jQuery('.vote-item img').on('click',_wrap,function(){
        _index = _photos_list.indexOf(jQuery(this).attr('src')); 
        jQuery('body').append(_template);//добавляем _template в конец body. после этого экран затемнен, но фото еще нет
        changeBigPhoto(true);//устанавливаем большую фотографию
    })
    
    //клик по кнопке голосования
    jQuery(document).on("click", "input#vote-button",function(){
        jQuery('p#'+_vId).click();
        jQuery('p#'+_vId).parent().addClass('voted');
        removeVoteButton();
    });
    
    //клик по ссылке "читать дальше"
    jQuery(document).on("click", "a#show-dsc",function(){
        jQuery('span#show-dsc-all').detach();
        jQuery('p#txt-describe').append('<span id="hide-dsc-all">'+_parts[3]+' <a id="hide-dsc">Скрыть.</a></span>');
        jQuery('html, body').animate({scrollTop: jQuery('div#gallery-box-expanded-content').height()/4}, 500); 
    });
    
    //клик по ссылке "скрыть"
    jQuery(document).on("click", "a#hide-dsc",function(){
        jQuery('span#hide-dsc-all').detach();
        jQuery('p#txt-describe').append(_readmore_lnk_txt);
        jQuery('html, body').animate({scrollTop: 0}, 500);        
    });
    
    //обработка клавиатуры
    jQuery(document).keyup(function(e) {
        switch(e.keyCode){
            case 27: jQuery('#gallery-box-expanded').detach(); break;     // esc
            case 37: jQuery('div.left-arrow').click(); break;             // <-
            case 39: jQuery('div.right-arrow').click(); break;            // ->
        }
    });    
});
 

    //эта функция используется при первой и всех последующих отрисовках больших картинок
    function changeBigPhoto(first_instance){

        var _href =  _photos_list[_index].replace('/sm/','/big/');//заменяем фото, которое было на большое(нужно только при развороте)
        var _img = jQuery("img#gallery-box-expanded-image");
        var _gallery_expanded = _img.parents('div#gallery-box-expanded-content');
        //опрделям wrap для активной картинки
        var _img_wrap = jQuery('img[src="'+_photos_list[_index]+'"]').parents('.vote-item');
        _vId = _img_wrap.children('p').attr('id');
        document.location.hash = '#photo'+_vId;//смена номеров фото в uri
        removeVoteButton();   
        addVoteButton();
        
        _img.attr('src',_href);//появилась большая картинка
        _img.css({'max-width':jQuery(window).width()-10,'max-height':jQuery(window).height()-200}).attr('height',jQuery(window).height()-200);
        jQuery(".scl-buttons").css({'top':jQuery(window).height()/2-150});
        
        var _textvote = 'Всего голосов: <strong>'+_img_wrap.children('.wrapper-amount').children('.konkurs-item-amount').text()+'</strong>';
        jQuery('#vote-count').html(_textvote); 
        var _text = _img_wrap.children('span.vote-title').html();
        _parts =  new Array();
        if(_text.length < 255){
            var _re = /(\<strong\>.*\<\/strong\>)(.*)?/i
            _parts = _re.exec(_text);
        } else {
            var _re = /(\<strong\>.*\<\/strong\>)(.{1,260})?\s(.*)?/i
            _parts = _re.exec(_text);
        }
        
        _gallery_expanded.children('#gallery-box-title').html('( '+(_index+1)+' из '+_photos_list.length+' ) '+_parts[1]);
        _gallery_expanded.children('p#txt-describe').empty();
        
        if (typeof _parts[2] != 'undefined')
            _gallery_expanded.children('p#txt-describe').html(_parts[2]);
        if (typeof _parts[3] != 'undefined') 
            _gallery_expanded.children('p#txt-describe').append(_readmore_lnk_txt);
        
        _img.on('load',function(){
            if(first_instance==true){
                jQuery('div#gallery-box-expanded').show();
                var gewidth = 800;
                if (_img.width() > gewidth) gewidth = _img.width(); 
                _gallery_expanded.css({
                    "margin-left":"-"+ gewidth/2 + "px",
                    "width": gewidth
                });
                _img.css({
                    "margin-left": (gewidth-_img.width())/2 +"px"
                });
                var chcss = {
                    "height": _img.height() - 2,
                    "margin-top":_img.offset().top - 50,
                };
                jQuery('.right-arrow').css(chcss);
                jQuery('.left-arrow').css(chcss);
                jQuery('html, body').animate({
                    scrollTop: 0
                }, 500);
            } 
        })
        return false;
    }
    
    function addVoteButton(){
        if (jQuery(".expanded-list-items.vote-for").length > 0){
            jQuery(_vote_button).appendTo(jQuery(".vbplace"));
            jQuery("#vote-count").css({"margin-right":'50px'});
        }
        return false;
    }
        
    function removeVoteButton(){
        jQuery(".vote-button-container").detach();
        jQuery("#vote-count").css({"margin-right":'0px'});        
        return false;
    }
    
    function getGPvars(_location){
        if(arguments.length<1) _location = window.location;
        _gpid = getGpid(_location);
    }
        
    function getGpid(_location){
        var _id = 0;
        var matches = _location.href.match(/#photo(\d+)/);
        if(matches) {
            _id = parseInt(matches[1],10);
        } 
        return _id;
    }    