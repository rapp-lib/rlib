window.Viframe = function($vif_block){
    var self = this;
    /**
     * 初期化処理
     */
    self._init = function () {
        self.$vif_block = $vif_block;

        // 初期化済みであれば登録処理を行わない
        if (self.$vif_block.data("viframe_object")) return;

        // 要素から取り出せるようにする
        self.$vif_block.data("viframe_object", self);

        // idの指定があればtargetで指定されるリストに登録
        self.id = $vif_block.attr("id") || null;
        if (self.id) window.Viframe.targets[self.id] = self;
    };
    /**
     * リクエスト送信して結果を読み込む
     */
    self.load = function (src){
        var url = null;
        var data = null;
        var method = "GET";
        if (typeof(src)=="string") url = src;
        else if (src instanceof jQuery && src.prop("tagName")=="A") url = src.attr("href");
        else if (src instanceof jQuery && src.prop("tagName")=="FORM") {
            url = src.attr("action");
            data = src.serializeArray();
            method = src.attr("method") || "GET";
        }
        if ( ! url) return;
        Viframe._beforeLoad(self.$vif_block);
        $.ajax({
            url: url,
            type: method,
            data: data,
            dataType: "html"
        }).done(function(html){
            Viframe._receiveData(self.$vif_block, html);
        }).fail(function(){
            Viframe._receiveError(self.$vif_block);
        });
    };
    self._init();
};
window.Viframe.targets = {};
/**
 * viframe要素からViframeオブジェクトを取得する
 */
window.Viframe.get = function($elm){
    $elm = $($elm);
    if ( ! $elm.hasClass("viframe")) return null;
    if ($elm.data("viframe-object"))  return $elm.data("viframe-object");
    return new Viframe($elm);
};
/**
 * Closestな要素からViframeオブジェクトを取得する
 */
window.Viframe.getClosest = function($elm){
    $elm = $($elm);
    var $parents = $elm.parents(".viframe");
    if ($parents.length==0) return null;
    return Viframe.get($parents.eq(0));
};
/**
 * IDからViframeオブジェクトを取得する
 */
window.Viframe.getById = function(id){
    return Viframe.targets[id];
};
/**
 * 読み込み前処理
 */
window.Viframe._beforeLoad = function($vif_block){
    if ( ! Viframe.spinner) Viframe.spinner = new Spinner();
    $vif_block.html("");
    Viframe.spinner.spin($vif_block.get(0));
};
/**
 * 読み込み完了時の処理
 */
window.Viframe._receiveData = function ($vif_block, html){
    $vif_block.html(html);
};
/**
 * 読み込みエラー時の処理
 */
window.Viframe._receiveError = function ($vif_block){
    console.error("VIF Load failed");
};
/**
 * リンクやFormの送信による遷移の割り込み処理
 */
window.Viframe._interceptTransition = function (event){
    var $link = $(this);
    var id = $link.attr("target");
    var vif = null;
    if (id) vif = Viframe.getById(id);
    else vif = Viframe.getClosest($link);
    if (vif) {
        event.preventDefault();
        event.stopPropagation();
        vif.load($link);
        return false;
    }
};
/**
 * 初期化処理
 */
window.Viframe.init = function($block){
    $block = $($block || document);
    var $vif_blocks = $block.find(".viframe");
    if ($vif_blocks.length > 0) {
        $vif_blocks.each(function(){
            var $elm = $(this);
            var vif = Viframe.get($elm);
            var url = $elm.attr("data-src");
            if (url) {
                $elm.removeAttr("data-src");
                vif.load(url);
            }
        });
        if ( ! Viframe.initialized) {
            Viframe.initialized = true;
            $(document).on("click", 'a', Viframe._interceptTransition);
            $(document).on("submit", 'form', Viframe._interceptTransition);
        }
    }
};

$(function(){
    window.Viframe.init();
});
