jQuery(document).ready(function(){
    //добавляем GET-параметр в URL
    function add_get_param_value(_param_name,_param_value){
        var _get_url= window.location.href.split('search').pop();
        if(_get_url.indexOf(_param_name) == -1) _get_url += (!_get_url.indexOf('?')?'?':'&') + _param_name + '=' + _param_value;
        else _get_url = _get_url.replace(_param_name + '=',_param_name + '=' + _param_value + ',');
        return _get_url.replace(/\,\,/g,',');
    }
    //убираем GET-параметр из URL
    function remove_get_param_value(_param_name,_param_value){
        var _get_url= window.location.href.split('search').pop();
        var _full_replace_reg = new RegExp(_param_name+'=[^\&]+&?','g');
        var _value_replace_reg = new RegExp('((^|\\=|\\,)' + _param_value + '($|\\,|\\&))','g');
        if(_get_url.indexOf(_param_name) >= 0) _get_url = _get_url.replace(_full_replace_reg,_param_name + '=' + _get_url.split(_param_name)[1].split('&')[0].replace(_value_replace_reg,',').replace(/\,\,|\=/g,',').replace(/^\,|\,$/g,'') + '&');
        return _get_url;                                 
    }
    
    function remove_get_param(){
        
    }
    
    function on_ajax_success(){
        jQuery('.ajax-search-results, .left-search-form').removeClass('waiting');
    }
    
    function on_ajax_fail(){
        jQuery('.ajax-search-results, .left-search-form').removeClass('waiting').addClass('system_error');
    }
    
    
    var _def_val = getBSNCookie('View_count');
    var _def_type = getBSNCookie('View_type');
    if(!_def_type) {
        _def_type = 'list';
        setBSNCookie('View_type', _def_type, 20, '/');
    }
    if(_def_val) jQuery('#count_selector .list-data li[data-value="'+_def_val+'"]').click();
    jQuery(document).on('change',"#count_selector", function(event, value){
        setBSNCookie('View_count', value, 20, '/');
        window.location.href = window.location.href;
    });
    jQuery(document).on('change',"#sort_selector",function(event, value){
       window.location.href = jQuery(this).children('.list-data').data('link') + jQuery(this).children('input').val();
    })
    
    //выбираем пункт из меню
    jQuery('.values__item.checkbox').on('click',function(e){
        //не позволяем ссылке нажаться
        e.stopPropagation();
        e.preventDefault();
        var _this = jQuery(this);
        //обновляем URL
        window.history.pushState("object or string", 
                                 "Title", 
                                 window.location.href.split('search')[0] + 'search' + (_this.hasClass('on')?remove_get_param_value(_this.parents('.form__field-box').attr('class').replace('form__field-box','').trim(),_this.attr('data-value')):add_get_param_value(_this.parents('.form__field-box').attr('class').replace('form__field-box','').trim(),_this.attr('data-value'))));
        //обновляем переключатель
        _this.toggleClass('on');
        jQuery('.ajax-search-results, .left-search-form').addClass('waiting');
        //обновляем выдачу
        getPendingContent('.ajax-search-results',window.location.href,false,true,false,false,false,on_ajax_success);
        
    });
    
    getPendingContent('.ajax-search-results',window.location.href,false,true,false,false,false,on_ajax_success);
    
});