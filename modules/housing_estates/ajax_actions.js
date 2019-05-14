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
	if(jQuery('.file_upload').length>0){
		jQuery('.file_upload').uploadifive();
	}	
});