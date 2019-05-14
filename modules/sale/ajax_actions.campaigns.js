jQuery(document).ready(function(){
    jQuery('.list_table').click(function(e){
        var _target = jQuery(e.target);
        switch(true){
            case _target.parent('div').hasClass('ci-delete') :
            case _target.parent('div').hasClass('ci-archive') :
                if(_target.parent('div').hasClass('ci-delete')) if(!confirm('Вы уверены, что нужно удалить этот объект?')) return false;
                else if(_target.parent('div').hasClass('ci-archive')) if(!confirm('Вы уверены, что хотите переместить в архив этот объект?')) return false;

                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.attr('href'),
                    data: {ajax: true},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids.length){
                                    if(_target.parent('div').hasClass('ci-delete')) location.reload();
                                    else if(_target.parent('div').hasClass('ci-archive')) jQuery('#item_'+msg.ids[0]).removeClass('active').addClass('archive');
                                } else alert('Ни один элемент не удален.');
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
        }
        return true;
    });



    // текущая дата
    var _today = new Date();   
    var yesterday = new Date(_today.getTime() - (24 * 60 * 60 * 1000));    
    jQuery('.ov-timer').each(function(){
        setDateParams(jQuery(this), jQuery(this).data('date-end'));

    }); 
    function setDateParams(_this, _date){
        // дата предстоящего события (год, месяц, число)
        var _mysql_date = _date.split(/[.]/);
        var _endDate = new Date(_mysql_date[2], _mysql_date[1]-1, _mysql_date[0]);
        // если событие еще не наступило
        if(yesterday <= _endDate){
            if(Math.floor(Math.round(_endDate-_today)/86400000) < 3) _this.addClass('red');
            _this.text(timeToEvent(_endDate, 'inline')); 
            refreshIntervalId  = window.setInterval(function(){ 
              _this.text(timeToEvent(_endDate, 'inline')); 
            },1000);           
        } 
    }    

    //управление превью акции
    jQuery('input[name="id_offer_type"]').change(function(){
        setSelector(jQuery(this).val());
    });
    
    function setSelector(__thisval){
        _id_offer =  __thisval;
        if(__thisval == 1){
            jQuery('#label_field_action, #label_field_cost, #label_field_old_cost, #p_field_action_title, #span_field_action, #span_field_cost, #span_field_old_cost, #span_field_action_title').slideDown(0);
            jQuery('#label_field_installment, #label_field_discount, #span_field_installment, #span_field_discount').slideUp(0);
            jQuery('#label_field_old_cost').css({'margin-left': '0px'});
        } else if(__thisval == 2) {
            jQuery('#label_field_old_cost').css({'margin-left': '96px'});
            jQuery('#label_field_action, #label_field_cost, #label_field_installment, #p_field_action_title, #span_field_action, #span_field_cost, #span_field_installment, #span_field_action_title').slideUp(0);
            jQuery('#label_field_old_cost, #label_field_discount, #span_field_old_cost, #span_field_discount').slideDown(0);
        } else {
            jQuery('#label_field_action, #p_field_action_title, #label_field_old_cost, #label_field_discount, #span_field_action, #span_field_action_title, #span_field_old_cost, #span_field_discount').slideUp(0);
            jQuery('#label_field_cost, #label_field_installment, #span_field_cost, #span_field_installment').slideDown(0);
            jQuery('#label_field_old_cost').css({'margin-left': '0px'});
        }
        fillCampaignPreview();
    }
    
    var _in = jQuery('input[name="id_offer_type"]');
    if(_in.size()>0) setSelector(jQuery('.offer-sticker:visible').data('id'));
    
    //изменили данные скидок
     jQuery('#cost,#old_cost,#installment,#discount,#action_title').on("change, keyup", function(){
         fillCampaignPreview();
    })
    //инициализация и управление датами показа
    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker({
          timepicker:false,
          format:'d.m.Y',
          minDate:0,
          onChangeDateTime:function(dp,$input){
              $input.attr('value',$input.val())
              if($input.attr('id') == 'date_end')  {
                  jQuery('.ov-timer').attr('data-date-end',$input.val());
                  if(typeof refreshIntervalId!='undefined') clearInterval(refreshIntervalId);
                  setDateParams(jQuery('.ov-timer'), jQuery('.ov-timer').attr('data-date-end'));
              }
              $input.blur();
          }
        });

    }

    //fileuploader init
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({
                onChangeCount: function(){
                    checkForm();
                }
            }
        );
    }

    jQuery(document).on('click','.boxcaption_main', function(){      
        jQuery('#offer-img').attr('src', jQuery(this).siblings('.itemsContainer').children('img').attr('src'));
    })

   
    
});
var _id_offer = 1;

