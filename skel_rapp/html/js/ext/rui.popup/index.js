window.rui.Popup ={};

// 子ウィンドウに紐付く情報
window.rui.Popup.contexts ={};

//-------------------------------------
// 入力ポップアップを開く処理 
window.rui.Popup.open_input_dialog =function (_) {
	
	var url =_["url"] || "/";
	var feature =_["feature"] || "dependent=yes, menubar=no, toolbar=no, scrollbars=yes";
	var onload =_["onload"];
	var onclose =_["onclose"];
	var option =_["option"];
	
	var window_id ="_"+parseInt(Math.random()*1000000);
	var new_window =window.open(url,window_id,feature);
	
	// IEの場合は親子関係の紐付けを保つように処理
	if (navigator.appName.toLowerCase().indexOf('internet explorer')+1?1:0) {
		
		new_window.opener =window;
	}
	
	rui.Popup.contexts[window_id] ={
		id: window_id,
		child: new_window,
		parent: window,
		onclose: onclose,
		option: option
	};
	
	if (onload) {
	
		$(new_window).bind("load",function(){ 
			
			onload(new_window, window, rui.Popup.contexts[window_id]); 
		});
	}
	
	new_window.focus();
	
	return window_id;
};

//-------------------------------------
// 入力ポップアップを閉じる処理
window.rui.Popup.close_input_dialog =function (_) {

	var onclose =_["onclose"];
	var onerror =_["onerror"];
	var param =_["param"];
	
	var context =rui.Popup.get_opener_context();
	
	// 異常終了時の処理（親ウィンドウがない）
	if ( ! context) {
		
		if (onerror) {
			
			onerror("NO_OPENER");
		}
		
	} else {
		
		// 正常終了時の処理 
		if (context.onclose) {
		
			context.onclose(param, window, window.opener, context);
		}
		
		if (onclose) {
		
			onclose(param, window, window.opener, context);
		}
	}
	
	window.close();
};

//-------------------------------------
// ポップアップの開き元のパラメータを得る
window.rui.Popup.get_opener_context =function () {
	
	var window_id =window.name;
	
	// 親ウィンドウがない場合
	if ( ! window.opener  
			|| ! window.opener["rui"] 
			|| ! window.opener.rui["Popup"]
			|| ! window.opener.rui.Popup.contexts[window_id]) {
		
		return undefined;
	}
	
	return window.opener.rui.Popup.contexts[window_id];
};

//-------------------------------------
// 開いているポップアップを閉じる
window.rui.Popup.close_child =function (window_id) {
	
	// ID指定で閉じる
	if (window_id) {
		
		if (rui.Popup.contexts[window_id]
				&& rui.Popup.contexts[window_id]["child"]) {
			
			var child =rui.Popup.contexts[window_id]["child"];
			
			// 子孫もすべて閉じる
			if (child["rui"] && child.rui["Popup"]) {
				
				child.rui.Popup.close_child();
			}
			
			if (child["name"]) {
			
				child.close();
			}
		}
	
	// ID指定しなければ全て閉じる
	} else {
		
		for (var per_window_id in rui.Popup.contexts) {
			
			rui.Popup.close_child(per_window_id);
		}
	}
};

//-------------------------------------
// 
window.rui.Popup.bind_close_input_dialog =function (_) {
	
	var target =_["target"] || ".closeInputDialog";
	var onclose =_["onclose"];
	var onerror =_["onerror"];
	
	// リンクのクリックでGETパラメータをcloseに渡す
	$("a"+target).bind("click",function(){
		var param =rui.decode_query($(this).attr("href"));
		rui.Popup.close_input_dialog({
			onclose: onclose,
			onerror: onerror,
			param: param
		});
		return false;
	});
	
	// formの送信で入力値をcloseに渡す
	$("form"+target).bind("submit",function(){
		var param ={};
		$.each($(this).serializeArray(),function(i,field){
			param[field.name] =field.value;
		});
		rui.Popup.close_input_dialog({
			onclose: onclose,
			onerror: onerror,
			param: param
		});
		return false;
	});
};

//-------------------------------------
// URLパラメータを解析 
window.rui.decode_query =function (str) {

	var dec = decodeURIComponent;
	var par = new Array;
	var itm;
	
	if(typeof(str) == 'undefined') return par;
	if(str.indexOf('?', 0) > -1) str = str.split('?')[1];
	
	str = str.split('&');
	
	for(var i = 0; str.length > i; i++){
		
		itm = str[i].split("=");
		
		if(itm[0] != ''){
			
			par[itm[0]] = typeof(itm[1]) == 'undefined' ? true : dec(itm[1]);
		}
	}
	
	return par;
};

//-------------------------------------
// 配列からURLパラメータを作成
window.rui.encode_query =function (par) {

	var enc = encodeURIComponent;
	var str = '', amp = '';
	
	if(!par) return '';
	
	for(var i in par){
		
		str = str + amp + i + "=" + enc(par[i]);
		amp = '&'
	}
	
	return str;
};