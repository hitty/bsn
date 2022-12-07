/**
* Main JavaScript file
*/ 
var _debug;
function showConfirmWindow(title_text, answer_text, confirm_text, reject_text){
    var _confirm_template = '<div id="confirm-box-expanded">\
                                <div id="confirm-box-expanded-wrapper"></div>\
                                <div id="confirm-box-expanded-content">\
                                    <div id="confirm-box-expanded-container">\
                                    <div class="confirm-title"><strong>'+title_text+'</strong></div>\
                                    <div class="answer-box"><p class="answer-text">'+answer_text+'</p></div>\
                                    <div class="response-box">\
                                        <div class="confirm-button">'+confirm_text+'</div>\
                                        <div class="reject-button"><span>'+reject_text+'</span></div>\
                                    </div>\
                                </div>\
                                <a class="closebutton">Закрыть</a>\
                                </div>\
                                </div>'; 
    if (!jQuery( '#confirm-box-expanded' ).length>0)
        jQuery( 'body' ).append(_confirm_template);
    
    jQuery( '#confirm-box-expanded' ).show(100);        
}

jQuery(document).on("click","#confirm-box-expanded-content>.closebutton, #confirm-box-expanded > #confirm-box-expanded-wrapper, #confirm-box-expanded-container .response-box .reject-button", function(){ 
     jQuery( '#confirm-box-expanded' ).remove();  
});


