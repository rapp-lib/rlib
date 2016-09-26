/**

-- jquery.vifhistory --
	Sharingseed co,ltd
	Yasutaka Toyosawa 
	2014/04/17 

-------------------------------------
■サンプル：

	<div class="vifHistory" id="mainViframe">
		{{a _page="user_master.view_list" class="vifHref"}}{{/a}}
	</div>
*/
(function($){

	// vifHistory内で発行されるリクエストのRouter
	var Router_Viframe =Backbone.Router.extend({
	
		// vif用triggerにフォームのパラメータを渡すための共有変数
		requestParam : {},
		
		// vifフラグメントのパターン定義
		routes: {
			"vif/:targetId/:historyId/-*path(?:query)": "vif"
		},
		
		// vifフラグメントに対応するtrigger処理
		vif: function (targetId, historyId, path, query) {
				
			// URLの決定
			var url =path + (query ? "?"+query : "");
			
			// POSTの設定
			var data ={};
			
			if (this.requestParam[historyId]) {
				
				data =this.requestParam[historyId].data || {};
			}
			
			// Headerの設定
			var headers ={};
			
			headers["X-Vif-History-Id"] =historyId;
			
			// vifリクエスト送信
			$("#"+targetId).vifHref(url, { 
			
				data : data,
				headers : headers,
				
				// vifレスポンスに対する処理
				onSuccess : function ($anchor, $target, data, xhr, o) {
					
					var vifRequestUrl =xhr.getResponseHeader("X-Vif-Request-Url");
					var vifResponseTargetId =xhr.getResponseHeader("X-Vif-Target-Id");
					var vifResponseHistoryId =xhr.getResponseHeader("X-Vif-History-Id");
					var vifResponseCode =xhr.getResponseHeader("X-Vif-Response-Code");
					
					// フルページ転送
					if (vifResponseCode == "FORCE_FULLPAGE") {
						
						location.href =vifRequestUrl;
					}
											
					// 302転送時のFragmentURL履歴の書き換え
					if (vifRequestUrl) {
					
						var url =vifRequestUrl;
						var targetId =vifResponseTargetId || $target.attr("id");
						var historyId =vifResponseHistoryId || "1";
					
						var fragment ="vif/"+targetId+"/"+historyId+"/-"+url;
						vifRouter.navigate(fragment, {trigger: false, replace: true});
					}
				}
			});
		},
		
		// vifフラグメントへの擬似リクエストの発行
		request : function ($target, url, data) {
			
			var targetId =$target.attr("id");
			var historyId =parseInt(Math.random()*8999999)+1000000;
			
			var fragment ="vif/"+targetId+"/"+historyId+"/-"+url;
			
			this.requestParam[historyId] ={
				historyId : historyId,
				targetId : targetId,
				url : url,
				data : data
			};
			
			vifRouter.navigate(fragment, { trigger : true });
		}
	});
		
	$(function(){
	
		window.vifRouter =new Router_Viframe();
	
		// Historyの記録開始
		Backbone.history.start();
		
		// vif要素への処理対応付け
		$(".vifHistory").each(function(){
			
			// viHistory内ではリクエスト直接送信せず、フラグメント経由で擬似リクエスト発行
			$(this).viframe(null,{
				requestFunction : function ($anchor, $target, ajaxOptions) {
					vifRouter.request($target, ajaxOptions.url, ajaxOptions.data);
				}
			});
			
			var targetId =$(this).attr("id");
			var currentFragment =Backbone.history.fragment;
			var preloadUrl =$(this).find(".vifHref").attr("href");
			
			// 初期フラグメントがこのvifHistory宛でなければ初期URLの読み込みを行う
			if ( ! currentFragment.match("^vif\/"+targetId) && preloadUrl) {
				
				vifRouter.request($(this), preloadUrl);
			}
		});
	});
})(jQuery);