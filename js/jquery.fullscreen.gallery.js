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
    $.fn.fullscreenGallery = function(opts) {
        var defaults = {
            item                    :   'li', 
            photos_list             :   '.list', 
            photos_thumbs_list      :   '.thumbs-list', 
            right_arrow             :   '.right-arrow', 
            left_arrow              :   '.left-arrow',
            active_class            :   'active',
            active_index            :   1,
            fix_column              :   '.central-column',
            autoRefreshInterval     :   7000
        };
        const o = $.extend(defaults, opts || {});
        var selector = null;                          /* Элемент, к которому назначен вызов addrselector */
        var timer = null;
        /* функция стартовой инициализации */
        var start = function(){
            const right_arrow = jQuery( o.right_arrow, selector );
            const left_arrow = jQuery( o.left_arrow, selector );
            /* fullscreen-gallery */
            jQuery( o.photos_thumbs_list + ' ' + o.item, selector ).on( 'click', function(){
                o.active_index = jQuery(this).data('id'); 
                changePhoto( );
            })
            const photos_count = jQuery( o.photos_thumbs_list + ' ' + o.item, selector ).length;
            if( photos_count > 0 ){
                right_arrow.show();
                jQuery( o.left_arrow , selector ).show();
            }
            right_arrow.on( 'click', function(){
                o.active_index++;
                if( o.active_index > photos_count ) o.active_index = 1;
                changePhoto( )   ;
            })
            left_arrow.on( 'click', function(){
                o.active_index--;
                if( o.active_index == 0  ) o.active_index = photos_count;
                changePhoto( )   ;
            })
            timer = new Timer( function(){ right_arrow.click() },  o.autoRefreshInterval);
            //обработка клавиатуры
            jQuery(document).keyup(function(e) {
                switch(e.keyCode){
                    case 37: left_arrow.click(); break;             // <-
                    case 39: right_arrow.click(); break;            // ->
                }
            });    
            
            //fix next column
            if( selector.next( o.fix_column ).length > 0 ){
                fixColumnPosition();
                jQuery( window ).resize( fixColumnPosition );
            }                        
        }
        var fixColumnPosition = function() {
            selector.next( o.fix_column ).css( 'margin-top', selector.height() + ( jQuery( 'header').height() > 0 ? jQuery( 'header').height() : 0 ) + 'px' );
        }
        var changePhoto = function() {
            jQuery( o.photos_list + ' ' + o.item + '[data-id=' + o.active_index + ']', selector).addClass( o.active_class ).siblings( o.item ).removeClass( o.active_class );
            jQuery( o.photos_thumbs_list + ' ' + o.item + '[data-id=' + o.active_index + ']', selector).addClass('active').siblings( o.item ).removeClass(  o.active_class  );
            timer.reset( o.autoRefreshInterval );
            timer.start();
        }
        var Timer = function(fn, t) {
            var timerObj = setInterval(fn, t);

            this.stop = function() {
                if (timerObj) {
                    clearInterval(timerObj);
                    timerObj = null;
                }
                return this;
            }

            // start timer using current settings (if it's not already running)
            this.start = function() {
                if (!timerObj) {
                    this.stop();
                    timerObj = setInterval(fn, t);
                }
                return this;
            }

            // start with new interval, stop current interval
            this.reset = function(newT) {
                t = newT;
                return this.stop().start();
            }
        }
        return this.each(function(){
            selector = $(this);
            start();
            return false;
        })
    }
})(window, document, jQuery);