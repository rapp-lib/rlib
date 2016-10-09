/*
■使い方
		ZipCoder.load([ 郵便番号の文字列 ],function(addr){
			[ addrの構造 ]
				addr.pref_code: 県ID
				addr.pref: 県名
				addr.addr: 市町村以下合わせたもの
				addr.city: 市町村
				addr.area: 町名
				addr.street: 番地 
				※郵便番号の読み込みで問題がある場合は、addr=falseで呼び出される
		});	

■サンプル
	<script src="{{"/js/ZipCoder/ZipCoder.js"|path_to_url}}" charset="utf-8"></script>
	<script>
	$(".addrset .zipcodeButton").on("click",function(){

		var $button =$(this);
		var $addrset =$button.parents(".addrset");
		var zipcode_str =$addrset.find(".zip1").val()+""+$addrset.find(".zip2").val();
		
		ZipCoder.load(zipcode_str,function(addr){
			if ( ! addr) {
				alert("郵便番号が不正です");
			}
			$addrset.find(".pref_code").val(addr.pref_code);
			$addrset.find(".pref_name").val(addr.pref_name);
			$addrset.find(".addr1").val(addr.addr1);
			$addrset.find(".addr2").val(addr.addr2);
		});

		return false;
	});
	</script>
	<div class="addrset">
		<p>
			<input type="text" name="zip1"/> - <input type="text" name="zip2"/>
			<button class="zipcodeButton">郵便番号から住所検索</button>
		</p>
		<p><input type="hidden" name="pref_code"/><input type="input" name="pref_name"/></p>
		<p><input type="text" name="addr1"/></p>
		<p><input type="text" name="addr2"/></p>
	</div><!--/.addrset-->
*/

/**
 * 郵便番号から住所を検索する
 */
window.ZipCoder = {

	/**
	 * 郵便番号JSデータのホスト先URL
	 */
	data_url: "https://yubinbango.github.io/yubinbango-data/data", 

	/**
	 * 郵便番号データの読み込み
	 */
	load : function (code_str, onload) {
		
		// 郵便番号文字列の正規化 → \d{7}形式
		var code = code_str.replace(/[０-９]/g, function(t) {
			return String.fromCharCode(t.charCodeAt(0) - 65248) 
		}).match(/\d/g).join("");
		
		if (7 !== code.length) { 

			onload(false); 
		}

		var s = code.substr(0, 3);

		if (ZipCoder._cache[s]) {
			
			onload(ZipCoder._select_addr(s,code));
		
		} else {
			
			// JSONPの読み込み
			window.$yubin = function(data) {
				ZipCoder._cache[s] =data;
				onload(ZipCoder._select_addr(s,code));
			};
			var t = document.createElement("script");
			t.setAttribute("type", "text/javascript");
			t.setAttribute("charset", "UTF-8");
			t.setAttribute("src", ZipCoder.data_url+"/"+s+".js");
			document.head.appendChild(t);
		}
	},

	_select_addr : function (s,code) {
		var t =ZipCoder._cache[s][code];
		return t[0] && t[1] ? { 
			pref_code: t[0],
			city: t[1],
			area: t[2],
			street: t[3],
			pref: ZipCoder._pref_names[t[0]],
			addr: t[1]+t[2]+t[3]
	   	} : false;
	},

	_pref_names : [
		null, "北海道", "青森県", "岩手県", "宮城県", "秋田県", "山形県", 
		"福島県", "茨城県", "栃木県", "群馬県", "埼玉県", "千葉県", "東京都", "神奈川県", 
		"新潟県", "富山県", "石川県", "福井県", "山梨県", "長野県", "岐阜県", "静岡県", 
		"愛知県", "三重県", "滋賀県", "京都府", "大阪府", "兵庫県", "奈良県", "和歌山県", 
		"鳥取県", "島根県", "岡山県", "広島県", "山口県", "徳島県", "香川県", "愛媛県", 
		"高知県", "福岡県", "佐賀県", "長崎県", "熊本県", "大分県", "宮崎県", "鹿児島県", "沖縄県"
	],

	_cache : {}
};
