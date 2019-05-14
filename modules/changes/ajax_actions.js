jQuery(document).ready(function(){

    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker();
    }
	if(jQuery('#file_upload').length>0){
		jQuery('#file_upload').uploadifive({'queueSizeLimit':1});
	}
    
});