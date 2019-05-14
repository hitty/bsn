var _process = 0;
var _info_line = '';
jQuery(document).ready(function(){
    jQuery('aside li a.active:eq(' + ( parseInt(jQuery('aside li a.active').length) - 1 ) + ')' ).addClass('with-arrow');
    jQuery('li', jQuery('#objects-list-title')).on('click', function(){
          var _this = jQuery(this);
          _this.addClass('active').siblings('li').removeClass('active');
          jQuery('.middle-panel .tabs').removeClass('active').siblings('div[class*= "'+_this.data('type')+'"]').addClass('active'); 
    })
    
    jQuery('.download .link').on('click', function(){
        if(jQuery(this).hasClass('disabled')) return false;
        var _link = jQuery('#download-link').val();
        if(_link!=''){
            jQuery('#report-info').html('').append('Скачивание файла по ссылке...');
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/members/objects/agencies_uploads/test_link/',
                data: {ajax: true, link: _link},
                success: function(msg){
                    _process = msg.id;
                    getStatus(_process);
                }
            });
        }
        return false;
    })    
    
    jQuery('#download-link').on('change', function(){
        if(jQuery(this).val() == '') jQuery('.download .link, .help-text, .download-now, .download-button, .download-time').addClass('disabled');
        else {
            jQuery('.download .link').removeClass('disabled');
            if(jQuery('.switcher i').hasClass('active')) jQuery('.help-text, .download-now, .download-button, .download-time').removeClass('disabled');
        }
    })
    jQuery('.switcher i').on('click', function(){
        var _class = jQuery(this).attr('class');
        getPending('/members/objects/agencies_uploads/status_change/', {status:_class})
        if(_class == 'active' && jQuery('#download-link').val() != '') jQuery('.help-text, .download-now, .download-button, .download-time').removeClass('disabled');
        else jQuery('.help-text, .download-now, .download-button, .download-time').addClass('disabled');
        
    });
    
    if(jQuery('#download-title').length > 0){
         getStatus(jQuery('#download-title').data('id'));
    }
    jQuery('.download-report').on('click', function(){
        if(jQuery(this).hasClass('disabled') || parseInt(jQuery(this).data('id')) <=0) return false;
        window.open('/members/objects/agencies_uploads/pdf/'+jQuery(this).data('id')+'/');
        
    })
    /*
    if(jQuery('#select-time').length > 0){
        jQuery(".list-selector.select-time").on('change', function(event, value){    
            changeDateTime('change');
        })
        
    }
    */
    jQuery('.download-button').on('click', function(){
        if(jQuery(this).hasClass('disabled')) return false;
        jQuery('#select-time').on("change", changeDateTime('closest'));
    })
    
    function changeDateTime(url){
        if(parseInt(jQuery('#select-time').val()) == 0 ) {
            alert('Выберите время');
            return false;
        }
        var _el = jQuery('.download-time .list-data li[class=selected]');
        jQuery('.download-time-text').text(_el.text());
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/members/objects/agencies_uploads/time_change/'+url+'/',
            data: {ajax: true, hour: _el.data('hour'), minute: _el.data('minute'), download_status: jQuery('.download-now label').hasClass('on')},
            success: function(msg){
                jQuery('#select-hour').text(msg.hour);   
                jQuery('#select-minute').text(msg.minute);   
                jQuery('.help-text span').text(msg.hour + ' ' + msg.hour_text + ' ' + msg.minute + ' ' + msg.minute_text); 
                if(url == 'closest' && msg.ok) {
                    jQuery('.download-time, .download-now').addClass('disabled');
                    jQuery('.download-button, .select-time, .download-now').remove();
                    jQuery('.download-time-text').removeClass('disabled');
                }
            }
        });
    }
    
    /*
    jQuery('.download-now label').on('click', function(){
        var _el = jQuery('label');
        getPending('/members/objects/agencies_uploads/download_status/', {status: _el.hasClass('on')});
    })
    */
    
});

//получение статуса процесса
function getStatus(_process){
    _process_pending = window.setInterval(function(){
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/members/objects/agencies_uploads/process_info/',
                data: {ajax: true, process: _process},
                success: function(msg){
                   if(msg.status == 2) {
                       window.clearInterval(_process_pending)
                       jQuery('#report-info').html(msg.log);
                       if(msg.type == 2) jQuery('#download-report').removeClass('disabled');
                   } else jQuery('#report-info').append(msg.log);
                   if(msg.type == 2){
                       var _progressbar = jQuery('.progressbar', jQuery('.progress-wrap'));
                       jQuery('span', _progressbar).css({'width':msg.percentage+'%'});
                       jQuery('i', _progressbar).text(msg.percentage+'%');
                   }
                }
            });
    }, 1000)
}