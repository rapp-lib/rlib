window.input_plugin_show_uploaded_file = function (id, params) {
    $(function(){
        // file-input要素
        var $elm = $("#"+id);
        // codeを保持するhidden要素
        var $uploaded_elm = $elm.next("input.uploaded");
        // アップロード済み表示領域
        var $uploaded_area = $uploaded_elm
            .parents(".uploaded-set")
            .find(".uploaded-area");
        // アップロード済み表示領域がなければ作成する
        if ( ! $uploaded_area.length) {
            $uploaded_area = $("<span/>")
                .addClass("uploaded-area");
            var $uploaded_link = $("<a/>")
                .addClass("uploaded-link")
                .css("padding-left","10px")
                .attr("target","_blank")
                .text("アップロード済み");
            var $upload_cancel_link = $("<a/>")
                .addClass("upload-cancel-link")
                .css("padding-left","10px")
                .attr("href","javascript:void(0);")
                .text("削除");
            $uploaded_elm.after($uploaded_area);
            $uploaded_area.append($uploaded_link);
            $uploaded_area.append($upload_cancel_link);
        }
        // code更新時の処理
        var on_change_file_code = function(){
            var code = $uploaded_elm.val();
            if (code) {
                var file_url = window.current_webroot_url+"/file:/"+code;
                $uploaded_area.find("a.uploaded-link").attr("href",file_url);
                $uploaded_area.find("img.uploaded-link").attr("src",file_url);
                $uploaded_area.show();
            } else {
                $uploaded_area.hide();
            }
        };
        $uploaded_elm.on("change",on_change_file_code);
        $uploaded_area.find(".upload-cancel-link").on("click",function(){
            var $upload_cancel_link = $(this);
            $uploaded_elm.val("");
            on_change_file_code();
        });
        on_change_file_code();
    });
};
