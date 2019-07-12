/**
* Address selector $ plugin
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
if($)(function(window, document, $, undefined){
    $.fn.formSubmit = function(opts) {
        var defaults = {
            button                : null, 
            f_values              : {},                       /* массив значений всех элементов */
            popup_redirect        : false,
            scroll_to_error       : false,
            error_template        : '<span class="error">Обязательное поле</span>',
            notification_template : '<div class="row"><div class="notifications"><span></span></div></div>',
            onInit                : function(){},
            onFormSuccess         : function(data){}
        }
        var o = $.extend(defaults, opts || {});
        var init_selector = null;                           /* Элемент, к которому назначен вызов */
        
        /* функция стартовой инициализации */
        var start = function(){
            o.button = jQuery('button, .button', init_selector);
            o.button.on('click', function(e){
                
                if(jQuery(this).hasClass('disabled')) return false;
                
                e.stopPropagation();
                e.preventDefault();

                jQuery('.notifications div', init_selector).addClass('inactive');
                jQuery('.error', init_selector).remove();
                
                _error = false;
                _min_error_offset = 0;
                init_selector.find('input, textarea').each(function(){
                    var _this = jQuery(this);
                    _this.removeClass('red-border').next('span').removeClass('active');
                    if(_this.parent().hasClass('list-selector')) _this.parent().removeClass('red-border').next('span').removeClass('active');
                    var _type = _this.attr('type');
                    _required = _this.attr('required');
                    _name = _this.attr('name');
                    if(_type == 'checkbox' && _this.parent().hasClass('on')){
                        _value = 1;
                    } else {
                        _value = _type == 'radio' ? jQuery('input[name=' + _name + ']:checked', init_selector).val() : _this.val();
                    }
                    if( (_required == 'required' && (_value == '' || _value == 0) || (_name == 'phone' && _value.length != 17)) || 
                        (_name == 'email' && (_value.length > 0 && _value.match(/([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/) == null) )) {
                        //отдельно для селекторов
                        if(_this.parent().hasClass('list-selector')) _this.parent().addClass('red-border').parents('.row').append(o.error_template);
                        else _this.addClass('red-border').parents('.row').append(o.error_template);
                        if( o.scroll_to_error == true){
                            _min_error_offset = _min_error_offset == 0 || _min_error_offset > _this.offset().top ? _this.offset().top : _min_error_offset;
                        }
                        _error = true;
                    } else{
                        o.f_values[_name] = _value;
                        _this.removeClass('red-border').parents('.row').find('.error').remove();
                    }
                    
                });  
                if( o.scroll_to_error == true && _min_error_offset > 0 ) $("html,body").animate({ scrollTop: _min_error_offset - jQuery('header').height() - 20}, "slow");
                o.f_values['popup_redirect'] = o.popup_redirect;
                if(_error == true) return false;
                jQuery.ajax({
                    type: "POST", 
                    async: true,
                    dataType: 'json', 
                    url: init_selector.attr('action'),
                    data: o.f_values,   
                    cache: false,
                    success: function(msg){ 
                        showNotification(msg.error, msg.success);
                        if(msg.ok == true){
                            
                            if(typeof o.onFormSuccess == "function") o.onFormSuccess.call(this, msg);
                            
                            //вывод уведомления
                            if( typeof msg.html == 'string' ) {
                                if(jQuery('#background-shadow-inner .closebutton').length > 0) {
                                    setTimeout(function(){
                                        jQuery('#background-shadow-inner .closebutton').click();
                                    }, 2000);
                                }
                                if( init_selector.closest('.application-fixed').length > 0) var _fixed_wrap = init_selector.closest('.application-fixed');
                                
                                init_selector.parent('div').html(msg.html);
                                
                                if( typeof _fixed_wrap == 'object' && _fixed_wrap.length > 0) {
                                    jQuery('.form', _fixed_wrap).css({'margin-top' : '-' + parseInt( jQuery('.form').height() ) / 2 + 'px'})
                                    setTimeout(function(){
                                        jQuery('#application-button', _fixed_wrap).click();
                                    }, 2000);
                                }
                                
                            }
                            if(o.popup_redirect == true || o.popup_redirect == 'true' || msg.popup_redirect == true){
                                
                                setTimeout(function(){
                                    window.location.href = location.href.replace(location.hash, "");
                                }, 1500)
                            }                           
                        } 
                    }
                }) 
            })
            if(typeof o.onInit == "function"){
                o.onInit();
            }            
            if( init_selector.closest('.application-fixed').length > 0){
                var _fixed_wrap = init_selector.closest('.application-fixed');
                jQuery('.form', _fixed_wrap).css({'margin-top' : '-' + parseInt( jQuery('.form').height() ) / 2 + 'px'})
                jQuery('#application-button', _fixed_wrap).on('click', function(){
                    _fixed_wrap.toggleClass('active');
                    if( _fixed_wrap.hasClass('active') ) jQuery('body').append('<div id="background-shadow"><div id="background-shadow-bg"></div></div>');
                    else {
                        setTimeout(function(){
                            jQuery('#background-shadow').fadeOut(200, function(){
                                jQuery( '#background-shadow' ).remove()
                            })
                        }, 100 );                        
                    }
                })
            }
        }             
        //вывод уведомлений
        var showNotification = function(error, success){
            jQuery('.notifications', init_selector).remove();
            var _notification_class = typeof error == 'string' ? 'message-error' : ( typeof success == 'string' ? 'message-success' : false );
            if(_notification_class == false) return false;
            jQuery( o.notification_template ).insertAfter( jQuery('.title', init_selector) );
            
            jQuery('.notifications span', init_selector).attr('class', _notification_class).text(typeof error == 'string' ? error : success);
        }
        return this.each(function(){
            init_selector = $(this);
            start();   
        });
    }

    $.fn.popupWindow = function(opts) {
        var defaults = {
            container               : null,                     /* элемент DOM с html */
            url                     : null,                     /* URL для получения html */
            background_template     : '<div id="background-shadow"><div id="background-shadow-bg"></div><div id="background-shadow-inner"></div></div>', /* задний фон */
            closebutton_template    : '<span class="closebutton" data-icon="close"></span>', 
            closebutton             : '.closebutton',           /* закрытие формы */
            background_container    : '#background-shadow-bg',           
            inner_container         : '#background-shadow-inner',           
            popup_redirect          : true,
            width                   : 0,           /* ширина внутреннего блока */
            popupCallback           : function(data){}, /* функция, предваряющая закрытие function(item_id){} */
            onInit                  : function(){}
            
        };
        var o = $.extend(defaults, opts || {});
        var init_selector = null;                           /* Элемент, к которому назначен вызов formexpand */
        var current_item_data = null;                       /* Информация о текущем элементе */
        
        /* функция стартовой инициализации */
        var start = function(){
            

            init_selector.on('click', function(){
                    
                _gpval = init_selector.attr('data-location');
                //setGPval();
                
                o.container = init_selector.attr('data-container');
                o.url = init_selector.attr('data-url');
                
                if(jQuery(o.inner_container).length > 0 && jQuery(o.inner_container).hasClass('active')){
                    jQuery(o.inner_container).remove();
                    jQuery(o.background_container).remove();
                    init_selector.click();
                }
                
                container = jQuery(o.container);

                //загрузка заднего фона
                jQuery('body').append(o.background_template);
                
                //получение контента в зависимости от способа получения данных
                if( typeof o.container == 'string' ) jQuery( o.inner_container ).append( container.html() ).addClass( container.attr( 'class') );
                else if( typeof o.url == 'string' ) getPendingContent( o.inner_container, o.url , false, false, false, false, false, 
                    function(){ 
                        checkBoxesInit( jQuery( o.inner_container) ); 
                        listSelectorInit( jQuery( o.inner_container) );
                        jQuery( o.inner_container + ' .popup' ).each(
                            function(){ 
                                jQuery(this).popupWindow();
                            }
                        );
                        jQuery( 'input.phone,input[type=phone]', jQuery( o.inner_container ) ).mask('8 (999) 999-99-99');
                        //closebutton
                        jQuery( o.inner_container ).append( o.closebutton_template )
                        var _popup_redirect = o.popup_redirect && ( typeof init_selector.attr('data-redirect') == 'string' ? init_selector.attr('data-redirect') : false );
                        jQuery( o.inner_container + ' form' ).formSubmit(
                            { 
                                popup_redirect : _popup_redirect,
                                onFormSuccess: function(data){ o.popupCallback(data) }
                            }
                        );
                        if(typeof o.onInit == "function"){
                            o.onInit(container);
                        }
                        
                    }
                );
                else return false;
                //эффект появления заднего фона
                jQuery(o.background_container).fadeIn(100);
                
                //эффект появления контентной части
                setTimeout(function(){

                    o.width = jQuery( o.inner_container ).outerWidth();
                    o.height = parseInt( jQuery( o.inner_container ).outerHeight() );
                    o.window_height = parseInt( jQuery( window ).height() );
                    
                    if( o.height + 40 > o.window_height) jQuery( o.inner_container ).addClass('fixed');
                    jQuery( o.inner_container ).css( { 'margin-left': '-' + ( o.width/2 ) + 'px' } ).addClass('active');
                    
                }, 600)
                
                
                
                return false;  
            });
            
            //закрытие формы
            jQuery(document).on("click", o.background_container + ', ' + o.inner_container + ' ' + o.closebutton, close );

       }
       var close = function(){
                
            //эффект исчезания контентной части
            jQuery( o.inner_container ).removeClass('active');

            //закрытие формы справа
            if( jQuery('.application-fixed.active').length > 0) jQuery('.application-fixed.active #application-button').click();
            
            //эффект исчезания заднего фона
            setTimeout(
                function(){
                    jQuery(o.background_container).fadeOut(100, function(){
                        jQuery(this).parent().remove();
                        jQuery( o.inner_container ).remove()
                    })
                }, 350
            )
            _gpval = '';
            //setGPval();
            return false;
       }
       $.fn.popupWindow.destroy = function() {
       }
       
       return this.each(function(){
            init_selector = $(this);
            start();
       });
    }                
            
})(window, document, jQuery);            