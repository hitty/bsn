jQuery(document).ready(function(){
    _test_id = parseInt( jQuery('article').data('id') );
    _test_wrap = jQuery('.central-main-content');
    
    //следующий вопрос
    jQuery(document).on('click', '.to-step', function(){
        var _step = parseInt( jQuery(this).data('step') ) ;
        if( !jQuery('.question').hasClass('answered') && _step > 1) return false;
        getPendingContent('.central-main-content', '/articles/test/question/', {id_parent: _test_id,step: _step});
        return false;
    })
    //результаты теста вопрос
    jQuery(document).on('click', '.to-results', function(){
        getPendingContent('.central-main-content', '/articles/test/results/', {id_parent: _test_id})
        return false;
    })
    //выбор ответа
    jQuery(document).on('click', '.item', function(){
        if( jQuery('.question').hasClass('answered') ) return false;
        var _this = jQuery(this) ;
        var _id_answer = _this.data('id-answer');
        console.log(_this.data('id'))
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/articles/test/answer/',
                data: {ajax: true, 
                       id_answer: _id_answer,
                       step: _this.closest('.list').data('step'),
                       id_question: _this.closest('.list').data('id-question'),
                       id_parent: _test_id
                },
                success: function(msg){ 
                    jQuery('.item', _test_wrap).each(function(){
                        var _index = parseInt( jQuery(this).data('id-answer'));
                        if( typeof msg.results[_index] != 'undefined'){
                            jQuery(this).addClass( msg.results[_index].status ).append('<span class="answer">' + msg.results[_index].answer + '</span>')    
                            jQuery('.question').addClass('answered');
                        }
                    }) 
                    return false;   
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            }); 
            return false;
    })
    
    jQuery(document).on('click', '.promo-button', function(e){
        var _el = jQuery(this);
        var _params = {id:_el.data('id')};
        getPending('/' + _el.data('type') + '/click/', _params)
            
    });    
})
