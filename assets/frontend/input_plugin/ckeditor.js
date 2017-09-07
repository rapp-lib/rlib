InputPluginRegistry.registerPlugin("ckeditor", function ($elm, params) {
    var $editor = $elm;
    var editorId = $editor.attr("id");
    var ckeditorOptions = {};
    if ( ! editorId) {
        editorId = "editor-"+(Math.random().toString(36).slice(-8));
        $editor.attr("id", editorId);
    }
    if (params["upload_url"]) {
        ckeditorOptions.filebrowserUploadUrl = params["upload_url"];
    }
    CKEDITOR.replace(editorId, ckeditorOptions);
});
