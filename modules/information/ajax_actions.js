jQuery(document).ready(function(){
    //fileuploader init
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({'queueSizeLimit':200});
    }    
    jQuery('.delete-file').on('click',function(){
        var _this = jQuery(this);
        _id =  _this.data('id');
        _type =  _this.data('type');
        _url = '/admin/content/information/del_doc/'
        jQuery.ajax({
            type: "POST", async: true, dataType: 'json', cache: true, url: _url,
            data: {ajax: true, id: _id, type: _type},
            success: function(msg){
                if(typeof(msg)=='object') {
                    _this.parents('span.attached-file').fadeOut(200);
                } else alert('Ошибка удаления файла!');
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert('Ошибка связи с сервером!');
            },
            complete: function(){
            }
        });
                
    })     
    
    if(jQuery('.code').length!=0) jQuery('.code').text('{gallery:1-5}');
});