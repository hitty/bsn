jQuery(document).ready(function(){
    //форма подписки на новости
    var _subscribe = jQuery('.subscribe-news');
    jQuery('button', _subscribe).on('click', function(){
        var _input = jQuery('input', _subscribe);
        var _val = _input.val();
        var _text = '';
        var _button = jQuery(this);
        _button.remove();
        if(_val == '') _text = 'Поле не может быть пустым'
        else if( !validateEmail(_val) ) _text = 'Введите правильный email'
        if(_text != '') {
            _input.addClass('red-border').next('span').text(_text);
        } else {
            jQuery('.text', _subscribe).remove();
            
            jQuery('.success', _subscribe).show();
            getPending('/news/subscribe/', {email: _val})
            if( typeof _button.data('id') !== "undefined" ) getPending('/articles/click/', {id:  _button.data('id')})
        }
        return false;
    })
})
