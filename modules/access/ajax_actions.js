jQuery(document).ready(function(){
    
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({
                'buttonSetMain':false,
                'queueSizeLimit':1,
                'multi':false
            }
        );
    }
    //блокируем поля ниже общего лимита
    jQuery('input[name="total_objects"]').on('keyup',function(){
        _total_value = jQuery(this).val()
        jQuery("input[name$='_objects']").each(function(){
            if(jQuery(this).attr('name').match(/rent|sell|build/) != null) jQuery(this).prop("disabled",( _total_value != "0" && _total_value != ""));
            //else return true;
        })
    });
    setTimeout("jQuery('input[name=\"total_objects\"]').trigger('keyup')",1000);
    
    //управление  кол-ом вариантов для пакетов агентств
    if(jQuery('.packets_objects_counter').length>0){
        var _el=jQuery('input.packets_objects_counter');
        _el.each(function(){
            var _this = jQuery(this);
            var _name = _this.attr('name');
            var _val = _this.attr('value');
            if(_name=='live_objects') {
                var _rent_val = jQuery('input.packets_objects_counter[name=live_rent_objects]').attr('value');
                jQuery('#'+_name).parent('span').append('<span class="packets_objects_counter_types">Актуальных в базе: ПРОДАЖА: '+_val+'; АРЕНДА: '+_rent_val+'</span>');
            }
            else jQuery('#'+_name).parent('span').append('<span class="packets_objects_counter_types">Актуальных в базе:'+_val+'</span>');
        })    


        function getStatus()
        {
            if(jQuery("select#id_tarif").val() > 0){
                jQuery('.object_packets_vars').attr('disabled', false);
            } else jQuery('.object_packets_vars').attr('disabled','disabled');
        }
        
        getStatus();
        
        jQuery("#id_tarif").change(function(){
            getStatus();
        });    
        
        // возможность изменения тарифа
        function getChangeStatus()
        {
            if(jQuery("input[name=change_tarif]:checked").val() == 1){
                jQuery('#id_tarif,#tarif_start,#tarif_end').attr('disabled', false);
            } else {
                jQuery('#id_tarif,#tarif_start,#tarif_end').attr('disabled','disabled');
            }
        }
        getChangeStatus();
        jQuery("input[name=change_tarif]").change(function(){
            getChangeStatus();
        });   
        
        // возможность изменения тарифа
        function getChangeAdvertPhone()
        {
            var _advert_phone = jQuery("input[name=advert_phone]").val().replace(/\D/g,'');
            console.log( _advert_phone + ';' + _advert_phone.length );
            if( _advert_phone.length == 11 ){
                jQuery('#advert_phone_date_end').attr('disabled', false);
            } else {
                jQuery('#advert_phone_date_end').attr('disabled','disabled');
            }
        }
        getChangeAdvertPhone();
        jQuery("input[name=advert_phone]").on('keyup', function(){
            getChangeAdvertPhone();
        });    

         
        //стоимость не редактируется руками, только подтягивается
        jQuery('#tarif_cost').attr('readonly',true);
        if(jQuery('#tarif_cost').attr('value').length == 0) jQuery('#tarif_cost').val(jQuery('select[name="id_tarif"]').find('option:selected').attr('data-cost'));
        jQuery('select[name="id_tarif"]').on('change',function(){
            if(jQuery('select[name="id_tarif"]').find('option:selected').attr('data-cost') == undefined) jQuery('#p_field_tarif_cost').children('label').removeClass('required');
            else jQuery('#p_field_tarif_cost').children('label').addClass('required');
            jQuery('#tarif_cost').val(jQuery('select[name="id_tarif"]').find('option:selected').attr('data-cost'));
        });
    }
    
});