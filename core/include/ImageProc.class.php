<?php

/*
	●サンプルコード：
	
		registry(array(
			"ImageProc.engine" =>"gd",
			"ImageProc.cache_dir" =>realpath(registry("Path.html_dir").'/save/image_cache),
		));
		
		obj("ImageProc")->resize_boxed($uploaded_image,
				array("width"=>120,"height"=>120));
*/

//-------------------------------------
// 画像処理
class ImageProc {
	
	protected $engine;
	protected $cache_dir;
	
	//-------------------------------------
	// 初期化
	public function __constract () {
		
		if (registry("ImageProc.engine") == "gd") {
			
			require_once(dirname(__FILE__)."/ImageProc/ImageProcImpl_GD.class.php");
			$this->engine =new ImageProcImpl_GD;
			
		} elseif (registry("ImageProc.engine") == "im") {
			
			require_once(dirname(__FILE__)."/ImageProc/ImageProcImpl_IM.class.php");
			$this->engine =new ImageProcImpl_IM;
		}
		
		$this->cache_dir =registry("ImageProc.cache_dir");
	}
	
	//-------------------------------------
	// 実装呼び出し
	public function __call ($f,$a) {
		
		$method =$f;
		list($src_file,$options)=$a;
		
		// 書き出し先の確認
		$dest_file =$this->get_dest_file($method,$src_file,$options);
		
		// キャッシュ確認
		if ($this->check_cache($src_file,$dest_file)) {
			
			touch($dest_file);
		
			return $dest_file;
		}
		
		// 実装の呼び出し手続き
		if ( ! method_exists($this->engine,$method)) {
			
			report_error("Method not supported.",array(
				"method" =>$method,
				"engine" =>$this->engine,
			));
		}
		
		$result =call_user_func_array(array($this->engine,$method),array(
			$src_file,
			$dest_file,
			$options,
		));
		
		return $result
				? $dest
				: null;
	}
	
	//-------------------------------------
	// 出力ファイル名の決定
	protected function get_dest_file (
			$method,
			$src_file,
			$options=array()) {
		
		$dest_file =$options["dest_file"];
		
		// ImageProc.cache_dirを利用した自動決定
		if ( ! $dest_file) {
			
			if ( ! $this->cache_dir) {
				
				report_error("Setting ImageProc.cache_dir is-not valid.");
			}
			
			$src_file =realpath($src_file);
			$options =print_r($options,true);
			$dest_file =$this->cache_dir."/".md5($src_file."/".$method."/".$options);
		}
			
		if ( ! is_writable(dirname($dest_file))) {
			
			report_error("Setting dest_file dir is-not writable.",array(
				"dest_file" =>$dest_file,
			));
		}
		
		return $dest_file;
	}
	
	//-------------------------------------
	// キャッシュの存在と時刻の確認
	protected function check_cache ($src_file, $dest_file) {
		
		// ファイルが未生成
		if ( ! file_exists($dest_file)) {
			
			return false;
		}
		
		// 作成時刻が古い
		if (filectime($dest_file) < filectime($src_file)) {
			
			return false;
		}
		
		return true;
	}
}