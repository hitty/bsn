jQuery(document).ready(function(){
    //по клику на записи убираем анонс и показывем полный текст записи
    jQuery('.project-changes tr').click(function(e){
        if($(e.target).parents('.report-text').length>0 || $(e.target).hasClass('report-text'))
            if($(e.target).attr('href')!==undefined){
                window.open($(e.target).attr('href'),'_blank'); 
                return false;
            } 
            else return false;
        else{
            var _divs = $(this).children().children();
            if (_divs.children('.report-text').hasClass('unactive')){
                _divs.children('.report-short').addClass('unactive');
                _divs.children('.report-text').removeClass('unactive');
            }
            else{
                _divs.children('.report-short').removeClass('unactive');
                _divs.children('.report-text').addClass('unactive');
            }
        }
        
    });
});