function fillCampaignPreview(){
    var _has_offers = jQuery('.offer-price').data('offers');

    var _offer_addr = jQuery('#txt_addr').val();
    if(_offer_addr == '') _offer_addr = 'АДРЕС ОБЪЕКТА';
    jQuery('#offer-img-address').text(_offer_addr);
    //id_offer_type color
    jQuery('.offer-sticker[data-id='+_id_offer+']').show().siblings('div.offer-sticker').hide();
    //управление ценой
    jQuery('.cd-old-price').show();
    var _cost = parseInt(jQuery('#cost').val().replace(/\s/g,''));

    if(_has_offers == '' || _cost==0){
        var _old_cost = parseInt(jQuery('#old_cost').val().replace(/\s/g,''));
        if(_id_offer == 1){ //акция
            if(_cost > 0) jQuery('#offer-price').html('от <span>'+_cost+'</span> <span class="rur">a</span>');
            else jQuery('#offer-price').html('Цена не указана');

            if(_old_cost > 0) jQuery('#offer-price-old').html('<span>'+_old_cost+'</span>');
            else jQuery('#offer-price-old').html('старая цена');

            jQuery('#cd-old-price').text(jQuery('#old_cost').val().replace(/\s/g,''));
        } else if(_id_offer == 2){
            var _discount = parseFloat(jQuery('#discount').val())
            _cost = parseInt(_old_cost * ((100-_discount) / 100));
            if(_cost > 0) jQuery('#offer-price').html('от <span>'+_cost+'</span> <span class="rur">a</span>');
            else jQuery('#offer-price').html('Цена не указана');

            if(_old_cost > 0) jQuery('#offer-price-old').html('<span>'+_old_cost+'</span>');
            else jQuery('#offer-price-old').html('старая цена');

        } else if(_id_offer == 3){
            if(_cost > 0) jQuery('#offer-price').html('от <span>'+_cost+'</span> <span class="rur">a</span>');
            else jQuery('#offer-price').html('Цена не указана');
        }
    }

    if(_id_offer == 1){ //акция
        var _action_title = jQuery('#action_title').val();
        if(_action_title == '') _action_title = 'ТЕКСТ АКЦИИ';
        jQuery('.offer-sticker.green > span').text(_action_title);
    } else if(_id_offer == 2){
        var _discount = parseFloat(jQuery('#discount').val())
        if(_discount>100) {
            _discount = parseInt(_discount/10);
            jQuery('#discount').val(_discount)
        }
        if(_discount > 0) jQuery('#discount-text').html('до <span>'+_discount+'</span> %');
        else jQuery('#discount-text').html('РАЗМЕР СКИДКИ');

        jQuery('.offer-sticker.orange > span').text(_discount);
    } else if(_id_offer == 3){
        var _installment = parseFloat(jQuery('#installment').val())
        if(_installment>100) {
            _installment = parseInt(_installment/10);
            jQuery('#discount').val(_installment)
        }
        if(_installment > 0) jQuery('#installment-text').html('рассрочка <span>'+_installment+'</span> %');
        else jQuery('#installment-text').html('РАЗМЕР РАСССРОЧКИ');
        
        
        jQuery('.cd-old-price').hide();
    }

    return false;
        
}    