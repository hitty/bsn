jQuery(document).ready(function(){
    //голосование - отметка
    jQuery(document).on("click", '.modal-inner .item', function(){
        var _this = jQuery(this);
        var _parent = _this.parents('.list');
        if( !_parent.hasClass('can-vote') ) return false;
        _this.addClass('active').siblings('.item').removeClass('active');
        jQuery('.modal-inner .button').removeClass('inactive');
    })
    //голосование - отправка результатов
    jQuery(document).on("click", '.modal-inner .button', function(){
        var _location = '/' + window.location.pathname.replace(/\//g, '') + '/vote/';
        var _active_el = jQuery('.modal-inner .item.active');
        var _id_category = jQuery('.modal-inner .list').data('id');
        //получение контента в зависимости от способа получения данных
        getPending( _location, {id_category : _id_category, id: _active_el.data('id')}, false, 
            function(){ 
                jQuery('.voting-success').addClass('active');
                setTimeout( function(){
                    jQuery('.voting-success').removeClass('active');
                }, 1500)
                jQuery('.modal-inner .list').removeClass('can-vote');
                jQuery('.modal-inner .button').remove();
                jQuery('.categories-list .item[data-id=' + _id_category + ']').removeClass('none-voted').find('.voted-for b').text( _active_el.find('.title').text() );
                showVoteResults( _id_category )
            }
        );                
    });

});