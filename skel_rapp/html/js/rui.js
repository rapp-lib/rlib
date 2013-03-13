
//-------------------------------------
// UIライブラリ
window.rui ={};

window.rui.ext_url ="";
window.rui.ext_loaded ={ js:{}, css:{}, pkg:{} };

//-------------------------------------
// 機能の初期化
window.rui.init =function (ext_relative_url) {
	
	rui.ext_url =location.protocol+"//"+location.host+ext_relative_url;
	
	// console機能の補完
	if ( ! ('console' in window)) { 
		
		window.console ={ log: function (s) { return s; } }; 
	};
};

//-------------------------------------
// JS読み込み
window.rui.require_js =function (ext_file) {
	
	if ( ! rui.ext_loaded.js[ext_file]) {
		
		$("head").append($("<script/>").attr({
			language: 'javascript',
			charset: 'UTF-8',
			src: rui.ext_url+"/"+ext_file
		}));
		rui.ext_loaded.js[ext_file] =true;
	}
};

//-------------------------------------
// CSS読み込み
window.rui.require_css =function (ext_file) {
	
	if ( ! rui.ext_loaded.css[ext_file]) {
		
		$("head").append($("<link/>").attr({
			rel: 'stylesheet',
			type: 'text/css',
			href: rui.ext_url+"/"+ext_file
		}));
		rui.ext_loaded.css[ext_file] =true;
	}
};

//-------------------------------------
// パッケージ読み込み
window.rui.require =function (ext_pkg_name, callback) {
	
	if (typeof(ext_pkg_name) == "object") {
	
		for (var i in ext_pkg_name) {
		
			rui.require(ext_pkg_name[i]);
		}
		
	} else {
		
		if ( ! rui.ext_loaded.pkg[ext_pkg_name]) {
			
			rui.require_js(ext_pkg_name+"/index.js");
			rui.ext_loaded.pkg[ext_pkg_name] =true;
		}
	}
	
	if (callback) { 
		
		callback({loaded: ext_pkg_name}); 
	}
};