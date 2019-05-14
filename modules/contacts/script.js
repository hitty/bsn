jQuery(document).ready(function(){
        jQuery('.contacts-form .blue').on('click', function(){
            var _values = {};
            var _error = false;
            jQuery('input, textarea').each(function(){
                var _this = jQuery(this);
                if(_this.attr('id') == 'theme') {
                    var _value = _this.val();
                    var _name = _this.attr('name');
                    _this = _this.parents('.list-selector');
                } else {
                    var _value = _this.val();
                    var _name = _this.attr('name');
                }
                var _span = _this.next('span');
                 _this.removeClass('red-border');
                if(parseInt(_value ==0) || _value == '' || (_this.attr('name') == 'email' && !validateEmail(_value))){
                    _error = true; 
                    _span.fadeIn();
                     _this.addClass('red-border');
                } else {
                    _values[_name] = _value;
                    _span.fadeOut();
                }
            })
            if(_error == false) jQuery('.contacts-form').submit();
            else return false;
        })
        if(getParameterByName('ok') == 'true' ) {
            if(getBSNCookie('contacts_form') != null) jQuery('.contacts-form').addClass('send');
        } 
})
