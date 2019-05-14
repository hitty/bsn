var _previous_value = null;         
var _gpval = '';  
jQuery(document).ready(function(){

    checkBoxesInit('');
    
    listSelectorInit('');
   
    
    /* tabs (without pending content uploading) */
    jQuery(jQuery(".tabs-container .active").attr("data-tab-ref"), jQuery(".tabs-container").attr("data-content-container")).show();
    jQuery(".tabs-container").click(function(e){
        var el = jQuery(e.target);
        if(el.is('a')) return true;
        if(!el.attr("data-tab-ref")) el = el.parent();
        if(!el.attr("data-tab-ref")) return false;
        var new_tab_class = el.attr("data-tab-ref");
        var tab_container = null;
        if(el.parent().is("[data-content-container]")) tab_container = jQuery(el.parent().attr("data-content-container"));
        var current_tab_class = jQuery(".active", el.parent()).attr("data-tab-ref");
        
        jQuery(current_tab_class, tab_container).hide();
        jQuery(new_tab_class, tab_container).show();
        jQuery("[data-tab-ref='"+current_tab_class+"']", el.parent()).removeClass("active");
        jQuery("[data-tab-ref='"+new_tab_class+"']", el.parent()).addClass("active");
        return false;
    });
    /* tabs (with pending content upload) */    
    jQuery(".filter span, .filter-secondary span").click(function(){
        var _secondary_click = jQuery(this).parent().hasClass('filter-secondary');
        if(_secondary_click){
            jQuery(this).addClass('active').siblings('span').removeClass('active');
            jQuery(this).parents('.filter').attr('data-secondary-param', jQuery(this).data('param') ).find('.active').first().click();
            return false;
        }
        var _secondary_in = _secondary_click || jQuery(this).siblings('.filter-secondary').length > 0;
        var el = jQuery(this);
        if(el.hasClass("active") && !_secondary_in) return false;
        
        var cont = el.parent();                
        el.addClass("active").siblings('span').removeClass('active');
        var _param = el.attr("data-param") != '' && el.attr("data-param") != undefined ? el.attr("data-param") + '/' : '';
        var _query = el.attr("data-query") != undefined && el.attr("data-query") != undefined ? el.attr("data-query") : '';
        var _url = cont.attr("data-url") + _param + ( cont.attr("data-secondary-param") != undefined && cont.attr("data-secondary-param") !='' ? cont.attr("data-secondary-param") + '/' : '' ) + _query;
        var _container = cont.attr("data-content-container");
        
        if( el.siblings('input').length > 0 ) el.siblings('input').val( _param )
        
        if(el.parents('.filter').hasClass('ajax-tabs-container')){
           getPendingContent(_container, _url); 
        } else {
            _gpval = el.data('tab-ref');
            setGPval();
            jQuery('.tab'+el.data('tab-ref'), jQuery(cont.data('content-container'))).addClass('active').siblings('.tab').removeClass('active');
            //новые табы с прокруткой
            if(cont.hasClass('scroll')) {
                var _new_height = 0;
                jQuery('.sticky-wrapper').each(function(){
                    if(jQuery(this).parents('.right-column.contacts').length == 0) _new_height += jQuery(this).height();
                    
                })
                jQuery("html,body").animate({ scrollTop: jQuery('.tab'+el.data('tab-ref')).offset().top - _new_height + 20}, "slow");
            }
            setTimeout(function(){
               jQuery('.lazy').lazy({
                    effect: 'fadeIn',
                    visibleOnly: true,
                    afterLoad: function(element) {
                        element.removeClass('lazy');
                    }
                })  
                
            },150)
        }
       
    })
    
    var loc = history.location || document.location;
    var _val = getGPval(loc);
    if(_val != '') {
        if( _val.indexOf('.') != 0 ) jQuery(".popup[data-location=" + _val + "]").click();
        else jQuery(".card .filter span[data-tab-ref = '" + _val + "']").click();
    }
    jQuery('.filter').each(function(){
        if( jQuery(this).find('.active').length == 0 )  jQuery('span:visible:first', jQuery(this)).click();
    })
    
    jQuery('.breadcrumbs_block i').on('click', function(e){
        jQuery('.breadcrumbs_block i').not(jQuery(this)).removeClass('on');
        jQuery(this).toggleClass('on');
    })
    
    if( jQuery("#datetime-filter").length > 0 ){
        var _this = jQuery("#datetime-filter");
        var _url = _this.data('url') != undefined ? _this.data('url') : false;
        var _container = _this.data('container') != undefined ? _this.data('container') : false;;
        if( _url !== false && _container !== false ){
            jQuery('#datetime-filter .item').each(function(e, index){
                jQuery(this).on('click', function(){
                    getPendingContent(_container, _url + ( jQuery(this).data('params') != "undefined" ? jQuery(this).data('params') : '' ), {ajax:true}, false, false); 
                    jQuery('#datetime-filter .item').removeClass('active');
                    jQuery(this).addClass('active')
                    
                }) 
            })
        }
        if( jQuery('.progress-gallery-wrap').length > 0) {
            getPendingContent('.progress-gallery-wrap', '/zhiloy_kompleks/block/gallery/'+jQuery("#datetime-filter").data('id')+'/', {id:jQuery(this).data('id')}, false, false);        
        }
        jQuery('.years-list').on('change', function(){
            var _year = parseInt(jQuery(this).children('input[name=progress_years]').val());
            jQuery('#datetime-filter .list .item').removeClass('on');
            jQuery('#datetime-filter .list .item[data-year='+_year+']').addClass('on');
            jQuery('#datetime-filter .item:visible:last').click()
        })
        
        if(jQuery('#datetime-filter').is(':visible') && jQuery('#datetime-filter .list .active').length == 0) {
            jQuery('#datetime-filter .list .item:visible:last').click()
        }
    }
       
     
    
    //ajax button pagination
    jQuery(document).on('click', '.ajax-pagination.button', function(){
        if(jQuery(this).data('link')) {
            _this = jQuery(this)
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: false,
                url: _this.data('link'),
                success: function(msg){
                    if(msg.ok){
                        var _txt = msg.html;
                        _this.after(_txt);
                        _this.remove();
                        jQuery('.lazy').lazy({
                            afterLoad: function(element) {
                                element.removeClass('lazy');
                            }
                        })  
                        
                        history.pushState(null, null, jQuery(this).data('link'));
                    }
                }
            })
        }
    })
    
    jQuery(document).on('keyup', 'input.digit', function(e){
        if( ! ( e.keyCode >= 48 && e.keyCode <=57 || e.keyCode >= 96 && e.keyCode <=105 || e.keyCode == 46 || e.keyCode == 8 )  ) return false;
        var _this = jQuery(this);
        var _val = _this.val().replace(/ /g,"");
        var _new_val = (_val.replace(/\D/g,"")).replace(/(\d)(?=(\d{3})+([^\d]|$))/g,"$1 ");
        _this.val( _new_val );    
    })

});
           
