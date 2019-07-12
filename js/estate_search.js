_ajax_search = false;
_click_type = 'inner';
_last_click = 0;
_estate_url = '';
function search_result(){
    _estate_url = jQuery('.list-data li[data-value='+jQuery("#estate-object-type").val()+']', jQuery('.select-object-type')).data('url');
    
    var _form = jQuery(jQuery(".middle-panel .tab.active").length > 0 ? jQuery(".middle-panel .tab.active") : jQuery('.fast-search.tiny .row'));
    console.log(_form.html())
    _estate_type = _form.data('estate-type');
    var _deal_type = jQuery('#estate-deal-type', _form).length > 0 ? jQuery('#estate-deal-type', _form).val() : _form.data('deal');
    _estate_url = _estate_url + _deal_type + '/';
    params = [];
    // собираем данные из формы
    _subways = jQuery("#subways").val();
    _districts = jQuery("#districts").val();
    _district_areas = jQuery("#district-areas").val();
    _cottage_districts = jQuery("#cottage_districts").val();
        
    var _objects_group = jQuery("#estate-object-type").val();
    
    if(jQuery('body.mainpage').length == 0){
        if(jQuery(".price-selector input[name='from_value']", _form).length > 0)var _price_from = parseInt(jQuery(".price-selector input[name='from_value']", _form).val().replace(/[^0-9]/g, ''));
        if(jQuery(".price-selector input[name='to_value']", _form).length > 0) var _price_to = parseInt(jQuery(".price-selector input[name='to_value']", _form).val().replace(/[^0-9]/g, ''));                        
    } else {
        if(jQuery(".price-selector input[name='from_value']").length > 0)var _price_from = parseInt(jQuery(".price-selector input[name='from_value']").val().replace(/[^0-9]/g, ''));
        if(jQuery(".price-selector input[name='to_value']").length > 0) var _price_to = parseInt(jQuery(".price-selector input[name='to_value']").val().replace(/[^0-9]/g, ''));                        
    }
    
    if(jQuery(".squares input[name='square_full_from']", _form).length > 0)var _square_full_from = parseInt(jQuery(".squares input[name='square_full_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='square_full_to']", _form).length > 0) var _square_full_to = parseInt(jQuery(".squares input[name='square_full_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_square_full_from>_square_full_to) {
        _square_full_from_to = _square_full_to;
        _square_full_to = _square_full_from;
        _square_full_from = _square_full_from_to;
    }
    if(_square_full_from) params.push('square_full_from='+_square_full_from);
    if(_square_full_to) params.push('square_full_to='+_square_full_to);
    
    var _housing_estate_page = jQuery("input[name='housing_estate_page']").val();
    if(_housing_estate_page) params.push('housing_estate_page='+_housing_estate_page);
    
    var _only_objects = jQuery("input[name='only_objects']", _form).val();
    if(_only_objects) params.push('only_objects='+_only_objects);
    
    if(jQuery(".squares input[name='square_live_from']", _form).length > 0)var _square_live_from = parseInt(jQuery(".squares input[name='square_live_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='square_live_to']", _form).length > 0) var _square_live_to = parseInt(jQuery(".squares input[name='square_live_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_square_live_from>_square_live_to) {
        _square_live_from_to = _square_live_to;
        _square_live_to = _square_live_from;
        _square_live_from = _square_live_from_to;
    }
    if(_square_live_from) params.push('square_live_from='+_square_live_from);
    if(_square_live_to) params.push('square_live_to='+_square_live_to);

    if(jQuery(".squares input[name='square_kitchen_from']", _form).length > 0)var _square_kitchen_from = parseInt(jQuery(".squares input[name='square_kitchen_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='square_kitchen_to']", _form).length > 0) var _square_kitchen_to = parseInt(jQuery(".squares input[name='square_kitchen_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_square_kitchen_from>_square_kitchen_to) {
        _square_kitchen_from_to = _square_kitchen_to;
        _square_kitchen_to = _square_kitchen_from;
        _square_kitchen_from = _square_kitchen_from_to;
    }
    if(_square_kitchen_from) params.push('square_kitchen_from='+_square_kitchen_from);
    if(_square_kitchen_to) params.push('square_kitchen_to='+_square_kitchen_to);

    if(jQuery(".squares input[name='square_ground_from']", _form).length > 0)var _square_ground_from = parseInt(jQuery(".squares input[name='square_ground_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='square_ground_to']", _form).length > 0) var _square_ground_to = parseInt(jQuery(".squares input[name='square_ground_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_square_ground_from>_square_ground_to) {
        _square_ground_from_to = _square_ground_to;
        _square_ground_to = _square_ground_from;
        _square_ground_from = _square_ground_from_to;
    }
    if(_square_ground_from) params.push('square_ground_from='+_square_ground_from);
    if(_square_ground_to) params.push('square_ground_to='+_square_ground_to);

    if(jQuery(".squares input[name='square_usefull_from']", _form).length > 0)var _square_usefull_from = parseInt(jQuery(".squares input[name='square_usefull_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='square_usefull_to']", _form).length > 0) var _square_usefull_to = parseInt(jQuery(".squares input[name='square_usefull_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_square_usefull_from>_square_usefull_to) {
        _square_usefull_from_to = _square_usefull_to;
        _square_usefull_to = _square_usefull_from;
        _square_usefull_from = _square_usefull_from_to;
    }
    if(_square_usefull_from) params.push('square_usefull_from='+_square_usefull_from);
    if(_square_usefull_to) params.push('square_usefull_to='+_square_usefull_to);

    if(jQuery("input[name='level']", _form).length > 0) var _level = parseInt(jQuery("input[name='level']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='housing_estate']", _form).length > 0) _housing_estate = parseInt(jQuery("input[name='housing_estate']", _form).val().replace(/[^0-9]/g, ''));                        
    else _housing_estate=0;
    if(jQuery("input[name='business_center']", _form).length > 0) var _business_center = parseInt(jQuery("input[name='business_center']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='cottage']", _form).length > 0) var _cottage = parseInt(jQuery("input[name='cottage']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery('body.mainpage').length == 0) {
        if(jQuery("input[name='geodata']", _form).length > 0) _geodata = parseInt(jQuery("input[name='geodata']", _form).val().replace(/[^0-9]/g, ''));                        
    } else {
        if(jQuery("input[name='geodata']").length > 0) _geodata = parseInt(jQuery("input[name='geodata']").val().replace(/[^0-9]/g, ''));                        
    }
    
    if(jQuery("input[name='build_complete']", _form).length > 0) var _build_complete = parseInt(jQuery("input[name='build_complete']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='geodata_selected']", _form).length > 0) var _geodata_selected = jQuery("input[name='geodata_selected']", _form).val();                        
    if(jQuery("input[name='way_time']", _form).length > 0) var _way_time = parseInt(jQuery("input[name='way_time']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='way_type']", _form).length > 0) var _way_type = parseInt(jQuery("input[name='way_type']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='building_type']", _form).length > 0) var _building_type = parseInt(jQuery("input[name='building_type']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='elevator']", _form).length > 0) var _elevator = parseInt(jQuery("input[name='elevator']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='facing']", _form).length > 0) var _facing = parseInt(jQuery("input[name='facing']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='toilet']", _form).length > 0) var _toilet = parseInt(jQuery("input[name='toilet']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='balcon']", _form).length > 0) var _balcon = parseInt(jQuery("input[name='balcon']", _form).val().replace(/[^0-9]/g, ''));                        
    if(jQuery("input[name='heating']", _form).length > 0) var _heating = parseInt(jQuery("input[name='heating']", _form).val().replace(/[^0-9]/g, ''));                        

    if(jQuery(".squares input[name='ceiling_height_from']", _form).length > 0)var _ceiling_height_from = parseInt(jQuery(".squares input[name='ceiling_height_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='ceiling_height_to']", _form).length > 0) var _ceiling_height_to = parseInt(jQuery(".squares input[name='ceiling_height_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_ceiling_height_from>_ceiling_height_to) {
        _ceiling_height_from_to = _ceiling_height_to;
        _ceiling_height_to = _ceiling_height_from;
        _ceiling_height_from = _ceiling_height_from_to;
    }
    if(_ceiling_height_from) params.push('ceiling_height_from='+_ceiling_height_from);
    if(_ceiling_height_to) params.push('ceiling_height_to='+_ceiling_height_to);
            
    if(jQuery("input[name='enter']", _form).length > 0) var _enter = parseInt(jQuery("input[name='enter']", _form).val().replace(/[^0-9]/g, ''));  
    if(_enter) params.push('enter='+_enter);
    if(jQuery("input[name='water_supply']", _form).length > 0) var _water_supply = parseInt(jQuery("input[name='water_supply']", _form).val().replace(/[^0-9]/g, ''));  
    if(_water_supply) params.push('water_supply='+_water_supply);        

    if(jQuery(".squares input[name='phones_count_from']", _form).length > 0)var _phones_count_from = parseInt(jQuery(".squares input[name='phones_count_from']", _form).val().replace(/[^0-9]/g, ''));
    if(jQuery(".squares input[name='phones_count_to']", _form).length > 0) var _phones_count_to = parseInt(jQuery(".squares input[name='phones_count_to']", _form).val().replace(/[^0-9]/g, ''));                        
    if(_phones_count_from>_phones_count_to) {
        _phones_count_from_to = _phones_count_to;
        _phones_count_to = _phones_count_from;
        _phones_count_from = _phones_count_from_to;
    }
    if(_phones_count_from) params.push('phones_count_from='+_phones_count_from);
    if(_phones_count_to) params.push('phones_count_to='+_phones_count_to);
    
    if(jQuery("input[name='parking']", _form).length > 0) var _parking = parseInt(jQuery("input[name='parking']", _form).val().replace(/[^0-9]/g, ''));  
    if(_parking) params.push('parking='+_parking);                                
    if(jQuery("input[name='security']", _form).length > 0) var _security = parseInt(jQuery("input[name='security']", _form).val().replace(/[^0-9]/g, ''));  
    if(_security) params.push('security='+_security);
    if(jQuery("input[name='electricity']", _form).length > 0) var _electricity = parseInt(jQuery("input[name='electricity']", _form).val().replace(/[^0-9]/g, ''));  
    if(_electricity) params.push('electricity='+_electricity);
    if(jQuery("input[name='id_heating']", _form).length > 0) var _id_heating = parseInt(jQuery("input[name='id_heating']", _form).val().replace(/[^0-9]/g, ''));  
    if(_id_heating) params.push('id_heating='+_id_heating);                    
    if(jQuery("input[name='id_electricity']", _form).length > 0) var _id_electricity = parseInt(jQuery("input[name='id_electricity']", _form).val().replace(/[^0-9]/g, ''));  
    if(_id_electricity) params.push('id_electricity='+_id_electricity);
    if(jQuery("input[name='bathrooms']", _form).length > 0) var _bathrooms = parseInt(jQuery("input[name='bathrooms']", _form).val().replace(/[^0-9]/g, ''));  
    if(_bathrooms) params.push('bathrooms='+_bathrooms);    
    if(jQuery("input[name='ownerships']", _form).length > 0) var _ownerships = parseInt(jQuery("input[name='ownerships']", _form).val().replace(/[^0-9]/g, ''));  
    if(_ownerships) params.push('ownerships='+_ownerships); 
    if(jQuery("input[name='gas']", _form).length > 0) var _gas = parseInt(jQuery("input[name='gas']", _form).val().replace(/[^0-9]/g, ''));  
    if(_gas) params.push('gas='+_gas);                       
    if(jQuery("input[name='developer']", _form).length > 0) var _developer = parseInt(jQuery("input[name='developer']", _form).val().replace(/[^0-9]/g, ''));  
    if(_developer) params.push('developer='+_developer);                       
    if(jQuery("input[name='class']", _form).length > 0) var _class = parseInt(jQuery("input[name='class']", _form).val().replace(/[^0-9]/g, ''));  
    if(_class) params.push('class='+_class);                       
    if(jQuery("input[name='object_type']", _form).length > 0) var _object_type = parseInt(jQuery("input[name='object_type']", _form).val().replace(/[^0-9]/g, ''));  
    if(_object_type) params.push('object_type='+_object_type); 
    if(jQuery("input[name='direction']", _form).length > 0) var _directions = parseInt(jQuery("input[name='direction']", _form).val().replace(/[^0-9]/g, ''));  
    if(_directions) params.push('direction='+_directions); 
    
    var _sqear_from = parseInt(jQuery("#from_sqear_value", _form).val());
    var _sqear_to = parseInt(jQuery("#to_sqear_value", _form).val());
    var _range_from = parseInt(jQuery("#from_range_value", _form).val());
    var _range_to = parseInt(jQuery("#to_range_value", _form).val());
    
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
                                    
    if(jQuery("input[name='low_rise']", _form).length > 0) var _low_rise = jQuery("input[name='low_rise']:checked").val();  
    if(_low_rise) params.push('low_rise='+_low_rise);                       

    if(jQuery("input[name='214_fz']", _form).length > 0) var _214_fz = jQuery("input[name='214_fz']:checked").val();  
    if(_214_fz) params.push('214_fz='+_214_fz);   
        
    if(jQuery("input[name='apartments']", _form).length > 0) var _apartments = jQuery("input[name='apartments']:checked").val();  
    if(_apartments) params.push('apartments='+_apartments);   
        
    if(jQuery("input[name='rating']", _form).length > 0) var _rating = jQuery("input[name='rating']:checked").val();  
    if(_rating) params.push('rating='+_rating);   
        
    if(jQuery(".class-count input:checked").length > 0) {
        _bc_class = jQuery(".class-count input:checked", _form);
        var _r = [];
        for(var i=0;i<_bc_class.size();i++) _r.push(_bc_class[i].value);
        params.push('class=' + _r.join(','));
    }
    if(jQuery(".promotions input[name=estate-type]").length > 0) {
        var _estate_url = jQuery(".promotions input[name=estate-type]:checked").val();  
    }

    if(jQuery("input[name=agency]").length > 0) var _agency = parseInt(jQuery("input[name='agency']").val().replace(/[^0-9]/g, ''));  
    if(_agency) params.push('agency='+_agency);
    
    if(jQuery("input[name='company_page']").length > 0) var _company_page = jQuery("input[name='company_page']").val();  
    if(_company_page) params.push('company_page='+_company_page);
    
    if(jQuery("input[name=agent]").length > 0) var _agent = parseInt(jQuery("input[name='agent']").val().replace(/[^0-9]/g, ''));
    if(_agent) params.push('agent='+_agent);
    
    if(jQuery("input[name='agent_page']").length > 0) var _agent_page = jQuery("input[name='agent_page']").val();
    if(_agent_page) params.push('agent_page='+_agent_page);
    
    var _rooms = jQuery(".rooms-count input:checked", _form);
    var _rooms_sale = jQuery(".rooms-sale-count input:checked", _form);
    var _user_objects = jQuery("input[name='user_objects']:checked").val();
    var _not_first_level = jQuery("input[name='not_first_level']:checked", _form).val();
    var _not_last_level = jQuery("input[name='not_last_level']:checked", _form).val();

    // накапливаем параметры
    if(_subways) params.push('subways='+_subways);
    if(parseInt(_districts)>0) params.push('districts='+_districts);
    if(_district_areas) params.push('district_areas='+_district_areas);
    if(_cottage_districts) params.push('cottage_districts='+_cottage_districts);

    if(_level) params.push('level='+_level);
    if(_housing_estate) params.push('housing_estate='+_housing_estate);
    if(_business_center) params.push('business_center='+_business_center);
    if(_cottage) params.push('cottage='+_cottage);
    if(typeof _geodata !="undefined" && _geodata) params.push('geodata='+_geodata);
    if(_build_complete) params.push('build_complete='+_build_complete);
    if(_geodata_selected) params.push('geodata_selected='+_geodata_selected);
    if(_way_time) params.push('way_time='+_way_time);
    if(_way_type) params.push('way_type='+_way_type);
    if(_building_type) params.push('building_type='+_building_type);
    if(_elevator) params.push('elevator='+_elevator);
    if(_facing) params.push('facing='+_facing);
    if(_toilet) params.push('toilet='+_toilet);
    if(_balcon) params.push('balcon='+_balcon);
    if(_heating) params.push('heating='+_heating);

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
    if(_rooms_sale.size()){
        var _r = [];
        for(var i=0;i<_rooms_sale.size();i++) _r.push(_rooms_sale[i].value);
        params.push('rooms_sale=' + _r.join(','));
    }    
    if(_user_objects) params.push('user_objects='+_user_objects);
    if(_not_first_level) params.push('not_first_level='+_not_first_level);
    if(_not_last_level) params.push('not_last_level='+_not_last_level);

    //таргет над выдачей
    if(jQuery('.dashed-link-blue.active').length > 0 && jQuery('.dashed-link-blue.active').attr('data-with-target') == "true") params.push('target=true');
    
    if(jQuery("input[name='count_selector']").length > 0) var _count_selector = parseInt(jQuery("input[name='count_selector']").val().replace(/[^0-9]/g, ''));  
    if(_count_selector) {
        setBSNCookie('View_count', _count_selector, 20, '/');
    }
    
     if(jQuery("input[name='search_form']", jQuery('#fast-search-form')).length > 0) params.push('search_form=1');
    // собираем данные из формы зарубежки
    if(jQuery('#inter_deals').length > 0){
        if(jQuery("input#inter_deals").length > 0) var _inter_deals = jQuery("input#inter_deals").val().replace(/[^0-9\,]/g, '');  
        _estate_url = 'inter/' + ( _inter_deals == 2 ? 'sell' : 'rent' ) + '/';
        
        if(jQuery("input#inter_type_objects").length > 0) var _inter_type_objects = jQuery("input#inter_type_objects").val().replace(/[^0-9\,]/g, '');  
        if(_inter_type_objects && _inter_type_objects!=0) params.push('inter_type_objects='+_inter_type_objects); 
        
        if(jQuery("input#inter_regions").length > 0) var _inter_regions = jQuery("input#inter_regions").val().replace(/[^0-9\,]/g, '');  
        if(_inter_regions && _inter_regions!=0) params.push('inter_regions='+_inter_regions); 
        
        if(jQuery("input#inter_type_groups", _form).length > 0) var _inter_type_groups = jQuery("input#inter_type_groups", _form).val().replace(/[^0-9\,]/g, '');  
        if(_inter_type_groups && _inter_type_groups!=0) params.push('inter_type_groups='+_inter_type_groups); 

        if(jQuery("input#inter_cost", _form).length > 0) var _inter_cost = parseInt(jQuery("input#inter_cost", _form).val().replace(/[^0-9\,]/g, ''));  
        if(_inter_cost && _inter_cost > 0) params.push('inter_cost='+_inter_cost); 


        if(jQuery("input#country", _form).length > 0) var _country = jQuery("input#country", _form).val().replace(/[^0-9\,]/g, '');  
        if(_country && _country > 0) params.push('country='+_country); 
    }
        
    if(jQuery('.ajax-search-results.objects')!==undefined && jQuery('.ajax-search-results.objects').hasClass('promotion')){
        //собираем параметры акции:
        params = [];
        //фильтр над выдачей:
        var _filter_above_result = jQuery('.promotion-objects').children('.custom-filter-line').children('.active').attr('data-value');
        params.push('above_result='+_filter_above_result);
        _estate_url = '/promotions/'+window.location.href.split('/')[4]+'/?' + (params.length > 0 ? params.join('&') : '');
        
    }else{
        var _catalog = jQuery('#fast-search-form').attr('action');
        _estate_url = _catalog + _estate_url + (params.length > 0 ? ( _estate_url.indexOf('?')>0 ? '&' : '?' ) + params.join('&') : '');
    }
    
    // конструируем запрос
    if(typeof _map_search=='boolean' && _map_search == true) { // поиск по карте
        getBounds();
        map_params = []
        map_params.push('top_left_lat='+_top_left_lat);
        map_params.push('top_left_lng='+_top_left_lng);
        map_params.push('bottom_right_lat='+_bottom_right_lat);
        map_params.push('bottom_right_lng='+_bottom_right_lng);
        mapPendingContent(_estate_url + ( _estate_url.indexOf('?')>0 ? '&' : (params.length > 0 ? '&' : '?' )) + map_params.join('&'));
        return false;
    } else{
        if(_ajax_search){
            getPendingContent('#ajax-search-results', _estate_url);
            
        } else window.location.href = _estate_url;
    } 
    return false;
}

jQuery(document).ready(function(){
    jQuery("#fast-search-form").submit(function(e){
        search_result();
        return false;
    });
    //управление типом сделки
     jQuery('.select-deal-type').on('change', function(){
        var _rent = jQuery(this).val() == 1 ? 'rent' : 'sell';
        jQuery('.fast-search .select-object-type .list-data li').removeClass('hidden').siblings('li[data-deal=' + ( jQuery(this).val() == 1 ? 'sell' : 'rent' ) + ']').addClass('hidden');
        jQuery('.fast-search .select-object-type .list-data li:first').click();
     })       
     //вывод результатов ajax-ом
     if(jQuery('.fast-search.ajax-form').length > 0){
         jQuery('input,.list-selector', jQuery('.fast-search.ajax-form')).on('change', search_result);
     }
    //обработчик для паджинатора объектов в выдаче компании
    if(jQuery('.ajax-search-results').length > 0){
        jQuery('.ajax-search-results').each(function(){
            //отключаем событие, чтобы не подключалось второй раз
            jQuery(document).off('click','.paginator span');
            jQuery(document).on('click','.paginator span',function(e){
                e.preventDefault();
                var _url = jQuery(this).attr('data-link');
                var _selector = jQuery(this).parents('.ajax-search-results').attr('class').replace(/(\s)/g,'.');
                
                //если слева висит быстрая форма, обновляем URL
                if(jQuery('.left-search-form').length > 0) {
                    //обновляем URL
                    window.history.pushState("object or string", "Title", _url);
                }
                
                getPendingContent('.' + _selector,_url);
                jQuery(document).scrollTop(jQuery('#fast-search-form').offset().top-85);
            });
        });
        _ajax_search = true;
    }                                                                                                                                 
    
    jQuery("#fast-search-form .subway-picker .pick .counter .count").change(function(){
        jQuery("#fast-search-form .district-picker, #fast-search-form .district-area-picker").trigger('change', "");
    });
    jQuery("#fast-search-form .district-picker .pick .counter .count, #fast-search-form .district-area-picker .pick .counter .count").change(function(){
        jQuery("#fast-search-form .subway-picker").trigger('change', "");
    });
    
    //управление типами объектов
    jQuery(".list-selector.select-object-type").on('change', function(event, value){
        var _el = jQuery('.list-data li[data-value='+jQuery("#estate-object-type").val()+']', jQuery('.select-object-type'));
        var _deal = _el.data('deal');
        var _type = _el.data('type');
        jQuery('.middle-panel .tab').removeClass('active');
        jQuery('.tab[data-estate-type='+_type+']').addClass('active').attr('data-deal', _deal);
        jQuery('.pick', jQuery('.select-object-type')).text(_el.data('title'));
        jQuery('.first-row li, .second-row li').removeClass('selected');
        _el.addClass('selected');
        var _rooms_wrap = jQuery('.fast-search .extend-search-wrap .rooms-sale-count');
        if(jQuery('#estate-object-type').val() == '2-live-sell')  _rooms_wrap.show().prev('span').show();
        else {
            _rooms_wrap.hide().prev('span').hide();
            _rooms_wrap.find('label').removeClass('on');
            _rooms_wrap.find('input').attr('checked', false);
        }
    });
    //автозаполнения
    jQuery('.autocomplete', jQuery('#fast-search-form')).each(function(){
        var _input = jQuery(this);
        _input.typeWatch({
            callback: function(){
                jQuery(this).next('input').val(0);
                var _searchstring = this.text;
                _input.addClass('wait');
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: _input.data('url'),
                    data: {ajax: true, search_string: _searchstring},
                    success: function(msg){
                        if(typeof(msg)=='object' && msg.ok) {
                            if(msg.list.length>0) showPopupList(_input, msg.list);
                            else hidePopupList();
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log('Запрос не выполнен!');
                    },
                    complete: function(){
                        _input.removeClass('wait');
                    }
                });
            },
            wait: 150,
            highlight: true,
            captureLength: 1
        }).blur(function(){
            setTimeout(function(){hidePopupList(_input)}, 350);
        });        
    })

    function showPopupList(_el,_list, _type){
        var _wrapper = _el.parent();
        var str = '<ul class="typewatch_popup_list">';
        for(var i in _list){                   
            str += '<li data-id="'+_list[i].id+'" title="'+_list[i].title+(typeof _list[i].additional_title=='string'?_list[i].additional_title:'')+'">'+_list[i].title+(typeof _list[i].additional_title=='string'?'<span>'+_list[i].additional_title+'</span>':'')+'</li>';
        }
        str += '</ul>';
        hidePopupList(_wrapper);
        _wrapper.append(jQuery(str));
        jQuery(".typewatch_popup_list li", _wrapper).bind('click', function(){
            var _parent_box = jQuery(this).parent().parent();
            var _el_class = _el.attr('name');
            jQuery('input[name='+_el_class+']').next('.clear-input').removeClass('hidden').next('input').val( jQuery(this).data('id') );
            jQuery('input[name='+_el_class+']').val(jQuery(this).text()).attr('title',jQuery(this).text());
            hidePopupList(_parent_box);
        });
        
    }
    jQuery('.clear-input').on('click', function(){
       var _class = jQuery(this).prev('input').attr('name');
        jQuery('input[name='+_class+']').attr('value','').val('').siblings('input').val('').siblings('.clear-input').addClass('hidden');
        jQuery('.autocomplete.address').change();
    });    
    //управление адресом
    jQuery('.autocomplete.address').on('change', function(){
        if(jQuery(this).val()=='') jQuery('.list-picker.location').removeClass('disabled');
        else jQuery('.list-picker.location').addClass('disabled');
    })
    function hidePopupList(_wrapper){
        if(!_wrapper) _wrapper = jQuery(document);
        jQuery(".typewatch_popup_list li", _wrapper).unbind('click');
        jQuery(".typewatch_popup_list", _wrapper).remove();
    }  
    //расширенный поиск
    jQuery('.extend-search').on('change', function(e){
        jQuery('.extend-search-wrap').slideToggle(300);
    })
    jQuery('.list-selector.select-object-type').change();

    
    
    _background_template = '<div id="background-shadow-expanded">'
                        +'<div id="background-shadow-expanded-wrapper"></div>'
                        +'</div>'
                    +'</div>';
    
    /* LOCATION */
    var _active_type = '';
    _geodata_ids = {'districts':[],'district-areas':[],'subways':[]};
    var _active_tab = '';
    var _offers_wrap = [];
    jQuery(document).on("click", ".list-picker.location", function(){
        var _this = jQuery(this);
        if(_this.hasClass('disabled')) return false;
        var _list = jQuery('#geodata-picker-wrap');
        jQuery('body').append(_background_template);
        jQuery('#background-shadow-expanded').fadeIn(100);
        setTimeout(function(){
            jQuery('#geodata-picker-wrap').fadeIn(100).css({display:'table'});;
        }, 200)
        
        if(_active_type=='') _list.children('.filter').children('span').first().click();
        else jQuery('#geodata-picker-wrap').children('.filter').children().first().click();
        return false;
    });
    
    //заполнение массива элементами
    jQuery('.location-list > .selected-items', jQuery('#geodata-picker-wrap')).each(function(e){
        var _this = jQuery(this);
        var _type = _this.data('type');
        _this.children('.item.on').each(function(e){
            _geodata_ids[_type].push(jQuery(this).data('id'));
        });
        var _filter = jQuery('#geodata-picker-wrap .filter');
        jQuery('span', _filter).each(function(){
            var _el = jQuery('#geodata-picker-wrap .filter span[data-type='+_type+'] i');
            _el.text(_geodata_ids[_type].length);
        })
        
    });
    
    jQuery(".filter span").on('change', function(e, value){
        var _this = jQuery(this);
        if(typeof value != 'undefined') jQuery('input[type="hidden"]',_this).val(value);
        e.preventDefault();
    });
    jQuery(".filter span").on('click', function(e){
        e.preventDefault();
        var _el = jQuery(e.target); //ditrict-picker
        var _selector = _el.parent(); //items-list
        var _items = jQuery('.items-list .items');
        _active_type = _el.data('type');
        _el.addClass('on').siblings('.filter span').removeClass('on');
        jQuery('.location-list .selected-items.'+_active_type+'-list').addClass('on').siblings('.selected-items').removeClass('on');
        var _url = jQuery('input[type="hidden"]',_el).attr('data-url');
        var _values = Array();
        if(jQuery('input[type="hidden"]',_el).length>0)
            if(jQuery('input[type="hidden"]',_el).val().length>0) _values = jQuery('input[type="hidden"]',_el).val().split(',');
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: true,
                url: _url, data: {ajax: true, selected: _values},
                success: function(msg){ 
                    if( typeof(msg)=='object') {
                        _items.html(msg.html);
                        districtMark();
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
                }
            });
            return false;
        });
        jQuery(document).on('click', '#geodata-picker-wrap .geodata-button button', function(event){
            if(!jQuery('#geodata-picker-wrap .geodata-button button').is(':visible')) return false;
            event.preventDefault();
            jQuery('#geodata-picker-wrap').fadeOut(100);
            setTimeout(function(){
                jQuery('#background-shadow-expanded').fadeOut(100);
            }, 200)
            return false;
        });
        jQuery('body').delegate("#background-shadow-expanded, #geodata-picker-wrap .close-btn",'click',function(event){ 
            event.preventDefault();
            jQuery("button", jQuery('#geodata-picker-wrap')).click();
        });
    jQuery(document).keyup(function(e) {
            switch(e.keyCode){
                case 27: jQuery("button", jQuery('#geodata-picker-wrap')).click();  break;     // esc
            }   
    });    

    //Сброс гео фильтра
    //заполнение массива элементами
    jQuery('#geodata-picker-wrap').delegate('#reset-geo','click',function(event){
        jQuery('.location-list > .selected-items').each(function(e){
            var _this = jQuery(this);
            var _type = _this.data('type');
            _this.children('.item').each(function(e){
                geoChoose('del', '', jQuery(this).data('id'), _type, event);
            });
        });    
    })
    //РАЙОНЫ ГОРОДА
    jQuery('body').delegate('#districts-svg > polygon, #district-areas-svg  > polygon', 'click',function(event){
         var _this = jQuery(this);
         var _class = _this.attr('class');
         if(_class=='polygon') {
             geoChoose('add', _this.attr('title'), _this.data('id'), _active_type, event);
         } else {
             geoChoose('del', _this.attr('title'), _this.data('id'), _active_type, event);
         }
    })
    
    jQuery('body')
        .delegate('#geodata-picker-wrap .location-list .selected-items .item:not(.on)','mouseover', function(event) {
            var _this = jQuery(this);
            var _id = _this.data('id');
            var _type = _this.parents('.selected-items').data('type');
            if(_type!='subways'){
                var _polygon = jQuery('#'+_type+'-svg > polygon[data-id = '+_id+']');
                _polygon.attr('class','hover polygon');
            } else{
                subwayHover(_id,'mouseover');
            }
        })
        .delegate('#geodata-picker-wrap .location-list .selected-items .item:not(.on)','mouseout', function(event) {
            var _this = jQuery(this);
            var _id = _this.data('id');
            var _type = _this.parents('.selected-items').data('type');
            if(_type!='subways'){
                var _polygon = jQuery('#'+_type+'-svg > polygon[data-id = '+_id+']');
                _polygon.attr('class','polygon');
            } else{
                subwayHover(_id,'mouseout');
            }
        })

    
    jQuery('body')
        .delegate('#districts-svg > polygon, #district-areas-svg  > polygon','mouseover', function(event) {
            jQuery('body').append('<span id="geodata-tooltip" style="position:absolute;"></span>');
            jQuery('#geodata-tooltip').html(jQuery(this).attr('title'));
            var _width = jQuery('#geodata-tooltip').width();
            var _this = jQuery(this);
            _this.mousemove(function( event ) {
                jQuery('#geodata-tooltip').css({left:event.pageX-(_width/2), top: event.pageY+20});
            }); 
            jQuery('#geodata-picker-wrap .location-list div[data-type='+jQuery('#geodata-picker-wrap .filter .active').data('type')+'] .item[data-id='+_this.data('id')+']').addClass('hover');
        })
        .delegate('#districts-svg > polygon, #district-areas-svg  > polygon','mouseout', function(event) {
            var _this = jQuery(this);
            jQuery('#geodata-tooltip').remove();
            jQuery('#geodata-picker-wrap .location-list div[data-type='+jQuery('#geodata-picker-wrap .filter .active').data('type')+'] .item[data-id='+_this.data('id')+']').removeClass('hover');
        }); 
        jQuery('.location-list .selected-items').delegate('.item','click',function(event){
            if(jQuery(this).hasClass('on')) var _action = 'del';
            else _action = 'add';
            geoChoose(_action, jQuery(this).text(), jQuery(this).data('id'), jQuery(this).parents('div').data('type'), event);
        })
        function geoChoose(_action, _title, _id, _active_type_click, _event){
            console.log(_action + ',' + _title + ',' + _id + ',' + _active_type_click);
            var _selected_titles_wrap = jQuery('.form-wrap .selected-items[data-type='+_active_type_click+']');
            if(_active_type_click == 'subways'){
                var _span = jQuery('.subways-title-item[data-subway-title-id='+_id+']',jQuery('#subways-title-wrap'));
                var _circle = jQuery('#subways-svg > circle[data-id='+_id+']');
                var _class = _circle.attr('class');
            }
            if(_action == 'add'){
                if(jQuery.inArray(_id, _geodata_ids[_active_type_click]) == -1){
                    _geodata_ids[_active_type_click].push(_id);
                    jQuery('.empty-list',_selected_titles_wrap).hide();
                    _selected_titles_wrap.append("<div class='item' data-id='"+ _id+"'>"+_title+"</div>");
                     if(_active_type_click == 'subways'){
                        _span.addClass('active');
                        _circle.attr('class',_class+' active');
                     }
                     else {
                         jQuery('#'+_active_type_click+'-svg').children('polygon[data-id='+_id+']').attr('class','polygon active');
                     }
                }
                jQuery('.location-list h5.'+_active_type_click+' ').addClass('active');
                jQuery('.address-select').addClass('disabled').children('input').attr('disabled', 'disabled');
                jQuery('.selected-items[data-type='+_active_type_click+'] .item[data-id='+_id+']').addClass('on').removeClass('hover');

            } else {
                _geodata_ids[_active_type_click].splice(_geodata_ids[_active_type_click].indexOf(_id), 1);
                 if(_active_type_click == 'subways' && _active_type_click == _active_type){
                     _span.removeClass('active');
                    _circle.attr('class',_class.replace(' active',''));
                 }
                 else {
                     jQuery('#'+_active_type_click+'-svg').children('polygon[data-id='+_id+']').attr('class','polygon');
                 }
                 jQuery('div.item[data-id='+_id+']',_selected_titles_wrap).remove();
                 if(_geodata_ids['district-areas'].length + _geodata_ids['districts'].length + _geodata_ids['subways'].length == 0) jQuery('.address-select').removeClass('disabled').children('input').attr('disabled', false);
                 if(_geodata_ids[_active_type_click].length == 0) jQuery('.location-list h5.'+_active_type_click+' ').removeClass('active');
                 jQuery('.selected-items[data-type='+_active_type_click+'] .item[data-id='+_id+']').removeClass('on');
            }
            jQuery('input#'+_active_type_click).val(_geodata_ids[_active_type_click].join(','));
            var _filter = jQuery('#geodata-picker-wrap .filter');
            jQuery('span', _filter).each(function(){
                jQuery(this).children('i').text(_geodata_ids[jQuery(this).data('type')].length);
            })

            //leftsidebar
            if(jQuery('#left-column #estate-search').length > 0 && ( typeof _event == "undefined" || ( _event.screenX && _event.screenX != 0 && _event.screenY && _event.screenY != 0) ) ) {
                var _sidebar_wrap = jQuery('#left-column #estate-search');
                jQuery('.' + _active_type_click + ' [data-id=' + _id + ']').click();
            }

            geodataInformer(_geodata_ids);
        }        
        function districtMark(){
            if(_geodata_ids[_active_type]!==undefined && _geodata_ids[_active_type].length>0)
            {
                for(i=0; i < _geodata_ids[_active_type].length; i++){
                   if(_active_type == 'subways'){
                        jQuery('.subways-title-item[data-subway-title-id='+_geodata_ids[_active_type][i]+']',jQuery('#subways-title-wrap')).addClass('active');
                        jQuery('#subways-svg > circle[data-id='+_geodata_ids[_active_type][i]+']').attr('class',jQuery('#subways-svg > circle[data-id='+_geodata_ids[_active_type][i]+']').attr('class')+' active');
                   } else {
                       jQuery('#'+_active_type+'-svg').children('polygon[data-id='+_geodata_ids[_active_type][i]+']').attr('class','polygon active');
                   }
                }    
            }
            geodataInformer(_geodata_ids);
        }
    //информер общего количества выбранных объектов
    function geodataInformer(_geodata_ids){
        if(jQuery('.list-picker.location i').length > 0){
             var _length = _geodata_ids['district-areas'].length + _geodata_ids['districts'].length + _geodata_ids['subways'].length;
             if(_length > 0) jQuery('.list-picker.location i').removeClass('hidden').text(_length);
             else jQuery('.list-picker.location i').addClass('hidden');
        }
    }
    //МЕТРО
    //hover над иконкой метро
    jQuery('body').delegate('#subways-svg > circle', 'mouseover',function(){
        subwayHover(jQuery(this).data('id'),'mouseover');
    }).delegate('#subways-svg > circle', 'mouseout',function(){
        subwayHover(jQuery(this).data('id'),'mouseout');
    })
    jQuery('body').delegate('#subways-title-wrap > .subways-title-item','mouseover',function(){
        subwayHover(jQuery(this).data('subway-title-id'),'mouseover');
    }).delegate('#subways-title-wrap > .subways-title-item', 'mouseout',function(){
        subwayHover(jQuery(this).data('subway-title-id'),'mouseout');
    })  
    function subwayHover(_active,_estate_url){
        var _circle = jQuery('#subways-svg > circle[data-id='+_active+']');
        var _span = jQuery('.subways-title-item[data-subway-title-id='+_active+']',jQuery('#subways-title-wrap'));
        var _class = _circle.attr('class');
        if(_estate_url=='mouseover'){
            _circle.attr('class',_class+' hover') ;
            _span.addClass('hover') ; 
            jQuery('#geodata-picker-wrap .location-list div[data-type=subways] .item[data-id='+_active+']').addClass('hover');
        }  else {
           _circle.attr('class',_class.replace(' hover',''));
           _span.removeClass('hover') ;
           jQuery('#geodata-picker-wrap .location-list div[data-type=subways] .item[data-id='+_active+']').removeClass('hover');
        }
        
        
    }
    //выбор линии метро
    jQuery('body').delegate('#subways-lines span', 'click', function(){
        if(jQuery(this).hasClass('on')) {
            var _action = 'del';
            jQuery(this).removeClass('on');
        } else {
            _action = 'add';
            jQuery(this).addClass('on');
        }
        jQuery('#subways-title-wrap .subways-title-item[data-line='+jQuery(this).data('id')+']').each(function(){
            var _this = jQuery(this);
            geoChoose(_action, _this.text(), _this.data('subway-title-id'), 'subways');    
        })
    })
    //клик по названию / иконке
    jQuery('body').delegate('#subways-title-wrap > .subways-title-item','click',function(){
        var _this = jQuery(this);
        var _action = 'add';
        if(_this.hasClass('active')) _action = 'del';
        geoChoose(_action, _this.text(), _this.data('subway-title-id'), _active_type);
    })
    jQuery('body').delegate('#subways-svg > circle', 'click',function(){
        var _this = jQuery(this);
        var _action = 'add';
        var _class = _this.attr('class');
        if(_class.indexOf('active') > 0) _action = 'del';
        geoChoose(_action, jQuery('.subways-title-item[data-subway-title-id='+_this.data('id')+']',jQuery('#subways-title-wrap')).text(), _this.data('id'), _active_type);
    })   

    
    // стоимость в КП
    jQuery('.fast-search .estate-complex.cottage .select-object_type').on('change', function(){
        if(parseInt(jQuery(this).children('input').val()) > 0) jQuery('.price-selector').removeClass('inactive');
        else jQuery('.price-selector').addClass('inactive');
        
    })

    
    
    if(jQuery('.catalog-item .object-types-list.links.simple-view').length > 0){
        var _links_wrap = jQuery('.catalog-item .object-types-list.links.simple-view');
        jQuery('.tab .expand', _links_wrap).on('click', function(){
            jQuery(this).hide().parents('.tab').css({'max-height' : '100%'});
        })
    }
});