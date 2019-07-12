/* Modernizr 2.6.2 (Custom Build) | MIT & BSD
 * Build: http://modernizr.com/download/#-csstransforms-csstransitions-touch-shiv-cssclasses-prefixed-teststyles-testprop-testallprops-prefixes-domprefixes-load
 */
;window.Modernizr=function(a,b,c){function z(a){j.cssText=a}function A(a,b){return z(m.join(a+";")+(b||""))}function B(a,b){return typeof a===b}function C(a,b){return!!~(""+a).indexOf(b)}function D(a,b){for(var d in a){var e=a[d];if(!C(e,"-")&&j[e]!==c)return b=="pfx"?e:!0}return!1}function E(a,b,d){for(var e in a){var f=b[a[e]];if(f!==c)return d===!1?a[e]:B(f,"function")?f.bind(d||b):f}return!1}function F(a,b,c){var d=a.charAt(0).toUpperCase()+a.slice(1),e=(a+" "+o.join(d+" ")+d).split(" ");return B(b,"string")||B(b,"undefined")?D(e,b):(e=(a+" "+p.join(d+" ")+d).split(" "),E(e,b,c))}var d="2.6.2",e={},f=!0,g=b.documentElement,h="modernizr",i=b.createElement(h),j=i.style,k,l={}.toString,m=" -webkit- -moz- -o- -ms- ".split(" "),n="Webkit Moz O ms",o=n.split(" "),p=n.toLowerCase().split(" "),q={},r={},s={},t=[],u=t.slice,v,w=function(a,c,d,e){var f,i,j,k,l=b.createElement("div"),m=b.body,n=m||b.createElement("body");if(parseInt(d,10))while(d--)j=b.createElement("div"),j.id=e?e[d]:h+(d+1),l.appendChild(j);return f=["&#173;",'<style id="s',h,'">',a,"</style>"].join(""),l.id=h,(m?l:n).innerHTML+=f,n.appendChild(l),m||(n.style.background="",n.style.overflow="hidden",k=g.style.overflow,g.style.overflow="hidden",g.appendChild(n)),i=c(l,a),m?l.parentNode.removeChild(l):(n.parentNode.removeChild(n),g.style.overflow=k),!!i},x={}.hasOwnProperty,y;!B(x,"undefined")&&!B(x.call,"undefined")?y=function(a,b){return x.call(a,b)}:y=function(a,b){return b in a&&B(a.constructor.prototype[b],"undefined")},Function.prototype.bind||(Function.prototype.bind=function(b){var c=this;if(typeof c!="function")throw new TypeError;var d=u.call(arguments,1),e=function(){if(this instanceof e){var a=function(){};a.prototype=c.prototype;var f=new a,g=c.apply(f,d.concat(u.call(arguments)));return Object(g)===g?g:f}return c.apply(b,d.concat(u.call(arguments)))};return e}),q.touch=function(){var c;return"ontouchstart"in a||a.DocumentTouch&&b instanceof DocumentTouch?c=!0:w(["@media (",m.join("touch-enabled),("),h,")","{#modernizr{top:9px;position:absolute}}"].join(""),function(a){c=a.offsetTop===9}),c},q.csstransforms=function(){return!!F("transform")},q.csstransitions=function(){return F("transition")};for(var G in q)y(q,G)&&(v=G.toLowerCase(),e[v]=q[G](),t.push((e[v]?"":"no-")+v));return e.addTest=function(a,b){if(typeof a=="object")for(var d in a)y(a,d)&&e.addTest(d,a[d]);else{a=a.toLowerCase();if(e[a]!==c)return e;b=typeof b=="function"?b():b,typeof f!="undefined"&&f&&(g.className+=" "+(b?"":"no-")+a),e[a]=b}return e},z(""),i=k=null,function(a,b){function k(a,b){var c=a.createElement("p"),d=a.getElementsByTagName("head")[0]||a.documentElement;return c.innerHTML="x<style>"+b+"</style>",d.insertBefore(c.lastChild,d.firstChild)}function l(){var a=r.elements;return typeof a=="string"?a.split(" "):a}function m(a){var b=i[a[g]];return b||(b={},h++,a[g]=h,i[h]=b),b}function n(a,c,f){c||(c=b);if(j)return c.createElement(a);f||(f=m(c));var g;return f.cache[a]?g=f.cache[a].cloneNode():e.test(a)?g=(f.cache[a]=f.createElem(a)).cloneNode():g=f.createElem(a),g.canHaveChildren&&!d.test(a)?f.frag.appendChild(g):g}function o(a,c){a||(a=b);if(j)return a.createDocumentFragment();c=c||m(a);var d=c.frag.cloneNode(),e=0,f=l(),g=f.length;for(;e<g;e++)d.createElement(f[e]);return d}function p(a,b){b.cache||(b.cache={},b.createElem=a.createElement,b.createFrag=a.createDocumentFragment,b.frag=b.createFrag()),a.createElement=function(c){return r.shivMethods?n(c,a,b):b.createElem(c)},a.createDocumentFragment=Function("h,f","return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&("+l().join().replace(/\w+/g,function(a){return b.createElem(a),b.frag.createElement(a),'c("'+a+'")'})+");return n}")(r,b.frag)}function q(a){a||(a=b);var c=m(a);return r.shivCSS&&!f&&!c.hasCSS&&(c.hasCSS=!!k(a,"article,aside,figcaption,figure,footer,header,hgroup,nav,section{display:block}mark{background:#FF0;color:#000}")),j||p(a,c),a}var c=a.html5||{},d=/^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,e=/^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,f,g="_html5shiv",h=0,i={},j;(function(){try{var a=b.createElement("a");a.innerHTML="<xyz></xyz>",f="hidden"in a,j=a.childNodes.length==1||function(){b.createElement("a");var a=b.createDocumentFragment();return typeof a.cloneNode=="undefined"||typeof a.createDocumentFragment=="undefined"||typeof a.createElement=="undefined"}()}catch(c){f=!0,j=!0}})();var r={elements:c.elements||"abbr article aside audio bdi canvas data datalist details figcaption figure footer header hgroup mark meter nav output progress section summary time video",shivCSS:c.shivCSS!==!1,supportsUnknownElements:j,shivMethods:c.shivMethods!==!1,type:"default",shivDocument:q,createElement:n,createDocumentFragment:o};a.html5=r,q(b)}(this,b),e._version=d,e._prefixes=m,e._domPrefixes=p,e._cssomPrefixes=o,e.testProp=function(a){return D([a])},e.testAllProps=F,e.testStyles=w,e.prefixed=function(a,b,c){return b?F(a,b,c):F(a,"pfx")},g.className=g.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(f?" js "+t.join(" "):""),e}(this,this.document),function(a,b,c){function d(a){return"[object Function]"==o.call(a)}function e(a){return"string"==typeof a}function f(){}function g(a){return!a||"loaded"==a||"complete"==a||"uninitialized"==a}function h(){var a=p.shift();q=1,a?a.t?m(function(){("c"==a.t?B.injectCss:B.injectJs)(a.s,0,a.a,a.x,a.e,1)},0):(a(),h()):q=0}function i(a,c,d,e,f,i,j){function k(b){if(!o&&g(l.readyState)&&(u.r=o=1,!q&&h(),l.onload=l.onreadystatechange=null,b)){"img"!=a&&m(function(){t.removeChild(l)},50);for(var d in y[c])y[c].hasOwnProperty(d)&&y[c][d].onload()}}var j=j||B.errorTimeout,l=b.createElement(a),o=0,r=0,u={t:d,s:c,e:f,a:i,x:j};1===y[c]&&(r=1,y[c]=[]),"object"==a?l.data=c:(l.src=c,l.type=a),l.width=l.height="0",l.onerror=l.onload=l.onreadystatechange=function(){k.call(this,r)},p.splice(e,0,u),"img"!=a&&(r||2===y[c]?(t.insertBefore(l,s?null:n),m(k,j)):y[c].push(l))}function j(a,b,c,d,f){return q=0,b=b||"j",e(a)?i("c"==b?v:u,a,b,this.i++,c,d,f):(p.splice(this.i++,0,a),1==p.length&&h()),this}function k(){var a=B;return a.loader={load:j,i:0},a}var l=b.documentElement,m=a.setTimeout,n=b.getElementsByTagName("script")[0],o={}.toString,p=[],q=0,r="MozAppearance"in l.style,s=r&&!!b.createRange().compareNode,t=s?l:n.parentNode,l=a.opera&&"[object Opera]"==o.call(a.opera),l=!!b.attachEvent&&!l,u=r?"object":l?"script":"img",v=l?"script":u,w=Array.isArray||function(a){return"[object Array]"==o.call(a)},x=[],y={},z={timeout:function(a,b){return b.length&&(a.timeout=b[0]),a}},A,B;B=function(a){function b(a){var a=a.split("!"),b=x.length,c=a.pop(),d=a.length,c={url:c,origUrl:c,prefixes:a},e,f,g;for(f=0;f<d;f++)g=a[f].split("="),(e=z[g.shift()])&&(c=e(c,g));for(f=0;f<b;f++)c=x[f](c);return c}function g(a,e,f,g,h){var i=b(a),j=i.autoCallback;i.url.split(".").pop().split("?").shift(),i.bypass||(e&&(e=d(e)?e:e[a]||e[g]||e[a.split("/").pop().split("?")[0]]),i.instead?i.instead(a,e,f,g,h):(y[i.url]?i.noexec=!0:y[i.url]=1,f.load(i.url,i.forceCSS||!i.forceJS&&"css"==i.url.split(".").pop().split("?").shift()?"c":c,i.noexec,i.attrs,i.timeout),(d(e)||d(j))&&f.load(function(){k(),e&&e(i.origUrl,h,g),j&&j(i.origUrl,h,g),y[i.url]=2})))}function h(a,b){function c(a,c){if(a){if(e(a))c||(j=function(){var a=[].slice.call(arguments);k.apply(this,a),l()}),g(a,j,b,0,h);else if(Object(a)===a)for(n in m=function(){var b=0,c;for(c in a)a.hasOwnProperty(c)&&b++;return b}(),a)a.hasOwnProperty(n)&&(!c&&!--m&&(d(j)?j=function(){var a=[].slice.call(arguments);k.apply(this,a),l()}:j[n]=function(a){return function(){var b=[].slice.call(arguments);a&&a.apply(this,b),l()}}(k[n])),g(a[n],j,b,n,h))}else!c&&l()}var h=!!a.test,i=a.load||a.both,j=a.callback||f,k=j,l=a.complete||f,m,n;c(h?a.yep:a.nope,!!i),i&&c(i)}var i,j,l=this.yepnope.loader;if(e(a))g(a,0,l,0);else if(w(a))for(i=0;i<a.length;i++)j=a[i],e(j)?g(j,0,l,0):w(j)?B(j):Object(j)===j&&h(j,l);else Object(a)===a&&h(a,l)},B.addPrefix=function(a,b){z[a]=b},B.addFilter=function(a){x.push(a)},B.errorTimeout=1e4,null==b.readyState&&b.addEventListener&&(b.readyState="loading",b.addEventListener("DOMContentLoaded",A=function(){b.removeEventListener("DOMContentLoaded",A,0),b.readyState="complete"},0)),a.yepnope=k(),a.yepnope.executeStack=h,a.yepnope.injectJs=function(a,c,d,e,i,j){var k=b.createElement("script"),l,o,e=e||B.errorTimeout;k.src=a;for(o in d)k.setAttribute(o,d[o]);c=j?h:c||f,k.onreadystatechange=k.onload=function(){!l&&g(k.readyState)&&(l=1,c(),k.onload=k.onreadystatechange=null)},m(function(){l||(l=1,c(1))},e),i?k.onload():n.parentNode.insertBefore(k,n)},a.yepnope.injectCss=function(a,c,d,e,g,i){var e=b.createElement("link"),j,c=i?h:c||f;e.href=a,e.rel="stylesheet",e.type="text/css";for(j in d)e.setAttribute(j,d[j]);g||(n.parentNode.insertBefore(e,n),m(c,0))}}(this,document),Modernizr.load=function(){yepnope.apply(window,[].slice.call(arguments,0))};
(function() {

    var event = jQuery.event,

        //helper that finds handlers by type and calls back a function, this is basically handle
        // events - the events object
        // types - an array of event types to look for
        // callback(type, handlerFunc, selector) - a callback
        // selector - an optional selector to filter with, if there, matches by selector
        //     if null, matches anything, otherwise, matches with no selector
        findHelper = function( events, types, callback, selector ) {
            var t, type, typeHandlers, all, h, handle, 
                namespaces, namespace,
                match;
            for ( t = 0; t < types.length; t++ ) {
                type = types[t];
                all = type.indexOf(".") < 0;
                if (!all ) {
                    namespaces = type.split(".");
                    type = namespaces.shift();
                    namespace = new RegExp("(^|\\.)" + namespaces.slice(0).sort().join("\\.(?:.*\\.)?") + "(\\.|$)");
                }
                typeHandlers = (events[type] || []).slice(0);

                for ( h = 0; h < typeHandlers.length; h++ ) {
                    handle = typeHandlers[h];
                    
                    match = (all || namespace.test(handle.namespace));
                    
                    if(match){
                        if(selector){
                            if (handle.selector === selector  ) {
                                callback(type, handle.origHandler || handle.handler);
                            }
                        } else if (selector === null){
                            callback(type, handle.origHandler || handle.handler, handle.selector);
                        }
                        else if (!handle.selector ) {
                            callback(type, handle.origHandler || handle.handler);
                            
                        } 
                    }
                    
                    
                }
            }
        };

    /**
     * Finds event handlers of a given type on an element.
     * @param {HTMLElement} el
     * @param {Array} types an array of event names
     * @param {String} [selector] optional selector
     * @return {Array} an array of event handlers
     */
    event.find = function( el, types, selector ) {
        var events = ( $._data(el) || {} ).events,
            handlers = [],
            t, liver, live;

        if (!events ) {
            return handlers;
        }
        findHelper(events, types, function( type, handler ) {
            handlers.push(handler);
        }, selector);
        return handlers;
    };
    /**
     * Finds all events.  Group by selector.
     * @param {HTMLElement} el the element
     * @param {Array} types event types
     */
    event.findBySelector = function( el, types ) {
        var events = $._data(el).events,
            selectors = {},
            //adds a handler for a given selector and event
            add = function( selector, event, handler ) {
                var select = selectors[selector] || (selectors[selector] = {}),
                    events = select[event] || (select[event] = []);
                events.push(handler);
            };

        if (!events ) {
            return selectors;
        }
        //first check live:
        /*$.each(events.live || [], function( i, live ) {
            if ( $.inArray(live.origType, types) !== -1 ) {
                add(live.selector, live.origType, live.origHandler || live.handler);
            }
        });*/
        //then check straight binds
        findHelper(events, types, function( type, handler, selector ) {
            add(selector || "", type, handler);
        }, null);

        return selectors;
    };
    event.supportTouch = "ontouchend" in document;
    
    $.fn.respondsTo = function( events ) {
        if (!this.length ) {
            return false;
        } else {
            //add default ?
            return event.find(this[0], $.isArray(events) ? events : [events]).length > 0;
        }
    };
    $.fn.triggerHandled = function( event, data ) {
        event = (typeof event == "string" ? $.Event(event) : event);
        this.trigger(event, data);
        return event.handled;
    };
    /**
     * Only attaches one event handler for all types ...
     * @param {Array} types llist of types that will delegate here
     * @param {Object} startingEvent the first event to start listening to
     * @param {Object} onFirst a function to call 
     */
    event.setupHelper = function( types, startingEvent, onFirst ) {
        if (!onFirst ) {
            onFirst = startingEvent;
            startingEvent = null;
        }
        var add = function( handleObj ) {

            var bySelector, selector = handleObj.selector || "";
            if ( selector ) {
                bySelector = event.find(this, types, selector);
                if (!bySelector.length ) {
                    $(this).delegate(selector, startingEvent, onFirst);
                }
            }
            else {
                //var bySelector = event.find(this, types, selector);
                if (!event.find(this, types, selector).length ) {
                    event.add(this, startingEvent, onFirst, {
                        selector: selector,
                        delegate: this
                    });
                }

            }

        },
            remove = function( handleObj ) {
                var bySelector, selector = handleObj.selector || "";
                if ( selector ) {
                    bySelector = event.find(this, types, selector);
                    if (!bySelector.length ) {
                        $(this).undelegate(selector, startingEvent, onFirst);
                    }
                }
                else {
                    if (!event.find(this, types, selector).length ) {
                        event.remove(this, startingEvent, onFirst, {
                            selector: selector,
                            delegate: this
                        });
                    }
                }
            };
        $.each(types, function() {
            event.special[this] = {
                add: add,
                remove: remove,
                setup: function() {},
                teardown: function() {}
            };
        });
    };
})(jQuery);
(function($){
var isPhantom = /Phantom/.test(navigator.userAgent),
    supportTouch = !isPhantom && "ontouchend" in document,
    scrollEvent = "touchmove scroll",
    // Use touch events or map it to mouse events
    touchStartEvent = supportTouch ? "touchstart" : "mousedown",
    touchStopEvent = supportTouch ? "touchend" : "mouseup",
    touchMoveEvent = supportTouch ? "touchmove" : "mousemove",
    data = function(event){
        var d = event.originalEvent.touches ?
            event.originalEvent.touches[ 0 ] :
            event;
        return {
            time: (new Date).getTime(),
            coords: [ d.pageX, d.pageY ],
            origin: $( event.target )
        };
    };

/**
 * @add jQuery.event.swipe
 */
var swipe = $.event.swipe = {
    /**
     * @attribute delay
     * Delay is the upper limit of time the swipe motion can take in milliseconds.  This defaults to 500.
     * 
     * A user must perform the swipe motion in this much time.
     */
    delay : 500,
    /**
     * @attribute max
     * The maximum distance the pointer must travel in pixels.  The default is 75 pixels.
     */
    max : 75,
    /**
     * @attribute min
     * The minimum distance the pointer must travel in pixels.  The default is 30 pixels.
     */
    min : 30
};

$.event.setupHelper( [

/**
 * @hide
 * @attribute swipe
 */
"swipe",
/**
 * @hide
 * @attribute swipeleft
 */
'swipeleft',
/**
 * @hide
 * @attribute swiperight
 */
'swiperight',
/**
 * @hide
 * @attribute swipeup
 */
'swipeup',
/**
 * @hide
 * @attribute swipedown
 */
'swipedown'], touchStartEvent, function(ev){
    var
        // update with data when the event was started
        start = data(ev),
        stop,
        delegate = ev.delegateTarget || ev.currentTarget,
        selector = ev.handleObj.selector,
        entered = this;
    
    function moveHandler(event){
        if ( !start ) {
            return;
        }
        // update stop with the data from the current event
        stop = data(event);

        // prevent scrolling
        if ( Math.abs( start.coords[0] - stop.coords[0] ) > 10 ) {
            event.preventDefault();
        }
    };

    // Attach to the touch move events
    $(document.documentElement).bind(touchMoveEvent, moveHandler)
        .one(touchStopEvent, function(event){
            $(this).unbind( touchMoveEvent, moveHandler);
            // if start and stop contain data figure out if we have a swipe event
            if ( start && stop ) {
                // calculate the distance between start and stop data
                var deltaX = Math.abs(start.coords[0] - stop.coords[0]),
                    deltaY = Math.abs(start.coords[1] - stop.coords[1]),
                    distance = Math.sqrt(deltaX*deltaX+deltaY*deltaY);

                // check if the delay and distance are matched
                if ( stop.time - start.time < swipe.delay && distance >= swipe.min ) {
                    var events = ['swipe'];
                    // check if we moved horizontally
                    if( deltaX >= swipe.min && deltaY < swipe.min) {
                        // based on the x coordinate check if we moved left or right
                        events.push( start.coords[0] > stop.coords[0] ? "swipeleft" : "swiperight" );
                    } else
                    // check if we moved vertically
                    if(deltaY >= swipe.min && deltaX < swipe.min){
                        // based on the y coordinate check if we moved up or down
                        events.push( start.coords[1] < stop.coords[1] ? "swipedown" : "swipeup" );
                    }

                    // trigger swipe events on this guy
                    $.each($.event.find(delegate, events, selector), function(){
                        this.call(entered, ev, {start : start, end: stop})
                    })
                
                }
            }
            // reset start and stop
            start = stop = undefined;
        })
});

})(jQuery)
/**
 * jquery.elastislide.js v1.1.0
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * Copyright 2012, Codrops
 * http://www.codrops.com
 */

