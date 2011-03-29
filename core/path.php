<?php
	
	//-------------------------------------
	// page_to_pathのマップを得る
	function & get_page_to_path_map ($flip=false) {
		
		static $cache;
		
		if ( ! $cache) {
			
			foreach ((array)registry("Routing.page_to_path") as $k1 => $v1) {
				
				foreach ($v1 as $k2 => $v2) {
					
					$cache["page_to_path"][$k1.".".$k2] =$v2;
				}
			}
			
			$cache["path_to_page"] =array_flip($cache["page_to_path"]);
		}
		
		return $cache[$flip ? "path_to_page" : "page_to_path"];
	}
	
	//-------------------------------------
	// 相対Path指定の解決
	function relative_path ($path) {
	
		if (preg_match('!^\.(.*)$!',$path,$match)) {
		
			$path =file_to_url(dirname(registry("Request.request_file"))).$path;
		}
		
		return $path;
	}
	
	//-------------------------------------
	// 相対Page指定の解決
	function relative_page ($page) {
	
		if (preg_match('!^([^\.]*?)(\.?)([^\.]*?)$!',$page,$match)) {
		
			$page ="";
			$page .=$match[1] ? $match[1]
					: registry("Request.controller_name");
			$page .=".";
			$page .=$match[3] ? $match[3]
					: registry("Request.action_name");
		}
		
		return $page;
	}
	
	//-------------------------------------
	// PathからPageを得る（主にRouting時に使用）
	function path_to_page ($path) {
		
		$path =relative_path($path);
		$path_to_page =& get_page_to_path_map(true);
		$page =$path_to_page[$path];
		
		return $page;
	}
	
	//-------------------------------------
	// PageからPathを得る
	function page_to_path ($page) {
		
		$page =relative_page($page);
		$page_to_path =& get_page_to_path_map();
		$path =$page_to_path[$page];
		
		return $path;
	}
	
	//-------------------------------------
	// Pathからファイル名を得る
	function path_to_file ($path) {
		
		$path =relative_path($path);
		return registry("Path.html_dir").$path;
	}

	//-------------------------------------
	// ファイル名からURLを得る
	function file_to_url ($file) {
	
		$pattern ='!^'.preg_quote(registry('Path.document_root_dir')).'/?!';
		$url =preg_match($pattern,$file) 
				? preg_replace($pattern,"/",$file) 
				: null;
				
		return $url;
	}
	
	//-------------------------------------
	// PageからURLを得る（主にRedirectやHREFに使用）
	function path_to_url ($page) {
	
		$file =path_to_file($page);
		$url =file_to_url($file);
		
		return $url;
	}
	
	//-------------------------------------
	// Pageからファイル名を得る
	function page_to_file ($page) {
	
		$page =relative_page($page);
		$path =page_to_path($page);
		$file =$path
				? registry("Path.html_dir").$path
				: null;
		
		return $file;
	}
	
	//-------------------------------------
	// PageからURLを得る（主にRedirectやHREFに使用）
	function page_to_url ($page) {
	
		$page =relative_page($page);
		$file =page_to_file($page);
		$url =file_to_url($file);
		
		return $url;
	}
	