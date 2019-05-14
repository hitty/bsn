jQuery(document).ready(function(){
    var _template = '<div id="signup-background-shadow-expanded">'
                        +'<div id="signup-background-shadow-expanded-wrapper"></div>'
                        +'</div>'
                        
                    +'</div>';    
    /* форма авторизации */
    jQuery('#signup').on('click',function(){
        jQuery('body').append(_template);
        jQuery('.signup-view-form').fadeIn(200);
        jQuery('html, body').animate({scrollTop: 0}, 500);
        return false;
        
    });    
    jQuery(document).on("click", ".signup-view-form .closebutton, #signup-background-shadow-expanded-wrapper",function(){ 
         jQuery('#signup-background-shadow-expanded').remove();
         jQuery('.signup-view-form').hide();
         return false;
    }); 
        
})