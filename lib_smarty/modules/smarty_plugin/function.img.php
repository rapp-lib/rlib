<?php

	function smarty_function_img ($params, &$smarty) {
		
		$op_keys =array(
			'src',
			'alt_html',
			'resize',
			'size',
			'downsize'
		);
		
		usefunc("get_relative_path");
		
		// src指定
		if (registry("file_mfu_virtual_chdir")) {
		
			$real_chdir =getcwd();
			
			chdir(registry("file_mfu_virtual_chdir"));
			
			$src =get_relative_path($params['src']);
			
			chdir($real_chdir);
			
		} else {
		
			$src =get_relative_path($params['src']);
		}
		
		// 画像ファイルが指定されていなければimgタグを出力しない	
		if( ! ($src && file_exists($src)) || is_dir($src) ){
		
			return $params['alt_html'];
		}
					
		// resize指定
		if ($src && $params["resize"] && 
				preg_match('!^([^#]+)(#(.+?)x(.+?))?$!',
				$params["resize"],$match)) {
			
			usefunc("resize_image");
			
			$src =resize_image($src,$match[1],array(
				"width" =>$match[3],
				"height" =>$match[4],
				"cache_dir" =>registry("resize_image_dir"),
				"downsize" =>$params["downsize"],
			));	
		}
					
		// size指定
		if ($src && $params["size"] && 
				preg_match('!^([^#]+)(#(.+?)x(.+?))?$!',
				$params["size"],$match)) {
			
			usefunc("resize_image");
		
			$image_size =calculate_image_size(
					$image_path,
					$mode,
					$match[3],
					$match[4]);
			
			$params["width"] =$image_size["width"];
			$params["height"] =$image_size["height"];
		}
		
		$attr_html ="";
		
		// パラメータの選別
		foreach (array_keys($params) as $key) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$params[$key].'"';
			}
		}
		
		// キャッシュ制御文字列付加
		$src =$src.(strpos($src,"?")===false ? "?" : "&").time();
		
		$html ="";
		$html .='<img'.$attr_html.' src="'.$src.'" />';
		
		return $html;
	}
	