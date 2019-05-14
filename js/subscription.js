jQuery(document).ready(function(){
    
    jQuery('.subscribe-panel .subscribe-button').on('click', function(){
        if( jQuery(this).siblings('form').length > 0 ){
            jQuery(this).parent().addClass('active');
            return false;
        }
        subscribeSubmit( jQuery(this).parent(), jQuery(this).data('email') )
    })
    
    jQuery('.subscribe-panel form').on('submit', function(){
        subscribeSubmit( jQuery(this).parent(), jQuery(this).find('input').val()) 
        return false;
    })
    
    
    function subscribeSubmit( _this, _email){
        var _url = _this.data( 'url' );
        var _estate_url = _this.data( 'estate-url' );
        var _estate_type = _this.data( 'estate-type' );
        var _title = _this.data( 'title' );
        var _deal_type = _this.data( 'deal-type' );
        var _params = {
             url : _estate_url,
             estate_type : _estate_type,
             title : _title,
             deal_type : _deal_type,
             email : _email
        }
        getPending( _url, _params, false, function(){
            _this.removeClass('active').addClass('success').html( 'Вы подписаны на рассылку!\n\n«' + _title + '»' )    
        });
        
    }
    
});                       