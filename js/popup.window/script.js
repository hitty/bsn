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
            closebutton             : '.closebutton',           /* закрытие формы */
            closebutton_template    : '<span class="closebutton" data-icon="close"></span>',           
            error_template        : '<span class="error">Обязательное поле</span>',
            notification_class    : 'form-block notifications',
            form_row              : '.row',
            onInit                : function(){},
            onFormSuccess         : function(data){}
        }
        var o = $.extend(defaults, opts || {});
        var init_selector = null;                           /* Элемент, к которому назначен вызов */
        
        /* функция стартовой инициализации */
        var start = function(){
            o.button = jQuery( 'button, .button', init_selector);      
            formValidate( init_selector );
            //уникальные поля
            init_selector.find( 'input.unique' ).each(function(){
                var search_timeout = undefined;
                var _input = jQuery(this);
                jQuery(this).bind( 'keyup', function() {
                    clearTimeout(search_timeout);
                    search_timeout = setTimeout(function() {
                        validateUnique( _input )
                    }, 250);
                }); 
            })

            o.button.on( 'click', function(e){
                
                if( jQuery(this).hasClass( 'disabled' ) || jQuery(this).hasClass( 'waiting' )) return false;
                jQuery(this).addClass( 'waiting' );
                
                e.stopPropagation();
                e.preventDefault();

                //валидация формы
                _min_error_offset = 0;
                init_selector.find( 'input, textarea' ).each(function(){
                    var _this = jQuery(this);
                    _this.removeClass('red-border').closest( o.form_row ).find('.error').remove();
                    if( _this.parent().hasClass( 'list-selector' )) _this.parent().removeClass( 'red-border' ).next( 'span' ).removeClass( 'active' );
                    var _type = _this.attr( 'type' );
                    _required = _this.attr( 'required' );
                    _name = _this.attr( 'name' );
                    if( _name != false ) {
                        if( _type == 'checkbox' && _this.parent().hasClass( 'on' )){
                            _value = 1;
                        } else {
                            _value = _type == 'radio' ? jQuery( 'input[name=' + _name + ']:checked', init_selector).val() : _this.val();
                        }
                        if( ( _required == 'required' && ( _value == '' || _value == 0) || ( _name == 'phone' && (_value.replace(/\D/g,'')).length != 11)) || 
                            ( _name == 'email' && ( _value.length > 0 && _value.match(/([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/) == null) )) {
                                //отдельно для селекторов
                            if( _this.parent().hasClass( 'list-selector' )) _this.parent().addClass( 'red-border' ).parents( o.form_row ).append(o.error_template);
                            else  notification( _this, true, o.error_template ); 
                            if( o.scroll_to_error == true){
                                _min_error_offset = _min_error_offset == 0 || _min_error_offset > _this.offset().top ? _this.offset().top : _min_error_offset;
                            }
                        } else {
                            o.f_values[_name] = _value;
                            if( !_this.hasClass( 'unique' ) ) notification( _this, false, '' );
                        }
                    }
                });  
                if( o.scroll_to_error == true && _min_error_offset > 0 ) $("html,body").animate({ scrollTop: _min_error_offset - jQuery( 'header' ).height() - 20}, "slow");
                o.f_values['popup_redirect'] = o.popup_redirect;
                if( jQuery( '.error', init_selector ).length || jQuery( '.red-border', init_selector ).length ) {
                    jQuery(this).removeClass( 'waiting' );
                    return false;
                }
                console.log( init_selector.attr( 'action' ) + o.f_values )
                jQuery.ajax({
                    type: "POST", 
                    async: true,
                    dataType: 'json', 
                    url: init_selector.attr( 'action' ),
                    data: o.f_values,   
                    cache: false,
                    success: function( msg ) { 
                        var _error_notification = jQuery( '.' + o.notification_class.split(' ').join('.'), init_selector );
                        o.button.removeClass( 'waiting' );
                        if( msg.ok == true ) {
                            if(typeof o.onFormSuccess == "function") o.onFormSuccess.call(this, msg);
                            //вывод уведомления
                            _error_notification.remove();
                            init_selector.find( 'input, textarea' ).addClass('success');
                            if( msg.success ) jQuery( '.button-container', init_selector ).html ( msg.success ).addClass( 'notifications success' );
                            if( jQuery( '.modal-inner .closebutton' ).length > 0) {
                                setTimeout(function(){
                                    jQuery( '.modal-inner .closebutton' ).click();
                                }, typeof msg.html == 'string' && msg.html.length > 20 ? 3200 : 1500 );
                            }

                            if( init_selector.closest('.application-fixed').length > 0) var _fixed_wrap = init_selector.closest('.application-fixed');
                            
                            if( typeof msg.html == 'string' && msg.html.length > 20 ) init_selector.parent( 'div' ).html(msg.html).prepend( o.closebutton_template );

                            if(o.popup_redirect == true || o.popup_redirect == 'true' || msg.popup_redirect == true){
                                setTimeout(function(){
                                    window.location.href = msg.redirect_url ? msg.redirect_url : location.href.replace(location.hash, "");
                                }, typeof msg.html == 'string' && msg.html.length > 20 ? 3500 : 1700 )
                            } 
                                                      
                        
                            if( jQuery( '.result-html', init_selector ).length > 0 && msg.success_text.length > 0 ) {
                                jQuery( '.result-html', init_selector ).html( msg.success_text ).addClass( 'success' );
                                jQuery( 'input,textarea,.list-selector', init_selector ).attr('disabled', 'disabled').addClass('disabled');
                            }

                        } else if( msg.error ||  msg.errors )  {
                            if( _error_notification.length > 0 ) _error_notification.html( msg.error );
                            else jQuery( '<div class="' + o.notification_class + '">' + msg.error + '</div>' ).insertBefore( jQuery( o.form_row, init_selector ).first() ) ;
                            if( msg.errors ){
                                for(var index in msg.errors) { 
                                    notification( jQuery( '[name=' + index + ']', init_selector ),  true, msg.error != msg.errors[index] ? '<span class="error">' + msg.errors[index] + '</span>' : '' )
                                }
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
                    if( _fixed_wrap.hasClass('active') ) jQuery('body').append('<div class="modal-container"><div class="modal-bg"></div></div>');
                    else {
                        setTimeout(function(){
                            jQuery('.modal-container').fadeOut(200, function(){
                                jQuery( '.modal-container' ).remove()
                            })
                        }, 100 );                        
                    }
                })
            }
            //видимость отклюбченных селекторов
            if( jQuery( '.disabled', init_selector ).length > 0 ){
                var _el_disabled = jQuery( '.disabled', init_selector );
                jQuery('.disable-selector', init_selector).on( 'click', function(){
                    _el_disabled.toggleClass('disabled');
                })
            }
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 13: o.button.click(); return false; break
                    case 27: jQuery( '.modal-inner .closebutton' ).click(); return false; break
                }
            });        
        }             
        //проверка на уникальность
        var validateUnique = function( _this ){
            var _params = {};
            _params[ _this.attr( 'name' ) ] = _this.val();
            var _url = _this.data( 'url' );
            getPending( _url, _params, function( data ){ 
                    notification( _this,  !data.ok, '<span class="error">' + data.error + '</span>' );
                } 
            );
        }
        //уведомления
        var notification = function( _this, _error, _text ){
            
            if( _error == true ){
                _this.addClass( 'red-border' ).closest( o.form_row ).append( _text );
            } else {
                _this.removeClass( 'red-border' ).closest( o.form_row ).find( '.error' ).remove();
            }
            
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
            background_template     : '<div class="modal-container modal-container-opened"><div class="modal-bg"></div><div class="modal-inner"><div class="modal-stage"><div class="modal-slide"></div></div></div></div>', /* задний фон */
            closebutton             : '.closebutton',           /* закрытие формы */
            closebutton_template    : '<span class="closebutton" data-icon="close"></span>',           
            background_container    : '.modal-bg',           
            inner_container         : '.modal-slide',           
            close_container         : '.modal-close',           
            close_template          : '<div class="modal-close"></div>',           
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
            init_selector.on( 'click', function(){
                jQuery('.modal-container').remove();
                jQuery( 'body,html' ).addClass( 'modal-active' );
                    
                _gpval = init_selector.attr( 'data-location' );
                if( typeof _gpval == "string" && _gpval != '' ) setGPval( _gpval );
                
                o.container = init_selector.attr( 'data-container' );
                o.url = init_selector.attr( 'data-url' );
                
                if( jQuery( o.inner_container ).length > 0 && jQuery( o.inner_container ).hasClass( 'active' )){
                    jQuery( o.inner_container ).remove();
                    jQuery( o.background_container ).remove();
                    init_selector.click();
                }
                
                container = jQuery( o.container );

                //загрузка заднего фона
                jQuery( 'body' ).append(o.background_template);
                
                //получение контента в зависимости от способа получения данных
                if( typeof o.container == 'string' ) jQuery( o.inner_container ).append( container.html() ).addClass( container.attr( 'class' ) );
                else if( typeof o.url == 'string' ) getPendingContent( o.inner_container, o.url , false, false, false, 
                    function( msg ){ 
                        setTimeout(function(){
                            //эффект появления контентной части
                            o.width = jQuery( o.inner_container ).outerWidth();
                            o.height = parseInt( jQuery( o.inner_container ).outerHeight() );
                            o.window_height = parseInt( jQuery( window ).height() );
                            
                            jQuery( o.inner_container ).addClass( 'active' );

                            
                            checkBoxesInit( jQuery( o.inner_container ) ); 
                            listSelectorInit( jQuery( o.inner_container ) );
                            jQuery( o.inner_container + ' .popup' ).each(
                                function(){ 
                                    jQuery(this).popupWindow();
                                }
                            );
                            formValidate( o.inner_container );
                            
                            //closebutton
                            var _popup_redirect = o.popup_redirect && ( typeof init_selector.attr( 'data-redirect' ) == 'string' ? init_selector.attr( 'data-redirect' ) : false );
                            if( typeof jQuery( o.inner_container + ' form' ).formEdit !== "undefined" ) jQuery( o.inner_container + ' form' ).formEdit();
                            jQuery( o.inner_container + ' form' ).formSubmit(
                                { 
                                    popup_redirect : _popup_redirect,
                                    onFormSuccess: function(data){ o.popupCallback(data) }
                                }
                            ); 
                            if(typeof o.onInit == "function"){
                                o.onInit( container, msg );
                            }
                            
                            jQuery( o.inner_container ).children(0).prepend( o.closebutton_template );
                            jQuery( o.inner_container ).append( o.close_template );

                        }, 20 );
                        
                    }
                );
                else return false;
                //эффект появления заднего фона
                jQuery( o.background_container ).fadeIn(100);
                    
                return false;  
            });
            //закрытие формы
            jQuery(document).on("click", o.close_container + ', ' + o.inner_container + ' ' + o.closebutton, closePopupWindow );

       }
       var closePopupWindow = function(){
            //эффект исчезания контентной части
            jQuery( o.inner_container ).removeClass( 'active' );
           
            //эффект исчезания заднего фона
            setTimeout(
                function(){
                    jQuery( 'body,html' ).removeClass( 'modal-active' );
                    jQuery( o.inner_container ).remove();
                    jQuery( o.background_container ).fadeOut(100, function(){
                        jQuery(this).parent().remove();
                        
                        
                    })
                }, 0
            )
            _gpval = ''; 
             setGPval( _gpval );
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