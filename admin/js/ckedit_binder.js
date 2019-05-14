jQuery(document).ready(function(){
    jQuery("textarea[class^='CKEdit']").each(function(){
        var el = $(this);
        var conf = el.is("[class$='Big']") ? {toolbar: 'Big'} : {toolbar: 'Small'};
        if(CKEDITOR.instances[el.attr('id')]) CKEDITOR.remove(CKEDITOR.instances[el.attr('id')]);
        CKEDITOR.replace( el.attr('id'), conf );
    });
});