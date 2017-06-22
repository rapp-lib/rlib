InputPluginRegistry.registerPlugin("date_select", function ($elm, params) {
    var bind_elms = [];
    params.delim = params.delim || "-";
    // 要素を隠す
    $elm.hide();
    // 既存要素の関連づけ
    if (params.bind) {
        if (params.bind.finds) {
            for (var i in params.bind.finds) {
                bind_elms[i] = $elm.parent().find(params.bind.finds[i]);
            }
        }
        if (params.bind.ids) {
            for (var i in params.bind.ids) {
                bind_elms[i] = $("#"+params.bind.ids[i]);
            }
        }
    //TODO:指定の様式のselectフィールドを作成
    } else {
        //
    }
    // selectフィールドの値の初期化
    var date = new Date($elm.val());
    var values = {
        year : date.getFullYear(),
        month : date.getMonth() + 1,
        day : date.getDate(),
        hour : date.getHours(),
        min : date.getMinutes(),
        sec : date.getSeconds()
    };
    for (var i in bind_elms) {
        bind_elms[i].val(values[i]);
    }
    // 値の更新時の処理
    var on_update_bind_input = function (e) {
        // 選択操作時のみ選択肢を正規化
        if (e) {
            for (var i in bind_elms) {
                // 選択解除された場合、全ての選択肢を選択解除させる
                if ( ! $(this).val() && bind_elms[i].val()) {
                    bind_elms[i].val("");
                }
                // 有効な値が選択された場合、解除されている全ての選択肢を有効なものに置き換える
                if ($(this).val() && ! bind_elms[i].val()) {
                    bind_elms[i].val(bind_elms[i].find("option").eq(1).attr("value"));
                }
            }
        }
        var values = {
            year : 1970,
            month : 1,
            day : 1,
            hour : 0,
            min : 0,
            sec : 0
        };
        for (var i in bind_elms) {
            values[i] = bind_elms[i].val();
        }
        if (values.year && values.month && values.day) {
            var date = new Date(values.year, values.month-1, values.day, values.hour, values.min, values.sec);
            var yyyy = date.getFullYear();
            var mm = ('0'+(date.getMonth()+1)).slice(-2);
            var dd = ('0'+date.getDate()).slice(-2);
            var hh = ('0'+date.getHours()).slice(-2);
            var ii = ('0'+date.getMinutes()).slice(-2);
            var ss = ('0'+date.getSeconds()).slice(-2);
            $elm.val(yyyy+"-"+mm+"-"+dd+" "+hh+":"+ii+":"+ss);
        } else {
            $elm.val("");
        }
    };
    for (var i in bind_elms) {
        bind_elms[i].on("change",on_update_bind_input);
    }
    on_update_bind_input();
});