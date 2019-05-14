function set_phone_styles(){
        jQuery('.comagic-sitephone__button').addClass('button green send').css({'background-image':'none','background':'#a9d71d'});
        jQuery('.comagic-sitephone__button').css({'box-shadow':'none'});
        jQuery('.comagic-sitephone__button').on('mouseenter',function(){
            jQuery(this).css('background','#98c11a');
        });
        jQuery('.comagic-sitephone__button').on('mouseleave',function(){
            jQuery(this).css('background','#a9d71d');
        });
        //changing styles
        jQuery('.comagic-sitephone__header').after('<span class="title">Обратный звонок</span><span>Наши операторы свяжутся с вами в течение 2 минут</span>');
        jQuery('.comagic-sitephone__header').remove();
        
        
        jQuery('.comagic-sitephone__footer').css({'top':'5px','right':'5px','margin':'0px','display':'none'}).children('a').css('color','initial');
    }
jQuery(document).ready(function(){
    
    var _tgb_popup = '<div id="background-shadow-expanded">'
                        +'<div id="background-shadow-expanded-wrapper"></div>'
                        +'<div class="tgb-popup">'+
                            '<a class="closebutton" data-icon="close"></a>'+
                            '<span class="form-title">'+
                                '<span class="info"> <span class="phone"></span><span class="title"></span> </span>'+
                                '<span class="logo"></span>'+
                            '</span>'+
                            '<div class="top-block">'+
                                '<div class="callback-block">'+
                                    '<span class="title">Обратный звонок</span><span>Наши операторы свяжутся с вами в течение 2 минут</span>'+
                                    '<div><input type="text" name="phone" class="phone" placeholder="Ваш номер телефона"><span class=""></span><button class="green send">Перезвоните мне</button></div>'+
                                '</div>'+
                                '<div class="promotion-block">'+
                                    '<div class="tgb"></div>'+
                                    '<div class="company-page">'+
                                        '<span class="block-title">Страница акции</span>'+
                                        '<span>На сайте компании</span>'+
                                        '<div class="button">Перейти</div>'+
                                    '</div>'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                     '</div>';
	
    if(jQuery('.right-tgb').length > 0) jQuery('.callback-show').css({'left':parseInt(jQuery('.right-tgb').offset().left - 70) + 'px','top':parseInt(jQuery('.central-wrapper').offset().top + 20) + 'px'}).addClass('active');
    jQuery(document).on('click', '.tgb.with-popup, .callback-show', function(e){
        e.preventDefault();
        e.stopPropagation();
        _popup_order = true;
        _source_tgb = jQuery(this);
        
        //читаем данные для всплывашки
        var _params = {ajax:true,'id':jQuery(this).children('span').attr('data-id')};
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: '/tgb/popup/owner-info/', data: _params,
            success: function(msg){
                var _form = jQuery('.tgb-popup');
                if(_source_tgb.hasClass('tgb')){
                    _form.find('.tgb').html("").append(_source_tgb.html()).find('.external-link').removeClass('disabled').find('p').remove();
                    _form.find('.company-page').find('.button').off('click');
                    _form.find('.company-page').find('.button').on('click',function(){
                        _form.find('.tgb').trigger('click');
                    });
                }else{
                    jQuery('.tgb-popup').css('height','300px');
                    _form.find('.promotion-block').remove();
                }
                var _form_title = _form.find('.form-title');
                //document.styleSheets[0].insertRule('.tgb-popup .form-title:before, .tgb-popup .top-block .company-page .button, .tgb-popup.active .closebutton {background-color: #' + msg.main_color + '}',0);
				_form.attr('data-agency-id',msg.id);
                _form_title.find('.title').html(msg.title);
                _form_title.find('.phone').html(msg.phone);
                _form_title.find('.logo').css("background", "url(" + msg.logo_url +") no-repeat");
				//вставляем форму обратного звонка
				jQuery('.callback-block').html("");
				//если форма куда-то пропала, создаем
				if(jQuery('.comagic-sitephone').length == 0){
					
                    jQuery('.callback-block').addClass('waiting');
                    setTimeout("jQuery('.comagic-sitephone').show().appendTo('.callback-block');set_phone_styles();jQuery('.callback-block').removeClass('waiting');",2000);
					//setTimeout(jQuery('.comagic-sitephone').show().appendTo('.callback-block'),200);
				}
				else if(jQuery('.callback-block').find('.comagic-sitephone').length == 0){
                    jQuery('.comagic-sitephone').removeClass('comagic-widget--hidden').show().appendTo('.callback-block');
                    set_phone_styles();
                } 
				
                return false;
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
            },
            complete: function(){
            }
        
        });
        
		//кнопка "перейти"
		jQuery(document).on('click','.promotion-block .company-page .button',function(){
			jQuery('.promotion-block .tgb .external-link').click();
		});
        
        jQuery('body').append(_tgb_popup);
        _background_shadow_id = "#background-shadow-expanded";
        
        jQuery(_background_shadow_id).fadeIn(100);
        
        //маски
        setTimeout(function(){
            jQuery(_background_shadow_id + ' .tgb-popup').addClass('active');
            //jQuery('input.phone').mask('8 (000) 000-00-00');
            /*
            jQuery('input.phone').focus(function(){
                  if(jQuery(this).val().length == 0) jQuery(this).val('8');
            })
            */
            //jQuery(_background_shadow_id + ' .tgb-popup input[name=phone]').focus();
            //обработка клавиатуры
            
        }, 200);
        
        //обработка закрытия
        jQuery(document).on("click", ".closebutton, #background-shadow-expanded-wrapper",function(){ 
            //аккуратно убираем форму звонка на место, откуда брали, чтобы не стереть ее
            jQuery('.comagic-sitephone').hide().insertAfter(jQuery('#shadow-wrapper'));
            jQuery(_background_shadow_id + ' .application-view-form').removeClass('active');
            jQuery(' .application-view-form').removeClass('active');
            setTimeout(function(){
                 jQuery(_background_shadow_id+" #background-shadow-expanded-wrapper").fadeOut(100);
                 
                 //jQuery(_background_shadow_id).remove();
                 
                 jQuery(_background_shadow_id).promise().done(function() {
                     jQuery(_background_shadow_id).remove();
                 }); 
            }, 350);
        });
        jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 27:
                        //аккуратно убираем форму звонка на место, откуда брали, чтобы не стереть ее
                        jQuery('.comagic-sitephone').fadeOut(100).insertAfter(jQuery('#shadow-wrapper'));
                        //аккуратно убираем форму звонка на место, откуда брали, чтобы не стереть ее
                        //jQuery('.comagic-sitephone').hide().insertAfter(jQuery('#shadow-wrapper'));
                        jQuery(_background_shadow_id).fadeOut(200);jQuery(_background_shadow_id).remove();
                        break;     // esc
                    case 13: jQuery(_background_shadow_id + " .tgb-popup .send", "body").click(); break;             // enter
                }
            });
        
        jQuery(document).off('click','.tgb-popup .green.send');
        
        jQuery(document).on('click','.comagic-sitephone__button',function(){
			
			setTimeout(function(){
                        if( !jQuery('.comagic-sitephone').hasClass('comagic-widget--hidden') ) return true;
						else{
							//аккуратно убираем форму звонка на место, откуда брали, чтобы не стереть ее
                            jQuery('.comagic-sitephone').hide().insertAfter(jQuery('#shadow-wrapper'));
							jQuery.ajax({
								type: "POST", async: true,
								dataType: 'json', cache: false,
								url: '/tgb/popup/callback-click/', data: {ajax: true, id:jQuery('.tgb.with-popup').children('span').attr('data-id'), result:true, status:0},
								success: function(msg){
                                    jQuery('.callback-block').html("");
									jQuery('.callback-block').append("<span class='callback-success-notify'>Не перезвонили? Оставьте заявку!</span><span id=\"application-button\" class=\"button green public\" data-agency-id=\"19\" onclick=\"try{jQuery('.tgb-popup.active').hide();setTimeout(jQuery('.tgb-popup.active').remove(),500);jQuery('#background-shadow-expanded').remove(); yaCounter21898216.reachGoal('click_app'); return true; }catch(err){ }\">Оставить заявку</span>");
									jQuery('#application-button').attr('data-agency-id',jQuery('.tgb-popup').attr('data-agency-id'));
								},
								error: function(XMLHttpRequest, textStatus, errorThrown){
									//elem.addClass('system_error').html('Ошибка обращения к серверу!'); 
									//console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
								},
								complete: function(){
									//elem.removeClass('waiting');
								}
							
							});
						}
					   },5000);
        });
        return false;
    });
});