;( function( $, window, undefined ) {
    
    'use strict';

    /*
    * debouncedresize: special jQuery event that happens once after a window resize
    *
    * latest version and complete README available on Github:
    * https://github.com/louisremi/jquery-smartresize/blob/master/jquery.debouncedresize.js
    *
    * Copyright 2011 @louis_remi
    * Licensed under the MIT license.
    */
    var $event = $.event,
    $special,
    resizeTimeout;

    $special = $event.special.debouncedresize = {
        setup: function() {
            $( this ).on( "resize", $special.handler );
        },
        teardown: function() {
            $( this ).off( "resize", $special.handler );
        },
        handler: function( event, execAsap ) {
            // Save the context
            var context = this,
                args = arguments,
                dispatch = function() {
                    // set correct event type
                    event.type = "debouncedresize";
                    $event.dispatch.apply( context, args );
                };

            if ( resizeTimeout ) {
                clearTimeout( resizeTimeout );
            }

            execAsap ?
                dispatch() :
                resizeTimeout = setTimeout( dispatch, $special.threshold );
        },
        threshold: 150
    };

    // ======================= imagesLoaded Plugin ===============================
    // https://github.com/desandro/imagesloaded

    // $('#my-container').imagesLoaded(myFunction)
    // execute a callback when all images have loaded.
    // needed because .load() doesn't work on cached images

    // callback function gets image collection as argument
    //  this is the container

    // original: mit license. paul irish. 2010.
    // contributors: Oren Solomianik, David DeSandro, Yiannis Chatzikonstantinou

    // blank image data-uri bypasses webkit log warning (thx doug jones)
    var BLANK = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    $.fn.imagesLoaded = function( callback ) {
        var $this = this,
            deferred = $.isFunction($.Deferred) ? $.Deferred() : 0,
            hasNotify = $.isFunction(deferred.notify),
            $images = $this.find('img').add( $this.filter('img') ),
            loaded = [],
            proper = [],
            broken = [];

        // Register deferred callbacks
        if ($.isPlainObject(callback)) {
            $.each(callback, function (key, value) {
                if (key === 'callback') {
                    callback = value;
                } else if (deferred) {
                    deferred[key](value);
                }
            });
        }

        function doneLoading() {
            var $proper = $(proper),
                $broken = $(broken);

            if ( deferred ) {
                if ( broken.length ) {
                    deferred.reject( $images, $proper, $broken );
                } else {
                    deferred.resolve( $images );
                }
            }

            if ( $.isFunction( callback ) ) {
                callback.call( $this, $images, $proper, $broken );
            }
        }

        function imgLoaded( img, isBroken ) {
            // don't proceed if BLANK image, or image is already loaded
            if ( img.src === BLANK || $.inArray( img, loaded ) !== -1 ) {
                return;
            }

            // store element in loaded images array
            loaded.push( img );

            // keep track of broken and properly loaded images
            if ( isBroken ) {
                broken.push( img );
            } else {
                proper.push( img );
            }

            // cache image and its state for future calls
            $.data( img, 'imagesLoaded', { isBroken: isBroken, src: img.src } );

            // trigger deferred progress method if present
            if ( hasNotify ) {
                deferred.notifyWith( $(img), [ isBroken, $images, $(proper), $(broken) ] );
            }

            // call doneLoading and clean listeners if all images are loaded
            if ( $images.length === loaded.length ){
                setTimeout( doneLoading );
                $images.unbind( '.imagesLoaded' );
            }
        }

        // if no images, trigger immediately
        if ( !$images.length ) {
            doneLoading();
        } else {
            $images.bind( 'load.imagesLoaded error.imagesLoaded', function( event ){
                // trigger imgLoaded
                imgLoaded( event.target, event.type === 'error' );
            }).each( function( i, el ) {
                var src = el.src;

                // find out if this image has been already checked for status
                // if it was, and src has not changed, call imgLoaded on it
                var cached = $.data( el, 'imagesLoaded' );
                if ( cached && cached.src === src ) {
                    imgLoaded( el, cached.isBroken );
                    return;
                }

                // if complete is true and browser supports natural sizes, try
                // to check for image status manually
                if ( el.complete && el.naturalWidth !== undefined ) {
                    imgLoaded( el, el.naturalWidth === 0 || el.naturalHeight === 0 );
                    return;
                }

                // cached images don't fire load sometimes, so we reset src, but only when
                // dealing with IE, or image is complete (loaded) and failed manual check
                // webkit hack from http://groups.google.com/group/jquery-dev/browse_thread/thread/eee6ab7b2da50e1f
                if ( el.readyState || el.complete ) {
                    el.src = BLANK;
                    el.src = src;
                }
            });
        }

        return deferred ? deferred.promise( $this ) : $this;
    };

    // global
    var $window = $( window ),
        Modernizr = window.Modernizr;

    $.Elastislide = function( options, element ) {
        
        this.$el = $( element );
        this._init( options );
        
    };

    $.Elastislide.defaults = {
        // orientation 'horizontal' || 'vertical'
        orientation : 'horizontal',
        // sliding speed
        speed : 500,
        // sliding easing
        easing : 'ease-in-out',
        // the minimum number of items to show. 
        // when we resize the window, this will make sure minItems are always shown 
        // (unless of course minItems is higher than the total number of elements)
        minItems : 3,
        // index of the current item (left most item of the carousel)
        start : 0,
        // click item callback
        onClick : function( el, position, evt ) { return false; },
        onReady : function() { return false; },
        onBeforeSlide : function() { return false; },
        onAfterSlide : function() { return false; }
    };

    $.Elastislide.prototype = {

        _init : function( options ) {
            
            // options
            this.options = $.extend( true, {}, $.Elastislide.defaults, options );

            // https://github.com/twitter/bootstrap/issues/2870
            var self = this,
                transEndEventNames = {
                    'WebkitTransition' : 'webkitTransitionEnd',
                    'MozTransition' : 'transitionend',
                    'OTransition' : 'oTransitionEnd',
                    'msTransition' : 'MSTransitionEnd',
                    'transition' : 'transitionend'
                };
            
            this.transEndEventName = transEndEventNames[ Modernizr.prefixed( 'transition' ) ];
            
            // suport for css transforms and css transitions
            this.support = Modernizr.csstransitions && Modernizr.csstransforms;

            // current item's index
            this.current = this.options.start;

            // control if it's sliding
            this.isSliding = false;

            this.$items = this.$el.children( 'li' );
            // total number of items
            this.itemsCount = this.$items.length;
            if( this.itemsCount === 0 ) {

                return false;

            }
            this._validate();
            // remove white space
            this.$items.detach();
            this.$el.empty();
            this.$el.append( this.$items );

            // main wrapper
            this.$el.wrap( '<div class="elastislide-wrapper elastislide-loading elastislide-' + this.options.orientation + '"></div>' );

            // check if we applied a transition to the <ul>
            this.hasTransition = false;
            
            // add transition for the <ul>
            this.hasTransitionTimeout = setTimeout( function() {
                
                self._addTransition();

            }, 100 );

            // preload the images
            
            this.$el.imagesLoaded( function() {

                self.$el.show();

                self._layout();
                self._configure();
                
                if( self.hasTransition ) {

                    // slide to current's position
                    self._removeTransition();
                    self._slideToItem( self.current );

                    self.$el.on( self.transEndEventName, function() {

                        self.$el.off( self.transEndEventName );
                        self._setWrapperSize();
                        // add transition for the <ul>
                        self._addTransition();
                        self._initEvents();

                    } );

                }
                else {

                    clearTimeout( self.hasTransitionTimeout );
                    self._setWrapperSize();
                    self._initEvents();
                    // slide to current's position
                    self._slideToItem( self.current );
                    setTimeout( function() { self._addTransition(); }, 25 );

                }

                self.options.onReady();

            } );

        },
        _validate : function() {

            if( this.options.speed < 0 ) {

                this.options.speed = 500;

            }
            if( this.options.minItems < 1 || this.options.minItems > this.itemsCount ) {

                this.options.minItems = 1;

            }
            if( this.options.start < 0 || this.options.start > this.itemsCount - 1 ) {

                this.options.start = 0;

            }
            if( this.options.orientation != 'horizontal' && this.options.orientation != 'vertical' ) {

                this.options.orientation = 'horizontal';

            }
                
        },
        _layout : function() {

            this.$el.wrap( '<div class="elastislide-carousel"></div>' );

            this.$carousel = this.$el.parent();
            this.$wrapper = this.$carousel.parent().removeClass( 'elastislide-loading' );

            // save original image sizes
            var $img = this.$items.find( 'img:first' );
            this.imgSize = { width : $img.outerWidth( true ), height : $img.outerHeight( true ) };

            this._setItemsSize();
            this.options.orientation === 'horizontal' ? this.$el.css( 'max-height', this.imgSize.height ) : this.$el.css( 'height', this.options.minItems * this.imgSize.height );

            // add the controls
            this._addControls();

        },
        _addTransition : function() {

            if( this.support ) {

                this.$el.css( 'transition', 'all ' + this.options.speed + 'ms ' + this.options.easing );
                
            }
            this.hasTransition = true;

        },
        _removeTransition : function() {

            if( this.support ) {

                this.$el.css( 'transition', 'all 0s' );

            }
            this.hasTransition = false;
            
        },
        _addControls : function() {

            var self = this;

            // add navigation elements
            this.$navigation = $( '<nav><span class="elastislide-prev">Previous</span><span class="elastislide-next">Next</span></nav>' )
                .appendTo( this.$wrapper );


            this.$navPrev = this.$navigation.find( 'span.elastislide-prev' ).on( 'mousedown.elastislide', function( event ) {

                self._slide( 'prev' );
                return false;

            } );

            this.$navNext = this.$navigation.find( 'span.elastislide-next' ).on( 'mousedown.elastislide', function( event ) {

                self._slide( 'next' );
                return false;

            } );

        },
        _setItemsSize : function() {

            // width for the items (%)
            var w = this.options.orientation === 'horizontal' ? ( Math.floor( this.$carousel.width() / this.options.minItems ) * 100 ) / this.$carousel.width() : 100;
            
            this.$items.css( {
                'width' : w + '%',
                'max-width' : this.imgSize.width,
                'max-height' : this.imgSize.height
            } );

            if( this.options.orientation === 'vertical' ) {
            
                this.$wrapper.css( 'max-width', this.imgSize.width + parseInt( this.$wrapper.css( 'padding-left' ) ) + parseInt( this.$wrapper.css( 'padding-right' ) ) );
            
            }

        },
        _setWrapperSize : function() {

            if( this.options.orientation === 'vertical' ) {

                this.$wrapper.css( {
                    'height' : this.options.minItems * this.imgSize.height + parseInt( this.$wrapper.css( 'padding-top' ) ) + parseInt( this.$wrapper.css( 'padding-bottom' ) )
                } );

            }

        },
        _configure : function() {

            // check how many items fit in the carousel (visible area -> this.$carousel.width() )
            this.fitCount = this.options.orientation === 'horizontal' ? 
                                this.$carousel.width() < this.options.minItems * this.imgSize.width ? this.options.minItems : Math.floor( this.$carousel.width() / this.imgSize.width ) :
                                this.$carousel.height() < this.options.minItems * this.imgSize.height ? this.options.minItems : Math.floor( this.$carousel.height() / this.imgSize.height );

        },
        _initEvents : function() {

            var self = this;

            $window.on( 'debouncedresize.elastislide', function() {

                self._setItemsSize();
                self._configure();
                self._slideToItem( self.current );

            } );

            this.$el.on( this.transEndEventName, function() {

                self._onEndTransition();

            } );

            if( this.options.orientation === 'horizontal' ) {

                this.$el.on( {
                    swipeleft : function() {

                        self._slide( 'next' );
                    
                    },
                    swiperight : function() {

                        self._slide( 'prev' );
                    
                    }
                } );

            }
            else {

                this.$el.on( {
                    swipeup : function() {

                        self._slide( 'next' );
                    
                    },
                    swipedown : function() {

                        self._slide( 'prev' );
                    
                    }
                } );

            }

            // item click event
            this.$el.on( 'click.elastislide', 'li', function( event ) {

                var $item = $( this );

                self.options.onClick( $item, $item.index(), event );
                
            });

        },
        _destroy : function( callback ) {
            
            this.$el.off( this.transEndEventName ).off( 'swipeleft swiperight swipeup swipedown .elastislide' );
            $window.off( '.elastislide' );
            
            this.$el.css( {
                'max-height' : 'none',
                'transition' : 'none'
            } ).unwrap( this.$carousel ).unwrap( this.$wrapper );

            this.$items.css( {
                'width' : 'auto',
                'max-width' : 'none',
                'max-height' : 'none'
            } );

            this.$navigation.remove();
            this.$wrapper.remove();

            if( callback ) {

                callback.call();

            }

        },
        _toggleControls : function( dir, display ) {

            if( display ) {

                ( dir === 'next' ) ? this.$navNext.show() : this.$navPrev.show();

            }
            else {

                ( dir === 'next' ) ? this.$navNext.hide() : this.$navPrev.hide();

            }
            
        },
        _slide : function( dir, tvalue ) {

            if( this.isSliding ) {

                return false;

            }
            
            this.options.onBeforeSlide();

            this.isSliding = true;

            var self = this,
                translation = this.translation || 0,
                // width/height of an item ( <li> )
                itemSpace = this.options.orientation === 'horizontal' ? this.$items.outerWidth( true ) : this.$items.outerHeight( true ),
                // total width/height of the <ul>
                totalSpace = this.itemsCount * itemSpace,
                // visible width/height
                visibleSpace = this.options.orientation === 'horizontal' ? this.$carousel.width() : this.$carousel.height();
            
            if( tvalue === undefined ) {
                
                var amount = this.fitCount * itemSpace;

                if( amount < 0 ) {

                    return false;

                }

                if( dir === 'next' && totalSpace - ( Math.abs( translation ) + amount ) < visibleSpace ) {

                    amount = totalSpace - ( Math.abs( translation ) + visibleSpace );

                    // show / hide navigation buttons
                    this._toggleControls( 'next', false );
                    this._toggleControls( 'prev', true );

                }
                else if( dir === 'prev' && Math.abs( translation ) - amount < 0 ) {

                    amount = Math.abs( translation );

                    // show / hide navigation buttons
                    this._toggleControls( 'next', true );
                    this._toggleControls( 'prev', false );

                }
                else {
                    
                    // future translation value
                    var ftv = dir === 'next' ? Math.abs( translation ) + Math.abs( amount ) : Math.abs( translation ) - Math.abs( amount );
                    
                    // show / hide navigation buttons
                    ftv > 0 ? this._toggleControls( 'prev', true ) : this._toggleControls( 'prev', false );
                    ftv < totalSpace - visibleSpace ? this._toggleControls( 'next', true ) : this._toggleControls( 'next', false );
                        
                }
                
                tvalue = dir === 'next' ? translation - amount : translation + amount;

            }
            else {

                var amount = Math.abs( tvalue );

                if( Math.max( totalSpace, visibleSpace ) - amount < visibleSpace ) {

                    tvalue    = - ( Math.max( totalSpace, visibleSpace ) - visibleSpace );
                
                }

                // show / hide navigation buttons
                amount > 0 ? this._toggleControls( 'prev', true ) : this._toggleControls( 'prev', false );
                Math.max( totalSpace, visibleSpace ) - visibleSpace > amount ? this._toggleControls( 'next', true ) : this._toggleControls( 'next', false );

            }
            
            this.translation = tvalue;

            if( translation === tvalue ) {
                
                this._onEndTransition();
                return false;

            }

            if( this.support ) {
                
                this.options.orientation === 'horizontal' ? this.$el.css( 'transform', 'translateX(' + tvalue + 'px)' ) : this.$el.css( 'transform', 'translateY(' + tvalue + 'px)' );

            }
            else {

                $.fn.applyStyle = this.hasTransition ? $.fn.animate : $.fn.css;
                var styleCSS = this.options.orientation === 'horizontal' ? { left : tvalue } : { top : tvalue };
                
                this.$el.stop().applyStyle( styleCSS, $.extend( true, [], { duration : this.options.speed, complete : function() {

                    self._onEndTransition();
                    
                } } ) );

            }
            
            if( !this.hasTransition ) {

                this._onEndTransition();

            }

        },
        _onEndTransition : function() {

            this.isSliding = false;
            this.options.onAfterSlide();

        },
        _slideTo : function( pos ) {

            var pos = pos || this.current,
                translation = Math.abs( this.translation ) || 0,
                itemSpace = this.options.orientation === 'horizontal' ? this.$items.outerWidth( true ) : this.$items.outerHeight( true ),
                posR = translation + this.$carousel.width(),
                ftv = Math.abs( pos * itemSpace );

            if( ftv + itemSpace > posR || ftv < translation ) {

                this._slideToItem( pos );
            
            }

        },
        _slideToItem : function( pos ) {

            // how much to slide?
            var amount    = this.options.orientation === 'horizontal' ? pos * this.$items.outerWidth( true ) : pos * this.$items.outerHeight( true );
            this._slide( '', -amount );
            
        },
        // public method: adds new items to the carousel
        /*
        
        how to use:
        var carouselEl = $( '#carousel' ),
            carousel = carouselEl.elastislide();
        ...
        
        // append or prepend new items:
        carouselEl.prepend('<li><a href="#"><img src="images/large/2.jpg" alt="image02" /></a></li>');

        // call the add method:
        es.add();
        
        */
        add : function( callback ) {
            
            var self = this,
                oldcurrent = this.current,
                $currentItem = this.$items.eq( this.current );
            
            // adds new items to the carousel
            this.$items = this.$el.children( 'li' );
            this.itemsCount = this.$items.length;
            this.current = $currentItem.index();
            this._setItemsSize();
            this._configure();
            this._removeTransition();
            oldcurrent < this.current ? this._slideToItem( this.current ) : this._slide( 'next', this.translation );
            setTimeout( function() { self._addTransition(); }, 25 );
            
            if ( callback ) {

                callback.call();

            }
            
        },
        // public method: sets a new element as the current. slides to that position
        setCurrent : function( idx, callback ) {
            
            this.current = idx;

            this._slideTo();
            
            if ( callback ) {

                callback.call();

            }
            
        },
        // public method: slides to the next set of items
        next : function() {

            self._slide( 'next' );

        },
        // public method: slides to the previous set of items
        previous : function() {

            self._slide( 'prev' );

        },
        // public method: slides to the first item
        slideStart : function() {

            this._slideTo( 0 );

        },
        // public method: slides to the last item
        slideEnd : function() {

            this._slideTo( this.itemsCount - 1 );

        },
        // public method: destroys the elastislide instance
        destroy : function( callback ) {

            this._destroy( callback );
        
        }

    };
    
    var logError = function( message ) {

        if ( window.console ) {

            window.console.error( message );
        
        }

    };
    
    $.fn.elastislide = function( options ) {

        var self = $.data( this, 'elastislide' );
        
        if ( typeof options === 'string' ) {
            
            var args = Array.prototype.slice.call( arguments, 1 );
            
            this.each(function() {
            
                if ( !self ) {

                    logError( "cannot call methods on elastislide prior to initialization; " +
                    "attempted to call method '" + options + "'" );
                    return;
                }
                if ( !$.isFunction( self[options] ) || options.charAt(0) === "_" ) {
                    logError( "no such method '" + options + "' for elastislide self" );
                    return;
                }
                self[ options ].apply( self, args );
            });
        } 
        else {
            this.each(function() {
                if ( self ) {
                    self._init();
                }
                else {
                    self = $.data( this, 'elastislide', new $.Elastislide( options, this ) );
                }
            });
        }
        return self;
    };
} )( jQuery, window );
