jQuery(document).ready(function(){
    
    jQuery(' .housing-estates-experts .sent-mail-status .send ').on('click', function(){
        var _this = jQuery(this);
        var _id = _this.data('id');
        _this.parent().addClass('active');
        getPending('/admin/service/housing_estates_rating/experts/invite/' + _id + '/' );
    })
});