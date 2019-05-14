_search_flag = false;
jQuery(document).ready(function(){
    
    jQuery( '.fullscreen-gallery' ).fullscreenGallery();
    
    jQuery('.offers .item').on('click', function(){
        var _form = jQuery('.fast-search');
        console.log( _form.html() );
        jQuery(' .checkbox-group.rooms-count label[for=rooms_count_' + jQuery(this).data('rooms')+ '_build]', _form).click().siblings('label').removeClass('on');
        jQuery("html,body").animate({ scrollTop: _form.offset().top - jQuery('header').height()}, "slow");
    })
    
    setTimeout(function(){
        
    }, 500);
    
    jQuery('.objects-by-agency span').on('click', function(){
        var _id = parseInt(jQuery(this).data('id'));
        jQuery('[name=agency]', jQuery('.fast-search')).val( _id > 0 ? _id : 0 );
        jQuery('[name=exclude_agency]', jQuery('.fast-search')).val( _id > 0 ? 0 : jQuery('.objects-by-agency span:first').data('id') );
        jQuery('.fast-search').submit();
    })
    
    //рейтинг - голосование
    if( jQuery('.housing-estate-votings').length > 0) {
        var _wrap = jQuery('.housing-estate-votings'); 
        var _button = jQuery('.actions .button'); 
        //обработка подтягивания звездочек левее
        jQuery('.stars span', _wrap).on('mouseover',function(){
            if( _wrap.hasClass('voted') ) return false;
            _current_rating = jQuery(this).index() + 1;
            jQuery('.stars span', _wrap).each(function(){
                if(jQuery(this).index()<_current_rating){
                    jQuery(this).addClass('hovered');
                }
            });
        });
        //если не нажали, при уходе звездочки левее сбрасываются
        jQuery('.stars span', _wrap).on('mouseleave',function(){
            if( _wrap.hasClass('voted') ) return false;
            _current_rating = jQuery(this).index() + 1;
            jQuery('.stars span', _wrap).each(function(){
                if( jQuery(this).index()<_current_rating ){
                    jQuery(this).removeClass('hovered');
                }
            });
        });    
        
        //нажимаем: включаем все звездочки левее
        jQuery('.stars span', _wrap).on('click',function(){
            if( _wrap.hasClass('voted') ) return false;
            jQuery(this).parent().addClass('active');
            jQuery('.stars span', _wrap).removeClass('active').removeClass('hovered');
            _current_rating = jQuery(this).index() + 1;
            jQuery('.stars span', _wrap).each(function(){
                if(jQuery(this).index()<_current_rating) jQuery(this).addClass('active');
            });
            _button.removeClass('disabled');
            
        });
        _button.on('click',function(){           
            if(_button.hasClass('disabled')) return false;
            var _total_wrap = jQuery('.total', _wrap);
            if( _total_wrap.length > 0 ) {
                var _user_rating = parseFloat(_total_wrap.text())
                var _user_alredy_voted = parseInt(_wrap.data('users-total'))
                _total_wrap.text( ( (_user_alredy_voted*_user_rating + _current_rating) / (_user_alredy_voted + 1) ).toFixed(2))
            }
            _wrap.addClass('voted');
            getPending('vote/', {rating: _current_rating} );
        })
    }
});   