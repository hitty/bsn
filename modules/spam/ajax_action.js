    jQuery(document).ready(function(){
        jQuery('#up_banner, #down_banner').on('click', function(){
            var _this = jQuery(this);
            var banner_type = _this.attr("id");
            var href = _this.attr("href");
            jQuery.ajax({
               url:href,
               data: { banner_type:banner_type, ajax:true},
                type: "POST", dataType: 'json',
                async: true, cache: false,
               success: function(message) {
                   if(message.ok) {
                        var _a = jQuery('#'+banner_type);
                        _a.siblings('img').remove();  
                        _a.remove();
                   }
                   return false;
               }
           }); 
           return false;
        });
    });