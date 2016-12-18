$(function(){
    $(".ckeditor-autoload").each(function(){
        var $editor = $(this);
        var editorId = $editor.attr("id");
        var ckeditorOptions = {};
        if ( ! editorId) {
            editorId = "editor-".Math.random().toString(36).slice(-8);
            $editor.attr("id", editorId);
        }
        if ($editor.attr("data-upload-url")) {
            ckeditorOptions.filebrowserUploadUrl = $editor.attr("data-upload-url");
        }
        CKEDITOR.replace(editorId, ckeditorOptions);
    }
});