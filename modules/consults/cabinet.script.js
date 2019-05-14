jQuery(document).ready(function(){
    
    function show_answer_form(e){
        var _this = jQuery(e);
        var _this_row = _this.parents('.adv-table-row');
        _this_row.addClass('answering');
        var _question_td = _this_row.children('.question-body');
        //раскрываем вопрос
        var _info_block = _question_td.children('.info-block');
        if(!_info_block.children('.question-text-block').hasClass('active')) _question_td.children('.question-text').click();
        //раскрываем форму
        _info_block.addClass('active');
        var _question_form = _info_block.children('.question-answer-form');
        _question_form.addClass('active');
        
        //CKEdtior + корректируем высоту строки
        if(_info_block.find(".question-answer-form .edit-block.active textarea[class^='CKEdit']").length > 0){
            _info_block.find("textarea[class^='CKEdit']").each(function(){
                var el = $(this);
                var conf = {toolbar: 'VerySmall'};
                /*
                if(CKEDITOR.instances[el.attr('id')] !== undefined){
                    delete CKEDITOR.instances[el.attr('id')];
                    _this_row.find('#' + el.attr('id')).remove();
                } 
                */
                if(CKEDITOR.instances[el.attr('id')] == undefined){
                    CKEDITOR.replace(el.attr('id'),conf);
                    CKEDITOR.instances[el.attr('id')].on("instanceReady", function(event){
                        while(_this_row.offset().top + _this_row.height() < _info_block.offset().top + _info_block.height()){
                            _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 10) + "px");
                        }
                        _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 20) + "px");
                    });
                }else{
                    while(_this_row.offset().top + _this_row.height() < _info_block.offset().top + _info_block.height()){
                        _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 10) + "px");
                    }
                    _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 20) + "px");
                }
            });
        }else{
            while(_this_row.offset().top + _this_row.height() < _info_block.offset().top + _info_block.height()){
                _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 10) + "px");
            }
            _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 20) + "px");
        }
        
        //если есть кнопка Ответить, прячем
        if(_this_row.children('.answers').children('.blue').length > 0) _this_row.children('.answers').children('.blue').addClass('hidden');
    }
    
    function hide_answer_form(){
        var _form = jQuery('.question-answer-form.active');
        _form.removeClass('active');
        var _this_row = _form.parents('tr');
        _this_row.removeAttr('style').removeClass('answering');
        var _info_block = _this_row.children('.question-body').children('.info-block')
        //при необходимости, сворачиваем вопрос
        if(_info_block.children('.question-text-block').hasClass('active')) _this_row.find('.question-text').click();
        _info_block.removeClass('active');
        //если есть кнопка Ответить, показываем
        if(_this_row.children('.answers').children('.blue').length > 0) _this_row.children('.answers').children('.blue').removeClass('hidden');
    }
    //сворачиваем или разворачиваем блок с комментариями
    jQuery(document).on('click','.adv-table-row .question-text',function(){
        //если уже развернуто, сворачиваем
        var _question_block = jQuery(this).siblings('.info-block').children('.question-text-block');
        //если это не ответ на вопрос, щелкаем блок
        if(_question_block.siblings('.active').length == 0 ) _question_block.parents('.info-block').toggleClass('active');
        _question_block.toggleClass('active');
        //меняем надпись на кнопке
        if(_question_block.hasClass('active')) jQuery(this).addClass('hide-question').html("Скрыть вопрос");
        else jQuery(this).removeClass('hide-question').html("Показать вопрос");
        
        //при необходимости, корректируем высоту строки по содержимому (+20 - учитываем кнопки "Сохранить" и "Удалить" при hover)
        var _this_row = jQuery(this).parents('tr');
        //если это не ответ на вопрос, корректируем высоту
        if(!_this_row.hasClass('answering')){
            var _add_height = _question_block.offset().top + _question_block.height() - _this_row.offset().top - _this_row.height();
            _this_row.height(_this_row.height()+parseInt(_add_height)+20);
            _question_block.siblings('.question-answer-form').css('margin-top',"10px");
        }else{
            _this_row.height(_this_row.height()-parseInt(_question_block.height()));
            _question_block.siblings('.question-answer-form').css('margin-top',"10px");
        }
    });
    //по кнопке ответить/вы ответили разворачиваем форму/ответ и вопрос
    jQuery(document).on('click','.adv-table-row .answers .blue, .adv-table-row .answers .you-answered, .adv-table-row .answers .your-draft',function(){
        //если это кнопка ответить, не даем нажать второй раз;
        //если это кнопка Вы ответили, прячем форму
        if(jQuery(this).parents('tr').hasClass('answering') && (jQuery(this).hasClass('blue') || jQuery(this).hasClass('you-answered') || jQuery(this).hasClass('your-draft'))){
            hide_answer_form();
            return false;
        }
        show_answer_form(jQuery(this));
    });
    
    //отмена редактирования ответа
    jQuery(document).on('click','.adv-table-row .question-answer-form .cancel-answer',function(){
        hide_answer_form();
    });
    //сохраняем ответ (черновик или отправляем на модерацию)
    jQuery(document).on('click','.adv-table-row .question-answer-form .save-answer, .adv-table-row .question-answer-form .send-answer',function(){
        var _this_id = jQuery(this).parents('tr').attr('id');
        var _url = window.location.href + "add_answer/";
        _this = jQuery(this);
        _ckeditor_instance = 'ckedit_notes_' + _this_id;
        _this.siblings('textarea').val(CKEDITOR.instances[_ckeditor_instance].getData());
        if(_this.siblings('textarea').val() == ''){
            _this.parents('.question-answer-form').attr('data-note',"Ответ не может быть пустым").addClass('note error');
            return false;
        } 
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url,
            data: {ajax: true,
                   id:_this_id,
                   answer:_this.siblings('textarea').val(),
                   answer_id:_this.siblings('textarea').attr('data-id'),
                   id_parent:_this.parents('tr').attr('id'),
                   is_draft:_this.hasClass('save-answer')},
            success: function(msg){
                if(msg.ok){
                    //если добавили черновик, меняем кнопку справа на "Черновик"
                    var _answers_block = _this.parents('tr').children('.answers');
                    if(_answers_block.children('.you-answered').length > 0) _answers_block.children('.you-answered').remove();
                    //отмечаем что сохранено или отправлено
                    if(_this.hasClass('send-answer')){
                        alert('Ваш ответ отправлен на модерацию');
                        _this.siblings('textarea').prop("disabled",true);
                        _this.siblings('.save-answer').remove();
                        _this.siblings('.cancel-answer').html("Свернуть");
                        _this.parents('tr').children('.answers').children('button').remove();
                        
                        //если это редактирование опубликованного ответа, убираем форму и кнопку "редактировать ответ"
                        _this.parent().siblings('.edit-answer').remove();
                        _this.parent().siblings('.question-answer-text').html(CKEDITOR.instances[_ckeditor_instance].getData());
                        _this.siblings('.cancel-answer').siblings(':not(.send-answer)').remove();
                        
                        //корректируем высоту строки
                        var _this_row = _this.parents('.adv-table-row');
                        var _question_td = _this_row.children('.question-body');
                        var _info_block = _question_td.children('.info-block');
                        while(_this_row.offset().top + _this_row.height() > _info_block.offset().top + _info_block.height() + 50){
                            _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) - 10) + "px");
                        }
                        _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) - 20) + "px");
        
                        _this.remove();
                        
                        if(_answers_block.children('.your-draft').length > 0) _answers_block.children('.your-draft').removeClass('your-draft').addClass('you-answered').html("Модерация");
                        else _answers_block.append('<span class="your-draft control">Модерация</span>');
                    }else{
                        _this.parents('.question-answer-form').attr('data-note',"Сохранено").addClass('note saved');
                        if(_answers_block.children('button').length > 0) _answers_block.children('button').remove();
                        _answers_block.children('.answers-amount').after("<span class=\"your-draft control\">Черновик</span>");
                        _answers_block.removeClass('can-answer');
                    } 
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
                return false;
            },
            complete: function(){
            }
        });
    });
    
    jQuery(document).on('click','.edit-answer',function(){
        var _this_row = jQuery(this).parents('.adv-table-row');
        var _question_td = _this_row.children('.question-body');
        var _info_block = _question_td.children('.info-block');
        
        if(jQuery(this).siblings('.edit-block').hasClass('hidden')){
            jQuery(this).siblings('.edit-block').removeClass('hidden');
            while(_this_row.offset().top + _this_row.height() < _info_block.offset().top + _info_block.height()){
                _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 10) + "px");
            }
            _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) + 20) + "px");
        }else{
            jQuery(this).siblings('.edit-block').addClass('hidden');
            while(_this_row.offset().top + _this_row.height() > _info_block.offset().top + _info_block.height() + 50){
                _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) - 10) + "px");
            }
            _this_row.css('height',(parseInt(_this_row.css('height').replace(/[^0-9]/g,'')) - 20) + "px");
        }
    });
    
    //убираем флажок "сохранено" при редактировании ответа
    jQuery(document).on('keyup','.adv-table-row .question-answer-form textarea',function(){
        jQuery(this).parent().removeClass('note saved').attr("data-note","");
        jQuery(this).parent().removeClass('note error').attr("data-note","");
    });
});