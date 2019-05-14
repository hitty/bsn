CKEDITOR.editorConfig = function( config ) {
};

CKEDITOR.plugins.addExternal('tooltip', '/admin/js/ckeditor/plugins/ckeditor-tooltip/tooltip/', 'plugin.js' );
CKEDITOR.editorConfig = function( config ) {
    config.extraPlugins = "tooltip";
    config.language = 'ru';
    config.width = "100%";
    
    config.toolbar_Full = [
        { name: 'document',    items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
        { name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
        { name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
        { name: 'forms',       items : [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
        '/',
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
        { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
        { name: 'links',       items : [ 'Link','Unlink','Anchor','Tooltip','RemoveTooltip' ] },
        { name: 'insert',      items : [ 'Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak' ] },
        '/',
        { name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
        { name: 'colors',      items : [ 'TextColor','BGColor' ] },
        { name: 'tools',       items : [ 'Maximize', 'ShowBlocks','-','About' ] }
    ];
    config.toolbar_Big = [
        { name: 'document',    items : [ 'Source','-','SpellCheck' ] },
        { name: 'clipboard',   items : [ 'PasteText','PasteFromWord' ] },
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat','Blockquote' ] },
        { name: 'links',       items : [ 'Link','Unlink','Anchor','Tooltip','RemoveTooltip' ] },
        { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
        { name: 'insert',      items : [ 'Image','Flash','Table','HorizontalRule','SpecialChar' ] },
        { name: 'colors',      items : [ 'TextColor','BGColor' ] },
        { name: 'styles',      items : [ 'Format','Font','FontSize' ] },
        { name: 'tools',       items : [ 'Maximize', 'ShowBlocks' ] }
    ];
    config.toolbar_Small = [
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline','-','SpellCheck','RemoveFormat','-','NumberedList','BulletedList','Tooltip','RemoveTooltip'] }
    ];
    config.toolbar_Promo = [
        { name: 'basicstyles', items : [ 'Source','Bold','Italic','Image','Link','Unlink','-','PasteText','PasteFromWord','RemoveFormat','-','NumberedList','BulletedList','Tooltip','RemoveTooltip','-','Maximize'] }
    ];
    config.toolbar_VerySmall = [
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline' ] }
    ];

    config.allowedContent = true;
        
    config.filebrowserBrowseUrl     =  '/admin/js/ckfinder/ckfinder.html',
    config.filebrowserImageBrowseUrl = '/admin/js/ckfinder/ckfinder.html?Type=Images',
    config.filebrowserFlashBrowseUrl = '/admin/js/ckfinder/ckfinder.html?Type=Flash',
    config.filebrowserUploadUrl         =  '/admin/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
    config.filebrowserImageUploadUrl = '/admin/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
    config.filebrowserFlashUploadUrl = '/admin/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'
    config.docType = '<!doctype html>';
    config.emailProtection = 'encode';                    
};

