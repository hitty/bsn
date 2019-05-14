
function getPending(_url, _params){
    if(typeof(_params) == 'undefined' || !_params) _params = {ajax: true};
    else _params.ajax = true;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url, data: _params,
            success: function(msg){},
            error: function(XMLHttpRequest, textStatus, errorThrown){
                //console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
            }
        });
    return true;
}
jQuery(document).ready(function(){
    jQuery(document).on('click', 'div.spec-offers.pingola span', function(e){
        var _el = jQuery(this);
        var referrer = document.referrer;
        var _params = {id:_el.attr('data-id'),from:'png','ref':referrer,type:'object'};
        getPending('/spec_offers/click/',_params)
            
    });
    
    /* span links manage */
    jQuery('body').on('click', 'span.external-link, a.external-link', function(e){
       var _link = jQuery(this).data('link');  
       if(_link.indexOf('http://') == -1) _link = 'http://'+_link; 
       window.open(_link);
      
    });
  
})

function popupwindow(url, title, w, h) {
  var left = (screen.width/2)-(w/2);
  var top = (screen.height/2)-(h/2);
  return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
} 


function postSize(e){
  var target = parent.postMessage ? parent : (parent.document.postMessage ? parent.document : undefined);
  /*
  if (typeof target != "undefined" && document.body.scrollHeight)   {
    target.postMessage(document.getElementById("spec-offers-list").scrollHeight, "*");
  }
  */
}
window.addEventListener("load", postSize, false);

// Функция вычисляет время до события
function timeToEvent(eventDate, output_format)
{
      var now = new Date();
      var output = '';      
     // количество дней до события
     var daystoED = Math.floor(Math.round(eventDate-now)/86400000);
     if(output_format == 'inline') daystoED = (daystoED < 1) ? "" : daystoED+" дн. ";
     else daystoED = (daystoED < 1) ? "" : daystoED;
     // количество часов до события
     var hourstoED = 24 - now.getHours() - 1;
       hourstoED = (hourstoED < 10) ? "0"+hourstoED : hourstoED;
     // количество минут до события
     var minutestoED = 60 - now.getMinutes() - 1;
         minutestoED = (minutestoED < 10) ? "0"+minutestoED : minutestoED;
     // количество секунд до события
     var secondstoED = 60 - now.getSeconds() - 1;
     secondstoED = (secondstoED < 10) ? "0"+secondstoED : secondstoED;       
     //сообщение
     if(output_format == 'split')  output = {'days':daystoED, 'hours':hourstoED, 'minutes':minutestoED, 'seconds':secondstoED};
     else  output = daystoED+hourstoED+":"+minutestoED+":"+secondstoED;
   return output;
}