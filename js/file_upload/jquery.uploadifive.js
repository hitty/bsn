var isSortable = false;
var objectType = '';
var _sessId = '';
var _idObject = 0;
var _qu = [];
var xhr = [];
_settings = '';
;(function($) {

    var methods = {

        init : function(options) {
            
            return this.each(function() {
                var $this = $(this);
                _idObject = $this.attr('data-id');
                _sessId = $this.attr('data-session-id');    

                // Create a reference to the jQuery DOM object
                
                    $this.data('uploadifive', {
                        inputs     : {}, // The object that contains all the file inputs
                        inputCount : 0,  // The total number of file inputs created
                        fileID     : 0,
                        queue      : {
                                         count      : 0, // Total number of files in the queue
                                         selected   : 0, // Number of files selected in the last select operation
                                         replaced   : 0, // Number of files replaced in the last select operation
                                         errors     : 0, // Number of files that returned an error in the last select operation
                                         queued     : 0, // Number of files added to the queue in the last select operation
                                         cancelled  : 0  // Total number of files that have been cancelled or removed from the queue
                                     },
                        uploads    : {
                                         current    : 0, // Number of files currently being uploaded
                                         attempts   : 1, // Number of file uploads attempted in the last upload operation
                                         successful : 0, // Number of files successfully uploaded in the last upload operation
                                         errors     : 0, // Number of files returning errors in the last upload operation
                                         count      : 0  // Total number of files uploaded successfully
                                     }
                    });
                var $data = $this.data('uploadifive');

                // Set the default options
                var settings = $data.settings = $.extend({
                    'idobject'        : $this.attr('data-id'),// Automatically upload a file when it's added to the queue
                    'auto'            : true,               // Automatically upload a file when it's added to the queue
                    'buttonClass'     : false,              // A class to add to the UploadiFive button
                    'buttonText'      : 'Добавить фото',    // The text that appears on the UploadiFive button
                    'checkScript'     : false,              // Path to the script that checks for existing file names 
                    'dnd'             : true,               // Allow drag and drop into the queue
                    'dropTarget'      : false,              // Selector for the drop target
                    'fileObjName'     : 'Filedata',         // The name of the file object to use in your server-side script
                    'fileSizeLimit'   : 115120*115120,        // Maximum allowed size of files to upload
                    'fileType'        : 'image',              // Type of files allowed (image, etc)
                    'formData'        : {'RSASESSIONID':_sessId,
                                       'id':$this.attr('data-id'),
                                       ajax: true
                                      }, // Additional data to send to the upload script
                    
                    'height'          : 15,                 // The height of the button
                    'itemTemplate'    : false,              // The HTML markup for the item in the queue
                    'first_instance'  : true,                // The HTML markup for the item in the queue
                    'method'          : 'post',             // The method to use when submitting the upload
                    'videoContainer'  : false,             // The method to use when submitting the upload
                    'multi'           : true,               // Set to true to allow multiple file selections
                    'overrideEvents'  : [],                 // An array of events to override
                    'process_pending' : [],                 // An array of events to override
                    'queueID'         : false,              // The ID of the file queue
                    'queueSizeLimit'  : 20,                  // The maximum number of files that can be in the queue
                    'removeCompleted' : false,              // Set to true to remove files that have completed uploading
                    'simUploadLimit'  : 0,                  // The maximum number of files to upload at once
                    'truncateLength'  : 0,                  // The length to truncate the file names to
                    'uploadLimit'     : 1000,                  // The maximum number of files you can upload
                    'uploadUrl'       : $this.attr('data-url'),  // The path to the upload script
                    'uploadScript'    : $this.attr('data-url')+'add/',  // The path to the upload script
                    'width'           : 100,                 // The width of the button
                    'buttonSetMain'   : true,                // Show button "Set Main Photo"
                    'buttonText'      : 'Загрузите изображения .jpeg .png .gif',//текст для кнопки

                    onInit    : function() {
                        if(settings.first_instance === true){
                                            settings.idobject = $this.attr('data-id');
                                            if(settings.fileType == 'video') $data.showUploadedVideos(settings.idobject);
                                            else $data.showUploadedPhotos(settings.idobject);
                                        }
                                        settings.first_instance = false; 
                                    },
                    onChangeCount: function(){
                    },
                    
                }, options);
                _settings = settings;
                // Calculate the file size limit
                if (isNaN(settings.fileSizeLimit)) {
                    var fileSizeLimitBytes = parseInt(settings.fileSizeLimit) * 1.024
                    if (settings.fileSizeLimit.indexOf('KB') > -1) {
                        settings.fileSizeLimit = fileSizeLimitBytes * 1000;
                    } else if (settings.fileSizeLimit.indexOf('MB') > -1) {
                        settings.fileSizeLimit = fileSizeLimitBytes * 1000000;
                    } else if (settings.fileSizeLimit.indexOf('GB') > -1) {
                        settings.fileSizeLimit = fileSizeLimitBytes * 1000000000;
                    }
                } else {
                    settings.fileSizeLimit = settings.fileSizeLimit * 1024;
                }

                // Create a template for a file input
                $data.inputTemplate = $('<input type="file" id="uploadInput"><span>'+settings.buttonText+'</span>');

                // Create a new input
                $data.createInput = function() {

                    // Create a clone of the file input
                    var input     = $data.inputTemplate.clone();
                    // Create a unique name for the input item
                    var inputName = input.name = 'input' + $data.inputCount++;
                    // Set the multiple attribute
                    if (settings.multi) {
                        input.attr('multiple', true);
                    }
                    // Set the onchange event for the input
                    input.bind('change', function() {
                        $data.queue.selected = 0;
                        $data.queue.replaced = 0;
                        $data.queue.errors   = 0;
                        $data.queue.queued   = 0;
                        // Add a queue item to the queue for each file
                        var limit = this.files.length;
                        $data.queue.selected = limit;
                        if (($data.queue.count + limit) > settings.queueSizeLimit && settings.queueSizeLimit !== 0) {
                            if ($.inArray('onError', settings.overrideEvents) < 0) {
                                alert('Максимально возможное количество файлов(' + settings.queueSizeLimit + '). ');
                            }
                            // Trigger the error event
                            if (typeof settings.onError === 'function') {
                                settings.onError.call($this, 'QUEUE_LIMIT_EXCEEDED');
                            }
                        } else {
                            for (var n = 0; n < limit; n++) {
                                file = this.files[n];
                                $data.addQueueItem(file);
                            }
                            $data.inputs[inputName] = this;
                            $data.createInput();
                        }
                        // Upload the file if auto-uploads are enabled
                        if (settings.auto) {
                            methods.upload.call($this);
                        }
                        // Trigger the select event
                        if (typeof settings.onSelect === 'function') {
                            settings.onSelect.call($this, $data.queue);
                        }
                    });
                    // Hide the existing current item and add the new one
                    if ($data.currentInput) {
                        $data.currentInput.remove();
                    }
                    $data.uploadli.append(input);
                    $data.currentInput = input; 
                }

                // Remove an input
                $data.destroyInput = function(key) {
                    $($data.inputs[key]).remove();
                    delete $data.inputs[key];
                    $data.inputCount--;
                }

                // Drop a file into the queue
                $data.drop = function(e) {
                    $data.queue.selected = 0;
                    $data.queue.replaced = 0;
                    $data.queue.errors   = 0;
                    $data.queue.queued   = 0;

                    var fileData = e.dataTransfer;

                    var inputName = fileData.name = 'input' + $data.inputCount++;
                    // Add a queue item to the queue for each file
                    var limit = fileData.files.length;
                    $data.queue.selected = limit;
                    if (($data.queue.count + limit) > settings.queueSizeLimit && settings.queueSizeLimit !== 0) {
                        // Check if the queueSizeLimit was reached
                        if ($.inArray('onError', settings.overrideEvents) < 0) {
                            alert('The maximum number of queue items has been reached (' + settings.queueSizeLimit + ').  Please select fewer files.');
                        }
                        // Trigger the onError event
                        if (typeof settings.onError === 'function') {
                            settings.onError.call($this, 'QUEUE_LIMIT_EXCEEDED');
                        }
                    } else {
                        // Add a queue item for each file
                        for (var n = 0; n < limit; n++) {
                            file = fileData.files[n];
                            $data.addQueueItem(file);
                        }
                        // Save the data to the inputs object
                        $data.inputs[inputName] = fileData;
                    }

                    // Upload the file if auto-uploads are enabled
                    if (settings.auto) {
                        methods.upload.call($this);
                    }

                    // Trigger the onDrop event
                    if (typeof settings.onDrop === 'function') {
                        settings.onDrop.call($this, fileData.files, fileData.files.length);
                    }

                    // Stop FireFox from opening the dropped file(s)
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Check if a filename exists in the queue
                $data.fileExistsInQueue = function(file) {
                    for (var key in $data.inputs) {
                        input = $data.inputs[key];
                        limit = input.files.length;
                        for (var n = 0; n < limit; n++) {
                            existingFile = input.files[n];
                            // Check if the filename matches
                            if (existingFile.name == file.name && !existingFile.complete) {
                                return true;
                            }
                        }
                    }
                    return false;
                }

                // Remove an existing file in the queue
                $data.removeExistingFile = function(file) {
                    for (var key in $data.inputs) {
                        input = $data.inputs[key];
                        limit = input.files.length;
                        for (var n = 0; n < limit; n++) {
                            existingFile = input.files[n];
                            // Check if the filename matches
                            if (existingFile.name == file.name && !existingFile.complete) {
                                $data.queue.replaced++;
                                methods.cancel.call($this, existingFile, true);
                            }
                        }
                    }
                }

                // Create the file item template
                if (settings.itemTemplate == false) {
                    $data.queueItem = $('<li class="uploadifive_queue-item">\
                        <div class="itemsContainer">\
                            <span class="filename_wrap">Размер файла<br/><span class="filename"></span></span>\
                            <div class="progress">\
                                <div class="progress-bar"></div>\
                            </div>\
                            <span class="fileinfo_wrap">Загрузка <span class="fileinfo"></span></span>\
                        </div>\
                    </li>');
                    
                } else {
                    $data.queueItem = $(settings.itemTemplate);
                }

                // Add an item to the queue
                $data.addQueueItem = function(file) {
                    if ($.inArray('onAddQueueItem', settings.overrideEvents) < 0) {
                        // Check if the filename already exists in the queue
                        $data.removeExistingFile(file);
                        // Create a clone of the queue item template
                        file.queueItem = $data.queueItem.clone();
                        // Add an ID to the queue item
                        file.queueItem.attr('id', settings.id + '-file-' + $data.fileID++);
                        // Bind the close event to the close button
                        // Get the size of the file
                        var fileSize = Math.round(file.size / 1024);
                        var suffix   = 'KB';
                        if (fileSize > 1000) {
                            fileSize = Math.round(fileSize / 1000);
                            suffix   = 'MB';
                        }
                        var fileSizeParts = fileSize.toString().split('.');
                        fileSize = fileSizeParts[0];
                        if (fileSizeParts.length > 1) {
                            fileSize += '.' + fileSizeParts[1].substr(0,2);
                        }
                        fileSize += suffix;                        
                        file.queueItem.find('.filename').html(fileSize);
                        // Add a reference to the file
                        file.queueItem.data('file', file);
                        $data.queueEl.append(file.queueItem);
                    }
                    // Trigger the addQueueItem event
                    if (typeof settings.onAddQueueItem === 'function') {
                        settings.onAddQueueItem.call($this, file);
                    }
                    // Check the filetype
                    if (settings.fileType) {
                        if ($.isArray(settings.fileType)) {
                            var isValidFileType = false;
                            for (var n = 0; n < settings.fileType.length; n++) {
                                if (file.type.indexOf(settings.fileType[n]) > -1) {
                                    console.log(file.type)
                                    isValidFileType = true;
                                }
                            }
                            if (!isValidFileType) {
                                console.log(file.type)
                                $data.error('Недопустимый тип файла', file);
                            }
                        } else {
                            if (file.type.indexOf(settings.fileType) < 0) {
                                console.log(file.type)
                                $data.error('Недопустимый тип файла', file);
                            }
                        }
                    }
                    // Check the filesize
                    if (file.size > settings.fileSizeLimit && settings.fileSizeLimit != 0) {
                        $data.error('FILE_SIZE_LIMIT_EXCEEDED', file);
                    } else {
                        $data.queue.queued++;
                    }
                }

                // Remove an item from the queue
                $data.removeQueueItem = function(file, instant, delay) {
                    
                    // Set the default delay
                    if (!delay) delay = 0;
                    var fadeTime = instant ? 0 : 500;
                    if (file.queueItem) {
                        if (file.queueItem.find('.fileinfo').html() != ' - Completed') {
                            file.queueItem.find('.fileinfo').html(' - Cancelled');
                        }
                        file.queueItem.find('.progress-bar').width(0);
                        file.queueItem.delay(delay).fadeOut(fadeTime, function() {
                           $(this).remove();
                        });
                        delete file.queueItem;
                        $data.queue.count--;
                    }
                }

                // Count the number of files that need to be uploaded
                $data.filesToUpload = function() {
                    var filesToUpload = 0;
                    for (var key in $data.inputs) {
                        input = $data.inputs[key];
                        limit = input.files.length;
                        for (var n = 0; n < limit; n++) {
                            file = input.files[n];
                            if (!file.skip && !file.complete) {
                                filesToUpload++;
                            }
                        }
                    }
                    return filesToUpload;
                }

                // Check if a file exists
                $data.checkExists = function(file) {
                    if ($.inArray('onCheck', settings.overrideEvents) < 0) {
                        // This request needs to be synchronous
                        $.ajaxSetup({
                            'async' : false
                        });
                        // Send the filename to the check script
                        var checkData = $.extend(settings.formData, {filename: file.name});
                        $.post(settings.checkScript, checkData, function(fileExists) {
                            file.exists = parseInt(fileExists);
                        });
                        if (file.exists) {
                            if (!confirm('A file named ' + file.name + ' already exists in the upload folder.\nWould you like to replace it?')) {
                                // If not replacing the file, cancel the upload
                                methods.cancel.call($this, file);
                                return true;
                            }
                        }
                    }
                    // Trigger the check event
                    if (typeof settings.onCheck === 'function') {
                        settings.onCheck.call($this, file, file.exists);
                    }
                    return false;
                }

                // Upload a single file
                $data.uploadFile = function(file, uploadAll) {
                    
                    if (!file.skip && !file.complete && !file.uploading ) {
                        
                        file.uploading = true;
                        $data.uploads.current++;
                        $data.uploads.attempted++;

                        // Create a new AJAX request
                        xhr[file.name] = file.xhr = new XMLHttpRequest();

                        // Start the upload
                        // Use the faster FormData if it exists
                        if (typeof FormData === 'function' || typeof FormData === 'object') {

                            // Create a new FormData object
                            var formData = new FormData();

                            // Add the form data
                            formData.append(settings.fileObjName, file);

                            // Add the rest of the formData
                            for (i in settings.formData) {
                                formData.append(i, settings.formData[i]);
                            }

                            // Open the AJAX call
                            xhr[file.name].open(settings.method, settings.uploadScript, true);

                            // On progress function
                            xhr[file.name].upload.addEventListener('progress', function(e) {
                                if (e.lengthComputable) {
                                    $data.progress(e, file);
                                }
                            }, false);

                            // On complete function
                            xhr[file.name].addEventListener('load', function(e) {
                                if (this.readyState == 4) {
                                    file.uploading = false;
                                    if (this.status == 200) {
                                        if (file.xhr.responseText !== 'Invalid file type.') {
                                            $data.uploadComplete(e, file, uploadAll);
                                        } else {
                                            $data.error(file.xhr.responseText, file, uploadAll);
                                        }
                                    } else if (this.status == 404) {
                                        $data.error('404_FILE_NOT_FOUND', file, uploadAll);
                                    } else if (this.status == 403) {
                                        $data.error('403_FORBIDDEN', file, uplaodAll);
                                    } else {
                                        $data.error('Unknown Error', file, uploadAll);
                                    }
                                }
                            });

                            // Send the form data (multipart/form-data)
                            xhr[file.name].send(formData);
                           

                        }
                    }
                }

                // Update a file upload's progress
                $data.progress = function(e, file) {
                    if ($.inArray('onProgress', settings.overrideEvents) < 0) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) * 100);
                        }
                        file.queueItem.find('.fileinfo').html(percent + '%');
                        file.queueItem.find('.progress-bar').css('width', percent + '%');
                        if(percent > 99){ 
                            if(settings.fileType == 'video'){
                                jQuery(settings.videoContainer).show();
                                jQuery(settings.videoContainer).append('<i class="video-transcoding">Подождите, идет обработка видео. По окончании обработки мы отправим Вам уведомление на email.</i>');
                            }  
                        }                      
                    }
                    // Trigger the progress event
                    if (typeof settings.onProgress === 'function') {
                        settings.onProgress.call($this, file, e);
                    }
                }

                // Trigger an error
                $data.error = function(errorType, file, uploadAll) {
                    if ($.inArray('onError', settings.overrideEvents) < 0) {
                        // Get the error message
                        switch(errorType) {
                            case '404_FILE_NOT_FOUND':
                                errorMsg = '404 ошибка';
                                break;
                            case '403_FORBIDDEN':
                                errorMsg = '403 запрещено';
                                break;
                            case 'FORBIDDEN_FILE_TYPE':
                                errorMsg = 'Недопустимый тип файла';
                                break;
                            case 'FILE_SIZE_LIMIT_EXCEEDED':
                                errorMsg = 'Файл слишком большой';
                                break;
                            default:
                                errorMsg = 'Неизвестная ошибка';
                                break;
                        }

                        // Add the error class to the queue item
                        file.queueItem.addClass('error')
                        // Output the error in the queue item
                        .find('.fileinfo').html(' - ' + errorMsg);
                        // Hide the 
                        file.queueItem.find('.progress').remove();
                    }
                    // Trigger the error event
                    if (typeof settings.onError === 'function') {
                        settings.onError.call($this, errorType, file);
                    }
                    file.skip = true;
                    if (errorType == '404_FILE_NOT_FOUND') {
                        $data.uploads.errors++;
                    } else {
                        $data.queue.errors++;
                    }
                    if (uploadAll) {
                        methods.upload.call($this, null, true);
                    }
                }

                // Trigger when a single file upload is complete
                $data.uploadComplete = function(e, file, uploadAll) {
                    if ($.inArray('onUploadComplete', settings.overrideEvents) < 0) {
                        
                        if(xhr[file.name].responseText.length>0){
                            eval("var obj1="+xhr[file.name].responseText);
                           
                            if(obj1.ok){
                                var _this_item = file.queueItem.children('div.itemsContainer');
                                if(settings.fileType == 'video'){
                                    settings.process_pending = window.setInterval(function(){
                                        $data.checkVideoStatus();
                                    }, 2000);
                                    
                                } else {
                                    _this_item.attr('data-id_obj',obj1.list.photo_id).append('<img src="'+obj1.list.file_name+'"  class="mUploadImg_photos" />');
                                    _this_item.children('.filename_wrap, .fileinfo_wrap, .progress').remove();
                                    _this_item.children('.mUploadImg_photos').fadeIn(150);
                                    $data.appendManagePart(_this_item,obj1.list.photo_id);
                                    file.queueItem.addClass('complete');
                                    settings.onChangeCount.call($data.queue.count);
                                    //поиск главной фотографии
                                    if($data.queue.count == 1) {
                                        jQuery('#'+settings.id + '_queue').children('li:nth-child(2)').children('.boxcaption_main').click();
                                        jQuery('#'+settings.id + '_queue').children('li:nth-child(2)').children('.boxcaption_main').on('click');
                                    }
                                }
                                
                            } else if(obj1.error !== 'null'){
                                alert(obj1.error);
                                file.queueItem.remove();
                            }   
                        }
                    }
                    // Trigger the complete event
                    if (typeof settings.onUploadComplete === 'function') {
                        settings.onUploadComplete.call($this, file, file.xhr.responseText);
                    }
                    if (settings.removeCompleted) {
                        setTimeout(function() { methods.cancel.call($this, file); }, 3000);
                    }
                    file.complete = true;
                    $data.uploads.successful++;
                    $data.uploads.current--;
                    delete file.xhr;
                    if (uploadAll) {
                        methods.upload.call($this, null, true);
                    }
                }

                // Trigger when all the files are done uploading
                $data.queueComplete = function() {
                    // Trigger the queueComplete event
                    if (typeof settings.onQueueComplete === 'function') {
                        settings.onQueueComplete.call($this, $data.uploads);
                    }
                }
                
    
    
                // show Uploaded photos 
                $data.showUploadedPhotos = function(_idobject) {                
                     
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: settings.uploadUrl+'list/',
                        data: {'id':_idobject, ajax: true}, cache:false,
                        success: function(msg){
                            if(typeof(msg)=='object') {
                                if(msg.ok) {
                                    for(i=0;i<msg.list.length;i++){
                                        var mainPhotoClass = msg.list[i].main_photo=='true'?'mainPhoto':'';
                                        jQuery('#'+settings.id + '_queue').append('\
                                                <li id="preload_'+i+'" class="'+mainPhotoClass+'">\
                                                    <div class="itemsContainer" data-id_obj="'+msg.list[i].id+'">\
                                                        <img src="/'+msg.folder+'/sm/'+msg.list[i].subfolder+'/'+msg.list[i].name+'"  class="mUploadImg_photos" />\
                                                    </div>\
                                                </li>');
                                        _this_item = jQuery('li#preload_'+i).children('div');
                                        _this_item.children('.mUploadImg_photos').fadeIn(150);
                                        $data.appendManagePart(_this_item,msg.list[i]);
                                    }
                                }
                                if(typeof msg.list != 'undefined') _photos_length = msg.list.length;
                                else _photos_length = 0;
                            return _photos_length;
                            }
                        },
                        error:function (xhr, ajaxOptions, thrownError){
                                
                        },
                        complete: function(){
                        }
                    });
                }
                // show Uploaded video 
                $data.showUploadedVideos = function(_idobject) {                
                    settings.process_pending = window.setInterval(function(){
                        $data.checkVideoStatus();
                    }, 2000);
                    return false;
                }                
                // show Video Uploaded status
                $data.checkVideoStatus = function() {                
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: settings.uploadUrl+'list/',
                        data: {ajax: true}, cache:false,
                        success: function(msg){
                            if(typeof(msg)=='object') {
                                if(msg.ok) {
                                    if(msg.list.status == 3) {
                                        $data.queue.count++;
                                        jQuery('input#'+settings.id).siblings('.fileUploadStat').children('.totalObjects').html($data.queue.count)                                    
                                        jQuery(settings.videoContainer).show();
                                        window.clearInterval(settings.process_pending);
                                        jQuery('.video-transcoding').remove();
                                        if(jQuery('#file_upload_video-file-0').length > 0) jQuery('#file_upload_video-file-0').remove(); 
                                        jQuery(settings.videoContainer).on('click', '.boxcaption_del', function(){
                                            $data.deleteVideoFile();
                                        })
                                        getPendingContent([settings.videoContainer],[settings.uploadUrl+'manage/']);
                                        return false;
                                    } else {
                                        if(jQuery('.video-transcoding').length == 0) jQuery(settings.videoContainer).append('<i class="video-transcoding">Подождите, идет обработка видео. По окончании обработки мы отправим Вам уведомление на email..</i>');
                                    }
                                } else {
                                    jQuery(settings.videoContainer).hide();
                                    window.clearInterval(settings.process_pending);
                                }
                                return false;
                            }
                        },
                        error:function (xhr, ajaxOptions, thrownError){
                                
                        },
                        complete: function(){
                        }
                    });
                }                

                //добавление блока управления фотографиями
                $data.appendManagePart = function(_this_item, list) {                
                    
                    $data.queue.count++;
                    jQuery('input#'+settings.id).siblings('.fileUploadStat').children('.totalObjects').html($data.queue.count)
                    if($data.queue.count>1) {jQuery('.fileSorting').show();}
                    if($data.queue.count>settings.queueSizeLimit-1) {
                        jQuery('#'+settings.id+'_queue').children('.uploadifyButton').addClass('inactive');
                    }    
                    
                    var buttonSetMain = settings.buttonSetMain == true ? '<div class="boxcaption-wrap"><div class="boxcaption_main" data-icon="star" title="Сделать ' + ( settings.fileType == 'video' ? 'видео' : 'фотографию' ) + ' основной"></div>' : '<div class="boxcaption-wrap">';
                    
                    _this_item.parents('li').append('\
                            '+buttonSetMain+'\
                                <div class="boxcaption_del"  data-icon="delete" title="Удалить ' + ( settings.fileType == 'video' ? 'видео' : 'фотографию' ) + '"></div>' + ( settings.fileType == 'video' ? '' : '<div class="boxcaption_rotate" data-icon="refresh" title="Повернуть фотографию"></div></div>' ) + '\
                            \
                    ');
                    
                    //удаление фотографии
                    _this_item.parents('li').find('.boxcaption_del').bind('click', function() {
                           $data.deleteFile(jQuery(this));
                           return false;
                    });
                    //сделать главной фотографию
                    _this_item.parents('li').find('.boxcaption_main').bind('click', function() {
                           $data.makeMainFile(jQuery(this));
                           return false;
                    });
                    //поворот фотографии
                    _this_item.parents('li').find('.boxcaption_rotate').bind('click', function() {
                           $data.rotateFile(jQuery(this));
                           return false;
                    });
                   
                }
                $data.deleteFile = function(file) {
                    var _this_elm = file;
                    var _objectId = _this_elm.parent().siblings('div.itemsContainer').attr('data-id_obj');
                    var _input = _this_elm.closest('li').parents('ul').prev('.mUploadImg').children('input[type=file]'); 
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: _input.data('url')+'del/',
                        data: {ajax: true,'id_photo':_objectId},
                        success: function(msg){
                            if(typeof(msg)=='object'){
                                    $data.queue.count--;
                                    jQuery('input#'+settings.id).siblings('.fileUploadStat').children('.totalObjects').html($data.queue.count);
                                    settings.onChangeCount.call($data.queue.count);
                                    _this_elm.parents('li').fadeOut(100).remove();
                                    if($data.queue.count<2) { jQuery('.fileSorting').hide(); }
                                    var setUploadLimit = settings.uploadLimit;
                                    settings.uploadLimit = setUploadLimit+1;
                                    if($data.queue.count<settings.queueSizeLimit) {
                                        jQuery('.uploadifyButton').removeClass('inactive');
                                    }
                                    //поиск главной фотографии
                                    if(jQuery('#'+settings.id + '_queue').find('li.mainPhoto').length == 0 && $data.queue.count>0) {
                                        jQuery('#'+settings.id + '_queue').children('li:nth-child(2)').children('.boxcaption_main').click();
                                        jQuery('#'+settings.id + '_queue').children('li:nth-child(2)').children('.boxcaption_main').on('click');
                                    }
                                    
                                
                            }
                        },
                        error:function (xhr, ajaxOptions, thrownError){
                                
                        },
                        complete: function(){
                        }
                    });  
                }             
                // delete Video files
                $data.deleteVideoFile = function(){
                    getPending(settings.uploadUrl+'del/');
                    jQuery(settings.videoContainer).fadeOut(100).html('');
                    $data.queue.count--;
                    jQuery('input#'+settings.id).siblings('.fileUploadStat').children('.totalObjects').html($data.queue.count)                        
                }
                $data.makeMainFile = function(file) {
                    var _this_elm = file.parent();
                    var _input = _this_elm.parents('li').parents('ul').prev('.mUploadImg').children('input[type=file]');
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: _input.data('url')+'setMain/',
                        data: {'id_photo':_this_elm.siblings('div.itemsContainer').data('id_obj'), 'id':_input.data('id'), ajax: true},
                        success: function(msg){
                            if(typeof(msg)=='object'){
                                if(msg.ok){
                                    _this_elm.parents('li').addClass('mainPhoto').siblings('li.mainPhoto').removeClass('mainPhoto');
                                }
                            }
                        },
                        error:function (xhr, ajaxOptions, thrownError){
                                
                        },
                        complete: function(){
                        }
                    });                  
                }
                
                $data.rotateFile = function(file) {
                    var _this_elm = file.parent();
                    _image = _this_elm.parents('li').find('.mUploadImg_photos');
                    var _input = _this_elm.parents('li').parents('ul').prev('.mUploadImg').children('input[type=file]');
                    jQuery.ajax({
                        type: "POST", async: true,
                        dataType: 'json', url: _input.data('url')+'rotate/',
                        data: {'id_photo':_this_elm.siblings('div.itemsContainer').data('id_obj'), 'id':_input.data('id'), ajax: true},
                        success: function(msg){
                            if(typeof(msg)=='object'){
                                if(msg.ok){
                                    _image.attr('src',_image.attr('src') + '?' + Math.random());
                                }
                            }
                        },
                        error:function (xhr, ajaxOptions, thrownError){
                                
                        },
                        complete: function(){
                        }
                    });                  
                }
                
                $data.saveFileTitle = function(file) {

                }                               
                // ----------------------
                // Initialize UploadiFive
                // ----------------------

                // Check if HTML5 is available
                if (window.File && window.FileList && window.Blob && (window.FileReader || window.FormData)) {
                    // Assign an ID to the object
                    settings.id = $this.attr('id');

                    // Wrap the instance
                    var $wrapper = jQuery('<div />', {
                        'id'    : settings.id,
                        'class' : 'mUploadImg'
                    });
                    
                    // Wrap the file input in a div with overflow set to hidden
                    $data.button = $('<div id="' + settings.id + '_button" class="mUploadImgButton">' + settings.buttonText + '</div>');
                    if (settings.buttonClass) $data.button.addClass(settings.buttonClass);

                    var $uploadStat = jQuery('<div />', {
                        'class' : 'fileUploadStat',
                        html    : '<span class="totalObjects">0</span> / '+settings.queueSizeLimit + ( settings.fileType == 'video' ? ' видео' : ' фото' )
                    });
                    
                    // Insert the button above the file input
                    $this.before($wrapper)
                    // Add the file input to the button
                    .appendTo($wrapper)
                    // Modify the styles of the file input
                    .hide();
                    $wrapper.append($uploadStat);
                    $wrapper.append($data.button);

                    // Create the queue container
                    settings.queueID = settings.id + '_queue';
                    $data.queueEl = $('<ul id="' + settings.queueID + '" class="file_upload_queue" />');
                    $wrapper.after($data.queueEl);
                    
                    $data.uploadli =  $('<li />', {
                        'class' : 'uploadifyButton',
                        'title'    : settings.buttonText,
                        'data-icon'    : 'file_upload'
                    });
                    $data.queueEl.append($data.uploadli);

                    // Create a new input
                    $data.createInput.call($this);
                   
                    
                    // Add drag and drop functionality
                    if (settings.dnd) {
                        var $dropTarget = settings.dropTarget ? $(settings.dropTarget) : $data.queueEl.get(0);
                        $dropTarget.addEventListener('dragleave', function(e) {
                            // Stop FireFox from opening the dropped file(s)
                            e.preventDefault();
                            e.stopPropagation();
                        }, false);
                        $dropTarget.addEventListener('dragenter', function(e) {
                            // Stop FireFox from opening the dropped file(s)
                            e.preventDefault();
                            e.stopPropagation();
                        }, false);
                        $dropTarget.addEventListener('dragover', function(e) {
                            // Stop FireFox from opening the dropped file(s)
                            e.preventDefault();
                            e.stopPropagation();
                        }, false);
                        $dropTarget.addEventListener('drop', $data.drop, false);
                    }

                    // Send as binary workaround for Chrome
                    if (!XMLHttpRequest.prototype.sendAsBinary) {
                        XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
                            function byteValue(x) {
                                return x.charCodeAt(0) & 0xff;
                            }
                            var ords = Array.prototype.map.call(datastr, byteValue);
                            var ui8a = new Uint8Array(ords);
                            this.send(ui8a.buffer);
                        }
                    }

                    // Trigger the oninit event
                    if (typeof settings.onInit === 'function') {
                        settings.onInit.call($this);
                    }

                } else {

                    // Trigger the fallback event
                    if (typeof settings.onFallback === 'function') {
                        settings.onFallback.call($this);
                    }
                    return false;

                }

            });

        },


        // Write some data to the console
        debug : function() {

            return this.each(function() {

                console.log($(this).data('uploadifive'));

            });

        },

        // Clear all the items from the queue
        clearQueue : function() {

            this.each(function() {

                var $this    = $(this),
                    $data    = $this.data('uploadifive'),
                    settings = $data.settings;

                for (var key in $data.inputs) {
                    input = $data.inputs[key];
                    limit = input.files.length;
                    for (i = 0; i < limit; i++) {
                        file = input.files[i];
                        methods.cancel.call($this, file);
                    }
                }
                // Trigger the onClearQueue event
                if (typeof settings.onClearQueue === 'function') {
                    settings.onClearQueue.call($this, $('#' + $data.settings.queueID));
                }

            });

        },

        // Cancel a file upload in progress or remove a file from the queue
        cancel : function(file, fast) {

            this.each(function() {

                var $this    = $(this),
                    $data    = $this.data('uploadifive'),
                    settings = $data.settings;

                // If user passed a queue item ID instead of file...
                if (typeof file === 'string') {
                    if (!isNaN(file)) {
                        fileID = 'uploadifive-' + $(this).attr('id') + '-file-' + file;
                    }
                    file = $('#' + fileID).data('file');
                }

                file.skip = true;
                $data.filesCancelled++;
                if (file.uploading) {
                    $data.uploads.current--;
                    file.uploading = false;
                    file.xhr.abort();
                    delete file.xhr;
                    methods.upload.call($this);
                }
                if ($.inArray('onCancel', settings.overrideEvents) < 0) {
                    $data.removeQueueItem(file, fast);
                }

                // Trigger the cancel event
                if (typeof settings.onCancel === 'function') {
                    settings.onCancel.call($this, file);
                }
                
            });
            
        },

        // Upload the files in the queue
        upload : function(file, keepVars) {

            this.each(function() {

                var $this    = $(this),
                    $data    = $this.data('uploadifive'),
                    settings = $data.settings;

                if (file) {

                    $data.uploadFile.call($this, file);

                } else {

                    // Check if the upload limit was reached
                    if (($data.uploads.count + $data.uploads.current) < settings.uploadLimit || settings.uploadLimit == 0) {
                        if (!keepVars) {
                            $data.uploads.attempted   = 0;
                            $data.uploads.successsful = 0;
                            $data.uploads.errors      = 0;
                            var filesToUpload = $data.filesToUpload();
                            // Trigger the onUpload event
                            if (typeof settings.onUpload === 'function') {
                                settings.onUpload.call($this, filesToUpload);
                            }
                        }

                        // Loop through the files
                        $('#' + settings.queueID).find('.uploadifive_queue-item').not('.error, .complete').each(function() {
                            file = $(this).data('file');
                            // Check if the simUpload limit was reached
                            if (($data.uploads.current >= settings.simUploadLimit && settings.simUploadLimit !== 0) || ($data.uploads.current >= settings.uploadLimit && settings.uploadLimit !== 0) || ($data.uploads.count >= settings.uploadLimit && settings.uploadLimit !== 0)) {
                                return false;
                            }

                            $data.uploadFile(file, true);
                        });
                        if ($('#' + settings.queueID).find('.uploadifive_queue-item').not('.error, .complete').size() == 0) {
                            $data.queueComplete();
                        }
                    } else {
                        if ($data.uploads.current == 0) {
                            if ($.inArray('onError', settings.overrideEvents) < 0) {
                                if ($data.filesToUpload() > 0 && settings.uploadLimit != 0) {
                                    alert('The maximum upload limit has been reached.');
                                }
                            }
                            // Trigger the onError event
                            if (typeof settings.onError === 'function') {
                                settings.onError.call($this, 'UPLOAD_LIMIT_EXCEEDED', $data.filesToUpload());
                            }
                        }
                    }

                }

            });

        },   

        // Destroy an instance of UploadiFive
        destroy : function() {

            this.each(function() {

                var $this    = $(this),
                    $data    = $this.data('uploadifive'),
                    settings = $data.settings;
            
                // Clear the queue
                methods.clearQueue.call($this);
                // Destroy the queue if it was created
                if (!settings.queueID) $('#' + settings.queueID).remove();
                // Remove extra inputs
                $this.siblings('input').remove();
                // Show the original file input
                $this.show()
                // Move the file input out of the button
                .insertBefore($data.button);
                // Delete the button
                $data.button.remove();
                // Trigger the destroy event
                if (typeof settings.onDestroy === 'function') {
                    settings.onDestroy.call($this);
                }

            });

        }

    }

    $.fn.uploadifive = function(method) {

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('The method ' + method + ' does not exist in $.uploadify');
        }

    }

    //эффекты кнопок управление загрузкой фото
        //изменение сортировки вкл/выкл
        jQuery(document).on('click', '.fileSorting', function(){
                isSortable=isSortable==true?false:true;
                jQuery('.fileSorting').toggleClass('fileSortingOpacity100');
                if(isSortable==true && $data.queue.count>1) { 
                    jQuery('#'+settings.id + '_queue').sortable(photoOriginalOptions); 
                    jQuery('#'+settings.id + '_queue li').addClass('sortableList');
                } else {
                    jQuery('#'+settings.id + '_queue').sortable({ disabled: true }); 
                    jQuery('#'+settings.id + '_queue li').removeClass('sortableList');
                }
        });    
        var photoOriginalOptions = { 
            containment: 'parent',
            cursor: 'pointer',
            disabled: false,
            update: function(){ 
                var _ids_order = new Array()
                jQuery(".itemsContainer").each(function(index, element) {
                  _ids_order[index] = jQuery(this).attr('data-id_obj');
                });
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: settings.uploadUrl+'sort/',
                    data: {ajax: true,'order':_ids_order},
                    success: function(msg){},
                    error:function (xhr, ajaxOptions, thrownError){        },
                    complete: function(){ }
                });
            }  
        };
})(jQuery);
function sleep(ms) {
    ms += new Date().getTime();
    while (new Date() < ms){}
} 
/* I gave the queueItems IDs and they each have a reference to the file held in the 'data' obj. */