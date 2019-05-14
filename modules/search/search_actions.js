jQuery(function(){    //datepicker init
    var _form = jQuery("#search-line > form");
    jQuery( "#date_start, #date_end",_form ).datepicker({
        hideIfNoPrevNext: true,
        numberOfMonths: 2,
        dateFormat: 'dd.mm.y',
        maxDate: "+0d"
    });
    
    var _form = jQuery('form[name=search]');
    jQuery('input[type=submit], button[type=submit]', _form).on('click', function(e){
        var _form_values = [];
        var _parameters = '';
        _form.find('input').each(function(){
            var _this = jQuery(this); 
            var _val = _this.val();
            var _name = _this.attr('name');
            if(_this.attr('type')=='checkbox'){
                if(_this.parent('label').hasClass('on')) _form_values.push(_name+'='+_val);
            }
            else if(_val!='') _form_values.push(_name+'='+_val);
        })
        if(_form_values.length > 0) _parameters = '?'+_form_values.join("&");
        _url =  _form.attr('action')+_parameters;
        document.location = _url;
        return false;
    });
        
});
