/**

-- jquery.viframe --
	Sharingseed co,ltd
	Yasutaka Toyosawa 
	2012/12/07 

-------------------------------------
■サンプル：

	<script src="{{'/js/ext/jquery.viframe/index.js'|path_to_url}}"></script>
	
	{{a href="./item/list.html" target="item_list_2"}}[更新 外→中]{{/a}}<br/>
	<div class="viframe" id="item_list_2"><a href="./item/list.html" class="vifHref"></a></div>


	{{form _page=".item_add" target="item_list_3" id="form_3"}}
		{{input type="hidden" name="id" value=rand(1,999999)}}
		{{input type="text" name="name"}}
		{{input type="submit" value="追加"}}
	{{/form}}
	
	<div class="viframe" id="item_list_3"><a href="./item/list.html" class="vifHref"></a></div>
	
	<script>
	$(function(){
		$("#form_3").on("vifSuccess",function($anchor,$target,data){ alert("追加完了！"); });
		$("#form_3").on("vifError",function($anchor,$target){ alert("エラー："+textStatus);	});
	});
	</script>

-------------------------------------
■API：

	$target.viframe(url,o);
	$target.vifHref(url,o);
	$anchor.vifSetTarget($target,o);
	$anchorOrTarget.on("vifSuccess",function(e,$anchor,$target,data,xhr,o) { });
	$anchorOrTarget.on("vifError",function(e,$anchor,$target,textStatus,xhr,o) { });
	$anchorOrTarget.on("vifBefore",function(e,$anchor,$target,xhr,o) { });
	$anchorOrTarget.on("vifAfter",function(e,$anchor,$target,xhr,o) { });

-------------------------------------
■その他の使用方法：

	□$target:id を $anchor:target に指定することでも vifSetTarget() 同様の効果
		<div id="testTarget"/> <a href="..." target="testTarget"/>
		
	□.viframe .vifHref の指定でも viframe() vifHref() 同様の効果
		<div class="viframe"><a href="./item/list.html" class="vifHref"></a></div>
		
-------------------------------------
■パラメータ：

	$anchor ... リクエスト発行要素
	$target ... レスポンス設定先要素
	$anchorOrTarget ... $anchorまたは$target要素
	url ... 読み込み先URL
	xhr ... XmlRequestHandler 
	data ... レスポンスデータ
	textStatus ... HTTPレスポンスステータス
	o ... vifBindRequestのオプション
		ajaxOptions : {} ... $.ajaxのリクエストオプション
		delegate : "" ... delegateするselector
		stripHead : false ... 自動的にレスポンスのHead要素を削除する設定
		ignoreExternalLink : true ... 外部リンクへのリクエストは無視する
*/

