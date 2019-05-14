/**
* Address selector jQuery plugin
* 
* Входные данные:
* по умолчанию читаются из аттрибутов data-id, data-district_id, data-subway_id тэга, к которому применен плагин
* если в опциях переданы селекторы соответствующих тегов, то значения берутся из них
* 
* Результат:
* по умолчанию записывается в аттрибуты data-id, data-district_id, data-subway_id тэга, к которому применен плагин
* если в опциях были переданы селекторы соответствующих тегов, то значения записываются и в них
* 
*/
if(jQuery)(function(window, document, $, undefined){
    $.fn.photogallery = function(opts) {
        var defaults = {
            click_counter           :      false,                     /* счетчик кликов*/
            index                   :   0,  
            photos_list             :   new Array(),  
            photos_ids              :   new Array(),  
            gpid                    :   0,  
            gptype                  :   '',  
            title                   :   '',  
            page_title              :   '',  
            photo_title             :   '',  
            counter                 :   0,          
            scroll_step             :   0,    
            already_load            :   true,
            big_image_wrap          : jQuery('<div id="gallery-box-expanded">'
                                            +'<div id="gallery-box-expanded-wrapper"></div>'
                                            +'<div id="gallery-box-expanded-content">'
                                                +'<div id="gallery-box-expanded-container"></div>'
                                                +'<a class="closebutton">Закрыть</a>'
                                                +'<div id="gallery-box-title"></div>'
                                                +'<div class="scl-buttons"></div>'
                                            +'</div>'
                                        +'</div>'),
            no_direct               :      false,
            wide_gallery : $(this).hasClass('wide-gallery'), 
            bottom_arrow : $('span.bottom-arrow', $(this)), 
            right_arrow : $('span.right-arrow', $(this)), 
            top_arrow : $('span.top-arrow', $(this)), 
            left_arrow : $('span.left-arrow', $(this))
        };
        var options = $.extend(defaults, opts || {});
        var active_element = null;                          /* Элемент, к которому назначен вызов addrselector */
        var current_item_data = null;                       /* Информация о текущем элементе */
        
        /* функция стартовой инициализации */
        var start = function(){

            getGPvars();

            var _changed_photo = jQuery('.big-image img', active_element);
            _changed_photo.css({'opacity':'1'});            

            //get payed format
            options.correct_index = 0; 
            /* галерея фоток объекта */
            if(jQuery('.thumbs-list a',active_element).length){
                
                jQuery('.thumbs-list a',active_element).each(function(){
                    if(typeof jQuery('.thumbs-list a.active').attr('href')!='undefined'){
                        options.photos_list.push(jQuery(this).attr('href'));
                        options.photos_ids.push(jQuery(this).attr('data-id'));
                        options.index = options.photos_list.indexOf(jQuery('.thumbs-list a.active').attr('href'));
                        jQuery('<img/>')[0].src = jQuery('.thumbs-list a.active').attr('href');        
                        jQuery('<img/>')[0].src = jQuery('.thumbs-list a.active').attr('href').replace(options.wide_gallery == true ? '/very_big/' : '/big/','/med/');        
                    }
                })
                if(options.photos_list.length <= 1){
                    jQuery('.right-arrow', active_element).hide();
                    jQuery('.left-arrow', active_element).hide();
                }
            } else if(jQuery('.big-image > a',active_element).length){
                options.photos_list.push(jQuery('.big-image > a',active_element).attr('href'));
                options.photos_ids.push(jQuery('.big-image > a',active_element).attr('data-id'));
                jQuery('.right-arrow', active_element).hide();
                jQuery('.left-arrow', active_element).hide();
                jQuery('<img/>')[0].src = jQuery('.big-image > a',active_element).attr('href');        
                jQuery('<img/>')[0].src = jQuery('.big-image > a',active_element).attr('href').replace(options.wide_gallery == true ? '/very_big/' : '/big/','/med/');        
                    
            }
            //клик по развернутой фотографии
            options.big_image_wrap.on("click", ".left-arrow,.right-arrow", function(){
                if(jQuery(this).hasClass('left-arrow')) --options.index;
                 else ++options.index;
                 if(options.index<0) options.index = options.photos_list.length - 1;
                 if(typeof(options.photos_list[options.index])=='undefined') options.index = 0;
                 if(jQuery(this).parents('.big-image').length == 0) changeBigPhoto(false);    
                 //если была верхняя картинка, то меняем ее
                 if (jQuery('.big-image').length){
                     options.click_counter = true;
                     changePhoto();
                 } 
            });
            jQuery('.big-image .left-arrow, .big-image .right-arrow', active_element).on('click', function(){
                if(jQuery(this).hasClass('left-arrow')) --options.index;
                 else ++options.index;
                 if(options.index<0) options.index = options.photos_list.length - 1;
                 if(typeof(options.photos_list[options.index])=='undefined') options.index = 0;
                 if(jQuery(this).parents('.big-image').length == 0) changeBigPhoto(false);    
                 //если была верхняя картинка, то меняем ее
                 if (jQuery('.big-image').length){
                     options.click_counter = true;
                     changePhoto();
                 } 
            })
            
            options.big_image_wrap.on("click", ".closebutton,#gallery-box-expanded-wrapper",function(){ 
                 options.big_image_wrap.fadeOut(300);
                 jQuery('html').css({'height':'100%'});    
            });
            
            //клик по верхней картинке - разворачиваем ее в большую
            jQuery('.big-image .expand',active_element).on('click',function(){
                if(options.index < 0) options.index = 0;
                //добавляем options.big_image_wrap в конец body (если там еще нет другого такого же). после этого экран затемнен, но фото еще нет
                jQuery('body').append(options.big_image_wrap);
                options.big_image_wrap.fadeIn(300);
                changeBigPhoto(true);//устанавливаем большую фотографию
                return false;
            })
            
            jQuery('.thumbs-list a', active_element).on('click', function(){//клик по фотографии из списка - сменяем верхнюю
                options.index = options.photos_ids.indexOf(jQuery(this).attr('data-id'));
                jQuery('.thumbs-list a', active_element).removeClass('active');
                jQuery(this).addClass('active');
                //если большая картнка есть, то это фотоотчет или карточка объекта - меняем верхнюю фотографию
                if (jQuery('.big-image').length){
                    options.click_counter = true;
                    changePhoto();
                }
                //если нет, то это дипломы - сразу разворачиваем большую фотографию
                else{
                    jQuery('body').append(options.big_image_wrap);
                    options.big_image_wrap.fadeIn(300);
                    changeBigPhoto(true);//устанавливаем большую фотографию
                }
                return false;
            });
            
            if(options.gpid>0) {
                jQuery(".thumbs-list a[data-id='"+options.gpid+"']").click();
                jQuery('.big-image .expand').click();
            }
            
            //прокрутка превьющек - горизонтально
            options.right_arrow.on('click',function(){
                var _photos_length = options.photos_list.length;
                var _a_width = jQuery(".thumbs-list a").width()+8;
                if(options.scroll_step + 3 + options.correct_index < _photos_length){
                   ++options.scroll_step;
                   jQuery('.thumbs-list',active_element).css({left:-_a_width*options.scroll_step})  ;
                   options.left_arrow.removeClass('inactive');
                   if(options.scroll_step + 3 + options.correct_index == _photos_length) options.right_arrow.addClass('inactive');
                   else  options.right_arrow.removeClass('inactive');
                }  
                return false;
            })
            options.left_arrow.on('click',function(){
                var _photos_length = options.photos_list.length;
                var _a_width = jQuery(".thumbs-list a").width()+8;
                if(options.scroll_step > 0){
                   --options.scroll_step;
                   jQuery('.thumbs-list',active_element).css({left:-_a_width*options.scroll_step})  ;
                   options.right_arrow.removeClass('inactive');
                   if(options.scroll_step == 0) options.left_arrow.addClass('inactive');
                   else  options.left_arrow.removeClass('inactive');
                } 
                return false;
            })   
            
            //прокрутка превьющек - вертикально
            options.bottom_arrow.on('click',function(){
                var _photos_length = options.photos_list.length;
                var _a_height = jQuery(".thumbs-list a" ,active_element).height()+10;
                if(options.scroll_step + 4 + options.correct_index < _photos_length){
                   ++options.scroll_step;
                   options.top_arrow.removeClass('inactive');
                   if(options.scroll_step + 4 + options.correct_index == _photos_length) {
                       options.bottom_arrow.addClass('inactive');
                       jQuery('.thumbs-list' ,active_element).css({'bottom':(options.scroll_step - 1)*90+43, 'top':'auto'})  ; 
                   }
                   else  {
                       options.bottom_arrow.removeClass('inactive');
                       jQuery('.thumbs-list' ,active_element).css({top:-_a_height*options.scroll_step, 'bottom': 'auto'})  ;
                   }
                }  
                return false;
            })
            options.top_arrow.on('click',function(){
                var _photos_length = options.photos_list.length;
                var _a_height = jQuery(".thumbs-list a" ,active_element).height()+10;
                if(options.scroll_step > 0){
                   --options.scroll_step;
                   var _max_offset = jQuery(".thumbs-list" ,active_element).height() - _a_height*options.scroll_step;
                   options.bottom_arrow.removeClass('inactive');
                   if(options.scroll_step == 0) {
                       options.top_arrow.addClass('inactive');
                       jQuery('.thumbs-list' ,active_element).css({'bottom':'auto', 'top':'0'})  ; 
                   } else  {
                       options.top_arrow.removeClass('inactive');
                       jQuery('.thumbs-list' ,active_element).css({top:-_a_height*options.scroll_step, 'bottom':'auto'})  ;
                   }
                   
                } 
                return false;
            })   
            
            //показ div pluso.ru со списком соцсетей выше gallery-box-expanded-wrapper
            jQuery(document).on("click mouseover mousemove", "a.pluso-more",function(){
                $('.pluso-box').css({'z-index':'99993'});
            });          
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 27: options.big_image_wrap.find('.closebutton').click(); break;     // esc
                    case 37: options.big_image_wrap.find('.left-arrow').click(); break;             // <-
                    case 39: options.big_image_wrap.find('.right-arrow').click(); break;            // ->
                }
            });                            
        }
        //замена верхней фотографии на исх. странице
        var changePhoto = function(){
           if(options.click_counter == true){
               options.click_counter = false;
               var _href =  options.photos_list[options.index];
               var _id =  options.photos_ids[options.index];
               jQuery('.thumbs-list a', active_element).removeClass('active');
               jQuery('a[data-pos="'+options.index+'"]', active_element).addClass('active');
               var _changed_href =  options.photos_list[options.index].replace('/big/','/med/');//заменяем фото, которое было на среднее(нужно только при развороте)
               jQuery('.big-image',active_element).children('a').attr('href',_href).css('background-image',"url("+_href+")").attr('data-id',_id);
               jQuery('.big-image',active_element).append("<img src='"+_changed_href+"' class='changed-photo' />")
                jQuery('.big-image img.changed-photo',active_element).on("load", function() {
                   var _changed_photo = jQuery('.changed-photo');
                   _changed_photo.css({'margin-left':'-'+_changed_photo.width()/2+'px', 'margin-top':'-'+_changed_photo.height()/2+'px'}).removeClass('changed-photo').addClass('changed');
                   jQuery(this).addClass('fade-out');
                   _changed_photo.animate({
                      }, 300, function() {
                        jQuery(this).siblings('img').remove();// Animation complete.
                   });
                }).each(function() {
                  if(this.complete) jQuery(this).load();
                });
                 
                 if(jQuery('.gallery-box .big-image .video').length > 0){
                     if(options.index == 0) jQuery('.gallery-box .big-image .video').fadeIn();
                     else jQuery('.gallery-box .big-image .video').fadeOut();
                 }
                
                //прокрутка фотографий
               if(typeof options.bottom_arrow == 'object' && typeof options.top_arrow == 'object'){
                   if(options.index > options.scroll_step && options.index>2){
                       options.bottom_arrow.click();
                   } else if(options.index <= options.scroll_step && options.scroll_step > 0){
                       options.top_arrow.click();
                   } else return false;
               }                   
               else if(typeof options.right_arrow == 'object' && typeof options.right_arrow == 'object'){
                   if(options.scroll_step<options.index-2 - options.correct_index) {
                        var _a_width = jQuery(".thumbs-list a",active_element).width()+8;           
                         options.scroll_step = options.index - 1  - options.correct_index;
                         jQuery('.thumbs-list',active_element).css({left:-_a_width*options.scroll_step})  ;
                   }
                   if(options.index > options.scroll_step && options.index>1){
                       options.right_arrow.click();
                   } else if(options.index <= options.scroll_step && options.scroll_step > 0){
                       options.left_arrow.click();
                   } else return false;
               }
           } else {
               return false;
           }     
        }     
        


     
        //эта функция используется при первой и всех последующих отрисовках больших картинок
        var changeBigPhoto = function(first_instance){
            if(options.wide_gallery == true) return false;
            ++options.counter;
            var _bigPhoto = options.big_image_wrap.children(".gallery-box-expanded-image img");
                _title = options.title;
                if(_title == ''){
                    var _title = options.title;
                    jQuery('h1.topspace, h1.mtitle, #relocation').each(function(){
                        if(jQuery(this).text()!='') {
                            _title = options.title + jQuery(this).text() + '. ';
                            return true;
                        }
                    });
                }
            var _container = options.big_image_wrap.find("#gallery-box-expanded-container");
            options.gpid = parseInt(options.photos_ids[options.index],10);
            
            if(jQuery('.gallery-box .big-image .video').length > 0){
                if(options.index == 0){
                    getPendingContent('#gallery-box-expanded-container .gallery-box-expanded-image .photo', '/video/block/'+jQuery('.gallery-box .big-image .video').data('type')+'/' + jQuery('.gallery-box .big-image .video').data('id')+'/');
                    return false;
                } 
            }
            
            var _url = '/photos/'+options.gptype+'/'+options.gpid+'/';
            _container.addClass('wait');
            
            jQuery.ajax({
                type: "POST", async: true, dataType: 'json', cache: true, url: _url,
                data: {ajax: true, title: _title+' | '+options.page_title, no_direct: options.no_direct},
                success: function(msg){
                    
                    if(typeof(msg)=='object') {
                        _container.html(msg.html);
                        options.big_image_wrap.find('#gallery-box-title').text('( '+(options.index+1)+' из '+options.photos_list.length + ' ) '+ _title);
                        jQuery('div.gallery-box-expanded-sidebar').append('<div id="gallery-box-expanded-sidebar-'+options.counter+'"></div>');
                        getPendingContent(['#gallery-box-expanded-sidebar-'+options.counter],     ['/banners/right/photogallery-top/'+options.counter+'/']);
                        if(options.photos_list.length <= 1){
                            jQuery('.right-arrow', active_element).hide();
                            jQuery('.left-arrow', active_element).hide();
                        }
                        jQuery('.thumbs-list a', active_element).removeClass('active');
                        jQuery(".thumbs-list a").eq(options.index).addClass('active');
                    } else alert('Ошибка!');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    alert('Ошибка связи с сервером!');
                },
                complete: function(){
                    _container.removeClass('wait');
                }
            });
            
            if ($('.scl-buttons').text() == '') getPendingContent(['.scl-buttons'], ['/banners/social_buttons_photogallery']); //для pluso.ru
            
            setGPvar();
        }
        
        var getGPvars = function(_location){
            if(options.gptype != '') return false;
            if(arguments.length<1) {
                if(jQuery('#relocation').length!=0) _location = jQuery('#relocation').data('url').toString(); 
                else _location = window.location.pathname.toString();            
            }
            options.gpid = getGpid(_location);
            
            switch(true){
                case /^\/news\//.test(_location):
                    options.page_title = 'Новости.'; options.gptype = 'news'; break;
                case /^\/diploms\//.test(_location):
                    options.page_title = 'Дипломы.'; options.gptype = 'diploms'; break;
                case /^\/articles\//.test(_location):
                    options.page_title = 'Статьи.'; options.gptype = 'articles'; break;
                case /^\/calendar\//.test(_location):
                    options.page_title = 'Мероприятия.'; options.gptype = 'calendar_events'; break;
                case /^\/cottages\//.test(_location):
                case /^\/cottedzhnye_poselki\//.test(_location):
                    options.page_title = 'Коттеджные поселки.'; options.gptype = 'cottages'; break;
                case /^\/zhiloy_kompleks\/progress\//.test(_location):
                    options.page_title = 'Ход строительствы жилых комплексов.'; options.gptype = 'housing_estates_progresses'; break;
                case /^\/build\/housing_estates\//.test(_location):
                case /^\/rsti\//.test(_location):
                case /^\/arenda-ofisa-ot-sobstvennika\//.test(_location):
                case /^\/kommercheskie-pomescheniya-life-primorskiy\//.test(_location):
                case /^\/dom_na_frunzenskoy\//.test(_location):
                case /^\/zhiloy_kompleks\//.test(_location):
                    options.page_title = 'Жилые комплексы.'; options.gptype = 'housing_estates'; break;
                case /^\/commercial\/business_centers\//.test(_location):
                case /^\/business_centers\//.test(_location):
                case /^\/members\/office\/business_centers\/edit\//.test(_location):
                    options.page_title = 'Бизнес-центры.'; options.gptype = 'business_centers'; break;
                case /^\/zhiloy_kompleks\//.test(_location):
                    options.page_title = 'Жилые комплексы.'; options.gptype = 'housing_estates'; break;
                case /^\/calendar\//.test(_location):
                    options.page_title = 'Мероприятия.'; options.gptype = 'calendar_events'; break;
                case /^\/live\/exclusive\//.test(_location):
                case /^\/build\/objects\//.test(_location):
                case /^\/country\/cottage\//.test(_location):
                case /^\/country\/complex\//.test(_location):
                case /^\/commercial\/exclusive\//.test(_location):
                case /^\/elite\/exclusive\//.test(_location):
                case /^\/inter\/exclusive\//.test(_location):
                case /^\/garage\//.test(_location):
                case /^\/mortgage\//.test(_location):
                case /^\/invest\//.test(_location):
                    options.page_title = 'Спецпредложения.'; options.gptype = 'spec_offers_objects'; break;
                case /^\/live\//.test(_location):
                    options.page_title = 'Жилая недвижимость.'; options.gptype = 'live'; break;
                case /^\/build\//.test(_location):
                    options.page_title = 'Строящаяся недвижимость.'; options.gptype = 'build'; break;
                case /^\/build_complexes\//.test(_location):
                    options.page_title = 'Жилые комплексы.'; options.gptype = 'build_complexes'; break;
                case /^\/commercial\//.test(_location):
                    options.page_title = 'Коммерческая недвижимость.'; options.gptype = 'commercial'; break;
                case /^\/country\//.test(_location):
                    options.page_title = 'Загородная недвижимость.'; options.gptype = 'country'; break;
                case /^\/inter\//.test(_location):
                    options.page_title = 'Зарубежная недвижимость.'; options.gptype = 'inter'; break;
                case /^\/galleries\//.test(_location):
                    options.page_title = 'Фотогалереи.'; options.gptype = 'galleries'; break;
                default:
                    options.gpid = 0; break;
            }
        }
        var getGpid = function(_location){
            var _id = 0;
            var matches = _location.toString().match(/#photo(\d+)/);
            if(matches) {
                _id = parseInt(matches[1],10);
            } 
            return _id;
        }
        var setGPvar = function(_location){
            if(options.gpid>0 && typeof window.location.match!='indefined'){    
                new_href = window.location.href.replace(/#photo\d+$/, '#photo'+options.gpid);
                if(new_href != window.location) {
                    window.location = new_href;
                }
            }
        }        
        return this.each(function(){
            active_element = $(this);
            getGPvars();
            start();
            return false;
        })
    }
})(window, document, jQuery);