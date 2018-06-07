InputPluginRegistry.registerPlugin("bind_select", function ($elm, params) {
    var bind_elms = [];
    // 要素を隠す
    $elm.hide();
    // 既存要素の関連づけ
    if (params.bind) {
        if (params.bind.find) {
            bind_elms = $(params.bind.find);
        }
        if (params.bind.id) {
            bind_elms = $("#"+params.bind.id);
        }
    }
    // selectフィールドの値の初期化
    var preset_value = $elm.val();
    if (bind_elms.length == 1) {
        bind_elms.val(preset_value);
    } else if (preset_value instanceof Array) {
        bind_elms.val(preset_value);
    } else {
        bind_elms.val([preset_value]);
    }
    // 値の更新時の処理
    var on_update_bind_input = function (e) {
        if (bind_elms.length == 1) {
            $elm.attr("value", bind_elms.val());
        } else {
            $elm.attr("value", bind_elms.filter(":checked").val());
        }
    };
    bind_elms.on("change",on_update_bind_input);
    on_update_bind_input();
});