(function($){
	
	//------------------------------------- 
	// .viframe要素を全てviframe読み込み
	$(function(){
	
		$(".viframe").each(function(){
			
			var $vifHref =$(this).children(".vifHref");
			var url =$vifHref.length > 0 ? $vifHref : "";
			
			$(this).viframe(url);
		});
	});
	
	//-------------------------------------
	// オプションのマージ処理
	var merge =function(a,b) {
	
		var c ={};
		for (var k in a) { c[k] =a[k]; }
		for (var k in b) { c[k] =b[k]; }
		return c;
	};
	
	//-------------------------------------
	// デフォルトオプション
	$.vifConfig ={
		ajaxOptions : {},
		delegate : "",
		stripHead : false,
		ignoreExternalLink : true
	};
	
	//-------------------------------------
	// エラーハンドル
	$.vifTriggerError =function (msg,info) {
		if (console && console.error) {
			console.error(["[viframe] "+msg,info]);
		}
	};
	
	//-------------------------------------
	// viframe機能の初期化
	$.fn.viframe =function(url,o) {
	
		o =o || {};
		url =url || "";
		
		return this.each(function() {
			$.viframe($(this), url, o);
		});
	};
	$.viframe =function($target, url, o) {
		
		// viframe内のリクエストは全てそのviframeへdelegate
		$target.vifBindRequest($target,merge(o,{
			delegate : "a,form"
		}));
		
		// viframeのIDをtargetとしている要素にもdelegate
		if ($target.attr("id")) {
			
			$(document).vifBindRequest($target,merge(o,{
				delegate : "*[target='"+$target.attr("id")+"']"
			}));
		}
		
		// 初回読み込み
		if (url) {
		
			$target.vifHref(url);
		}
	};
	
	//-------------------------------------
	// リクエストを発行してレスポンスを$targetに関連付ける
	$.fn.vifHref =function(url, o) {
	
		o =o || {};
		
		return this.each(function() {
			$.vifHref($(this), url, o);
		});
	};
	$.vifHref =function($target, url, o) {
		
		$vifHref =(typeof url == "string")
				? $("<a/>").attr("href",url)
				: $(url);
		
		$vifHref.vifBindRequest($target,o);
		$vifHref.trigger(($vifHref.get(0).tagName == "FORM") ? "submit" : "click");
	};
	
	//-------------------------------------
	// $anchorのリクエストのレスポンスを$targetに関連付ける
	$.fn.vifSetTarget =function($target, o) {
	
		o =o || {};
		
		return this.each(function() {
			$.vifSetTarget($(this), $target, merge(o,{}));
		});
	};
	$.vifSetTarget =function($anchor, $target, o) {
		
		$anchor.vifBindRequest($target,merge(o,{}));
	};
	
	//-------------------------------------
	// 指定要素の発行するリクエストに割り込んでAjax通信に置き換える
	$.fn.vifBindRequest =function($target, o) {
	
		o =merge($.vifConfig,o);
		
		return this.each(function() {
			$.vifBindRequest($(this), $target, o);
		});
	};
	$.vifBindRequest =function ($anchor, $target, o) {

		var onBoundReuqest =function(e){ 
		
			var $anchor =$(e.currentTarget);
			var tagName =$anchor.get(0).tagName;
				
			// リンクの場合
			if (tagName == "A" && e.type == "click") {
			
				o.ajaxOptions.type ="GET";
				o.ajaxOptions.url =$anchor.attr("href");
				
			// フォームの場合
			} else if (tagName == "FORM" && e.type == "submit") {
				
				o.ajaxOptions.type =$anchor.attr("method") || "GET";
				o.ajaxOptions.url =$anchor.attr("action");
				o.ajaxOptions.data =$anchor.serialize();
			
			// リクエスト以外には割り込まない
			} else {
				
				return true;
			}

			// target=_parent、_blank、ページ内アンカー、JS実行であれば割り込まない
			if ($anchor.attr("target") == "_parent"
					|| $anchor.attr("target") == "_blank"
					|| o.ajaxOptions.url.match(/^#/)
					|| o.ajaxOptions.url.match(/^javascript:/)) {
				
				return true;
			}
			
			// 外部リンクの扱い
			if (o.ajaxOptions.url.match(/^https?:\/\/.+/)
					&& o.ajaxOptions.url.indexOf(location.origin) !== 0) {
				
				// ignoreExternalLink=1であれば割り込まない（default:1）
				if (o.ignoreExternalLink) {
					
					return true;
					
				// 「Header Append Access-Control-Allow-Origin : *」でクロスドメイン対応
				// ※IE7以前は非対応
				} else {
				
					o.ajaxOptions.crossDomain =true;
				}
			}
			
			// 通信成功時
			o.ajaxOptions.success =function (data ,textStatus ,xhr) {
				
				if ( ! $target || ! $target.html || $target.length == 0) {
					
					// 指定した要素が存在しないエラー
					$.vifTriggerError("bound $target not-found",{
						target : $target,
						anchor : $anchor,
						o : o
					});
					
					return;
				}
				
				var innerHtml =data;
				
				// headタグ削除
				if (o.stripHead
						&&(innerHtml.indexOf("<head>")!==0 || 
						innerHtml.indexOf("<HEAD>")!==0)) {
					
					var innerHtml =innerHtml.replace(/<head>([\n\r]|.)*?<\/head>/im,'');
				}
				
				$target.html(innerHtml); 
				
				$anchor.trigger("vifSuccess",[$anchor,$target,data,xhr,o]);
				$target.trigger("vifSuccess",[$anchor,$target,data,xhr,o]);
			};
			
			// 通信失敗時
			o.ajaxOptions.error =function (xhr, textStatus, errorThrown) {
				
				// 通信エラー
				$.vifTriggerError("request error",{
					target : $target,
					anchor : $anchor,
					textStatus : textStatus,
					errorThrown : errorThrown,
					o : o
				});
				
				$target.trigger("vifError",[$anchor,$target,textStatus,xhr,o]);
				$anchor.trigger("vifError",[$anchor,$target,textStatus,xhr,o]);
			};
			
			// 通信前
			o.ajaxOptions.beforeSend =function (xhr) {
			
				$anchor.trigger("vifBefore",[$anchor,$target,xhr,o]);
				$target.trigger("vifBefore",[$anchor,$target,xhr,o]);
			};
			
			// 通信後
			o.ajaxOptions.complete	=function (xhr) {
			
				$anchor.trigger("vifAfter",[$anchor,$target,xhr,o]);
				$target.trigger("vifAfter",[$anchor,$target,xhr,o]);
			};
		
			$.ajax(o.ajaxOptions);
			
			return false;
		};

		// リクエスト発行イベントに常時割り込み
		if (o.delegate) {
		
			$anchor.on("click submit",o.delegate,onBoundReuqest);
		
		// リクエスト発行イベントに割り込み登録
		} else {

			var tagName =$anchor.get(0).tagName;
		
			if (tagName == "A") {
			
				$anchor.on("click",onBoundReuqest);
				
			} else if (tagName == "FORM") {
			
				$anchor.on("submit",onBoundReuqest);
				
			} else {
			
				$anchor.children("a").on("click",onBoundReuqest);
				$anchor.children("form").on("submit",onBoundReuqest);
			}
		}
	};
	
})(jQuery);