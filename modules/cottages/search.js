jQuery(document).ready(function(){
    jQuery("#fast-search-form").submit(function(){
        var _form = jQuery(jQuery(".select-type li.active", jQuery(this)).attr('data-tab-ref'));
        var _search_type = _form.attr('data-search-type');
        setBSNCookie('Search_type', _search_type, 720, '/');
        var params = [];
        // собираем данные из формы
        var _obj_type = jQuery("#count_selector", _form).val();
        var _developers = jQuery("#developers", _form).val();
        var _cotage_title = jQuery("#cotage_title", _form).val();
        var _districts = jQuery("#districts", _form).val();
        var _directions = jQuery("#directions", _form).val();
        var _price_from = parseInt(jQuery("#from_value", _form).val());
        var _price_to = parseInt(jQuery("#to_value", _form).val());
        var _sqear_from = parseInt(jQuery("#from_sqear_value", _form).val());
        var _sqear_to = parseInt(jQuery("#to_sqear_value", _form).val());
        var _range_from = parseInt(jQuery("#from_range_value", _form).val());
        var _range_to = parseInt(jQuery("#to_range_value", _form).val());

        // накапливаем параметры
        if(_developers) params.push('developers='+_developers);
        if(_cotage_title) params.push('title='+_cotage_title);
        if(_districts) params.push('districts='+_districts);
        if(_directions) params.push('directions='+_directions);
        if(_obj_type > 0) params.push('object_type='+_obj_type);
        if(_price_from>_price_to) {
            _price_from = _price_from
        }
        if(_price_from) params.push('min_cost='+_price_from);
        if(_price_to) params.push('max_cost='+_price_to);
        
        if(_sqear_from>_sqear_to) {
            _sqear_from = _sqear_from
        }
        if(_sqear_from) params.push('min_sqear='+_sqear_from);
        if(_sqear_to) params.push('max_sqear='+_sqear_to);

        if(_range_from>_range_to) {
            _range_from = _range_from
        }
        if(_range_from) params.push('min_range='+_range_from);
        if(_range_to) params.push('max_range='+_range_to);

        // конструируем запрос
        window.location.href = '/cottedzhnye_poselki/?' + params.join('&');
        return false;
    });
    
    jQuery("#sort_selector").on('change', function(event, value){
        window.location.href = jQuery(this).children('.list-data').data('link') + jQuery(this).children('input').val();
    });

    _def_val = getBSNCookie('View_count_estate');
    if(_def_val) jQuery('#count_selector .list-data li[data-value="'+_def_val+'"]').click();
    jQuery("#count_selector").on('change', function(event, value){
        setBSNCookie('View_count_estate', value, 30, '/');
        window.location.href = window.location.href;
    })
        
    jQuery('.objects-list-body .row').on('click', function(e){
        var _link = jQuery(this).find("a.a_to_click");
        if (e.target === _link[0]) return false;
        _link.trigger('click');
        return false;
    });
    $("a.a_to_click").click(function() {   location.href = this.href; });
});
