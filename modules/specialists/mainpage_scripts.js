jQuery(document).ready(function(){
    /*
    jQuery('#activity_selector').on('change', function(){
        document.location.href = '/specialists/' + jQuery('input', jQuery('#activity_selector')).val()  + '/'    
    })
    */
    
    jQuery( '#fast-search-form').formSubmit( 
        {
            ajax_form : false, 
            onFormSuccess:function(data){
                var _url = data.url + ( data.activity_selector.length > 0 ? data.activity_selector + '/' : '' ) + ( data.specialist.length > 0 ? '?specialist=' + data.specialist : '' );
                document.location.href = _url;
            }
        } 
    );
});