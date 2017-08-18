$(function(){
    $root = $(document);
    $root.find("[data-sort-toggle]").each(function(){
        var $toggle = $(this);
        var key = $toggle.data("sort-toggle");
        var key_desc = key.match(/@DESC$/i);
        var key_reverse = key_desc ? key.replace(/@DESC$/i, "") : key+"@DESC";
        // 検索URL要素の探索
        var $sort;
        if ($toggle.attr("data-sort-url")) {
            $sort = $(this);
        } else if ($toggle.attr("data-sort-handle")) {
            $sort = $($toggle.data("sort-handle"));
        } else if ($toggle.closest("[data-sort-url]")) {
            $sort = $toggle.closest("[data-sort-url]");
        }
        var base_url = $sort.data("sort-base-url") || "";
        var current = $sort.data("sort-current") || "";
        var default_key = $sort.data("sort-default") || "";
        var param_name = $sort.data("sort-param-name") || "sort";
        // 現在ソート状態にあわせてリンク先の決定とClassの適用
        var link = "";
        var classes = ["sort"];
        // デフォルト選択はパラメータ未設定と対応付ける
        if (current == "" && default_key == key) {
            classes.push("selected");
            classes.push(key_desc ? "desc" : "asc");
            link = "";
        // 選択されている場合
        } else if (current == key) {
            classes.push("selected");
            classes.push(key_desc ? "desc" : "asc");
            link = key_reverse;
        // 逆順選択されている場合
        } else if (current == key_reverse) {
            classes.push("selected");
            classes.push(key_desc ? "asc" : "desc");
            link = key;
        // 選択されていない場合
        } else {
            classes.push("not-selected");
            classes.push(key_desc ? "desc" : "asc");
            link = key;
        }
        var url = base_url;
        if (link) url += (base_url.match('\\?') ? "&" : "?")+param_name+"="+link;
        if ($toggle.prop("tagName")=="A") $toggle.attr("href", url);
        else $toggle.on("click", function(){ location.href = url; return false; });
        $toggle.addClass(classes.join(" "));
    });
});
