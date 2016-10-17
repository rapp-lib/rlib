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

	<!-- 下記のタグを入れたページはViframeでは読み込まれない（ログイン画面など） -->
	<a class="vifPreventLoad"></a>

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
		ignoreExternalLink : true ... 外部リンクへのリクエストは無視する
*/

(function($){

	//-------------------------------------
	// オプションのマージ処理
	var merge =function(a,b) {

		var c ={};
		for (var k in a) { c[k] =a[k]; }
		for (var k in b) { c[k] =b[k]; }
		return c;
	};

	//-------------------------------------
	// オプションのClone処理
	var objClone = function (obj) {
		var clone = new (obj.constructor);
		for (var p in obj) {
			clone[p] = typeof obj[p] == 'object' ? objClone(obj[p]) : obj[p];
		}
		return clone;
	}
	var arrayClone = function(obj){
		var clone = [];
		for (var i = 0, l = obj.length; i < l; i++) {
			clone[i] = typeof obj[i] == 'object' ? obj[i].clone() : obj[i];
		}
		return clone;
	}

	//-------------------------------------
	// デフォルトオプション
	$.vifConfig ={
		ajaxOptions : {},
		delegate : "",
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
	$.vifBindRequest =function ($anchor, $target, oOrigin) {

		var o =objClone(oOrigin) || {};
		var onBoundReuqest =function(e){

			var o =objClone(oOrigin) || {};
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

				o.ajaxOptions.data =o.ajaxOptions.data || {};

				var formData =$anchor.serializeArray();

				for (var i in formData) {
					var inputName = formData[i]["name"];
					if (inputName.match(/^(.*?)\[\]$/)) {
						inputName = RegExp.$1;
						if ( ! o.ajaxOptions.data[inputName]) {
							o.ajaxOptions.data[inputName] = [];
						}
						o.ajaxOptions.data[inputName].push(formData[i]["value"]);
					} else {
						o.ajaxOptions.data[inputName] =formData[i]["value"];
					}
				}

			// リクエスト以外には割り込まない
			} else {

				return true;
			}

			// target=_parent、_blank、ページ内アンカー、JS実行であれば割り込まない
			if ($anchor.attr("target") == "_parent"
					|| $anchor.attr("target") == "_blank"
					|| ( $anchor.attr("target")
						&& $anchor.attr("target") != $target.attr("id") )
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

			// Header追加
			o.ajaxOptions.headers =o.ajaxOptions.headers || {};

			o.ajaxOptions.headers["X-Vif-Request"] ="1";
			o.ajaxOptions.headers["X-Vif-Target-Id"] =$target.attr("id") || "noname";

			if (o.headers) {

				for (var i in o.headers) {

					o.ajaxOptions.headers[i] =o.headers[i];
				};
			}

			// POSTパラメータ追加
			o.ajaxOptions.data =o.ajaxOptions.data || {};

			if (o.data) {

				for (var i in o.data) {

					o.ajaxOptions.data[i] =o.data[i];
				};
			}

			// 通信成功時
			o.ajaxOptions.success =function (data ,textStatus ,xhr) {

				var vifResponseTargetId =xhr.getResponseHeader("X-Vif-Target-Id");

				if (vifResponseTargetId && $target.attr("id") != vifResponseTargetId) {

					$target =$("#"+vifResponseTargetId);
				}

				// 指定した要素が存在しない
				if ( ! $target || ! $target.html || $target.length == 0) {

					$.vifTriggerError("bound $target not-found",{
						target : $target,
						anchor : $anchor,
						o : o
					});

					return;
				}

				var $innerHtml =$(data);

				// .vifPreventLoadがある場合
				if ($innerHtml.find(".vifPreventLoad").length) {

					$.vifTriggerError(".vifPreventLoad would-not loaded",{
						target : $target,
						anchor : $anchor,
						data : $innerHtml,
						o : o
					});

					return;
				}

				$target.html(data);

				o.onSuccess && o.onSuccess($anchor,$target,data,xhr,o);
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

				o.onError && o.onError($anchor,$target,textStatus,xhr,o);
				$target.trigger("vifError",[$anchor,$target,textStatus,xhr,o]);
				$anchor.trigger("vifError",[$anchor,$target,textStatus,xhr,o]);
			};

			// 通信前
			o.ajaxOptions.beforeSend =function (xhr) {

				o.onBeforeSend && o.onBeforeSend($anchor,$target,xhr,o);
				$anchor.trigger("vifBefore",[$anchor,$target,xhr,o]);
				$target.trigger("vifBefore",[$anchor,$target,xhr,o]);
			};

			// 通信後
			o.ajaxOptions.complete	=function (xhr) {

				o.onComplete && o.onBeforeSend($anchor,$target,xhr,o);
				$anchor.trigger("vifAfter",[$anchor,$target,xhr,o]);
				$target.trigger("vifAfter",[$anchor,$target,xhr,o]);
			};

			// 通信を行う関数の指定がある場合
			if (o.requestFunction) {

				o.requestFunction($anchor, $target, o.ajaxOptions);

			// file要素がある場合はiframeでRequest
			} else if ($anchor.find('input:file').length) {

				$.vifRequestByIframe($anchor, o.ajaxOptions);

			// AJAXでリクエスト
			} else {

				$.ajax(o.ajaxOptions);
			}

			e.preventDefault();
			e.stopPropagation();
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

	//-------------------------------------
	// iframeを使用したAjax実装
	var iframeUuid =0;
	$.vifRequestByIframe = function ($anchor, ajaxOptions) {

		var iframeName ='jquery_upload'+(++iframeUuid);
		var $iframe =$('<iframe id="'+iframeName+'" name="'+iframeName+'"/>');
		$iframe.attr("style","position:absolute;top:-9999px");
		$iframe.appendTo('body');

		$iframe.on("load",function(){
			setTimeout(function() {

				var contents =$iframe.contents().get(0);

				// 応答処理
				if ($.isXMLDoc(contents) || contents.XMLDocument) {

					// XML応答処理
					var parseXml =function (text) {
						if (window.DOMParser) {
							return new DOMParser().parseFromString(text, 'application/xml');
						} else {
							var xml = new ActiveXObject('Microsoft.XMLDOM');
							xml.async = false;
							xml.loadXML(text);
							return xml;
						}
					}

					ajaxOptions.success(parseXml(contents.XMLDocument || contents));

				} else if ($(contents).find('body').length) {

					// HTML応答の処理
					ajaxOptions.success($(contents).find('body').html());

				} else {

					// エラー処理
					ajaxOptions.error();
				}

				// 送信後
				ajaxOptions.complete();
			},0);
		});

		// 送信前
		ajaxOptions.beforeSend();

		$anchor.attr("target",iframeName);
		$anchor.trigger("submit");
	}

	//-------------------------------------
	// .viframe要素を全てviframe読み込み
	$(function(){

		$(".viframe").each(function(){

			var $vifHref =$(this).children(".vifHref");
			var url =$vifHref.length > 0 ? $vifHref : "";

			$(this).viframe(url);
		});
	});
})(jQuery);