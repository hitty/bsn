jQuery(document).ready(function(){
    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker();
    }
	
	//fileuploader init
	if(jQuery('#file_upload').length>0){
		jQuery('#file_upload').uploadifive();
	}	
});