jQuery(document).ready(function(){
    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker({
          timepicker:false,
          format:'d.m.Y',
          onChangeDateTime:function(dp,jQueryinput){
              jQueryinput.attr('value',jQueryinput.val())
              if(jQueryinput.attr('id') == 'f_date_end' || jQuery('#f_date_end').val()!='') {
                  jQuery('input[name=f_period]').val('0');
                  filter_activate();
              }
              jQueryinput.blur();
          }
        });

    }	
    jQuery('#span_field_period input').on('change', function(){  
        var _val = jQuery(this).attr('value');
        if(_val==1) _diff  = 7;
        else _diff = 30;
        var _previous_date = new Date();
        _previous_date.setDate(_previous_date.getDate() - _diff);
        jQuery('#f_date_start').val(_previous_date.format("dd.mm.yyyy"));
        jQuery('#f_date_end').val(new Date().format("dd.mm.yyyy"));
        filter_activate();
    })    

    var _table = jQuery('.finances-list');
    jQuery('.comment, .comment-edit',_table).each(function(e){
        jQuery(this).click(function(){
            jQuery(this).parents('tr').addClass('w-agency-comment');
            jQuery(this).siblings('input').focus();
        })
    })
    

    
});