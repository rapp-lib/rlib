<?php
/*
	Formatの指定例:
		100x50 ... 100x50以内に長辺を収めて縮小
		x100 ... 100以内に高さを収める
		100x ... 100以内に幅を収める
		100 ... 100x100と同義
		100-t ... 100に短編を合わせて、100x100の正方形にトリミング
	
	キャッシュファイルの保存場所の設定
		registry("UserFile.resize_dir") または、
		registry("UserFile.user_file_dir")."/resized"
*/
	
//-------------------------------------
// リサイズ画像の動的生成機能
class ResizeImage {
	
	//-------------------------------------
	// キャッシュファイル基本名を作成
	public function get_cache_key ($src_file, $format) {
	
		$hash_level =3;
		$hash_table =array_splice(preg_split('!!',md5($src_file)),1,$hash_level);
		
		return implode("/",$hash_table)."/".substr(md5($src_file),3,5)."_".$format."_".basename($src_file);
	}
	
	//-------------------------------------
	// リクエストからリサイズ済み画像ファイルを生成
	public function resize_by_request ($request) {

		// 設定
		$save_dir =registry("UserFile.resize_dir")
				? registry("UserFile.resize_dir")
				: registry("UserFile.user_file_dir")."/resized";
				
		// リクエスト
		$src_file =registry("Path.document_root_dir").$request['file_url'];
		$format =$request['format'];
		
		// ファイル指定の確認
		if ( ! $src_file || ! is_readable($src_file) 
				|| ! is_file($src_file) || ! is_public_file($src_file)) {

			report_warning("Source file is-not readable",array(
				"file" =>$src_file,
				"is_readable" =>is_readable($src_file),
				"is_file" =>is_file($src_file),
				"is_public" =>is_public_file($src_file),
			));
			
			return false;
		}

		// フォーマット指定の解釈
		$size_w =0;
		$size_h =0;
		$mode ="resize";

		if (preg_match('!^(\d+)(-t)?$!',$format,$match)) {

			$size_w =0+$match[1];
			$size_h =0+$match[1];
			$mode =$match[2] ? "trim_center" : $mode;

		} elseif (preg_match('!^(\d*)x(\d*)(-t)?$!',$format,$match)) {

			$size_w =0+$match[1];
			$size_h =0+$match[2];
			$mode =$match[3] ? "trim_center" : $mode;
		
		} else {
			
			report_warning("Request format is invalid",array(
				"format" =>$format,
			));
			
			return false;
		}

		// 書き込み先の確認
		if ( ! $save_dir || ! is_writable($save_dir) || ! is_dir($save_dir)) {

			report_warning("Config error, not writable",array(
				"save_dir" =>$save_dir,
			));
			
			return false;
		}
		
		$cache_file =$save_dir."/".$this->get_cache_key($src_file,$format);

		// キャッシュがないか、古ければければ生成
		if ( ! file_exists($cache_file)
				|| filemtime($cache_file) < filemtime($src_file)) {

			// 画像の取り込み
			$image =new ImageHandler($src_file);
			$image_type =$image->get_type();

			// 要求されたファイルが無効
			if ( ! $image_type) {

				report_warning("Source file type error",array(
					"file" =>$src_file,
				));
				
				return false;
			}
			
			if ($mode == "resize") {
					
				$image->squeeze($size_w,$size_h);
			
			} elseif ($mode == "trim_center") {

				$image->square();
				$image->squeeze($size_w,$size_h);
			}

			// フォルダ作成
			if ( ! file_exists(dirname($cache_file))) {
				
				mkdir(dirname($cache_file),0775,true);
			}
	
			$image->save($cache_file);
		}
		
		return $cache_file;
	}
}