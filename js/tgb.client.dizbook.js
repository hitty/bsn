function listener(event) {
  if (event.origin == 'https://www.bsn.ru') {
    if(parseInt(event.data) >= 0) {
        var _el = document.getElementById("tgb-list-iframe");
        if(parseInt(event.data) <= 100) _el.remove();
        else {
            _el.height = 300;    
            _el.width = 200;    
        }
    }
  }  
}

if (window.addEventListener) {
  window.addEventListener("message", listener);
} else {
  // IE8
  window.attachEvent("onmessage", listener);
}     
