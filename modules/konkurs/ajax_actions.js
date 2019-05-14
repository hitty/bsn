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
});