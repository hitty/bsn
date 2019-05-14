if($)(function(window, document, $, undefined){

    $.fn.popupWindow = function(opts) {
        var defaults = {
            url                     : null,                     /* URL для получения html */
            konkurs_url             : null,                     /* URL для конкурса */
            background_template     : '<div id="background"><div id="background-shadow"><div id="background-shadow-bg"></div><div id="background-shadow-inner"></div></div></div>', /* задний фон */
            closebutton_template    : '<span class="close" data-icon="close"></span>', 
            closebutton             : '.close',           /* закрытие формы */
            background_container    : '#background-shadow-bg',           
            inner_container         : '#background-shadow-inner',           
            width                   : 0,           /* ширина внутреннего блока */
            onExit                  : function(selected_data){}, /* функция, предваряющая закрытие function(item_id){} */
            onStart                 : function(){}
        };
        var o = $.extend(defaults, opts || {});
        var init_selector = null;                           /* Элемент, к которому назначен вызов formexpand */
        var current_item_data = null;                       /* Информация о текущем элементе */
        
        /* функция стартовой инициализации */
        var start = function(){
            

            init_selector.on('click', function(){
                    
                _gpval = init_selector.attr('data-location');
                setGPval();
                
                o.url = init_selector.attr('data-url');
                
                if(jQuery(o.inner_container).length > 0 && jQuery(o.inner_container).hasClass('active')){
                    jQuery(o.inner_container).remove();
                    jQuery(o.background_container).remove();
                    init_selector.click();
                }
                
                if(typeof o.onStart == "function"){
                    o.onStart();
                }
                
                //загрузка заднего фона
                jQuery('body').append(o.background_template);
                
                //получение контента в зависимости от способа получения данных
                getPendingContent( o.inner_container, '/' + o.konkurs_url + '/block/' + _gpval.replace('vote-', '') + '/', false, false, false, false, false, 
                    function(){ 
                        jQuery( o.inner_container ).append( o.closebutton_template );
                        listHeight();
                    }
                );
                
                //эффект появления заднего фона
                jQuery(o.background_container).fadeIn(100);
                
                //эффект появления контентной части
                setTimeout(function(){
                    jQuery('#background').addClass('fixed');
                                
                    o.width = jQuery( o.inner_container ).outerWidth();
                    
                    jQuery( o.inner_container ).css( { 'margin-left': '-' + ( o.width/2 ) + 'px' } ).addClass('active');
                    
                }, 200)
                
                if(typeof o.onExit == "function"){
                    o.onExit();
                }
                
                return false;  
            });
            
            //закрытие формы
            jQuery(document).on("click", o.background_container + ', ' + o.inner_container + ' ' + o.closebutton, close );

            //изменение высоты списка
            jQuery(window).resize(function() {
                listHeight();
            });
            
        }
       var close = function(){
            jQuery('#background').removeClass('fixed');
                
            //эффект исчезания контентной части
            jQuery( o.inner_container ).removeClass('active');

            //эффект исчезания заднего фона
            setTimeout(
                function(){
                    jQuery(o.background_container).fadeOut(100, function(){
                        jQuery('#background').remove();
                        jQuery( o.inner_container ).remove()
                    })
                }, 350
            )
            _gpval = '';
            setGPval('');
            return false;
       }

       var listHeight = function (){
            jQuery(o.inner_container + ' .list').height( document.body.clientHeight - 260 - jQuery(o.inner_container + ' h2').height() );        
       }

       return this.each(function(){
            init_selector = $(this);
            start();
       });
       
    }                
            
})(window, document, jQuery);            