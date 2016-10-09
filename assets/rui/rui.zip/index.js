/*
■API:
	rui.Zip.load(
		【郵便番号】,
		【zipjsonフォルダのURL】,
		【成功時のcallback】,
			function ({
				pref_code: 県ID
				pref: 県名
				addr: 市町村以下合わせたもの
				city: 市町村
				area: 町名
				street: 番地
			})
		【失敗時のコールバック】
			function(失敗理由コード,郵便番号)
	);

■Zipデータの場所:
	ここで配布されています:
		http://www.kawa.net/works/ajax/ajaxzip2/ajaxzip2.html
		※最新版をJPから取ってコンバートする方法もここにあります
		※コードもこれを参考にしました

	このあたりに配置してあります:
		dev.sharingseed.info toyosawa /test/zip/zipjson/
		dev.sharingseed.info toyosawa /test/zip/zipjson.zip

■Sample1:
	このあたりに配置してあります:
		dev.sharingseed.info toyosawa /test/zip/sample.php

■Sample2:
	<script>
	$(function(){
		$("#zipLoadButton").bind("click",function(){
			rui.Zip.load(
				$("input[name=zip]").val(),
				"//ajaxzip3.googlecode.com/svn/trunk/ajaxzip3/zipdata/",
				function(data){
					//$("select[name=pref]").val(data.pref_code);
					$("input[name=pref]").val(data.pref);
					$("input[name=addr]").val(data.addr);
				},
				function(reason,nzip){
					alert("郵便番号が不正です");
				}
			);
		});
	});
	</script>
	郵便番号: <input type="text" name="zip" size="9" maxlength="8"/> <br/>
	<a href="javascript:0;" id="zipLoadButton">住所自動入力</a><br/>
	都道府県: <input type="text" name="addr" size="40"><br/>
	市区町村番地: <input type="text" name="addr" size="40"><br/>
	それ以降: <input type="text" name="street" size="40"><br/>
*/

rui.Zip =function () {};
rui.Zip.cache =[];
rui.Zip.prefs =[
    null,       '北海道',   '青森県',   '岩手県',   '宮城県',
    '秋田県',   '山形県',   '福島県',   '茨城県',   '栃木県',
    '群馬県',   '埼玉県',   '千葉県',   '東京都',   '神奈川県',
    '新潟県',   '富山県',   '石川県',   '福井県',   '山梨県',
    '長野県',   '岐阜県',   '静岡県',   '愛知県',   '三重県',
    '滋賀県',   '京都府',   '大阪府',   '兵庫県',   '奈良県',
    '和歌山県', '鳥取県',   '島根県',   '岡山県',   '広島県',
    '山口県',   '徳島県',   '香川県',   '愛媛県',   '高知県',
    '福岡県',   '佐賀県',   '長崎県',   '熊本県',   '大分県',
    '宮崎県',   '鹿児島県', '沖縄県'
];
rui.Zip.load =function (vzip, data_url, callback, callback_error) {

	if ( ! data_url) {

		data_url ="./zipjson/";
	}
	if ( ! callback) {

		callback =function(){};
	}
	if ( ! callback_error) {

		callback_error =function(){};
	}

    // 郵便番号を数字のみ7桁取り出す
    var nzip = '';
    for( var i=0; i<vzip.length; i++ ) {
        var chr = vzip.charCodeAt(i);
        if ( chr < 48 ) continue;
        if ( chr > 57 ) continue;
        nzip += vzip.charAt(i);
    }
    if ( nzip.length < 7 ) return callback_error("format_error",nzip);

    // 郵便番号上位3桁でキャッシュデータを確認
    var data = rui.Zip.cache[nzip.substr(0,3)];
    if ( data ) return rui.Zip.parseZipJson(nzip,data,callback,callback_error);

    // JSONファイルを受信する
    jQuery.ajax({
		url: data_url+'/zip-'+nzip.substr(0,3)+'.json',
		success : function (data) {
		    rui.Zip.cache[nzip.substr(0,3)] = data;
			rui.Zip.parseZipJson(nzip,data,callback,callback_error);
		},
		error : function (xhr,status) {
			return callback_error("request_error",nzip);
		},
		async: false,
		dataType: "json"
	});
};
rui.Zip.parseZipJson =function (nzip,data,callback,callback_error) {

    var array = data[nzip];

	// Opera バグ対策：0x00800000 を超える添字は +0xff000000 されてしまう
    var opera = (nzip-0+0xff000000)+"";
    if ( ! array && data[opera] ) array = data[opera];

	if ( ! array || ! array[0]) return callback_error("response_error",nzip);

	return callback({
	    pref_code: array[0], // 県ID
	    pref: rui.Zip.prefs[array[0]], // 県名
	    city: (array[1]||""), // 市区町村名
	    area: (array[2]||""), // 町域名
	    street: (array[3]||""), // 番地
	    addr: (array[1]||"")+(array[2]||"")+(array[3]||"") // 市区町村名番地
	});
};
