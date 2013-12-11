	
	// file(LRA)要素を非同期で送信する機能
	$.vifUpload =function($upload){
		
		// アップロード用のフォームを作成
		var $tmp_form =$('<form action="#" method="post"'
				+' enctype="multipart/form-data" target="_blank"'
				+' style="display:none;"></form>');
		
		var $root_form =$upload.parents("form").eq(0);
		var $container =$upload.parents(".vifUploadContainer");
		var $container_html =$container.html();
		var $container_contents =$container.children();
		
		var $uploading_message =$('<span>アップロード中...</span>');
		var $cancel_btn =$('<a href="javascript:void(0);">(キャンセル)</a>');
		
		// 上位のフォームに未完了チェックを追加
		var uploading =true;
		
		$root_form.on("submit",function(e){
			
			if (uploading) {
				
				if ( ! confirm("アップロード中のファイルがあります。"
						+"中断して先へ進んでもよろしいですか？")) {
					
					e.preventDefault();
					return false;
				}
			}
		});
		
		// 送信キャンセル
		var cancelUpload =function(){
			
			uploading =false;
			$container.html($container_html);
			$tmp_form.remove();
		};
		
		// 要素の一時的な移動
		$container.html("");
		$container.append($uploading_message);
		$cancel_btn.on("click",cancelUpload);
		$container.append($cancel_btn);
		
		$tmp_form.appendTo("body");
		$tmp_form.append($container_contents);
		
		// 確認画面へ送信してLRAから結果をJSONで戻させる
		$tmp_form.attr("action",$root_form.attr("action"));
		$tmp_form.find(".lraResponse").attr("value","json");
		
		// iframeで非同期送信
		$.vifRequestByIframe($tmp_form,{
			
			// 通信成功時
			success : function (response) {
				
				// アップロード終了済判定
				if ( ! uploading) {
					
					return;
				}
				
				$container.html($container_html);
				$tmp_form.remove();
						
				response =jQuery.parseJSON(response);
				
				if (response && response.status) {
					
					// 正常なアップロード完了
					if (response.status == "success") {
					
						$container.find(".lraValue").attr("value",response.code);
						$container.find("a.uploadedFile").attr("href",response.url);
						$container.find("img.uploadedFile").attr("src",response.url);
						$container.find(".uploadedSet").show();
					
					// アップロードファイル理由による受付拒否
					} else if (response.status == "denied") {
						
						// 拡張子エラー
						if (response.reason == "ext_denied") {
							
							$container.find(".messageArea").html(
									response.ext+" ファイルはアップロードできません");
							
						} else {
						
							$container.find(".messageArea").html("アップロードできませんでした");
						}
						
					// システムエラー
					} else if (response.status == "error") {
					
						$container.find(".messageArea").html("エラーが発生しました");
						
						if (console && console.log) {
							
							console.log(response)
						}
					}
					
					uploading =false;
				}
			},
			
			// 通信エラー
			error : function () {
			
				uploading =false;
				$container.html($container_html);
				$tmp_form.remove();
					
				$container.find(".messageArea").html("エラーが発生しました");
			},
			
			complete : function () {},
			beforeSend : function () {}
		});
	};
	
	// .vifUpload のファイルアップロードを検出
	$(document).ready(function(){
		
		$(document).on("change","input[type=file].vifUpload",function(){
		
			$.vifUpload($(this));
		});
	});