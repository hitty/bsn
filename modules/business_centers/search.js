jQuery(document).ready(function(){
    jQuery("#fast-search-form").submit(function(){
        var _form = jQuery(this);
        var params = [];
        // собираем данные из формы
        var _subways = jQuery(".subway-picker input[name='subways']", _form).val();
        var _districts = jQuery(".district-picker input[name='districts']", _form).val();
        var _district_areas = jQuery(".district-area-picker input[name='district_areas']", _form).val();
        var _title = jQuery(".title-selector input[name='title']", _form).val();
        var _class = jQuery(".class-count input:checked", _form);
        
        // накапливаем параметры
        if(_subways) params.push('subways='+_subways);
        if(_districts) params.push('districts='+_districts);
        if(_district_areas) params.push('district_areas='+_district_areas);
        if(_class.size()){
            var _r = [];
            for(var i=0;i<_class.size();i++) _r.push(_class[i].value);
            params.push('class=' + _r.join(','));
        }
        if(_title) params.push('title='+_title);
        // конструируем запрос
        window.location.href = '/business_centers/?' + params.join('&');
        return false;
    });
    jQuery("#fast-search-form .subway-picker .pick .counter .count").change(function(){
        jQuery("#fast-search-form .district-picker, #fast-search-form .district-area-picker").trigger('change', "");
    });
    jQuery("#fast-search-form .district-picker .pick .counter .count, #fast-search-form .district-area-picker .pick .counter .count").change(function(){
        jQuery("#fast-search-form .subway-picker").trigger('change', "");
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