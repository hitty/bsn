jQuery(document).ready(function(){
    if(jQuery('.datetimepicker').length>0){
        jQuery('.datetimepicker').datetimepicker();
    }
    //fileuploader init
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({'queueSizeLimit':200});
    }    
    if(jQuery.isFunction(jQuery('input[name=start]').datetimepicker)){
        jQuery('input[name=start]').datetimepicker({
          datepicker:false,
          format:'H:i',
            allowTimes:[
              '13:00', '13:30',
              '14:00', '14:30',
              '15:00', '15:30',
              '16:00', '16:30',
              '17:00', '17:30',
              '18:00', '18:30'
             ],
          onChangeDateTime:function(dp,$input){
              $input.attr('value',$input.val())
              changeTime($input.parents(),'on');
          }
        });
    }
    
    
    jQuery('.datetimepicker').on('change',function(){
        changeTime(jQuery(this).parents('.fieldwrapper'),'on');    
    })
    jQuery('.p_field_open_hours').each(function(){
        var _this = jQuery(this);
        _this.children('span').children('.switcher').on('click',function(){
            jQuery(this).toggleClass('checked');
            if(jQuery(this).hasClass('checked')) {
                changeTime(jQuery(this).parents('.fieldwrapper'),'on');
                jQuery(this).siblings('input').attr('disabled',false).addClass('active-date');
            } else {
                jQuery(this).siblings('input').attr('disabled','disabled').removeClass('active-date');
                changeTime(jQuery(this).parents('.fieldwrapper'),'off');
            }
        })
    })
    
    function changeTime(_th,_action){
        var _start = _th.children('input[name=start]').val();
        var _day = _th.children('.switcher').data('day');
        var _data = {start:_start, day:_day, action:_action};
        getPending('/admin/content/news/time/', _data);
    }
    
    
    //promo blocks
    _promo_type = jQuery('input[name="promo"]:checked').val();
    if( jQuery('.promo-wrapper.template').length > 0 || jQuery('.test-wrapper.template').length > 0){
        var _promo_wrapper = jQuery('.' + ( _promo_type == 1 ? 'promo' : 'test' )+ '-wrapper.template');
        var _id = parseInt(_promo_wrapper.data('id'));
       
        jQuery('#btn_promo-add-button').on('click', function(){
            if(_id == 0) return false;
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/content/articles/' + ( _promo_type == 1 ? 'promo' : 'test')+ '/add/',
                data: {ajax: true, 
                       id: _promo_wrapper.data('id')
                },
                success: function(msg){ 
                    var _html = '<div class="' + ( _promo_type == 1 ? 'promo' : 'test' ) + '-wrapper" data-id="' + msg.id + '">' + 
                                    _promo_wrapper.html() + 
                                '</div>';
                    if( jQuery('#p_field_promo-add-button .' + ( _promo_type == 1 ? 'promo' : 'test' ) + '-wrapper').length == 0) jQuery('#p_field_promo-add-button').prepend(_html);
                    else jQuery( _html ).insertAfter( jQuery( "#p_field_promo-add-button ." + ( _promo_type == 1 ? 'promo' : 'test' ) + "-wrapper" ).last() );                    
                    
                    initPromoBlock( msg.id, 'add', _promo_type == 1 ? 'promo' : 'test' );
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            }); 
            return false;
        })
        if(_id == 0) jQuery('#btn_promo-add-button').text('Для добавления блоков сохраните статью').addClass('disabled');

        jQuery(document).on('click', '.delete-item', function(){ 
            getPending('/admin/content/articles/' + ( jQuery(this).parent().hasClass('test-results-wrapper') ? 'test/results' : ( _promo_type == 1 ? 'promo' : 'test' ) ) + '/delete/', {id : jQuery(this).parent().data('id') } );
            jQuery(this).parent().fadeOut(100)
        })
            
        jQuery(document).on('click', '.refresh-item', function(e, _action){
            var _this = jQuery(this);
            var _wrap = _this.parent();
            var _preview = jQuery('.preview', _wrap);     
            f_values = {};
            f_values['id'] = jQuery(this).parent().data('id');
            if( _promo_type == 1 || _wrap.hasClass('test-results-wrapper')) f_values['content'] = CKEDITOR.instances['textarea_' + f_values['id']].getData();
            _wrap.find('input, textarea').each(function(){
                var _this = jQuery(this);
                var _name = _this.attr('name');
                if( _name != undefined ){ 
                    var _type = _this.attr('type');
                    _value = _type == 'radio' ? jQuery('input[name=' + _name + ']:checked', _wrap).val() : _this.val();
                    if( _promo_type == 1 || _wrap.hasClass('test-results-wrapper') ) f_values[_name.replace(/_\d+$/,'')] = _value;
                    else f_values[_name] = _value;
                }
            });
            _this.addClass('active');                              ;
            getPending('/admin/content/articles/' + ( _wrap.hasClass('test-results-wrapper') ? 'test/results' : ( _promo_type == 1 ? 'promo' : 'test' ) ) + '/save/', f_values );
            setTimeout(function(){
                if( _promo_type == 1 ) getPendingContent('#preview-' + _wrap.data('id') , '/articles/promo/' + _wrap.data('id') + '/');    
            }, 50) 
            setTimeout(function(){
                _this.removeClass('active');     
            }, 1000) 
        })

        // только test-wrapper
        jQuery(document).on('click', '.test-wrapper .add', function(){
            var _test_wrapper = jQuery(this).closest('.test-wrapper');
            _this_button = jQuery(this);
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/content/articles/test/questions/add/',
                data: {ajax: true, 
                       id: _test_wrapper.data('id')
                },
                success: function(msg){ 
                    var _html = msg.html;
                    jQuery('.questions', _test_wrapper).append(_html);
                                        
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            }); 
            return false;
        })    
        jQuery(document).on('click', '.delete-row', function(){ 
            getPending('/admin/content/articles/test/questions/delete/', {id : jQuery(this).parent().data('id') } );
            jQuery(this).parent().fadeOut(100)
        })
        //результаты тестов
            // только test-wrapper
        jQuery(document).on('click', '.test-results-add', function(){
            var _test_wrapper = jQuery(this).closest('.test-wrapper');
            _this_button_results = jQuery(this);
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/content/articles/test/results/add/',
                data: {ajax: true, 
                       id: jQuery('#tags_list').attr('data-id_object')
                },
                success: function(msg){ 
                    var _html = msg.html;
                    jQuery('<div>'+_html+'</div>').insertBefore( _this_button_results);
                    initPromoBlock( msg.id, 'add', 'test-results' );                    
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            }); 
            return false;
        })  

    
        managePromoVisibility( jQuery('input[name="promo"]:checked'))  ;
        jQuery('input[name="promo"]').change(function(){
            managePromoVisibility( jQuery('input[name="promo"]:checked'));
        })
    }
    
       
    function managePromoVisibility(_el){
        _promo_type = _el.val();
        jQuery('.title_promo_blocks,.title_test,.title_test_results,.title_promo_link,#p_field_promo_link,#p_field_promo_link_text,p_field_promo_link_undertext,#p_field_content_short,#p_field_test_partner_text,#p_field_test_gradient,#p_field_test_steps').hide();
        if( _promo_type == 1 || _promo_type == 3){
            if(_promo_type == 1) jQuery('#p_field_promo_link,#p_field_promo_link_text,#p_field_promo_link_undertext,#p_field_promo-add-button').show(0);
            else if(_promo_type == 3) {
                jQuery('.test-results-list,#p_field_content_short,#p_field_promo_link,#p_field_promo_link_text,#p_field_promo_link_undertext,.title_promo_link,#p_field_test_partner_text,#p_field_test_gradient,#p_field_test_steps').show(0);
                jQuery('#p_field_content_short label').text('Вступительный текст теста');
            }
            jQuery('#p_field_promo-add-button').show(0);
            jQuery('#p_field_content').hide(0);
            
            if( _promo_type == 1) jQuery('.title_promo_blocks,.title_promo_link').show();
            else jQuery('.title_test,.title_test_results').show();
        } else {
            jQuery('#p_field_promo-add-button,#p_field_promo_link,#p_field_promo_link_undertext,.title_promo_link,.test-results-list').hide(0);
            jQuery('#p_field_content_short,#p_field_content').show(0);
            jQuery('#p_field_content_short label').text('Краткий анонс');
        }
        
        if( _promo_type != 2){
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/content/articles/' + ( _promo_type == 1 ? 'promo' : 'test')+ '/list/',
                data: {ajax: true, 
                       id: _promo_wrapper.data('id')
                },
                success: function(msg){ 
                    jQuery('.promo-wrapper,.test-wrapper').remove();
                    jQuery('#p_field_promo-add-button').prepend(msg.html);
                    jQuery('.' + ( _promo_type == 1 ? 'promo' : 'test' ) + '-wrapper', jQuery('#p_field_promo-add-button')).each(function(){
                        initPromoBlock( jQuery(this).data('id'), 'list', _promo_type == 1 ? 'promo' : 'test' );    
                    })
                    
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            });             
            if( _promo_type == 3){
                jQuery.ajax({
                    type: "POST", dataType: 'json',
                    async: true, cache: false,
                    url: '/admin/content/articles/test_results/list/',
                    data: {ajax: true, 
                           id: _promo_wrapper.data('id')
                    },
                    success: function(msg){ 
                        
                        jQuery( '<div class="test-results-list">' + msg.html + '</div>').insertAfter( jQuery('.title_row.title_test_results') );
                        jQuery( '.test-results-wrapper' ).each( function() {
                            initPromoBlock( jQuery(this).data('id'), 'list' , 'test-results' );    
                        })
                        
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        console.log('Запрос не выполнен!');
                    }
                }); 
            }            

        }
    }        
    
    function initPromoBlock(id, _action, _type){
        console.log(_type)
        var _current_wrapper = jQuery('.' + _type + '-wrapper[data-id=' + id + ']');
        
        if( jQuery('.preview', _current_wrapper).length > 0) jQuery('.preview', _current_wrapper).attr('id', 'preview-' + id);
        if( jQuery('input[name=file_upload]', _current_wrapper).length > 0) jQuery('input[name=file_upload]', _current_wrapper).attr('id', 'file_upload_' + id).attr('data-id', id).uploadifive({'queueSizeLimit':1})
        
        if( jQuery('textarea', _current_wrapper).length > 0) {
            jQuery('textarea', _current_wrapper).attr('id', 'textarea_' + id).attr('name', 'textarea_name_' + id)
            conf = {toolbar: 'Promo'};
            if(CKEDITOR.instances['textarea_' + id]) CKEDITOR.remove(CKEDITOR.instances['textarea_' + id]);
            CKEDITOR.replace('textarea_' + id,conf);
            var _current_ckeditor = CKEDITOR.instances['textarea_' + id]
        }
        
        if( _action == 'list' && _promo_type == 1)  getPendingContent('#preview-' + id , '/articles/promo/' + id + '/');   
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
//longread blocks
    if( jQuery('.longread-wrapper.template').length > 0){
        var _longread_wrapper = jQuery('.longread-wrapper.template');
        var _id = parseInt(_longread_wrapper.data('id'));
       
        jQuery('#btn_longread-add-button').on('click', function(){
            if(_id == 0) return false;
            jQuery.ajax({
                type: "POST", dataType: 'json',
                async: true, cache: false,
                url: '/admin/content/longread/advert/add/',
                data: {ajax: true, 
                       id: _longread_wrapper.data('id')
                },
                success: function(msg){ 
                    var _html = '<div class="longread-wrapper" data-id="' + msg.id + '">' + 
                                    _longread_wrapper.html() + 
                                '</div>';
                    if( jQuery('#p_field_longread-add-button .longread-wrapper').length == 0) jQuery('#p_field_longread-add-button').prepend(_html);
                    else jQuery( _html ).insertAfter( jQuery( "#p_field_longread-add-button .longread-wrapper" ).last() );                    
                    
                    initlongreadBlock( msg.id, 'add' );
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('Запрос не выполнен!');
                }
            }); 
            return false;
        })
        if(_id == 0) jQuery('#btn_longread-add-button').text('Для добавления блоков сохраните статью').addClass('disabled');

        jQuery(document).on('click', '.delete-item', function(){ 
            getPending('/admin/content/longread/advert/delete/', {id : jQuery(this).parent().data('id') } );
            jQuery(this).parent().fadeOut(100)
        })
            
        jQuery(document).on('click', '.refresh-item', function(e, _action){
            var _this = jQuery(this);
            var _wrap = _this.parent();
            var _preview = jQuery('.preview', _wrap);     
            f_values = {};
            f_values['id'] = jQuery(this).parent().data('id');
            _wrap.find('input,textarea').each(function(){
                var _this = jQuery(this);
                var _name = _this.attr('name');
                if( _name != undefined ){ 
                    var _type = _this.attr('type');
                    _value = _this.val();
                    f_values[_name] = _value;
                }
            });
            _this.addClass('active');
            getPending('/admin/content/longread/advert/save/', f_values );
            setTimeout(function(){
                _this.removeClass('active');     
            }, 1000) 
        })
              
        jQuery.ajax({
            type: "POST", dataType: 'json',
            async: true, cache: false,
            url: '/admin/content/longread/advert/list/',
            data: {ajax: true, 
                   id: _longread_wrapper.data('id')
            },
            success: function(msg){ 
                jQuery('.longread-wrapper,.test-wrapper').remove();
                jQuery('#p_field_longread-add-button').prepend(msg.html);
                jQuery('.longread-wrapper', jQuery('#p_field_longread-add-button')).each(function(){
                    initlongreadBlock( jQuery(this).data('id'), 'list' );    
                })
                
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log('Запрос не выполнен!');
            }
        });             
        
    
        function initlongreadBlock(id, _action){
            var _current_wrapper = jQuery('.longread-wrapper[data-id=' + id + ']');
            if( jQuery('input[name=file_upload]', _current_wrapper).length > 0) jQuery('input[name=file_upload]', _current_wrapper).attr('id', 'file_upload_' + id).attr('data-id', id).uploadifive({'queueSizeLimit':1})
        }    
    }    
    
});