// jQueryを拡張して$(...).mi()を有効にする
$.fn.extend({
    mi : function () {
        return new window.MiHandler(this);
    }
});
// 可変数フォームを構築するクラス
window.MiHandler = function ($mi)
{
    var o = this;
    /**
     * 設定
     */
    o.config = {};
    /**
     * 初期化処理
     */
    o.init = function()
    {
        // 拡張機能の読み込み
        $.each(MiHandler.extensions,function(){
            this(o,$mi);
        });
        // テンプレートの取り込み
        o.config.tmpl = _.template($mi.find(".mi-tmpl").eq(0).html());
        // パラメータの取得
        o.config.minItems = $mi.attr("data-minItems") || 0;
        o.config.maxItems = $mi.attr("data-maxItems") || 9999;
        o.config.maxRank = $mi.attr("data-maxRank") || 9999;
        o.config.subNodeSelector = $mi.attr("data-subNodeSelector") || "ul";
        o.config.subNodeHtml = $mi.attr("data-subNodeHtml") || "<ul></ul>";
        // 追加ボタンの操作
        $mi.find(".mi-append").on("click",function(){
            o.appendItem();
        });
        // 既存の各要素について初期化処理
        $mi.find(".mi-item").each(function(){
            o.initItem($(this));
        });
        $mi.trigger("miInit");
        o.update();
        // 不足している要素の追加
        while ($mi.find(".mi-item").length < o.config.minItems) {
            o.appendItem();
        }
    };
    /**
     * コントロールの表示更新
     */
    o.update = function()
    {
        // 要素数が上限であれば追加ボタンを隠す
        if ($mi.find(".mi-item").length >= o.config.maxItems) {
            $mi.find(".mi-append").hide();
        } else {
            $mi.find(".mi-append").show();
        }
        // 要素数が下限であれば削除ボタンを隠す
        if ($mi.find(".mi-item").length <= o.config.minItems) {
            $mi.find(".mi-remove").hide();
        } else {
            $mi.find(".mi-remove").show();
        }
        // 要素の連番表示の更新
        var seq = 1;
        $mi.find(".mi-item").each(function(){
            $(this).find(".mi-item-seq").val(seq);
            $(this).find(".mi-item-seq-area").html(seq);
            seq++;
        });
        // 親子構造の更新
        var stack = [];
        $mi.find(".mi-item").each(function(){
            var id = $(this).attr("data-itemId");
            var rank = o.getRank($(this));
            var pid = rank>0 && stack[rank-1] ? stack[rank-1] : 0;
            stack[rank] = id;
            $(this).attr("data-pid", pid);
            $(this).attr("data-rank", rank);
        });
        $mi.trigger("miUpdate");
    };
    /**
     * 要素の追加
     */
    o.appendItem = function($anchorItem){
        // 要素数が上限であれば追加拒否
        if ($mi.find(".mi-item").length >= o.config.maxItems) {
            return;
        }
        // テンプレートHTMLにIDを設定して新しい要素を生成
        var $item = $(o.config.tmpl({ id : o.getNextId() }));
        o.initItem($item);
        if ($anchorItem) {
            $anchorItem.after($item);
        } else {
            $(".mi-anchor",$mi).before($item);
        }
        $mi.trigger("miChange",["append",$item,$anchorItem]);
        o.update();
        $item.trigger("dom-structure-add");
        return $item;
    };
    /**
     * 要素の初期化
     */
    o.initItem = function($item)
    {
        // フォームの編集
        $item.find("input,select,textarea").on("change",function(e){
            $mi.trigger("miChange",["edit",$(this).closest(".mi-item"), undefined, $(this)]);
        });
        // 削除ボタンの操作
        $item.find(".mi-item-remove").on("click",function(){
            o.removeItem($item);
        });
        // 上と入れ替えるボタンの操作
        $item.find(".mi-item-up").on("click",function(){
            o.swapUpItem($item);
        });
        // 下と入れ替えるボタンの操作
        $item.find(".mi-item-down").on("click",function(){
            o.swapDownItem($item);
        });
        // 右にインデントボタンの操作
        $item.find(".mi-item-indent").on("click",function(){
            o.indentItem($item);
        });
        // 左にインデント解除ボタンの操作
        $item.find(".mi-item-unindent").on("click",function(){
            o.unindentItem($item);
        });
        $mi.trigger("miInitItem",[$item]);
    };
    /**
     * 要素の削除
     */
    o.removeItem = function($item)
    {
        // 要素数が下限であれば削除拒否
        if ($mi.find(".mi-item").length <= o.config.minItems) {
            return;
        }
        // 子要素を下げる
        $item.children(o.config.subNodeSelector)
                .children(".mi-item").each(function(){
            o.unindentItem($(this));
        });
        $mi.trigger("miChange",["remove",$item]);
        $item.remove();
        o.update();
    };
    /*
     * 要素を一つ上に移動
     */
    o.swapUpItem = function($item){
        // 入れ替える前の要素がなければ拒否
        if ( ! $item.prev().hasClass("mi-item")) {
            // インデントされている場合は解除して再検証
            o.unindentItem($item);
            if ( ! $item.prev().hasClass("mi-item")) {
                return;
            }
        }
        $mi.trigger("miChange",["swapUp",$item,$item.prev()]);
        $item.after($item.prev());
        o.update();
    };
    /**
     * 要素を一つ下に移動
     */
    o.swapDownItem = function($item)
    {
        // 入れ替える次の要素がなければ拒否
        if ( ! $item.next().hasClass("mi-item")) {
            // インデントされている場合は解除して再検証
            o.unindentItem($item);
            if ( ! $item.next().hasClass("mi-item")) {
                return;
            }
        }
        $mi.trigger("miChange",["swapDown",$item,$item.next()]);
        $item.before($item.next());
        o.update();
    };
    /**
     * 要素のインデント
     */
    o.indentItem = function($item)
    {
        // 前に親になる要素がなければ拒否
        if ( ! $item.prev().hasClass("mi-item")) {
            return;
        }
        // 最大ランクチェック
        var rank = o.getRank($item);
        $item.find(".mi-item").each(function(){
            var childRank = o.getRank($(this));
            if (rank < childRank) {
                rank = childRank;
            }
        });
        // ランク制限に該当する場合は拒否
        if (rank >= o.config.maxRank) {
            return;
        }
        var $container = $item.prev().children(o.config.subNodeSelector);
        // コンテナがなければ作成
        if ( ! $container.length) {
            $item.prev().append(o.config.subNodeHtml);
            $container = $item.prev().children(o.config.subNodeSelector);
        }
        $mi.trigger("miChange",["indent",$item,$item.prev()]);
        $item.appendTo($container);
        o.update();
    };
    /**
     * 要素のインデント解除
     */
    o.unindentItem = function($item)
    {
        var $container = $item.parent(o.config.subNodeSelector);
        // 親がなければ拒否
        if ( ! $container.parent().hasClass("mi-item")) {
            return;
        }
        $mi.trigger("miChange",["unindent",$item,$container.parent()]);
        $item.insertAfter($container.parent());
        // 子要素が全て抜けたコンテナは削除
        if ( ! $container.children(".mi-item").length) {
            $container.remove();
        }
        o.update();
    };
    /**
     * 次に生成する要素のIDの取得
     */
    o.getNextId = function(){
        var nextId = 0;
        $mi.find(".mi-item").each(function(){
            var itemId = 1*$(this).attr("data-itemId");
            if (nextId <= itemId) {
                nextId = itemId;
            }
        });
        return 1*nextId+1;
    };
    /**
     * 親要素を取得
     */
    o.getParentItem = function ($item) {
        if ($item.parent().parent().hasClass("mi-item")) {
            return $item.parent().parent();
        }
        return null;
    };
    /**
     * ランクを取得
     */
    o.getRank = function ($item) {
        return $item.parents(".mi-item").filter("#"+$mi.attr("id")+" .mi-item").length;
    };
    /**
     * 順序的に前に位置する要素を取得
     */
    o.getPrevItem = function ($item) {
        var $resultItem = null;
        var $prevItem = null;
        $mi.find(".mi-item").each(function(){
            var $currnetItem = $(this);
            if ($currnetItem.attr("id") == $item.attr("id") && $resultItem == null) {
                $resultItem = $prevItem;
            }
            $prevItem = $currnetItem;
        });
        return $resultItem;
    };
    /**
     * 順序的に次に位置する要素を取得
     */
    o.getNextItem = function ($item) {
        var $resultItem = null;
        var isFound = false;
        $mi.find(".mi-item").each(function(){
            var $currnetItem = $(this);
            if (isFound && $resultItem == null) {
                $resultItem = $currnetItem;
            }
            isFound = $currnetItem.attr("id") == $item.attr("id");
        });
        return $resultItem;
    };
    // 初期化
    o.init();
};
/**
 * 登録済み拡張機能
 */
MiHandler.extensions = [];
/**
 * 拡張機能の登録
 */
MiHandler.extend = function (extension)
{
    MiHandler.extensions.push(extension);
};
// 自動適用
jQuery(function(){ jQuery(".mi").mi(); });
