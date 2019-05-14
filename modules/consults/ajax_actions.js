jQuery(document).ready(function(){
	//fileuploader init
	if(jQuery('#file_upload').length>0){
		jQuery('#file_upload').uploadifive({
				'buttonSetMain':false,
				'queueSizeLimit':1,
				'multi':false
			}
		);
	}
    // datetimepicker init
    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker();
    }
    //clearing fonts in answer
    jQuery('.answers-block').find('font').removeAttr('face').removeAttr('size');
});