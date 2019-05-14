jQuery(document).ready(function(){
	jQuery('#redirect_submit').click(function(e){
        var _form = jQuery('#cottage_form');
        _form.attr('action',_form.attr('action')+'?redirect=true');
        _form.submit();
    })
	//fileuploader init
	if(jQuery('#file_upload').length>0){
		jQuery('#file_upload').uploadifive();
	}	
    jQuery('.change-manager').on('change', function(){
        jQuery(this).next('input').fadeIn();
    })
    jQuery('.save-manager').each(function(){
        jQuery(this).on('click',function(){
            _this = jQuery(this)
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/estate/cottages/save_manager/',
                data: {ajax: true, id: _this.data('id'), id_manager: _this.siblings('.change-manager').val()},
                success: function(msg){ 
                    if(msg.ok)  _this.fadeOut();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            });
            
        })
    });    
});