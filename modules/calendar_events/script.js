jQuery(document).ready(function(){
    jQuery('.item.show-all').on( 'click', function(){
        jQuery(this).siblings().removeClass('hidden');
        jQuery(this).remove();
    })
});