/**
* Admin Main JavaScript file
* подключается в админку сайта всегда (для любого запрошенного набора) и всегда последним
*/

/*
* вызов отложенного действия (AJAX)
* параметры: URL [, DATA] [, CALLBACK] [, SILENT MODE]
*/
function startPendingAction(_url, _param1, _param2, _param3){
    var _callback = function(){};
    var _data = {ajax: true};
    var _silent_mode = false;
    if(arguments.length>1){
        if(typeof(_param1)=='function') _callback = _param1;
        else if(typeof(_param1)=='object') jQuery.extend(_data, _param1);
        else if(typeof(_param1)=='boolean') _silent_mode = _param1;
    }
    if(arguments.length>2){
        if(typeof(_param2)=='function') {
            if(typeof(_param1)=='function') return false;
            else _callback = _param2;
        } else if(typeof(_param2)=='boolean') {
            if(typeof(_param1)=='boolean') return false;
            else _silent_mode = _param2;
        }
    }
    if(arguments.length>3){
        if(typeof(_param3)=='boolean') {
            if(typeof(_param1)=='boolean' || typeof(_param2)=='boolean') return false;
            else _silent_mode = _param3;
        }
    }
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', url: _url,
        data: _data,  cache: false,
        success: function(msg){
            if(typeof(msg)=='object') {
                if(msg.ok) {
                    if(msg.ids.length){
                        _callback(msg);
                    } else if(!_silent_mode) alert('Ни один элемент не затронут.');
                } else if(!_silent_mode) alert('Ошибка: '+msg.error);
            } else if(!_silent_mode) alert('Ошибка!');
        },
        error: function(){
            if(!_silent_mode) alert('Server connection error!');
        }
    });
    return false;
}
function getPending(_url, _params){
    if(typeof(_params) == 'undefined' || !_params) _params = {ajax: true};
    else _params.ajax = true;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: true,
            url: _url, data: _params,
            success: function(msg){},
            error: function(XMLHttpRequest, textStatus, errorThrown){
                //console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
            }
        });
    return true;
}
function getPendingContent(_element, _url, _params, _cached, _effect, _delete_if_not, _func, _func_on_success){
    var _elem_array = new Array()
    var _url_array = new Array();
    var _params_array = new Array();
    var _cached_array = new Array();
    if(typeof(_element) == 'object'){
        _elem_array =  _element;
        _element =  _elem_array.shift();
        _url_array =  _url;
        _url =  _url_array.shift();
        if(typeof(_params) == 'undefined') _params_array = [];
        else {
            _params_array =  _params;
            if(_params_array.length>0) _params =  _params_array.shift();
            else  _params = {ajax: true};
        } 
        if(typeof(_cached) == 'undefined' || !_cached) _cached_array = [];
        else {
            _cached_array =  _cached;
            if(_cached_array.length>0) _cached =  _cached_array.shift();
            else  _cached = false;
        } 
    } 
    var elem = _element;
    if(typeof(_element) == 'string') elem = jQuery(_element);     
    if(_element.length > 0){
        if(typeof(_params) == 'undefined' || !_params) _params = {ajax: true};
        else{
            if(typeof(_params) == 'string') _params = JSON.parse(_params);
            _params.ajax = true;
        } 
        if(typeof(_cached) == 'undefined') _cached = false;
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: _cached,
            url: _url, data: _params,
            success: function(msg){
                if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok && typeof(msg.html)=='string' && msg.html.length) {
                    if(typeof(_effect) == 'undefined') {
                        elem.fadeOut(100,function(){
                            elem.html(msg.html).fadeIn(200);
                        });
                    } else {
                        jQuery('span.waiting').remove();
                        elem.html(msg.html);
                    } 
                    
                    if(typeof(_func_on_success) == 'object' || typeof(_func_on_success) == 'function') {
                        _func_on_success();
                    }
                    
                } else {
                    
                }
                if(typeof(_elem_array) == 'object' && _elem_array.length>0) getPendingContent(_elem_array, _url_array, _params_array, _cached_array);
                return msg;
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                
            },
            complete: function(){
                if(typeof(_func) == 'object' || typeof(_func) == 'function') {
                    _func;
                }
            }
        });
    }
    return true;
}
function removeTableRows(msg){
    var _obj = null;
    for(var i=0;i<msg.ids.length;i++){
        _obj = jQuery('#item_'+msg.ids[i]);
        _obj.fadeOut(500,function(){_obj.remove();});
    }
}


