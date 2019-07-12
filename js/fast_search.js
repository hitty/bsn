jQuery(document).ready(function(){
    jQuery("#fast-search-form").submit(function(){
        var _form = jQuery(jQuery(".select-type li.active", jQuery(this)).attr('data-tab-ref'));
        var _estate_type = _form.attr('data-estate-type');
        var _deal_type = jQuery(".select-deal .active input", _form).attr('data-deal-type');
        var params = [];
        // собираем данные из формы
        var _subways = jQuery(".subway-picker input[name='subways']", _form).val();
        var _districts = jQuery(".district-picker input[name='districts']", _form).val();
        var _district_areas = jQuery(".district-area-picker input[name='district_areas']", _form).val();
        var _countries = jQuery(".country-picker input[name='countries']", _form).val();
        var _obj_type = jQuery(".select-obj-type input[name='obj_type']", _form).val();
        var _currency = jQuery(".select-currency input[name='currency']", _form).val();
        var _price_from = parseInt(jQuery(".price-selector input[name='from_value']", _form).val().replace(/[^0-9]/g, ''));
        var _price_to = parseInt(jQuery(".price-selector input[name='to_value']", _form).val().replace(/[^0-9]/g, ''));                        
        var _rooms = jQuery(".rooms-count input:checked", _form);
        var _with_photo = jQuery("#with_photo:checked").val();
        var _elite = jQuery("#elite:checked").val();
        if(_estate_type=='live'){
            var _by_the_day = jQuery("#by_the_day_live:checked").val();
        }
        // накапливаем параметры
        if(_subways) params.push('subways='+_subways);
        if(_districts) params.push('districts='+_districts);
        if(_district_areas) params.push('district_areas='+_district_areas);
        if(_countries) params.push('countries='+_countries);
        if(_obj_type && _obj_type>0) params.push('obj_type='+_obj_type);
        if(_currency && _currency.length) params.push('currency='+_currency);
        if(_price_from>_price_to) {
            _price_from_to = _price_to;
            _price_to = _price_from;
            _price_from = _price_from_to;
        }
        if(_price_from) params.push('min_cost='+_price_from);
        if(_price_to) params.push('max_cost='+_price_to);
        if(_rooms.size()){
            var _r = [];
            for(var i=0;i<_rooms.size();i++) _r.push(_rooms[i].value);
            params.push('rooms=' + _r.join(','));
        }
        if(_with_photo) params.push('with_photo='+_with_photo);
        if(_estate_type=='live'){
            if(_by_the_day) params.push('by_the_day='+_by_the_day);
        }
        onClick="_gaq.push(['_trackEvent', 'Целевое действие', 'Искать',,, false]);"
        // конструируем запрос
        window.location.href = '/' + _estate_type + '/' + (_deal_type ? _deal_type : 'sell') + '/?' + params.join('&');
        return false;
    });
    jQuery("#fast-search-form .subway-picker .pick .counter .count").change(function(){
        jQuery("#fast-search-form .district-picker, #fast-search-form .district-area-picker").trigger('change', "");
    });
    jQuery("#fast-search-form .district-picker .pick .counter .count, #fast-search-form .district-area-picker .pick .counter .count").change(function(){
        jQuery("#fast-search-form .subway-picker").trigger('change', "");
    });

    jQuery('#fstab2 .select-deal input').on('click',function(){
        if(jQuery(this).attr('data-deal-type')=='rent') jQuery('#by_the_day_live').parent().show();
        else jQuery('#by_the_day_live').parent().hide();
    });
    jQuery('.select-deal input').on('change',function(){
        var _obj_type = jQuery(this).parents('.tab').find('.select-obj-type');
        var _ul = _obj_type.children('.list-data');
        jQuery('li',_ul).removeClass('active');
        jQuery('li[rel='+jQuery(this).attr('data-deal-type')+']',_ul).addClass('active');
        var _obj_type_value = jQuery('input[name=obj_type]',_obj_type).val();
        if(jQuery('li.active[data-value='+_obj_type_value+']',_ul).text() == '') _ul.children('li:first-child').click();
    });
});