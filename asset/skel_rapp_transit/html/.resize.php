<?php
/*
	【★DEPRECATED】modifierでの実装を推奨
		<img src="{{$t.Manager.profile_image|userfile:'public'|resize:'80-t'|or:'/dummy-img.png'}}"/>
	
	Params:
		f: 画像ファイルのURLを指定
		s: フォーマットを指定
			100x50 ... 100x50以内に長辺を収めて縮小
			x100 ... 100以内に高さを収める
			100x ... 100以内に幅を収める
			100 ... 100x100と同義
			100-t ... 100に短編を合わせて、100x100の正方形にトリミング

	Sample:
		<img src="/.resize.php?s=120x120&f=/abs_url_to_image/image.jpg"/>
		<img src="/.resize.php?s=x120-t&f=/abs_url_to_image/image.jpg"/>
*/

	require_once(dirname(__FILE__)."/../config/config.php");

	__start();
	exit;

	//-------------------------------------
	// start
	function __start () {

		// 終端処理の登録
		register_shutdown_webapp_function("__end");

		// 初期設定の適応
		start_webapp();
		
		$cache_file =obj("ResizeImage")->resize_by_request(array(
			"file_url" =>$_REQUEST["f"],
			"format" =>$_REQUEST["s"].($_REQUEST["t"] ? "-t" : ""),
		));
		
		if ( ! $cache_file) {
			
			report_error("ResizeImage proccess error");
		}
		
		// 出力
		clean_output_shutdown(array("file"=>$cache_file));

		shutdown_webapp("normal");
	}

	//-------------------------------------
	// __end
	function __end ($cause, $options) {
	}
