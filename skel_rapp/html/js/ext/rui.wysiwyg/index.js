/**
-------------------------------------
rui.wysiwig 

rev:2013/02/08.modeac Y.Toyosawa
-------------------------------------	

■概要：

	wysiwigクラスを指定するとWysiwygになる。

■使用例：

	<textarea class="wysiwig"></textarea>
*/

	rui.require_js("rui.wysiwyg/nicEdit.js");
	rui.require_css("rui.wysiwyg/nicEdit.css");
	
	//-------------------------------------
	// WysiwigテキストエリアUI
	window.rui.Wysiwyg ={};
	window.rui.Wysiwyg.instances ={};
	
	//-------------------------------------
	// テキストエリアにWysiwygUIを付加
	window.rui.Wysiwyg.init =function ($elm) {
		
		var elm_id =$elm.attr("id");
		
		if ( ! elm_id) {
		
			elm_id ="wys"+Math.ceil(Math.random()*999999);
			$elm.attr("id",elm_id);
		}
		
		rui.Wysiwyg.instances[elm_id] = new nicEditor({
			fullPanel : true,
			convertToText : false,
			iconsPath : rui.ext_url+"/rui.wysiwyg/nicEditorIcons.gif"
		});
		rui.Wysiwyg.instances[elm_id].panelInstance(elm_id);
	};
	
	bkLib.onDomLoaded(function(){
	
		$(".wysiwyg").each(function(){
		
			rui.Wysiwyg.init($(this));
		});
		
		$(document).trigger("loadWysiwyg");
	});