function getGPval( _location ){
    var _val = '';
    if( typeof _location == "undefined" ) _location = window.location.href;
    var matches = _location.toString().match(/#(.+)/);
    if(matches) {
        _val = matches[1];
    } 
    return _val;
}
function setGPval(){
    
    new_href = '';
    if(/#(.+)$/.test(window.location.href)){
        var matches = window.location.href.match(/#(.+)/);
        if(matches[1] != _gpval) new_href = window.location.href.replace(/#.+$/, '#'+_gpval);
    } else {
        new_href = window.location.href + ( window.location.href.indexOf('#') == -1 ? '#' : '' ) +_gpval;
    }
    if(new_href != window.location.href && new_href!='') {
        if( _gpval == '' ) new_href = new_href.replace('#','') ;
        var state = {
            title: document.title,
            url: new_href,
            gpid: _gpval
        }
        history.pushState( state, state.title, state.url );
        //window.location.href = new_href;
    }
}
function checkBoxesInit(parent_object){
    jQuery(".checkbox, .checkbox-group, .radio-group", parent_object).each(function(){
        var _elem = jQuery(this);
        var _type = _elem.hasClass('radio-group') ? 'radio' : 'checkbox';
        jQuery("input[type='"+_type+"']", _elem).change(function(){
            if(_type == 'checkbox'){
                if(jQuery(this).is(":checked")) jQuery(this).parent().addClass("on");
                else jQuery(this).parent().removeClass("on");
            } else jQuery(this).parent().addClass("on").siblings('label').removeClass('on')
        });
        if(_type == 'checkbox') jQuery("input[type='"+_type+"']", parent_object).each(function(){jQuery(this).change()});
    });
}
function listSelectorInit(parent_object){
        /* list-selector */
        _opened_listelector = null;
        jQuery(".list-selector", parent_object).each(function(){
            var _selector = jQuery(this);
            jQuery(".select, .pick", _selector).click(function(){   
                jQuery(".select, .pick").parent(".list-selector").not(_selector).removeClass("dropped");
                _selector.toggleClass("dropped");
                if(_selector.hasClass("dropped")) _opened_listelector = _selector;
                else  _opened_listelector = null;
                return false;
            });
            jQuery(".list-data li:not(.disabled)", _selector).click(function(event, first_call){
                
                if(typeof first_call == 'undefined') first_call = false;
                var _li = jQuery(this);
                var _lhtml = _li.html();
                _li.addClass("selected").siblings('li').removeClass("selected");
                if(_li.data('title')!='' && typeof _li.data('title')=='string') {_lhtml = _li.data('title');}
                if(_lhtml!=jQuery(".pick", _selector).html()){
                    jQuery(".pick", _selector).html(_lhtml).attr('title',_lhtml);
                    _previous_value =  jQuery('input[type="hidden"]',_selector).val();
                    var _val = _li.attr("data-value");
                    jQuery('input[type="hidden"]',_selector).val(_val);
                    if(_val.length <= 1 && (_val=='' || _val==0)) _selector.removeClass('active');
                    else  _selector.addClass('active');
                    if(!first_call) _selector.trigger('change',_lhtml);
                }
                _selector.removeClass("dropped");
                _opened_listelector = null;
            });
            var _def_val = jQuery('input[type="hidden"]',_selector).val();
            var _active_item = jQuery('.list-data li[data-value="'+_def_val+'"]', _selector);
            if(!_active_item.size()) _active_item = jQuery('.list-data li:first', _selector);
            _active_item.trigger("click", true);
        });
        jQuery(document).click(function(){
            if(_opened_listelector){jQuery(".select", _opened_listelector).click(); _opened_listelector=null;}
        })
    }