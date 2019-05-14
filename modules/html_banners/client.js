
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
    //клики - по тгб с просто переходом или клики по блоку для перехода на всплывашке
    jQuery(document).on('click', 'div.banners:not(.with-popup) span, div.banners:not(.with-popup) a', function(e){
        var _el = jQuery(this);
        var referrer = document.referrer;
        var _params = {id:_el.attr('data-id'),'ref':referrer,'position':_el.data('position')};
        getPending('/banners/click/',_params)
            
    });
    
    /* span links manage */
    jQuery('body').on('click', 'span.external-link', function(e){
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
  if (typeof target != "undefined" && document.body.scrollHeight)   {
      target.postMessage(document.getElementById("banners-list").scrollHeight, "*");
  }
}
window.addEventListener("load", postSize, false);