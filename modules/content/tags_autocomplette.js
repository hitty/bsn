jQuery(document).ready(function(){
    var _input = jQuery("#tag_add_input");
    /* автокомплит тегов */
    _input.typeWatch({
        callback: function(){
            var _searchstring = this.text;
            _input.addClass('wait');
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: _input.attr('data-url')+'list/',
                data: {ajax: true, search_string: _searchstring},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        if(msg.list.length>0) showTagsPopupList(_input, msg.list);
						else hideTagsPopupList();
                    } else alert('Ошибка запроса к серверу!');
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                },
                complete: function(){
                    _input.removeClass('wait');
                }
            });
        },
        wait: 750,
        highlight: true,
        captureLength: 2
    }).blur(hideTagsPopupList());
    
    /* связь вписанного тега с объектом и добавление в список связянных тегов */
    jQuery('#tag_add_btn').click(function(){
        var _tag = _input.val();
        if(_tag.length<1) return false;
        _input.addClass('wait');
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: _input.attr('data-url')+'add/',
            data: {ajax: true, tag: _tag, id_object: jQuery('#tags_list').attr('data-id_object')},
            success: function(msg){
                if(typeof(msg)=='object' && msg.ok) {
                    jQuery('#tags_list').append(jQuery('<div class="tag_item"><span class="tag_id">'+msg.id+'</span><span class="tag_title">'+msg.tag+'</span><span class="tag_close" title="Удалить">удалить</span></div>'));
					_input.val('');
                } else console.log('Запрос не выполнен! '+msg.error);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert('Ошибка запроса к серверу!');
            },
            complete: function(){
                _input.removeClass('wait');
            }
        });
    });

    /* разлинковка тегов */
    jQuery('#tags_list').bind('click', function(e){
        if(jQuery(e.target).hasClass('tag_close')){
            var _el = jQuery(e.target).parent();
            var _id_tag = jQuery('.tag_id', _el).html();
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: _input.attr('data-url')+'del/',
                data: {ajax: true, id_tag: _id_tag, id_object: jQuery('#tags_list').attr('data-id_object')},
                success: function(msg){
                    if(typeof(msg)=='object' && msg.ok) {
                        _el.fadeOut('500',function(){jQuery(this).remove()});
                    } else console.log('Запрос не выполнен! '+msg.error);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    alert('Ошибка запроса к серверу!');
                },
                complete: function(){
                    _input.removeClass('wait');
                }
            });
        }else{
            if(jQuery(e.target).parents('.tag_item').length > 0) jQuery(e.target).parents('.tag_item').toggleClass('selected');
            else return;
            //если есть выделенные, подбираем статьи
            if(jQuery('#tags_list').children('.tag_item.selected').length > 0){
                var _selected_tags = [];
                var _article_id = parseInt(window.location.href.split('/')[7]);
                jQuery('#tags_list').children('.tag_item.selected').each(function(){
                    _selected_tags.push(jQuery(this).children('.tag_id').html());
                });
                jQuery('.tagged-articles-block').children('.search-title').addClass("searching");
                jQuery('.tagged-articles-block .search-results').html("");
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: '/admin/content/news/tagged_articles/',
                    data: {ajax: true, article_id:_article_id, tags: _selected_tags},
                    success: function(msg){
                        if(typeof(msg)=='object' && msg.ok) {
                            jQuery('.tagged-articles-block .search-results').html(msg.html);
                        } else console.log('Запрос не выполнен! '+msg.error);
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        alert('Ошибка запроса к серверу!');
                    },
                    complete: function(){
                        _input.removeClass('wait');
                        jQuery('.tagged-articles-block').children('.search-title').removeClass("searching");
                    }
                });
            }else{
                jQuery('.tagged-articles-block').children('.search-title').removeClass("searching");
                jQuery('.tagged-articles-block .search-results').html("");
            }
        }
        return false;
    });
});

function showTagsPopupList(_el,_list){
    var str = '<ul id="tags_popup_list">';
    for(var i in _list){
        str += '<li><span class="tag_title">'+_list[i].title+'</span><span class="tag_count">('+_list[i].tag_count+')</span></li>';
    }
    str += '</ul>';
    hideTagsPopupList();
    jQuery('#tags_inputbox').append(jQuery(str));
    jQuery("#tags_popup_list li").bind('click', function(){
        jQuery("#tag_add_input").val( jQuery('.tag_title',jQuery(this)).html() );
        hideTagsPopupList();
    });
}
function hideTagsPopupList(){
    jQuery("#tags_popup_list li").unbind('click');
    jQuery("#tags_popup_list").remove();
}