function getPendingContent( _element, _url, _params, _cached, _effect, _func_on_success, _func_on_complete ){
    var _elem_array = new Array()
    var _url_array = new Array();
    var _params_array = new Array();
    var _cached_array = new Array();
    if(typeof(_element) == 'object' && ! (_element instanceof $ ) ){
        _elem_array =  _element;
        _element =  _elem_array.shift();
        _url_array =  _url;
        _url =  _url_array.shift();
        if(typeof(_params) == 'undefined' ) _params_array = [];
        else {
            _params_array =  _params;
            if(_params_array.length>0) _params =  _params_array.shift();
            else  _params = {ajax: true};
        } 
        if(typeof(_cached) == 'undefined' || !_cached) _cached_array = [];
        else {
            _cached_array =  _cached;
            if(_cached_array.length>0) _cached =  _cached_array.shift();
            else  _cached = false;
        } 
    } 
    var elem = _element;
    if(typeof(_element) == 'string' ) elem = jQuery(_element);     
    else if( _element instanceof $ ) elem = _element;
    if(_element.length > 0){
        if(typeof(_params) == 'undefined' || !_params) _params = {ajax: true};
        else{
            if(typeof(_params) == 'string' ) _params = JSON.parse(_params);
            _params.ajax = true;
        } 
        if(typeof(_cached) == 'undefined' ) _cached = false;
        elem.addClass( 'waiting' );
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: _cached,
            url: _url, data: _params,
            success: function(msg){
                if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok && typeof(msg.html)=='string' && msg.html.length) {
                    elem.removeClass( 'waiting' );
                    if(typeof(_effect) == 'undefined' ) {
                        elem.fadeOut(100,function(){
                            elem.html(msg.html).fadeIn(200);
                        });
                    } else {
                        elem.html(msg.html);
                    } 
                    setTimeout(function(){
                       jQuery( '.lazy' ).lazy({
                            effect: 'fadeIn',
                            visibleOnly: true,
                            afterLoad: function(element) {
                                element.removeClass( 'lazy' );
                            }
                        })  
                        
                    },150)
                    if(typeof(_func_on_success) == 'object' || typeof(_func_on_success) == 'function' ) {
                        _func_on_success( msg );
                    }
                    
                } else {
                    
                }
                if(typeof(_elem_array) == 'object' && _elem_array.length>0) getPendingContent(_elem_array, _url_array, _params_array, _cached_array);
                return msg;
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                
            },
            complete: function(){
                if(typeof(_func) == 'object' || typeof(_func) == 'function' ) {
                    _func;
                }
            }
        });
    }
    return true;
}
function getPending(_url, _params, _updatefield, _func_on_success){
    if(typeof(_params) == 'undefined' || !_params) _params = {ajax: true};
    else _params.ajax = true;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url, data: _params,
            success: function(msg){
                if(_updatefield) jQuery(_updatefield).html(msg.new_value);
                if(typeof _func_on_success == "function") return _func_on_success.call(this, msg);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log( 'XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!' );
                return false;
            }
        });
}
/* cookie functions */
function setBSNCookie(name, value, expiredays, path, domain, secure){
    var cookie_string = name+"="+escape(value);
    if(expiredays){
        var exdate=new Date();
        exdate.setDate(exdate.getDate()+expiredays);
        cookie_string += "; expires=" + exdate.toGMTString();
    }
    if(path) cookie_string += "; path="+escape(path);
    if(domain) cookie_string += "; domain="+escape(domain);
    if(secure) cookie_string += "; secure";
    document.cookie = cookie_string;
}
function getBSNCookie(name){
    var cookie=" "+document.cookie;
    var search=" "+name+"=";
    var setStr=null;
    var offset=0;
    var end=0;
    if(cookie.length>0){
        offset=cookie.indexOf(search);
        if(offset!=-1){
            offset+=search.length;
            end=cookie.indexOf(";",offset);
            if(end==-1) end=cookie.length;
            setStr=unescape(cookie.substring(offset,end));
        }
    }
    return setStr;
}
function getParameterByName(name)
{
    return decodeURI(
            (RegExp(name + '=' + '(.+?)(&|$)' ).exec(location.search)||[,null])[1]
        );
}
jQuery(document).ready(function(){
    
    /**
    * Загрузка ТГБ (лево, право) 
    */
    var _payed_format = false;
    if( jQuery( '.payed-format' ).length == 0 ) {
        
        var _banners = _banners_urls = [];
        if(jQuery('#top-banner').length > 0) { _banners.push( '#top-banner' ); _banners_urls.push( '/ab/top/' )};
        if(jQuery('#right-top-banner').length > 0) { _banners.push( '#right-top-banner' ); _banners_urls.push( '/ab/right/' )};
        if(jQuery('#middle-bottom-banner').length > 0) { _banners.push( '#middle-bottom-banner' ); _banners_urls.push( '/ab/bottom/' )};
        if(jQuery('#back-banner').length > 0) { _banners.push( '#back-banner' ); _banners_urls.push( '/ab/body/' )};
        if(jQuery('#mainpage-banner').length > 0) { _banners.push( '#mainpage-banner' ); _banners_urls.push( '/ab/mainpage/new/' )};

        if( _banners.length > 0 ) getPendingContent( _banners, _banners_urls );

        /**
        * Обработка клика на баннер
        */
        jQuery(document).on( 'click', '.banner-item', function(e){
            var _el = jQuery(this);
            if(typeof _el.data( 'id' ) != "undefined") {
                var _wrap = _el.parents( 'div' ).parents( 'div' );
                var _params = {id:_el.data( 'id' ) };
                getPending('/ab/click/',_params) 
            }
        });                    
    }
    
    /**
    * Обработка клика на ТГБ
    */
    jQuery(document).on( 'click', 'div.tgb span', function(e){
        var _el = jQuery(this);
        if(typeof _el.data( 'id' ) != "undefined") {
            var _wrap = _el.parents( 'div' ).parents( 'div' );
            var _params = { 
                id:_el.data( 'id' ), 
                estate_type: typeof  _wrap.data( 'estate-type' ) != "undefined" > 0 ? _wrap.data( 'estate-type' ) : '' 
            };
            getPending( '/tgb/click/',_params );
        }
    });
    
    jQuery( document ).on( 'click', '.credit-box_item', function(e){
        try{
            _gaq.push(['_trackEvent', 'Целевое действие', 'Ипотека',,, false]);
        }catch(e){
            
        }
        var _el = jQuery(this);
        var _params = {id:_el.attr('data-id' ), type:_el.attr('data-type')};
        getPending( '/credit_calculator/click/',_params)
            
    });
    jQuery(document).on('click','div.context-block', function(e){
        var _el = jQuery(this);
        var referrer = document.referrer;
        var _from = '';
        //записываем набор классов элемента, по которому в php определим откуда был клик
        _from = jQuery(this).attr('class').toString();
        var _params = {id:_el.attr('data-id'),from:_from,'ref':referrer};
        getPending('/context_campaigns/click/',_params);
    });

    /* popup */
    jQuery( '.popup' ).each(function(){ jQuery(this).popupWindow({}) });
    
    /* pending content */
    jQuery( '.pending' ).each(function(){ 
        getPendingContent( jQuery(this), jQuery(this).data( 'url' ) );
    });
    
    /* span links manage */
    jQuery(document).on("click",  ".external-link",  function(e){
       if(jQuery(this).hasClass( 'disabled' )) return true;
       var _link = jQuery(this).data( 'link' );  
       if(_link.indexOf( 'http://' ) == -1 && _link.indexOf( 'https://' ) == -1) _link = 'http://'+_link; 
       window.open(_link);
      
    });
    
    jQuery(document).on("click", ".spec-offers-list-remove", function(e){
        var _parent =  jQuery(this).parents( '.spec-offers-list' );
        _parent.toggleClass( 'expanded' );
        carouselScrollItemsLog(_parent.children( 'ul' ).children( 'li' ),_parent.data( 'index' ),99)
        return false;
    }) 
    
    jQuery(document).on("click", ".internal-link", function( e ){
        var _link = "";
        if(jQuery(this).hasClass( 'disabled' ) || jQuery(this).prop("tagName") == 'A' ) return true;
        if (jQuery(this).data( 'link' )!== undefined)
            _link = jQuery(this).data( 'link' );
        else
            _link = jQuery(this).parent( '.star' ).data( 'link' ); 
        //если указано, открываем в новой вкладке
        if(jQuery(this).data( 'new-tab' )!==undefined)
            window.open(_link);
        else
            document.location.href = _link;
        return false; 
    });
    
    jQuery( 'button.disabled, .button.disabled' ).on( 'click', function(){
        return false;
    })
    
     _debug = jQuery( '#debug' ).length > 0;
            
    jQuery( '.switcher i' ).on( 'click', function(){
        jQuery(this).toggleClass( 'active' );
    })    

    jQuery( '.mobile-version' ).on( 'click', function(){
        setBSNCookie( 'desktop_version', false, -35600, '', 'bsn.ru' );    
        window.location.reload();
    })
    
    jQuery(document).on( 'click','.ajax-search-results .paginator-link',function(e){
        var _class = '.' + (jQuery(this).closest( '.ajax-search-results' ).attr( 'class' )).replace(/ /ig, '.' );
        getPendingContent( _class, jQuery(this).attr( 'href' ),false,false,false,false,false,function(){
            var _elem_offset = jQuery( _class ).length > 0 && parseInt( jQuery( _class ).offset().top ) > 0 ? parseInt( jQuery( _class ).offset().top ) : parseInt( jQuery( '#fast-search-form' ).offset().top );
            $("html,body").animate({ scrollTop: _elem_offset  - jQuery( '.topmenu' ).height() - 40 }, "slow");
        });    
        return false;  
    })
                  
    scrollToGetParam();
    //topline
    if(jQuery('.top-banner-wrapper').length > 0){
        jQuery('.top-banner-wrapper .closebutton').on('click', function(){
            getPending('/ab/topline/', {action: 'off'} );
            jQuery('.top-banner-wrapper').slideUp(300, function(){
                jQuery('.top-banner-wrapper').remove();    
            });
            return false;
        })
    }    
    //видимость кнопки регистрации
    jQuery(document).on( 'click', '.modal-inner .terms label', function(){
        if( !jQuery(this).hasClass( 'on' ) ) jQuery( '.registration #submit_button', jQuery( '.modal-inner' )).removeClass( 'disabled' );
        else jQuery( '.registration #submit_button', jQuery( '.modal-inner' )).addClass( 'disabled' );
    })
    
    menuWidth();
    jQuery( window ).resize(menuWidth);
    function menuWidth(){
        var _wrapper_width = 1180 ;
        var _width = parseInt( jQuery( window ).width() ) ;
        jQuery( '.topmenu .topmenu-secondlevel' ).css( { 'left' : '-' + ( _width - _wrapper_width ) / 2 + 'px', 'right' : '-' + ( _width - _wrapper_width ) / 2 + 'px' } )
    }
    
   
    if( _debug ){
        window.onerror = function myErrorHandler( errorMsg, url, lineNumber ) {
            alert( "Error occured: " + errorMsg );//or any message
            return false;
        }
    }  
    
    jQuery( '.scroll-to' ).on( 'click', function(){
        var _target = jQuery(this).data( 'target' );
        if( jQuery( '[name=' + _target + ']' ).length > 0 ){
            var _offset = jQuery( '[name=' + _target + ']' ).offset().top - jQuery( 'header' ).height() + 40;
            jQuery( "html,body" ).animate({ scrollTop: _offset }, "slow");    
        }
    })
    
    
    jQuery( '.lazy' ).lazy({
        effect: 'fadeIn',
        visibleOnly: true,
        enableThrottle: true,
        throttle: 250,
        afterLoad: function(element) {
            element.removeClass( 'lazy' );
        }
    })


    // advert manages
    setTimeout(function(){
        jQuery('.advert,.banner-item').each(function(){
            var _this = jQuery(this);
            console.log( _this )
            let advert_box_template = '<div class="advert-box">' +
                '<span class="advert-box__close" data-icon="close"></span>' +
                '<span class="advert-box__title">Рекламное объвление</span>' +
                ( _this.data('token') ? '<span class="advert-box__item">Номер: ' + _this.data('token') + '</span>' : '' ) +
                '<span class="advert-box__item"><a class="advert-box__link" href="' + ( _this.data('advert-url') ? _this.data('advert-url') : _this.data('link') ) + '" target="_blank">О рекламодателе</a></span>' +
                '<span class="advert-box__item"><a class="advert-box__link" href="https://www.bsn.ru/about/" target="_blank">Реклама  ООО "Петросервис"</a></span>' +
                '</div>'
            _this.prepend('<div class="advert-panel">Реклама</div>')
            _this.prepend(advert_box_template)
            jQuery('.advert-panel', _this).on('click', function(){
                _this.addClass('advert-is-active');
                return false;
            })
            jQuery('.advert-box__close', _this).on('click', function(){
                _this.removeClass('advert-is-active');
                return false;
            })
            jQuery('.advert-box', _this).on('click', function(e){
                if(jQuery(e.target).attr('href')) {
                    window.open(jQuery(e.target).attr('href'));
                    return false;
                }
                if( !jQuery(e.target).is('.advert-box__link') ) return false;

            })
        })
    }, 1100)
})
function validateEmail(email) { 
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}
function popupNewWindow(url, title, w, h) {
  var left = (screen.width/2)-(w/2);
  var top = (screen.height/2)-(h/2);
  return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
} 
function makeSuffix(number, titles)  
{  
    cases = [2, 0, 1, 1, 1, 2];  
    return titles[ (number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5] ];  
}
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '' )
    .replace(/[^0-9+\-Ee.]/g, '' );
    var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined' ) ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined' ) ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + (Math.round(n * k) / k)
        .toFixed(prec);
    };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
    .split( '.' );
    if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '' )
    .length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1)
      .join( '0' );
    }
    return s.join(dec);
}
function scrollToGetParam(){
    var _url = window.location.href.match(/(\?|\&)scrollto\=[^&$]+/);
    if(_url == null) return true;
    _url = _url.shift();
    var _elem = jQuery( '#' + _url.split( '=' )[1]).offset().top - jQuery( '.topmenu' ).height() - 20;
    $("html,body").animate({ scrollTop: _elem }, "slow");
}
function popupwindow(url, title, w, h) {
  var left = (screen.width/2)-(w/2);
  var top = (screen.height/2)-(h/2);
  return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
} 
function formattedNumber(t) {
    return t.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ")
}
