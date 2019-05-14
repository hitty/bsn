jQuery(document).ready(function(){

    var _wrap = jQuery('.tarifs-table')    ;
    
    jQuery('.label').on('click', function(){
        var _label = jQuery(this);
        _label.addClass('on').siblings().removeClass('on');
        var _discount = _label.data('discount');
        var _period = _label.data('period');
        jQuery('td[data-in-month]', _wrap).each(function(){
            jQuery(this).text( parseInt( jQuery(this).data('in-month') ) * _period);
        })
        jQuery('td.prices', _wrap).each(function(){
            var _el_new_cost = jQuery( '.new-price', jQuery(this) );
            var _el_cost = jQuery( '.old-price', jQuery(this) );
            
            var _new_cost = ( _period * parseInt( _el_new_cost.data('fcost') ) ) * ( 100 - _discount ) / 100
            var _old_cost = _period * parseInt( _el_new_cost.data('fcost') )
            _el_cost.text( number_format( _old_cost, 0, '.', ' ' ) + ' Р');
            _el_new_cost.text( number_format( _new_cost, 0, '.', ' ' ) + ' Р' );
            if( _period == 1) jQuery('.old-price', jQuery(this) ).addClass('hidden');
            else jQuery('.old-price', jQuery(this) ).removeClass('hidden');
           
        })
    })
    jQuery('.pseudo-element').popupWindow({popup_redirect:true});
    jQuery('button',jQuery('.tarifs-table .buttons-row')).each(function(){

        jQuery(this).on('click',function(){
            _tarif = jQuery(this).data('id');
            _months = jQuery('.period-choose .label.on').data('period');
            jQuery('.pseudo-element').attr( 'data-url', '/members/pay_tarif/?id_tarif='+_tarif+'&period='+_months );
            jQuery('.pseudo-element').click();
        })
    })
    
    jQuery('.tarifs-table')
        .mouseover(function(e) {
            var _tag = jQuery(e.target).prop("tagName") == 'TD' ? jQuery(e.target) : ( jQuery(e.target).parent().prop("tagName") == 'TD' ? jQuery(e.target).parent() : ( jQuery(e.target).parent().parent().prop("tagName") == 'TD' ? jQuery(e.target).parent().parent() : false ) )
            var _tarif = _tag.data('tarif');
            if( typeof _tarif == "undefined" ) _tarif = 'S';
            jQuery('.table-hover[data-tarif=' + _tarif + ']').addClass('active').siblings('i').removeClass('active');
            jQuery('.tarifs-text .item[data-tarif=' + _tarif + ']').addClass('active').siblings().removeClass('active');
            jQuery('.tarifs-table td[data-tarif=' + _tarif + ']').addClass('active').siblings().removeClass('active');
        })
        .mouseout(function() {
            _tarif = 'S';
            jQuery('.table-hover[data-tarif=' + _tarif + ']').addClass('active').siblings('i').removeClass('active');
            jQuery('.tarifs-text .item[data-tarif=' + _tarif + ']').addClass('active').siblings().removeClass('active');
            jQuery('.tarifs-table td[data-tarif=' + _tarif + ']').addClass('active').siblings().removeClass('active');
        })
});