InputPluginRegistry.registerPlugin("date_select", function ($elm, params) {
    var bind_elms = [];
    params.delim = params.delim || "-";
    // 要素を隠す
    $elm.hide();
    // 既存要素の関連づけ
    if (params.bind) {
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
        day : date.getDate()
    };
    for (var i in bind_elms) {
        bind_elms[i].val(values[i]);
    }
    // 値の更新時の処理
    var on_update_bind_input = function () {
        var values = {
            year : 1970,
            month : 1,
            day : 1
        };
        for (var i in bind_elms) {
            values[i] = bind_elms[i].val();
        }
        var date = new Date(values.year, values.month-1, values.day);
        var yyyy = date.getFullYear();
        var mm = ('0'+(date.getMonth()+1)).slice(-2);
        var dd = ('0'+date.getDate()).slice(-2);
        $elm.val(yyyy+"-"+mm+"-"+dd);
    };

    for (var i in bind_elms) {
        bind_elms[i].on("change",on_update_bind_input);
    }
    on_update_bind_input();
});
