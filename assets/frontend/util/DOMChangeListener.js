window.DOMChangeListener = function($elm, mode, listen_callback){
    // MutationObserverに対応している場合
    if (MutationObserver) {
        var mo = new MutationObserver(function (records) {
            var nodes = [];
            for (var i in records) if (records[i].addedNodes) for (var u in records[i].addedNodes) {
                nodes.push(records[i].addedNodes[u]);
            }
            if (nodes.length > 0) listen_callback($elm, nodes);
        });
        $elm.each(function(){
            mo.observe($(this).get(0), {childList:true, subtree:true});
        });
    // IE10以前でMutationObserver非対応であれば、DOMNodeInsertedを使用する
    } else {
        var nodes = [];
        var timer = 0;
        $elm.on('DOMNodeInserted',function(e){
            nodes.push(e.target);
            if (timer==0) timer = setTimeout(function(){
                if (nodes.length > 0) listen_callback($elm, nodes);
                nodes = [];
                timer = 0;
            },50);
        });
    }
};
