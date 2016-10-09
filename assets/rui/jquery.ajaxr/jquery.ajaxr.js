(function($){
    
    //-------------------------------------
    // Ajaxrレスポンスの処理
    var ajaxrProcResponse = function (xhr, o) {
        
        var res;
        
        try {
            
            // ajaxrレスポンスの解析
            res =$.parseJSON(xhr.responseText);
        
        // ajaxrレスポンスの解析エラー
        } catch (e) {
            
            res ={
                type : "ajaxr_error",
                message : "Response is-not json : "+xhr.responseText
            };
        }
        
        // report処理
        if (res.report) {
            
            // Bufferの展開
            $(o.bufferOutputElm || "body").append(res.buffer);
            
            // エラーの表示
            if (res.type=="ajaxr_error" || res.type=="error_reporting") {
                
                if (console) { console.error("[AJAXR "+res.type+"] "+res.message); }
            
            // 転送の表示
            } else if (res.type=="redirect") {
                
                if (console) { console.log("[AJAXR "+res.type+"] "+res.url); }
            }
        }
        
        // エラー
        if (res.type=="ajaxr_error" || res.type=="error_reporting") {
        
        // 転送
        } else if (res.type=="redirect") {
            
            o.url =res.url;
            return $.ajaxr(o);
        
        // 正常応答
        } else if (res.type=="clean_output" || res.type=="normal") {
            
            if (o.dataType=="json") {
                
                res.response =$.parseJSON(res.response);
            }
            
            o.success(res.response, o.dataType, xhr);
        }
    };
	$.extend({
		ajaxr : function (o) {
            
            // 元のパラメータの保持
            var oRaw ={};
            for (var i in o) { oRaw[i] =o[i]; }
            
            // 通信完了時にAjaxrレスポンスを処理
            o.complete =function (xhr, textStatus) {
                ajaxrProcResponse(xhr,oRaw);
            };
            
            // パラメータを書き換えてリクエスト送信
            o.success =undefined;
            o.cache =o.cache || false;
            o.dataType ="text";
            o.headers =o.headers || {};
            o.headers["X-AJAXR"] ="ON";
            
            return $.ajax(o);
		}
	});

})(jQuery);