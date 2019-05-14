jQuery(document).ready(function(){
    /* list-selector */
    _opened_listelector = null;
    jQuery("#ajax-search-results .list-selector").each(function(){
        var _selector = jQuery(this);
        jQuery(".select, .pick", _selector).click(function(){   
            _selector.toggleClass("dropped");
            if(_selector.hasClass("dropped")) _opened_listelector = _selector;
            else  _opened_listelector = null;
            return false;
        });
        jQuery(".list-data li:not(.disabled)", _selector).click(function(event, first_call){
            if(typeof first_call == 'undefined') first_call = false;
            var _li = jQuery(this);
            var _lhtml = _li.html();;
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
    if(jQuery('#ajax-search-results').length > 0){
        jQuery("#sort_selector, #count_selector").on('change', function(event, value){
            search_result();
            return false;
        });
    }
    jQuery(document).click(function(){
        if(_opened_listelector){jQuery(".select", _opened_listelector).click(); _opened_listelector=null;}
    })
})