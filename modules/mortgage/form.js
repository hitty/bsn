
jQuery(document).ready(function(){

    //видимость кнопки регистрации
    jQuery(document).on('click', '.mortgage-wrapper .terms .single-selector label', function(){
        if( !jQuery(this).hasClass('on') ) jQuery('.mortgage-wrapper .terms button').removeClass('disabled');
        else jQuery('.mortgage-wrapper .terms button').addClass('disabled');
    })    
    
    jQuery('#birthdate').mask('99.99.99')
    
});