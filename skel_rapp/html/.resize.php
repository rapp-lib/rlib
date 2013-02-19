<?php
/*
	■使用例：
		<img src="{{'/.resize.php'|path_to_url}}?s=120x120&f=/abs_url_to_image/image.jpg"/>
		<img src="{{'/.resize.php'|path_to_url}}?s=x120&t=on&f=/abs_url_to_image/image.jpg"/>
	
	■設定：
		registry("ImageResize.resized_image_dir.default")
			縮小画像の保存先の指定
		
	■パラメータ：
		(1)画像ファイルのURLを指定 ★必須
		
			f=/abs_url_to_image/image.jpg
			
		(2)縮小サイズの指定 ★必須
			指定された長辺にあわせてリサイズ
			
			s=WIDTHxHEIGHT
			s=WIDTHx
			s=xHEIGHT
			s=SIZE
				
		(3)短辺に合せてトリミング
			指定すると画像の短辺に合せてトリミング
			指定しなければ画角維持
			
			t=on
			
	■特性：
		MD5でファイル名を生成
		キャッシュを作成して次回以降それを使用する
		生成したファイルをreadfile出力させることで実現
*/

	require_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;
	
	//-------------------------------------
	// start
	function __start () {
		
		// 終端処理の登録
		register_shutdown_webapp_function("__end");
		
		$webapp_log =& ref_globals("webapp_log");
		elapse("webapp");
		elapse("webapp.prepare");
		
		// 初期設定の適応
		start_webapp();
		
		elapse("webapp.prepare",true);
		elapse("webapp.action");
		
		// パラメータ
		$request_uri =$_REQUEST['f'];
		$request_size =$_REQUEST['s'];
		$request_trim_center =$_REQUEST['t']=="on" ? 1 : 0;
		
		//-------------------------------------
		// リクエスト情報の解釈
		$document_root_dir =registry("Path.document_root_dir");
		$html_dir =registry("Path.html_dir");
		$request_file =preg_replace(
				'!/$!','/index.html',$document_root_dir.$request_uri);
		
		// ファイル指定が不正
		if ( ! $request_file
				|| ! is_readable($request_file) 
				|| ! is_file($request_file) ) {
			
			report_error("Request file is-not readable",array(
				"url" =>$request_uri,
				"file" =>$request_file,
			));
		}
		
		$request_file_ext =preg_match('!(\.[^\/\.]+)$!',$request_file,$match)
				? $match[1]
				: "";
				
		$request_w =0;
		$request_h =0;
		
		if (preg_match('!^\d+$!',$request_size,$match)) {

			$request_w =0+$match[0];
			$request_h =0+$match[0];
			
		} elseif (preg_match('!^(\d*)x(\d*)$!',$request_size,$match)) {
		
			$request_w =0+$match[1];
			$request_h =0+$match[2];
		}
		
		// サイズ指定が不正
		if ( ! $request_w &&  ! $request_h) {
			
			report_error("Request size in-not valid",array(
				"size" =>$request_size,
			));
		}
		
		$save_dir =registry("ImageResize.resized_image_dir.default");
		$cache_file =$save_dir."/".md5($request_file)
				.".s_".$request_w."x".$request_h
				.".t_".$request_trim_center
				.$request_file_ext;
		
		// 要求されたファイルが無効
		if ( ! save_dir 
				|| ! is_writable($save_dir)
				|| ! is_dir($save_dir)) {
			report(registry("ImageResize"));
			report_error("Config error",array(
				"ImageResize.resized_image_dir.default" =>$save_dir,
			));
		}
		// キャッシュがなければ生成
		if ( ! file_exists($cache_file)) {
			
			// 画像の取り込み
			$image =new ImageHandler($request_file);
			$image_type =$image->get_type();
			
			// 要求されたファイルが無効
			if ( ! $image_type) {
				
				report_error("Request file format error",array(
					"file" =>$request_file,
				));
			}
			
			$image->squeeze($request_w,$request_h);
			
			if ($request_trim_center) {
				
				$image->trim_center();
			}
			
			$image->save($cache_file);
		}
		
		elapse("webapp.fetch",true);
		elapse("webapp",true);
		
		// 出力
		clean_output_shutdown(array("file"=>$cache_file));
		
		elapse("webapp.fetch",true);
		elapse("webapp",true);
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
		
		$webapp_log =& ref_globals("webapp_log");
		
		$webapp_log["ShutdownCause"] =$cause;
		$webapp_log["Elapsed"] =elapse();
		
		report("WebappLog",$webapp_log);
	}