jQuery(document).ready(function(){
    //подсчет кол-ва символов
    jQuery('input').each(function(){
        var _length = jQuery(this).val().length;
        jQuery(this).siblings('span.count-letters').text(_length);
        jQuery(this).on('keyup', function(){
            var _length = jQuery(this).val().length;
            jQuery(this).siblings('span.count-letters').text(_length);
        })
    })
    jQuery('.notification .close').click(function(){
        jQuery(this).parent().slideUp(200);
        return  false;
    });
    
    jQuery(document).on('click',"img.mUploadImg_photos", function(){
        var _this = jQuery(this);
        var _src = _this.attr('src').replace('/sm/','/big/');
        
        var image = new Image();
        image.src = _src;
        var width = image.width;
        var height = image.height;
        window.open(_src,"Image","width=" + width + ",height=" + height);            

    }) 
    
    //очистка кеша
    jQuery('.delete-memcache').click(function(e){
        var _target = jQuery(e.target);
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: _target.attr('href'),
            data: {ajax: true},
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                        _target.addClass('deleted');
                    } else alert('Ошибка: '+msg.error);
                } else alert('Ошибка!');
            },
            error: function(){
                alert('Server connection error!');
            },
            complete: function(){
            }
        });
        return false;
    });           
    switch(true){
        //confirm deleting if it is specoffers
        case (document.URL.search('\/spec_offers\/') > 0):
            jQuery('.list_table').click(function(e){
            var _target = jQuery(e.target);
            if(_target.is('a') && _target.children('span')) _target = _target.children('span');
            switch(true){
                //удаление элемента
                case _target.hasClass('ico_del') :
                    if(!confirm('Вы уверены, что нужно удалить этот объект?')) return false;
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: _target.parent().attr('href'),
                        data: {ajax: true},
                        success: function(msg){
                            if(typeof(msg) == 'object') {
                                if(msg.ok) {
                                    if(msg.ids.length){
                                        var _obj = null;
                                        for(var i=0;i<msg.ids.length;i++){
                                            _obj = jQuery('#item_'+msg.ids[i]);
                                            _obj.fadeOut(500,function(){_obj.remove();});
                                        }
                                    } else alert('Ни один элемент не удален.');
                                } else alert('Ошибка: '+msg.error);
                            } else alert('Ошибка!');
                        },
                        error: function(){
                            alert('Server connection error!');
                        },
                        complete: function(){
                        }
                    });
                    return false;
                    //установка флагов
                    case _target.attr('data-id')>0:    
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', url: document.location.pathname+'setStatus/',
                            data: {ajax: true, id : _target.attr('data-id'), value : _target.attr('data-state')==1?'':'checked', flag : _target.attr('name')},
                            success: function(msg){
                                if(typeof(msg)=='object') {
                                    if(msg.ok) {
                                        if(msg.ids.length){
                                        } else alert('Ни один элемент не удален.');
                                    } else alert('Ошибка: '+msg.error);
                                } else alert('Ошибка!');
                            },
                            error: function(){
                                alert('Server connection error!');
                            },
                            complete: function(){
                            }
                        });
                        _target.attr('data-state',Math.abs(_target.attr('data-state')-1));
                        return true;
                }
                return true;
            });
        break;
        
        //confirm delete and accept transactions in admin transactions
        case ($('.list_table.transactions').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                switch(true){
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно удалить этот объект?')) return false;
                        startPendingAction(_el.attr('href'), removeTableRows);
                        return false;
                        break;
                    case _target.hasClass('ico_accept'):   
                        if(!confirm('Вы уверены, что нужно подтвердить эту транзакцию?')) return false;
                        startPendingAction(_el.attr('href'), removeTableRows);
                        return false;
                        break;
                }
                return false;
            });
        break;
        
        //confirm deleting objects in admin estate
        case (($('.ico_archive').length > 0||$('.ico_remoderate').length > 0) && !$('.list_table').hasClass('apps')):
            jQuery('.list_table').on('click', '.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                switch(true){
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно удалить этот объект?')) return false;
                        startPendingAction(_el.attr('href'), removeTableRows);
                        return false;
                        break;
                    case _target.hasClass('ico_archive'):   
                        if(!confirm('Вы уверены, что нужно отправить в архив этот объект?')) return false;
                        startPendingAction(_el.attr('href'), removeTableRows);
                        return false;
                        break;
                    case _target.hasClass('ico_restore'):   
                        if(!confirm('Вы уверены, что нужно опубликовать этот объект?')) return false;
                        startPendingAction(_el.attr('href'), removeTableRows);
                        return false;
                        break;
                    case _target.hasClass('ico_remoderate'):   
                        startPendingAction(_el.attr('href'), function(msg){
                            if((msg.manual && !msg.isnew) || (!msg.manual && msg.isnew)) {
                                removeTableRows(msg);
                            } else {
                                if(msg.isnew) alert('Модерация не выполнена (выявлена ошибка)');
                                else alert('Модерация выполнена успешно (в автоматическом режиме)');
                            }
                        });
                        return false;
                        break;
                }

            });
        break;
        
        //similar tags uniting
        case ($('.similar-tag').length > 0):
            jQuery('.small_icons span.unite-tags').on('click',function(e){
                if (jQuery(this).hasClass('disabled')) return false;
                var _target = jQuery(this);
                var _url = _target.attr('data-href')+_target.attr('data-selected-id')+'/';
                if(_target.attr('data-selected-id') == undefined || _target.attr('data-selected-id') == 0){
                    alert('Не выбран тег, по которому будет производиться объединение');
                    return false;
                }
                if(!confirm('Вы уверены, что нужно объединить теги по выбранному?')) return false;
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _url,
                    data: {ajax: true,notselected_ids: _target.attr('data-notselected-id')},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                _obj = _target.parents('tr');
                                _obj.fadeOut(500,function(){_obj.remove();});
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(msg){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return true;
            });
        break;
        
        case ($('.list_table.news_from_sources').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                switch(true){
                    case _target.hasClass('ico_to_news') :
                        if(!confirm('Вы уверены, что нужно перенести в новости эту статью?')) return false;
                        break;
                    case _target.hasClass('ico_del') :
                        break;
                    case _target.hasClass('ico_variants'):
                        _target.parents('tr').find('.addr-variants').toggleClass('shown');
                        return false;
                    default:
                        return true;
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: {ajax: true},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids !== undefined && msg.ids.length > 0){
                                    var _obj = null;
                                    for(var i=0;i<msg.ids.length;i++){
                                        _obj = jQuery('#item_'+msg.ids[i]);
                                        _obj.fadeOut(500,function(){_obj.remove();});
                                    }
                                    if(msg.new_href !== undefined) window.location.href = msg.new_href;
                                } else alert("Ошибка: " + msg.error);
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
            });
        break;
        
        case ($('.list_table.address_adding').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                switch(true){
                    case _target.hasClass('ico_add') :
                        if(!confirm('Вы уверены, что нужно добавить этот адрес?')) return false;
                        break;
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно удалить эту запись?')) return false;
                        break;
                    case _target.hasClass('ico_variants'):
                        _target.parents('tr').find('.addr-variants').toggleClass('shown');
                        return false;
                    default:
                        return true;
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: {ajax: true},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids !== undefined && msg.ids.length > 0){
                                    var _obj = null;
                                    for(var i=0;i<msg.ids.length;i++){
                                        _obj = jQuery('#item_'+msg.ids[i]);
                                        _obj.fadeOut(500,function(){_obj.remove();});
                                    }
                                } else alert("Ошибка: " + msg.error);
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
            });
        break;
        case ($('.list_table.consults-members').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                switch(true){
                    case _target.hasClass('ico_add') :
                        if(!confirm('Вы уверены, что нужно зарегистрировать этого специалиста?')) return false;
                        break;
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно удалить этого специалиста?')) return false;
                        break;
                    default:
                        return true;
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: {ajax: true},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids !== undefined && msg.ids.length > 0){
                                    var _obj = null;
                                    for(var i=0;i<msg.ids.length;i++){
                                        _obj = jQuery('#item_'+msg.ids[i]);
                                        _obj.fadeOut(500,function(){_obj.remove();});
                                    }
                                    if(msg.alert !== undefined) alert(msg.alert);
                                } else alert("Ошибка: " + msg.error);
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
            });
            break;
        case ($('.list_table.ip_visits').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                if(_target.hasClass('disabled')) return false;
                switch(true){
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно заблокировать этот IP?')) return false;
                        break;
                    case _target.hasClass('ico_restore') :
                        if(!confirm('Вы уверены, что нужно разблокировать этот IP?')) return false;
                        break;
                    default:
                        return true;
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: {ajax: true, id:_el.parent().attr('data-id'), ip:_el.parent().attr('data-ip'), block_type:_el.attr('data-type')},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids !== undefined && msg.ids.length > 0){
                                    jQuery('#item_'+msg.ids[0]).toggleClass('blocked');
                                    jQuery('#item_'+msg.ids[0]).find('ico_del').toggleClass('disabled').siblings('.ico_restore').toggleClass('disabled');
                                } else alert("Ошибка: " + msg.error);
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
            });
            break;
        case ($('.list_table.housing_estate_variants').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                if(_target.hasClass('disabled')) return false;
                
                switch(true){
                    case _target.hasClass('ico_copy') :
                        var _input_copy_to = _target.parent().siblings('#copy-to-id');
                        if(_target.hasClass('active')){
                            if(_input_copy_to.val().length == 0 || !confirm('Вы уверены, что нужно скопировать варианты?')){
                                _target.removeClass('active').parent().siblings('#copy-to-id').hide();
                                return false;
                            }
                        }else{
                            _target.addClass('active').parent().siblings('#copy-to-id').show();
                            return false;
                        }
                        var _data = {ajax: true, id_user_from:_el.attr('data-id'),id_user_to:parseInt(_el.siblings('#copy-to-id').val())};
                        _target.parents('tr').addClass('processing');
                        break;
                    case _target.hasClass('ico_unattach') :
                        if(!confirm('Вы уверены, что нужно открепить эти варианты от ЖК?')) return false;
                        var _data = {ajax: true, id_user_from:_el.attr('data-id')};
                        break;
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно УДАЛИТЬ эти варианты?')) return false;
                        var _data = {ajax: true, id_user_from:_el.attr('data-id')};
                        break;
                    default:
                        return true;
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: _data,
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                switch(true){
                                    case msg.type == 'copy': 
                                        _target.parents('tr').removeClass('processing');
                                        alert('Скопировано '+msg.objects_count+' объектов и '+msg.photos_count+' фотографий');
                                        window.location.reload();
                                        break;
                                    case msg.type == 'unattach': 
                                        alert(msg.objects_count+' вариантов данной компании откреплены от ЖК');
                                        removeTableRows(msg);
                                        break;
                                    case msg.type == 'del': 
                                        alert(msg.objects_count+' вариантов данной компании удалены из базы');
                                        removeTableRows(msg);
                                        break;
                                }
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
            });
            break;
        case ($('.list_table .mortgage-apps').length > 0):
            jQuery('.list_table').on('click','.small_icons a', function(e){
                var _el = jQuery(this);
                var _target = _el.children('span');
                if(_target.hasClass('disabled')) return false;
                
                switch(true){
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно удалить эту заявку?')) return false;
                        var _data = {ajax: true, id_user_from:_el.attr('data-id')};
                        break;
                    default:
                        return true;
                }
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: _data,
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                switch(true){
                                    case msg.type == 'del': 
                                        removeTableRows(msg);
                                        break;
                                }
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
            });
            break;
        case ($('.percent-rules').length > 0):
            jQuery('.add-rule').on('click',function(){
                if(!jQuery('#new-rule').hasClass('active')) jQuery('#new-rule').addClass('active');
                else{
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: jQuery(this).attr('data-href'),
                        data: jQuery('#new-rule').val(),
                        success: function(msg){
                            if(typeof(msg)=='object') {
                                if(msg.ok){
                                    alert("Правило успешно добавлено");
                                    jQuery('#new-rule').val("");
                                } 
                                else alert('Ошибка: '+msg.error);
                            } else alert('Ошибка!');
                        },
                        error: function(){
                            alert('Server connection error!');
                        },
                        complete: function(){
                        }
                    });
                }
                return false;
            });
            break;
        default:
            //confirm deleting in other branches
            jQuery('.list_table').click(function(e){
                var _target = jQuery(e.target);
                if(_target.is('a') && _target.children('span')) _target = _target.children('span');
                switch(true){
                    case _target.hasClass('ico_del') :
                        if(!confirm('Вы уверены, что нужно удалить этот объект?')) return false;
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', url: _target.parent().attr('href'),
                            data: {ajax: true},
                            success: function(msg){
                                if(typeof(msg)=='object') {
                                    if(msg.ok) {
                                        if(msg.ids.length){
                                            var _obj = null;
                                            for(var i=0;i<msg.ids.length;i++){
                                                _obj = jQuery('#item_'+msg.ids[i]);
                                                _obj.fadeOut(500,function(){_obj.remove();});
                                            }
                                        } else alert('Ни один элемент не удален.');
                                    } else alert('Ошибка: '+msg.error);
                                } else alert('Ошибка!');
                            },
                            error: function(){
                                alert('Server connection error!');
                            },
                            complete: function(){
                            }
                        });
                        return false;
                    break;
                    case (_target.hasClass('ico_remoderate') && jQuery('.list_table').hasClass('apps')):
                        if(!confirm('Вы уверены, что нужно вернуть этот объект на модерацию?')) return false;
                        startPendingAction(_target.parent().attr('href'), removeTableRows);
                        return false;
                        break;
                    case _target.hasClass('ico_to_called'):
                        if(!confirm('Вы уверены, что нужно убрать эту заявку?')) return false;
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', url: _target.parent().attr('href'),
                            data: {ajax: true},
                            success: function(msg){
                                if(typeof(msg)=='object') {
                                    if(msg.ok) {
                                        if(msg.ids.length){
                                            var _obj = null;
                                            for(var i=0;i<msg.ids.length;i++){
                                                _obj = jQuery('#item_'+msg.ids[i]);
                                                _obj.fadeOut(500,function(){_obj.remove();});
                                            }
                                        } else alert('Ни один элемент не удален.');
                                    } else alert('Ошибка: '+msg.error);
                                } else alert('Ошибка!');
                            },
                            error: function(){
                                alert('Server connection error!');
                            },
                            complete: function(){
                            }
                        });
                        return false;
                        break;
                    //для списка агентств - если хотим увидеть распределение по типам сделок
                    case _target.hasClass('ico_refresh') || _target.hasClass('ico_turnoff'):
                        //startPendingAction(_target.parent().attr('href'));
                        if(_target.data('confirm')!==undefined && _target.data('confirm').length>0)
                            if(!confirm(_target.data('confirm'))) return false;
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', url: _target.parent().attr('href'),
                            data: {ajax: true},
                            success: function(msg){
                                if(typeof(msg)=='object') {
                                    if(msg.ok) {
                                        if(msg.field_num!==undefined){
                                            if(typeof(msg.field_update)){
                                                
                                                if(_target.attr('data-target-elem')!==undefined){
                                                    _field = _target.parents('tr').find(_target.attr('data-target-elem'));
                                                    _field.html("");
                                                    if(msg.field_update.build!==undefined || msg.field_update.build!==undefined || msg.field_update.build!==undefined || msg.field_update.build!==undefined){
                                                        _field.parents('td').addClass('td-tall');
                                                        if(_field.parents('tr').children('.small_icons.ac').find('.ico_details')==undefined)
                                                        _field.parents('tr').children('.small_icons.ac').append('<a href="/admin/access/agencies/details/4966/" title="Подробности"><span class="ico_details">Подробности</span></a>');
                                                    }
                                                        
                                                } 
                                                else _field = _target.parents('tr').children('td').eq(msg.field_num);
                                                
                                                if(_field.children('.build').length == 0) _field.append('<span class="obj-line build"><span>стройка: </span><i>0</i></span>');
                                                if(_field.children('.live').length == 0) _field.append('<span class="obj-line live"><span>жилая: </span><i>0</i></span>');
                                                if(_field.children('.commercial').length == 0) _field.append('<span class="obj-line commercial"><span>коммерческая: </span><i>0</i></span>');
                                                if(_field.children('.country').length == 0) _field.append('<span class="obj-line country"><span>загородная: </span><i>0</i></span>');
                                                
                                                if(msg.field_update.build!==undefined) _field.children('.build').children('i').html(msg.field_update.build);
                                                if(msg.field_update.live!==undefined) _field.children('.live').children('i').html(msg.field_update.live);
                                                if(msg.field_update.commercial!==undefined) _field.children('.commercial').children('i').html(msg.field_update.commercial);
                                                if(msg.field_update.country!==undefined) _field.children('.country').children('i').html(msg.field_update.country);
                                            }
                                        } 
                                    } else alert('Ошибка: '+msg.error);
                                } else alert('Ошибка!');
                            },
                            error: function(){
                                alert('Server connection error!');
                            },
                            complete: function(){
                            }
                        });
                        return false;
                    break;
                    case _target.hasClass('ico_details'):
                        if(_target.data('confirm')!==undefined && _target.data('confirm').length>0)
                            if(!confirm(_target.data('confirm'))) return false;
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', url: _target.parent().attr('href'),
                            data: {ajax: true},
                            success: function(msg){
                                if(typeof(msg)=='object') {
                                    if(msg.ok) {
                                        if(msg.field_num!==undefined){
                                            if(typeof(msg.field_update)){
                                                
                                                if(_target.attr('data-target-elem')!==undefined){
                                                    _field = _target.parents('tr').find(_target.attr('data-target-elem'));
                                                    _field.html("");
                                                } 
                                                else _field = _target.parents('tr').children('td').eq(msg.field_num);
                                                
                                                if(_field.children('.build').length == 0) _field.append('<span class="obj-line build"><span>стройка: </span><i>0</i> / <i>0</i></span>');
                                                if(_field.children('.live.sell').length == 0) _field.append('<span class="obj-line live sell"><span>жилая продажа: </span><i>0</i> / <i>0</i></span>');
                                                if(_field.children('.live.rent').length == 0) _field.append('<span class="obj-line live rent"><span>жилая аренда: </span><i>0</i> / <i>0</i></span>');
                                                if(_field.children('.commercial.sell').length == 0) _field.append('<span class="obj-line commercial sell"><span>комм. продажа: </span><i>0</i> / <i>0</i></span>');
                                                if(_field.children('.commercial.rent').length == 0) _field.append('<span class="obj-line commercial rent"><span>комм. аренда: </span><i>0</i> / <i>0</i></span>');
                                                if(_field.children('.country.sell').length == 0) _field.append('<span class="obj-line country sell"><span>заг. продажа: </span><i>0</i> / <i>0</i></span>');
                                                if(_field.children('.country.rent').length == 0) _field.append('<span class="obj-line country rent"><span>заг. аренда: </span><i>0</i> / <i>0</i></span>');
                                                
                                                if(msg.field_update.build_sell!==undefined) _field.children('.build').children('i').eq(0).html(msg.field_update.build_sell).siblings('i').html(msg.field_update.build_sell_limit);
                                                if(msg.field_update.live_sell!==undefined) _field.children('.live.sell').children('i').eq(0).html(msg.field_update.live_sell).siblings('i').html(msg.field_update.live_sell_limit);
                                                if(msg.field_update.live_rent!==undefined) _field.children('.live.rent').children('i').eq(0).html(msg.field_update.live_rent).siblings('i').html(msg.field_update.live_rent_limit);
                                                if(msg.field_update.commercial_sell!==undefined) _field.children('.commercial.sell').children('i').eq(0).html(msg.field_update.commercial_sell).siblings('i').html(msg.field_update.commercial_sell_limit);
                                                if(msg.field_update.commercial_rent!==undefined) _field.children('.commercial.rent').children('i').eq(0).html(msg.field_update.commercial_rent).siblings('i').html(msg.field_update.commercial_rent_limit);
                                                if(msg.field_update.country_sell!==undefined) _field.children('.country.sell').children('i').eq(0).html(msg.field_update.country_sell).siblings('i').html(msg.field_update.country_sell_limit);
                                                if(msg.field_update.country_rent!==undefined) _field.children('.country.rent').children('i').eq(0).html(msg.field_update.country_rent).siblings('i').html(msg.field_update.country_rent_limit);
                                            }
                                        } 
                                    } else alert('Ошибка: '+msg.error);
                                } else alert('Ошибка!');
                            },
                            error: function(){
                                alert('Server connection error!');
                            },
                            complete: function(){
                            }
                        });
                        return false;
                    break;
                    case _target.hasClass('ico_variants'):
                        _target.parents('tr').find('.addr-variants').toggleClass('shown');
                        return false;
                    break;
                    case _target.hasClass('ico_load'):
                        if(_target.data('confirm')!==undefined && _target.data('confirm').length>0)
                            if(!confirm(_target.data('confirm'))) return false;
                        jQuery.ajax({
                            type: "POST", async: true,
                            dataType: 'json', url: _target.parent().attr('href'),
                            data: {ajax: true},
                            success: function(msg){
                                if(typeof(msg)=='object') {
                                    alert(_target.data('success'));
                                    window.location.reload();
                                } else alert('Ошибка запуска. Обратитесь в тех. отдел');
                            },
                            error: function(){
                                alert('Server connection error!');
                            },
                            complete: function(){
                            }
                        });
                        return false;
                    break;
                }
                return true;
            });
        break;
    }
    
    //список чекбоксов с картинками (заявки на ипотеку)
    jQuery('.checkbox-set .radio-block .checkbox').on('click',function(e){
        if(jQuery(e.target).is('input')) return true;
        jQuery(this).find('input').click();
    });
    jQuery('.checkbox-set .radio-block input.checkbox').on('click',function(e){
        var _checkboxes_selected = [];
        var _checkbox_set = jQuery(this).parents('.checkbox-set');
        var _name = _checkbox_set.attr('class').replace('checkbox-set','').trim();
        _checkbox_set.find('input:checked').each(function(){
            _checkboxes_selected.push(jQuery(this).attr('data-id'));
        });
        jQuery('input[name="' + _name + '"]').val(_checkboxes_selected.join(','));
        return true;
    });
    //
    
    //аяксовый radio
    jQuery('.ajax-radio input').on('click',function(){
        if(!confirm('Изменить статус ответа на заявку?')) return false;
        var _id = window.location.href.match(/[0-9]+\/$/);
        if(_id == null) return false;
        _id = parseInt(_id.pop());
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: jQuery(this).parents('.ajax-radio').attr('data-url'),
            data: {ajax: true, id:_id,id_bank: jQuery(this).parents('tr').attr('id').replace(/[^0-9]/g,''), status:jQuery(this).val()},
            success: function(msg){
                if(typeof(msg)=='object') {
                    if(msg.ok) {
                        alert('Статус ответа заявки успешно изменен');
                    } else alert('Ошибка: '+msg.error);
                } else alert('Ошибка!');
            },
            error: function(){
                alert('Server connection error!');
            },
            complete: function(){
            }
        });
    });
    jQuery('.ajax-radio > span').on('click',function(e){
        if(jQuery(e.target).is('input')) return true;
        jQuery(this).children('input').click();
    });
    //
    
    jQuery('.mortgage-apps .vertical-block .full-comment').on('click',function(){
        jQuery(this).toggleClass('active');
    });
    
    jQuery('.list_table table th').on('click',function(){
        var _sort_field = jQuery(this).attr('data-sort-field');
        
        if(_sort_field == undefined) return false;
        
        jQuery(this).addClass('sort').siblings().removeClass('sort').removeClass('asc').removeClass('desc');
        if(jQuery(this).hasClass('desc')){
            jQuery(this).removeClass('desc').addClass('asc');
        } 
        else{
            jQuery(this).removeClass('asc').addClass('desc');
        } 
        
        _sort_field += '>' + (jQuery(this).hasClass('asc')?'asc':'desc');
        
        var _get_part = window.location.href.split('?');
        if(_get_part[1] !== undefined) _get_part = _get_part.join('?') + '&sortby=' + _sort_field;
        else _get_part = _get_part[0] + '?sortby=' + _sort_field;
        
        window.location.href = _get_part;
    });
    
    //radio для показа/скрытия поля выбора в следующем поле
    //radio p_field_show_'field_name' задействует p_field_'field_name'
    jQuery('p[id*="_show_"]').each(function(){
        var _target_id = jQuery(this).attr('id').replace('_show','');
        if(jQuery('#' + _target_id).length > 0){
            jQuery(this).find('input').on('click',function(){
                if(jQuery(this).is(":checked") && jQuery(this).val() == 1) jQuery('#' + _target_id).show();
                else jQuery('#' + _target_id).hide().find('input:checked').attr('checked',false).siblings("input[name='" + _target_id.replace('p_field_','') + "']").val("");
            });
            if(jQuery(this).find("input:checked[value='2']").length > 0) jQuery('#' + _target_id).hide();
        }
    });
    
    clickPrice();
    jQuery("#price-on-period, #wanted-price").on('change, keyup',clickPrice);
    
    jQuery('.news-from-sources-item .show-content').on('click',function(){
        jQuery(this).toggleClass("shown");
    });
});
//расчет стоимости клика
function clickPrice(){
    var _price = jQuery('#price-on-period').val();
    var _wanted_price = jQuery('#wanted-price').val();
    //расчет стоимости за 1 клик
    if(_price > 0){
        var _table = jQuery('#result_info_stats > table');
        if(_table.length > 0){
            var _date_diff = 30;
            var _date_interval = Math.floor(_table.data('date-interval'));
            
            _table.find('td.clicks').each(function(){
                var _this = jQuery(this);
                var _clicks = Math.floor(_this.text());
                if(_clicks > 0) var _click_price = Math.floor((_price/_date_diff)/(_clicks));
                else  var _click_price = 0;
                _this.next('td').text(_click_price).addClass('green-light');
                setTimeout(function(){
                  _this.next('td').removeClass('green-light');
                }, 1000);                
            })
            var _total_clicks = _table.find('th.total-clicks');
            var _total_click_price  =  Math.floor(((_price/_date_diff)*_date_interval)/(Math.floor(_total_clicks.text())));
            _total_clicks.next('th').text(_total_click_price);
        }
    }
    //расчет остаточного кол-ва кликов
    if(_wanted_price > 0){
        _n = parseInt(((_price / _wanted_price)  - parseInt(jQuery('#clicks-last-month').text()) - parseInt(jQuery('#advert-clicks span').text())*0.9));
        jQuery('#clicks-to-the-end-total span').text(_n);
        jQuery('#clicks-to-the-end-total span').addClass('green-light');
        setTimeout(function(){
          jQuery('#clicks-to-the-end-total span').removeClass('green-light');
        }, 1000);                
    }
    
}                
