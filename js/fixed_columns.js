jQuery(document).ready(function(){
        
        jQuery('.fixed-column').each(function(){
            var _this = jQuery(this);
            scrollWrappers( _this );
            jQuery(window).scroll(function(){
                scrollWrappers( _this );
                return false;
            }); 
        });
       
        function scrollWrappers(_this){
            if(jQuery('#left-column').height() > _this.height()){
                var _doc_height = jQuery(window).height()
                var _column_height = parseInt(_this.height());
                var _top = parseInt(jQuery(window).scrollTop());

                _column_top = parseInt( _this.parent().offset().top );
                var d = new Date();
                if(jQuery('#middle-bottom-banner').length > 0) _footer_top = parseInt(jQuery('#middle-bottom-banner').offset().top)
                else if(jQuery('footer').length > 0) _footer_top = parseInt(jQuery('footer').offset().top)
                if(_column_top <= _top + 40)  {
                    _this.addClass('scrolled-top').css('top', 20 + 'px').removeClass('scrolled-bottom');

                    if( _top + _column_height > _footer_top - 80) {
                        _this.removeClass('scrolled-top').css('top', 'auto').addClass('scrolled-bottom');      
                    }
                } else {
                    _this.removeClass('scrolled-top').removeClass('scrolled-bottom').css('top', 'auto')    
                }        
            }
            
            
        }        
        
    

});