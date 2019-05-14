(document).ready(function(){
    jQuery(document).on('click', 'div.tgb span, div.tgb a', function(e){
        var _el = jQuery(this);
        var referrer = document.referrer;
        var _params = {id:_el.attr('data-id'),from:_el.data('from'),'ref':referrer,'position':_el.data('position')};
        getPending('/tgb/click/',_params)
            
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
  return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=200, height=450, top='+top+', left='+left);
} 


function postSize(e){
  var target = parent.postMessage ? parent : (parent.document.postMessage ? parent.document : undefined);
  if (typeof target != "undefined" && document.body.scrollHeight)   {
      target.postMessage(document.getElementById("tgb-list").scrollHeight, "*");
  }
}
window.addEventListener("load", postSize, false);