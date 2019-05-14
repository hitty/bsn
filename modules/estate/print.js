jQuery(document).ready(function(){
    /*при нажатии на кнопку "Печать" кнопки прячутся, чтобы их не было на странице при печати*/
    jQuery('#print').click(function(){
        jQuery(this).hide();
        jQuery('#close').hide();
        print('');
        jQuery(this).show();
        jQuery('#close').show();
    });
});