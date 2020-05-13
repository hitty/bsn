_window_scroll_top = 0;
jQuery(document).ready(function(){
    
    var loc = history.location || document.location;
    var _val = getGPval(loc);

    jQuery('.item').each(function(){ 
        jQuery(this).popupWindow({
            onInit : function( i, data  ){
                if( data.can_vote == 0 ) showVoteResults( data.id_category )
            }
        }) 
    });

    if(_val != '') jQuery(".item[data-location = '" + _val + "']").click();
    
})
function showVoteResults( _id_category ){
    getPending( 
        '/' + window.location.pathname.replace(/\//g, '') + '/results/' ,
        {id_category: _id_category },
        false,
        function( data ){
            var total = parseInt( data.total );
            var list = data.list;
            jQuery( '.modal-inner .list .item' ).each( function(){
                var id = jQuery(this).data('id')
                jQuery('.results i', jQuery(this) ).css( { 'width': 100 * ( parseInt( list[id].vote_count ) / total ) + '%'})    
            })
        }
        
    )

}

   