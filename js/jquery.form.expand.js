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
    $.fn.formexpand = function(opts) {
        var defaults = {
            container               : null,                     /* содержимое */
            background_template     : '<div id="background-shadow-expanded"><div id="background-shadow-expanded-wrapper"></div></div>', /* задний фон */
            closebutton             : '.closebutton',           /* закрытие формы */
            background_container    : '#background-shadow-expanded',           /* закрытие формы */
            onExit                  : function(selected_data){} /* функция, предваряющая закрытие function(item_id){} */
        };
        var options = $.extend(defaults, opts || {});
        var init_selector = null;                           /* Элемент, к которому назначен вызов formexpand */
        var current_item_data = null;                       /* Информация о текущем элементе */
        
        /* функция стартовой инициализации */
        var start = function(){
            var container = jQuery(options.container);
            if(container == null) return false;
            container.css({'position': 'fixed', 'z-index': '99992', 'opacity':'0', 'visibility':'hidden', 'left':'50%'});
            init_selector.on('click', function(){
                //позиционирование
                var width = container.width();
                var height = container.height(); 
                container.css({'top':'120px', 'margin-left':'-'+(width/2)+'px'});
                jQuery('body').append(options.background_template);
                jQuery(options.background_container).fadeIn(100);
                setTimeout(function(){
                    container.css({'visibility':'visible'}).animate({"opacity":1, 'top':'140px'}, 220);
                }, 200)
                return false;  
            })
            
            jQuery(document).on("click", options.closebutton+',#background-shadow-expanded-wrapper',function(){ 
                container.animate({"opacity":0, 'top':'120px'}, 
                {
                    duration: 220,
                    complete: function () {
                        container.css({'visibility':'hidden'})
                    }
                });
                setTimeout(function(){
                    jQuery(options.background_container).fadeOut(100, function(){
                        jQuery(this).remove();
                    })
                }, 350)
                 return false;
            }); 
            
       
       }
        return this.each(function(){
            init_selector = $(this);
            start();   
        });
    }
})(window, document, jQuery);