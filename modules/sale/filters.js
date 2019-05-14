jQuery(document).ready(function(){
    
    //для функционирования переключателя неделя/месяц на странице sale/stats
    jQuery("#csf-week").click(function(){     
        //если кнопка была нажата, отжимаем ее, удаляем cookie
        if (getCookie('fixed_time_period') == "week"){
            setCookie('fixed_time_period', "",15,'/');
            jQuery('#csf-week')[0].checked=false;
            //убираем соответствующий get-параметр из url
            var _get = window.location.href.split('?')[1].split('&');
            for (var key in _get){
                if (_get[key].search('f_time_period')!=-1)_get.splice(key, 1);
            }
            _get = '?'+_get.join('&');
            window.location.href = window.location.href.split('?')[0]+_get;
        }else{
            setCookie('fixed_time_period', "week", 15,'/');
            jQuery('#csf-week')[0].checked=true;
            _url = window.location.href.split('?');
            if (_url[1]!=null) window.location.href = window.location.href.concat("&f_time_period=week");
            else window.location.href = window.location.href.concat("?f_time_period=week");
        }
        
    });
    jQuery("#csf-month").click(function(){
        //если кнопка была нажата, отжимаем ее, удаляем cookie
        if (getCookie('fixed_time_period') == "month"){
            setCookie('fixed_time_period', "",15,'/');
            jQuery('#csf-month')[0].checked=false;
            //убираем соответствующий get-параметр из url
            var _get = window.location.href.split('?')[1].split('&');
            for (var key in _get){
                if (_get[key].search('f_time_period')!=-1)_get.splice(key, 1);
            }
            _get = '?'+_get.join('&');
            window.location.href = window.location.href.split('?')[0]+_get;
        }else{
            setCookie('fixed_time_period', "month", 15,'/');
            jQuery('#csf-month')[0].checked=true;
            _url = window.location.href.split('?');
            if (_url[1]!=null) window.location.href = window.location.href.concat("&f_time_period=month");
            else window.location.href = window.location.href.concat("?f_time_period=month");
        }
    });
    
    //для сохранения нажатых кнопок переключателя на странице результата
    if (getCookie('fixed_time_period') == "week"){
        jQuery('#csf-week')[0].checked=true;
    }else
    if (getCookie('fixed_time_period') == "month"){
        jQuery('#csf-month')[0].checked=true;
    }else{
        setCookie('fixed_time_period', "",15,'/');
        jQuery('#csf-month')[0].checked=false;
        jQuery('#csf-week')[0].checked=false;
    }
    
    //для переключения количества элементов на странице sale/stats
    jQuery("div.st-output a:not(.output-active)").click(function(){
        setCookie('View_count', this.text, 15,'/');
        window.location.href = window.location.href;
    });    
    
});