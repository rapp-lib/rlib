InputPluginRegistry.registerPlugin("split_text", function ($elm, params) {
    var bind_elms = [];
    params.delim = params.delim || "-";
    // 要素を隠す
    $elm.hide();
    // 既存要素の関連づけ
    if (params.bind) {
        if (params.bind.ids) {
            for (var i in params.bind.ids) {
                var $bind_elm = $("#"+params.bind.ids[i]);
                bind_elms.push($bind_elm);
            }
        }
    // 指定の数のtextフィールドを作成
    } else {
        params.num = params.num || 2;
        for (var i=0; i<params.num; i++) {
            var $bind_elm = $('<input/>').attr("type","text").addClass("split_text");
            bind_elms.push($bind_elm);
            $elm.before($bind_elm);
            if (i < params.num-1) {
                $elm.before(params.delim);
            }
        }
    }
    // textフィールドの初期化
    var values = $elm.val().split(params.delim);
    for (var i in bind_elms) {
        var $bind_elm = bind_elms[i];
        if (values[i]) {
            $bind_elm.val(values[i]);
        }
    }
    // 値の更新時の処理
    var on_update_bind_input = function () {
        // 値が入っていない時はDelimが入らないようにする
        var flg = false;
        for (var i in bind_elms) {
            if(bind_elms[i].val()) {
                flg = true;
                break;
            }
        }
        // 値をDelimで区切った文字を組み立てる
        var value = "";
        if(flg) {
            for (var i in bind_elms) {
                var $bind_elm = bind_elms[i];
                value += $bind_elm.val();
                if (i < bind_elms.length-1) {
                    value += params.delim;
                }
            }
        }
        $elm.val(value);
    };
    for (var i in bind_elms) {
        var $bind_elm = bind_elms[i];
        $bind_elm.on("change keyup",on_update_bind_input);
    }
    on_update_bind_input();
});
